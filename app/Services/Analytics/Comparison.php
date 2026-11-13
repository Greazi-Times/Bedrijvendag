<?php

namespace App\Services\Analytics;

use App\Models\User;

/** Side-by-side figures of editions, with absolute and relative change. Unrecorded metrics are marked, never zero. */
class Comparison
{
    public function __construct(private EventPlanningStats $planning, private TaxonomyStats $taxonomy) {}

    /**
     * Metric definitions: key in perEdition(), label, kind (int|pct|money|dec), permission needed, whether a rise is good.
     *
     * @var list<array{0: string, 1: string, 2: string, 3: ?string, 4?: bool}>
     */
    private const METRICS = [
        ['participants', 'Goedgekeurde / geregistreerde bedrijven', 'int', null],
        ['new', 'Nieuwe bedrijven', 'int', null],
        ['returning', 'Terugkerende bedrijven', 'int', null],
        ['retained', 'Behouden bedrijven', 'int', null],
        ['retention', 'Retentiepercentage', 'pct', null],
        ['stands_total', 'Bedrijfsstands beschikbaar', 'int', 'event-stands.view'],
        ['stands_occupied', 'Bezette bedrijfsstands', 'int', 'event-stands.view'],
        ['occupancy', 'Standbezetting', 'pct', 'event-stands.view'],
        ['revenue', 'Verwachte deelnamekosten', 'money', Kpis::FINANCIAL_PERMISSION],
        ['lunch', 'Lunchinschrijvingen', 'int', 'enrollments.view'],
        ['drinks', 'Borrelgasten', 'int', 'enrollments.view'],
        ['partners', 'Partners', 'int', null],
    ];

    /**
     * @param  list<int>  $eventIds  the editions to compare, first one is the baseline-facing "current"
     * @return array{editions: list<array{id: int, name: string, provisional: bool}>, rows: list<array<string, mixed>>}
     */
    public function table(array $eventIds, User $user): array
    {
        $per = $this->planning->perEdition();
        $chosen = array_values(array_filter($eventIds, fn (int $id): bool => isset($per[$id])));
        $editions = array_map(fn (int $id): array => ['id' => $id, 'name' => $per[$id]['name'], 'provisional' => $per[$id]['provisional']], $chosen);
        $currency = $this->planning->chartCurrency();
        $rows = [];

        foreach (self::METRICS as [$key, $label, $kind, $permission]) {
            if ($permission && ! $user->can($permission)) {
                continue;
            }

            $values = array_map(fn (int $id): mixed => $per[$id][$key] ?? null, $chosen);
            $format = fn ($v): string => match (true) {
                $v === null => 'Niet vastgelegd',
                $kind === 'pct' => Fmt::pct($v),
                $kind === 'money' => Fmt::money((int) $v, $currency),
                default => Fmt::int($v),
            };
            $change = count($values) >= 2 ? Fmt::change($values[0], $values[1]) : ['absolute' => null, 'percent' => null];
            $notes = [];

            foreach ($chosen as $i => $id) {
                if ($values[$i] === null) {
                    $notes[] = "{$per[$id]['name']}: niet vastgelegd of niet meer beschikbaar";
                } elseif (in_array($key, ['retention'], true) && $per[$id]['provisional']) {
                    $notes[] = "{$per[$id]['name']}: voorlopig";
                }
            }

            $rows[] = [
                'label' => $label,
                'values' => array_map($format, $values),
                'missing' => array_map(fn ($v): bool => $v === null, $values),
                'absolute' => $change['absolute'] === null ? Fmt::DASH : ($change['absolute'] > 0 ? '+' : '').($kind === 'money' ? Fmt::money((int) $change['absolute'], $currency) : Fmt::dec($change['absolute'], $kind === 'pct' ? 1 : 0)).($kind === 'pct' ? ' pp' : ''),
                'percent' => $change['percent'] === null ? ($change['absolute'] === null ? Fmt::DASH : 'n.v.t.') : ($change['percent'] > 0 ? '+' : '').Fmt::dec($change['percent']).'%',
                'note' => implode('; ', $notes),
            ];
        }

        if ($user->can('sectors.view') && count($chosen) >= 1) {
            $rows[] = $this->coverage('Vertegenwoordigde sectoren', 'sector', $chosen, $per);
        }

        if ($user->can('education.view') && count($chosen) >= 1) {
            $rows[] = $this->coverage('Gedekte opleidingen', 'education', $chosen, $per);
        }

        return ['editions' => $editions, 'rows' => $rows];
    }

    /** @param list<int> $chosen @param array<int, array<string, mixed>> $per @return array<string, mixed> */
    private function coverage(string $label, string $kind, array $chosen, array $per): array
    {
        $all = $this->taxonomy->perEdition($kind);
        $values = array_map(fn (int $id): int => count(array_filter($all[$id] ?? [])), $chosen);
        $change = count($values) >= 2 ? Fmt::change($values[0], $values[1]) : ['absolute' => null, 'percent' => null];

        return [
            'label' => $label,
            'values' => array_map(fn (int $v): string => Fmt::int($v), $values),
            'missing' => array_map(fn (): bool => false, $values),
            'absolute' => $change['absolute'] === null ? Fmt::DASH : ($change['absolute'] > 0 ? '+' : '').Fmt::dec($change['absolute'], 0),
            'percent' => $change['percent'] === null ? ($change['absolute'] === null ? Fmt::DASH : 'n.v.t.') : ($change['percent'] > 0 ? '+' : '').Fmt::dec($change['percent']).'%',
            'note' => 'Op basis van het huidige bedrijfsprofiel',
        ];
    }
}
