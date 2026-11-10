<?php

use App\Filament\Resources\Enrollments\Pages\ViewEnrollment;
use App\Mail\EnrollmentApprovedMail;
use App\Mail\EnrollmentInfoReceivedMail;
use App\Mail\EnrollmentInvitationMail;
use App\Models\Company;
use App\Models\CompanyProfileSubmission;
use App\Models\Education;
use App\Models\Enrollment;
use App\Models\EnrollmentInvitation;
use App\Services\InformationWorkflow;
use App\Services\InvitationService;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use Livewire\Livewire;

test('a valid token shows the published information form and prefills contact details', function () {
    [$e, $token, $event] = approvedEnrollment();
    $draft = $event->infoForm->draft_schema;
    $draft['sections'][0]['fields'][] = ['key' => 'draft_only', 'type' => 'text', 'label' => 'Draft only'];
    $event->infoForm->update(['draft_schema' => $draft]);

    $this->get(route('information.show', $token))->assertOk()->assertInertia(fn (Assert $p) => $p
        ->component('Information/Show')->where('state', 'ready')
        ->where('company.name', 'Acme BV')->where('initial.contact_email', 'jane@acme.example')
        ->where('settings.max_lunch_participants', 3)->where('submitted', null)
        ->whereNot('form.schema', fn ($s) => str_contains(json_encode($s), 'draft_only')));
});

test('invalid, expired and revoked tokens give a message and expose nothing', function () {
    [$e, $token] = approvedEnrollment();

    $this->get(route('information.show', 'x'.str_repeat('a', 47)))->assertInertia(fn (Assert $p) => $p->where('state', 'invalid')->missing('company')->missing('form'));
    $this->get(route('information.show', 'short'))->assertInertia(fn (Assert $p) => $p->where('state', 'invalid'));

    $e->invitations()->update(['expires_at' => now()->subMinute()]);
    $this->get(route('information.show', $token))->assertInertia(fn (Assert $p) => $p->where('state', 'expired')->missing('company')->missing('form'));

    $e->invitations()->update(['expires_at' => now()->addDay(), 'revoked_at' => now()]);
    $this->get(route('information.show', $token))->assertInertia(fn (Assert $p) => $p->where('state', 'revoked')->missing('company'));

    expect(fn () => app(InformationWorkflow::class)->submit($token, infoPayload($e->event)))->toThrow(Symfony\Component\HttpKernel\Exception\HttpException::class);
});

test('only approved enrollments can open the form', function () {
    Mail::fake();
    $event = openEvent();
    $pending = submitEnrollment($event);
    [$invitation, $token] = EnrollmentInvitation::issue($pending);

    $this->get(route('information.show', $token))->assertInertia(fn (Assert $p) => $p->where('state', 'invalid')->missing('company'));

    $pending->forceFill(['status' => 'rejected'])->save();
    $this->get(route('information.show', $token))->assertInertia(fn (Assert $p) => $p->where('state', 'invalid'));
});

test('a company submits the information form and is notified', function () {
    [$e, $token, $event] = approvedEnrollment();
    Mail::fake();

    $this->post(route('information.store', $token), infoPayload($event, ['lunch_participants' => [['lunch_name' => 'Ann', 'lunch_diet' => ['vegan', 'halal']]]], ['choice' => 'confirmed']))
        ->assertRedirect(route('information.show', $token));

    $sub = $e->infoSubmissions()->sole();
    expect($sub->revision)->toBe(1)->and($sub->form_version_id)->toBe($event->infoForm->latestVersion->id)
        ->and($sub->answers['lunch_participants'][0]['lunch_diet'])->toBe(['vegan', 'halal'])
        ->and($sub->profile_status)->toBe('confirmed')
        ->and($sub->answers['company_verification'])->toBe('confirmed');
    expect($e->fresh()->info_status)->toBe('submitted');
    Mail::assertQueued(EnrollmentInfoReceivedMail::class, fn ($m) => $m->hasTo('jane@acme.example'));

    $this->get(route('information.show', $token))->assertInertia(fn (Assert $p) => $p->where('submitted.revision', 1)->where('initial.drinks_attending', 'no'));
});

