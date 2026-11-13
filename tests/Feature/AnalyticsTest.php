<?php

use App\Filament\Pages\Dashboard;
use App\Models\Company;
use App\Models\Enrollment;
use App\Models\Event;
use App\Models\EventStand;
use App\Models\User;
use App\Services\Analytics\Analytics;
use App\Services\Analytics\CateringStats;
use App\Services\Analytics\ChartCatalog;
use App\Services\Analytics\Comparison;
use App\Services\Analytics\EnrollmentStats;
use App\Services\Analytics\EventPlanningStats;
use App\Services\Analytics\Kpis;
use App\Services\Analytics\ListCatalog;
use App\Services\Analytics\Participation;
use App\Services\Analytics\Scope;
use App\Services\Analytics\TaxonomyStats;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

/*
| Fixture (today is fixed at 2026-11-10; E5 is upcoming and therefore provisional):
|
|   edition        E1 2023   E2 2024   E3 2025   E4 2026   E5 2027
|   source         stands    stands    approved  approved  approved       (E3 is the first edition with enrollments)
|   A  every one     x         x         x         x         x
|   B  once          x
|   C  misses E2/E4  x                   x                   x
|   D  new in E5                                             x
|   E  approved, no stand                          x         x
|   F  stand records only (E2 counts, E4 stand is grandfathered)   F: E2 stand, E4 stand w/o enrollment
|   G  several revisions of the second form                  x
*/

require_once __DIR__.'/../Support/AnalyticsFixtures.php';

beforeEach(function () {
    $this->withoutVite();
    Carbon::setTestNow('2026-11-10 12:00:00');
    $this->world = analyticsWorld();
});

afterEach(fn () => Carbon::setTestNow());

// Participation: sources and classification

test('editions are ordered chronologically and the participation source switches at the first enrollment edition', function () {
    $editions = app(Participation::class)->editions();

    expect(array_column($editions, 'name'))->toBe(['Editie 2023', 'Editie 2024', 'Editie 2025', 'Editie 2026', 'Editie 2027'])
        ->and(array_column($editions, 'enrollments'))->toBe([false, false, true, true, true])
        ->and(array_column($editions, 'provisional'))->toBe([false, false, false, false, true]);
});

test('historical participation comes from stands and new participation from approved enrollments', function () {
    ['e' => $e, 'c' => $c] = $this->world;
    $sets = app(Participation::class)->sets();

    expect(analyticsSorted($sets[$e[1]->id]))->toBe(analyticsIds($c, ['A', 'B', 'C']))
        ->and(analyticsSorted($sets[$e[2]->id]))->toBe(analyticsIds($c, ['A', 'F']))
        ->and(analyticsSorted($sets[$e[3]->id]))->toBe(analyticsIds($c, ['A', 'C']))
        ->and(analyticsSorted($sets[$e[5]->id]))->toBe(analyticsIds($c, ['A', 'C', 'D', 'E', 'G']));
});

test('a company with both an approved enrollment and stands is counted once and a stand without enrollment is not participation', function () {
    ['e' => $e, 'c' => $c] = $this->world;
    $participation = app(Participation::class);

    expect(array_count_values($participation->sets()[$e[5]->id]))->each->toBe(1)
        ->and(analyticsSorted($participation->sets()[$e[4]->id]))->toBe(analyticsIds($c, ['A', 'E']))
        ->and($participation->grandfathered()[$e[4]->id])->toBe([$c['F']->id])
        ->and($participation->grandfathered()[$e[2]->id])->toBe([]);
});

test('pending and rejected enrollments are not participation', function () {
    ['e' => $e, 'c' => $c] = $this->world;
    $set = app(Participation::class)->sets()[$e[5]->id];

    expect($set)->not->toContain($c['H']->id)->and($set)->not->toContain($c['I']->id);
});

