<?php

namespace App\Filament\Resources\CompanyProfileSubmissions\Pages;

use App\Filament\Resources\CompanyProfileSubmissions\CompanyProfileSubmissionResource;
use App\Models\CompanyProfileSubmission;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewCompanyProfileSubmission extends ViewRecord
{
    protected static string $resource = CompanyProfileSubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->label('Approve changes')
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->visible(fn (): bool => $this->record->status === CompanyProfileSubmission::STATUS_PENDING)
                ->form([
                    Textarea::make('review_note')
                        ->label('Review note')
                        ->rows(3),
                ])
                ->requiresConfirmation()
                ->action(function (array $data): void {
                    $emailSent = $this->record->approve($data['review_note'] ?? null);

                    $notification = Notification::make()
                        ->title('Company profile updated')
                        ->body($emailSent
                            ? 'The company contact has been emailed.'
                            : 'The email could not be sent. Check the mail settings before approving more submissions.');

                    ($emailSent ? $notification->success() : $notification->warning())->send();
                }),
            Action::make('reject')
                ->label('Reject')
                ->color('danger')
                ->icon('heroicon-o-x-circle')
                ->visible(fn (): bool => $this->record->status === CompanyProfileSubmission::STATUS_PENDING)
                ->form([
                    Textarea::make('review_note')
                        ->label('Reason or internal note')
                        ->rows(3),
                ])
                ->requiresConfirmation()
                ->action(function (array $data): void {
                    $emailSent = $this->record->reject($data['review_note'] ?? null);

                    $notification = Notification::make()
                        ->title('Submission rejected')
                        ->body($emailSent
                            ? 'The company contact has been emailed.'
                            : 'The email could not be sent. Check the mail settings before rejecting more submissions.');

                    ($emailSent ? $notification->success() : $notification->warning())->send();
                }),
        ];
    }
}
