<?php

namespace App\Filament\Resources\Companies\Tables;

use App\Models\Company;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Maatwebsite\Excel\Excel;
use pxlrbt\FilamentExcel\Actions\ExportAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class CompaniesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('logo_path')
                    ->searchable(),
                TextColumn::make('website_url')
                    ->searchable(),
                TextColumn::make('profile_contact_email')
                    ->label('Profile contact')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('profile_verification_url')
                    ->label('Verification link')
                    ->copyable()
                    ->copyMessage('Verification link copied')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                ExportAction::make('verificationLinks')
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
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('verificationLink')
                    ->label('Verification link')
                    ->icon('heroicon-o-link')
                    ->url(fn (Company $record): string => $record->profileVerificationUrl())
                    ->openUrlInNewTab(),
                Action::make('regenerateProfileToken')
                    ->label('Regenerate link')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function (Company $record): void {
                        $record->regenerateProfileToken();

                        Notification::make()
                            ->title('Verification link regenerated')
                            ->success()
                            ->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