test('new, returning, retained, reactivated and lost companies are classified against the previous edition', function () {
    ['e' => $e, 'c' => $c] = $this->world;
    $p = app(Participation::class);

    $e2 = $p->classify($e[2]->id);
    expect($e2['new'])->toBe([$c['F']->id])->and($e2['returning'])->toBe([$c['A']->id])->and(analyticsSorted($e2['lost']))->toBe(analyticsIds($c, ['B', 'C']));

    $e3 = $p->classify($e[3]->id);
    expect($e3['new'])->toBe([])->and(analyticsSorted($e3['returning']))->toBe(analyticsIds($c, ['A', 'C']))->and($e3['retained'])->toBe([$c['A']->id])
        ->and($e3['reactivated'])->toBe([$c['C']->id])->and($e3['lost'])->toBe([$c['F']->id]);

    $e5 = $p->classify($e[5]->id);
    expect(analyticsSorted($e5['new']))->toBe(analyticsIds($c, ['D', 'G']))->and(analyticsSorted($e5['returning']))->toBe(analyticsIds($c, ['A', 'C', 'E']))
        ->and(analyticsSorted($e5['retained']))->toBe(analyticsIds($c, ['A', 'E']))->and($e5['reactivated'])->toBe([$c['C']->id])->and($e5['lost'])->toBe([]);
});

test('retention, churn and return rate use the documented formulas and edition one has no predecessor', function () {
    ['e' => $e] = $this->world;
    $p = app(Participation::class);

    $first = $p->classify($e[1]->id);
    expect($first['has_previous'])->toBeFalse()->and($first['retention'])->toBeNull()->and($first['churn'])->toBeNull()
        ->and(count($first['new']))->toBe(3)->and($first['new_rate'])->toBe(100.0);

    $e2 = $p->classify($e[2]->id);
    expect($e2['retention'])->toBe(33.3)->and($e2['churn'])->toBe(66.7)->and($e2['return_rate'])->toBe(50.0);

    $e4 = $p->classify($e[4]->id);
    expect($e4['retention'])->toBe(50.0)->and($e4['churn'])->toBe(50.0);
});

test('the upcoming edition is provisional', function () {
    ['e' => $e] = $this->world;

    expect(app(Participation::class)->classify($e[5]->id)['provisional'])->toBeTrue()
        ->and(app(Participation::class)->classify($e[4]->id)['provisional'])->toBeFalse();
});

test('history figures count editions per company and the longest consecutive streak', function () {
    $h = app(Participation::class)->history();

    expect($h['ever'])->toBe(7)->and($h['at_least_2'])->toBe(3)->and($h['at_least_3'])->toBe(2)->and($h['at_least_5'])->toBe(1)
        ->and($h['average'])->toBe(2.0)->and($h['longest_streak'])->toBe(5)
        ->and($h['attended'])->toBe(['1' => 4, '2' => 1, '3' => 1, '4' => 0, '5+' => 1])
        ->and($h['streaks'])->toBe(['1' => 5, '2' => 1, '3' => 0, '4' => 0, '5+' => 1]);
});

test('company rows carry first and last participation, streaks and a status', function () {
    ['e' => $e, 'c' => $c] = $this->world;
    $rows = collect(app(Participation::class)->companyRows($e[5]->id))->keyBy('id');

    expect($rows[$c['A']->id])->toMatchArray(['first' => 'Editie 2023', 'last' => 'Editie 2027', 'total' => 5, 'streak' => 5, 'longest' => 5, 'status' => 'Behouden'])
        ->and($rows[$c['C']->id])->toMatchArray(['total' => 3, 'streak' => 1, 'longest' => 1, 'previous' => false, 'selected' => true, 'status' => 'Gereactiveerd'])
        ->and($rows[$c['D']->id]['status'])->toBe('Nieuw')
        ->and($rows[$c['E']->id])->toMatchArray(['streak' => 2, 'status' => 'Behouden'])
        ->and($rows[$c['B']->id])->toMatchArray(['selected' => false, 'status' => 'Niet teruggekeerd', 'streak' => 0])
        ->and($rows[$c['C']->id]['timeline'])->toBe([true, false, true, false, true])
        ->and($rows->has($c['H']->id))->toBeFalse();
});

