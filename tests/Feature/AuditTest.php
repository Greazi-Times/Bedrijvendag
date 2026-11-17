<?php

use App\Filament\Resources\Enrollments\Pages\ListEnrollments;
use App\Filament\Resources\Enrollments\Pages\ViewEnrollment;
use App\Filament\Resources\Events\Pages\EditEvent;
use App\Filament\Resources\EventStands\Pages\ManageStands;
use App\Filament\Resources\Forms\Pages\EditForm;
use App\Filament\Resources\Forms\Pages\ListForms;
use App\Mail\EnrollmentApprovedMail;
use App\Mail\EnrollmentInfoReceivedMail;
use App\Mail\EnrollmentInvitationMail;
use App\Mail\EnrollmentReceivedMail;
use App\Mail\EnrollmentRejectedMail;
use App\Models\Company;
use App\Models\Enrollment;
use App\Models\Event;
use App\Models\EventStand;
use App\Models\Form;
use App\Models\Partner;
use App\Services\CompanyMatcher;
use App\Services\EnrollmentWorkflow;
use App\Services\InvitationService;
use App\Services\StandEligibility;
use App\Support\Forms\DefaultForms;
use App\Support\Forms\FormEngine;
use App\Support\Forms\FormSchema;
use App\Support\LogRedactor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

/* ---------------------------------------------------------------- stand counts */

test('lowering the stand count past an occupied stand is refused and nothing changes', function () {
    $event = standEvent(['max_stands' => 5]);
    $company = approvedFor($event, 'Acme BV');
    emptyStand($event, 4)->update(['company_id' => $company->id]);
    emptyStand($event, 5);

    $error = null;
    try {
        $event->update(['max_stands' => 3]);
    } catch (ValidationException $e) {
        $error = $e->errors()['max_stands'][0] ?? null;
    }

    expect($error)->toContain('4 (Acme BV)')->toContain('Clear or move it first');
    expect($event->fresh()->max_stands)->toBe(5)->and(EventStand::where('event_id', $event->id)->count())->toBe(2);
});

test('lowering the count is allowed when only empty stands are affected, and raising is always allowed', function () {
    $event = standEvent(['max_stands' => 5]);
    $company = approvedFor($event, 'Acme BV');
    emptyStand($event, 2)->update(['company_id' => $company->id]);
    emptyStand($event, 5);

    $event->update(['max_stands' => 3]);
    expect($event->fresh()->max_stands)->toBe(3);

    $event->update(['max_stands' => 8]);
    expect($event->fresh()->max_stands)->toBe(8);
});

test('the same protection applies to partner stands', function () {
    $event = standEvent(['partner_stand_count' => 3]);
    $partner = Partner::create(['name' => 'Partner Inc', 'description' => 'x', 'logo' => 'p.png']);
    EventStand::create(['event_id' => $event->id, 'type' => 'partner', 'stand_number' => '3', 'partner_id' => $partner->id]);

    expect(fn () => $event->update(['partner_stand_count' => 2]))->toThrow(ValidationException::class);
    expect($event->fresh()->partner_stand_count)->toBe(3);
});

test('a bulk change of the count cannot destroy an occupied stand either', function () {
    $event = standEvent(['max_stands' => 5]);
    $company = approvedFor($event, 'Acme BV');
    $occupied = emptyStand($event, 3);
    $occupied->update(['company_id' => $company->id, 'x_percent' => 40, 'y_percent' => 50]);
    emptyStand($event, 4);
    emptyStand($event, 5);

    Event::query()->whereKey($event->id)->update(['max_stands' => 2]); // skips model events on purpose
    $this->actingAs(reviewerWithout(['event-stands.view', 'event-stands.update']));
    Livewire::test(ManageStands::class)->set('selectedEventId', $event->id);

    $numbers = EventStand::where('event_id', $event->id)->where('type', 'company')->orderBy('stand_number')->pluck('stand_number')->map(fn ($n) => (int) $n)->all();
    expect($numbers)->toBe([1, 2, 3])->and($occupied->fresh()->company_id)->toBe($company->id)->and($occupied->fresh()->x_percent)->toBe(40.0);
});

test('the edition form shows the error and keeps the count', function () {
    $event = standEvent(['max_stands' => 5]);
    $company = approvedFor($event, 'Acme BV');
    emptyStand($event, 5)->update(['company_id' => $company->id]);
    $this->actingAs(reviewerWithout(['events.view', 'events.update']));

    Livewire::test(EditEvent::class, ['record' => $event->getKey()])->fillForm(['max_stands' => 2])->call('save')->assertHasFormErrors(['max_stands']);

    expect($event->fresh()->max_stands)->toBe(5);
});

