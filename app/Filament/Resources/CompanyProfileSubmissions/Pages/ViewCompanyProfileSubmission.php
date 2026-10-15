<?php

namespace App\Filament\Resources\CompanyProfileSubmissions\Pages;

use App\Filament\Resources\CompanyProfileSubmissions\CompanyProfileSubmissionResource;
use App\Models\CompanyProfileSubmission;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Pages\ViewRecord;

class ViewCompanyProfileSubmission extends ViewRecord
{
    protected static string $resource = CompanyProfileSubmissionResource::class;

    /**
     * The status this page was opened with. The record itself is reloaded on every
     * request, so without this a version superseded in the meantime would just
     * lose its buttons; now the click reaches the version check and the
     * administrator is told why and where the latest version is.
     */
    public ?string $statusWhenOpened = null;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $this->statusWhenOpened = $this->record->status;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->authorize('review')
                ->label('Approve changes')
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->visible(fn (): bool => $this->statusWhenOpened === CompanyProfileSubmission::STATUS_PENDING)
                ->form([
                    Textarea::make('review_note')
                        ->label('Review note')
                        ->rows(3),
                ])
                ->requiresConfirmation()
                ->action(fn (array $data) => $this->review('approve', $data['review_note'] ?? null)),
            Action::make('reject')
                ->authorize('review')
                ->label('Reject')
                ->color('danger')
                ->icon('heroicon-o-x-circle')
                ->visible(fn (): bool => $this->statusWhenOpened === CompanyProfileSubmission::STATUS_PENDING)
                ->form([
                    Textarea::make('review_note')
                        ->label('Reason or internal note')
                        ->rows(3),
                ])
                ->requiresConfirmation()
                ->action(fn (array $data) => $this->review('reject', $data['review_note'] ?? null)),
        ];
    }

    /**
     * @param  'approve'|'reject'  $decision
     */
    private function review(string $decision, ?string $note): void
    {
        CompanyProfileSubmissionResource::review($this->record, $decision, $note);

        // Show what is stored now: the review result, or that this version was superseded.
        $this->record->refresh();
        $this->statusWhenOpened = $this->record->status;
    }
}
