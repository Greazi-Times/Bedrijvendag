<?php

require_once __DIR__.'/../Support/AnalyticsFixtures.php';

use App\Filament\Pages\Attendance;
use App\Models\Company;
use App\Models\CompanyAttendance;
use App\Models\Enrollment;
use App\Models\EnrollmentInfoSubmission;
use App\Models\EventStand;
use Illuminate\Database\QueryException;
use Livewire\Livewire;

function attendanceWorld(): array
{
    $event = analyticsEdition('Editie A', '2027-03-01');
    $other = analyticsEdition('Editie B', '2028-03-01');
    $acme = Company::create(['name' => 'Acme']);
    $beta = Company::create(['name' => 'Beta']);

    foreach ([[$event, $acme], [$event, $beta], [$other, $beta]] as [$e, $c]) {
        analyticsEnroll($e, $c);
    }

    foreach ([[$event, $acme, 1], [$event, $acme, 7], [$event, $beta, 2], [$other, $beta, 3]] as [$e, $c, $n]) {
        EventStand::query()->forceCreate(['event_id' => $e->id, 'company_id' => $c->id, 'type' => 'company', 'stand_number' => (string) $n]);
    }

    return compact('event', 'other', 'acme', 'beta');
}

function attendancePage($event, array $permissions = ['attendance.view', 'attendance.update', 'attendance.dietary'])
{
    test()->actingAs(reviewerWithout($permissions));

    return Livewire::test(Attendance::class, ['eventId' => $event->id]);
}

function lunchInfo(Enrollment $enrollment, array $entries): EnrollmentInfoSubmission
{
    return analyticsInfo($enrollment, 1, ['lunch_count' => count($entries), 'lunch_participants' => $entries]);
}

test('each company appears once per edition with all its stands, and editions are isolated', function () {
    $w = attendanceWorld();
    $rows = attendancePage($w['event'])->instance()->companies();

    expect($rows->pluck('name')->all())->toEqualCanonicalizing(['Acme', 'Beta'])
        ->and($rows->firstWhere('name', 'Acme')['stands'])->toBe(['1', '7'])
        ->and($rows->firstWhere('name', 'Beta')['stands'])->toBe(['2']);
    expect(attendancePage($w['other'])->instance()->companies()->pluck('stands')->all())->toBe([['3']]);
});

test('checking in stores the time and administrator, opens the confirmation and keeps the time when repeated', function () {
    $w = attendanceWorld();
    $page = attendancePage($w['event'])->call('checkIn', $w['acme']->id)
        ->assertSet('detailCompanyId', $w['acme']->id)->assertSet('justCheckedIn', true)
        ->assertSee('Company Checked In')->assertSee('1, 7');

    $row = CompanyAttendance::query()->where('company_id', $w['acme']->id)->sole();
    expect($row->checked_in_at)->not->toBeNull()->and($row->checked_in_by)->toBe(auth()->id());
    $time = $row->checked_in_at;

    $this->travel(5)->minutes();
    $page->call('closeDetails')->call('showDetails', $w['acme']->id)->assertSet('justCheckedIn', false)->assertSee('Check-in details')
        ->call('checkIn', $w['acme']->id)->assertSet('justCheckedIn', false);

    expect(CompanyAttendance::query()->count())->toBe(1)->and($row->fresh()->checked_in_at->equalTo($time))->toBeTrue();
});

test('the database refuses a second attendance row for the same company and edition', function () {
    $w = attendanceWorld();
    CompanyAttendance::create(['event_id' => $w['event']->id, 'company_id' => $w['acme']->id]);

    expect(fn () => CompanyAttendance::create(['event_id' => $w['event']->id, 'company_id' => $w['acme']->id]))->toThrow(QueryException::class);
});

test('a company without a stand in the edition cannot be checked in', function () {
    $w = attendanceWorld();
    attendancePage($w['other'])->call('checkIn', $w['acme']->id);

    expect(CompanyAttendance::query()->count())->toBe(0);
});

