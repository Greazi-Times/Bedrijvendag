<?php

namespace App\Services\Analytics;

use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Every dashboard chart by key: heading, description, Chart.js type, labels, datasets and options.
 * A chart the user may not see returns null (and is not computed).
 */
class ChartCatalog
{
    /** Readable on light and dark backgrounds. */
    private const COLORS = ['#ff6600', '#3b82f6', '#10b981', '#f59e0b', '#8b5cf6', '#f43f5e', '#14b8a6', '#64748b', '#ec4899', '#84cc16'];

    /** key => [permission, heading, description] */
    public const CHARTS = [
        'enrollments_daily' => ['enrollments.view', 'Inschrijvingen per dag', 'Aantal ingediende inschrijvingen per dag (filters: status en periode).'],
        'enrollments_weekly' => ['enrollments.view', 'Inschrijvingen per week', 'Per ISO-week.'],
        'enrollments_cumulative' => ['enrollments.view', 'Cumulatieve inschrijvingen', 'Oplopend totaal sinds de eerste inschrijving.'],
        'enrollments_approved' => ['enrollments.view', 'Goedgekeurde inschrijvingen in de tijd', 'Cumulatief, op beoordelingsdatum.'],
        'enrollments_backlog' => ['enrollments.view', 'Openstaande inschrijvingen in de tijd', 'Ingediend min beoordeeld, per dag (gereconstrueerd uit indien- en beoordelingsdatum).'],
        'enrollments_days_before' => ['enrollments.view', 'Inschrijvingen: dagen vóór de editie', 'Cumulatieve inschrijvingen vergeleken op hetzelfde aantal dagen vóór de editie.'],
        'retention_new_returning' => ['companies.view', 'Nieuwe en terugkerende bedrijven per editie', 'Gestapeld; samen alle deelnemende bedrijven.'],
        'retention_rate' => ['companies.view', 'Retentie en verloop per editie', 'Percentage van de vorige editie. De lopende of aankomende editie is voorlopig en wordt niet getoond.'],
        'retention_lost' => ['companies.view', 'Verloren bedrijven per editie', 'In de vorige editie, niet in deze. Lopende of aankomende edities zijn niet definitief en blijven leeg.'],
        'retention_reactivated' => ['companies.view', 'Gereactiveerde bedrijven per editie', 'Eerder deelgenomen, maar niet in de direct voorgaande editie.'],
        'attended_distribution' => ['companies.view', 'Aantal bijgewoonde edities per bedrijf', 'Aantal bedrijven per aantal edities waaraan ze deelnamen.'],
        'streak_distribution' => ['companies.view', 'Verdeling langste aaneengesloten reeks', 'Aantal bedrijven per lengte van hun langste reeks van opeenvolgende edities.'],
        'participation_growth' => ['companies.view', 'Groei van het aantal deelnemers', 'Bedrijven per editie: goedgekeurde inschrijvingen, of standtoewijzingen voor edities vóór het inschrijfsysteem.'],
        'cohort_second' => ['companies.view', 'Nieuwe bedrijven die een tweede keer meedoen', 'Per cohort (eerste editie): percentage dat in de volgende editie terugkomt.'],
        'stands_occupancy' => ['event-stands.view', 'Standbezetting per editie', 'Bezette bedrijfsstands in % van het aantal beschikbare stands.'],
        'stands_occupied_free' => ['event-stands.view', 'Bezette en vrije bedrijfsstands', 'Per editie, gestapeld.'],
        'stands_trend' => ['event-stands.view', 'Bezette bedrijfsstands in de tijd', 'Aantal bezette bedrijfsstands per editie.'],
        'stands_vs_approved' => ['event-stands.view', 'Standtoewijzingen en goedgekeurde bedrijven', 'Bedrijven met een stand naast het aantal deelnemende bedrijven.'],
        'stands_partner' => ['event-stands.view', 'Partnerstands per editie', 'Bezette partnerstands, los van de bedrijfsstands.'],
        'revenue_per_edition' => [Kpis::FINANCIAL_PERMISSION, 'Verwachte deelnamekosten per editie', 'Geaccepteerde deelnamekosten van goedgekeurde bedrijven; geen ontvangen betalingen.'],
        'revenue_over_time' => [Kpis::FINANCIAL_PERMISSION, 'Verwachte deelnamekosten in de tijd', 'Cumulatief, op datum van indienen, voor de geselecteerde editie.'],
        'revenue_new_returning' => [Kpis::FINANCIAL_PERMISSION, 'Nieuwe en terugkerende bedrijven: verwachte kosten', 'Gestapeld per editie.'],
        'avg_fee' => [Kpis::FINANCIAL_PERMISSION, 'Gemiddeld geaccepteerd bedrag per editie', 'Verwachte deelnamekosten / goedgekeurde bedrijven.'],
        'diet_distribution' => ['enrollments.view', 'Dieetwensen (anoniem)', 'Selecties kunnen overlappen; de staven zijn geen losse maaltijdaantallen.'],
        'lunch_per_edition' => ['enrollments.view', 'Lunchdeelnemers per editie', 'Alleen edities waarvan de tweede formulieren nog zijn opgeslagen.'],
        'catering_compare' => ['enrollments.view', 'Dieetwensen: vergelijking met vorige editie', 'Alleen als beide edities nog dieetgegevens hebben.'],
        'lunch_over_time' => ['enrollments.view', 'Lunchdeelnemers naarmate bedrijven invullen', 'Cumulatief, op datum van de laatste versie van het tweede formulier.'],
        'drinks_compare' => ['enrollments.view', 'Verwachte borrelgasten per editie', 'Alleen edities waarvan de tweede formulieren nog zijn opgeslagen.'],
        'sectors_bar' => ['sectors.view', 'Bedrijven per sector', 'Een bedrijf kan meerdere sectoren hebben; de staven zijn niet onderling uitsluitend.'],
        'sectors_new_returning' => ['sectors.view', 'Sectoren: nieuwe en terugkerende bedrijven', 'Gestapeld.'],
        'sectors_stacked' => ['sectors.view', 'Sectorverdeling per editie', 'Gestapeld; op basis van het huidige bedrijfsprofiel.'],
        'sectors_growth' => ['sectors.view', 'Sectorgroei t.o.v. vergelijkingseditie', 'Verschil in aantal bedrijven.'],
        'educations_bar' => ['education.view', 'Bedrijven per opleiding', 'Een bedrijf kan meerdere opleidingen bedienen.'],
        'educations_trend' => ['education.view', 'Opleidingsdekking in de tijd', 'Bedrijven per opleiding, per editie.'],
        'educations_coverage' => ['education.view', 'Aantal opleidingen met minstens één bedrijf', 'Per editie.'],
    ];

