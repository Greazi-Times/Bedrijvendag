<?php

namespace App\Filament\Resources\CompanyInterestRequests\Pages;

use App\Filament\Resources\CompanyInterestRequests\CompanyInterestRequestResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCompanyInterestRequest extends ViewRecord
{
    protected static string $resource = CompanyInterestRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
