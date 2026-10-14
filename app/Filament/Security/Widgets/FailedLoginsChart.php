<?php

namespace App\Filament\Security\Widgets;

use App\Filament\Security\Concerns\InteractsWithSecurityRange;
use App\Models\AuditLog;
use Filament\Widgets\ChartWidget;

class FailedLoginsChart extends ChartWidget
{
    use InteractsWithSecurityRange;

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected ?string $heading = 'Failed sign-ins and lockouts per day';

    protected ?string $description = 'Days in UTC.';

    protected ?string $maxHeight = '260px';

    protected function getData(): array
    {
        [$from, $until] = $this->range();

        $rows = AuditLog::query()
            ->whereIn('event', ['auth.login.failed', 'security.login.lockout'])
            ->whereBetween('created_at', [$from, $until])
            ->selectRaw('date(created_at) as day, event, count(*) as total')
            ->groupBy('day', 'event')
            ->get();

        $days = collect();

        for ($day = $from->startOfDay(); $day <= $until; $day = $day->addDay()) {
            $days->push($day->toDateString());
        }

        $series = fn (string $event): array => $days
            ->map(fn (string $day): int => (int) $rows->first(fn ($row): bool => $row->day === $day && $row->event === $event)?->total)
            ->all();

        return [
            'datasets' => [
                ['label' => 'Failed sign-ins', 'data' => $series('auth.login.failed'), 'tension' => 0.2],
                ['label' => 'Lockouts', 'data' => $series('security.login.lockout'), 'tension' => 0.2],
            ],
            'labels' => $days->all(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
