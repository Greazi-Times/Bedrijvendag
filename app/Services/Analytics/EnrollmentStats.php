<?php

namespace App\Services\Analytics;

use App\Models\Enrollment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/** Enrollment figures for one edition (or all editions when the scope has no event). Plain aggregates only. */
class EnrollmentStats
{
    public function __construct(private Analytics $analytics, private Participation $participation) {}

    /**
     * Status counts and review figures. Percentages use REVIEWED enrollments (approved + rejected) as denominator.
     *
     * @return array<string, mixed>
     */
    public function summary(?int $eventId): array
    {
        return $this->analytics->remember("enrollment-summary:{$eventId}", function () use ($eventId): array {
            $rows = $this->rows($eventId);
            $count = fn (string $status): int => $rows->where('status', $status)->count();
            $approved = $count(Enrollment::STATUS_APPROVED);
            $rejected = $count(Enrollment::STATUS_REJECTED);
            $pending = $count(Enrollment::STATUS_PENDING);
            $reviewed = $approved + $rejected;
            $durations = $rows->filter(fn ($r): bool => $r->reviewed_at !== null && $r->status !== Enrollment::STATUS_PENDING)
                ->map(fn ($r): int => max(0, Carbon::parse($r->reviewed_at)->diffInSeconds(Carbon::parse($r->submitted_at), true)));
            $submitted = $rows->where('status', Enrollment::STATUS_APPROVED);
            $infoDone = $submitted->where('info_status', Enrollment::INFO_SUBMITTED)->count();
            $oldest = $rows->where('status', Enrollment::STATUS_PENDING)->min('submitted_at');

            return [
                'total' => $rows->count(),
                'approved' => $approved,
                'pending' => $pending,
                'rejected' => $rejected,
                'approval_rate' => $reviewed > 0 ? round($approved / $reviewed * 100, 1) : null,
                'rejection_rate' => $reviewed > 0 ? round($rejected / $reviewed * 100, 1) : null,
                'avg_review_seconds' => $durations->isEmpty() ? null : (int) round($durations->avg()),
                'oldest_pending' => $oldest,
                'this_week' => $rows->filter(fn ($r): bool => Carbon::parse($r->submitted_at)->gte(now()->startOfWeek()))->count(),
                'this_month' => $rows->filter(fn ($r): bool => Carbon::parse($r->submitted_at)->gte(now()->startOfMonth()))->count(),
                'info_done' => $infoDone,
                'info_missing' => $approved - $infoDone,
                'info_rate' => $approved > 0 ? round($infoDone / $approved * 100, 1) : null,
                // A company record created after the enrollment came in was created by the approval; one that already existed was reused.
                'new_records' => $eventId === null ? null : DB::table('enrollments')->join('companies', 'companies.id', '=', 'enrollments.company_id')
                    ->where('enrollments.event_id', $eventId)->where('enrollments.status', Enrollment::STATUS_APPROVED)
                    ->whereColumn('companies.created_at', '>=', 'enrollments.submitted_at')->count(),
            ];
        });
    }

    /**
     * Time series for the trend charts. Date range and status filters apply here.
     *
     * @return array{days: array<string, int>, weeks: array<string, int>, cumulative: array<string, int>, approved: array<string, int>, backlog: array<string, int>}
     */
    public function trends(Scope $scope): array
    {
        $key = 'enrollment-trends:'.md5(json_encode([$scope->event?->id, $scope->status, $scope->from?->toDateString(), $scope->until?->toDateString()]));

        return $this->analytics->remember($key, function () use ($scope): array {
            $rows = $this->rows($scope->event?->id)
                ->when($scope->status, fn (Collection $c, string $s) => $c->where('status', $s))
                ->filter(fn ($r): bool => (! $scope->from || Carbon::parse($r->submitted_at)->gte($scope->from)) && (! $scope->until || Carbon::parse($r->submitted_at)->lte($scope->until)));

            $submitted = $rows->map(fn ($r): string => Carbon::parse($r->submitted_at)->toDateString())->countBy()->sortKeys();
            $approvedAt = $rows->where('status', Enrollment::STATUS_APPROVED)->filter(fn ($r) => $r->reviewed_at)->map(fn ($r): string => Carbon::parse($r->reviewed_at)->toDateString())->countBy();
            $reviewedAt = $rows->where('status', '!=', Enrollment::STATUS_PENDING)->filter(fn ($r) => $r->reviewed_at)->map(fn ($r): string => Carbon::parse($r->reviewed_at)->toDateString())->countBy();

            if ($submitted->isEmpty()) {
                return ['days' => [], 'weeks' => [], 'cumulative' => [], 'approved' => [], 'backlog' => []];
            }

            $days = [];
            $cumulative = [];
            $approved = [];
            $backlog = [];
            $total = $approvedTotal = $reviewedTotal = 0;

            $end = Carbon::parse(collect([$submitted->keys()->last(), $reviewedAt->keys()->max()])->filter()->max());

            for ($day = Carbon::parse($submitted->keys()->first()); $day->lte($end); $day->addDay()) {
                $d = $day->toDateString();
                $days[$d] = (int) ($submitted[$d] ?? 0);
                $total += $days[$d];
                $approvedTotal += (int) ($approvedAt[$d] ?? 0);
                $reviewedTotal += (int) ($reviewedAt[$d] ?? 0);
                $cumulative[$d] = $total;
                $approved[$d] = $approvedTotal;
                $backlog[$d] = $total - $reviewedTotal;
            }

            $weeks = [];

            foreach ($days as $d => $n) {
                $week = Carbon::parse($d)->isoFormat('GGGG-[W]WW');
                $weeks[$week] = ($weeks[$week] ?? 0) + $n;
            }

            return compact('days', 'weeks', 'cumulative', 'approved', 'backlog');
        });
    }

    /**
     * Cumulative registrations by number of days before the event, so editions on different dates line up.
     *
     * @return array<int, int> days before the event (0..$maxDays) => enrollments submitted at least that many days before
     */
    public function daysBefore(int $eventId, int $maxDays = 120): array
    {
        return $this->analytics->remember("days-before:{$eventId}:{$maxDays}", function () use ($eventId, $maxDays): array {
            $date = DB::table('events')->where('id', $eventId)->value('date');
            $event = Carbon::parse($date)->startOfDay();
            $ahead = $this->rows($eventId)->map(fn ($r): int => (int) Carbon::parse($r->submitted_at)->startOfDay()->diffInDays($event, false));
            $curve = [];

            for ($d = $maxDays; $d >= 0; $d--) {
                $curve[$d] = $ahead->filter(fn (int $n): bool => $n >= $d)->count();
            }

            return $curve;
        });
    }

    /** @return Collection<int, object{status: string, info_status: string, submitted_at: string, reviewed_at: ?string}> */
    private function rows(?int $eventId): Collection
    {
        return DB::table('enrollments')->when($eventId, fn ($q) => $q->where('event_id', $eventId))
            ->get(['status', 'info_status', 'submitted_at', 'reviewed_at']);
    }

    /** @return array{new: int, returning: int, retained: int, reactivated: int, no_previous: int} */
    public function breakdown(int $eventId): array
    {
        $c = $this->participation->classify($eventId);

        return ['new' => count($c['new']), 'returning' => count($c['returning']), 'retained' => count($c['retained']), 'reactivated' => count($c['reactivated']), 'no_previous' => count($c['new'])];
    }
}
