<?php

namespace App\Filament\Resources\CompanyAccessRequests\Tables;

use App\Filament\Resources\CompanyAccessRequests\ReviewActions;
use App\Models\CompanyAccessRequest;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;

class CompanyAccessRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('submitted_at', 'desc')
            ->columns([
                TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => $state === CompanyAccessRequest::TYPE_NEW ? 'New company' : 'Existing company')
                    ->color(fn (string $state): string => $state === CompanyAccessRequest::TYPE_NEW ? 'info' : 'gray')
                    ->sortable(),
                TextColumn::make('company_name')
                    ->label('Company')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('company.name')
                    ->label('Matched company')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('contact_email')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        CompanyAccessRequest::STATUS_APPROVED => 'success',
                        CompanyAccessRequest::STATUS_REJECTED => 'danger',
                        default => 'warning',
                    })
                    ->sortable(),
                TextColumn::make('submitted_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        CompanyAccessRequest::STATUS_PENDING => 'Pending',
                        CompanyAccessRequest::STATUS_APPROVED => 'Approved',
                        CompanyAccessRequest::STATUS_REJECTED => 'Rejected',
                    ])
                    ->default(CompanyAccessRequest::STATUS_PENDING),
                SelectFilter::make('type')
                    ->options([
                        CompanyAccessRequest::TYPE_EXISTING => 'Existing company',
                        CompanyAccessRequest::TYPE_NEW => 'New company',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('approve')
                    ->authorize('review')
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->visible(fn (CompanyAccessRequest $record): bool => $record->status === CompanyAccessRequest::STATUS_PENDING)
                    ->modalHeading('Approve access request')
                    ->modalSubmitActionLabel('Approve')
                    ->form([
                        Textarea::make('review_note')
                            ->label('Review note')
                            ->rows(3),
                    ])
                    ->requiresConfirmation()
                    ->action(function (CompanyAccessRequest $record, array $data): void {
                        try {
                            $approved = $record->approve($data['review_note'] ?? null);
                        } catch (ValidationException $exception) {
                            Notification::make()->title('Approval blocked')->body(collect($exception->errors())->flatten()->implode(' '))->danger()->send();

                            return;
                        }

                        ReviewActions::feedback($record, $approved, 'approved');
                    }),
                ReviewActions::configureReject(Action::make('reject'))
                    ->visible(fn (CompanyAccessRequest $record): bool => $record->status === CompanyAccessRequest::STATUS_PENDING)
                    ->action(fn (CompanyAccessRequest $record, array $data) => ReviewActions::reject($record, $data)),
            ]);
    }
}
