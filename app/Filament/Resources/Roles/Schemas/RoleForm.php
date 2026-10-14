<?php

namespace App\Filament\Resources\Roles\Schemas;

use App\Filament\Forms\Components\PermissionMatrix;
use App\Support\Permissions;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Spatie\Permission\Models\Role;

class RoleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Role')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->disabled(fn (?Role $record): bool => in_array($record?->name, Permissions::SYSTEM_ROLES, true))
                            ->helperText(fn (?Role $record): ?string => in_array($record?->name, Permissions::SYSTEM_ROLES, true) ? 'System roles keep their name.' : null),
                    ]),

                Section::make('Permissions')
                    ->description('Rows are modules, columns are what a role may do there. You can only grant or remove permissions you hold yourself.')
                    ->schema([
                        PermissionMatrix::make('permissions')->hiddenLabel(),
                    ]),
            ]);
    }
}
