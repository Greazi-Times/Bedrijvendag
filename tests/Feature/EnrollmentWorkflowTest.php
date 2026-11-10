<?php

use App\Filament\Resources\Enrollments\Pages\ViewEnrollment;
use App\Mail\EnrollmentApprovedMail;
use App\Mail\EnrollmentReceivedMail;
use App\Mail\EnrollmentRejectedMail;
use App\Models\Company;
use App\Models\CompanyProfileSubmission;
use App\Models\Education;
use App\Models\Enrollment;
use App\Models\EnrollmentInvitation;
use App\Models\Sector;
use App\Models\User;
use App\Services\EnrollmentWorkflow;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use Livewire\Livewire;

beforeEach(fn () => Mail::fake());

test('public page renders the published version only and states of closed enrollment', function () {
    $event = openEvent();
    $draft = $event->enrollmentForm->draft_schema;
    $draft['sections'][0]['fields'][] = ['key' => 'draft_only', 'type' => 'text', 'label' => 'Draft only'];
    $event->enrollmentForm->update(['draft_schema' => $draft]);

    $this->get(route('enrollment.show', $event->enrollment_token))->assertOk()->assertInertia(fn (Assert $p) => $p
        ->component('Enrollment/Show')->where('state', 'open')
        ->where('fee.cents', 25000)
        ->whereNot('form.schema', fn ($s) => str_contains(json_encode($s), 'draft_only')));

    $event->update(['enrollment_enabled' => false]);
    $this->get(route('enrollment.show', $event->enrollment_token))->assertInertia(fn (Assert $p) => $p->where('state', 'disabled')->where('form', null));

    $event->update(['enrollment_enabled' => true, 'enrollment_closes_at' => now()->subDay()]);
    $this->get(route('enrollment.show', $event->enrollment_token))->assertInertia(fn (Assert $p) => $p->where('state', 'closed'));

    $this->get('/inschrijven/not-a-token')->assertNotFound();
});

test('closed enrollment refuses submissions', function () {
    $event = openEvent(['enrollment_enabled' => false]);

    $this->post(route('enrollment.store', $event->enrollment_token), enrollmentPayload($event))->assertForbidden();
    expect(Enrollment::count())->toBe(0);
});

test('a new company enrollment stays pending and creates no company', function () {
    $event = openEvent();
    $e = submitEnrollment($event);

    expect($e->status)->toBe('pending')->and($e->info_status)->toBe('not_invited')
        ->and($e->event_id)->toBe($event->id)
        ->and($e->form_version_id)->toBe($event->enrollmentForm->latestVersion->id)
        ->and($e->company_name)->toBe('Acme BV')
        ->and(Company::count())->toBe(0);

    Mail::assertQueued(EnrollmentReceivedMail::class, fn ($m) => $m->hasTo('jane@acme.example'));
});

test('the accepted fee and terms are frozen at submission', function () {
    $event = openEvent();
    $e = submitEnrollment($event);

    $event->update(['fee_cents' => 99900, 'terms_html' => '<p>Terms v2</p>']);

    $e->refresh();
    expect($e->fee_cents_accepted)->toBe(25000)->and($e->currency_accepted)->toBe('EUR')
        ->and($e->terms_version_accepted)->toBe(1)->and($e->terms_html_accepted)->toContain('Terms v1')
        ->and($e->accepted_at)->not->toBeNull();
});

test('submitting without accepting the terms or with a stale form is refused', function () {
    $event = openEvent();

    expect(fn () => submitEnrollment($event, ['terms_accept' => false]))->toThrow(ValidationException::class);
    expect(fn () => submitEnrollment($event, [], ['form_version_id' => 9999]))->toThrow(ValidationException::class);
    expect(Enrollment::count())->toBe(0);
});

test('an existing company enrollment never touches the company', function () {
    $event = openEvent();
    $company = Company::create(['name' => 'Acme BV', 'website_url' => 'https://old.example', 'description_nl' => '<p>Old</p>']);

    $e = submitEnrollment($event, ['company_website' => 'https://new.example'], ['selected_company_id' => $company->id]);

    expect($e->selected_company_id)->toBe($company->id)->and($e->company_id)->toBeNull();
    expect($company->fresh()->website_url)->toBe('https://old.example')->and(Company::count())->toBe(1);
    expect(CompanyProfileSubmission::count())->toBe(0);
});