test('repeating the identical request creates nothing, an edit creates a new revision and keeps the old one', function () {
    [$e, $token, $event] = approvedEnrollment();
    Mail::fake();

    $first = submitInfo($token, $event);
    $again = submitInfo($token, $event);
    expect($again->id)->toBe($first->id)->and($e->infoSubmissions()->count())->toBe(1);
    Mail::assertQueued(EnrollmentInfoReceivedMail::class, 1);

    $second = submitInfo($token, $event, ['drinks_attending' => 'yes', 'drinks_count' => 2]);
    expect($second->revision)->toBe(2)->and($e->infoSubmissions()->count())->toBe(2)
        ->and($e->infoSubmissions()->get()->last()->answers['drinks_attending'])->toBe('no');
    Mail::assertQueued(EnrollmentInfoReceivedMail::class, 2);
});

test('editing after the form changed keeps old revisions with their own version', function () {
    [$e, $token, $event] = approvedEnrollment();
    $v1 = $event->infoForm->latestVersion;
    $first = submitInfo($token, $event, ['drinks_attending' => 'yes', 'drinks_count' => 3]);

    $draft = $event->infoForm->draft_schema;
    $draft['sections'][2]['fields'] = array_values(array_filter($draft['sections'][2]['fields'], fn ($f) => $f['key'] !== 'drinks_count'));
    $draft['sections'][2]['fields'][0]['label'] = 'Renamed question';
    $draft['sections'][3]['fields'][] = ['key' => 'extra_q', 'type' => 'text', 'label' => 'Extra'];
    $event->infoForm->update(['draft_schema' => $draft]);
    $v2 = $event->infoForm->fresh()->publish();

    // the page prefills the old answers by stable key
    $this->get(route('information.show', $token))->assertInertia(fn (Assert $p) => $p->where('form.version_id', $v2->id)->where('initial.drinks_attending', 'yes'));

    // a stale page (old version id) must reload instead of writing against the wrong schema
    expect(fn () => app(InformationWorkflow::class)->submit($token, infoPayload($event, [], [], ['form_version_id' => $v1->id])))->toThrow(ValidationException::class);

    $second = app(InformationWorkflow::class)->submit($token, ['form_version_id' => $v2->id, 'answers' => infoAnswers(['drinks_attending' => 'no', 'extra_q' => 'hi']), 'verification' => ['choice' => 'confirmed']]);

    expect($second->form_version_id)->toBe($v2->id)->and($second->answers)->toHaveKey('extra_q')
        ->and($first->fresh()->form_version_id)->toBe($v1->id)->and($first->fresh()->answers['drinks_count'])->toBe(3)
        ->and($e->infoSubmissions()->count())->toBe(2);
});

test('conditional questions, repeaters and the edition lunch limit are enforced', function () {
    [$e, $token, $event] = approvedEnrollment();
    $event->update(['max_lunch_participants' => 2]);

    $row = fn (array $diet, ?string $other = null) => ['lunch_name' => 'P', 'lunch_diet' => $diet] + ($other ? ['lunch_diet_other' => $other] : []);

    expect(fn () => submitInfo($token, $event, ['lunch_participants' => [$row(['none']), $row(['none']), $row(['none'])]]))->toThrow(ValidationException::class);
    expect(fn () => submitInfo($token, $event, ['lunch_participants' => [$row(['other'])]]))->toThrow(ValidationException::class); // "other" needs text
    expect(fn () => submitInfo($token, $event, ['drinks_attending' => 'yes', 'drinks_count' => 0]))->toThrow(ValidationException::class); // a given count must be valid; it is optional
    expect(fn () => submitInfo($token, $event, ['lunch_participants' => [$row(['lasagne'])]]))->toThrow(ValidationException::class); // not an option

    $sub = submitInfo($token, $event, ['lunch_participants' => [$row(['other'], 'Nut allergy'), $row(['none'], 'ignored')]]);
    expect($sub->answers['lunch_participants'][0]['lunch_diet_other'])->toBe('Nut allergy')
        ->and($sub->answers['lunch_participants'][1])->not->toHaveKey('lunch_diet_other');
});

test('after the deadline the form is closed or read-only and writes are refused', function () {
    [$e, $token, $event] = approvedEnrollment();

    $event->update(['info_closes_at' => now()->subDay()]);
    $this->get(route('information.show', $token))->assertInertia(fn (Assert $p) => $p->where('state', 'closed')->missing('form'));
    $this->post(route('information.store', $token), infoPayload($event))->assertForbidden();

    $event->update(['info_closes_at' => now()->addDay()]);
    submitInfo($token, $event);
    $event->update(['info_closes_at' => now()->subHour()]);
    $this->get(route('information.show', $token))->assertInertia(fn (Assert $p) => $p->where('state', 'locked'));
    $this->post(route('information.store', $token), infoPayload($event, ['drinks_attending' => 'unsure']))->assertForbidden();
    expect($e->infoSubmissions()->count())->toBe(1);
});

