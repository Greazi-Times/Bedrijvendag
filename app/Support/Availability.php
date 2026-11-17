<?php

namespace App\Support;

use Carbon\CarbonInterface;

/** One definition of "is this open?" for everything with an on/off switch and an optional opening and closing moment (enrollment, feedback). */
final class Availability
{
    /** 'disabled' | 'not_open_yet' | 'closed' | 'open'. Switching it off always wins over the dates. */
    public static function state(bool $enabled, ?CarbonInterface $opensAt, ?CarbonInterface $closesAt): string
    {
        return match (true) {
            ! $enabled => 'disabled',
            $opensAt?->isFuture() => 'not_open_yet',
            $closesAt?->isPast() => 'closed',
            default => 'open',
        };
    }
}
