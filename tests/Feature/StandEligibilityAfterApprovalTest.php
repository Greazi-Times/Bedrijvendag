<?php

use App\Filament\Resources\Enrollments\Pages\ViewEnrollment;
use App\Filament\Resources\EventStands\Pages\ManageStands;
use App\Mail\EnrollmentApprovedMail;
use App\Models\Company;
use App\Models\Enrollment;
use App\Models\EventStand;
use App\Models\Partner;
use App\Services\InvitationService;
use App\Services\StandEligibility;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

/** Names offered by the Event Map's Assign dropdown for a stand, exactly as an administrator would see them. */
function offeredFor(App\Models\Event $event, EventStand $stand): array
{
    return array_values(companySelect(Livewire::test(ManageStands::class)->set('selectedEventId', $event->id)->mountTableAction('assign', $stand))->getOptions());
}

test('a company is eligible and in the Event Map dropdown the moment its first enrollment is approved', function () {
    Mail::fake();
    $event = standEvent();
    $stand = emptyStand($event);
    $company = Company::create(['name' => 'Fresh BV']);
    $enrollment = submitEnrollment($event, ['company_name' => 'Fresh BV', 'contact_email' => 'fresh@x.example'], ['selected_company_id' => $company->id]);
    $this->actingAs(reviewerWithout(['event-stands.view', 'event-stands.update', 'enrollments.view', 'enrollments.review']));

    // pending: not offered and refused
    expect(offeredFor($event, $stand))->not->toContain('Fresh BV')->and(assignFails($stand, $company))->toBeTrue();

    // the administrator approves through the real review action; no sync, no second step
    Livewire::test(ViewEnrollment::class, ['record' => $enrollment->getKey()])->callAction('approve', ['mode' => 'existing', 'company_id' => $company->id])->assertNotified();

    expect($enrollment->fresh()->status)->toBe('approved')->and($enrollment->fresh()->info_status)->toBe('awaiting');
    expect(offeredFor($event, $stand))->toBe(['Fresh BV']);

    $page = Livewire::test(ManageStands::class)->set('selectedEventId', $event->id);
    $page->callTableAction('assign', $stand, ['company_id' => $company->id])->assertHasNoTableActionErrors();
    expect($stand->fresh()->company_id)->toBe($company->id);

    // the invitation to the second form was issued by the same approval
    Mail::assertQueued(EnrollmentApprovedMail::class, fn ($m) => $m->enrollment->is($enrollment));
});

test('a company whose second form is not completed can be assigned a stand, whatever the invitation looks like', function () {
    Mail::fake();
    $event = standEvent();
    [$e] = approvedEnrollment($event);
    $company = $e->fresh()->company;
    expect($e->fresh()->info_status)->toBe('awaiting')->and($e->infoSubmissions()->count())->toBe(0);

    // expired, revoked, never delivered: none of it matters for the stand
    $e->invitations()->update(['expires_at' => now()->subDay()]);
    app(InvitationService::class)->revoke($e->fresh());
    $stand = emptyStand($event);

    expect(app(StandEligibility::class)->isEligible($event, $company))->toBeTrue();
    $stand->update(['company_id' => $company->id]);
    expect($stand->fresh()->company_id)->toBe($company->id)->and(app(StandEligibility::class)->flaggedStands($event))->toBeEmpty();
});

