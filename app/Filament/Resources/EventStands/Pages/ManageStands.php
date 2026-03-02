<?php

namespace App\Filament\Resources\EventStands\Pages;

use App\Filament\Resources\EventStands\EventStandResource;
use App\Models\Company;
use App\Models\CompanyEvent;
use App\Models\Event;
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

    public ?int $selectedEventId = null;

    public ?float $markerX = null;

    public ?float $markerY = null;

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

    public function setMarkerForStand(int $companyEventId, float $xPercent, float $yPercent): void
    {
        if (! $this->selectedEventId) {
            return;
        }

        $xPercent = max(0, min(100, round($xPercent, 2)));
        $yPercent = max(0, min(100, round($yPercent, 2)));

        CompanyEvent::query()
            ->whereKey($companyEventId)
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

    public function clearMarkerForStand(int $companyEventId): void
    {
        if (! $this->selectedEventId) {
            return;
        }

        CompanyEvent::query()
            ->whereKey($companyEventId)
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
                    ->afterStateUpdated(function (?int $state): void {
                        $this->selectedEventId = $state;
                        $this->syncStandRowsForSelectedEvent();
                        $this->resetTable();
                    }),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn () => $this->getStandQuery())
            ->columns([
                TextColumn::make('stand_number')
                    ->label('Stand')
                    ->sortable(),

                TextColumn::make('company.name')
                    ->label('Company')
                    ->default('—'),

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

  <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700">
    <img
      src="' . htmlspecialchars($url, ENT_QUOTES) . '"
      alt="Event map"
      class="block w-full max-h-[70vh] object-contain cursor-crosshair select-none"
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
                    ->form([
                        Select::make('company_id')
                            ->label('Company')
                            ->searchable()
                            ->getSearchResultsUsing(fn (string $search): array => Company::query()
                                ->where('name', 'like', "%{$search}%")
                                ->orderBy('name')
                                ->limit(50)
                                ->pluck('name', 'id')
                                ->all()
                            )
                            ->getOptionLabelUsing(fn ($value): ?string => $value ? Company::query()->whereKey($value)->value('name') : null
                            )
                            ->required(),
                    ])
                    ->action(function (array $data, $record) {
                        $companyId = (int) $data['company_id'];

                        // If the company is already assigned in this event, move it to this stand.
                        // This respects your unique index (event_id, company_id).
                        DB::transaction(function () use ($companyId, $record): void {
                            CompanyEvent::query()
                                ->where('event_id', $this->selectedEventId)
                                ->where('company_id', $companyId)
                                ->where('id', '!=', $record->id)
                                ->update(['company_id' => null]);

                            $record->update([
                                'company_id' => $companyId,
                            ]);
                        });

                        $this->resetTable();
                    }),

                Action::make('remove')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        DB::transaction(function () use ($record): void {
                            $record->update([
                                'company_id' => null,
                            ]);
                        });

                        $this->resetTable();
                    }),
            ]);
    }

    protected function getStandQuery(): Builder
    {
        if (! $this->selectedEventId) {
            return CompanyEvent::query()->whereRaw('1 = 0');
        }

        return CompanyEvent::query()
            ->where('event_id', $this->selectedEventId)
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

        $max = (int) $event->max_stands;

        if ($max < 1) {
            return;
        }

        // Delete stands that are now out of range.
        CompanyEvent::query()
            ->where('event_id', $this->selectedEventId)
            ->where('stand_number', '>', $max)
            ->delete();
        // Create missing stand rows (company_id stays null until assigned).
        // We only need the existing stand numbers once.
        $existing = CompanyEvent::query()
            ->where('event_id', $this->selectedEventId)
            ->pluck('stand_number')
            ->all();

        $existing = array_flip($existing);

        $chunk = [];
        $chunkSize = 500;

        for ($i = 1; $i <= $max; $i++) {
            if (isset($existing[$i])) {
                continue;
            }

            $chunk[] = [
                'event_id' => $this->selectedEventId,
                'stand_number' => $i,
                'company_id' => null,
            ];

            if (count($chunk) >= $chunkSize) {
                CompanyEvent::query()->insertOrIgnore($chunk);
                $chunk = [];
            }
        }

        if ($chunk !== []) {
            CompanyEvent::query()->insertOrIgnore($chunk);
        }
    }
}
