<?php

namespace App\Policies;

use App\Models\User;
use App\Support\Access;
use App\Support\Permissions;
use Spatie\Permission\Models\Role;

class RolePolicy extends ModelPolicy
{
    protected string $module = 'roles';

    public function update(User $user, ?Role $role = null): bool
    {
        return parent::update($user) && (! $role || Access::canManageRole($user, $role));
    }

    public function delete(User $user, ?Role $role = null): bool
    {
        return parent::delete($user)
            && (! $role || (! in_array($role->name, Permissions::SYSTEM_ROLES, true) && Access::canManageRole($user, $role)));
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