test('company rows stop at the selected edition', function () {
    ['e' => $e, 'c' => $c] = $this->world;
    $rows = collect(app(Participation::class)->companyRows($e[2]->id))->keyBy('id');

    expect($rows->has($c['D']->id))->toBeFalse()->and($rows[$c['A']->id]['total'])->toBe(2)->and($rows[$c['F']->id]['status'])->toBe('Nieuw');
});

test('cohorts show how many of a first-edition group came back and leave unknown cells empty', function () {
    ['e' => $e] = $this->world;
    $cohorts = collect(app(Participation::class)->cohorts())->keyBy('id');

    expect($cohorts[$e[1]->id]['size'])->toBe(3)->and($cohorts[$e[1]->id]['cells'])->toBe([3, 1, 2, 1, null])
        ->and($cohorts[$e[2]->id]['cells'])->toBe([1, 0, 0, null])
        ->and($cohorts[$e[3]->id]['cells'])->toBe([0, 0, null])
        ->and($cohorts[$e[4]->id]['cells'])->toBe([1, null])
        ->and($cohorts[$e[5]->id]['cells'])->toBe([2]);
});

test('an edition without anything is empty and has no division by zero', function () {
    $empty = analyticsEdition('Lege editie', '2020-03-01', ['max_stands' => 0, 'partner_stand_count' => 0]);
    $p = app(Participation::class);
    $c = $p->classify($empty->id);

    expect($c['participants'])->toBe(0)->and($c['retention'])->toBeNull()->and($c['churn'])->toBeNull()->and($c['return_rate'])->toBeNull();

    $stands = app(EventPlanningStats::class)->stands($empty->id);
    expect($stands['occupancy'])->toBeNull()->and($stands['company_free'])->toBe(0);

    $summary = app(EnrollmentStats::class)->summary($empty->id);
    expect($summary['approval_rate'])->toBeNull()->and($summary['info_rate'])->toBeNull()->and($summary['avg_review_seconds'])->toBeNull()->and($summary['oldest_pending'])->toBeNull();
});

test('no editions at all does not break the participation service', function () {
    Event::query()->delete();
    app(Analytics::class)->flush();
    $p = app(Participation::class);

    expect($p->editions())->toBe([])->and($p->history()['ever'])->toBe(0)->and($p->history()['average'])->toBeNull()->and($p->cohorts())->toBe([]);
});

// Enrollments

test('enrollment figures use reviewed enrollments as the denominator', function () {
    ['e' => $e] = $this->world;
    $s = app(EnrollmentStats::class)->summary($e[5]->id);

    expect($s)->toMatchArray(['total' => 7, 'approved' => 5, 'pending' => 1, 'rejected' => 1, 'approval_rate' => 83.3, 'rejection_rate' => 16.7])
        ->and($s['avg_review_seconds'])->toBe(2 * 86400)
        ->and(Carbon::parse($s['oldest_pending'])->toDateString())->toBe('2026-12-31');
});

test('second form completion counts approved enrollments without a submission as missing', function () {
    ['e' => $e] = $this->world;
    $s = app(EnrollmentStats::class)->summary($e[5]->id);

    expect($s['info_done'])->toBe(3)->and($s['info_missing'])->toBe(2)->and($s['info_rate'])->toBe(60.0);
});

test('registration progress can be compared by days before the edition', function () {
    ['e' => $e] = $this->world;
    $curve = app(EnrollmentStats::class)->daysBefore($e[5]->id, 90);

    // Approved/pending/rejected were all submitted 60 days before 2027-03-01.
    expect($curve[90])->toBe(0)->and($curve[61])->toBe(0)->and($curve[60])->toBe(7)->and($curve[0])->toBe(7);
});

