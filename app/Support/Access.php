<?php

namespace App\Support;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Rules that keep role and user management safe. Policies answer "may this
 * user do X"; these rules also hold for Super Admins, who pass every policy
 * through Gate::before: nobody can change their own roles, lock out the last
 * Super Admin, touch the protected roles, or hand out more than they hold.
 */
final class Access
{
    /** @return Collection<int, string> */
    public static function permissionsOf(User $user): Collection
    {
        return $user->isSuperAdmin()
            ? Permission::query()->pluck('name')
            : $user->getAllPermissions()->pluck('name');
    }

    /** True when $actor holds every permission in $names. */
    public static function covers(User $actor, Collection $names): bool
    {
        return $names->diff(self::permissionsOf($actor))->isEmpty();
    }

    /** An account can be managed only by someone who holds at least its permissions. */
    public static function canManageUser(User $actor, User $target): bool
    {
        if ($actor->isSuperAdmin()) {
            return true;
        }

        return ! $target->isSuperAdmin() && self::covers($actor, $target->getAllPermissions()->pluck('name'));
    }

    /** Super Admin is never edited; other roles need covering permissions and must not be the actor's own. */
    public static function canManageRole(User $actor, Role $role): bool
    {
        if ($role->name === Permissions::SUPER_ADMIN) {
            return false;
        }

        if ($actor->isSuperAdmin()) {
            return true;
        }

        return ! $actor->hasRole($role) && self::covers($actor, $role->permissions->pluck('name'));
    }

    /** @return Collection<int, Role> */
    public static function assignableRoles(User $actor): Collection
    {
        $roles = Role::query()->with('permissions')->orderBy('name')->get();

        if ($actor->isSuperAdmin()) {
            return $roles;
        }

        $held = self::permissionsOf($actor);

        return $roles
            ->reject(fn (Role $role): bool => $role->name === Permissions::SUPER_ADMIN)
            ->filter(fn (Role $role): bool => $role->permissions->pluck('name')->diff($held)->isEmpty())
            ->values();
    }

    /** @param  array<int, int|string>  $roleIds */
    public static function syncRoles(User $actor, User $target, array $roleIds): void
    {
        $current = $target->roles()->pluck('roles.id')->map(fn ($id): int => (int) $id);
        $wanted = collect($roleIds)->map(fn ($id): int => (int) $id)->unique();
        $changed = $current->diff($wanted)->merge($wanted->diff($current));

        if ($changed->isEmpty()) {
            return;
        }

        self::deny($actor->is($target), 'You cannot change your own roles.');
        self::deny($changed->diff(self::assignableRoles($actor)->pluck('id'))->isNotEmpty(), 'You are not allowed to assign or remove one of these roles.');

        $roles = Role::query()->whereIn('id', $wanted)->get();
        self::deny($roles->count() !== $wanted->count(), 'One of these roles does not exist.');

        if ($target->isSuperAdmin() && ! $roles->contains('name', Permissions::SUPER_ADMIN)) {
            self::ensureAnotherSuperAdmin($target);
        }

        $before = $target->getRoleNames()->values()->all();

        DB::transaction(function () use ($target, $roles, $before): void {
            $target->syncRoles($roles);

            AuditLog::record('user.roles_updated', $target, [
                'old' => $before,
                'new' => $roles->pluck('name')->sort()->values()->all(),
            ]);
        });
    }

    public static function deleteUser(User $actor, User $target): void
    {
        self::deny($actor->is($target), 'You cannot delete your own account.');
        self::deny(! self::canManageUser($actor, $target), 'You are not allowed to delete this account.');

        if ($target->isSuperAdmin()) {
            self::ensureAnotherSuperAdmin($target);
        }

        DB::transaction(function () use ($target): void {
            AuditLog::record('user.deleted', $target, [
                'email' => $target->email,
                'roles' => $target->getRoleNames()->values()->all(),
            ]);

            $target->delete();
        });
    }

