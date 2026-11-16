<?php

use App\Filament\Pages\Dashboard;
use App\Models\Event;
use App\Services\Analytics\Analytics;
use App\Services\Analytics\Scope;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

/** Editions are created out of date order on purpose: only the event date may decide, never the id. */
function datedEditions(array $dates = ['2026-11-20', '2025-03-01', '2027-03-01', '2026-03-01', '2025-11-01']): array
{
    return collect($dates)->mapWithKeys(fn (string $date): array => [$date => openEvent(['name' => "Edition {$date}", 'date' => $date])])->all();
}

function onDate(string $moment): void
{
    Carbon::setTestNow(Carbon::parse($moment, 'Europe/Amsterdam'));
    app(Analytics::class)->flush();
}

afterEach(fn () => Carbon::setTestNow());

test('before the next edition the upcoming one is selected and compared with the one immediately before it', function () {
    $e = datedEditions();
    onDate('2026-11-10 12:00');

    $scope = Scope::from(null);

    expect($scope->event->id)->toBe($e['2026-11-20']->id)
        ->and($scope->compare->id)->toBe($e['2026-03-01']->id) // chronological, not "same time last year", and never the later 2027 edition
        ->and($scope->notice())->toBeNull();
});

test('an edition taking place today wins over a later one, also late in the evening Amsterdam time', function () {
    $e = datedEditions();

    foreach (['2026-11-20 00:30', '2026-11-20 23:50'] as $moment) {
        onDate($moment);
        expect(Scope::from(null)->event->id)->toBe($e['2026-11-20']->id);
    }

    onDate('2026-11-21 00:10');
    expect(Scope::from(null)->event->id)->toBe($e['2027-03-01']->id)->and(Scope::from(null)->compare->id)->toBe($e['2026-11-20']->id);
});

test('without an upcoming edition the most recently held one is selected', function () {
    $e = datedEditions();
    onDate('2028-01-01 09:00');

    $scope = Scope::from(null);

    expect($scope->event->id)->toBe($e['2027-03-01']->id)->and($scope->compare->id)->toBe($e['2026-11-20']->id);
});

test('each edition defaults to the one before it and never to itself or a later one', function () {
    $e = datedEditions();
    $dates = ['2025-03-01', '2025-11-01', '2026-03-01', '2026-11-20', '2027-03-01'];

    foreach ($dates as $i => $date) {
        $scope = Scope::from(['edition' => (string) $e[$date]->id]);
        expect($scope->event->id)->toBe($e[$date]->id)
            ->and($scope->compare?->id)->toBe($i === 0 ? null : $e[$dates[$i - 1]]->id);
    }
});

test('a missing previous edition is explained, and an empty database has an empty state', function () {
    $e = datedEditions(['2025-03-01']);
    $scope = Scope::from(['edition' => (string) $e['2025-03-01']->id]);

    expect($scope->compare)->toBeNull()->and($scope->notice())->toBe('Geen vorige editie beschikbaar');

    Event::query()->delete();
    $empty = Scope::from(null);
    expect($empty->event)->toBeNull()->and($empty->notice())->toBe('Er zijn nog geen edities.');
});

test('all editions is a historical overview without a comparison and without a notice', function () {
    datedEditions();
    $all = Scope::from(['edition' => 'all']);

    expect($all->event)->toBeNull()->and($all->compare)->toBeNull()->and($all->notice())->toBeNull();
});

test('a manually chosen comparison is used, but never the selected edition itself', function () {
    $e = datedEditions();
    $manual = Scope::from(['edition' => (string) $e['2026-11-20']->id, 'compare' => (string) $e['2025-03-01']->id]);

    expect($manual->compare->id)->toBe($e['2025-03-01']->id);
    expect(Scope::from(['edition' => (string) $e['2026-11-20']->id, 'compare' => (string) $e['2026-11-20']->id])->compare)->toBeNull();
});

test('the dashboard follows the selected edition, keeps a manual comparison across tabs and restores the defaults on reset', function () {
    $e = datedEditions();
    onDate('2026-11-10 12:00');
    $this->actingAs(reviewerWithout(['dashboard.view', 'enrollments.view', 'companies.view']));
    $resolve = fn ($page) => Scope::from($page->get('filters'));

    $page = Livewire::test(Dashboard::class)->assertOk();
    expect($resolve($page)->event->id)->toBe($e['2026-11-20']->id)->and($resolve($page)->compare->id)->toBe($e['2026-03-01']->id);

    // Changing the edition moves the automatic comparison along with it.
    $page->set('filters.edition', (string) $e['2026-03-01']->id);
    expect($resolve($page)->compare->id)->toBe($e['2025-11-01']->id);

    // A manual choice survives switching tabs and sub-sections.
    $page->set('filters.compare', (string) $e['2025-03-01']->id)->set('activeTab', 'bedrijven')->set('activeSection', 'cohortanalyse')->set('activeTab', 'evenement');
    expect($resolve($page)->compare->id)->toBe($e['2025-03-01']->id);

    // Choosing the manual comparison as the primary edition drops it, so the automatic one applies again.
    $page->set('filters.edition', (string) $e['2025-03-01']->id);
    expect($page->get('filters.compare'))->toBeNull()->and($resolve($page)->compare)->toBeNull();
    $page->assertSee('Geen vorige editie beschikbaar');

    $page->set('filters.edition', (string) $e['2027-03-01']->id)->set('filters.compare', (string) $e['2025-03-01']->id);
    $page->call('resetFilters');
    expect($resolve($page)->event->id)->toBe($e['2026-11-20']->id)->and($resolve($page)->compare->id)->toBe($e['2026-03-01']->id);
});
