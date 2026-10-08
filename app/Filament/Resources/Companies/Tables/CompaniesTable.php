<?php

namespace App\Filament\Resources\Companies\Tables;

use App\Models\Company;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CompaniesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                ImageColumn::make('logo_path')
                    ->label('Logo')
                    ->disk('public')
                    ->height(40)
                    ->width(40)
                    ->square()
                    ->toggleable(),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
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
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make()
                    ->button(),
                EditAction::make()
                    ->button(),
                Action::make('verificationLink')
                    ->label('Verification link')
                    ->icon('heroicon-o-link')
                    ->button()
                    ->url(fn (Company $record): string => $record->profileVerificationUrl())
                    ->openUrlInNewTab(),
                Action::make('regenerateProfileToken')
                    ->label('Regenerate link')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->button()
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
