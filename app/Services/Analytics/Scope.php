<?php

namespace App\Services\Analytics;

use App\Models\Enrollment;
use App\Models\Event;
use Illuminate\Support\Carbon;

/** What the global dashboard filters select. `event` is null in "all editions" mode. */
final class Scope
{
    public function __construct(
        public readonly ?Event $event,
        public readonly ?Event $compare,
        public readonly ?string $status = null,
        public readonly ?Carbon $from = null,
        public readonly ?Carbon $until = null,
    ) {}

    /** @param array<string, mixed>|null $filters the dashboard filter form state */
    public static function from(?array $filters): self
    {
        $events = Event::query()->orderBy('date')->orderBy('id')->get(['id', 'name', 'date']);
        $edition = $filters['edition'] ?? null;
        $event = $edition === 'all' ? null : ($events->firstWhere('id', (int) $edition) ?? app(Analytics::class)->defaultEvent());
        $compare = ($filters['compare'] ?? null) ? $events->firstWhere('id', (int) $filters['compare']) : null;

        // Default comparison: the edition immediately before the selected one.
        if ($event && ! $compare) {
            $compare = $events->filter(fn (Event $e): bool => [$e->date->toDateString(), $e->id] < [$event->date->toDateString(), $event->id])->last();
        }

        $status = $filters['status'] ?? null;

        return new self(
            $event,
            $event && $compare?->is($event) ? null : $compare,
            in_array($status, [Enrollment::STATUS_PENDING, Enrollment::STATUS_APPROVED, Enrollment::STATUS_REJECTED], true) ? $status : null,
            filled($filters['from'] ?? null) ? Carbon::parse($filters['from'])->startOfDay() : null,
            filled($filters['until'] ?? null) ? Carbon::parse($filters['until'])->endOfDay() : null,
        );
    }

    public function label(): string
    {
        return $this->event?->name ?? 'Alle edities';
    }
}