test('enrollment trends can be filtered by status and period', function () {
    ['e' => $e] = $this->world;
    $stats = app(EnrollmentStats::class);

    $all = $stats->trends(new Scope($e[5], null));
    expect(array_sum($all['days']))->toBe(7)->and(end($all['cumulative']))->toBe(7)->and(end($all['backlog']))->toBe(1);

    $pending = $stats->trends(new Scope($e[5], null, 'pending'));
    expect(array_sum($pending['days']))->toBe(1);

    $none = $stats->trends(new Scope($e[5], null, null, Carbon::parse('2030-01-01')));
    expect($none['days'])->toBe([]);
});

test('newly created company records are told apart from reused ones', function () {
    ['e' => $e, 'c' => $c] = $this->world;
    // Created long before the enrollment came in: reused. One extra company created after submission: created by approval.
    $late = Company::create(['name' => 'Aangemaakt bij goedkeuring']);
    $late->forceFill(['created_at' => '2027-02-01'])->saveQuietly();
    analyticsEnroll($e[5], $late);
    app(Analytics::class)->flush();

    $s = app(EnrollmentStats::class)->summary($e[5]->id);
    expect($s['approved'])->toBe(6)->and($s['new_records'])->toBe(1);
});

// Stands

test('stand occupancy counts occupied company stands against the available ones and keeps partners separate', function () {
    ['e' => $e, 'c' => $c] = $this->world;
    $s = app(EventPlanningStats::class)->stands($e[5]->id);

    expect($s)->toMatchArray(['company_total' => 5, 'company_occupied' => 4, 'company_free' => 1, 'occupancy' => 80.0, 'partner_total' => 2, 'partner_occupied' => 1, 'partner_free' => 1, 'allocated_companies' => 3, 'free_partner_numbers' => ['2']])
        ->and($s['multiple'])->toBe([$c['A']->id => 2]);
});

test('approved companies without a stand are listed and stands without approval are flagged but not changed', function () {
    ['e' => $e, 'c' => $c] = $this->world;
    $s = app(EventPlanningStats::class)->stands($e[5]->id);

    expect(analyticsSorted($s['approved_without_stand']))->toBe(analyticsIds($c, ['D', 'E', 'G']))
        ->and($s['grandfathered'])->toBe([$c['B']->id])
        ->and($s['warnings'])->toBe(1)
        ->and(EventStand::query()->where('event_id', $e[5]->id)->where('company_id', $c['B']->id)->exists())->toBeTrue();
});

test('older editions have no approved-without-stand figure because they have no enrollments', function () {
    ['e' => $e] = $this->world;

    expect(app(EventPlanningStats::class)->stands($e[2]->id)['approved_without_stand'])->toBeNull();
});

// Revenue

test('expected revenue sums accepted fees of approved enrollments per currency', function () {
    ['e' => $e] = $this->world;
    $money = app(EventPlanningStats::class)->revenue()[$e[5]->id]['currencies']['EUR'];

    expect($money)->toBe(['approved' => 120000, 'count' => 5, 'new' => 45000, 'returning' => 75000, 'pending' => 10000, 'rejected' => 30000]);
});

test('editions without enrollments have no revenue figure instead of zero', function () {
    ['e' => $e] = $this->world;

    expect(app(EventPlanningStats::class)->revenue()[$e[1]->id])->toBeNull()
        ->and(app(EventPlanningStats::class)->perEdition()[$e[1]->id]['revenue'])->toBeNull();
});

test('amounts in different currencies are never added together', function () {
    ['e' => $e, 'c' => $c] = $this->world;
    analyticsEnroll($e[4], $c['G'], extra: ['currency_accepted' => 'USD', 'fee_cents_accepted' => 5000]);
    app(Analytics::class)->flush();

    $currencies = app(EventPlanningStats::class)->revenue()[$e[4]->id]['currencies'];
    expect($currencies['EUR']['approved'])->toBe(50000)->and($currencies['USD']['approved'])->toBe(5000)
        ->and(App\Services\Analytics\Fmt::moneyPerCurrency(['EUR' => 50000, 'USD' => 5000]))->toBe('€ 500,00 + USD 50,00');
});

// Catering

