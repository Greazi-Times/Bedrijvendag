<?php

namespace App\Filament\Resources\NewsletterSubscribers\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Maatwebsite\Excel\Excel;
use pxlrbt\FilamentExcel\Actions\ExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class NewsletterSubscribersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable(),
                TextColumn::make('subscribed_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->headerActions([
                ExportAction::make()
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
                            ->withFilename('newsletter-subscribers-' . now()->format('Y-m-d')),
                    ]),
            ])
            ->actions([
                // no row actions needed
            ]);
    }
}
