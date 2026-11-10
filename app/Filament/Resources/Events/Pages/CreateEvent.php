<?php

namespace App\Filament\Resources\Events\Pages;

use App\Filament\Resources\Events\EventResource;
use App\Models\Event;
use App\Services\EditionForms;
use App\Services\FormResolver;
use Filament\Resources\Pages\CreateRecord;

class CreateEvent extends CreateRecord
{
    protected static string $resource = EventResource::class;

    /**
     * A new edition inherits both global default forms and needs no setup. Only when an earlier edition's custom form
     * is chosen as a starting point does the new edition get its own independent copy of it.
     */
    protected function afterCreate(): void
    {
        $source = Event::query()->find($this->data['copy_forms_from'] ?? null);

        if (! $source) {
            return;
        }

        foreach (array_keys(FormResolver::COLUMNS) as $kind) {
            if ($form = app(FormResolver::class)->override($source, $kind)) {
                app(EditionForms::class)->customize($this->getRecord(), $kind, $form, auth()->user());
            }
        }
    }
}
