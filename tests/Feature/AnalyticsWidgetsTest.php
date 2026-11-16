<?php

use App\Filament\Resources\Companies\CompanyResource;
use App\Filament\Widgets\Analytics\ChartWidget;
use App\Filament\Widgets\Analytics\CohortWidget;
use App\Filament\Widgets\Analytics\ComparisonWidget;
use App\Filament\Widgets\Analytics\KpiWidget;
use App\Filament\Widgets\Analytics\ListWidget;
use App\Filament\Widgets\Analytics\NotesWidget;
use App\Services\Analytics\ChartCatalog;
use App\Services\Analytics\Kpis;
use App\Services\Analytics\ListCatalog;
use App\Services\Analytics\Participation;
use App\Services\Analytics\Scope;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

require_once __DIR__.'/../Support/AnalyticsFixtures.php';

beforeEach(function () {
    $this->withoutVite();
    Carbon::setTestNow('2026-11-10 12:00:00');
    $this->world = analyticsWorld();
});

afterEach(fn () => Carbon::setTestNow());

const ANALYTICS_KPI_SECTIONS = ['overview', 'actions', 'admin', 'enrollments', 'breakdown', 'retention', 'stands', 'finance', 'catering', 'promo', 'taxonomy'];

/** Mounting a Filament widget costs seconds, so each widget class is mounted once; every key is computed through the catalogs. */
test('each widget class renders for an administrator', function () {
    ['e' => $e] = $this->world;
    $this->actingAs(analyticsAdmin());
    $filters = ['pageFilters' => ['edition' => (string) $e[5]->id, 'compare' => (string) $e[4]->id]];

    Livewire::test(KpiWidget::class, ['section' => 'overview'] + $filters)->assertOk()->assertSee('Goedgekeurde bedrijven')->assertSee('Verwachte deelnamekosten');
    Livewire::test(ChartWidget::class, ['chart' => 'retention_new_returning'] + $filters)->assertOk()->assertSee('Nieuwe en terugkerende bedrijven per editie');
    Livewire::test(CohortWidget::class)->assertOk()->assertSee('Retentiecohorten');
    Livewire::test(ComparisonWidget::class, ['mode' => 'pair'] + $filters)->assertOk()->assertSee('Editie 2027');
    Livewire::test(ComparisonWidget::class, ['mode' => 'all'] + $filters)->assertOk()->assertSee('Editie 2023');
    Livewire::test(NotesWidget::class, ['section' => 'retention'])->assertOk()->assertSee('Retentie =');
});

test('every stat section, chart and list can be computed for each kind of selection', function () {
    ['e' => $e] = $this->world;
    $admin = analyticsAdmin();
    $empty = analyticsEdition('Lege editie', '2020-03-01');
    $selections = [new Scope($e[5], $e[4]), new Scope($e[1], null), new Scope($empty, null), new Scope(null, null, 'approved')];

    foreach ($selections as $scope) {
        foreach (ANALYTICS_KPI_SECTIONS as $section) {
            expect(app(Kpis::class)->section($section, $scope, $admin))->toBeArray();
        }

        foreach (array_keys(ChartCatalog::CHARTS) as $key) {
            $chart = app(ChartCatalog::class)->make($key, $scope, $admin);
            expect($chart)->toHaveKeys(['type', 'labels', 'datasets', 'options', 'empty']);
        }

        foreach (array_keys(ListCatalog::LISTS) as $key) {
            expect(app(ListCatalog::class)->rows($key, $scope, $admin))->toBeArray();
        }
    }
});

test('stat cards for the upcoming edition carry the headline numbers', function () {
    ['e' => $e] = $this->world;
    $cards = collect(app(Kpis::class)->section('overview', new Scope($e[5], $e[4]), analyticsAdmin()))->keyBy('label');

    expect($cards['Goedgekeurde bedrijven']['value'])->toBe('5')->and($cards['Goedgekeurde bedrijven']['description'])->toContain('+3')
        ->and($cards['Nieuwe bedrijven']['value'])->toBe('2')->and($cards['Terugkerende bedrijven']['value'])->toBe('3')
        ->and($cards['Bezette stands']['value'])->toBe('4')->and($cards['Standbezetting']['value'])->toBe('80,0%')
        ->and($cards['Verwachte deelnamekosten']['value'])->toBe('€ 1.200,00')->and($cards['Lunchdeelnemers']['value'])->toBe('5')
        ->and($cards['Verwachte borrelgasten']['value'])->toBe('6');

    $retention = collect(app(Kpis::class)->section('retention', new Scope($e[5], $e[4]), analyticsAdmin()))->keyBy('label');
    expect($retention['Retentiepercentage']['value'])->toBe('100,0%')->and($retention['Retentiepercentage']['description'])->toContain('Voorlopig')
        ->and($retention['Bedrijven ooit deelgenomen']['value'])->toBe('7')->and($retention['Langste aaneengesloten reeks']['value'])->toBe('5 edities');
});