test('changing an edition date moves it in or out of the eligibility rules without touching stands', function () {
    $event = standEvent();
    $stand = legacyStand($event, Company::create(['name' => 'Legacy BV']));
    $eligibility = app(StandEligibility::class);

    expect($eligibility->flaggedStands($event)->count())->toBe(1);

    $event->update(['date' => '2020-01-01']);
    expect($eligibility->flaggedStands($event->fresh())->count())->toBe(0);
    expect(assignFails(emptyStand($event, 2), Company::create(['name' => 'Anyone'])))->toBeTrue(); // historical: needs the override

    $event->update(['date' => '2030-01-01']);
    expect($eligibility->flaggedStands($event->fresh())->count())->toBe(1)->and($stand->fresh()->company_id)->not->toBeNull();
});

/* ---------------------------------------------------------------- form builder protections */

test('mapped system fields cannot be retyped, hidden behind a condition or lose their option source', function () {
    $schema = fn (callable $edit) => tap(DefaultForms::enrollment(), fn (&$s) => $edit($s));
    $problems = fn (array $s) => implode('|', FormSchema::problems($s, 'enrollment'));

    expect($problems($schema(function (&$s) {
        $s['sections'][0]['fields'][0]['type'] = 'info';
    })))->toContain('must be a text question');
    expect($problems($schema(function (&$s) {
        $s['sections'][1]['fields'][0]['type'] = 'text';
    })))->toContain('must be a email question');
    expect($problems($schema(function (&$s) {
        $s['sections'][3]['fields'][0]['type'] = 'checkbox';
    })))->toContain('must be a terms question');
    expect($problems($schema(function (&$s) {
        $s['sections'][0]['fields'][0]['visible_if'] = ['field' => 'x', 'operator' => 'filled'];
    })))->toContain('cannot be conditional');
    expect($problems($schema(function (&$s) {
        $s['sections'][0]['fields'][4]['options_source'] = null;
        $s['sections'][0]['fields'][4]['options'] = [['value' => 'a', 'label' => 'A']];
    })))->toContain('must keep its options from the educations list');
    expect($problems(DefaultForms::enrollment()))->toBe('');
});

test('an unsafe form cannot be published and the published version stays untouched', function () {
    $event = standEvent();
    $form = $event->enrollmentForm;
    $v1 = $form->latestVersion;
    $draft = $form->draft_schema;
    $draft['sections'][0]['fields'][0]['type'] = 'info';
    $form->update(['draft_schema' => $draft]);

    expect(fn () => $form->fresh()->publish())->toThrow(ValidationException::class);

    expect($form->versions()->count())->toBe(1)->and($form->fresh()->latestVersion->schema)->toBe($v1->schema);
});

test('a company logo accepts images only while other file questions still accept pdf', function () {
    $engine = new FormEngine;
    $schema = DefaultForms::enrollment();
    $pdf = fn () => UploadedFile::fake()->create('logo.pdf', 20, 'application/pdf');

    expect(fn () => $engine->validate($schema, enrollmentAnswers(['company_logo' => $pdf()])))->toThrow(ValidationException::class);
    expect(fn () => $engine->validate($schema, enrollmentAnswers(['company_logo' => UploadedFile::fake()->create('logo.png', 20, 'application/x-php')])))->toThrow(ValidationException::class);

    $schema['sections'][3]['fields'][] = ['key' => 'extra_doc', 'type' => 'file', 'label' => 'Document'];
    expect($engine->validate($schema, enrollmentAnswers(['extra_doc' => $pdf()])))->toHaveKey('extra_doc');
});

test('a form that companies have answered cannot be deleted', function () {
    $event = standEvent();
    submitEnrollment($event);
    $form = $event->enrollmentForm;
    DB::table('events')->where('id', $event->id)->update(['enrollment_form_id' => null]);

    expect($form->fresh()->isInUse())->toBeTrue();
    expect(fn () => $form->fresh()->delete())->toThrow(LogicException::class);

    $this->actingAs(reviewerWithout(['forms.view', 'forms.update', 'forms.delete']));
    Livewire::test(EditForm::class, ['record' => $form->getKey()])->assertActionHidden('delete');
});

