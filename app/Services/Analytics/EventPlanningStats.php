<?php

namespace App\Services\Analytics;

use App\Models\Enrollment;
use App\Models\Event;
use App\Models\EventStand;
use App\Services\StandEligibility;
use Illuminate\Support\Facades\DB;

/**
 * Stands (Event Map) and expected participation revenue.
 *
 * Revenue is the fee ACCEPTED at enrollment (`fee_cents_accepted`), not an invoice or a payment: the system records no
 * invoiced / paid state, so none is shown. Amounts are kept per currency and never added across currencies.
 */
class EventPlanningStats
{
    public function __construct(private Analytics $analytics, private Participation $participation) {}

    /**
     * @return array{company_total: int, company_occupied: int, company_free: int, occupancy: ?float, partner_total: int, partner_occupied: int, partner_free: int, allocated_companies: int, approved_without_stand: list<int>|null, multiple: array<int, int>, grandfathered: list<int>, warnings: int, free_partner_numbers: list<string>}
     */
    public function stands(int $eventId): array
    {
        return $this->analytics->remember("stands:{$eventId}", function () use ($eventId): array {
            $event = Event::query()->findOrFail($eventId);
            $rows = EventStand::query()->where('event_id', $eventId)->get(['type', 'company_id', 'partner_id']);
            $companyRows = $rows->where('type', 'company')->whereNotNull('company_id');
            $companyIds = $companyRows->pluck('company_id')->unique();
            $partnerOccupied = $rows->where('type', 'partner')->whereNotNull('partner_id')->count();
            $total = (int) ($event->max_stands ?? 0);
            $partnerTotal = (int) ($event->partner_stand_count ?? 0);
            $edition = collect($this->participation->editions())->firstWhere('id', $eventId);
            $approved = $this->participation->sets()[$eventId] ?? [];

            return [
                'company_total' => $total,
                'company_occupied' => $companyRows->count(),
                'company_free' => max(0, $total - $companyRows->count()),
                'occupancy' => $total > 0 ? round($companyRows->count() / $total * 100, 1) : null,
                'partner_total' => $partnerTotal,
                'partner_occupied' => $partnerOccupied,
                'partner_free' => max(0, $partnerTotal - $partnerOccupied),
                'allocated_companies' => $companyIds->count(),
                // Only meaningful where approvals exist; older editions have no enrollments to compare with.
                'approved_without_stand' => ($edition['enrollments'] ?? false) ? array_values(array_diff($approved, $companyIds->all())) : null,
                'multiple' => $companyRows->countBy('company_id')->filter(fn (int $n): bool => $n > 1)->all(),
                'grandfathered' => $this->participation->grandfathered()[$eventId] ?? [],
                'warnings' => app(StandEligibility::class)->flaggedStands($event)->count(),
                'free_partner_numbers' => $event->getAvailablePartnerStandNumbers(),
            ];
        });
    }

    /**
     * Accepted fees per edition and currency. Null for editions without enrollments (no fee was recorded).
     *
     * @return array<int, array{currencies: array<string, array{approved: int, count: int, new: int, returning: int, pending: int, rejected: int}>}|null>
     */
    public function revenue(): array
    {
        return $this->analytics->remember('revenue', function (): array {
            $rows = Enrollment::query()->get(['event_id', 'company_id', 'status', 'fee_cents_accepted', 'currency_accepted'])->groupBy('event_id');
            $out = [];

            foreach ($this->participation->editions() as $edition) {
                $id = $edition['id'];

                if (! isset($rows[$id])) {
                    $out[$id] = null;

                    continue;
                }

                $new = array_flip($this->participation->classify($id)['new']);
                $currencies = [];

                foreach ($rows[$id] as $row) {
                    $c = &$currencies[$row->currency_accepted];
                    $c ??= ['approved' => 0, 'count' => 0, 'new' => 0, 'returning' => 0, 'pending' => 0, 'rejected' => 0];
                    $fee = (int) $row->fee_cents_accepted;

                    match ($row->status) {
                        Enrollment::STATUS_APPROVED => [$c['approved'] += $fee, $c['count']++, isset($new[$row->company_id]) ? $c['new'] += $fee : $c['returning'] += $fee],
                        Enrollment::STATUS_PENDING => $c['pending'] += $fee,
                        default => $c['rejected'] += $fee,
                    };
                    unset($c);
                }

                $out[$id] = ['currencies' => $currencies];
            }

            return $out;
        });
    }

    /** The currency used for charts: the most frequent accepted currency (EUR when nothing is recorded). */
    public function chartCurrency(): string
    {
        return (string) (DB::table('enrollments')->select('currency_accepted')->groupBy('currency_accepted')->orderByRaw('count(*) desc')->value('currency_accepted') ?? 'EUR');
    }

    /**
     * One metric row per edition, for charts and comparisons. Metrics that were never collected for an edition are null.
     *
     * @return array<int, array<string, mixed>>
     */
    public function perEdition(): array
    {
        return $this->analytics->remember('per-edition', function (): array {
            $revenue = $this->revenue();
            $currency = $this->chartCurrency();
            $partners = EventStand::query()->where('type', 'partner')->whereNotNull('partner_id')->select('event_id', DB::raw('count(distinct partner_id) as n'))->groupBy('event_id')->pluck('n', 'event_id');
            $lunch = app(CateringStats::class)->perEdition();
            $out = [];

            foreach ($this->participation->editions() as $edition) {
                $id = $edition['id'];
                $class = $this->participation->classify($id);
                $stands = $this->stands($id);
                $money = $revenue[$id]['currencies'][$currency] ?? null;

                $out[$id] = [
                    'name' => $edition['name'],
                    'provisional' => $edition['provisional'],
                    'participants' => $class['participants'],
                    'new' => count($class['new']),
                    'returning' => count($class['returning']),
                    'retained' => count($class['retained']),
                    'reactivated' => count($class['reactivated']),
                    'lost' => count($class['lost']),
                    'retention' => $class['retention'],
                    'churn' => $class['churn'],
                    'stands_total' => $stands['company_total'],
                    'stands_occupied' => $stands['company_occupied'],
                    'occupancy' => $stands['occupancy'],
                    'partner_stands' => $stands['partner_occupied'],
                    'partners' => (int) ($partners[$id] ?? 0),
                    'revenue' => $money['approved'] ?? null,
                    'avg_fee' => ($money['count'] ?? 0) > 0 ? (int) round($money['approved'] / $money['count']) : null,
                    'revenue_new' => $money['new'] ?? null,
                    'revenue_returning' => $money['returning'] ?? null,
                    'lunch' => $lunch[$id]['lunch'] ?? null,
                    'drinks' => $lunch[$id]['drinks'] ?? null,
                ];
            }

            return $out;
        });
    }
}