    public function __construct(
        private Participation $participation,
        private EnrollmentStats $enrollments,
        private EventPlanningStats $planning,
        private CateringStats $catering,
        private TaxonomyStats $taxonomy,
    ) {}

    public static function heading(string $key): string
    {
        return self::CHARTS[$key][1] ?? '';
    }

    public static function permission(string $key): ?string
    {
        return self::CHARTS[$key][0] ?? null;
    }

    /** @return array{type: string, labels: list<string>, datasets: list<array<string, mixed>>, options: array<string, mixed>, empty: bool, description: string}|null */
    public function make(string $key, Scope $scope, User $user): ?array
    {
        [$permission, , $description] = self::CHARTS[$key] ?? [null, null, null];

        if ($permission === null || ! $user->can($permission)) {
            return null;
        }

        $chart = $this->build($key, $scope);
        $chart['description'] = $description;
        $chart['empty'] = collect($chart['datasets'])->every(fn (array $d): bool => collect($d['data'])->filter(fn ($v) => $v !== null && $v != 0)->isEmpty());

        return $chart;
    }

    /** @return array<string, mixed> */
    private function build(string $key, Scope $scope): array
    {
        $editions = $this->participation->editions();
        $names = array_column($editions, 'name');
        $per = $this->planning->perEdition();
        $col = fn (string $field, bool $skipProvisional = false): array => array_map(fn (array $e): mixed => $skipProvisional && $e['provisional'] ? null : ($per[$e['id']][$field] ?? null), $editions);
        $id = $scope->event?->id;

        return match ($key) {
            'enrollments_daily' => $this->series($this->enrollments->trends($scope)['days'], 'Inschrijvingen', 'bar'),
            'enrollments_weekly' => $this->series($this->enrollments->trends($scope)['weeks'], 'Inschrijvingen', 'bar'),
            'enrollments_cumulative' => $this->series($this->enrollments->trends($scope)['cumulative'], 'Totaal', 'line'),
            'enrollments_approved' => $this->series($this->enrollments->trends($scope)['approved'], 'Goedgekeurd', 'line'),
            'enrollments_backlog' => $this->series($this->enrollments->trends($scope)['backlog'], 'Openstaand', 'line'),
            'enrollments_days_before' => $this->daysBefore($scope),
            'retention_new_returning' => $this->chart('bar', $names, [$this->ds('Nieuw', $col('new'), 1), $this->ds('Terugkerend', $col('returning'), 2)], stacked: true),
            'retention_rate' => $this->chart('line', $names, [$this->ds('Retentie %', $col('retention', true), 2), $this->ds('Verloop %', $col('churn', true), 5)]),
            'retention_lost' => $this->chart('bar', $names, [$this->ds('Verloren', $col('lost', true), 5)]),
            'retention_reactivated' => $this->chart('bar', $names, [$this->ds('Gereactiveerd', $col('reactivated'), 3)]),
            'attended_distribution' => $this->chart('bar', array_keys($h = $this->participation->history()['attended']), [$this->ds('Bedrijven', array_values($h), 1)]),
            'streak_distribution' => $this->chart('bar', array_keys($h = $this->participation->history()['streaks']), [$this->ds('Bedrijven', array_values($h), 4)]),
            'participation_growth' => $this->chart('line', $names, [$this->ds('Deelnemende bedrijven', $col('participants'), 0)]),
            'cohort_second' => $this->chart('bar', array_column($c = $this->participation->cohorts(), 'name'), [$this->ds('Terug in volgende editie %', array_map(fn (array $row): ?float => ($row['cells'][1] ?? null) === null || $row['size'] === 0 ? null : round($row['cells'][1] / $row['size'] * 100, 1), $c), 2)]),
            'stands_occupancy' => $this->chart('bar', $names, [$this->ds('Bezetting %', $col('occupancy'), 0)]),
            'stands_occupied_free' => $this->chart('bar', $names, [$this->ds('Bezet', $col('stands_occupied'), 0), $this->ds('Vrij', array_map(fn (array $e): ?int => isset($per[$e['id']]) ? max(0, $per[$e['id']]['stands_total'] - $per[$e['id']]['stands_occupied']) : null, $editions), 7)], stacked: true),
            'stands_trend' => $this->chart('line', $names, [$this->ds('Bezette bedrijfsstands', $col('stands_occupied'), 0)]),
            'stands_vs_approved' => $this->chart('bar', $names, [$this->ds('Deelnemende bedrijven', $col('participants'), 1), $this->ds('Bedrijven met stand', $col('stands_occupied'), 0)]),
            'stands_partner' => $this->chart('bar', $names, [$this->ds('Bezette partnerstands', $col('partner_stands'), 4), $this->ds('Partners', $col('partners'), 6)]),
            'revenue_per_edition' => $this->chart('bar', $names, [$this->ds('Verwacht ('.$this->planning->chartCurrency().')', $this->euros($col('revenue')), 2)]),
            'revenue_over_time' => $this->revenueOverTime($id),
            'revenue_new_returning' => $this->chart('bar', $names, [$this->ds('Nieuw', $this->euros($col('revenue_new')), 1), $this->ds('Terugkerend', $this->euros($col('revenue_returning')), 2)], stacked: true),
            'avg_fee' => $this->chart('line', $names, [$this->ds('Gemiddeld ('.$this->planning->chartCurrency().')', $this->euros($col('avg_fee')), 3)]),
            'diet_distribution' => $this->diet($id),
            'lunch_per_edition' => $this->chart('bar', $names, [$this->ds('Lunchdeelnemers', $col('lunch'), 3)]),
            'catering_compare' => $this->dietCompare($scope),
            'lunch_over_time' => $this->lunchOverTime($id),
            'drinks_compare' => $this->chart('bar', $names, [$this->ds('Borrelgasten', array_map(fn (array $e): mixed => $per[$e['id']]['drinks'] ?? null, $editions), 4)]),
            'sectors_bar' => $this->taxonomyBar('sector', $id),
            'sectors_new_returning' => $this->taxonomyBar('sector', $id, split: true),
            'sectors_stacked' => $this->taxonomyStacked('sector', $names),
            'sectors_growth' => $this->taxonomyGrowth($scope),
            'educations_bar' => $this->taxonomyBar('education', $id),
            'educations_trend' => $this->taxonomyStacked('education', $names, line: true),
            'educations_coverage' => $this->chart('line', $names, [$this->ds('Opleidingen met bedrijven', array_map(fn (array $row): int => count(array_filter($row)), array_values($this->taxonomy->perEdition('education'))), 1)]),
            default => $this->chart('bar', [], []),
        };
    }

