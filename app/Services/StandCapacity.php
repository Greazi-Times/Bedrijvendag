<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventStand;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/** Lowering an edition's stand counts must never remove an occupied stand. */
class StandCapacity
{
    private const FIELDS = ['company' => 'max_stands', 'partner' => 'partner_stand_count'];

    /** @return Collection<int, EventStand> occupied stands of this type above the given maximum */
    public function occupiedAbove(Event $event, string $type, int $max): Collection
    {
        return EventStand::query()
            ->with(['company', 'partner'])
            ->where('event_id', $event->id)
            ->where('type', $type)
            ->whereRaw('CAST(stand_number AS UNSIGNED) > ?', [max(0, $max)])
            ->where(fn ($q) => $q->whereNotNull('company_id')->orWhereNotNull('partner_id'))
            ->orderByRaw('CAST(stand_number AS UNSIGNED)')
            ->get();
    }

    /** @throws ValidationException when the pending counts on the event would drop an occupied stand */
    public function assertCanResize(Event $event): void
    {
        $errors = [];

        foreach (self::FIELDS as $type => $field) {
            if (! $event->exists || ! $event->isDirty($field)) {
                continue;
            }

            $max = (int) ($event->{$field} ?? 0);
            $occupied = $this->occupiedAbove($event, $type, $max);

            if ($occupied->isNotEmpty()) {
                $list = $occupied->map(fn (EventStand $s): string => ($type === 'partner' ? 'P' : '').(int) $s->stand_number.' ('.($s->company?->name ?? $s->partner?->name ?? '?').')')->implode(', ');
                $errors[$field] = "Cannot reduce the number of {$type} stands to {$max}: stand {$list} is occupied. Clear or move it first; nothing was changed.";
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }
}