test('an unknown selected company id is rejected', function () {
    expect(fn () => submitEnrollment(openEvent(), [], ['selected_company_id' => 4242]))->toThrow(ValidationException::class);
});

test('duplicates are caught by company, by name plus contact, and by submission key', function () {
    $event = openEvent();
    $company = Company::create(['name' => 'Acme BV']);

    $first = submitEnrollment($event, [], ['selected_company_id' => $company->id]);
    expect(fn () => submitEnrollment($event, ['contact_email' => 'other@acme.example'], ['selected_company_id' => $company->id]))->toThrow(ValidationException::class);
    expect(fn () => submitEnrollment($event, ['company_name' => 'Acme B.V.']))->toThrow(ValidationException::class); // same name key + same contact
    expect(fn () => submitEnrollment($event, ['company_name' => 'Zed Ltd', 'contact_email' => 'z@z.nl']))->not->toThrow(ValidationException::class);
    expect(fn () => submitEnrollment($event, ['company_name' => 'Zed Ltd', 'contact_email' => 'z@z.nl']))->toThrow(ValidationException::class);

    // identical resubmission (same key) returns the same enrollment and sends no second mail
    $payload = enrollmentPayload($event, ['company_name' => 'Other Corp', 'contact_email' => 'o@o.nl']);
    $a = app(EnrollmentWorkflow::class)->submit($event, $payload);
    $b = app(EnrollmentWorkflow::class)->submit($event, $payload);
    expect($a->id)->toBe($b->id);
    Mail::assertQueued(EnrollmentReceivedMail::class, 3); // first + Zed + Other Corp
    expect($first->id)->not->toBe($a->id);
});

test('similar names with different contacts are both accepted and left to the reviewer', function () {
    $event = openEvent();
    submitEnrollment($event, ['company_name' => 'Acme BV', 'contact_email' => 'a@acme.nl']);

    expect(fn () => submitEnrollment($event, ['company_name' => 'Acme BV', 'contact_email' => 'b@acme.nl']))->not->toThrow(ValidationException::class);
    expect(Enrollment::count())->toBe(2);
});

test('the company search exposes names only and requires a valid link', function () {
    $event = openEvent();
    Company::create(['name' => 'C.S.I Incorporated', 'website_url' => 'https://csi.example', 'profile_contact_email' => 'secret@csi.example']);

    $res = $this->getJson(route('enrollment.search', [$event->enrollment_token, 'q' => 'CSI Incorporated']))->assertOk();
    expect($res->json(0))->toHaveKeys(['id', 'name', 'website_url', 'exact'])->not->toHaveKeys(['profile_contact_email', 'profile_token'])
        ->and($res->json(0)['exact'])->toBeTrue();
    expect($res->getContent())->not->toContain('secret@csi.example');

    $this->getJson(route('enrollment.search', ['bad-token', 'q' => 'CSI']))->assertNotFound();
});

test('selecting a company returns only public profile data', function () {
    $event = openEvent();
    $edu = Education::create(['name' => 'Mechatronica']);
    $company = Company::create(['name' => 'Acme', 'profile_contact_email' => 'secret@acme.example']);
    $company->educations()->sync([$edu->id]);

    $res = $this->getJson(route('enrollment.company', [$event->enrollment_token, $company->id]))->assertOk();

    expect($res->json('education_ids'))->toBe([$edu->id]);
    expect($res->getContent())->not->toContain('secret@acme.example')->not->toContain('profile_token');
});

test('uploads go to private storage and are served only to reviewers', function () {
    Storage::fake('local');
    Storage::fake('public');
    $event = openEvent();
    $e = submitEnrollment($event, ['company_logo' => UploadedFile::fake()->image('logo.png')]);

    $file = $e->files['company_logo'];
    Storage::disk('local')->assertExists($file['path']);
    expect(Storage::disk('public')->allFiles())->toBe([]);

    $this->get(route('enrollment.file', [$e, 'company_logo']))->assertRedirect(); // guests are sent to login

    $this->actingAs(User::factory()->create())->get(route('enrollment.file', [$e, 'company_logo']))->assertForbidden();
    $this->actingAs(reviewer())->get(route('enrollment.file', [$e, 'company_logo']))->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
});

