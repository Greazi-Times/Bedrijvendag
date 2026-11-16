<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\Analytics\ChartWidget;
use App\Filament\Widgets\Analytics\CohortWidget;
use App\Filament\Widgets\Analytics\ComparisonWidget;
use App\Filament\Widgets\Analytics\KpiWidget;
use App\Filament\Widgets\Analytics\ListWidget;
use App\Filament\Widgets\Analytics\NotesWidget;
use App\Filament\Widgets\Analytics\OverviewWidget;
use App\Filament\Widgets\BorrelEnrollmentsPerEventChart;
use App\Filament\Widgets\DashboardStatsOverview;
use App\Models\Enrollment;
use App\Models\Event;
use App\Services\Analytics\Analytics;
use App\Services\Analytics\ChartCatalog;
use App\Services\Analytics\Kpis;
use App\Services\Analytics\ListCatalog;
use App\Services\Analytics\Scope;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\WidgetConfiguration;
use Illuminate\Support\Str;
use Livewire\Attributes\Url;

/**
 * The statistics dashboard: global filters on top, one tab per section. The widgets of every tab are generic
 * (stat cards, charts, lists) configured by key; all calculations live in App\Services\Analytics.
 */
class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    /** Slug of the open section; kept in the URL (?sectie=) so a reload or shared link opens the same tab. */
    #[Url(as: 'sectie')]
    public ?string $activeTab = null;

    /** Slug of the open sub-section inside the tab (?onderdeel=). Only that sub-section's widgets are mounted. */
    #[Url(as: 'onderdeel')]
    public ?string $activeSection = null;

    /** An explicit comparison that became the selected edition is dropped, so the automatic previous edition applies again. */
    public function updatedFilters(): void
    {
        $f = $this->filters ?? [];

        if (filled($f['compare'] ?? null) && (string) $f['compare'] === (string) ($f['edition'] ?? '')) {
            $this->filters['compare'] = null;
        }

        // Same as Filament's own hook, which this method replaces: keep the filters for the session.
        $this->persistsFiltersInSession() && session()->put($this->getFiltersSessionKey(), $this->filters);
    }

    public function updatedActiveTab(): void
    {
        $this->activeSection = null;
    }

    /** Permission needed to see a stat-card section at all; cards inside are checked again one by one. */
    private const KPI_PERMISSIONS = [
        'overview' => null, 'actions' => null, 'admin' => 'enrollments.view', 'enrollments' => 'enrollments.view', 'breakdown' => 'enrollments.view',
        'retention' => 'companies.view', 'stands' => 'event-stands.view', 'finance' => Kpis::FINANCIAL_PERMISSION,
        'catering' => 'enrollments.view', 'promo' => 'enrollments.view', 'taxonomy' => null,
    ];

    public function filtersForm(Schema $schema): Schema
    {
        $editions = fn (): array => Event::query()->orderByDesc('date')->orderByDesc('id')->get(['id', 'name', 'date'])
            ->mapWithKeys(fn (Event $e): array => [$e->id => "{$e->name} ({$e->date->format('d-m-Y')})"])->all();

        // The edition is the one filter people use all the time, so it sits first and wide; the rest is tucked away but its state stays visible as chips.
        return $schema->columns(1)->components([
            Grid::make(['default' => 1, 'md' => 2])->columnSpanFull()->schema([
                Select::make('edition')->label('Editie')->options(fn (): array => ['all' => 'Alle edities'] + $editions())
                    ->default(fn () => app(Analytics::class)->defaultEvent()?->id)->selectablePlaceholder(false)->native(false)->live()
                    ->hint('Standaard: eerstvolgende editie'),
                Select::make('compare')->label('Vergelijk met')->options(fn ($get): array => array_diff_key($editions(), [(string) $get('edition') => true]))->placeholder('Vorige editie')->native(false)
                    ->hint('Voor vergelijkingen'),
            ]),
            Section::make('Meer filters')->columnSpanFull()->compact()->collapsible()->collapsed()->columns(['default' => 1, 'md' => 3])->schema([
                Select::make('status')->label('Status inschrijving')->placeholder('Alle statussen')->native(false)
                    ->options([Enrollment::STATUS_PENDING => 'In behandeling', Enrollment::STATUS_APPROVED => 'Goedgekeurd', Enrollment::STATUS_REJECTED => 'Afgewezen'])
                    ->helperText('Geldt voor de inschrijvingsgrafieken.'),
                DatePicker::make('from')->label('Ingediend vanaf')->native(false)->displayFormat('d-m-Y'),
                DatePicker::make('until')->label('Ingediend t/m')->native(false)->displayFormat('d-m-Y')
                    ->helperText('Periode geldt voor de inschrijvingsgrafieken.'),
            ]),
        ]);
    }

    /** Back to the defaults (current edition, previous edition as comparison, no status or period). */
    public function resetFilters(): void
    {
        $this->filters = null;
        session()->forget($this->getFiltersSessionKey());
        $this->getFiltersForm()->fill();
    }

    /** @return list<string> the non-default filters, as labels for the chips above the widgets */
    private function appliedFilters(): array
    {
        $f = $this->filters ?? [];
        $chips = [];

        if (($f['edition'] ?? null) === 'all') {
            $chips[] = 'Alle edities';
        }

        filled($f['compare'] ?? null) && $chips[] = 'Vergeleken met '.(Event::find($f['compare'])?->name ?? '?');
        filled($f['status'] ?? null) && $chips[] = 'Status: '.['pending' => 'In behandeling', 'approved' => 'Goedgekeurd', 'rejected' => 'Afgewezen'][$f['status']] ?? '';
        filled($f['from'] ?? null) && $chips[] = 'Vanaf '.\Illuminate\Support\Carbon::parse($f['from'])->format('d-m-Y');
        filled($f['until'] ?? null) && $chips[] = 'T/m '.\Illuminate\Support\Carbon::parse($f['until'])->format('d-m-Y');

        return $chips;
    }

    public function content(Schema $schema): Schema
    {
        // Only the active sub-section's widgets exist. Every mounted widget takes part in each filter update, and Livewire
        // refuses requests with more than payload.max_components (20) components, so rendering whole tabs (~60) failed.
        // Closures: Filament builds the schema at boot, before a changed activeTab is applied, so it is resolved at render time.
        $tabs = fn (): array => array_filter(array_map(
            fn (array $tab): array => ['icon' => $tab['icon'], 'sections' => array_filter($tab['sections'], fn (array $w): bool => $w !== [])],
            $this->tabs(),
        ), fn (array $tab): bool => $tab['sections'] !== []);

        return $schema->components([
            $this->getFiltersFormContentComponent(),
            View::make('filament.pages.dashboard-tabs')->viewData(function () use ($tabs): array {
                $all = $tabs();
                $label = $this->activeTabLabel($all);
                $sections = array_keys($all[$label]['sections'] ?? []);

                return [
                    'tabs' => array_values(array_map(fn (array $tab, string $l): array => ['slug' => Str::slug($l), 'label' => $l, 'icon' => $tab['icon']], $all, array_keys($all))),
                    'active' => $this->activeTabSlug($all),
                    'sections' => count($sections) > 1 ? array_map(fn (string $l): array => ['slug' => Str::slug($l), 'label' => $l], $sections) : [],
                    'activeSection' => Str::slug((string) $this->activeSectionLabel($all[$label]['sections'] ?? [])),
                    'chips' => $this->appliedFilters(),
                    'notice' => Scope::from($this->filters)->notice(),
                ];
            }),
            Grid::make(['default' => 1, 'lg' => 2])->schema(function () use ($tabs): array {
                $all = $tabs();
                $active = $this->activeTabSlug($all);
                $sections = $all[$this->activeTabLabel($all)]['sections'] ?? [];
                $section = Str::slug((string) $this->activeSectionLabel($sections));

                // Filament keys widgets by position; keying by tab and section too means switching mounts fresh widgets instead of reusing one with old properties.
                return collect($this->getWidgetsSchemaComponents($sections[$this->activeSectionLabel($sections)] ?? []))
                    ->values()->map(fn ($component, int $i) => $component->key("{$active}-{$section}-{$i}"))->all();
            }),
        ]);
    }

    /** @param array<string, mixed> $sections */
    private function activeSectionLabel(array $sections): ?string
    {
        foreach (array_keys($sections) as $label) {
            if (Str::slug($label) === $this->activeSection) {
                return $label;
            }
        }

        return array_key_first($sections);
    }

    /** @param array<string, mixed> $tabs */
    private function activeTabLabel(array $tabs): ?string
    {
        foreach (array_keys($tabs) as $label) {
            if (Str::slug($label) === $this->activeTab) {
                return $label;
            }
        }

        return array_key_first($tabs);
    }

    /** @param array<string, mixed> $tabs */
    private function activeTabSlug(array $tabs): string
    {
        return Str::slug((string) $this->activeTabLabel($tabs));
    }

    /** @return array<string, array{icon: Heroicon, sections: array<string, list<WidgetConfiguration|class-string>>}> */
    private function tabs(): array
    {
        $kpi = fn (string $section, ?string $title = null, ?string $subtitle = null): array => [KpiWidget::class, KpiWidget::make(['section' => $section, 'title' => $title, 'subtitle' => $subtitle]), self::KPI_PERMISSIONS[$section] ?? null];
        $chart = fn (string $key): array => [ChartWidget::class, ChartWidget::make(['chart' => $key]), ChartCatalog::permission($key)];
        $list = fn (string $key): array => [ListWidget::class, ListWidget::make(['list' => $key]), ListCatalog::permission($key)];
        $notes = fn (string $section): array => [NotesWidget::class, NotesWidget::make(['section' => $section]), self::KPI_PERMISSIONS[$section] ?? null];
        $plain = fn (string $class, array $props = [], ?string $permission = null): array => [$class, $class::make($props), $permission];

        // Six categories; sub-sections keep each mounted set of widgets under Livewire's component limit.
        // Moved from the old nine tabs: Inschrijvingen+Administratie -> Aanmeldingen; Bedrijven en retentie+Sectoren en opleidingen -> Bedrijven;
        // Plattegrond en stands+Catering en borrel -> Evenement; Financieel -> Financiën; Historische vergelijking -> Historie.
        $definition = [
            'Overzicht' => [Heroicon::Squares2x2, [
                'Overzicht' => [
                    $plain(OverviewWidget::class),
                    $chart('enrollments_days_before'), $chart('retention_new_returning'), $chart('stands_occupancy'), $chart('participation_growth'),
                    $kpi('overview', 'Alle cijfers van de editie', 'Vergeleken met de vorige of gekozen vergelijkingseditie. Klik op een kaart voor details.'),
                    $notes('overview'),
                ],
            ]],
            'Aanmeldingen' => [Heroicon::ClipboardDocumentCheck, [
                'Inschrijvingen' => [
                    $kpi('enrollments', 'Inschrijvingen'), $kpi('breakdown', 'Nieuw en terugkerend'),
                    $chart('enrollments_daily'), $chart('enrollments_weekly'), $chart('enrollments_cumulative'), $chart('enrollments_approved'),
                    $chart('enrollments_backlog'), $chart('enrollments_days_before'), $notes('enrollments'),
                ],
                'Beoordeling' => [
                    $kpi('actions', 'Openstaande taken'), $kpi('admin', 'Beoordeling'),
                    $chart('enrollments_backlog'),
                    $plain(DashboardStatsOverview::class), $plain(BorrelEnrollmentsPerEventChart::class),
                ],
            ]],
            'Bedrijven' => [Heroicon::ArrowPath, [
                'Retentie' => [
                    $kpi('retention', 'Deelname en retentie'),
                    $chart('retention_new_returning'), $chart('retention_rate'), $chart('retention_lost'), $chart('retention_reactivated'),
                    $chart('attended_distribution'), $chart('streak_distribution'), $chart('participation_growth'), $notes('retention'),
                ],
                'Deelnamegeschiedenis' => [
                    $list('matrix'), $list('participation'),
                ],
                'Cohortanalyse' => [
                    [CohortWidget::class, CohortWidget::make(), 'companies.view'], $chart('cohort_second'),
                ],
                'Sectoren en opleidingen' => [
                    $kpi('taxonomy', 'Sectoren en opleidingen'),
                    $chart('sectors_bar'), $chart('sectors_new_returning'), $chart('sectors_stacked'), $chart('sectors_growth'),
                    $chart('educations_bar'), $chart('educations_trend'), $chart('educations_coverage'), $notes('taxonomy'),
                ],
            ]],
            'Evenement' => [Heroicon::MapPin, [
                'Standbezetting' => [
                    $kpi('stands', 'Stands'),
                    $chart('stands_occupancy'), $chart('stands_occupied_free'), $chart('stands_trend'), $chart('stands_vs_approved'), $chart('stands_partner'),
                    $list('approved_no_stand'), $list('flagged_stands'), $list('free_partner_stands'), $list('multi_stand'), $list('grandfathered'), $notes('stands'),
                ],
                'Catering en borrel' => [
                    $kpi('catering', 'Lunch en borrel', 'Anoniem; laatste versie van het tweede formulier per bedrijf.'),
                    $kpi('promo', 'Promotiemateriaal'),
                    $chart('diet_distribution'), $chart('lunch_per_edition'), $chart('catering_compare'), $chart('lunch_over_time'), $chart('drinks_compare'),
                    $list('promo_special'), $notes('catering'),
                ],
            ]],
            'Financiën' => [Heroicon::Banknotes, [
                'Financiën' => [
                    $kpi('finance', 'Verwachte opbrengst'),
                    $chart('revenue_per_edition'), $chart('revenue_over_time'), $chart('revenue_new_returning'), $chart('avg_fee'), $notes('finance'),
                ],
            ]],
            'Historie' => [Heroicon::ChartBar, [
                'Historie' => [
                    [ComparisonWidget::class, ComparisonWidget::make(['mode' => 'pair']), null],
                    [ComparisonWidget::class, ComparisonWidget::make(['mode' => 'all']), null],
                    $notes('comparison'),
                ],
            ]],
        ];

        $user = auth()->user();
        $tabs = [];

        foreach ($definition as $label => [$icon, $sections]) {
            $tabs[$label] = ['icon' => $icon, 'sections' => array_map(fn (array $specs): array => array_values(array_map(
                fn (array $spec) => $spec[1],
                array_filter($specs, fn (array $spec): bool => $spec[0]::canView() && ($spec[2] === null || ($user?->can($spec[2]) ?? false))),
            )), $sections)];
        }

        return $tabs;
    }
}