test('answers given under an older form version stay readable after the form changes', function () {
    $event = standEvent();
    $e = submitEnrollment($event, ['company_name' => 'Old Version BV', 'contact_email' => 'o@v.example']);

    $form = $event->enrollmentForm;
    $draft = $form->draft_schema;
    $draft['sections'][0]['fields'][0]['label'] = 'Organisation';          // renamed
    $draft['sections'][0]['fields'][2]['label'] = 'Logo file';
    $draft['sections'][0]['fields'] = array_values(array_filter($draft['sections'][0]['fields'], fn ($f) => $f['key'] !== 'company_website')); // removed
    $form->update(['draft_schema' => $draft]);
    expect(fn () => $form->fresh()->publish())->toThrow(ValidationException::class); // website is a mandatory system field

    $draft['sections'][0]['fields'][] = ['key' => 'company_website', 'type' => 'url', 'label' => 'Web', 'maps_to' => 'company.website'];
    $form->update(['draft_schema' => $draft]);
    $form->fresh()->publish();

    $this->actingAs(reviewer());
    Livewire::test(ViewEnrollment::class, ['record' => $e->getKey()])->assertSee('Company name')->assertSee('Old Version BV')->assertDontSee('Organisation');
    expect($e->fresh()->formVersion->version)->toBe(1)->and($e->fresh()->mapped('company.name'))->toBe('Old Version BV');
});

/* ---------------------------------------------------------------- concurrency, deletion, files */

test('a busy approval lock gives a clear retryable error and changes nothing', function () {
    $e = submitEnrollment(standEvent());
    config(['enrollment.lock_wait' => 0]);
    $held = Cache::lock('enrollment-approval', 30);
    $held->get();

    expect(fn () => app(EnrollmentWorkflow::class)->approve($e, reviewer(), ['mode' => 'new']))->toThrow(ValidationException::class);
    expect($e->fresh()->status)->toBe('pending')->and(Company::count())->toBe(0);

    $held->release();
    expect(app(EnrollmentWorkflow::class)->approve($e->fresh(), reviewer(), ['mode' => 'new']))->toBeTrue();
});

test('a busy submission lock for the same applicant is refused instead of creating a duplicate', function () {
    $event = standEvent();
    config(['enrollment.lock_wait' => 0]);
    $matcher = app(CompanyMatcher::class);
    $held = Cache::lock('enrollment-submit:'.$event->id.':'.sha1($matcher->key('Acme BV').'|jane@acme.example|0'), 30);
    $held->get();

    expect(fn () => submitEnrollment($event))->toThrow(ValidationException::class);
    expect(Enrollment::count())->toBe(0);

    $held->release();
    expect(submitEnrollment($event))->toBeInstanceOf(Enrollment::class);
});

test('deleting an enrollment removes its private files, invitations and submissions but keeps the company and stand', function () {
    Storage::fake('local');
    [$e, $token, $event] = approvedEnrollment(null, ['company_logo' => UploadedFile::fake()->image('l.png')]);
    submitInfo($token, $event);
    $logo = $e->fresh()->files['company_logo']['path'];
    Storage::disk('local')->assertExists($logo);
    $company = $e->fresh()->company;
    $stand = emptyStand($event);
    $stand->update(['company_id' => $company->id]);

    $this->actingAs(reviewerWithout(['enrollments.view', 'enrollments.delete']));
    Livewire::test(ViewEnrollment::class, ['record' => $e->getKey()])->callAction('deleteEnrollment');

    expect(Enrollment::count())->toBe(0)->and(DB::table('enrollment_invitations')->count())->toBe(0)->and(DB::table('enrollment_info_submissions')->count())->toBe(0);
    Storage::disk('local')->assertMissing($logo);
    expect($company->fresh())->not->toBeNull()->and($stand->fresh()->company_id)->toBe($company->id);
});

test('deleting an enrollment needs the delete permission', function () {
    $e = submitEnrollment(standEvent());
    $this->actingAs(reviewerWithout(['enrollments.view', 'enrollments.review']));

    Livewire::test(ViewEnrollment::class, ['record' => $e->getKey()])->assertActionHidden('deleteEnrollment');
});

test('deleting an edition removes its enrollments and their private files', function () {
    Storage::fake('local');
    $event = standEvent();
    $e = submitEnrollment($event, ['company_logo' => UploadedFile::fake()->image('l.png')]);
    $path = $e->files['company_logo']['path'];

    $event->delete();

    expect(Enrollment::count())->toBe(0);
    Storage::disk('local')->assertMissing($path);
});

/* ---------------------------------------------------------------- public exposure */

