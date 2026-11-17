<?php

require_once __DIR__.'/../Support/AnalyticsFixtures.php';

use App\Filament\Resources\EventStands\Pages\ManageStands;
use App\Models\Company;
use App\Models\Enrollment;
use App\Models\EventStand;
use App\Models\Partner;
use App\Models\User;
use App\Services\Analytics\Participation;
use App\Services\StandEligibility;
use App\Support\Permissions;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

function historicalSuperAdmin(): User
{
    reviewer(); // seeds roles and the panel
    $user = User::factory()->create()->assignRole(Role::findOrCreate(Permissions::SUPER_ADMIN, 'web'));
    test()->actingAs($user);

    return $user;
}

function overrideLogs()
{
    return Activity::query()->where('log_name', 'event-stands')->where('description', 'like', 'Stand assignment: enrollment%');
}

function mapAssign(User $user, $stand, array $data)
{
    return Livewire::actingAs($user)->test(ManageStands::class)->set('selectedEventId', $stand->event_id)
        ->callTableAction('assign', $stand, $data);
}

test('a Super Admin can assign a non-enrolled company to a historical edition with an explicit override and reason', function () {
    $past = historicalEvent();
    $stand = emptyStand($past);
    $company = Company::create(['name' => 'Old BV']);
    $user = historicalSuperAdmin();

    mapAssign($user, $stand, ['company_id' => $company->id, 'enrollment_override' => true, 'override_reason' => 'Aanwezig volgens oud archief', 'override_confirm' => true])->assertHasNoTableActionErrors();

    expect($stand->fresh()->company_id)->toBe($company->id)->and($stand->fresh()->participation_confirmed_at)->not->toBeNull();

    $log = overrideLogs()->latest('id')->first();
    expect($log->causer_id)->toBe($user->id)
        ->and($log->properties['enrollment_requirement_overridden'])->toBeTrue()
        ->and($log->properties['reason'])->toBe('Aanwezig volgens oud archief')
        ->and($log->properties['company_id'])->toBe($company->id)
        ->and($log->properties['event_id'])->toBe($past->id);
});

test('a missing confirmation or reason does not bypass eligibility', function () {
    $stand = emptyStand(historicalEvent());
    $company = Company::create(['name' => 'Old BV']);
    $user = historicalSuperAdmin();

    mapAssign($user, $stand, ['company_id' => $company->id, 'enrollment_override' => true, 'override_reason' => 'x', 'override_confirm' => false])->assertHasTableActionErrors();
    mapAssign($user, $stand, ['company_id' => $company->id, 'enrollment_override' => false])->assertHasTableActionErrors(['company_id']);
    expect($stand->fresh()->company_id)->toBeNull();
});

test('a Super Admin can move and reassign historical stands, keeping the log trail', function () {
    $past = historicalEvent();
    $first = emptyStand($past, 1);
    $second = emptyStand($past, 2);
    $a = Company::create(['name' => 'A BV']);
    $b = Company::create(['name' => 'B BV']);
    $user = historicalSuperAdmin();
    $override = fn ($c) => ['company_id' => $c->id, 'enrollment_override' => true, 'override_reason' => 'correctie', 'override_confirm' => true];

    mapAssign($user, $first, $override($a));
    mapAssign($user, $second, $override($a)); // move A to stand 2
    mapAssign($user, $second, $override($b)); // reassign stand 2

    expect($first->fresh()->company_id)->toBeNull()->and($second->fresh()->company_id)->toBe($b->id);
    $moved = overrideLogs()->orderBy('id')->get()[1];
    expect($moved->properties['previous_stand_of_company'])->toBe('1')->and($moved->properties['new_stand'])->toBe('2');
});

test('a Super Admin can override on past, today and future editions, for companies with no, pending or rejected enrollment', function () {
    Mail::fake();
    $user = historicalSuperAdmin();
    $editions = [historicalEvent(), standEvent(['name' => 'Today', 'date' => now('Europe/Amsterdam')->toDateString()]), standEvent(['name' => 'Future', 'date' => today()->addYear()->toDateString()])];

    foreach ($editions as $event) {
        $none = Company::create(['name' => "None {$event->id}"]);
        $pending = enrolledAs($event, 'pending', "Pending {$event->id}");
        $rejected = enrolledAs($event, 'rejected', "Rejected {$event->id}");
        $statuses = Enrollment::query()->pluck('status', 'id')->all();

        $stands = collect([1, 2, 3])->map(fn ($n) => EventStand::firstOrCreate(['event_id' => $event->id, 'type' => 'company', 'stand_number' => (string) $n]))->all(); // the page may have synced rows already

        foreach ([$none, $pending, $rejected] as $i => $company) {
            $stand = $stands[$i];
            mapAssign($user, $stand, ['company_id' => $company->id, 'enrollment_override' => true, 'override_reason' => 'handmatig', 'override_confirm' => true]);
            expect($stand->fresh()->company_id)->toBe($company->id);
        }

        expect(Enrollment::query()->pluck('status', 'id')->all())->toBe($statuses);
        expect($event->approvedCompanies()->count())->toBe(0);
    }

    expect(overrideLogs()->count())->toBe(9);
    Mail::assertNothingSent();
});

