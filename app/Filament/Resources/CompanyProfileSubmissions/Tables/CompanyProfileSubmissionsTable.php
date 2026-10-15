<?php

namespace App\Filament\Resources\CompanyProfileSubmissions\Tables;

use App\Filament\Resources\CompanyProfileSubmissions\CompanyProfileSubmissionResource;
use App\Models\CompanyProfileSubmission;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * The review queue. A company has at most one pending submission, so the
 * default "Pending review" filter lists every company that needs action once.
 * Earlier updates are superseded rows: reachable through the history, and
 * through the "Superseded" filter, but never shown as separate pending items.
 */
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
                    ->label('Proposed name')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('submitted_at')
                    ->label('Last updated')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => CompanyProfileSubmission::STATUS_LABELS[$state] ?? ucfirst($state))
                    ->color(fn (string $state): string => CompanyProfileSubmission::STATUS_COLORS[$state] ?? 'gray')
                    ->sortable(),
                TextColumn::make('revision')
                    ->label('Updates')
                    ->tooltip('Number of times the company sent changes in this review')
                    ->numeric()
                    ->alignCenter()
                    ->sortable(),
                TextColumn::make('contact_email')
                    ->searchable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        CompanyProfileSubmission::STATUS_PENDING => 'Pending review',
                        CompanyProfileSubmission::STATUS_APPROVED => 'Approved',
                        CompanyProfileSubmission::STATUS_REJECTED => 'Rejected',
                        CompanyProfileSubmission::STATUS_SUPERSEDED => 'Superseded (earlier updates)',
                    ])
                    ->default(CompanyProfileSubmission::STATUS_PENDING),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label(fn (CompanyProfileSubmission $record): string => $record->isPending() ? 'Review submission' : 'View'),
                Action::make('history')
                    ->label('View history')
                    ->icon('heroicon-o-clock')
                    ->color('gray')
                    ->modalHeading(fn (CompanyProfileSubmission $record): string => "Submission history: {$record->company?->name}")
                    ->modalContent(fn (CompanyProfileSubmission $record) => view('filament.resources.company-profile-submissions.history', [
                        'current' => $record,
                        'submissions' => $record->history(),
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->slideOver(),
                ActionGroup::make([
                    Action::make('approve')
                        ->authorize('review')
                        ->color('success')
                        ->icon('heroicon-o-check-circle')
                        ->visible(fn (CompanyProfileSubmission $record): bool => $record->isPending())
                        ->modalHeading('Approve company profile changes')
                        ->modalDescription('The latest version of this submission replaces the published company profile.')
                        ->modalSubmitActionLabel('Approve')
                        ->form([
                            Textarea::make('review_note')
                                ->label('Review note')
                                ->rows(3),
                        ])
                        ->requiresConfirmation()
                        ->action(fn (CompanyProfileSubmission $record, array $data) => CompanyProfileSubmissionResource::review($record, 'approve', $data['review_note'] ?? null)),
                    Action::make('reject')
                        ->authorize('review')
                        ->color('danger')
                        ->icon('heroicon-o-x-circle')
                        ->visible(fn (CompanyProfileSubmission $record): bool => $record->isPending())
                        ->modalHeading('Reject company profile changes')
                        ->modalSubmitActionLabel('Reject')
                        ->form([
                            Textarea::make('review_note')
                                ->label('Reason or internal note')
                                ->rows(3),
                        ])
                        ->requiresConfirmation()
                        ->action(fn (CompanyProfileSubmission $record, array $data) => CompanyProfileSubmissionResource::review($record, 'reject', $data['review_note'] ?? null)),
                ])->visible(fn (CompanyProfileSubmission $record): bool => $record->isPending()),
            ]);
    }
}
