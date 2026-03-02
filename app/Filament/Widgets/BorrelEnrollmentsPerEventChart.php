<?php

namespace App\Filament\Widgets;

use App\Models\Event;
use Filament\Widgets\ChartWidget;

class BorrelEnrollmentsPerEventChart extends ChartWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 1;

    protected ?string $heading = 'Borrel enrollments per event';

    protected function getData(): array
    {
        $dateColumn = 'date';

        $events = Event::query()
            ->withCount('borrelEnrollments')
            ->orderBy($dateColumn)
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Enrollments',
                    'data' => $events->pluck('borrel_enrollments_count')->all(),
                    'tension' => 0.4, // makes the line smooth
                ],
            ],
            'labels' => $events->map(fn ($e) => $e->name)->all(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
