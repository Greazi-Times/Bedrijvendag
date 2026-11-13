<?php

namespace App\Filament\Widgets\Analytics;

use App\Services\Analytics\Participation;
use Filament\Widgets\Widget;

/** Cohort heatmap: companies grouped by first edition and how many took part again, in counts and percentages. */
class CohortWidget extends Widget
{
    protected string $view = 'filament.widgets.analytics.cohort';

    protected int|string|array $columnSpan = 'full';

    protected function getViewData(): array
    {
        $cohorts = auth()->user()?->can('companies.view') ? app(Participation::class)->cohorts() : [];

        return ['cohorts' => $cohorts, 'width' => max(1, ...array_map(fn (array $c): int => count($c['cells']), $cohorts ?: [['cells' => []]]))];
    }
}
