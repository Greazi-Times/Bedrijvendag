<?php

namespace App\Filament\Resources\Enrollments\Pages;

use App\Filament\Resources\Enrollments\EnrollmentResource;
use App\Models\Company;
use App\Models\Enrollment;
use App\Services\EnrollmentWorkflow;
use App\Services\InvitationService;
use App\Support\EnrollmentPresenter;
use Filament\Actions\Action;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Validation\ValidationException;

class ViewEnrollment extends ViewRecord
{
    protected static string $resource = EnrollmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->label('Approve')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->authorize('review')
                ->visible(fn (Enrollment $record): bool => $record->status === Enrollment::STATUS_PENDING)
                ->modalHeading('Approve enrollment')
                ->modalDescription('Choose which company this enrollment belongs to. Existing company details are never overwritten; differences go to the profile review queue. The company receives the link to the information form.')
                ->form(function (Enrollment $record): array {
                    $exact = EnrollmentPresenter::matches($record)['exact'];
                    $default = $record->selected_company_id ?? $exact->first()?->id;

                    return [
                        Radio::make('mode')->label('Company')->options(['existing' => 'Link to an existing company', 'new' => 'Create a new company'])
                            ->default($default ? 'existing' : 'new')->required()->live(),
                        Select::make('company_id')->label('Existing company')->searchable()
                            ->options(fn (): array => Company::query()->orderBy('name')->pluck('name', 'id')->all())
                            ->default($default)
                            ->required(fn ($get): bool => $get('mode') === 'existing')
                            ->visible(fn ($get): bool => $get('mode') === 'existing'),
                        Toggle::make('confirm_distinct')->label('I checked: this is a different company than the existing one(s) with the same name')
                            ->visible(fn ($get): bool => $get('mode') === 'new' && $exact->isNotEmpty()),
                        Textarea::make('note')->label('Internal note')->rows(2),
                    ];
                })
                ->action(function (array $data, Enrollment $record): void {
                    try {
                        $done = app(EnrollmentWorkflow::class)->approve($record, auth()->user(), $data, $data['note'] ?? null);
                    } catch (ValidationException $e) {
                        Notification::make()->danger()->title('Cannot approve')->body(collect($e->errors())->flatten()->implode("\n"))->persistent()->send();

                        return;
                    }

                    $done
                        ? Notification::make()->success()->title('Enrollment approved')->body('The invitation email has been queued.')->send()
                        : Notification::make()->warning()->title('Already reviewed')->body('Someone else handled this enrollment.')->send();
                    $this->refreshFormData(['status', 'info_status', 'company_id']);
                }),
            Action::make('resendInvitation')
                ->label('Resend invitation')
                ->icon('heroicon-o-paper-airplane')
                ->color('gray')
                ->authorize('review')
                ->visible(fn (Enrollment $record): bool => $record->status === Enrollment::STATUS_APPROVED && $record->activeInvitation() !== null)
                ->requiresConfirmation()
                ->modalDescription('Sends a new personal link and revokes the previous one. The link in the earlier email stops working.')
                ->action(fn (Enrollment $record) => $this->reissue($record, force: false)),
            Action::make('regenerateInvitation')
                ->label('Regenerate invitation')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->authorize('review')
                ->visible(fn (Enrollment $record): bool => $record->status === Enrollment::STATUS_APPROVED && $record->activeInvitation() === null)
                ->requiresConfirmation()
                ->modalDescription('The previous link is expired or revoked. A new personal link is emailed to the contact.')
                ->action(fn (Enrollment $record) => $this->reissue($record, force: true)),
            Action::make('revokeInvitation')
                ->label('Revoke access')
                ->icon('heroicon-o-no-symbol')
                ->color('danger')
                ->authorize('review')
                ->visible(fn (Enrollment $record): bool => $record->status === Enrollment::STATUS_APPROVED && $record->activeInvitation() !== null)
                ->requiresConfirmation()
                ->modalDescription('The company can no longer open or edit the information form until you send a new link. Submitted answers are kept.')
                ->action(function (Enrollment $record): void {
                    app(InvitationService::class)->revoke($record);
                    Notification::make()->success()->title('Invitation revoked')->send();
                    $this->refreshFormData(['status']);
                }),
            Action::make('deleteEnrollment')
                ->label('Delete enrollment')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->outlined()
                ->authorize('delete')
                ->requiresConfirmation()
                ->modalHeading('Delete this enrollment?')
                ->modalDescription('Removes the contact and invoice details, all answers, uploaded files, invitations and second-form submissions of this enrollment (for example for a privacy request). The company profile and any stand are kept. This cannot be undone.')
                ->action(function (Enrollment $record): void {
                    $record->delete();
                    Notification::make()->success()->title('Enrollment deleted')->send();
                    $this->redirect(EnrollmentResource::getUrl('index'));
                }),
            Action::make('reject')
                ->label('Reject')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->authorize('review')
                ->visible(fn (Enrollment $record): bool => $record->status === Enrollment::STATUS_PENDING)
                ->requiresConfirmation()
                ->form([
                    Textarea::make('reason')->label('Reason (emailed to the contact; optional)')->rows(5)->maxLength(2000),
                    Textarea::make('note')->label('Internal note')->rows(2),
                ])
                ->action(function (array $data, Enrollment $record): void {
                    $done = app(EnrollmentWorkflow::class)->reject($record, auth()->user(), $data['reason'] ?? null, $data['note'] ?? null);

                    $done
                        ? Notification::make()->success()->title('Enrollment rejected')->send()
                        : Notification::make()->warning()->title('Already reviewed')->send();
                    $this->refreshFormData(['status', 'rejection_reason']);
                }),
        ];
    }

    private function reissue(Enrollment $record, bool $force): void
    {
        try {
            app(InvitationService::class)->reissue($record, $force);
        } catch (ValidationException $e) {
            Notification::make()->warning()->title('Invitation not sent')->body(collect($e->errors())->flatten()->implode("\n"))->send();

            return;
        }

        Notification::make()->success()->title('New invitation queued')->body('The previous link no longer works.')->send();
        $this->refreshFormData(['status']);
    }
}