test('catering uses only the latest revision of the second form', function () {
    ['e' => $e] = $this->world;
    $lunch = app(CateringStats::class)->forEvent($e[5]->id)['lunch'];

    expect($lunch['participants'])->toBe(5)->and($lunch['companies'])->toBe(2)->and($lunch['missing'])->toBe(2);
});

test('dietary selections may overlap and are counted per selection', function () {
    ['e' => $e] = $this->world;
    $lunch = app(CateringStats::class)->forEvent($e[5]->id)['lunch'];

    expect($lunch['diet'])->toBe(['vegetarian' => 2, 'vegan' => 1, 'halal' => 1, 'gluten_free' => 1, 'lactose_free' => 1, 'other' => 0])
        ->and($lunch['none'])->toBe(1)->and($lunch['diet_known'])->toBe(5);
});

test('closing drinks are counted from the latest answer with missing answers kept apart', function () {
    ['e' => $e] = $this->world;
    $c = app(CateringStats::class)->forEvent($e[5]->id);
    $d = $c['drinks'];

    expect($d)->toMatchArray(['yes' => 2, 'no' => 0, 'unsure' => 1, 'missing' => 2, 'people' => 6])
        ->and($c['approved'])->toBe(5)->and($c['missing'])->toBe(2);
});

test('promotional materials follow the stored option values', function () {
    ['e' => $e, 'c' => $c] = $this->world;
    $p = app(CateringStats::class)->forEvent($e[5]->id)['promo'];

    expect($p)->toMatchArray(['small' => 2, 'medium' => 2, 'large' => 1, 'multiple' => 2, 'screens' => 1, 'prototypes' => 0, 'missing' => 2])
        ->and($p['special'])->toHaveCount(1)->and($p['special'][0]['company_id'])->toBe($c['G']->id);
});

test('catering of an edition without stored second forms is reported as unavailable, not zero', function () {
    ['e' => $e] = $this->world;
    $c = app(CateringStats::class)->forEvent($e[4]->id);

    expect($c['available'])->toBeFalse()
        ->and(app(CateringStats::class)->perEdition())->toHaveKey($e[5]->id)->not->toHaveKey($e[4]->id)
        ->and(app(EventPlanningStats::class)->perEdition()[$e[4]->id]['lunch'])->toBeNull();
});

// Sectors and educations

test('sector distribution counts participants per sector and may exceed the number of companies', function () {
    ['e' => $e, 'sectors' => [$s1, $s2]] = $this->world;
    $rows = collect(app(TaxonomyStats::class)->distribution('sector', $e[5]->id))->keyBy('id');

    expect($rows[$s1->id])->toMatchArray(['total' => 3, 'new' => 1, 'returning' => 2])
        ->and($rows[$s2->id])->toMatchArray(['total' => 2, 'new' => 1, 'returning' => 1])
        ->and($rows->sum('total'))->toBeGreaterThan(count(app(Participation::class)->sets()[$e[5]->id]) - 2);
});

test('education reach counts companies targeting several educations', function () {
    ['e' => $e] = $this->world;

    expect(app(TaxonomyStats::class)->educationReach($e[5]->id))->toBe(['multiple' => 1, 'none' => 3, 'companies' => 5]);
});

test('sector distribution per edition feeds the trend charts', function () {
    ['e' => $e, 'sectors' => [$s1]] = $this->world;
    $all = app(TaxonomyStats::class)->perEdition('sector');

    expect($all[$e[1]->id][$s1->id])->toBe(2)->and($all[$e[5]->id][$s1->id])->toBe(3);
});

// Comparisons

test('edition comparison shows absolute and relative change', function () {
    ['e' => $e] = $this->world;
    $table = app(Comparison::class)->table([$e[5]->id, $e[4]->id], analyticsAdmin());
    $row = collect($table['rows'])->firstWhere('label', 'Goedgekeurde / geregistreerde bedrijven');

    expect($row['values'])->toBe(['5', '2'])->and($row['absolute'])->toBe('+3')->and($row['percent'])->toBe('+150,0%');
});