test('a check-in can be reversed without touching the stand and counts update', function () {
    $w = attendanceWorld();
    $page = attendancePage($w['event'])->call('checkIn', $w['beta']->id);

    expect($page->viewData('stats'))->toBe(['total' => 2, 'present' => 1, 'missing' => 1, 'percentage' => 50]);

    $page->call('reverse', $w['beta']->id);
    expect($page->viewData('stats'))->toBe(['total' => 2, 'present' => 0, 'missing' => 2, 'percentage' => 0])
        ->and(CompanyAttendance::query()->sole()->checked_in_at)->toBeNull()
        ->and(EventStand::query()->where('company_id', $w['beta']->id)->count())->toBe(2);
});

test('search, status filter and sorting work', function () {
    $w = attendanceWorld();
    $page = attendancePage($w['event'])->call('checkIn', $w['beta']->id)->call('closeDetails');

    expect($page->set('search', 'acm')->viewData('rows')->pluck('name')->all())->toBe(['Acme'])
        ->and($page->set('search', '7')->viewData('rows')->pluck('name')->all())->toBe(['Acme']);
    $page->set('search', '');
    expect($page->set('status', 'present')->viewData('rows')->pluck('name')->all())->toBe(['Beta'])
        ->and($page->set('status', 'not_checked_in')->viewData('rows')->pluck('name')->all())->toBe(['Acme']);
    expect($page->set('status', 'all')->set('sort', 'stand')->viewData('rows')->pluck('name')->all())->toBe(['Acme', 'Beta']);
});

test('dietary wishes come from the latest submission of the right company only', function () {
    $w = attendanceWorld();
    $acme = Enrollment::query()->where('company_id', $w['acme']->id)->where('event_id', $w['event']->id)->first();
    lunchInfo($acme, [['lunch_diet' => ['vegetarian']], ['lunch_diet' => ['halal']], ['lunch_diet' => ['halal', 'other'], 'lunch_diet_other' => 'Nuts'], ['lunch_diet' => ['none']]]);
    $beta = Enrollment::query()->where('company_id', $w['beta']->id)->where('event_id', $w['event']->id)->first();
    lunchInfo($beta, [['lunch_diet' => ['vegan']]]);

    attendancePage($w['event'])->call('checkIn', $w['acme']->id)
        ->assertSee('1× Vegetarisch')->assertSee('2× Halal')->assertSee('1× Overig: Nuts')->assertDontSee('Veganistisch');
});

test('a company without dietary information is told so', function () {
    $w = attendanceWorld();

    attendancePage($w['event'])->call('checkIn', $w['acme']->id)->assertSee('No dietary information provided');
});

test('dietary wishes are gone once the retention cleanup removed the answers', function () {
    $w = attendanceWorld();
    $acme = Enrollment::query()->where('company_id', $w['acme']->id)->where('event_id', $w['event']->id)->first();
    $submission = lunchInfo($acme, [['lunch_diet' => ['halal']]]);
    $page = attendancePage($w['event'])->call('checkIn', $w['acme']->id)->assertSee('1× Halal');

    // The real cleanup, run after the retention period for the edition has passed.
    (new App\Support\Privacy\PrivacyRetention(now()->parse('2027-03-01')->addDays(10)))->run(); // past the 7-day dietary period, before the enrollment itself is purged
    expect($submission->fresh()?->answers ?? [])->not->toHaveKey('lunch_participants');
    $page->call('showDetails', $w['acme']->id)->assertDontSee('Halal')->assertSee('No dietary information provided');
});