test('without confirmation or reason a Super Admin stays restricted on current and future editions', function () {
    $user = historicalSuperAdmin();

    foreach ([today()->toDateString(), today()->addYear()->toDateString()] as $i => $date) {
        $stand = emptyStand(standEvent(['name' => "Open {$i}", 'date' => $date]));
        $company = Company::create(['name' => "No BV {$i}"]);

        mapAssign($user, $stand, ['company_id' => $company->id, 'enrollment_override' => false]);
        mapAssign($user, $stand, ['company_id' => $company->id, 'enrollment_override' => true, 'override_reason' => 'x', 'override_confirm' => false]);
        expect($stand->fresh()->company_id)->toBeNull();

        $this->actingAs($user);
        expect(fn () => $stand->assignWithEnrollmentOverride($company->id, ''))->toThrow(ValidationException::class);
        expect($stand->fresh()->company_id)->toBeNull();
    }
});

test('regular admins and editors cannot use the override, even with forged data or the model entry point', function () {
    $past = historicalEvent();
    $company = Company::create(['name' => 'Old BV']);

    $user = reviewerWithout(['event-stands.view', 'event-stands.update']); // an admin/editor-like role with update rights
    $stand = emptyStand($past, 1);

    mapAssign($user, $stand, ['company_id' => $company->id, 'enrollment_override' => true, 'override_reason' => 'x', 'override_confirm' => true]);
    expect($stand->fresh()->company_id)->toBeNull();

    $this->actingAs($user);
    expect(fn () => $stand->assignWithEnrollmentOverride($company->id, 'x'))->toThrow(ValidationException::class);
    expect($stand->fresh()->company_id)->toBeNull();
    expect(overrideLogs()->count())->toBe(0);
});

test('bulk writes cannot carry the override', function () {
    $past = historicalEvent();
    $stand = emptyStand($past);
    $company = Company::create(['name' => 'Old BV']);
    $this->actingAs(historicalSuperAdmin());

    expect(fn () => EventStand::query()->whereKey($stand->id)->update(['company_id' => $company->id]))->toThrow(ValidationException::class);
    expect(fn () => $past->stands()->update(['company_id' => $company->id]))->toThrow(ValidationException::class);
});

test('existing historical assignments are preserved, and nothing happens to enrollments or mail', function () {
    Mail::fake();
    $past = historicalEvent();
    $legacy = legacyStand($past, Company::create(['name' => 'Legacy BV']), 1);
    $pending = enrolledAs($past, 'pending', 'Pending BV');
    $before = Enrollment::query()->pluck('status', 'id')->all();
    $stand = emptyStand($past, 2);
    $user = historicalSuperAdmin();

    $legacy->update(['x_percent' => 50]); // grandfathered layout edit stays free
    mapAssign($user, $stand, ['company_id' => $pending->id, 'enrollment_override' => true, 'override_reason' => 'bevestigd', 'override_confirm' => true]);

    expect($legacy->fresh()->company_id)->not->toBeNull()
        ->and(Enrollment::query()->pluck('status', 'id')->all())->toBe($before)
        ->and(Enrollment::count())->toBe(1);
    Mail::assertNothingSent();
});

test('partners are unaffected', function () {
    $past = historicalEvent();
    $partner = Partner::create(['name' => 'Partner Inc', 'description' => 'x', 'logo' => 'partners/x.png']);
    $stand = EventStand::create(['event_id' => $past->id, 'type' => 'partner', 'stand_number' => '1']);

    $stand->update(['partner_id' => $partner->id]);

    expect($stand->fresh()->partner_id)->toBe($partner->id);
});