test('completing the second form changes nothing about stand eligibility or the stand', function () {
    Mail::fake();
    $event = standEvent();
    [$e, $token] = approvedEnrollment($event);
    $company = $e->fresh()->company;
    $stand = emptyStand($event);
    $stand->update(['company_id' => $company->id, 'x_percent' => 12.5, 'y_percent' => 40]);
    $eligibility = app(StandEligibility::class);
    $before = $eligibility->eligibleCompanies($event)->pluck('companies.id')->all();

    // submit, then edit, with a profile correction, new contact and every kind of second-form answer
    submitInfo($token, $event, ['lunch_count' => 1, 'lunch_participants' => [['lunch_name' => 'Ann', 'lunch_diet' => ['vegan']]], 'drinks_attending' => 'yes', 'drinks_count' => 2]);
    submitInfo($token, $event, ['drinks_attending' => 'no', 'contact_name' => 'Planner', 'contact_email' => 'planner@acme.example'], ['choice' => 'correction', 'website_url' => 'https://changed.example']);

    expect($e->fresh()->info_status)->toBe('submitted');
    expect($eligibility->eligibleCompanies($event)->pluck('companies.id')->all())->toBe($before)->and($eligibility->isEligible($event, $company))->toBeTrue()
        ->and($eligibility->flaggedStands($event))->toBeEmpty();
    expect($stand->fresh()->company_id)->toBe($company->id)->and($stand->fresh()->x_percent)->toBe(12.5);
    // the stand can still be moved to another stand number afterwards
    $other = emptyStand($event, 2);
    $other->update(['company_id' => $company->id]);
    expect($other->fresh()->company_id)->toBe($company->id);
});

test('eligibility is computed from the enrollment approval alone, never from the second form', function () {
    $event = standEvent();
    $sql = strtolower(app(StandEligibility::class)->eligibleCompanies($event)->toSql());

    expect($sql)->toContain('"enrollments"')->toContain('status')->toContain('event_id')
        ->not->toContain('enrollment_info_submissions')->not->toContain('enrollment_invitations')->not->toContain('info_status')
        ->not->toContain('enrollment_contact_changes')->not->toContain('company_profile_submissions');
    expect(strtolower($event->approvedCompanies()->toSql()))->not->toContain('info_status')->not->toContain('enrollment_info_submissions');
});

test('pending and rejected first enrollments stay ineligible even with a second-form record', function () {
    Mail::fake();
    $event = standEvent();
    $pending = enrolledAs($event, 'pending', 'Pending BV');
    $rejected = enrolledAs($event, 'rejected', 'Rejected BV');
    $stand = emptyStand($event);
    $this->actingAs(reviewerWithout(['event-stands.view', 'event-stands.update']));

    // even a (forced) completed information status on a non-approved enrollment grants nothing
    Enrollment::query()->update(['info_status' => 'submitted']);

    expect(offeredFor($event, $stand))->not->toContain('Pending BV')->not->toContain('Rejected BV');
    expect(assignFails($stand, $pending))->toBeTrue()->and(assignFails($stand, $rejected))->toBeTrue();
});

test('approval makes a company eligible for that edition only, with or without a completed second form', function () {
    Mail::fake();
    $a = standEvent(['name' => 'Edition A']);
    $b = standEvent(['name' => 'Edition B']);
    [$e, $token] = approvedEnrollment($a);
    $company = $e->fresh()->company;
    $standB = emptyStand($b);
    $this->actingAs(reviewerWithout(['event-stands.view', 'event-stands.update']));

    expect(offeredFor($a, emptyStand($a)))->toContain($company->name)->and(offeredFor($b, $standB))->not->toContain($company->name);
    submitInfo($token, $a, ['drinks_attending' => 'yes', 'drinks_count' => 3], ['choice' => 'confirmed']);
    expect(offeredFor($b, $standB))->not->toContain($company->name)->and(assignFails($standB, $company))->toBeTrue();
});

test('partners are offered and assignable regardless of any enrollment or second-form state', function () {
    $event = standEvent();
    $partner = Partner::create(['name' => 'Partner Inc', 'description' => 'x', 'logo' => 'p.png']);
    enrolledAs($event, 'pending', 'Pending BV');
    enrolledAs($event, 'rejected', 'Rejected BV');
    $partnerStand = EventStand::where('event_id', $event->id)->where('type', 'partner')->first() ?? EventStand::create(['event_id' => $event->id, 'type' => 'partner', 'stand_number' => '1']);
    $this->actingAs(reviewerWithout(['event-stands.view', 'event-stands.update']));

    Livewire::test(ManageStands::class)->set('selectedEventId', $event->id)->callTableAction('assign', $partnerStand, ['partner_id' => $partner->id])->assertHasNoTableActionErrors();

    expect($partnerStand->fresh()->partner_id)->toBe($partner->id)->and($partnerStand->fresh()->company_id)->toBeNull();
});
