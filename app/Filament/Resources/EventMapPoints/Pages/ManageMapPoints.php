<?php

namespace App\Filament\Resources\EventMapPoints\Pages;

use App\Filament\Resources\EventMapPoints\EventMapPointResource;
use App\Models\Event;
use App\Models\EventMapPoint;
use App\Support\PageMedia;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ManageMapPoints extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static string $resource = EventMapPointResource::class;

    protected string $view = 'filament.resources.event-map-points.pages.manage-map-points';

    public ?int $selectedEventId = null;

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
    }

    public function updatedSelectedEventId($state): void
    {
        $this->selectedEventId = filled($state) ? (int) $state : null;
        $this->markerEditorPointId = null;
        $this->resetTable();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create')
                ->label('Add point')
                ->icon('heroicon-o-plus')
                ->visible(fn (): bool => filled($this->selectedEventId))
                ->action(function (): void {
                    $this->openCreatePointEditor();
                }),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => $this->getPointQuery()
                ->orderBy('type')
                ->orderBy('id'))
            ->columns([
                TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => $this->pointTypeOptions()[$state] ?? ucfirst($state))
                    ->sortable(),

                TextColumn::make('marker')
                    ->label('Marker')
                    ->getStateUsing(fn (EventMapPoint $record): string => is_numeric($record->x_percent) && is_numeric($record->y_percent) ? 'Set' : '-'),

            ])
            ->actions([
                Action::make('set_marker')
                    ->label('Set marker')
                    ->icon('heroicon-o-map-pin')
                    ->action(function (EventMapPoint $record): void {
                        $this->openMarkerEditorForPoint((int) $record->id);
                    }),

                Action::make('edit')
                    ->label('Edit')
                    ->icon('heroicon-o-pencil-square')
                    ->action(function (EventMapPoint $record): void {
                        $this->openEditPointEditor((int) $record->id);
                    }),

                Action::make('clear_marker')
                    ->label('Clear marker')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (EventMapPoint $record): bool => is_numeric($record->x_percent) && is_numeric($record->y_percent))
                    ->action(function (EventMapPoint $record): void {
                        $record->update([
                            'x_percent' => null,
                            'y_percent' => null,
                        ]);

                        Notification::make()
                            ->title('Marker cleared')
                            ->success()
                            ->send();

                        $this->resetTable();
                    }),

                Action::make('delete')
                    ->label('Delete')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (EventMapPoint $record): void {
                        $record->delete();

                        if ($this->markerEditorPointId === $record->id) {
                            $this->markerEditorPointId = null;
                        }

                        Notification::make()
                            ->title('Map point deleted')
                            ->success()
                            ->send();

                        $this->resetTable();
                    }),
            ]);
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

        $this->resetTable();
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
            ->title($this->pointEditorMode === 'create' ? 'Map point created' : 'Map point updated')
            ->success()
            ->send();

        $this->closePointEditor();
        $this->resetTable();
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

    public function closeMarkerEditor(): void
    {
        $this->markerEditorPointId = null;
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

    public function getMarkerEditorMapUrl(): ?string
    {
        return PageMedia::eventMapUrl() ?: null;
    }

    public function getMarkerEditorMarkers(): array
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
                        $this->selectedEventId = filled($state) ? (int) $state : $this->selectedEventId;
                    })
                    ->afterStateUpdated(function ($state): void {
                        $this->updatedSelectedEventId($state);
                    }),
            ]);
    }

    protected function getPointQuery(): Builder
    {
        if (! $this->selectedEventId) {
            return EventMapPoint::query()->whereRaw('1 = 0');
        }

        return EventMapPoint::query()
            ->where('event_id', $this->selectedEventId);
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
