<?php

namespace App\Filament\Widgets\Analytics;

use App\Filament\Resources\Companies\CompanyResource;
use App\Services\Analytics\ListCatalog;
use App\Services\Analytics\Scope;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use Livewire\Attributes\Locked;

/**
 * A list from the ListCatalog as a table over plain arrays (search, sort and paging happen on the aggregated rows,
 * never on a model query). Company names link to the existing company page for users who may open it.
 */
class ListWidget extends TableWidget
{
    use InteractsWithPageFilters;

    #[Locked]
    public string $list = 'participation';

    protected int|string|array $columnSpan = 'full';

    protected static bool $isLazy = true;

    /** A filter update can re-render this widget before Filament has set the per-page value; fall back to the table default instead of failing. */
    public function getTableRecordsPerPage(): int|string|null
    {
        return $this->tableRecordsPerPage ?? $this->getDefaultTableRecordsPerPageSelectOption();
    }

    public function table(Table $table): Table
    {
        $key = $this->list;
        $canOpen = (bool) auth()->user()?->can('companies.view');
        $name = TextColumn::make('name')->label('Bedrijf')->searchable()->sortable()
            ->url(fn (array $record): ?string => $canOpen && ! empty($record['company_id']) ? CompanyResource::getUrl('view', ['record' => $record['company_id']]) : null);

        return $table
            ->heading(ListCatalog::heading($key))
            ->records(fn (?string $search, ?string $sortColumn, ?string $sortDirection, int $page, int|string $recordsPerPage, ?array $filters): LengthAwarePaginator => $this->page($search, $sortColumn, $sortDirection, $page, (int) $recordsPerPage, $filters))
            ->columns(match ($key) {
                'participation' => [
                    $name,
                    TextColumn::make('first')->label('Eerste deelname'),
                    TextColumn::make('last')->label('Laatste deelname'),
                    TextColumn::make('total')->label('Edities')->numeric()->sortable(),
                    TextColumn::make('streak')->label('Huidige reeks')->numeric()->sortable()->tooltip('Aaneengesloten edities tot en met de geselecteerde editie'),
                    TextColumn::make('longest')->label('Langste reeks')->numeric()->sortable(),
                    TextColumn::make('previous_text')->label('Vorige editie')->badge()->color(fn (string $state): string => $state === 'Ja' ? 'success' : 'gray'),
                    TextColumn::make('selected_text')->label('Geselecteerde editie')->badge()->color(fn (string $state): string => $state === 'Ja' ? 'success' : 'gray'),
                    TextColumn::make('status')->label('Status')->badge()->color(fn (string $state): string => match ($state) {
                        'Nieuw' => 'info', 'Behouden' => 'success', 'Gereactiveerd' => 'warning', default => 'gray',
                    }),
                    TextColumn::make('symbols')->label('Tijdlijn')->fontFamily('mono')->tooltip(fn (array $record): string => $record['detail'])->extraAttributes(['style' => 'letter-spacing:.1em']),
                ],
                'flagged_stands' => [TextColumn::make('stand')->label('Stand')->sortable(), $name, TextColumn::make('reason')->label('Reden')->badge()->color('danger')],
                'free_partner_stands' => [TextColumn::make('stand')->label('Stand')->sortable()],
                'multi_stand' => [$name, TextColumn::make('stands')->label('Standnummers')],
                'promo_special' => [$name, TextColumn::make('items')->label('Meegebracht')->wrap()],
                default => [$name],
            })
            ->filters($key === 'participation' ? [SelectFilter::make('status')->label('Status')->options(['Nieuw' => 'Nieuw', 'Behouden' => 'Behouden', 'Gereactiveerd' => 'Gereactiveerd', 'Niet teruggekeerd' => 'Niet teruggekeerd'])] : [])
            ->recordActions($key === 'participation' ? [
                Action::make('timeline')->label('Tijdlijn')->icon('heroicon-o-clock')->modalSubmitAction(false)->modalCancelActionLabel('Sluiten')
                    ->modalHeading(fn (array $record): string => 'Deelname: '.$record['name'])
                    ->modalContent(fn (array $record): HtmlString => new HtmlString('<ul class="space-y-1 text-sm">'.collect(explode("\n", $record['detail']))->map(fn (string $line): string => '<li>'.e($line).'</li>')->implode('').'</ul>')),
            ] : [])
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(10)
            ->emptyStateHeading('Geen gegevens voor deze selectie')
            ->searchPlaceholder('Zoek bedrijf');
    }

    private function page(?string $search, ?string $sortColumn, ?string $sortDirection, int $page, int $perPage, ?array $filters): LengthAwarePaginator
    {
        $user = auth()->user();
        $rows = $user ? collect(app(ListCatalog::class)->rows($this->list, Scope::from($this->pageFilters), $user)) : collect();

        $rows = $rows
            ->when(filled($search), fn (Collection $c) => $c->filter(fn (array $r): bool => str_contains(mb_strtolower((string) ($r['name'] ?? $r['stand'] ?? '')), mb_strtolower((string) $search))))
            ->when(filled($filters['status']['value'] ?? null), fn (Collection $c) => $c->where('status', $filters['status']['value']))
            ->when($sortColumn, fn (Collection $c) => $c->sortBy($sortColumn, SORT_NATURAL | SORT_FLAG_CASE, $sortDirection === 'desc'))
            ->values();

        return new LengthAwarePaginator($rows->forPage($page, $perPage)->all(), $rows->count(), $perPage, $page);
    }
}
