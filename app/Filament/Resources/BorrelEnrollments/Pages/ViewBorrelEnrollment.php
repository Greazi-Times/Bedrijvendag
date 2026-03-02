<?php

namespace App\Filament\Resources\BorrelEnrollments\Pages;

use App\Filament\Resources\BorrelEnrollments\BorrelEnrollmentResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewBorrelEnrollment extends ViewRecord
{
    protected static string $resource = BorrelEnrollmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