test('approving a new company creates it, links the edition once and invites once', function () {
    Storage::fake('local');
    Storage::fake('public');
    $event = openEvent();
    $edu = Education::create(['name' => 'ICT']);
    $sector = Sector::create(['name' => 'Tech']);
    $e = submitEnrollment($event, ['company_logo' => UploadedFile::fake()->image('l.png'), 'educations' => [(string) $edu->id], 'sectors' => [(string) $sector->id], 'company_website' => 'https://acme.example']);
    $admin = reviewer();

    $workflow = app(EnrollmentWorkflow::class);
    expect($workflow->approve($e, $admin, ['mode' => 'new']))->toBeTrue();
    expect($workflow->approve($e, $admin, ['mode' => 'new']))->toBeFalse(); // repeated click
    expect($workflow->approve($e->fresh(), $admin, ['mode' => 'new']))->toBeFalse();

    $e->refresh();
    $company = Company::sole();
    expect($e->status)->toBe('approved')->and($e->info_status)->toBe('awaiting')
        ->and($e->company_id)->toBe($company->id)->and($e->reviewed_by)->toBe($admin->id)
        ->and($company->website_url)->toBe('https://acme.example')
        ->and($company->educations->pluck('id')->all())->toBe([$edu->id])
        ->and($company->sectors->pluck('id')->all())->toBe([$sector->id])
        ->and($company->logo_path)->not->toBeNull();
    Storage::disk('public')->assertExists($company->logo_path);
    expect($event->approvedCompanies()->pluck('companies.id')->all())->toBe([$company->id]);

    expect(EnrollmentInvitation::count())->toBe(1);
    Mail::assertQueued(EnrollmentApprovedMail::class, 1);
    Mail::assertQueued(EnrollmentApprovedMail::class, function ($m) use ($e) {
        $inv = EnrollmentInvitation::sole();

        return $inv->token_hash === EnrollmentInvitation::hash($m->token) && $inv->token_hash !== $m->token && $m->hasTo($e->contact_email);
    });
});

test('creating a company that exactly matches an existing one needs explicit confirmation', function () {
    $existing = Company::create(['name' => 'Acme B.V.']);
    $e = submitEnrollment(openEvent(), ['company_name' => 'ACME BV']);

    expect(fn () => app(EnrollmentWorkflow::class)->approve($e, reviewer(), ['mode' => 'new']))->toThrow(ValidationException::class);
    expect($e->fresh()->status)->toBe('pending')->and(Company::count())->toBe(1);
    Mail::assertNotQueued(EnrollmentApprovedMail::class);

    expect(app(EnrollmentWorkflow::class)->approve($e, reviewer(), ['mode' => 'new', 'confirm_distinct' => true]))->toBeTrue();
    expect(Company::count())->toBe(2)->and($existing->fresh()->name)->toBe('Acme B.V.');
});

test('approving against an existing company keeps its data and queues differences for review', function () {
    $event = openEvent();
    $company = Company::create(['name' => 'Acme BV', 'website_url' => 'https://old.example', 'description_nl' => '<p>Old text</p>']);
    $e = submitEnrollment($event, ['company_website' => 'https://new.example', 'company_description' => 'New text'], ['selected_company_id' => $company->id]);

    app(EnrollmentWorkflow::class)->approve($e, reviewer(), ['mode' => 'existing', 'company_id' => $company->id]);

    $company->refresh();
    expect($company->website_url)->toBe('https://old.example')->and($company->description_nl)->toContain('Old text');
    $sub = CompanyProfileSubmission::sole();
    expect($sub->status)->toBe('pending')->and($sub->proposed_website_url)->toBe('https://new.example')->and($sub->company_id)->toBe($company->id)
        ->and($e->fresh()->profile_submission_id)->toBe($sub->id)->and($e->fresh()->company_id)->toBe($company->id);
});

test('identical information produces no profile review item', function () {
    $company = Company::create(['name' => 'Acme BV', 'website_url' => 'https://acme.example', 'description_nl' => 'We build things.']);
    $e = submitEnrollment(openEvent(), ['company_website' => 'https://acme.example'], ['selected_company_id' => $company->id]);

    app(EnrollmentWorkflow::class)->approve($e, reviewer(), ['mode' => 'existing', 'company_id' => $company->id]);

    expect(CompanyProfileSubmission::count())->toBe(0);
});

