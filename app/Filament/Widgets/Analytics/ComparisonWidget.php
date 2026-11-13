<?php

namespace App\Filament\Widgets\Analytics;

use App\Models\Event;
use App\Services\Analytics\Comparison;
use App\Services\Analytics\Scope;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget;
use Livewire\Attributes\Locked;

/** Two views of edition comparison: selected vs comparison edition (with change), and all editions side by side. */
class ComparisonWidget extends Widget
{
    use InteractsWithPageFilters;

    protected string $view = 'filament.widgets.analytics.comparison';

    protected int|string|array $columnSpan = 'full';

    #[Locked]
    public string $mode = 'pair';

    protected function getViewData(): array
    {
        $user = auth()->user();
        $scope = Scope::from($this->pageFilters);
        $ids = $this->mode === 'all'
            ? Event::query()->orderBy('date')->orderBy('id')->pluck('id')->map(fn ($id): int => (int) $id)->all()
            : array_values(array_filter([$scope->event?->id, $scope->compare?->id]));

        return ['table' => $user ? app(Comparison::class)->table($ids, $user) : ['editions' => [], 'rows' => []], 'mode' => $this->mode];
    }
}
