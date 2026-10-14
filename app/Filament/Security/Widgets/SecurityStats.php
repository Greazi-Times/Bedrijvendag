<?php

namespace App\Filament\Security\Widgets;

use App\Filament\Resources\AuditLogs\AuditLogResource;
use App\Filament\Security\Concerns\InteractsWithSecurityRange;
use App\Models\AuditLog;
use App\Models\SecurityAlert;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SecurityStats extends StatsOverviewWidget
{
    use InteractsWithSecurityRange;

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        [$from, $until] = $this->range();

        // One aggregate query for all counters.
        $totals = AuditLog::query()
            ->whereBetween('created_at', [$from, $until])
            ->selectRaw("sum(case when event = 'auth.login.failed' then 1 else 0 end) as failed_logins")
            ->selectRaw("sum(case when event = 'security.login.lockout' then 1 else 0 end) as lockouts")
            ->selectRaw("sum(case when event in ('security.access.denied', 'security.privilege.denied') then 1 else 0 end) as denied")
            ->selectRaw("sum(case when severity = 'high' then 1 else 0 end) as high")
            ->selectRaw("sum(case when severity = 'critical' then 1 else 0 end) as critical")
            ->first();

        $link = fn (array $filters): string => AuditLogResource::filteredUrl([...$this->rangeFilter(), ...$filters]);

        return [
            Stat::make('Failed sign-ins', (int) $totals->failed_logins)
                ->url($link(['event' => ['values' => ['auth.login.failed']]])),
            Stat::make('Sign-in lockouts', (int) $totals->lockouts)
                ->url($link(['event' => ['values' => ['security.login.lockout']]])),
            Stat::make('Denied access', (int) $totals->denied)
                ->description('403 responses and refused privilege changes')
                ->url($link(['event' => ['values' => ['security.access.denied', 'security.privilege.denied']]])),
            Stat::make('High severity', (int) $totals->high)
                ->color($totals->high ? 'danger' : null)
                ->url($link(['severity' => ['values' => ['high']]])),
            Stat::make('Critical', (int) $totals->critical)
                ->color($totals->critical ? 'danger' : null)
                ->url($link(['severity' => ['values' => ['critical']]])),
            Stat::make('Open alerts', SecurityAlert::query()->whereNull('acknowledged_at')->count())
                ->description('Not limited to the date range'),
        ];
    }
}
