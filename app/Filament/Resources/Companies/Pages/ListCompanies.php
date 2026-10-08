<?php

namespace App\Filament\Resources\Companies\Pages;

use App\Filament\Resources\Companies\CompanyResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Excel;
use pxlrbt\FilamentExcel\Actions\ExportAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class ListCompanies extends ListRecords
{
    protected static string $resource = CompanyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ExportAction::make('verificationLinks')
                ->color('gray')
                ->label('Download verification links')
                ->exports([
                    ExcelExport::make('company-verification-links')
                        ->fromTable()
                        ->withColumns([
                            Column::make('name')
                                ->heading('Company'),
                            Column::make('website_url')
                                ->heading('Website'),
                            Column::make('profile_contact_email')
                                ->heading('Profile contact email'),
                            Column::make('profile_verification_url')
                                ->heading('Verification link'),
                        ])
                        ->only([
                            'name',
                            'website_url',
                            'profile_contact_email',
                            'profile_verification_url',
                        ])
                        ->withWriterType(Excel::XLSX)
                        ->withFilename('company-verification-links-'.now()->format('Y-m-d')),
                ]),
            CreateAction::make(),
        ];
    }
}