test('profile verification: confirming creates no review item', function () {
    $company = Company::create(['name' => 'Acme BV', 'website_url' => 'https://acme.example', 'description_nl' => 'We build things.']);
    [$e, $token, $event] = approvedEnrollment(null, ['company_website' => 'https://acme.example'], $company);

    $sub = submitInfo($token, $event, [], ['choice' => 'confirmed']);

    expect($sub->profile_status)->toBe('confirmed')->and(CompanyProfileSubmission::where('company_id', $company->id)->count())->toBe(0);
});

test('profile corrections go to the existing review queue and never change the live profile', function () {
    Storage::fake('public');
    $edu = Education::create(['name' => 'ICT']);
    $company = Company::create(['name' => 'Acme BV', 'website_url' => 'https://old.example', 'description_nl' => '<p>Old text</p>']);
    [$e, $token, $event] = approvedEnrollment(null, ['company_description' => 'Old text', 'company_website' => 'https://old.example'], $company);

    $sub = submitInfo($token, $event, [], ['choice' => 'correction', 'description' => 'New text', 'website_url' => 'https://new.example', 'education_ids' => [$edu->id], 'logo' => UploadedFile::fake()->image('l.png')]);

    $company->refresh();
    expect($company->website_url)->toBe('https://old.example')->and($company->description_nl)->toContain('Old text')->and($company->educations)->toHaveCount(0);

    $review = CompanyProfileSubmission::sole();
    expect($review->status)->toBe('pending')->and($review->company_id)->toBe($company->id)
        ->and($review->proposed_website_url)->toBe('https://new.example')->and($review->proposed_education_ids)->toBe([$edu->id])
        ->and($sub->fresh()->profile_status)->toBe('correction_requested')->and($sub->fresh()->profile_submission_id)->toBe($review->id);
    Storage::disk('public')->assertExists($review->proposed_logo_path);
});

test('a correction with no real difference or only wrapper markup differences creates no review item', function () {
    $company = Company::create(['name' => 'Acme BV', 'website_url' => 'https://acme.example', 'description_nl' => '<p>We build things.</p>']);
    [$e, $token, $event] = approvedEnrollment(null, ['company_website' => 'https://acme.example'], $company);

    // nothing really differs: refused with "no changes to submit" instead of creating a review item
    expect(fn () => submitInfo($token, $event, [], ['choice' => 'correction', 'description' => 'We build things.', 'website_url' => 'https://acme.example']))->toThrow(ValidationException::class);

    expect(CompanyProfileSubmission::count())->toBe(0)->and($e->infoSubmissions()->count())->toBe(0);
});

test('a correction request must contain something and valid values', function () {
    [$e, $token, $event] = approvedEnrollment();

    expect(fn () => submitInfo($token, $event, [], ['choice' => 'correction']))->toThrow(ValidationException::class);
    expect(fn () => submitInfo($token, $event, [], ['choice' => 'correction', 'website_url' => 'javascript:alert(1)']))->toThrow(ValidationException::class);
    expect(fn () => submitInfo($token, $event, [], ['choice' => 'correction', 'sector_ids' => [9999]]))->toThrow(ValidationException::class);
    expect(fn () => submitInfo($token, $event, [], ['choice' => 'correction', 'logo' => UploadedFile::fake()->create('x.php', 5, 'application/x-php')]))->toThrow(ValidationException::class);
    expect($e->infoSubmissions()->count())->toBe(0);
});

test('a newer correction supersedes the pending one through the existing mechanism', function () {
    $company = Company::create(['name' => 'Acme BV', 'website_url' => 'https://old.example', 'description_nl' => 'We build things.']);
    [$e, $token, $event] = approvedEnrollment(null, ['company_website' => 'https://old.example'], $company);

    submitInfo($token, $event, [], ['choice' => 'correction', 'website_url' => 'https://one.example']);
    submitInfo($token, $event, [], ['choice' => 'correction', 'website_url' => 'https://two.example']);

    $all = CompanyProfileSubmission::orderBy('id')->get();
    expect($all->pluck('status')->all())->toBe(['superseded', 'pending'])->and($all->last()->proposed_website_url)->toBe('https://two.example')
        ->and($all->last()->revision)->toBe(2);
});

