<?php

namespace App\Filament\Resources\Roles\Tables;

use App\Support\Permissions;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Spatie\Permission\Models\Role;

class RolesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->withCount(['users', 'permissions']))
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Role $record): ?string => match (true) {
                        $record->name === Permissions::SUPER_ADMIN => 'System role · every permission',
                        in_array($record->name, Permissions::SYSTEM_ROLES, true) => 'System role',
                        default => null,
                    }),
                TextColumn::make('users_count')
                    ->label('Users')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('permissions_count')
                    ->label('Permissions')
                    ->numeric()
                    ->sortable()
                    ->formatStateUsing(fn (int $state, Role $record): string => $record->name === Permissions::SUPER_ADMIN ? 'All' : (string) $state),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->emptyStateHeading('No roles yet');
    }
}
