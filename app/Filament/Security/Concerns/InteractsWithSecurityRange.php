<?php

namespace App\Filament\Security\Concerns;

use Carbon\CarbonImmutable;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\Date;

/** The date range chosen on the Security overview, in the display timezone. */
trait InteractsWithSecurityRange
{
    use InteractsWithPageFilters;

    public static function canView(): bool
    {
        return auth()->user()?->can('security.view') ?? false;
    }

    /** @return array{0: CarbonImmutable, 1: CarbonImmutable} start and end, in UTC */
    protected function range(): array
    {
        $timezone = config('audit.display_timezone');
        $from = $this->pageFilters['from'] ?? null;
        $until = $this->pageFilters['until'] ?? null;

        return [
            ($from ? Date::parse($from, $timezone) : Date::now($timezone)->subDays(6))->startOfDay()->utc(),
            ($until ? Date::parse($until, $timezone) : Date::now($timezone))->endOfDay()->utc(),
        ];
    }

    /** The same range as an audit log table filter. */
    protected function rangeFilter(): array
    {
        $timezone = config('audit.display_timezone');
        [$from, $until] = $this->range();

        return ['created_at' => ['from' => $from->setTimezone($timezone)->toDateString(), 'until' => $until->setTimezone($timezone)->toDateString()]];
    }
}