test('a zero baseline gives no percentage and missing data is marked as not recorded', function () {
    ['e' => $e] = $this->world;
    $table = app(Comparison::class)->table([$e[5]->id, $e[3]->id], analyticsAdmin());
    $new = collect($table['rows'])->firstWhere('label', 'Nieuwe bedrijven');
    $lunch = collect($table['rows'])->firstWhere('label', 'Lunchinschrijvingen');

    expect($new['values'])->toBe(['2', '0'])->and($new['percent'])->toBe('n.v.t.')
        ->and($lunch['values'][1])->toBe('Niet vastgelegd')->and($lunch['percent'])->toBe(App\Services\Analytics\Fmt::DASH);
});

test('the provisional retention of the upcoming edition is flagged in comparisons', function () {
    ['e' => $e] = $this->world;
    $row = collect(app(Comparison::class)->table([$e[5]->id, $e[4]->id], analyticsAdmin())['rows'])->firstWhere('label', 'Retentiepercentage');

    expect($row['note'])->toContain('voorlopig');
});

test('percentage change handles a zero baseline and unknown values', function () {
    expect(App\Services\Analytics\Fmt::change(5, 0))->toBe(['absolute' => 5, 'percent' => null])
        ->and(App\Services\Analytics\Fmt::change(null, 3))->toBe(['absolute' => null, 'percent' => null])
        ->and(App\Services\Analytics\Fmt::change(3, 6)['percent'])->toBe(-50.0);
});

// Filters

test('the default edition is the next upcoming one, else the most recent', function () {
    ['e' => $e] = $this->world;

    expect(Scope::from(null)->event->id)->toBe($e[5]->id)->and(Scope::from(null)->compare->id)->toBe($e[4]->id);

    Carbon::setTestNow('2030-01-01');
    app(Analytics::class)->flush();
    expect(Scope::from(null)->event->id)->toBe($e[5]->id);
    Carbon::setTestNow('2026-11-10 12:00:00');
});

test('filters select edition, comparison edition, status and period', function () {
    ['e' => $e] = $this->world;
    $scope = Scope::from(['edition' => (string) $e[3]->id, 'compare' => (string) $e[1]->id, 'status' => 'pending', 'from' => '2025-01-01', 'until' => '2025-02-01']);

    expect($scope->event->id)->toBe($e[3]->id)->and($scope->compare->id)->toBe($e[1]->id)->and($scope->status)->toBe('pending')
        ->and($scope->from->toDateString())->toBe('2025-01-01')->and($scope->until->toDateString())->toBe('2025-02-01');

    $all = Scope::from(['edition' => 'all']);
    expect($all->event)->toBeNull()->and($all->compare)->toBeNull();

    expect(Scope::from(['edition' => (string) $e[3]->id, 'compare' => (string) $e[3]->id])->compare)->toBeNull()
        ->and(Scope::from(['status' => 'bogus'])->status)->toBeNull();
});

// Authorization and the page

test('stat cards only appear for the permissions of the viewer', function () {
    ['e' => $e] = $this->world;
    $scope = new Scope($e[5], $e[4]);
    $labels = fn (User $u, string $section = 'overview') => array_column(app(Kpis::class)->section($section, $scope, $u), 'label');

    $limited = analyticsUser(['dashboard.view']);
    expect($labels($limited))->not->toContain('Goedgekeurde bedrijven')->not->toContain('Verwachte deelnamekosten')->not->toContain('Bezette stands');

    $viewer = analyticsUser(['dashboard.view', 'enrollments.view']);
    expect($labels($viewer))->toContain('Goedgekeurde bedrijven')->not->toContain('Verwachte deelnamekosten')->not->toContain('Lunchdeelnemers'.'x');

    $finance = analyticsUser(['dashboard.view', 'statistics.financial']);
    expect($labels($finance))->toContain('Verwachte deelnamekosten')->not->toContain('Goedgekeurde bedrijven')
        ->and($labels($viewer, 'finance'))->toBe([])->and($labels($finance, 'finance'))->toContain('Verwachte deelnamekosten');
});

