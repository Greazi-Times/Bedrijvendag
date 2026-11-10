<?php

namespace App\Filament\Pages\Concerns;

use App\Support\PolicyDraft;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

/** A header action that loads the repository draft into the editor without saving it or touching the stored policy. */
trait OffersPolicyDraft
{
    abstract protected static function draftFile(): string;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('loadDraft')
                ->label('Load draft into editor')
                ->icon('heroicon-o-document-text')
                ->color('gray')
                ->requiresConfirmation()
                ->modalDescription('Replaces the text in the editor with the draft from the policies folder. Nothing is saved until you press Save, and Save is blocked while the draft still has open items.')
                ->action(function (): void {
                    $html = PolicyDraft::html(static::draftFile());

                    if ($html === null) {
                        Notification::make()->warning()->title('No draft file found')->send();

                        return;
                    }

                    $this->form->fill(['content' => $html]);
                    Notification::make()->success()->title('Draft loaded, not saved')->send();
                }),
        ];
    }
}
