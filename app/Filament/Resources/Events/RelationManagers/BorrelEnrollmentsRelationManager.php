<?php

namespace App\Filament\Resources\Events\RelationManagers;

use App\Filament\Resources\BorrelEnrollments\BorrelEnrollmentResource;
use Filament\Tables\Columns\TextColumn;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class BorrelEnrollmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'borrelEnrollments';

    protected static ?string $relatedResource = BorrelEnrollmentResource::class;

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),

                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->headerActions([])
            /*->actions([
                DeleteAction::make()
                    ->visible(fn () => auth()->user()?->is_super_admin),
            ])*/
            ->bulkActions([]);
    }
}