test('charts and lists are withheld from users without the matching permission', function () {
    ['e' => $e] = $this->world;
    $scope = new Scope($e[5], $e[4]);
    $limited = analyticsUser(['dashboard.view']);
    $charts = app(ChartCatalog::class);

    foreach (array_keys(ChartCatalog::CHARTS) as $key) {
        expect($charts->make($key, $scope, $limited))->toBeNull();
    }

    expect(app(ListCatalog::class)->rows('participation', $scope, $limited))->toBe([])
        ->and(app(ListCatalog::class)->rows('promo_special', $scope, $limited))->toBe([])
        ->and($charts->make('revenue_per_edition', $scope, analyticsUser(['enrollments.view'])))->toBeNull()
        ->and($charts->make('revenue_per_edition', $scope, analyticsUser(['statistics.financial'])))->not->toBeNull();
});

test('action items are only counted for permitted sections', function () {
    ['e' => $e] = $this->world;
    $scope = new Scope($e[5], $e[4]);
    $items = fn (User $u) => collect(app(App\Services\Analytics\AdminStats::class)->items($scope, $u))->pluck('count', 'key')->all();

    expect($items(analyticsUser(['dashboard.view'])))->toBe([])
        ->and($items(analyticsUser(['enrollments.view'])))->toHaveKeys(['pending', 'info_missing', 'expired_invitations'])->not->toHaveKey('no_stand')
        ->and($items(analyticsUser(['enrollments.view']))['pending'])->toBe(1);

    $stands = $items(analyticsUser(['event-stands.view']));
    expect($stands)->toMatchArray(['no_stand' => 3, 'stand_warnings' => 1])->not->toHaveKey('pending');
});

test('lists contain company names and stand numbers only', function () {
    ['e' => $e, 'c' => $c] = $this->world;
    $scope = new Scope($e[5], $e[4]);
    $admin = analyticsAdmin();
    $rows = app(ListCatalog::class)->rows('approved_no_stand', $scope, $admin);

    expect(collect($rows)->pluck('name')->sort()->values()->all())->toBe(['Bedrijf D', 'Bedrijf E', 'Bedrijf G'])
        ->and(collect($rows)->first())->toHaveKeys(['id', 'company_id', 'name'])
        ->and(app(ListCatalog::class)->rows('promo_special', $scope, $admin)[0]['items'])->toContain('Large: Screens')
        ->and(app(ListCatalog::class)->rows('flagged_stands', $scope, $admin)[0]['reason'])->toContain('No enrollment');
});

test('the dashboard renders for an administrator with filters applied', function () {
    ['e' => $e] = $this->world;
    $this->actingAs(analyticsAdmin());

    Livewire::test(Dashboard::class)
        ->assertOk()
        ->assertSee('Overzicht')->assertSee('Bedrijven en retentie')->assertSee('Historische vergelijking')->assertSee('Administratie')
        ->set('filters.edition', (string) $e[3]->id)
        ->assertSet('filters.edition', (string) $e[3]->id)
        ->set('filters.edition', 'all')
        ->assertOk();
});

test('the dashboard page opens for a user with only the dashboard permission', function () {
    $this->actingAs(analyticsUser(['dashboard.view']));

    $this->get('/dashboard')->assertOk()->assertDontSee('Verwachte deelnamekosten');
});

test('the financial tab is hidden without the financial permission', function () {
    $this->actingAs(analyticsUser(['dashboard.view', 'enrollments.view']));
    $this->get('/dashboard')->assertOk()->assertDontSee('Financieel');

    $this->actingAs(analyticsUser(['dashboard.view', 'statistics.financial']));
    $this->get('/dashboard')->assertOk()->assertSee('Financieel');
});

// Performance

