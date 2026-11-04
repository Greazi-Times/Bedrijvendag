<?php

namespace App\Filament\Resources\CompanyAccessRequests;

use App\Models\CompanyAccessRequest;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

/** Reject modal and review feedback shared by the view page and the list table. */
class ReviewActions
{
    public const REASON_COMPANY_EXISTS = 'company_exists';

    public const COMPANY_EXISTS_MESSAGE = "Uw aanvraag om een nieuw bedrijf te registreren is afgewezen, omdat het opgegeven bedrijf al in ons systeem geregistreerd staat.\n\nAls u toegang wilt aanvragen tot dit bedrijfsprofiel, kunt u een nieuwe toegangsaanvraag indienen en uw bestaande bedrijf selecteren.\n\nKunt u uw bedrijf niet vinden of heeft u vragen? Neem dan contact met ons op via info@atixbedrijvendag.nl.";

    public static function configureReject(Action $action): Action
    {
        return $action
            ->authorize('review')
            ->color('danger')
            ->icon('heroicon-o-x-circle')
            ->modalHeading('Reject access request')
            ->modalSubmitActionLabel('Reject')
            ->form(fn (CompanyAccessRequest $record): array => [
                Select::make('reason')
                    ->label('Reason')
                    ->placeholder('General rejection')
                    ->options([self::REASON_COMPANY_EXISTS => 'Company already exists'])
                    ->visible(fn (): bool => $record->type === CompanyAccessRequest::TYPE_NEW)
                    ->live()
                    ->afterStateUpdated(function (?string $state, Get $get, Set $set): void {
                        $current = (string) $get('message');

                        if ($state === self::REASON_COMPANY_EXISTS) {
                            $set('message', self::COMPANY_EXISTS_MESSAGE);
                        } elseif ($current === self::COMPANY_EXISTS_MESSAGE) {
                            $set('message', null);
                        }
                    }),
                Textarea::make('message')
                    ->label('Message to the requester (optional, emailed when filled in)')
                    ->rows(8)
                    ->maxLength(2000),
                Textarea::make('review_note')
                    ->label('Internal note')
                    ->rows(3),
            ])
            ->requiresConfirmation();
    }

    /** @param array<string, mixed> $data */
    public static function reject(CompanyAccessRequest $record, array $data): void
    {
        $note = $data['review_note'] ?? null;

        if (blank($note) && ($data['reason'] ?? null) === self::REASON_COMPANY_EXISTS) {
            $note = 'Company already exists';
        }

        self::feedback($record, $record->reject($note, $data['message'] ?? null), 'rejected');
    }

    public static function feedback(CompanyAccessRequest $record, bool $done, string $verb): void
    {
        $record->refresh();

        if (! $done) {
            $pending = $record->status === CompanyAccessRequest::STATUS_PENDING;

            Notification::make()
                ->title($pending ? "Access request not {$verb}" : 'Already reviewed')
                ->body($pending ? 'The request could not be saved and is still pending. Check the logs and try again.' : 'This request was already handled by someone else.')
                ->{$pending ? 'danger' : 'warning'}()
                ->send();

            return;
        }

        $title = "Access request {$verb} successfully.";

        match ($record->notificationQueued) {
            true => Notification::make()->title($title)->body('The notification has been queued for delivery.')->success()->send(),
            false => Notification::make()->title($title)->body('The notification could not be queued. Contact the requester yourself; see the logs.')->warning()->send(),
            null => Notification::make()->title($title)->body('No notification was sent.')->success()->send(),
        };
    }
}
