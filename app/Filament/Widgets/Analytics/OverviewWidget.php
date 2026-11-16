<?php

namespace App\Filament\Widgets\Analytics;

use App\Services\Analytics\OverviewData;
use App\Services\Analytics\Scope;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget;

/** The whole Overzicht header in one component: edition, key figures, secondary figures, attention items and insights. */
class OverviewWidget extends Widget
{
    use InteractsWithPageFilters;

    protected string $view = 'filament.widgets.analytics.overview';

    protected int|string|array $columnSpan = 'full';

    protected function getViewData(): array
    {
        $user = auth()->user();

        return $user ? app(OverviewData::class)->build(Scope::from($this->pageFilters), $user) : ['event' => null, 'compare' => null, 'primary' => [], 'secondary' => [], 'attention' => [], 'insights' => []];
    }
}