test('catering cards say unavailable instead of zero when the second forms are gone', function () {
    ['e' => $e] = $this->world;
    $cards = app(Kpis::class)->section('catering', new Scope($e[4], null), analyticsAdmin());

    expect($cards)->toHaveCount(1)->and($cards[0]['label'])->toBe('Niet beschikbaar');
});

test('users without the matching permission get nothing from the catalogs', function () {
    ['e' => $e] = $this->world;
    $this->actingAs(analyticsUser(['dashboard.view']));
    $scope = new Scope($e[5], $e[4]);
    $user = auth()->user();

    foreach (['enrollments', 'breakdown', 'retention', 'stands', 'finance', 'catering', 'promo', 'admin'] as $section) {
        expect(app(Kpis::class)->section($section, $scope, $user))->toBe([]);
    }

    $labels = array_column(app(Kpis::class)->section('overview', $scope, $user), 'label');
    expect($labels)->not->toContain('Goedgekeurde bedrijven')->not->toContain('Verwachte deelnamekosten')->not->toContain('Lunchdeelnemers');

    Livewire::test(ListWidget::class, ['list' => 'participation', 'pageFilters' => ['edition' => (string) $e[5]->id]])->assertDontSee('Bedrijf A');
});

test('the company participation table searches, sorts, filters and links to the company page', function () {
    ['e' => $e, 'c' => $c] = $this->world;
    $this->actingAs(analyticsAdmin());

    Livewire::test(ListWidget::class, ['list' => 'participation', 'pageFilters' => ['edition' => (string) $e[5]->id]])
        ->assertSee('Bedrijf A')->assertSee('Bedrijf D')->assertDontSee('Bedrijf H')
        ->searchTable('Bedrijf D')->assertSee('Bedrijf D')->assertDontSee('Bedrijf A')
        ->searchTable('')
        ->filterTable('status', 'Gereactiveerd')->assertSee('Bedrijf C')->assertDontSee('Bedrijf A')
        ->removeTableFilters()
        ->sortTable('total', 'desc')->assertSeeInOrder(['Bedrijf A', 'Bedrijf C'])
        ->assertSee(CompanyResource::getUrl('view', ['record' => $c['A']->id]));
});

test('chart data carries the retention and cohort series', function () {
    ['e' => $e] = $this->world;
    $scope = new Scope($e[5], $e[4]);
    $admin = analyticsAdmin();
    $charts = app(ChartCatalog::class);

    $growth = $charts->make('participation_growth', $scope, $admin);
    expect($growth['labels'])->toBe(['Editie 2023', 'Editie 2024', 'Editie 2025', 'Editie 2026', 'Editie 2027'])
        ->and($growth['datasets'][0]['data'])->toBe([3, 2, 2, 2, 5]);

    $rate = $charts->make('retention_rate', $scope, $admin);
    expect($rate['datasets'][0]['data'])->toBe([null, 33.3, 50.0, 50.0, null]); // the upcoming edition is provisional

    $second = $charts->make('cohort_second', $scope, $admin);
    expect($second['datasets'][0]['data'][0])->toBe(33.3)->and($second['datasets'][0]['data'][4])->toBeNull();

    $days = $charts->make('enrollments_days_before', $scope, $admin);
    expect($days['datasets'])->toHaveCount(2)->and($days['labels'][0])->toBe('120 dgn');

    $revenue = $charts->make('revenue_per_edition', $scope, $admin);
    expect($revenue['datasets'][0]['data'])->toBe([null, null, 500.0, 500.0, 1200.0]);
});

// Redesigned dashboard: overview, insights, participation matrix, filters