test('authorization: viewing, checking in and dietary wishes are separate permissions', function () {
    $w = attendanceWorld();

    attendancePage($w['event'], ['attendance.view'])->call('checkIn', $w['acme']->id)->assertForbidden();
    expect(CompanyAttendance::query()->count())->toBe(0);

    $page = attendancePage($w['event'], ['attendance.view', 'attendance.update'])->call('checkIn', $w['acme']->id);
    $page->assertSee('Company Checked In')->assertDontSee('Dietary wishes')->assertDontSee('No dietary information provided');

    // Reopening details (a separate Livewire call) must not leak dietary data either.
    lunchInfo(Enrollment::query()->where('company_id', $w['acme']->id)->where('event_id', $w['event']->id)->first(), [['lunch_diet' => ['halal']]]);
    $page->call('closeDetails')->call('showDetails', $w['acme']->id)->assertDontSee('Halal')->assertDontSee('Dietary wishes');

    test()->actingAs(reviewerWithout(['events.view']));
    Livewire::test(Attendance::class)->assertForbidden();
});

test('permission sync hands the new attendance permissions to roles that already held the related ones', function () {
    $user = reviewerWithout(['event-stands.view', 'event-stands.update', 'enrollments.view', 'events.view']);
    $role = $user->roles->first();
    expect($role->hasPermissionTo('attendance.update'))->toBeFalse();

    App\Support\Permissions::sync();
    $role = $role->fresh();

    expect($role->hasPermissionTo('attendance.view'))->toBeFalse() // permissions already exist here, so nothing is retro-granted
        ->and(App\Support\Permissions::defined('attendance.dietary'))->toBeTrue();

    // First creation (as on deploy): drop them and sync again.
    Spatie\Permission\Models\Permission::query()->where('name', 'like', 'attendance.%')->delete();
    app(Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    App\Support\Permissions::sync();
    $role = $role->fresh();

    expect($role->hasPermissionTo('attendance.view'))->toBeTrue()->and($role->hasPermissionTo('attendance.update'))->toBeTrue()->and($role->hasPermissionTo('attendance.dietary'))->toBeTrue();
});

function lunchWorld(int $registered = 5): array
{
    $w = attendanceWorld();
    $acme = Enrollment::query()->where('company_id', $w['acme']->id)->where('event_id', $w['event']->id)->first();
    lunchInfo($acme, array_merge([['lunch_diet' => ['vegetarian']], ['lunch_diet' => ['halal']]], array_fill(0, $registered - 2, ['lunch_diet' => ['none']])));

    return $w;
}

test('lunch attendance supports partial counts, corrections and shows progress and statistics', function () {
    $w = lunchWorld();
    $page = attendancePage($w['event'])->call('openLunch', $w['acme']->id)
        ->assertSee('Registered participants')->assertSee('1× Vegetarisch')->assertSee('1× Halal')
        ->set('lunchCount', 3)->call('saveLunch')->assertHasNoErrors();

    expect(CompanyAttendance::query()->sole()->lunch_checked_in)->toBe(3)
        ->and($page->viewData('lunchStats'))->toBe(['registered' => 5, 'in' => 3, 'remaining' => 2])
        ->and($page->viewData('rows')->firstWhere('name', 'Acme')['lunch_in'])->toBe(3);
    $page->assertSee('3 / 5')->assertSee('No lunch registration'); // Beta has no registration

    $page->call('openLunch', $w['acme']->id)->set('lunchCount', 1)->call('saveLunch'); // correction downwards
    expect(CompanyAttendance::query()->sole()->lunch_checked_in)->toBe(1);
});

test('lunch counts are validated against zero and the registered number', function () {
    $w = lunchWorld();
    $page = attendancePage($w['event'])->call('openLunch', $w['acme']->id);

    $page->set('lunchCount', -1)->call('saveLunch')->assertHasErrors('lunchCount');
    $page->set('lunchCount', 6)->call('saveLunch')->assertHasErrors('lunchCount');
    expect(CompanyAttendance::query()->count())->toBe(0);
});

test('a stale lunch modal cannot overwrite a newer count', function () {
    $w = lunchWorld();
    $first = attendancePage($w['event'])->call('openLunch', $w['acme']->id);
    $second = attendancePage($w['event'])->call('openLunch', $w['acme']->id);

    $first->set('lunchCount', 2)->call('saveLunch');
    $second->set('lunchCount', 4)->call('saveLunch')->assertSet('lunchCount', 2)->assertSet('lunchLoaded', 2);
    expect(CompanyAttendance::query()->sole()->lunch_checked_in)->toBe(2);

    $second->set('lunchCount', 4)->call('saveLunch'); // after seeing the current value it can save
    expect(CompanyAttendance::query()->sole()->lunch_checked_in)->toBe(4);
});

test('lunch and company attendance are independent', function () {
    $w = lunchWorld();
    $page = attendancePage($w['event'])->call('openLunch', $w['acme']->id)->set('lunchCount', 2)->call('saveLunch');

    expect($page->viewData('stats')['present'])->toBe(0); // a lunch count does not check the company in

    $page->call('checkIn', $w['acme']->id)->call('reverse', $w['acme']->id);
    $row = CompanyAttendance::query()->sole();
    expect($row->checked_in_at)->toBeNull()->and($row->lunch_checked_in)->toBe(2); // reversing the company keeps the lunch count

    $page->call('checkIn', $w['acme']->id);
    expect(CompanyAttendance::query()->sole()->lunch_checked_in)->toBe(2);
});

test('lunch is isolated per edition and a company without registration cannot be checked in', function () {
    $w = lunchWorld();
    $other = attendancePage($w['other']);
    expect($other->viewData('lunchStats'))->toBe(['registered' => 0, 'in' => 0, 'remaining' => 0]);

    $page = attendancePage($w['event'])->call('openLunch', $w['beta']->id)->assertSet('lunchCompanyId', null);
    $page->set('lunchCount', 1)->call('saveLunch');
    expect(CompanyAttendance::query()->count())->toBe(0);
});

test('lunch permissions: updating needs attendance.update and diets need attendance.dietary', function () {
    $w = lunchWorld();

    $viewer = attendancePage($w['event'], ['attendance.view'])->call('openLunch', $w['acme']->id)->assertSee('Registered participants')->assertDontSee('Halal')->assertDontSee('Dietary wishes');
    $viewer->set('lunchCount', 2)->call('saveLunch')->assertForbidden();
    expect(CompanyAttendance::query()->count())->toBe(0);

    attendancePage($w['event'], ['attendance.view', 'attendance.update'])->call('openLunch', $w['acme']->id)->set('lunchCount', 2)->call('saveLunch')->assertDontSee('Halal');
    expect(CompanyAttendance::query()->sole()->lunch_checked_in)->toBe(2);
});

test('recorded lunch counts survive retention but the dietary wishes do not', function () {
    $w = lunchWorld();
    $page = attendancePage($w['event'])->call('openLunch', $w['acme']->id)->set('lunchCount', 3)->call('saveLunch');

    (new App\Support\Privacy\PrivacyRetention(now()->parse('2027-03-01')->addDays(10)))->run(); // dietary entries removed
    $page->call('openLunch', $w['acme']->id)->assertDontSee('Halal');

    Enrollment::query()->where('company_id', $w['acme']->id)->delete(); // registration gone entirely
    $page = attendancePage($w['event']);
    expect(CompanyAttendance::query()->sole()->lunch_checked_in)->toBe(3)
        ->and($page->viewData('rows')->firstWhere('name', 'Acme')['lunch_in'])->toBe(3);
    $page->assertSee('No lunch registration')->assertSee('3 recorded');
});

test('lunch count changes are audited', function () {
    $w = lunchWorld();
    attendancePage($w['event'])->call('openLunch', $w['acme']->id)->set('lunchCount', 2)->call('saveLunch');

    $log = Spatie\Activitylog\Models\Activity::query()->where('log_name', 'attendance')->latest('id')->first();
    expect($log->causer_id)->toBe(auth()->id())->and($log->properties['attributes']['lunch_checked_in'])->toBe(2);
});
