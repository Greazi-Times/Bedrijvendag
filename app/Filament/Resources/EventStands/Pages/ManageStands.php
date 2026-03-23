<?php

namespace App\Filament\Resources\EventStands\Pages;

use App\Filament\Resources\EventStands\EventStandResource;
use App\Models\Company;
use App\Models\EventStand;
use App\Models\Event;
use App\Models\Partner;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Resources\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

class ManageStands extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static string $resource = EventStandResource::class;

    protected string $view = 'filament.resources.event-stands.pages.manage-stands';

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
        ];
    }

    public ?int $selectedEventId = null;

    public ?float $markerX = null;

    public ?float $markerY = null;

    public function mount(): void
    {
        if ($this->selectedEventId) {
            $this->syncStandRowsForSelectedEvent();
        }
    }

    public function updatedSelectedEventId($state): void
    {
        $this->selectedEventId = filled($state) ? (int) $state : null;

        if (! $this->selectedEventId) {
            $this->resetTable();
            return;
        }

        $this->syncStandRowsForSelectedEvent();
        $this->resetTable();
    }

    protected function getSelectedEvent(): ?Event
    {
        return $this->selectedEventId ? Event::find($this->selectedEventId) : null;
    }

    protected function getSelectedEventMapUrl(): ?string
    {
        $event = $this->getSelectedEvent();

        if (! $event || ! $event->map_path) {
            return null;
        }

        return Storage::url($event->map_path);
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

    public function clearMarkerForStand(int $eventStandId): void
    {
        if (! $this->selectedEventId) {
            return;
        }

        EventStand::query()
            ->whereKey($eventStandId)
            ->where('event_id', $this->selectedEventId)
            ->update([
                'x_percent' => null,
                'y_percent' => null,
            ]);

        Notification::make()
            ->title('Marker cleared')
            ->success()
            ->send();

        $this->resetTable();
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

                TextColumn::make('company.name')
                    ->label('Company')
                    ->default('—')
                    ->toggleable(),

                TextColumn::make('partner.name')
                    ->label('Partner')
                    ->default('—')
                    ->toggleable(),

                TextColumn::make('assigned_name')
                    ->label('Assigned to')
                    ->getStateUsing(fn (EventStand $record): string => $record->company?->name ?? $record->partner?->name ?? '—'),

                TextColumn::make('x_percent')
                    ->label('Marker')
                    ->formatStateUsing(function ($state, $record) {
                        return (is_numeric($record->x_percent) && is_numeric($record->y_percent))
                            ? 'Set'
                            : '—';
                    }),
            ])
            ->actions([
                Action::make('set_marker')
                    ->label('Set marker')
                    ->modalHeading('Set marker')
                    ->modalWidth('5xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->modalContent(function ($record) {
                        $url = $this->getSelectedEventMapUrl();

                        if (! $url) {
                            return new HtmlString('<div class="text-sm text-gray-600 dark:text-gray-300">No map uploaded for this event.</div>');
                        }

                        $id = (int) $record->id;
                        $livewireId = $this->getId();

                        $html = '
<div
  class="max-h-[95vh] overflow-y-auto pointer-events-auto"
  x-data=\'{
    lwId: ' . json_encode($livewireId) . ',
    standId: ' . $id . ',
    saved: false,
    x: null,
    y: null,
    click(e){
      const img = e.currentTarget;
      const r = img.getBoundingClientRect();
      const x = ((e.clientX - r.left) / r.width) * 100;
      const y = ((e.clientY - r.top) / r.height) * 100;
      this.x = Math.max(0, Math.min(100, Math.round(x * 100) / 100));
      this.y = Math.max(0, Math.min(100, Math.round(y * 100) / 100));
      this.saved = true;
      if (window.Livewire && window.Livewire.find) {
        const lw = window.Livewire.find(this.lwId);
        if (lw) {
          Promise.resolve(lw.call("setMarkerForStand", this.standId, this.x, this.y))
            .then(() => {
              // Close the Filament modal after the save finishes.
              this.$dispatch("close-modal");
            })
            .catch(() => {
              // If something fails, keep the modal open so the admin can try again.
            });
        }
      }
    }
  }\'
>
  <div class="text-sm text-gray-600 dark:text-gray-300">
    Click on the map to set the marker for stand ' . htmlspecialchars((string) $record->stand_number, ENT_QUOTES) . '.
  </div>

  <div
    x-show="saved"
    x-cloak
    class="rounded-xl border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-900 dark:border-green-900/40 dark:bg-green-900/20 dark:text-green-100"
  >
    Saved. X: <span x-text="x"></span>%, Y: <span x-text="y"></span>%
  </div>

  <div class="mx-auto inline-block overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700">
    <img
      src="' . htmlspecialchars($url, ENT_QUOTES) . '"
      alt="Event map"
      class="block h-auto w-auto max-h-[70vh] max-w-full cursor-crosshair select-none"
      draggable="false"
      @click.stop.prevent="click($event)"
    />
  </div>

  <div class="text-xs text-gray-500 dark:text-gray-400">You can click again to move the marker.</div>
</div>';

                        return new HtmlString($html);
                    }),

                Action::make('clear_marker')
                    ->label('Clear marker')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => is_numeric($record->x_percent) && is_numeric($record->y_percent))
                    ->action(function ($record) {
                        $this->clearMarkerForStand((int) $record->id);
                    }),
                Action::make('assign')
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
                    ->color('danger')
                    ->requiresConfirmation()
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
}
