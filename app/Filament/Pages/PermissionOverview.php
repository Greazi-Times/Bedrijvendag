<?php

namespace App\Filament\Pages;

use App\Support\Permissions;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;
use UnitEnum;

/** Every permission against every role, read-only. Roles are edited under Roles. */
class PermissionOverview extends Page
{
    protected string $view = 'filament.pages.permission-overview';

    protected static ?string $slug = 'permissions';

    protected static ?string $title = 'Permissions';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static string|UnitEnum|null $navigationGroup = 'Administration';

    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        return auth()->user()?->can('permissions.view') ?? false;
    }

    /** @return Collection<int, Role> System roles first, then custom roles by name. */
    public function getRoles(): Collection
    {
        return Role::query()
            ->with('permissions:id,name')
            ->withCount('users')
            ->get()
            ->sortBy(fn (Role $role): string => (in_array($role->name, Permissions::SYSTEM_ROLES, true)
                ? array_search($role->name, Permissions::SYSTEM_ROLES, true)
                : 9).$role->name)
            ->values();
    }
}
