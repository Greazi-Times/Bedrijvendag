<?php

namespace App\Filament\Resources\Forms\Pages;

use App\Filament\Resources\Forms\FormResource;
use App\Filament\Resources\Forms\Schemas\FormBuilderSchema;
use Filament\Resources\Pages\CreateRecord;

class CreateForm extends CreateRecord
{
    protected static string $resource = FormResource::class;

    /** A new form starts from the default questions for its kind, including the locked system fields. */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['draft_schema'] = FormBuilderSchema::defaultFor($data['kind']);

        return $data;
    }
}
