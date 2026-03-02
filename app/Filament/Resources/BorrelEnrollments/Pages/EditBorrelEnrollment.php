<?php

namespace App\Filament\Resources\BorrelEnrollments\Pages;

use App\Filament\Resources\BorrelEnrollments\BorrelEnrollmentResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditBorrelEnrollment extends EditRecord
{
    protected static string $resource = BorrelEnrollmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