    /**
     * Create or update a role. Only permissions the actor holds can be added or
     * removed. Permissions that are no longer defined in code are kept as they are.
     *
     * @param  array<int, string>  $permissions
     */
    public static function saveRole(User $actor, ?Role $role, string $name, array $permissions): Role
    {
        $name = trim($name);
        $current = $role ? $role->permissions->pluck('name') : collect();
        $wanted = collect($permissions)->unique()->values();

        self::deny($wanted->reject(fn (string $permission): bool => Permissions::defined($permission))->isNotEmpty(), 'Unknown permission.');

        $wanted = $wanted->merge($current->reject(fn (string $permission): bool => Permissions::defined($permission)))->values();

        if ($role) {
            self::deny(! self::canManageRole($actor, $role), 'You are not allowed to change this role.');
            self::deny(in_array($role->name, Permissions::SYSTEM_ROLES, true) && $name !== $role->name, 'System roles cannot be renamed.');
        }

        self::deny(! $role && in_array($name, Permissions::SYSTEM_ROLES, true), 'This role name is reserved.');

        $added = $wanted->diff($current)->values();
        $removed = $current->diff($wanted)->values();

        self::deny(! self::covers($actor, $added->merge($removed)), 'You can only grant or remove permissions you hold yourself.');

        return DB::transaction(function () use ($role, $name, $wanted, $added, $removed): Role {
            $isNew = ! $role;
            $oldName = $role?->name;

            $role ??= new Role(['guard_name' => config('auth.defaults.guard')]);
            $role->name = $name;
            $role->save();
            $role->syncPermissions($wanted->all());

            $changes = array_filter([
                'name' => $isNew || $oldName === $name ? null : ['old' => $oldName, 'new' => $name],
                'permissions_added' => $added->all() ?: null,
                'permissions_removed' => $removed->all() ?: null,
            ]);

            if ($isNew || $changes) {
                AuditLog::record($isNew ? 'role.created' : 'role.updated', $role, $changes);
            }

            return $role;
        });
    }

    public static function deleteRole(User $actor, Role $role): void
    {
        self::deny(in_array($role->name, Permissions::SYSTEM_ROLES, true), 'System roles cannot be deleted.');
        self::deny(! self::canManageRole($actor, $role), 'You are not allowed to delete this role.');

        DB::transaction(function () use ($role): void {
            AuditLog::record('role.deleted', $role, [
                'permissions' => $role->permissions->pluck('name')->all(),
                'users' => $role->users()->count(),
            ]);

            $role->delete();
        });
    }

    /**
     * Every registered permission for the user, grouped by module, with the
     * roles (or a direct grant) that give it.
     *
     * @return array<string, list<array{label: string, allowed: bool, via: list<string>}>>
     */
    public static function effectivePermissions(User $user): array
    {
        $user->loadMissing(['roles.permissions', 'permissions']);
        $superAdmin = $user->isSuperAdmin();
        $direct = $user->permissions->pluck('name');
        $groups = [];

        foreach (Permissions::all() as $name) {
            $via = $superAdmin ? [Permissions::SUPER_ADMIN] : $user->roles
                ->filter(fn (Role $role): bool => $role->permissions->contains('name', $name))
                ->pluck('name')
                ->when($direct->contains($name), fn (Collection $via) => $via->push('Direct'))
                ->values()
                ->all();

            $groups[Permissions::moduleLabel(Permissions::module($name))][] = [
                'label' => Permissions::label($name),
                'allowed' => $via !== [],
                'via' => $via,
            ];
        }

        return $groups;
    }

    private static function ensureAnotherSuperAdmin(User $target): void
    {
        $others = User::role(Permissions::SUPER_ADMIN)->whereKeyNot($target->getKey())->count();

        self::deny($others === 0, 'The last Super Admin cannot be removed.');
    }

    private static function deny(bool $condition, string $message): void
    {
        if ($condition) {
            throw new AuthorizationException($message);
        }
    }
}