    /** @param array<string, int> $series @return array<string, mixed> */
    private function series(array $series, string $label, string $type): array
    {
        return $this->chart($type, array_map(fn (string $d): string => str_contains($d, 'W') ? $d : \Illuminate\Support\Carbon::parse($d)->format('d-m'), array_keys($series)), [$this->ds($label, array_values($series), 0)]);
    }

    /** @return array<string, mixed> */
    private function daysBefore(Scope $scope): array
    {
        if (! $scope->event) {
            return $this->chart('line', [], []);
        }

        $max = 120;
        $selected = $this->enrollments->daysBefore($scope->event->id, $max);
        $datasets = [$this->ds($scope->event->name, array_values($selected), 0)];

        if ($scope->compare) {
            $datasets[] = $this->ds($scope->compare->name, array_values($this->enrollments->daysBefore($scope->compare->id, $max)), 1);
        }

        return $this->chart('line', array_map(fn (int $d): string => "{$d} dgn", array_keys($selected)), $datasets);
    }

    /** @return array<string, mixed> */
    private function revenueOverTime(?int $eventId): array
    {
        if (! $eventId) {
            return $this->chart('line', [], []);
        }

        $currency = $this->planning->chartCurrency();
        $perDay = DB::table('enrollments')->where('event_id', $eventId)->where('status', Enrollment::STATUS_APPROVED)->where('currency_accepted', $currency)
            ->get(['submitted_at', 'fee_cents_accepted'])->groupBy(fn ($r): string => substr((string) $r->submitted_at, 0, 10))->map(fn ($rows) => $rows->sum('fee_cents_accepted'))->sortKeys();
        $running = 0;
        $cumulative = $perDay->map(function ($cents) use (&$running): float {
            $running += $cents;

            return $running / 100;
        });

        return $this->chart('line', $cumulative->keys()->map(fn (string $d): string => \Illuminate\Support\Carbon::parse($d)->format('d-m'))->all(), [$this->ds("Verwacht ({$currency})", $cumulative->values()->all(), 2)]);
    }

