<?php

namespace App\Filament\Resources\CompanyProfileSubmissions\Tables;

use App\Models\CompanyProfileSubmission;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CompanyProfileSubmissionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('submitted_at', 'desc')
            ->columns([
                TextColumn::make('company.name')
                    ->label('Company')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('proposed_name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        CompanyProfileSubmission::STATUS_APPROVED => 'success',
                        CompanyProfileSubmission::STATUS_REJECTED => 'danger',
                        default => 'warning',
                    })
                    ->sortable(),
                TextColumn::make('contact_email')
                    ->searchable(),
                TextColumn::make('submitted_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        CompanyProfileSubmission::STATUS_PENDING => 'Pending',
                        CompanyProfileSubmission::STATUS_APPROVED => 'Approved',
                        CompanyProfileSubmission::STATUS_REJECTED => 'Rejected',
                    ])
                    ->default(CompanyProfileSubmission::STATUS_PENDING),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('approve')
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->visible(fn (CompanyProfileSubmission $record): bool => $record->status === CompanyProfileSubmission::STATUS_PENDING)
                    ->modalHeading('Approve company profile changes')
                    ->modalSubmitActionLabel('Approve')
                    ->form([
                        Textarea::make('review_note')
                            ->label('Review note')
                            ->rows(3),
                    ])
                    ->requiresConfirmation()
                    ->action(function (CompanyProfileSubmission $record, array $data): void {
                        $record->approve($data['review_note'] ?? null);

                        Notification::make()
                            ->title('Company profile updated')
                            ->success()
                            ->send();
                    }),
                Action::make('reject')
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->visible(fn (CompanyProfileSubmission $record): bool => $record->status === CompanyProfileSubmission::STATUS_PENDING)
                    ->modalHeading('Reject company profile changes')
                    ->modalSubmitActionLabel('Reject')
                    ->form([
                        Textarea::make('review_note')
                            ->label('Reason or internal note')
                            ->rows(3),
                    ])
                    ->requiresConfirmation()
                    ->action(function (CompanyProfileSubmission $record, array $data): void {
                        $record->reject($data['review_note'] ?? null);

                        Notification::make()
                            ->title('Submission rejected')
                            ->success()
                            ->send();
                    }),
            ]);
    }
}
