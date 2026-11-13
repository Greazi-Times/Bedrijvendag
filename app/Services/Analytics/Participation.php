<?php

namespace App\Services\Analytics;

use App\Models\Company;
use App\Models\Enrollment;
use App\Models\Event;
use App\Models\EventStand;

/**
 * Who took part in which edition, and everything derived from that (new / returning / retained / reactivated / lost,
 * streaks, cohorts). Companies are identified by company id only, never by name.
 *
 * Source of participation per edition (one rule, in `data()`):
 *  - from the first edition that has any enrollment record onwards: approved enrollments (the stand is irrelevant;
 *    a stand without an approved enrollment is a grandfathered assignment and does NOT count as participation);
 *  - before that: company stands (the only evidence the old site recorded).
 * Both sources are reduced to distinct company ids per edition, so nobody is counted twice. Approval confirms
 * registration, not physical attendance; attendance is not recorded.
 */
class Participation
{
    public function __construct(private Analytics $analytics) {}

    /**
     * Chronological order: date, then id.
     *
     * @return list<array{id: int, name: string, date: string, provisional: bool, enrollments: bool}>
     */
    public function editions(): array
    {
        return $this->data()['editions'];
    }

    /** @return array<int, list<int>> event id => distinct participating company ids */
    public function sets(): array
    {
        return $this->data()['sets'];
    }

    /** @return array<int, list<int>> event id => company ids on a stand without an approved enrollment (enrollment-era editions only) */
    public function grandfathered(): array
    {
        return $this->data()['grandfathered'];
    }

    public function index(int $eventId): ?int
    {
        foreach ($this->editions() as $i => $edition) {
            if ($edition['id'] === $eventId) {
                return $i;
            }
        }

        return null;
    }

    /**
     * Classification of one edition against its predecessors. An upcoming or running edition is `provisional`: its
     * participant list (and so retention and churn) is not final.
     *
     * @return array{participants: int, new: list<int>, returning: list<int>, retained: list<int>, reactivated: list<int>, lost: list<int>, has_previous: bool, provisional: bool, retention: ?float, churn: ?float, return_rate: ?float, new_rate: ?float}
     */
    public function classify(int $eventId): array
    {
        return $this->analytics->remember('classes', fn (): array => $this->computeClasses())[$eventId]
            ?? ['participants' => 0, 'new' => [], 'returning' => [], 'retained' => [], 'reactivated' => [], 'lost' => [], 'has_previous' => false, 'provisional' => false, 'retention' => null, 'churn' => null, 'return_rate' => null, 'new_rate' => null];
    }

    /**
     * One row per company that took part up to and including the selected edition.
     *
     * @return list<array{id: int, name: string, first: string, last: string, total: int, streak: int, longest: int, previous: bool, selected: bool, status: string, timeline: list<bool>}>
     */
    public function companyRows(int $eventId): array
    {
        return $this->analytics->remember("company-rows:{$eventId}", function () use ($eventId): array {
            $upTo = $this->index($eventId);

            if ($upTo === null) {
                return [];
            }

            $editions = array_slice($this->editions(), 0, $upTo + 1);
            $sets = array_map(fn (array $e): array => array_flip($this->sets()[$e['id']] ?? []), $editions);
            $ids = array_keys(array_replace(...$sets));
            $names = Company::query()->whereIn('id', $ids)->pluck('name', 'id');
            $rows = [];

            foreach ($ids as $id) {
                $timeline = array_map(fn (array $set): bool => isset($set[$id]), $sets);
                $positions = array_keys(array_filter($timeline));
                $selected = $timeline[$upTo];
                $previous = $upTo > 0 && $timeline[$upTo - 1];

                $rows[] = [
                    'id' => $id,
                    'name' => (string) ($names[$id] ?? "#{$id}"),
                    'first' => $editions[$positions[0]]['name'],
                    'last' => $editions[end($positions)]['name'],
                    'total' => count($positions),
                    'streak' => $selected ? $this->runEndingAt($timeline, $upTo) : 0,
                    'longest' => $this->longestRun($timeline),
                    'previous' => $previous,
                    'selected' => $selected,
                    'status' => match (true) {
                        $selected && $positions[0] === $upTo => 'Nieuw',
                        $selected && $previous => 'Behouden',
                        $selected => 'Gereactiveerd',
                        default => 'Niet teruggekeerd',
                    },
                    'timeline' => $timeline,
                ];
            }

            return $rows;
        });
    }

    /**
     * Cohorts by first participating edition. `cells[d]` is the number of the cohort that took part d editions
     * later, or null when that edition does not exist yet or is still provisional (never shown as zero).
     *
     * @return list<array{id: int, name: string, size: int, cells: list<?int>}>
     */
    public function cohorts(): array
    {
        $editions = $this->editions();
        $sets = $this->sets();
        $cohorts = [];

        foreach ($editions as $k => $edition) {
            $members = array_flip($this->classify($edition['id'])['new']);
            $cells = [];

            for ($d = 0; $k + $d < count($editions); $d++) {
                $later = $editions[$k + $d];
                $cells[] = $d === 0 ? count($members) : ($later['provisional'] ? null : count(array_intersect_key($members, array_flip($sets[$later['id']] ?? []))));
            }

            $cohorts[] = ['id' => $edition['id'], 'name' => $edition['name'], 'size' => count($members), 'cells' => $cells];
        }

        return $cohorts;
    }

