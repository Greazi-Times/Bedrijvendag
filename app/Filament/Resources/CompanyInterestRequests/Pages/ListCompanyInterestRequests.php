<?php

namespace App\Filament\Resources\CompanyInterestRequests\Pages;

use App\Filament\Resources\CompanyInterestRequests\CompanyInterestRequestResource;
use App\Models\CompanyInterestRequest;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Excel;
use pxlrbt\FilamentExcel\Actions\ExportAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class ListCompanyInterestRequests extends ListRecords
{
    protected static string $resource = CompanyInterestRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ExportAction::make('companyInterestRequests')
                ->color('gray')
                ->label('Download CSV')
                ->exports([
                    ExcelExport::make('company-interest-requests')
                        ->fromTable()
                        ->withColumns([
                            Column::make('company_name')
                                ->heading('Company'),
                            Column::make('website_url')
                                ->heading('Website'),
                            Column::make('contact_name')
                                ->heading('Contact'),
                            Column::make('contact_email')
                                ->heading('Email'),
                            Column::make('event.name')
                                ->heading('Edition'),
                            Column::make('message')
                                ->heading('Message'),
                            Column::make('created_at')
                                ->heading('Received')
                                ->getStateUsing(
                                    fn (CompanyInterestRequest $record): string => $record->created_at->format('Y-m-d H:i:s'),
                                ),
                        ])
                        ->only([
                            'company_name',
                            'website_url',
                            'contact_name',
                            'contact_email',
                            'event.name',
                            'message',
                            'created_at',
                        ])
                        ->withWriterType(Excel::CSV)
                        ->withFilename('company-interest-requests-'.now()->format('Y-m-d')),
                ]),
        ];
    }
}