test('the overview collects the key figures, attention items and insights in one structure', function () {
    ['e' => $e] = $this->world;
    $admin = analyticsAdmin();
    $data = app(\App\Services\Analytics\OverviewData::class)->build(new Scope($e[5], $e[4]), $admin);

    expect(array_column($data['primary'], 'label'))->toContain('Goedgekeurde bedrijven', 'Nieuwe bedrijven', 'Terugkerende bedrijven', 'Standbezetting', 'Tweede formulier ingevuld')
        ->and(array_column($data['secondary'], 'label'))->toContain('In behandeling', 'Afgewezen', 'Goedgekeurd zonder stand', 'Lunchdeelnemers', 'Borrelgasten')
        ->and(array_column($data['attention'], 'key'))->toContain('pending')->not->toContain('failed_jobs')
        ->and(array_column($data['insights'], 'text'))->toContain(count(app(Participation::class)->classify($e[5]->id)['returning']).' bedrijven hebben eerder deelgenomen.');
});

test('the overview never carries financial or restricted figures for a limited user', function () {
    ['e' => $e] = $this->world;
    $data = app(\App\Services\Analytics\OverviewData::class)->build(new Scope($e[5], $e[4]), analyticsUser(['dashboard.view']));

    expect($data['primary'])->toBe([])->and($data['secondary'])->toBe([])->and($data['attention'])->toBe([])->and($data['insights'])->toBe([]);

    $withFinance = app(\App\Services\Analytics\OverviewData::class)->build(new Scope($e[5], $e[4]), analyticsUser(['dashboard.view', 'statistics.financial']));
    expect(array_column($withFinance['primary'], 'label'))->toBe(['Verwachte opbrengst']);

    $this->actingAs(analyticsUser(['dashboard.view', 'enrollments.view']));
    Livewire::test(\App\Filament\Widgets\Analytics\OverviewWidget::class, ['pageFilters' => ['edition' => (string) $e[5]->id]])->assertDontSee('Verwachte opbrengst');
});

test('the participation matrix has a column per edition and classifies the participation pattern', function () {
    ['e' => $e, 'c' => $c] = $this->world;
    $this->actingAs(analyticsAdmin());
    $rows = collect(app(ListCatalog::class)->rows('matrix', new Scope($e[5], $e[4]), auth()->user()))->keyBy('company_id');

    expect($rows[$c['A']->id]['pattern'])->toBe('Elke editie')
        ->and($rows[$c['C']->id]['pattern'])->toBe('Onderbroken')
        ->and($rows[$c['D']->id]['pattern'])->toBe('Eerste deelname')
        ->and($rows[$c['B']->id]['pattern'])->toBe('Gestopt')
        ->and($rows[$c['C']->id]['e'.$e[2]->id])->toBeFalse()
        ->and($rows[$c['C']->id]['e'.$e[1]->id])->toBeTrue();

    Livewire::test(ListWidget::class, ['list' => 'matrix', 'pageFilters' => ['edition' => (string) $e[5]->id]])
        ->assertSee('Bedrijf A')->assertSee('Editie 2023')
        ->filterTable('pattern', 'Onderbroken')->assertSee('Bedrijf C')->assertDontSee('Bedrijf A');
});

test('the matrix is empty for a user without company access', function () {
    ['e' => $e] = $this->world;

    expect(app(ListCatalog::class)->rows('matrix', new Scope($e[5], $e[4]), analyticsUser(['dashboard.view'])))->toBe([]);
});

test('resetting the dashboard filters restores the defaults and the applied filters are shown as chips', function () {
    ['e' => $e] = $this->world;
    $this->actingAs(analyticsAdmin());

    $page = \Livewire\Livewire::test(\App\Filament\Pages\Dashboard::class)
        ->set('filters.edition', 'all')->set('filters.status', 'approved')
        ->assertSee('Alle edities')->assertSee('Status: Goedgekeurd')
        ->call('resetFilters')->assertDontSee('Status: Goedgekeurd');

    expect($page->get('filters.status'))->toBeNull()->and($page->get('filters.edition'))->not->toBe('all');
});

test('each new dashboard sub-section mounts its own few widgets', function () {
    $this->actingAs(analyticsAdmin());
    $page = \Livewire\Livewire::test(\App\Filament\Pages\Dashboard::class)->set('activeTab', 'bedrijven')->set('activeSection', 'deelnamegeschiedenis');

    expect(substr_count($page->html(), 'wire:name="App\Filament\Widgets'))->toBe(2)->and(substr_count($page->html(), 'Widgets\Analytics\ListWidget'))->toBe(2);
    $page->set('activeSection', 'cohortanalyse');
    expect($page->html())->toContain('Analytics\CohortWidget');
    $page->set('activeTab', 'overzicht');
    expect($page->get('activeSection'))->toBeNull()->and($page->html())->toContain('Analytics\OverviewWidget');
});
