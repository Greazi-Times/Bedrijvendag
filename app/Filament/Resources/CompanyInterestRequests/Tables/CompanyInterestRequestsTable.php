<?php

namespace App\Filament\Resources\CompanyInterestRequests\Tables;

use App\Models\CompanyInterestRequest;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Maatwebsite\Excel\Excel;
use pxlrbt\FilamentExcel\Actions\ExportAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class CompanyInterestRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('company_name')
                    ->label('Company')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('contact_name')
                    ->label('Contact')
                    ->searchable(),
                TextColumn::make('contact_email')
                    ->label('Email')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('event.name')
                    ->label('Edition')
                    ->placeholder('No upcoming edition'),
                TextColumn::make('created_at')
                    ->label('Received')
                    ->dateTime()
                    ->sortable(),
            ])
            ->headerActions([
                ExportAction::make('companyInterestRequests')
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
            ])
            ->recordActions([
                ViewAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
