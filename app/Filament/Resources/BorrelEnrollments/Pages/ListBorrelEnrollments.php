<?php

namespace App\Filament\Resources\BorrelEnrollments\Pages;

use App\Filament\Resources\BorrelEnrollments\BorrelEnrollmentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBorrelEnrollments extends ListRecords
{
    protected static string $resource = BorrelEnrollmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
