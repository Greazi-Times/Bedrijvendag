<?php

namespace App\Filament\Resources\NewsletterSubscribers\Pages;

use App\Filament\Resources\NewsletterSubscribers\NewsletterSubscriberResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Excel;
use pxlrbt\FilamentExcel\Actions\ExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class ListNewsletterSubscribers extends ListRecords
{
    protected static string $resource = NewsletterSubscriberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ExportAction::make()
                ->color('gray')
                ->label('Download Excel')
                ->exports([
                    ExcelExport::make('newsletter')
                        ->fromTable()
                        ->only([
                            'email',
                            'subscribed_at',
                        ])
                        ->ignoreFormatting([
                            'subscribed_at',
                        ])
                        ->withWriterType(Excel::XLSX)
                        ->withFilename('newsletter-subscribers-'.now()->format('Y-m-d')),
                ]),
            CreateAction::make(),
        ];
    }
}
