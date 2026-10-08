<?php

namespace App\Filament\Resources\NewsletterSubscribers\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

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
            ->actions([
                // no row actions needed
            ]);
    }
}