test('an override-confirmed stand counts as participation once, and is cleared when the stand changes', function () {
    $era = openEvent(['name' => 'Era 2021', 'date' => '2021-03-01', 'max_stands' => 3]);
    enrolledAs($era, 'approved', 'Enrolled BV');
    $company = Company::create(['name' => 'Confirmed BV']);
    $stand = emptyStand($era, 2);
    $this->actingAs(historicalSuperAdmin());

    $stand->assignWithEnrollmentOverride($company->id, 'archief');
    $participation = fn () => app(Participation::class);
    expect($participation()->sets()[$era->id])->toContain($company->id);

    $stand->fresh()->update(['company_id' => null]);
    app()->forgetInstance(\App\Services\Analytics\Analytics::class);
    expect($stand->fresh()->participation_confirmed_at)->toBeNull();
});

test('historical detection flips at Amsterdam midnight, in winter and summer', function () {
    $eligibility = app(StandEligibility::class);
    $winter = standEvent(['name' => 'W', 'date' => '2030-01-15']);
    $summer = standEvent(['name' => 'S', 'date' => '2030-07-15']);

    $this->travelTo(\Carbon\Carbon::parse('2030-01-15 22:59:00', 'UTC')); // 23:59 Amsterdam
    expect($eligibility->isHistorical($winter))->toBeFalse();
    $this->travelTo(\Carbon\Carbon::parse('2030-01-15 23:00:00', 'UTC')); // 00:00 on the 16th
    expect($eligibility->isHistorical($winter))->toBeTrue();

    $this->travelTo(\Carbon\Carbon::parse('2030-07-15 21:59:00', 'UTC')); // 23:59 Amsterdam
    expect($eligibility->isHistorical($summer))->toBeFalse();
    $this->travelTo(\Carbon\Carbon::parse('2030-07-15 22:00:00', 'UTC'));
    expect($eligibility->isHistorical($summer))->toBeTrue();
});

test('the Event Map opens on the nearest upcoming edition, not the latest planned', function () {
    $this->travelTo(\Carbon\Carbon::parse('2026-11-17 12:00:00', 'UTC'));
    standEvent(['name' => 'Past', 'date' => '2026-03-01']);
    $near = standEvent(['name' => 'Near', 'date' => '2026-11-19']);
    standEvent(['name' => 'Far', 'date' => '2027-03-04']);
    $this->actingAs(reviewerWithout(['event-stands.view']));

    Livewire::test(ManageStands::class)->assertSet('selectedEventId', $near->id);
});

test('analytics: legacy, approved and confirmed participation combine, count once, and follow reassignment and removal', function () {
    $analytics = app(\App\Services\Analytics\Analytics::class);
    $participation = fn () => tap(app(Participation::class), fn () => $analytics->flush());
    $legacyOld = openEvent(['name' => 'Old 2019', 'date' => '2019-03-01', 'max_stands' => 5]);
    $era1 = openEvent(['name' => 'Era 2021', 'date' => '2021-03-01', 'max_stands' => 5]);
    $era2 = openEvent(['name' => 'Era 2022', 'date' => '2022-03-01', 'max_stands' => 5]);
    [$legacy, $approved, $confirmed] = [Company::create(['name' => 'Legacy']), Company::create(['name' => 'Approved']), Company::create(['name' => 'Confirmed'])];

    legacyStand($legacyOld, $legacy);              // pre-era, no confirmation flag: still counts
    analyticsEnroll($era1, $approved);
    analyticsEnroll($era2, $confirmed);            // approved in 2022 so 2021 confirmation makes it retained
    $this->actingAs(historicalSuperAdmin());
    emptyStand($era1, 1)->assignWithEnrollmentOverride($confirmed->id, 'archief');
    $both = emptyStand($era1, 2);
    $both->assignWithEnrollmentOverride($approved->id, 'dubbel'); // approved + confirmed stand

    expect($participation()->sets()[$legacyOld->id])->toBe([$legacy->id]);
    $set = $participation()->sets()[$era1->id];
    expect($set)->toHaveCount(2)->toContain($approved->id, $confirmed->id);
    $class = $participation()->classify($era2->id);
    expect($class['retained'])->toBe([$confirmed->id])->and($class['new'])->toBe([]);
    expect(Enrollment::where('event_id', $era1->id)->count())->toBe(1); // only the real one, nothing fabricated

    $both->refresh()->update(['company_id' => null]); // removal
    emptyStand($era1, 3)->assignWithEnrollmentOverride($legacy->id, 'verplaatst');
    $set = $participation()->sets()[$era1->id];
    expect($set)->toContain($legacy->id)->toContain($approved->id)->and(count($set))->toBe(3);
    expect($participation()->overridden()[$era1->id])->toContain($legacy->id)->not->toContain($approved->id);
});