test('markup-only differences do not create a profile review item', function () {
    $company = Company::create(['name' => 'Acme BV', 'website_url' => 'https://acme.example', 'description_nl' => '<p>We build things.</p>']);
    $e = submitEnrollment(openEvent(), ['company_website' => 'https://acme.example'], ['selected_company_id' => $company->id]);

    app(EnrollmentWorkflow::class)->approve($e, reviewer(), ['mode' => 'existing', 'company_id' => $company->id]);

    expect(CompanyProfileSubmission::count())->toBe(0);
});

test('one company cannot hold two approved enrollments for an edition, but may for another', function () {
    $event = openEvent();
    $company = Company::create(['name' => 'Acme BV']);
    $first = submitEnrollment($event, [], ['selected_company_id' => $company->id]);
    $second = submitEnrollment($event, ['company_name' => 'Acme Group', 'contact_email' => 'x@acme.example']);
    $workflow = app(EnrollmentWorkflow::class);
    $admin = reviewer();

    $workflow->approve($first, $admin, ['mode' => 'existing', 'company_id' => $company->id]);
    expect(fn () => $workflow->approve($second, $admin, ['mode' => 'existing', 'company_id' => $company->id]))->toThrow(ValidationException::class);
    expect($second->fresh()->status)->toBe('pending');
    Mail::assertQueued(EnrollmentApprovedMail::class, 1);

    $other = openEvent(['name' => 'Next']);
    $third = submitEnrollment($other, [], ['selected_company_id' => $company->id]);
    expect($workflow->approve($third, $admin, ['mode' => 'existing', 'company_id' => $company->id]))->toBeTrue();
});

test('a failed approval rolls back and sends nothing', function () {
    $e = submitEnrollment(openEvent());

    expect(fn () => app(EnrollmentWorkflow::class)->approve($e, reviewer(), ['mode' => 'existing', 'company_id' => 777]))->toThrow(ValidationException::class);

    expect($e->fresh()->status)->toBe('pending')->and(EnrollmentInvitation::count())->toBe(0)->and(Company::count())->toBe(0);
    Mail::assertNotQueued(EnrollmentApprovedMail::class);
});

test('rejecting stores the reason, mails it, creates nothing and is idempotent', function () {
    $e = submitEnrollment(openEvent());
    $admin = reviewer();
    $workflow = app(EnrollmentWorkflow::class);

    expect($workflow->reject($e, $admin, 'Sold out'))->toBeTrue();
    expect($workflow->reject($e, $admin, 'Again'))->toBeFalse();
    expect($workflow->approve($e->fresh(), $admin, ['mode' => 'new']))->toBeFalse();

    $e->refresh();
    expect($e->status)->toBe('rejected')->and($e->rejection_reason)->toBe('Sold out')->and($e->reviewed_by)->toBe($admin->id)
        ->and(Company::count())->toBe(0);
    Mail::assertQueued(EnrollmentRejectedMail::class, 1);
    Mail::assertQueued(EnrollmentRejectedMail::class, fn ($m) => $m->enrollment->rejection_reason === 'Sold out');
    Mail::assertNotQueued(EnrollmentApprovedMail::class);
});

test('the review screen is forbidden without permission and approve works with it', function () {
    $e = submitEnrollment(openEvent());

    $this->actingAs(reviewerWithout());
    Livewire::test(ViewEnrollment::class, ['record' => $e->getKey()])->assertForbidden();

    $this->actingAs(reviewer());
    Livewire::test(ViewEnrollment::class, ['record' => $e->getKey()])->assertSuccessful()
        ->callAction('approve', ['mode' => 'new'])->assertNotified();

    expect($e->fresh()->status)->toBe('approved');
});

test('viewers without the review permission cannot approve or reject', function () {
    $e = submitEnrollment(openEvent());
    $viewer = reviewerWithout(['enrollments.view']);
    $this->actingAs($viewer);

    Livewire::test(ViewEnrollment::class, ['record' => $e->getKey()])->assertSuccessful()
        ->assertActionHidden('approve')->assertActionHidden('reject');
});
