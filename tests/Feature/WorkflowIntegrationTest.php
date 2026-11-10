<?php

use App\Filament\Resources\Enrollments\Pages\ViewEnrollment;
use App\Filament\Resources\Events\Pages\CreateEvent;
use App\Filament\Resources\EventStands\Pages\ManageStands;
use App\Mail\EnrollmentApprovedMail;
use App\Mail\EnrollmentInfoReceivedMail;
use App\Mail\EnrollmentReceivedMail;
use App\Models\Company;
use App\Models\CompanyProfileSubmission;
use App\Models\Enrollment;
use App\Models\Event;
use App\Models\EventStand;
use App\Models\Partner;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;
use Livewire\Livewire;

test('an administrator can create an edition and it inherits the global forms or copies a custom one', function () {
    $this->actingAs(reviewerWithout(['events.view', 'events.create', 'events.update']));
    $description = ['type' => 'doc', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Hallo']]]]];

    // no form setup at all: both global forms are inherited
    Livewire::test(CreateEvent::class)->fillForm(['name' => 'Bedrijvendag 2028', 'date' => '2028-03-01', 'description' => $description])->call('create')->assertHasNoFormErrors();
    $plain = Event::where('name', 'Bedrijvendag 2028')->firstOrFail();
    expect($plain->enrollment_token)->toHaveLength(40)->and($plain->enrollment_enabled)->toBeFalse()
        ->and($plain->enrollmentForm->isGlobal())->toBeTrue()->and($plain->infoForm->isGlobal())->toBeTrue()->and($plain->enrollment_form_id)->toBeNull();

    // starting from a previous edition's custom form gives an independent copy of it
    $previous = standEvent(['name' => 'Previous edition']);
    app(App\Services\EditionForms::class)->customize($previous, 'enrollment');
    $draft = $previous->fresh()->enrollmentForm->draft_schema;
    $draft['sections'][0]['fields'][0]['label'] = 'Name of your organisation';
    $previous->fresh()->enrollmentForm->update(['draft_schema' => $draft]);
    $previous->fresh()->enrollmentForm->fresh()->publish();

    Livewire::test(CreateEvent::class)->fillForm(['name' => 'Bedrijvendag 2029', 'date' => '2029-03-01', 'description' => $description, 'copy_forms_from' => $previous->id])->call('create')->assertHasNoFormErrors();
    $event = Event::where('name', 'Bedrijvendag 2029')->firstOrFail();
    expect($event->enrollmentForm->isEditionSpecific())->toBeTrue()->and($event->enrollmentForm->event_id)->toBe($event->id)
        ->and($event->enrollmentForm->id)->not->toBe($previous->fresh()->enrollmentForm->id)
        ->and($event->enrollmentForm->latestVersion->schema['sections'][0]['fields'][0]['label'])->toBe('Name of your organisation')
        ->and($event->infoForm->isGlobal())->toBeTrue();
});

test('the complete workflow keeps every record pointing at the right edition, company and version', function () {
    Mail::fake();
    $admin = reviewer(['event-stands.view', 'event-stands.update', 'company-profile-submissions.view', 'company-profile-submissions.review', 'enrollments.delete']);
    $event = standEvent(['name' => 'Bedrijvendag 2027', 'max_stands' => 4]);
    $otherEvent = standEvent(['name' => 'Bedrijvendag 2028']);
    $existing = Company::create(['name' => 'C.S.I Incorporated', 'website_url' => 'https://old.example', 'description_nl' => '<p>Old text</p>']);

    // 4-5: the company opens the link and searches; a similar name is only a hint
    $this->getJson(route('enrollment.search', [$event->enrollment_token, 'q' => 'CSI Incorporated']))->assertOk()->assertJsonPath('0.id', $existing->id)->assertJsonPath('0.exact', true);
    $this->get(route('enrollment.show', $event->enrollment_token))->assertInertia(fn (Assert $p) => $p->where('state', 'open')->where('form.version_id', $event->enrollmentForm->latestVersion->id));

    // 6: it submits, claiming the existing company
    $this->post(route('enrollment.store', $event->enrollment_token), [
        'submission_key' => (string) Illuminate\Support\Str::uuid(), 'form_version_id' => $event->enrollmentForm->latestVersion->id, 'selected_company_id' => $existing->id,
        'answers' => enrollmentAnswers(['company_name' => 'CSI Incorporated', 'company_website' => 'https://new.example']),
    ])->assertRedirect(route('enrollment.received', $event->enrollment_token));
    $enrollment = Enrollment::sole();
    expect($enrollment->event_id)->toBe($event->id)->and($enrollment->selected_company_id)->toBe($existing->id)->and($enrollment->company_id)->toBeNull()
        ->and(Company::count())->toBe(1)->and($existing->fresh()->website_url)->toBe('https://old.example');
    Mail::assertQueued(EnrollmentReceivedMail::class, 1);

    // 14 (negative): not eligible while pending
    $stand = EventStand::create(['event_id' => $event->id, 'type' => 'company', 'stand_number' => '1']);
    expect(assignFails($stand, $existing))->toBeTrue();

    // 7-9: the administrator reviews and approves against the existing company
    $this->actingAs($admin);
    Livewire::test(ViewEnrollment::class, ['record' => $enrollment->getKey()])->assertSee('exact match')->callAction('approve', ['mode' => 'existing', 'company_id' => $existing->id])->assertNotified();
    $enrollment->refresh();
    expect($enrollment->status)->toBe('approved')->and($enrollment->company_id)->toBe($existing->id)->and($enrollment->event_id)->toBe($event->id)
        ->and($existing->fresh()->website_url)->toBe('https://old.example') // never overwritten by approval
        ->and($event->approvedCompanies()->pluck('companies.id')->all())->toBe([$existing->id])->and($otherEvent->approvedCompanies()->count())->toBe(0);

    // 10: the approval mail carries a working link for exactly this enrollment
    $token = null;
    Mail::assertQueued(EnrollmentApprovedMail::class, function ($m) use (&$token, $enrollment) {
        $token = $m->token;

        return $m->hasTo('jane@acme.example') && $m->enrollment->is($enrollment);
    });
    $this->get(route('information.show', $token))->assertInertia(fn (Assert $p) => $p->where('state', 'ready')->where('company.name', 'C.S.I Incorporated')->where('form.version_id', $event->infoForm->latestVersion->id));

    // 11-12: the company completes the form and proposes a correction
    $this->post(route('information.store', $token), infoPayload($event, ['drinks_attending' => 'no'], ['choice' => 'correction', 'description' => 'New text', 'website_url' => 'https://corrected.example']))->assertRedirect();
    $submission = $enrollment->infoSubmissions()->sole();
    expect($submission->form_version_id)->toBe($event->infoForm->latestVersion->id)->and($enrollment->fresh()->info_status)->toBe('submitted');
    Mail::assertQueued(EnrollmentInfoReceivedMail::class, fn ($m) => $m->hasTo('jane@acme.example'));

    // 13: the live profile only changes when an administrator approves the review item
    expect($existing->fresh()->website_url)->toBe('https://old.example');
    $review = CompanyProfileSubmission::where('status', 'pending')->latest('id')->firstOrFail();
    expect($review->company_id)->toBe($existing->id)->and($review->proposed_website_url)->toBe('https://corrected.example');
    $review->approve('ok');
    expect($existing->fresh()->website_url)->toBe('https://corrected.example')->and($existing->fresh()->description_nl)->toContain('New text');

    // 14-15: now eligible for this edition only, and stands can be assigned and managed
    expect(assignFails(EventStand::create(['event_id' => $otherEvent->id, 'type' => 'company', 'stand_number' => '1']), $existing))->toBeTrue();
    $page = Livewire::test(ManageStands::class)->set('selectedEventId', $event->id);
    $page->callTableAction('assign', $stand, ['company_id' => $existing->id])->assertHasNoTableActionErrors();
    expect($stand->fresh()->company_id)->toBe($existing->id)->and($stand->fresh()->event_id)->toBe($event->id);

    // partners need no enrollment
    $partner = Partner::create(['name' => 'Partner Inc', 'description' => 'x', 'logo' => 'p.png']);
    $partnerStand = EventStand::where('event_id', $event->id)->where('type', 'partner')->orderBy('stand_number')->firstOrFail();
    $page->callTableAction('assign', $partnerStand, ['partner_id' => $partner->id])->assertHasNoTableActionErrors();
    expect($partnerStand->fresh()->partner_id)->toBe($partner->id);

    // the stand survives losing the approval and is flagged instead
    $enrollment->forceFill(['status' => 'rejected'])->save();
    expect($stand->fresh()->company_id)->toBe($existing->id)->and(app(App\Services\StandEligibility::class)->flaggedStands($event)->pluck('id')->all())->toBe([$stand->id]);
});

test('a rejected applicant, a closed edition and an unpublished form behave consistently', function () {
    Mail::fake();
    $event = standEvent();
    $e = submitEnrollment($event);
    app(App\Services\EnrollmentWorkflow::class)->reject($e, reviewer(), 'Vol');

    // a rejected company may apply again, but never gets a stand or an invitation
    $again = submitEnrollment($event);
    expect($again->id)->not->toBe($e->id)->and($e->fresh()->status)->toBe('rejected')->and($e->invitations()->count())->toBe(0);
    expect(assignFails(emptyStand($event), Company::create(['name' => 'Acme BV'])))->toBeTrue();

    $event->update(['enrollment_closes_at' => now()->subMinute()]);
    $this->get(route('enrollment.show', $event->enrollment_token))->assertInertia(fn (Assert $p) => $p->where('state', 'closed')->where('form', null));

    $event->update(['enrollment_closes_at' => null]);
    // a draft that was never published must not be shown (the published versions that enrollments reference are protected by the database)
    $draftOnly = App\Models\Form::create(['name' => 'Draft only', 'kind' => 'enrollment', 'event_id' => $event->id, 'draft_schema' => App\Support\Forms\DefaultForms::enrollment()]);
    $event->update(['enrollment_form_id' => $draftOnly->id]);
    $this->get(route('enrollment.show', $event->fresh()->enrollment_token))->assertInertia(fn (Assert $p) => $p->where('state', 'disabled')->where('form', null));
    $this->post(route('enrollment.store', $event->enrollment_token), ['submission_key' => (string) Illuminate\Support\Str::uuid(), 'form_version_id' => 1, 'answers' => enrollmentAnswers()])->assertStatus(404);
});
