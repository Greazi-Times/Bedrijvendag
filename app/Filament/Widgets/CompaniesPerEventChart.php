<?php

namespace App\Filament\Widgets;

use App\Models\Event;
use Filament\Widgets\ChartWidget;

class CompaniesPerEventChart extends ChartWidget
{
    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 1;

    protected ?string $heading = 'Companies per event';

    protected function getData(): array
    {
        $dateColumn = 'date';

        $events = Event::query()
            ->withCount('companies')
            ->orderBy($dateColumn)
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Companies',
                    'data' => $events->pluck('companies_count')->all(),
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
