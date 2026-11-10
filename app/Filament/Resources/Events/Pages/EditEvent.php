<?php

namespace App\Filament\Resources\Events\Pages;

use App\Filament\Resources\Events\EventResource;
use App\Services\EditionForms;
use App\Services\FormResolver;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class EditEvent extends EditRecord
{
    protected static string $resource = EventResource::class;

    /** Model-level rules (stand counts, form choices) throw plain validation errors; show them beside the fields. */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        try {
            return parent::handleRecordUpdate($record, $data);
        } catch (ValidationException $e) {
            Notification::make()->danger()->title('Edition not saved')->body(collect($e->errors())->flatten()->implode("\n"))->persistent()->send();

            throw ValidationException::withMessages(collect($e->errors())->mapWithKeys(fn ($messages, $key): array => ["data.{$key}" => $messages])->all());
        }
    }

    /** @return list<Action> customize / go back to the global default / open, for each of the two forms */
    private function formActions(): array
    {
        $resolver = app(FormResolver::class);
        $actions = [];

        foreach (['enrollment' => 'enrollment form', 'info' => 'information form'] as $kind => $label) {
            $actions[] = Action::make("customize_{$kind}")
                ->label("Customize {$label}")
                ->icon('heroicon-o-document-duplicate')
                ->color('gray')
                ->authorize('update')
                ->visible(fn (): bool => ! $resolver->isCustomized($this->getRecord(), $kind))
                ->requiresConfirmation()
                ->modalHeading("Customize the {$label} for this edition?")
                ->modalDescription('Copies the current published global form into an independent form that only this edition uses. Later changes to the global form will no longer affect this edition. You can go back to the global default at any time.')
                ->action(function () use ($kind): void {
                    app(EditionForms::class)->customize($this->getRecord(), $kind, null, auth()->user());
                    $this->refreshFormData(['enrollment_form_status', 'info_form_status']);
                    Notification::make()->success()->title('This edition now has its own form')->send();
                });

            $actions[] = Action::make("global_{$kind}")
                ->label("Use global {$label}")
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('warning')
                ->authorize('update')
                ->visible(fn (): bool => $resolver->isCustomized($this->getRecord(), $kind))
                ->requiresConfirmation()
                ->modalHeading("Go back to the global {$label}?")
                ->modalDescription('This edition stops using its custom form for future submissions and inherits the global default instead. The custom form, its versions and every answer already submitted are kept and stay readable.')
                ->action(function () use ($kind): void {
                    app(EditionForms::class)->revert($this->getRecord(), $kind);
                    $this->refreshFormData(['enrollment_form_status', 'info_form_status']);
                    Notification::make()->success()->title('This edition now uses the global default')->send();
                });
        }

        return $actions;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('regenerateLink')
                ->label('Regenerate enrollment link')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->authorize('update')
                ->requiresConfirmation()
                ->modalDescription('The current link stops working immediately. Companies holding it will need the new one.')
                ->action(function (): void {
                    $this->getRecord()->regenerateEnrollmentToken();
                    $this->refreshFormData(['enrollment_link']);
                    Notification::make()->success()->title('New enrollment link generated')->send();
                }),
            ...$this->formActions(),
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
