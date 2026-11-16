<?php

namespace App\Filament\Widgets\Analytics;

use App\Filament\Resources\Companies\CompanyResource;
use App\Services\Analytics\ListCatalog;
use App\Services\Analytics\Participation;
use App\Services\Analytics\Scope;
use Filament\Actions\Action;
use Filament\Tables\Columns\IconColumn;
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
                'matrix' => [
                    $name,
                    ...array_map(fn (array $e): IconColumn => IconColumn::make('e'.$e['id'])->label($e['name'])->boolean()
                        ->trueIcon('heroicon-s-check-circle')->falseIcon('heroicon-o-minus')->trueColor('success')->falseColor('gray')
                        ->tooltip(fn (array $record): string => $e['name'].': '.($record['e'.$e['id']] ? 'deelgenomen' : 'niet deelgenomen')), $this->matrixEditions()),
                    TextColumn::make('total')->label('Edities')->numeric()->sortable(),
                    TextColumn::make('pattern')->label('Patroon')->badge()->sortable()->color(fn (string $state): string => match ($state) {
                        'Elke editie', 'Doorlopend' => 'success', 'Eerste deelname' => 'info', 'Onderbroken' => 'warning', default => 'gray',
                    }),
                ],
                'flagged_stands' => [TextColumn::make('stand')->label('Stand')->sortable(), $name, TextColumn::make('reason')->label('Reden')->badge()->color('danger')],
                'free_partner_stands' => [TextColumn::make('stand')->label('Stand')->sortable()],
                'multi_stand' => [$name, TextColumn::make('stands')->label('Standnummers')],
                'promo_special' => [$name, TextColumn::make('items')->label('Meegebracht')->wrap()],
                default => [$name],
            })
            ->filters(match ($key) {
                'participation' => [SelectFilter::make('status')->label('Status')->options(['Nieuw' => 'Nieuw', 'Behouden' => 'Behouden', 'Gereactiveerd' => 'Gereactiveerd', 'Niet teruggekeerd' => 'Niet teruggekeerd'])],
                'matrix' => [SelectFilter::make('pattern')->label('Patroon')->options(array_combine($p = ['Elke editie', 'Doorlopend', 'Onderbroken', 'Eerste deelname', 'Gestopt'], $p))],
                default => [],
            })
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
            ->when(filled($filters['pattern']['value'] ?? null), fn (Collection $c) => $c->where('pattern', $filters['pattern']['value']))
            ->when($sortColumn, fn (Collection $c) => $c->sortBy($sortColumn, SORT_NATURAL | SORT_FLAG_CASE, $sortDirection === 'desc'))
            ->values();

        return new LengthAwarePaginator($rows->forPage($page, $perPage)->all(), $rows->count(), $perPage, $page);
    }

    /** Editions up to and including the selected one: the columns of the participation matrix. @return list<array{id: int, name: string}> */
    private function matrixEditions(): array
    {
        $event = Scope::from($this->pageFilters)->event;
        $participation = app(Participation::class);
        $upTo = $event ? $participation->index($event->id) : null;

        return $upTo === null ? [] : array_map(fn (array $e): array => ['id' => $e['id'], 'name' => $e['name']], array_slice($participation->editions(), 0, $upTo + 1));
    }
}