test('public pages never contain an edition enrollment token or enrollment settings', function () {
    $event = standEvent(['terms_html' => '<p>Secret terms text</p>', 'fee_cents' => 123456]);
    Company::create(['name' => 'Shown BV']);

    foreach ([route('home'), route('events'), route('edition.show', $event), route('map'), route('companies'), route('partners')] as $url) {
        $html = $this->get($url)->getContent();
        expect($html)->not->toContain($event->enrollment_token)->not->toContain('Secret terms text')->not->toContain('enrollment_form_id')->not->toContain('123456');
    }
});

test('token carrying pages are not cached, not indexed and keep the referrer inside the site', function () {
    [$e, $token, $event] = approvedEnrollment();

    foreach ([route('enrollment.show', $event->enrollment_token), route('information.show', $token), route('contact.verify', str_repeat('a', 48))] as $url) {
        $response = $this->get($url)->assertOk();
        expect($response->headers->get('Referrer-Policy'))->toBe('same-origin')
            ->and($response->headers->get('Cache-Control'))->toContain('no-store')
            ->and($response->headers->get('X-Robots-Tag'))->toContain('noindex');
    }
});

/* ---------------------------------------------------------------- logs and mail */

test('log redaction removes email addresses and token-like strings', function () {
    $clean = LogRedactor::scrub('550 5.1.1 <jane@acme.example>: user unknown, link /informatie/'.str_repeat('Ab1', 16));

    expect($clean)->not->toContain('jane@acme.example')->not->toContain('Ab1Ab1Ab1')->toContain('[email]')->toContain('[token]');
});

test('a mail transport error is logged without the recipient or any token', function () {
    [$e, $token] = approvedEnrollment();
    $e->invitations()->update(['created_at' => now()->subMinutes(10)]);
    Log::spy();
    Mail::shouldReceive('to')->andThrow(new RuntimeException('Cannot send to jane@acme.example with '.str_repeat('Zz9', 20)));

    app(InvitationService::class)->reissue($e->fresh());

    Log::shouldHaveReceived('error')->withArgs(fn ($message, $context) => ! str_contains(json_encode($context), 'jane@acme.example') && ! str_contains(json_encode($context), 'Zz9Zz9Zz9') && str_contains(json_encode($context), '[email]'))->once();
});

test('every email renders with the right edition, link and recipient details', function () {
    [$e, $token, $event] = approvedEnrollment();
    $e->forceFill(['rejection_reason' => 'Geen plek meer.'])->save();
    $e = $e->fresh()->load('event');

    $received = (new EnrollmentReceivedMail($e))->render();
    $approved = (new EnrollmentApprovedMail($e, $token))->render();
    $invitation = (new EnrollmentInvitationMail($e, $token))->render();
    $rejected = (new EnrollmentRejectedMail($e))->render();
    $info = (new EnrollmentInfoReceivedMail($e, 'Planner Pat'))->render();
    $link = route('information.show', $token);

    foreach ([$received, $approved, $invitation, $rejected, $info] as $html) {
        expect($html)->toContain($event->name)->toContain('Acme BV');
    }
    expect($approved)->toContain($link)->and($invitation)->toContain($link)->and($rejected)->toContain('Geen plek meer.')->and($info)->toContain('Planner Pat')
        ->and($received)->not->toContain($token)->and($rejected)->not->toContain($token);
    expect($link)->toStartWith(config('app.url') ?: 'http');
});

/* ---------------------------------------------------------------- permissions */

test('without the right permissions the admin pages are forbidden', function () {
    $e = submitEnrollment(standEvent());
    $form = $e->event->enrollmentForm;
    $this->actingAs(reviewerWithout([]));

    Livewire::test(ListEnrollments::class)->assertForbidden();
    Livewire::test(ViewEnrollment::class, ['record' => $e->getKey()])->assertForbidden();
    Livewire::test(ListForms::class)->assertForbidden();
    Livewire::test(EditForm::class, ['record' => $form->getKey()])->assertForbidden();
});

test('view-only users can look but not change forms, review enrollments or assign stands', function () {
    $event = standEvent();
    $e = submitEnrollment($event);
    $stand = emptyStand($event);
    $this->actingAs(reviewerWithout(['forms.view', 'enrollments.view', 'event-stands.view']));

    Livewire::test(EditForm::class, ['record' => $event->enrollmentForm->getKey()])->assertForbidden(); // the editor itself needs forms.update
    Livewire::test(ViewEnrollment::class, ['record' => $e->getKey()])->assertSuccessful()->assertActionHidden('approve')->assertActionHidden('reject')->assertActionHidden('deleteEnrollment');
    Livewire::test(ManageStands::class)->set('selectedEventId', $event->id)->assertTableActionHidden('assign', $stand);
});

