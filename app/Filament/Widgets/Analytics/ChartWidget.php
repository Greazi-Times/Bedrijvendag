<?php

namespace App\Filament\Widgets\Analytics;

use App\Services\Analytics\ChartCatalog;
use App\Services\Analytics\Scope;
use Filament\Widgets\ChartWidget as BaseChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Contracts\Support\Htmlable;
use Livewire\Attributes\Locked;

/** Renders one chart of the ChartCatalog. The key is locked and the catalog checks the viewer's permission. */
class ChartWidget extends BaseChartWidget
{
    use InteractsWithPageFilters;

    #[Locked]
    public string $chart = 'participation_growth';

    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 1;

    protected ?string $maxHeight = '260px';

    private ?array $built = null;

    public function getHeading(): string|Htmlable|null
    {
        return ChartCatalog::heading($this->chart);
    }

    public function getDescription(): string|Htmlable|null
    {
        $chart = $this->built();

        return $chart === null ? null : ($chart['empty'] ? 'Geen gegevens voor deze selectie.' : $chart['description']);
    }

    protected function getType(): string
    {
        return $this->built()['type'] ?? 'bar';
    }

    protected function getOptions(): array
    {
        return $this->built()['options'] ?? [];
    }

    protected function getData(): array
    {
        $chart = $this->built();

        return ['datasets' => $chart['datasets'] ?? [], 'labels' => $chart['labels'] ?? []];
    }

    /** @return array<string, mixed>|null */
    private function built(): ?array
    {
        $user = auth()->user();

        return $this->built ??= $user ? app(ChartCatalog::class)->make($this->chart, Scope::from($this->pageFilters), $user) : null;
    }
}