test('a failed move rolls back and leaves the previous stand intact, with no success log', function () {
    $past = historicalEvent();
    $first = emptyStand($past, 1);
    $second = emptyStand($past, 2);
    $company = Company::create(['name' => 'Old BV']);
    $user = historicalSuperAdmin();
    mapAssign($user, $first, ['company_id' => $company->id, 'enrollment_override' => true, 'override_reason' => 'ok', 'override_confirm' => true]);
    $logs = overrideLogs()->count();

    mapAssign($user, $second, ['company_id' => $company->id, 'enrollment_override' => false]); // no override: refused

    expect($first->fresh()->company_id)->toBe($company->id)->and($second->fresh()->company_id)->toBeNull()
        ->and(overrideLogs()->count())->toBe($logs);
});

test('the one-shot override is cleared after a failure and layout edits and removals need no override', function () {
    $past = historicalEvent();
    $stand = emptyStand($past);
    $legacy = legacyStand($past, Company::create(['name' => 'Legacy BV']), 2);
    $company = Company::create(['name' => 'Old BV']);
    $this->actingAs(historicalSuperAdmin());

    expect(fn () => $stand->assignWithEnrollmentOverride($company->id, '   '))->toThrow(ValidationException::class);
    expect($stand->enrollmentOverrideReason)->toBeNull();
    expect(assignFails($stand, $company))->toBeTrue(); // a plain save afterwards is not overridden

    $legacy->update(['x_percent' => 12, 'y_percent' => 34]);
    $legacy->update(['company_id' => null]);
    expect($legacy->fresh()->company_id)->toBeNull()->and($legacy->fresh()->x_percent)->toBe(12.0);
});

test('the Event Map marks override stands informationally for Super Admins only, and drops the mark once approved', function () {
    $event = standEvent(['name' => 'Soon', 'date' => today()->addMonth()->toDateString()]);
    $stands = collect([1, 2])->map(fn ($n) => EventStand::firstOrCreate(['event_id' => $event->id, 'type' => 'company', 'stand_number' => (string) $n]));
    $company = Company::create(['name' => 'Manual BV']);
    $user = historicalSuperAdmin();
    mapAssign($user, $stands[0], ['company_id' => $company->id, 'enrollment_override' => true, 'override_reason' => 'Telefonisch afgesproken', 'override_confirm' => true]);

    $page = Livewire::actingAs($user)->test(ManageStands::class)->set('selectedEventId', $event->id);
    $page->assertTableColumnStateSet('manual_assignment', 'Handmatig toegewezen', $stands[0])
        ->assertTableColumnStateSet('eligibility', null, $stands[0]) // not an unresolved warning
        ->assertTableColumnStateSet('manual_assignment', null, $stands[1]);

    $other = reviewerWithout(['event-stands.view', 'event-stands.update']);
    Livewire::actingAs($user)->test(ManageStands::class)->set('selectedEventId', $event->id)
        ->assertTableColumnExists('manual_assignment', fn ($c) => str_contains((string) $c->getTooltip(), 'Telefonisch afgesproken'), $stands[0]);
    Livewire::actingAs($other)->test(ManageStands::class)->set('selectedEventId', $event->id)
        ->assertTableColumnExists('manual_assignment', fn ($c) => $c->getTooltip() === null, $stands[0]);

    // once the company is approved the stand no longer counts as an override
    analyticsEnroll($event, $company);
    Livewire::actingAs($user)->test(ManageStands::class)->set('selectedEventId', $event->id)
        ->assertTableColumnStateSet('manual_assignment', null, $stands[0]);
    expect(overrideLogs()->count())->toBe(1); // audit history intact
});

test('analytics: a later approval does not duplicate an override, and upcoming editions stay provisional', function () {
    $analytics = app(\App\Services\Analytics\Analytics::class);
    $future = openEvent(['name' => 'Next', 'date' => today()->addMonths(2)->toDateString(), 'max_stands' => 3]);
    analyticsEnroll($future, Company::create(['name' => 'Enrolled']));
    $company = Company::create(['name' => 'Manual BV']);
    $this->actingAs(historicalSuperAdmin());
    emptyStand($future, 1)->assignWithEnrollmentOverride($company->id, 'telefoon');

    $analytics->flush();
    $p = app(Participation::class);
    expect($p->sets()[$future->id])->toContain($company->id)->and($p->overridden()[$future->id])->toBe([$company->id])
        ->and(collect($p->editions())->firstWhere('id', $future->id)['provisional'])->toBeTrue();

    analyticsEnroll($future, $company);
    $analytics->flush();
    $p = app(Participation::class);
    expect(array_count_values($p->sets()[$future->id])[$company->id])->toBe(1)->and($p->overridden()[$future->id])->toBe([]);
});
