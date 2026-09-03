<?php

namespace App\Filament\Resources\EventStands\Pages;

use App\Filament\Resources\EventStands\EventStandResource;
use App\Models\Company;
use App\Models\Event;
use App\Models\EventMapPoint;
use App\Models\EventStand;
use App\Models\Partner;
use App\Support\PageMedia;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Support\Enums\Size;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

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
        $this->selectedEventId = Event::query()
            ->orderByDesc('date')
            ->value('id');

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
            ])
            ->actions([
                Action::make('set_marker')
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

                        return [
                            Select::make('company_id')
                                ->label('Company')
                                ->options(fn (): array => Company::query()
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->all()
                                )
                                ->searchable()
                                ->preload()
                                ->default(fn (EventStand $record): ?int => $record->company_id)
                                ->getSearchResultsUsing(fn (string $search): array => Company::query()
                                    ->where('name', 'like', "%{$search}%")
                                    ->orderBy('name')
                                    ->limit(50)
                                    ->pluck('name', 'id')
                                    ->all()
                                )
                                ->getOptionLabelUsing(fn ($value): ?string => $value ? Company::query()->whereKey($value)->value('name') : null)
                                ->required(),
                        ];
                    })
                    ->action(function (array $data, EventStand $record) {
                        DB::transaction(function () use ($data, $record): void {
                            if ($record->type === 'partner') {
                                $partnerId = (int) $data['partner_id'];

                                EventStand::query()
                                    ->where('event_id', $this->selectedEventId)
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

                            EventStand::query()
                                ->where('event_id', $this->selectedEventId)
                                ->where('type', 'company')
                                ->where('company_id', $companyId)
                                ->where('id', '!=', $record->id)
                                ->update(['company_id' => null]);

                            $record->update([
                                'company_id' => $companyId,
                                'partner_id' => null,
                                'type' => 'company',
                            ]);
                        });

                        $this->resetTable();
                    }),

                Action::make('remove')
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

        if ($max === 0) {
            $query->delete();

            return;
        }

        $query
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
}
