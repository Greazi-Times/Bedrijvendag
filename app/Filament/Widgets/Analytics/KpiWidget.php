<?php

namespace App\Filament\Widgets\Analytics;

use App\Services\Analytics\Kpis;
use App\Services\Analytics\Scope;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Livewire\Attributes\Locked;

/** One section of stat cards. The section is locked: a client cannot ask for another one. */
class KpiWidget extends StatsOverviewWidget
{
    use InteractsWithPageFilters;

    #[Locked]
    public string $section = 'overview';

    #[Locked]
    public ?string $title = null;

    #[Locked]
    public ?string $subtitle = null;

    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 'full';

    protected function getHeading(): ?string
    {
        return $this->title;
    }

    protected function getDescription(): ?string
    {
        return $this->subtitle;
    }

    protected function getColumns(): int|array|null
    {
        return ['default' => 1, 'sm' => 2, 'lg' => 3, 'xl' => 4];
    }

    protected function getStats(): array
    {
        $user = auth()->user();

        if (! $user) {
            return [];
        }

        return array_map(function (array $card): Stat {
            $stat = Stat::make($card['label'], $card['value'])->color($card['color']);

            $card['description'] && $stat->description($card['description']);
            $card['url'] && $stat->url($card['url']);
            $card['tooltip'] && $stat->extraAttributes(['title' => $card['tooltip']]);

            return $stat;
        }, app(Kpis::class)->section($this->section, Scope::from($this->pageFilters), $user));
    }
}
