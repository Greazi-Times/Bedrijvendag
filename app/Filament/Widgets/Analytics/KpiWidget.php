<?php

namespace App\Filament\Widgets\Analytics;

use App\Services\Analytics\Kpis;
use App\Services\Analytics\Scope;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget;
use Livewire\Attributes\Locked;

/** One section of stat cards in the dashboard card style. The section is locked: a client cannot ask for another one. */
class KpiWidget extends Widget
{
    use InteractsWithPageFilters;

    protected string $view = 'filament.widgets.analytics.kpi';

    #[Locked]
    public string $section = 'overview';

    #[Locked]
    public ?string $title = null;

    #[Locked]
    public ?string $subtitle = null;

    protected int|string|array $columnSpan = 'full';

    protected function getViewData(): array
    {
        $user = auth()->user();

        return [
            'cards' => $user ? app(Kpis::class)->section($this->section, Scope::from($this->pageFilters), $user) : [],
            'title' => $this->title,
            'subtitle' => $this->subtitle,
        ];
    }
}
