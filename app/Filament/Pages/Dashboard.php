<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\Analytics\ChartWidget;
use App\Filament\Widgets\Analytics\CohortWidget;
use App\Filament\Widgets\Analytics\ComparisonWidget;
use App\Filament\Widgets\Analytics\KpiWidget;
use App\Filament\Widgets\Analytics\ListWidget;
use App\Filament\Widgets\Analytics\NotesWidget;
use App\Filament\Widgets\BorrelEnrollmentsPerEventChart;
use App\Filament\Widgets\DashboardStatsOverview;
use App\Filament\Widgets\UpcomingEventWidget;
use App\Models\Enrollment;
use App\Models\Event;
use App\Services\Analytics\Analytics;
use App\Services\Analytics\ChartCatalog;
use App\Services\Analytics\Kpis;
use App\Services\Analytics\ListCatalog;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Components\Grid;
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

        return $schema->components([
            Select::make('edition')->label('Editie')->options(fn (): array => ['all' => 'Alle edities'] + $editions())
                ->default(fn () => app(Analytics::class)->defaultEvent()?->id)->selectablePlaceholder(false)->native(false)
                ->helperText('Standaard: eerstvolgende editie, anders de meest recente.'),
            Select::make('compare')->label('Vergelijk met')->options($editions)->placeholder('Vorige editie')->native(false)
                ->helperText('Voor vergelijkingen met een eerdere editie.'),
            Select::make('status')->label('Status inschrijving')->placeholder('Alle statussen')->native(false)
                ->options([Enrollment::STATUS_PENDING => 'In behandeling', Enrollment::STATUS_APPROVED => 'Goedgekeurd', Enrollment::STATUS_REJECTED => 'Afgewezen'])
                ->helperText('Geldt voor de inschrijvingsgrafieken.'),
            DatePicker::make('from')->label('Ingediend vanaf')->native(false)->displayFormat('d-m-Y'),
            DatePicker::make('until')->label('Ingediend t/m')->native(false)->displayFormat('d-m-Y')
                ->helperText('Periode geldt voor de inschrijvingsgrafieken.'),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        // Only the active tab's widgets exist. Every mounted widget takes part in each filter update, and Livewire
        // refuses requests with more than payload.max_components (20) components, so rendering all tabs (~60) failed.
        // Closures: Filament builds the schema at boot, before a changed activeTab is applied, so it is resolved at render time.
        $tabs = fn (): array => array_filter($this->tabs(), fn (array $tab): bool => $tab['widgets'] !== []);

        return $schema->components([
            $this->getFiltersFormContentComponent(),
            View::make('filament.pages.dashboard-tabs')->viewData(fn (): array => [
                'tabs' => array_values(array_map(fn (array $tab, string $label): array => ['slug' => Str::slug($label), 'label' => $label, 'icon' => $tab['icon']], $tabs(), array_keys($tabs()))),
                'active' => $this->activeTabSlug($tabs()),
            ]),
            Grid::make(['default' => 1, 'lg' => 2])->schema(function () use ($tabs): array {
                $all = $tabs();
                $active = $this->activeTabSlug($all);

                // Filament keys widgets by position; keying by tab too means switching tabs mounts fresh widgets instead of reusing one with old properties.
                return collect($this->getWidgetsSchemaComponents($all[$this->activeTabLabel($all)]['widgets'] ?? []))
                    ->values()->map(fn ($component, int $i) => $component->key("{$active}-{$i}"))->all();
            }),
        ]);
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

    /** @return array<string, array{icon: Heroicon, widgets: list<WidgetConfiguration|class-string>}> */
    private function tabs(): array
    {
        $kpi = fn (string $section, ?string $title = null, ?string $subtitle = null): array => [KpiWidget::class, KpiWidget::make(['section' => $section, 'title' => $title, 'subtitle' => $subtitle]), self::KPI_PERMISSIONS[$section] ?? null];
        $chart = fn (string $key): array => [ChartWidget::class, ChartWidget::make(['chart' => $key]), ChartCatalog::permission($key)];
        $list = fn (string $key): array => [ListWidget::class, ListWidget::make(['list' => $key]), ListCatalog::permission($key)];
        $notes = fn (string $section): array => [NotesWidget::class, NotesWidget::make(['section' => $section]), self::KPI_PERMISSIONS[$section] ?? null];
        $plain = fn (string $class, array $props = [], ?string $permission = null): array => [$class, $class::make($props), $permission];

        $definition = [
            'Overzicht' => [Heroicon::Squares2x2, [
                $plain(UpcomingEventWidget::class),
                $kpi('overview', 'Geselecteerde editie', 'Vergeleken met de vorige of gekozen vergelijkingseditie. Klik op een kaart voor details.'),
                $kpi('actions', 'Actie vereist', 'Alleen onderdelen waartoe u toegang heeft.'),
                $chart('participation_growth'), $chart('retention_new_returning'), $notes('overview'),
            ]],
            'Inschrijvingen' => [Heroicon::ClipboardDocumentCheck, [
                $kpi('enrollments', 'Inschrijvingen'), $kpi('breakdown', 'Nieuw en terugkerend'),
                $chart('enrollments_daily'), $chart('enrollments_weekly'), $chart('enrollments_cumulative'), $chart('enrollments_approved'),
                $chart('enrollments_backlog'), $chart('enrollments_days_before'), $notes('enrollments'),
            ]],
            'Bedrijven en retentie' => [Heroicon::ArrowPath, [
                $kpi('retention', 'Deelname en retentie'),
                $chart('retention_new_returning'), $chart('retention_rate'), $chart('retention_lost'), $chart('retention_reactivated'),
                $chart('attended_distribution'), $chart('streak_distribution'), $chart('participation_growth'), $chart('cohort_second'),
                [CohortWidget::class, CohortWidget::make(), 'companies.view'], $list('participation'), $notes('retention'),
            ]],
            'Plattegrond en stands' => [Heroicon::MapPin, [
                $kpi('stands', 'Stands'),
                $chart('stands_occupancy'), $chart('stands_occupied_free'), $chart('stands_trend'), $chart('stands_vs_approved'), $chart('stands_partner'),
                $list('approved_no_stand'), $list('flagged_stands'), $list('free_partner_stands'), $list('multi_stand'), $list('grandfathered'), $notes('stands'),
            ]],
            'Financieel' => [Heroicon::Banknotes, [
                $kpi('finance', 'Verwachte deelnamekosten'),
                $chart('revenue_per_edition'), $chart('revenue_over_time'), $chart('revenue_new_returning'), $chart('avg_fee'), $notes('finance'),
            ]],
            'Catering en borrel' => [Heroicon::Cake, [
                $kpi('catering', 'Lunch en borrel', 'Anoniem; laatste versie van het tweede formulier per bedrijf.'),
                $kpi('promo', 'Promotiemateriaal'),
                $chart('diet_distribution'), $chart('lunch_per_edition'), $chart('catering_compare'), $chart('lunch_over_time'), $chart('drinks_compare'),
                $list('promo_special'), $notes('catering'),
            ]],
            'Sectoren en opleidingen' => [Heroicon::AcademicCap, [
                $kpi('taxonomy', 'Sectoren en opleidingen'),
                $chart('sectors_bar'), $chart('sectors_new_returning'), $chart('sectors_stacked'), $chart('sectors_growth'),
                $chart('educations_bar'), $chart('educations_trend'), $chart('educations_coverage'), $notes('taxonomy'),
            ]],
            'Historische vergelijking' => [Heroicon::ChartBar, [
                [ComparisonWidget::class, ComparisonWidget::make(['mode' => 'pair']), null],
                [ComparisonWidget::class, ComparisonWidget::make(['mode' => 'all']), null],
                $notes('comparison'),
            ]],
            'Administratie' => [Heroicon::Cog6Tooth, [
                $kpi('actions', 'Openstaande taken'), $kpi('admin', 'Beoordeling'),
                $chart('enrollments_backlog'),
                $plain(DashboardStatsOverview::class), $plain(BorrelEnrollmentsPerEventChart::class),
            ]],
        ];

        $user = auth()->user();
        $tabs = [];

        foreach ($definition as $label => [$icon, $specs]) {
            $tabs[$label] = ['icon' => $icon, 'widgets' => array_values(array_map(
                fn (array $spec) => $spec[1],
                array_filter($specs, fn (array $spec): bool => $spec[0]::canView() && ($spec[2] === null || ($user?->can($spec[2]) ?? false))),
            ))];
        }

        return $tabs;
    }
}