    /** @return array<string, mixed> */
    private function diet(?int $eventId): array
    {
        $stats = $eventId ? $this->catering->forEvent($eventId) : null;

        if (! $stats || ! $stats['available'] || $stats['lunch']['diet_known'] === 0) {
            return $this->chart('bar', [], []);
        }

        $diet = $stats['lunch']['diet'];

        return $this->chart('bar', [...array_values(CateringStats::DIETS), 'Geen wensen'], [$this->ds('Deelnemers', [...array_values($diet), $stats['lunch']['none']], 3)], horizontal: true);
    }

    /** @return array<string, mixed> */
    private function dietCompare(Scope $scope): array
    {
        if (! $scope->event || ! $scope->compare) {
            return $this->chart('bar', [], []);
        }

        $datasets = [];

        foreach ([$scope->event, $scope->compare] as $i => $event) {
            $stats = $this->catering->forEvent($event->id);

            if ($stats['available'] && $stats['lunch']['diet_known'] > 0) {
                $datasets[] = $this->ds($event->name, array_values($stats['lunch']['diet']), $i);
            }
        }

        return count($datasets) === 2 ? $this->chart('bar', array_values(CateringStats::DIETS), $datasets) : $this->chart('bar', [], []);
    }

    /** @return array<string, mixed> */
    private function lunchOverTime(?int $eventId): array
    {
        $over = $eventId ? ($this->catering->forEvent($eventId)['lunch']['over_time'] ?? []) : [];
        ksort($over);
        $running = 0;
        $cumulative = array_map(function (int $n) use (&$running): int {
            return $running += $n;
        }, $over);

        return $this->chart('line', array_map(fn (string $d): string => \Illuminate\Support\Carbon::parse($d)->format('d-m'), array_keys($cumulative)), [$this->ds('Lunchdeelnemers', array_values($cumulative), 3)]);
    }

