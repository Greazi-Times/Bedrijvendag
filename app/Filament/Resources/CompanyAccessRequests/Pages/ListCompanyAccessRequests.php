<?php

namespace App\Filament\Resources\CompanyAccessRequests\Pages;

use App\Filament\Resources\CompanyAccessRequests\CompanyAccessRequestResource;
use Filament\Resources\Pages\ListRecords;

class ListCompanyAccessRequests extends ListRecords
{
    protected static string $resource = CompanyAccessRequestResource::class;
}