test('partners can always be assigned while companies still need approval, through the same page', function () {
    $event = standEvent();
    $partner = Partner::create(['name' => 'Partner Inc', 'description' => 'x', 'logo' => 'p.png']);
    $nobody = Company::create(['name' => 'Nobody BV']);
    $company = emptyStand($event, 1);
    $partnerStand = EventStand::where('event_id', $event->id)->where('type', 'partner')->first() ?? EventStand::create(['event_id' => $event->id, 'type' => 'partner', 'stand_number' => '1']);
    $this->actingAs(reviewerWithout(['event-stands.view', 'event-stands.update']));

    $page = Livewire::test(ManageStands::class)->set('selectedEventId', $event->id);
    $page->callTableAction('assign', $partnerStand, ['partner_id' => $partner->id])->assertHasNoTableActionErrors();
    $page->callTableAction('assign', $company, ['company_id' => $nobody->id])->assertHasTableActionErrors(['company_id']);

    expect($partnerStand->fresh()->partner_id)->toBe($partner->id)->and($company->fresh()->company_id)->toBeNull();
});

/* ---------------------------------------------------------------- company matching edge cases */

test('company matching stays conservative on unusual names', function () {
    $m = new CompanyMatcher;
    $companies = collect(['Ünïcode GmbH', 'Acme B.V. Holding', 'BV', 'Ab', 'Müller & Söhne'])->map(fn ($n) => Company::create(['name' => $n]));
    $ids = fn (string $n, string $k) => $m->matches($n)[$k]->pluck('name')->all();

    expect($ids('Unicode GmbH', 'exact'))->toBe(['Ünïcode GmbH'])
        ->and($ids('Acme', 'exact'))->toBe([])              // "B.V." in the middle is part of the name, not a legal suffix
        ->and($ids('!!!', 'exact'))->toBe([])->and($ids('!!!', 'similar'))->toBe([])
        ->and($ids('BV', 'exact'))->toBe([])                // a bare legal form carries no identity
        ->and($ids('Ab', 'similar'))->toBe([])
        ->and($ids('Muller en Sohne', 'exact'))->toBe([])   // never guess across words
        ->and($m->key('   ACME   b.v.  '))->toBe('acme#bv');
});

test('validation errors on the public forms come back to the page through the real redirect', function () {
    Mail::fake();
    [$e, $token, $event] = approvedEnrollment();
    $headers = fn (string $url) => ['X-Inertia' => 'true', 'Referer' => $url];
    $page = route('enrollment.show', $event->enrollment_token);

    // enrollment: a PHP file disguised as a png is refused and the user is sent back with the message
    $bad = enrollmentPayload($event->fresh(), ['company_name' => 'Fresh BV', 'contact_email' => 'fresh@x.example', 'company_logo' => UploadedFile::fake()->create('evil.png', 5, 'application/x-php')]);
    $this->withHeaders($headers($page))->post(route('enrollment.store', $event->enrollment_token), $bad)
        ->assertRedirect($page)->assertSessionHasErrors('answers.company_logo');
    expect(Enrollment::where('company_name', 'Fresh BV')->exists())->toBeFalse();

    // second form: the same, for a bad logo in the profile correction
    $info = route('information.show', $token);
    $payload = infoPayload($event, [], ['choice' => 'correction', 'logo' => UploadedFile::fake()->create('evil.png', 5, 'application/x-php')]);
    $this->withHeaders($headers($info))->post(route('information.store', $token), $payload)->assertRedirect($info)->assertSessionHasErrors('verification.logo');
});

test('the review screen points out other enrollments with a similar name or the same contact', function () {
    $event = standEvent();
    $first = submitEnrollment($event, ['company_name' => 'Zed Robotics', 'contact_email' => 'zed@zed.example']);
    $second = submitEnrollment($event, ['company_name' => 'Zed Robotics B.V.', 'contact_email' => 'zed@zed.example']); // different legal form: allowed, but flagged
    $third = submitEnrollment($event, ['company_name' => 'Unrelated BV', 'contact_email' => 'other@x.example']);
    $this->actingAs(reviewer());

    Livewire::test(ViewEnrollment::class, ['record' => $second->getKey()])->assertSee('may be the same company')->assertSee('Zed Robotics')->assertSee('same contact email');
    Livewire::test(ViewEnrollment::class, ['record' => $third->getKey()])->assertDontSee('may be the same company');
});