    /** @param 'sector'|'education' $kind @return array<string, mixed> */
    private function taxonomyBar(string $kind, ?int $eventId, bool $split = false): array
    {
        if (! $eventId) {
            return $this->chart('bar', [], []);
        }

        $rows = collect($this->taxonomy->distribution($kind, $eventId))->sortByDesc('total')->values();
        $labels = $rows->pluck('name')->all();

        return $split
            ? $this->chart('bar', $labels, [$this->ds('Nieuw', $rows->pluck('new')->all(), 1), $this->ds('Terugkerend', $rows->pluck('returning')->all(), 2)], stacked: true, horizontal: true)
            : $this->chart('bar', $labels, [$this->ds('Bedrijven', $rows->pluck('total')->all(), 0)], horizontal: true);
    }

    /** @param 'sector'|'education' $kind @param list<string> $names @return array<string, mixed> */
    private function taxonomyStacked(string $kind, array $names, bool $line = false): array
    {
        $all = $this->taxonomy->perEdition($kind);
        $categories = $this->taxonomy->categories($kind);
        $datasets = [];
        $i = 0;

        foreach ($categories as $categoryId => $name) {
            $datasets[] = $this->ds($name, array_map(fn (array $e): int => $all[$e['id']][$categoryId] ?? 0, $this->participation->editions()), $i++);
        }

        return $this->chart($line ? 'line' : 'bar', $names, $datasets, stacked: ! $line);
    }

    /** @return array<string, mixed> */
    private function taxonomyGrowth(Scope $scope): array
    {
        if (! $scope->event || ! $scope->compare) {
            return $this->chart('bar', [], []);
        }

        $now = collect($this->taxonomy->distribution('sector', $scope->event->id))->keyBy('id');
        $then = collect($this->taxonomy->distribution('sector', $scope->compare->id))->keyBy('id');
        $rows = $now->map(fn (array $row, int $id): array => ['name' => $row['name'], 'diff' => $row['total'] - ($then[$id]['total'] ?? 0)])->sortByDesc('diff')->values();

        return $this->chart('bar', $rows->pluck('name')->all(), [$this->ds('Verschil', $rows->pluck('diff')->all(), 2)], horizontal: true);
    }

    /** @param list<mixed> $euros cents => euros @return list<mixed> */
    private function euros(array $cents): array
    {
        return array_map(fn ($c) => $c === null ? null : round($c / 100, 2), $cents);
    }

    /** @param list<mixed> $data @return array<string, mixed> */
    private function ds(string $label, array $data, int $color, array $extra = []): array
    {
        $c = self::COLORS[$color % count(self::COLORS)];

        return ['label' => $label, 'data' => $data, 'borderColor' => $c, 'backgroundColor' => $c.'cc', 'tension' => 0.25, 'spanGaps' => false, ...$extra];
    }

    /** @param list<string> $labels @param list<array<string, mixed>> $datasets @return array<string, mixed> */
    private function chart(string $type, array $labels, array $datasets, bool $stacked = false, bool $horizontal = false): array
    {
        $options = ['plugins' => ['legend' => ['display' => count($datasets) > 1]], 'maintainAspectRatio' => true];

        if ($stacked) {
            $options['scales'] = ['x' => ['stacked' => true], 'y' => ['stacked' => true]];
        }

        if ($horizontal) {
            $options['indexAxis'] = 'y';
        }

        if ($type === 'line') {
            $datasets = array_map(fn (array $d): array => [...$d, 'backgroundColor' => $d['borderColor'].'33', 'pointRadius' => 2], $datasets);
        }

        return compact('type', 'labels', 'datasets', 'options');
    }
}
