<?php

namespace App\Filament\Widgets;

use App\Models\BorrelEnrollment;
use App\Models\Company;
use App\Models\Event;
use App\Models\Sector;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DashboardStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $dateColumn = 'date';

        $event =
            Event::query()
                ->whereDate($dateColumn, '>=', now()->toDateString())
                ->orderBy($dateColumn)
                ->first()
            ?? Event::query()->orderBy($dateColumn, 'desc')->first();

        $borrelCount = 0;

        if ($event) {
            // option A: if you have relation $event->borrelEnrollments()
            // $borrelCount = $event->borrelEnrollments()->count();

            // option B: if borrel enrollments table has event_id
            $borrelCount = BorrelEnrollment::query()->where('event_id', $event->id)->count();
        }

        return [
            Stat::make('Events', Event::count())
                ->icon('heroicon-o-calendar-days')
                ->color('primary')
                ->extraAttributes([
                    'class' => 'stats-bg-icon relative overflow-hidden transition duration-200 hover:shadow-lg hover:ring-2 hover:ring-primary-500/40 hover:bg-gray-50 dark:hover:bg-white/5',
                ]),

            Stat::make('Companies', Company::count())
                ->icon('heroicon-o-building-office-2')
                ->color('info')
                ->extraAttributes([
                    'class' => 'stats-bg-icon relative overflow-hidden transition duration-200 hover:shadow-lg hover:ring-2 hover:ring-info-500/40 hover:bg-gray-50 dark:hover:bg-white/5',
                ]),

            Stat::make('Borrel enrollments (current event)', $borrelCount)
                ->icon('heroicon-o-user-group')
                ->color('success')
                ->extraAttributes([
                    'class' => 'stats-bg-icon relative overflow-hidden transition duration-200 hover:shadow-lg hover:ring-2 hover:ring-success-500/40 hover:bg-gray-50 dark:hover:bg-white/5',
                ]),

            Stat::make('Sectors', Sector::count())
                ->icon('heroicon-o-squares-2x2')
                ->color('warning')
                ->extraAttributes([
                    'class' => 'stats-bg-icon relative overflow-hidden transition duration-200 hover:shadow-lg hover:ring-2 hover:ring-warning-500/40 hover:bg-gray-50 dark:hover:bg-white/5',
                ]),
        ];
    }
}
