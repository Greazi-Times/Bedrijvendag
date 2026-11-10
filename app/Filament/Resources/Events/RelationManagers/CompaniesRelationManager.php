<?php

namespace App\Filament\Resources\Events\RelationManagers;

use App\Models\Company;
use App\Services\StandEligibility;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class CompaniesRelationManager extends RelationManager
{
    protected static string $relationship = 'companies';

    public function getTableRecordKey($record): string
    {
        return (string) $record->stand_number;
    }

    public function table(Table $table): Table
    {
        return $table->relationship(null)->paginated(false)
            ->records(function (): Collection {
                $event = $this->getOwnerRecord();

                if (! $event->max_stands) {
                    return collect();
                }

                return collect(range(1, $event->max_stands))
                    ->map(function ($number) use ($event) {

                        $company = $event->companies()
                            ->wherePivot('stand_number', $number)
                            ->first();

                        $model = new Company;
                        $model->exists = false; // Prevent Filament from treating it as a real DB row

                        $model->forceFill([
                            'stand_number' => $number,
                            'company_id' => $company?->id,
                            'company_name' => $company?->name,
                        ]);

                        return $model;
                    });
            })
            ->columns([
                TextColumn::make('stand_number')
                    ->label('Stand'),

                TextColumn::make('company_name')
                    ->label('Company')
                    ->placeholder('Empty'),
            ])
            ->actions([
                Action::make('assign')
                    ->authorize(fn (): bool => auth()->user()->can('events.update'))
                    ->label('Assign / Change')
                    ->form(function ($record) {
                        $event = $this->getOwnerRecord();

                        // Get all company IDs already assigned to this event
                        $assignedCompanyIds = $event->companies()->pluck('companies.id')->toArray();

                        // If this stand already has a company, allow keeping it selectable
                        if ($record->company_id) {
                            $assignedCompanyIds = array_diff($assignedCompanyIds, [$record->company_id]);
                        }

                        return [
                            Select::make('company_id')
                                ->label('Company')
                                ->options(
                                    // Same rule as the Event Map: only companies with an approved enrollment for this edition.
                                    app(StandEligibility::class)->assignableCompanies($event)
                                        ->whereNotIn('companies.id', $assignedCompanyIds)
                                        ->orderBy('name')
                                        ->pluck('name', 'id')
                                )
                                ->searchable()
                                ->required(),
                        ];
                    })
                    ->action(function ($record, array $data) {
                        $event = $this->getOwnerRecord();

                        // attach() writes the pivot directly and skips model events, so the rule is enforced here.
                        try {
                            app(StandEligibility::class)->assertCompanyEligible($event, (int) $data['company_id']);
                        } catch (ValidationException $e) {
                            Notification::make()->danger()->title('Stand not assigned')->body(collect($e->errors())->flatten()->implode("\n"))->persistent()->send();

                            return;
                        }

                        // If a company is already assigned to this stand, detach it first
                        if ($record->company_id) {
                            $event->companies()->detach($record->company_id);
                        }

                        // Attach the new company to this stand
                        $event->companies()->attach(
                            $data['company_id'],
                            ['stand_number' => $record->stand_number]
                        );
                    }),

                Action::make('remove')
                    ->authorize(fn (): bool => auth()->user()->can('events.update'))
                    ->label('Remove')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => (bool) $record->company_id)
                    ->action(function ($record) {
                        $event = $this->getOwnerRecord();

                        if ($record->company_id) {
                            $event->companies()->detach($record->company_id);
                        }
                    }),
            ]);
    }
}
