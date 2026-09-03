<?php

namespace App\Filament\Resources\CompanyAccessRequests\Pages;

use App\Filament\Resources\CompanyAccessRequests\CompanyAccessRequestResource;
use App\Models\CompanyAccessRequest;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewCompanyAccessRequest extends ViewRecord
{
    protected static string $resource = CompanyAccessRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->label('Approve')
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->visible(fn (): bool => $this->record->status === CompanyAccessRequest::STATUS_PENDING)
                ->form([
                    Textarea::make('review_note')
                        ->label('Review note')
                        ->rows(3),
                ])
                ->requiresConfirmation()
                ->action(function (array $data): void {
                    $emailSent = $this->record->approve($data['review_note'] ?? null);

                    if ($emailSent) {
                        Notification::make()
                            ->title('Access request approved')
                            ->body('The requester has been emailed the private verification link.')
                            ->success()
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->title('Access request approved')
                        ->body('The email could not be sent. Check the mail settings before approving more requests.')
                        ->warning()
                        ->send();
                }),
            Action::make('reject')
                ->label('Reject')
                ->color('danger')
                ->icon('heroicon-o-x-circle')
                ->visible(fn (): bool => $this->record->status === CompanyAccessRequest::STATUS_PENDING)
                ->form([
                    Textarea::make('review_note')
                        ->label('Reason or internal note')
                        ->rows(3),
                ])
                ->requiresConfirmation()
                ->action(function (array $data): void {
                    $this->record->reject($data['review_note'] ?? null);

                    Notification::make()
                        ->title('Access request rejected')
                        ->success()
                        ->send();
                }),
        ];
    }
}
