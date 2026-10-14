<?php

namespace App\Filament\Resources\Roles\Schemas;

use App\Support\Access;
use App\Support\Permissions;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
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
                    ->description('Grouped by module. You can only grant or remove permissions you hold yourself.')
                    ->schema([
                        TextInput::make('search')
                            ->hiddenLabel()
                            ->placeholder('Search permissions')
                            ->prefixIcon(Heroicon::OutlinedMagnifyingGlass)
                            ->live(debounce: 300)
                            ->dehydrated(false),
                        Grid::make(['sm' => 2, 'xl' => 3])
                            ->schema(self::moduleLists()),
                    ]),
            ]);
    }

    /** Form state key for a module; dashes would break the Livewire state path. */
    public static function stateKey(string $module): string
    {
        return str_replace('-', '_', $module);
    }

    /** @return list<CheckboxList> */
    private static function moduleLists(): array
    {
        $lists = [];
        $held = Access::permissionsOf(auth()->user());

        foreach (Permissions::MODULES as $module => $definition) {
            $options = collect($definition['actions'])
                ->mapWithKeys(fn (string $action): array => ["{$module}.{$action}" => Permissions::label("{$module}.{$action}")])
                ->all();

            $lists[] = CheckboxList::make('permissions.'.self::stateKey($module))
                ->label($definition['label'])
                ->options($options)
                ->bulkToggleable(count($options) > 1)
                ->disableOptionWhen(fn (string $value): bool => ! $held->contains($value))
                // Hidden by the search, but still saved.
                ->dehydratedWhenHidden()
                ->hidden(fn (Get $get): bool => ! self::matches($get('search'), $definition['label'], $options));
        }

        return $lists;
    }

    /** @param  array<string, string>  $options */
    private static function matches(?string $search, string $label, array $options): bool
    {
        $search = mb_strtolower(trim((string) $search));

        if ($search === '') {
            return true;
        }

        return collect([$label, ...array_keys($options), ...array_values($options)])
            ->contains(fn (string $text): bool => str_contains(mb_strtolower($text), $search));
    }
}