    /**
     * Whole-history figures: attendance and streak distributions and the headline KPIs.
     *
     * @return array{ever: int, at_least_2: int, at_least_3: int, at_least_5: int, average: ?float, longest_streak: int, attended: array<string, int>, streaks: array<string, int>}
     */
    public function history(): array
    {
        return $this->analytics->remember('history', function (): array {
            $sets = array_map(fn (array $e): array => array_flip($this->sets()[$e['id']] ?? []), $this->editions());
            $ids = $sets === [] ? [] : array_keys(array_replace(...$sets));
            $totals = [];
            $longest = [];

            foreach ($ids as $id) {
                $timeline = array_map(fn (array $set): bool => isset($set[$id]), $sets);
                $totals[] = count(array_filter($timeline));
                $longest[] = $this->longestRun($timeline);
            }

            $bucket = function (array $values): array {
                $out = ['1' => 0, '2' => 0, '3' => 0, '4' => 0, '5+' => 0];

                foreach ($values as $v) {
                    $out[$v >= 5 ? '5+' : (string) $v]++;
                }

                return $out;
            };

            return [
                'ever' => count($ids),
                'at_least_2' => count(array_filter($totals, fn (int $t): bool => $t >= 2)),
                'at_least_3' => count(array_filter($totals, fn (int $t): bool => $t >= 3)),
                'at_least_5' => count(array_filter($totals, fn (int $t): bool => $t >= 5)),
                'average' => $ids === [] ? null : round(array_sum($totals) / count($ids), 2),
                'longest_streak' => $longest === [] ? 0 : max($longest),
                'attended' => $bucket($totals),
                'streaks' => $bucket($longest),
            ];
        });
    }

    /** @return array{editions: list<array<string, mixed>>, sets: array<int, list<int>>, grandfathered: array<int, list<int>>} */
    private function data(): array
    {
        return $this->analytics->remember('participation', function (): array {
            $events = Event::query()->orderBy('date')->orderBy('id')->get(['id', 'name', 'date']);
            $withEnrollments = Enrollment::query()->distinct()->pluck('event_id');
            $eraStart = $events->first(fn (Event $e): bool => $withEnrollments->contains($e->id))?->date;

            $companiesPerEvent = fn ($query) => $query->distinct()->get(['event_id', 'company_id'])
                ->groupBy('event_id')->map(fn ($rows): array => $rows->pluck('company_id')->map(fn ($id): int => (int) $id)->unique()->values()->all());

            $approved = $companiesPerEvent(Enrollment::query()->where('status', Enrollment::STATUS_APPROVED)->whereNotNull('company_id'));
            $stands = $companiesPerEvent(EventStand::query()->where('type', 'company')->whereNotNull('company_id'));

            $editions = [];
            $sets = [];
            $grandfathered = [];

            foreach ($events as $event) {
                $enrollmentEra = $eraStart !== null && $event->date->gte($eraStart);
                $fromApproved = $approved[$event->id] ?? [];
                $fromStands = $stands[$event->id] ?? [];

                $sets[$event->id] = $enrollmentEra ? $fromApproved : $fromStands;
                $grandfathered[$event->id] = $enrollmentEra ? array_values(array_diff($fromStands, $fromApproved)) : [];
                $editions[] = ['id' => $event->id, 'name' => $event->name, 'date' => $event->date->toDateString(), 'provisional' => $event->date->gte(today()), 'enrollments' => $enrollmentEra];
            }

            return compact('editions', 'sets', 'grandfathered');
        });
    }

    /** @return array<int, array<string, mixed>> */
    private function computeClasses(): array
    {
        $out = [];
        $seen = []; // company id => true, every company of an earlier edition
        $previous = null;

        foreach ($this->editions() as $edition) {
            $current = array_flip($this->sets()[$edition['id']] ?? []);
            $count = count($current);
            $returning = array_keys(array_intersect_key($current, $seen));
            $retained = $previous === null ? [] : array_keys(array_intersect_key($current, $previous));
            $lost = $previous === null ? [] : array_keys(array_diff_key($previous, $current));
            $previousCount = $previous === null ? 0 : count($previous);

            $out[$edition['id']] = [
                'participants' => $count,
                'new' => array_keys(array_diff_key($current, $seen)),
                'returning' => $returning,
                'retained' => $retained,
                'reactivated' => array_values(array_diff($returning, $retained)),
                'lost' => $lost,
                'has_previous' => $previous !== null,
                'provisional' => $edition['provisional'],
                'retention' => $previousCount > 0 ? round(count($retained) / $previousCount * 100, 1) : null,
                'churn' => $previousCount > 0 ? round(count($lost) / $previousCount * 100, 1) : null,
                'return_rate' => $count > 0 ? round(count($returning) / $count * 100, 1) : null,
                'new_rate' => $count > 0 ? round(($count - count($returning)) / $count * 100, 1) : null,
            ];

            $seen += $current;
            $previous = $current;
        }

        return $out;
    }

    /** @param list<bool> $timeline */
    private function longestRun(array $timeline): int
    {
        $best = $run = 0;

        foreach ($timeline as $present) {
            $run = $present ? $run + 1 : 0;
            $best = max($best, $run);
        }

        return $best;
    }

    /** @param list<bool> $timeline */
    private function runEndingAt(array $timeline, int $index): int
    {
        $run = 0;

        for ($i = $index; $i >= 0 && $timeline[$i]; $i--) {
            $run++;
        }

        return $run;
    }
}