test('one company cannot reach or change another companys data', function () {
    $event = openEvent();
    [$a, $tokenA] = approvedEnrollment($event, ['company_name' => 'Alpha BV', 'contact_email' => 'a@alpha.example']);
    [$b, $tokenB] = approvedEnrollment($event, ['company_name' => 'Beta BV', 'contact_email' => 'b@beta.example']);
    $beta = Company::where('name', 'Beta BV')->first();

    // attacker-supplied identifiers are ignored: the enrollment comes from the token alone
    app(InformationWorkflow::class)->submit($tokenA, infoPayload($event, [], ['choice' => 'correction', 'website_url' => 'https://evil.example'], ['enrollment_id' => $b->id, 'company_id' => $beta->id]));

    expect($b->infoSubmissions()->count())->toBe(0)->and($a->infoSubmissions()->count())->toBe(1);
    expect($beta->fresh()->website_url)->toBeNull();
    $review = CompanyProfileSubmission::sole();
    expect($review->company_id)->toBe($a->fresh()->company_id)->not->toBe($beta->id);

    $this->get(route('information.show', $tokenA))->assertInertia(fn (Assert $p) => $p->where('company.name', 'Alpha BV'));
    $this->get(route('information.show', $tokenB))->assertInertia(fn (Assert $p) => $p->where('company.name', 'Beta BV')->where('submitted', null));
});

test('reissuing replaces the link, leaves exactly one valid invitation and keeps answers', function () {
    [$e, $old, $event] = approvedEnrollment();
    submitInfo($old, $event);
    Mail::fake();
    $service = app(InvitationService::class);

    // inside the cooldown a repeated click does not mint another link
    expect(fn () => $service->reissue($e))->toThrow(ValidationException::class);
    expect($e->invitations()->count())->toBe(1);

    $service->reissue($e, force: true);
    $new = null;
    Mail::assertQueued(EnrollmentInvitationMail::class, function ($m) use (&$new) {
        $new = $m->token;

        return true;
    });

    expect($e->invitations()->count())->toBe(2)->and($e->invitations()->whereNull('revoked_at')->count())->toBe(1);
    $this->get(route('information.show', $old))->assertInertia(fn (Assert $p) => $p->where('state', 'revoked'));
    $this->get(route('information.show', $new))->assertInertia(fn (Assert $p) => $p->where('state', 'ready')->where('submitted.revision', 1));
    expect($e->infoSubmissions()->count())->toBe(1);
});

test('reissue after the cooldown, regeneration of an expired link, and revocation', function () {
    [$e, $token, $event] = approvedEnrollment();
    $service = app(InvitationService::class);
    Mail::fake();

    $e->invitations()->update(['created_at' => now()->subMinutes(10)]);
    $service->reissue($e->fresh());
    expect($e->invitations()->whereNull('revoked_at')->count())->toBe(1);

    $e->invitations()->whereNull('revoked_at')->update(['expires_at' => now()->subDay()]);
    expect($e->fresh()->activeInvitation())->toBeNull();
    $service->reissue($e->fresh(), force: true);
    expect($e->fresh()->activeInvitation())->not->toBeNull();

    expect($service->revoke($e->fresh()))->toBe(1)->and($e->fresh()->activeInvitation())->toBeNull();
    expect($service->revoke($e->fresh()))->toBe(0);
    Mail::assertQueued(EnrollmentInvitationMail::class, 2);
});

test('only approved enrollments can be invited', function () {
    $pending = submitEnrollment(openEvent());
    Mail::fake();

    expect(fn () => app(InvitationService::class)->reissue($pending, force: true))->toThrow(ValidationException::class);
    Mail::assertNothingQueued();
});

test('token bearing mails are encrypted in the queue and the plain token never lands in the jobs table', function () {
    expect(EnrollmentApprovedMail::class)->toImplement(ShouldBeEncrypted::class);
    expect(EnrollmentInvitationMail::class)->toImplement(ShouldBeEncrypted::class);

    config(['queue.default' => 'database']);
    $event = openEvent();
    $e = submitEnrollment($event);
    $token = 'PlainTokenPlainTokenPlainTokenPlainTokenPlainToken12';

    Mail::to('a@b.nl')->queue(new EnrollmentInvitationMail($e->load('event'), $token));

    $payload = DB::table('jobs')->latest('id')->value('payload');
    expect($payload)->not->toBeNull()->and($payload)->not->toContain($token);
});

