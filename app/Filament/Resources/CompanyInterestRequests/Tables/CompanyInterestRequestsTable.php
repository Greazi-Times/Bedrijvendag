<?php

namespace App\Filament\Resources\CompanyInterestRequests\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

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
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
