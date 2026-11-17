<?php

namespace App\Filament\Resources\EventStands\Pages;

use App\Filament\Resources\Enrollments\EnrollmentResource;
use App\Filament\Resources\EventStands\EventStandResource;
use App\Models\Company;
use App\Models\Event;
use App\Models\EventMapPoint;
use App\Models\EventStand;
use App\Models\Partner;
use App\Services\StandEligibility;
use App\Support\PageMedia;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Enums\Size;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ManageStands extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static string $resource = EventStandResource::class;

    protected string $view = 'filament.resources.event-stands.pages.manage-stands';

    protected static ?string $title = 'Event Map';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('download_stands_pdf')
                ->label('Download stands PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('primary')
                ->visible(fn (): bool => filled($this->selectedEventId))
                ->url(fn (): string => route('stands-pdf', ['event' => $this->selectedEventId]))
                ->openUrlInNewTab(),

            Action::make('create_map_location')
                ->authorize('update')
                ->label('Add map location')
                ->icon('heroicon-o-plus')
                ->visible(fn (): bool => filled($this->selectedEventId))
                ->action(fn () => $this->openCreatePointEditor()),
        ];
    }

    public ?int $selectedEventId = null;

    public ?float $markerX = null;

    public ?float $markerY = null;

    public ?int $markerEditorStandId = null;

    public ?int $markerEditorPointId = null;

    public ?string $pointEditorMode = null;

    public ?int $pointEditorPointId = null;

    public array $pointData = [
        'type' => 'other',
    ];

    public function mount(): void
    {
        // Same default as the dashboard: today or the next upcoming edition, else the latest past one.
        $this->selectedEventId = app(\App\Services\Analytics\Analytics::class)->defaultEvent()?->id;

        if ($this->selectedEventId) {
            $this->syncStandRowsForSelectedEvent();
        }
    }

    public function updatedSelectedEventId($state): void
    {
        $this->selectedEventId = filled($state) ? (int) $state : null;

        if (! $this->selectedEventId) {
            $this->markerEditorStandId = null;
            $this->markerEditorPointId = null;
            $this->closePointEditor();
            $this->resetTable();

            return;
        }

        $this->syncStandRowsForSelectedEvent();
        $this->markerEditorStandId = null;
        $this->markerEditorPointId = null;
        $this->closePointEditor();
        $this->resetTable();
    }

    protected function getSelectedEvent(): ?Event
    {
        return $this->selectedEventId ? Event::find($this->selectedEventId) : null;
    }

    protected function getSelectedEventMapUrl(): ?string
    {
        $event = $this->getSelectedEvent();

        if (! $event) {
            return null;
        }

        return PageMedia::eventMapUrl($event->map_path) ?: null;
    }

    public function openMarkerEditorForStand(int $eventStandId): void
    {
        $this->authorizeUpdate();

        if (! $this->selectedEventId) {
            return;
        }

        $exists = EventStand::query()
            ->whereKey($eventStandId)
            ->where('event_id', $this->selectedEventId)
            ->exists();

        if (! $exists) {
            return;
        }

        $this->markerEditorStandId = $eventStandId;
    }

    public function closeMarkerEditor(): void
    {
        $this->markerEditorStandId = null;
    }

    public function closePointMarkerEditor(): void
    {
        $this->markerEditorPointId = null;
    }

    public function getMarkerEditorStand(): ?EventStand
    {
        if (! $this->selectedEventId || ! $this->markerEditorStandId) {
            return null;
        }

        return EventStand::query()
            ->with(['company', 'partner'])
            ->whereKey($this->markerEditorStandId)
            ->where('event_id', $this->selectedEventId)
            ->first();
    }

    public function getMarkerEditorMapUrl(): ?string
    {
        return $this->getSelectedEventMapUrl();
    }

    public function getMarkerEditorMarkers(): array
    {
        if (! $this->selectedEventId) {
            return [];
        }

        return EventStand::query()
            ->where('event_id', $this->selectedEventId)
            ->whereNotNull('x_percent')
            ->whereNotNull('y_percent')
            ->orderByRaw("CASE WHEN type = 'company' THEN 0 ELSE 1 END")
            ->orderByRaw('CAST(stand_number AS UNSIGNED)')
            ->get(['id', 'type', 'stand_number', 'x_percent', 'y_percent'])
            ->map(fn (EventStand $stand): array => [
                'id' => $stand->id,
                'code' => $this->formatStandCode($stand),
                'type' => $stand->type,
                'x' => (float) $stand->x_percent,
                'y' => (float) $stand->y_percent,
                'current' => $stand->id === $this->markerEditorStandId,
            ])
            ->values()
            ->all();
    }

    public function getPointMarkerEditorMarkers(): array
    {
        if (! $this->selectedEventId) {
            return [];
        }

        return EventMapPoint::query()
            ->where('event_id', $this->selectedEventId)
            ->whereNotNull('x_percent')
            ->whereNotNull('y_percent')
            ->orderBy('type')
            ->orderBy('id')
            ->get(['id', 'label', 'type', 'x_percent', 'y_percent'])
            ->map(fn (EventMapPoint $point): array => [
                'id' => $point->id,
                'key' => 'point-'.$point->id,
                'label' => $this->pointTypeOptions()[$point->type] ?? $point->label,
                'code' => $this->formatMapPointCode($point),
                'type' => $point->type,
                'x' => (float) $point->x_percent,
                'y' => (float) $point->y_percent,
                'current' => $point->id === $this->markerEditorPointId,
            ])
            ->values()
            ->all();
    }

    public function setMarkerForStand(int $eventStandId, float $xPercent, float $yPercent): void
    {
        $this->authorizeUpdate();

        if (! $this->selectedEventId) {
            return;
        }

        $xPercent = max(0, min(100, round($xPercent, 2)));
        $yPercent = max(0, min(100, round($yPercent, 2)));

        EventStand::query()
            ->whereKey($eventStandId)
            ->where('event_id', $this->selectedEventId)
            ->update([
                'x_percent' => $xPercent,
                'y_percent' => $yPercent,
            ]);

        $this->markerX = $xPercent;
        $this->markerY = $yPercent;

        Notification::make()
            ->title('Marker saved')
            ->success()
            ->send();

        $this->resetTable();
    }

    public function setMarkerForPoint(int $mapPointId, float $xPercent, float $yPercent): void
    {
        $this->authorizeUpdate();

        if (! $this->selectedEventId) {
            return;
        }

        $xPercent = max(0, min(100, round($xPercent, 2)));
        $yPercent = max(0, min(100, round($yPercent, 2)));

        EventMapPoint::query()
            ->whereKey($mapPointId)
            ->where('event_id', $this->selectedEventId)
            ->update([
                'x_percent' => $xPercent,
                'y_percent' => $yPercent,
            ]);

        Notification::make()
            ->title('Marker saved')
            ->success()
            ->send();
    }

    public function openCreatePointEditor(): void
    {
        $this->authorizeUpdate();

        if (! $this->selectedEventId) {
            return;
        }

        $this->pointEditorMode = 'create';
        $this->pointEditorPointId = null;
        $this->pointData = [
            'type' => 'other',
        ];
    }

    public function openEditPointEditor(int $mapPointId): void
    {
        $this->authorizeUpdate();

        if (! $this->selectedEventId) {
            return;
        }

        $point = EventMapPoint::query()
            ->whereKey($mapPointId)
            ->where('event_id', $this->selectedEventId)
            ->first();

        if (! $point) {
            return;
        }

        $this->pointEditorMode = 'edit';
        $this->pointEditorPointId = $point->id;
        $this->pointData = [
            'type' => $point->type,
        ];
    }

    public function savePointEditor(): void
    {
        $this->authorizeUpdate();

        if (! $this->selectedEventId || ! $this->pointEditorMode) {
            return;
        }

        $data = $this->validate([
            'pointData.type' => ['required', 'string', 'in:bar,info,lunch,entrance,other'],
        ])['pointData'];

        $data['label'] = $this->pointTypeOptions()[$data['type']] ?? 'Other';
        $data['description'] = null;
        $data['sort_order'] = 0;

        if ($this->pointEditorMode === 'create') {
            $data['event_id'] = $this->selectedEventId;
            EventMapPoint::create($data);
        } else {
            EventMapPoint::query()
                ->whereKey($this->pointEditorPointId)
                ->where('event_id', $this->selectedEventId)
                ->update($data);
        }

        Notification::make()
            ->title($this->pointEditorMode === 'create' ? 'Map location created' : 'Map location updated')
            ->success()
            ->send();

        $this->closePointEditor();
    }

    public function closePointEditor(): void
    {
        $this->pointEditorMode = null;
        $this->pointEditorPointId = null;
        $this->resetValidation();
    }

    public function openMarkerEditorForPoint(int $mapPointId): void
    {
        $this->authorizeUpdate();

        if (! $this->selectedEventId) {
            return;
        }

        $exists = EventMapPoint::query()
            ->whereKey($mapPointId)
            ->where('event_id', $this->selectedEventId)
            ->exists();

        if (! $exists) {
            return;
        }

        $this->markerEditorPointId = $mapPointId;
    }

    public function getMarkerEditorPoint(): ?EventMapPoint
    {
        if (! $this->selectedEventId || ! $this->markerEditorPointId) {
            return null;
        }

        return EventMapPoint::query()
            ->whereKey($this->markerEditorPointId)
            ->where('event_id', $this->selectedEventId)
            ->first();
    }

    public function deletePoint(int $mapPointId): void
    {
        $this->authorizeUpdate();

        if (! $this->selectedEventId) {
            return;
        }

        EventMapPoint::query()
            ->whereKey($mapPointId)
            ->where('event_id', $this->selectedEventId)
            ->delete();

        if ($this->markerEditorPointId === $mapPointId) {
            $this->markerEditorPointId = null;
        }

        Notification::make()
            ->title('Map location deleted')
            ->success()
            ->send();
    }

    public function getMapLocations()
    {
        if (! $this->selectedEventId) {
            return collect();
        }

        return EventMapPoint::query()
            ->where('event_id', $this->selectedEventId)
            ->orderBy('type')
            ->orderBy('id')
            ->get();
    }

    public function pointTypeOptions(): array
    {
        return [
            'bar' => 'Bar',
            'info' => 'Info',
            'lunch' => 'Lunch',
            'entrance' => 'Entrance',
            'other' => 'Other',
        ];
    }

    public function form(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return $schema
            ->schema([
                Select::make('selectedEventId')
                    ->label('Event')
                    ->options(Event::orderBy('date', 'desc')->pluck('name', 'id'))
                    ->live()
                    ->afterStateHydrated(function ($state): void {
                        $this->selectedEventId = filled($state) ? (int) $state : null;

                        if ($this->selectedEventId) {
                            $this->syncStandRowsForSelectedEvent();
                        }
                    })
                    ->afterStateUpdated(function ($state): void {
                        $this->updatedSelectedEventId($state);
                    }),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn () => $this->getStandQuery())
            ->columns([
                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->sortable(),

                TextColumn::make('stand_number')
                    ->label('Stand')
                    ->sortable(),

                TextColumn::make('assigned_name')
                    ->label('Assigned to')
                    ->getStateUsing(fn (EventStand $record): string => $record->company?->name ?? $record->partner?->name ?? '—'),

                TextColumn::make('eligibility')
                    ->label('Enrollment')
                    ->badge()
                    ->color('warning')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->getStateUsing(fn (EventStand $record): ?string => $this->standWarning($record)['short'] ?? null)
                    ->url(fn (EventStand $record): ?string => ($e = $this->standWarning($record)['enrollment'] ?? null) ? EnrollmentResource::getUrl('view', ['record' => $e]) : null)
                    ->tooltip(fn (EventStand $record): ?string => ($w = $this->standWarning($record)) ? $w['label'].'. The stand and its position are kept; approve the enrollment, or remove or reassign the stand.' : null),
            ])
            ->actions([
                Action::make('set_marker')
                    ->authorize('update')
                    ->label(fn (EventStand $record): string => is_numeric($record->x_percent) && is_numeric($record->y_percent)
                        ? 'Change marker'
                        : 'Set marker')
                    ->icon('heroicon-o-map-pin')
                    ->button()
                    ->size(Size::Small)
                    ->color('gray')
                    ->action(function (EventStand $record): void {
                        $this->openMarkerEditorForStand((int) $record->id);
                    }),

                Action::make('assign')
                    ->authorize('update')
                    ->label(fn (EventStand $record): string => $this->standHasAssignment($record) ? 'Reassign' : 'Assign')
                    ->button()
                    ->size(Size::Small)
                    ->color('primary')
                    ->form(function ($record): array {
                        if ($record->type === 'partner') {
                            return [
                                Select::make('partner_id')
                                    ->label('Partner')
                                    ->options(fn (): array => Partner::query()
                                        ->orderBy('name')
                                        ->pluck('name', 'id')
                                        ->all()
                                    )
                                    ->searchable()
                                    ->preload()
                                    ->default(fn (EventStand $record): ?int => $record->partner_id)
                                    ->getSearchResultsUsing(fn (string $search): array => Partner::query()
                                        ->where('name', 'like', "%{$search}%")
                                        ->orderBy('name')
                                        ->limit(50)
                                        ->pluck('name', 'id')
                                        ->all()
                                    )
                                    ->getOptionLabelUsing(fn ($value): ?string => $value ? Partner::query()->whereKey($value)->value('name') : null)
                                    ->required(),
                            ];
                        }

                        $event = Event::query()->findOrFail($record->event_id); // the stand's own edition, never the page property
                        $eligibility = app(StandEligibility::class);
                        $canOverride = $eligibility->canOverride($event, auth()->user());
                        $current = $record->company_id;
                        // Grouped so a search term can never widen the list: (eligible OR current company) AND name LIKE ...
                        $scope = fn (bool $override) => Company::query()->where(fn ($w) => $w
                            ->whereIn('companies.id', $eligibility->assignableCompanies($event, $override)->select('companies.id'))
                            ->when($current, fn ($w) => $w->orWhere('companies.id', $current)));

                        return [
                            Placeholder::make('override_info')
                                ->hiddenLabel()
                                ->visible($canOverride)
                                ->content('Historische editie: inschrijvingscontrole overschrijven. Hiermee kan een bedrijf zonder goedgekeurde inschrijving op een stand worden gezet. Er wordt geen inschrijving aangemaakt of gewijzigd; de actie wordt vastgelegd in het logboek.'),
                            Toggle::make('historical_override')
                                ->label('Historische editie: inschrijvingscontrole overschrijven')
                                ->visible($canOverride)
                                ->live()
                                ->default(false),
                            Select::make('company_id')
                                ->label('Company')
                                ->helperText(fn (Get $get): string => $canOverride && $get('historical_override') ? 'Override active: every company can be selected.' : 'Only companies with an approved enrollment for this edition can be given a stand.')
                                ->options(fn (Get $get): array => $scope($canOverride && (bool) $get('historical_override'))->orderBy('name')->pluck('name', 'id')->all())
                                ->searchable()
                                ->preload()
                                ->default(fn (EventStand $record): ?int => $record->company_id)
                                ->getSearchResultsUsing(fn (string $search, Get $get): array => $scope($canOverride && (bool) $get('historical_override'))
                                    ->where('companies.name', 'like', '%'.addcslashes($search, '%_\\').'%')
                                    ->orderBy('name')
                                    ->limit(50)
                                    ->pluck('name', 'id')
                                    ->all()
                                )
                                ->getOptionLabelUsing(fn ($value): ?string => $value ? Company::query()->whereKey($value)->value('name') : null)
                                // The dropdown is not trusted: a hand-crafted request is checked again on the server.
                                ->rule(fn (Get $get): \Closure => function (string $attribute, $value, \Closure $fail) use ($eligibility, $event, $current, $canOverride, $get): void {
                                    $overriding = $canOverride && $get('historical_override') && $get('override_confirm') && filled($get('override_reason'));

                                    if ((int) $value !== (int) $current && ! $eligibility->isEligible($event, (int) $value) && ! $overriding) {
                                        $fail('This company has no approved enrollment for this edition.');
                                    }
                                })
                                ->required(),
                            Textarea::make('override_reason')
                                ->label('Reden voor overschrijven')
                                ->maxLength(500)
                                ->visible(fn (Get $get): bool => $canOverride && (bool) $get('historical_override'))
                                ->required(fn (Get $get): bool => $canOverride && (bool) $get('historical_override')),
                            Checkbox::make('override_confirm')
                                ->label('Ik begrijp dat dit de inschrijvingseis voor deze historische editie omzeilt')
                                ->visible(fn (Get $get): bool => $canOverride && (bool) $get('historical_override'))
                                ->accepted(fn (Get $get): bool => $canOverride && (bool) $get('historical_override')),
                        ];
                    })
                    ->action(function (array $data, EventStand $record) {
                        try {
                            DB::transaction(function () use ($data, $record): void {
                                if ($record->type === 'partner') {
                                    $partnerId = (int) $data['partner_id'];

                                    EventStand::query()
                                        ->where('event_id', $record->event_id)
                                        ->where('type', 'partner')
                                        ->where('partner_id', $partnerId)
                                        ->where('id', '!=', $record->id)
                                        ->update(['partner_id' => null]);

                                    $record->update([
                                        'company_id' => null,
                                        'partner_id' => $partnerId,
                                        'type' => 'partner',
                                    ]);

                                    return;
                                }

                                $companyId = (int) $data['company_id'];
                                $previousStand = EventStand::query()
                                    ->where('event_id', $record->event_id)->where('type', 'company')
                                    ->where('company_id', $companyId)->where('id', '!=', $record->id)
                                    ->value('stand_number');

                                EventStand::query()
                                    ->where('event_id', $record->event_id)
                                    ->where('type', 'company')
                                    ->where('company_id', $companyId)
                                    ->where('id', '!=', $record->id)
                                    ->update(['company_id' => null]);

                                // The override is requested explicitly; the server re-checks Super Admin + historical + reason.
                                if (! empty($data['historical_override']) && ! empty($data['override_confirm'])) {
                                    $record->assignWithHistoricalOverride($companyId, (string) ($data['override_reason'] ?? ''), $previousStand);

                                    return;
                                }

                                $record->update([
                                    'company_id' => $companyId,
                                    'partner_id' => null,
                                    'type' => 'company',
                                ]);
                            });
                        } catch (ValidationException $e) {
                            // Rolled back as a whole, including the removal from the previous stand.
                            Notification::make()->danger()->title('Stand not assigned')->body(collect($e->errors())->flatten()->implode("\n"))->persistent()->send();

                            return;
                        }

                        $this->resetTable();
                    }),

                Action::make('remove')
                    ->authorize('update')
                    ->label('Remove assignment')
                    ->icon('heroicon-o-trash')
                    ->button()
                    ->size(Size::Small)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (EventStand $record): bool => $this->standHasAssignment($record))
                    ->action(function (EventStand $record) {
                        DB::transaction(function () use ($record): void {
                            $record->update([
                                'company_id' => null,
                                'partner_id' => null,
                            ]);
                        });

                        $this->resetTable();
                    }),
            ]);
    }

    /** @var \Illuminate\Support\Collection<int, EventStand>|null computed once per request, not once per table row */
    private ?\Illuminate\Support\Collection $flaggedStands = null;

    /** @var array<int, array{label: string, short: string, enrollment: ?\App\Models\Enrollment}> */
    private array $standWarnings = [];

    /** @return \Illuminate\Support\Collection<int, EventStand> company stands of an active or future edition without an approved enrollment */
    public function getFlaggedStands(): \Illuminate\Support\Collection
    {
        if ($this->flaggedStands === null) {
            $eligibility = app(StandEligibility::class);
            $event = $this->getSelectedEvent();
            $this->flaggedStands = $event ? $eligibility->flaggedStands($event) : collect();
            $this->standWarnings = $this->flaggedStands->mapWithKeys(fn (EventStand $s): array => [$s->id => $eligibility->explain($s)])->all();
        }

        return $this->flaggedStands;
    }

    /** Super Admin looking at a past edition: the override is available. */
    public function historicalOverrideAvailable(): bool
    {
        $event = $this->getSelectedEvent();

        return $event !== null && app(StandEligibility::class)->canOverride($event, auth()->user());
    }

    public function getStandExplanation(EventStand $stand): string
    {
        return $this->standWarning($stand)['label'] ?? '';
    }

    /** Non-blocking: set for flagged stands only. */
    protected function standWarning(EventStand $stand): ?array
    {
        $this->getFlaggedStands();

        return $this->standWarnings[$stand->id] ?? null;
    }

    protected function getStandQuery(): Builder
    {
        if (! $this->selectedEventId) {
            return EventStand::query()->whereRaw('1 = 0');
        }

        return EventStand::query()
            ->with(['company', 'partner'])
            ->where('event_id', $this->selectedEventId)
            ->orderByRaw("CASE WHEN type = 'company' THEN 0 ELSE 1 END")
            ->orderByRaw('CAST(stand_number AS UNSIGNED)')
            ->orderBy('stand_number');
    }

    protected function syncStandRowsForSelectedEvent(): void
    {
        if (! $this->selectedEventId) {
            return;
        }

        $event = Event::find($this->selectedEventId);

        if (! $event) {
            return;
        }

        $this->syncCompanyStandRows((int) ($event->max_stands ?? 0));
        $this->syncPartnerStandRows((int) ($event->partner_stand_count ?? 0));
    }

    protected function syncCompanyStandRows(int $max): void
    {
        $this->syncStandRowsByType('company', $max);
    }

    protected function syncPartnerStandRows(int $max): void
    {
        $this->syncStandRowsByType('partner', $max);
    }

    protected function syncStandRowsByType(string $type, int $max): void
    {
        $max = max(0, $max);

        $query = EventStand::query()
            ->where('event_id', $this->selectedEventId)
            ->where('type', $type);

        // Only EMPTY stands above the maximum are removed; an occupied stand is never deleted here.
        // (Event::updating already refuses to lower the count past an occupied stand; this covers bulk changes.)
        $empty = fn ($q) => $q->whereNull('company_id')->whereNull('partner_id');

        if ($max === 0) {
            $empty($query)->delete();

            return;
        }

        $empty($query)
            ->whereRaw('CAST(stand_number AS UNSIGNED) > ?', [$max])
            ->delete();

        $existing = EventStand::query()
            ->where('event_id', $this->selectedEventId)
            ->where('type', $type)
            ->pluck('stand_number')
            ->map(fn ($standNumber) => (string) ((int) $standNumber))
            ->all();

        $existing = array_flip($existing);

        $chunk = [];
        $chunkSize = 500;

        for ($i = 1; $i <= $max; $i++) {
            $standNumber = (string) $i;

            if (isset($existing[$standNumber])) {
                continue;
            }

            $chunk[] = [
                'event_id' => $this->selectedEventId,
                'type' => $type,
                'stand_number' => $standNumber,
                'company_id' => null,
                'partner_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (count($chunk) >= $chunkSize) {
                EventStand::query()->upsert(
                    $chunk,
                    ['event_id', 'type', 'stand_number'],
                    ['updated_at']
                );
                $chunk = [];
            }
        }

        if ($chunk !== []) {
            EventStand::query()->upsert(
                $chunk,
                ['event_id', 'type', 'stand_number'],
                ['updated_at']
            );
        }
    }

    private function formatStandCode(EventStand $stand): string
    {
        if ($stand->type === 'partner') {
            return 'P'.preg_replace('/^P/i', '', (string) $stand->stand_number);
        }

        return (string) $stand->stand_number;
    }

    private function standHasAssignment(EventStand $stand): bool
    {
        return filled($stand->company_id) || filled($stand->partner_id);
    }

    private function formatMapPointCode(EventMapPoint $point): string
    {
        return match ($point->type) {
            'bar' => 'B',
            'info' => 'i',
            'lunch' => 'L',
            'entrance' => 'E',
            default => strtoupper(substr($point->label, 0, 1)),
        };
    }

    /** Viewing the page needs event-stands.view; every change needs event-stands.update. */
    private function authorizeUpdate(): void
    {
        abort_unless(auth()->user()?->can('event-stands.update'), 403);
    }
}