test('a mail dispatch failure is logged without secrets and does not undo the invitation; a retry works', function () {
    [$e, $token, $event] = approvedEnrollment();
    $e->invitations()->update(['created_at' => now()->subMinutes(10)]);

    Log::spy();
    Mail::shouldReceive('to')->andThrow(new RuntimeException('smtp down'));

    $invitation = app(InvitationService::class)->reissue($e->fresh());

    expect($invitation->fresh()->queued_at)->toBeNull()->and($e->fresh()->activeInvitation()->id)->toBe($invitation->id);
    Log::shouldHaveReceived('error')->withArgs(fn ($message, $context) => $message === 'Could not queue enrollment email.' && ! str_contains(json_encode($context), 'jane@acme.example'))->once();

    // admin retries: replacement link, this time through a working mailer
    Mail::swap(new Illuminate\Mail\MailManager(app()));
    Mail::fake();
    app(InvitationService::class)->reissue($e->fresh(), force: true);
    Mail::assertQueued(EnrollmentInvitationMail::class, 1);
    expect($e->invitations()->whereNull('revoked_at')->count())->toBe(1);
});

test('a failed queued mail job is logged by the mailable', function () {
    $e = submitEnrollment(openEvent());
    Log::spy();

    (new EnrollmentInvitationMail($e->load('event'), 'secret-token-value'))->failed(new RuntimeException('boom'));

    Log::shouldHaveReceived('error')->withArgs(fn ($message, $context) => $message === 'Enrollment email failed.' && ! str_contains(json_encode($context), 'secret-token-value'))->once();
});

test('the database refuses two approved enrollments of one company for an edition', function () {
    $event = openEvent();
    $company = Company::create(['name' => 'Acme BV']);
    $one = submitEnrollment($event);
    $two = submitEnrollment($event, ['company_name' => 'Other', 'contact_email' => 'o@o.nl']);

    $one->forceFill(['approved_company_id' => $company->id])->save();

    expect(fn () => $two->forceFill(['approved_company_id' => $company->id])->save())->toThrow(Illuminate\Database\UniqueConstraintViolationException::class);
});

test('admins see second form answers and invitation status, and invitation actions are permission gated', function () {
    [$e, $token, $event] = approvedEnrollment();
    submitInfo($token, $event, ['lunch_participants' => [['lunch_name' => 'Ann', 'lunch_diet' => ['vegan']]], 'drinks_attending' => 'unsure'], ['choice' => 'confirmed']);
    Mail::fake();

    $this->actingAs(reviewerWithout(['enrollments.view']));
    Livewire::test(ViewEnrollment::class, ['record' => $e->getKey()])->assertSuccessful()
        ->assertSee('Revision')->assertSee('Confirmed correct')->assertSee('Active')
        ->assertActionHidden('resendInvitation')->assertActionHidden('revokeInvitation')->assertActionHidden('regenerateInvitation');

    $this->actingAs(reviewer());
    $e->invitations()->update(['created_at' => now()->subMinutes(10)]);
    Livewire::test(ViewEnrollment::class, ['record' => $e->getKey()])
        ->assertActionVisible('resendInvitation')->callAction('resendInvitation')->assertNotified()
        ->assertSee('Vegan');
    Mail::assertQueued(EnrollmentInvitationMail::class, 1);

    Livewire::test(ViewEnrollment::class, ['record' => $e->getKey()])->callAction('revokeInvitation')->assertNotified();
    expect($e->fresh()->activeInvitation())->toBeNull();
    Livewire::test(ViewEnrollment::class, ['record' => $e->getKey()])->assertActionVisible('regenerateInvitation');
});

test('second form files are served privately to staff only', function () {
    Storage::fake('local');
    [$e, $token, $event] = approvedEnrollment();
    $draft = $event->infoForm->draft_schema;
    $draft['sections'][3]['fields'][] = ['key' => 'floorplan', 'type' => 'file', 'label' => 'Floor plan'];
    $event->infoForm->update(['draft_schema' => $draft]);
    $event->infoForm->fresh()->publish();

    $sub = submitInfo($token, $event->fresh(), ['floorplan' => UploadedFile::fake()->image('plan.png')]);
    Storage::disk('local')->assertExists($sub->files['floorplan']['path']);

    $this->get(route('enrollment.info-file', [$sub, 'floorplan']))->assertRedirect();
    $this->actingAs(reviewer())->get(route('enrollment.info-file', [$sub, 'floorplan']))->assertOk();
});