test('participation queries do not grow with the number of companies', function () {
    ['e' => $e] = $this->world;
    $count = function () use ($e): int {
        app(Analytics::class)->flush();
        Illuminate\Support\Facades\Cache::flush();
        DB::flushQueryLog();
        DB::enableQueryLog();
        $p = app(Participation::class);
        $p->companyRows($e[5]->id);
        $p->cohorts();
        $p->history();

        return count(DB::getQueryLog());
    };

    $before = $count();

    foreach (range(1, 25) as $i) {
        $company = Company::create(['name' => "Extra {$i}"]);
        analyticsEnroll($e[4], $company);
        analyticsEnroll($e[5], $company);
    }

    expect($count())->toBeLessThanOrEqual($before + 2);
});

test('catering queries do not grow with the number of enrollments', function () {
    ['e' => $e] = $this->world;
    $count = function () use ($e): int {
        app(Analytics::class)->flush();
        Illuminate\Support\Facades\Cache::flush();
        DB::flushQueryLog();
        DB::enableQueryLog();
        app(CateringStats::class)->forEvent($e[5]->id);

        return count(DB::getQueryLog());
    };

    $before = $count();

    foreach (range(1, 15) as $i) {
        $enrollment = analyticsEnroll($e[5], Company::create(['name' => "Catering {$i}"]));
        analyticsInfo($enrollment, 1, ['lunch_count' => 1, 'lunch_participants' => [['lunch_diet' => ['vegan']]], 'drinks_attending' => 'yes', 'drinks_count' => 1, 'promo_materials' => []]);
    }

    expect($count())->toBe($before);
});

test('cached figures are refreshed when the underlying data changes', function () {
    ['e' => $e, 'c' => $c] = $this->world;
    $before = count(app(Participation::class)->sets()[$e[5]->id]);

    analyticsEnroll($e[5], Company::create(['name' => 'Nieuw erbij']));
    app()->forgetInstance(Analytics::class); // the next request: the fingerprint is memoized per request

    expect(count(app(Participation::class)->sets()[$e[5]->id]))->toBe($before + 1);
});

// Livewire refuses a request with more components than payload.max_components, and every mounted widget takes part in
// each filter update. Only the active tab may be mounted.

test('no dashboard tab needs more widgets than Livewire allows in one request', function () {
    $this->actingAs(analyticsAdmin());
    $limit = (int) config('livewire.payload.max_components', 20);
    $page = Livewire::test(Dashboard::class)->instance();
    $tabs = (new ReflectionMethod($page, 'tabs'))->invoke($page);

    expect($limit)->toBeLessThanOrEqual(20)->and($tabs)->toHaveCount(9);

    foreach ($tabs as $label => $tab) {
        expect(count($tab['widgets']) + 1)->toBeLessThanOrEqual($limit, "{$label} mounts too many widgets");
    }
});

test('only the widgets of the active tab are mounted and switching tabs swaps them', function () {
    $this->actingAs(analyticsAdmin());
    $widgets = fn (string $html): int => substr_count($html, 'wire:name="App\Filament\Widgets');

    $page = Livewire::test(Dashboard::class);
    $overview = $widgets($page->html());
    expect($overview)->toBeGreaterThan(0)->toBeLessThan(10)->and($page->html())->not->toContain('Retentiecohorten');

    $page->set('activeTab', 'bedrijven-en-retentie')->assertSet('activeTab', 'bedrijven-en-retentie');
    expect($widgets($page->html()))->toBeGreaterThan($overview)->toBeLessThan(20);

    // An unknown tab falls back to the first one instead of mounting nothing or everything.
    $page->set('activeTab', 'bestaat-niet');
    expect($widgets($page->html()))->toBe($overview);
});

test('changing a global filter keeps the active tab and stays within the component limit', function () {
    ['e' => $e] = $this->world;
    $this->actingAs(analyticsAdmin());

    $page = Livewire::test(Dashboard::class)->set('activeTab', 'bedrijven-en-retentie')
        ->set('filters.edition', (string) $e[3]->id)->set('filters.status', 'approved')->assertOk();

    expect($page->get('activeTab'))->toBe('bedrijven-en-retentie')->and(substr_count($page->html(), 'wire:name="App\Filament\Widgets'))->toBeLessThan(20);
});
