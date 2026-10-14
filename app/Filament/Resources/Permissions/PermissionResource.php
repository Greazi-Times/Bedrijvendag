<?php

namespace App\Filament\Resources\Permissions;

use App\Filament\Resources\Permissions\Pages\ListPermissions;
use App\Support\Permissions;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Permission;

class PermissionResource extends Resource
{
    protected static ?string $model = Permission::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static string|null|\UnitEnum $navigationGroup = 'Administration';

    protected static ?int $navigationSort = 3;

    public static function table(Table $table): Table
    {
        $module = fn (Permission $record): string => Permissions::module($record->name);

        return $table
            ->heading('Defined in code')
            ->description('Permissions are defined in code (App\Support\Permissions) and created by `php artisan permissions:sync`. A permission only has an effect where the application checks it, so they cannot be added here. Grant them through roles.')
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount('roles')->with('roles:id,name'))
            ->defaultSort('name')
            ->defaultGroup(
                Group::make('name')
                    ->label('Module')
                    ->getKeyFromRecordUsing($module)
                    ->getTitleFromRecordUsing(fn (Permission $record): string => Permissions::moduleLabel($module($record)))
                    ->collapsible()
            )
            ->groupingSettingsHidden()
            ->paginated(false)
            ->columns([
                TextColumn::make('label')
                    ->state(fn (Permission $record): string => Permissions::label($record->name))
                    ->description(fn (Permission $record): ?string => Permissions::defined($record->name) ? null : 'Not used by the application'),
                TextColumn::make('name')
                    ->label('Key')
                    ->fontFamily('mono')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('roles.name')
                    ->label('Roles')
                    ->badge()
                    ->placeholder('No roles'),
                TextColumn::make('roles_count')
                    ->label('Role count')
                    ->numeric()
                    ->alignEnd(),
            ])
            ->filters([
                SelectFilter::make('module')
                    ->options(collect(Permissions::MODULES)->map(fn (array $definition): string => $definition['label'])->all())
                    ->query(fn (Builder $query, array $data) => $query->when(
                        $data['value'],
                        fn (Builder $query, string $module) => $query->where('name', 'like', "{$module}.%"),
                    )),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPermissions::route('/'),
        ];
    }
}
