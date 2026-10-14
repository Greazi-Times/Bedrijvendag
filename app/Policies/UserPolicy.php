<?php

namespace App\Policies;

use App\Models\User;
use App\Support\Access;

class UserPolicy extends ModelPolicy
{
    protected string $module = 'users';

    public function update(User $user, ?User $target = null): bool
    {
        return parent::update($user) && (! $target || Access::canManageUser($user, $target));
    }

    public function delete(User $user, ?User $target = null): bool
    {
        return parent::delete($user) && (! $target || (! $user->is($target) && Access::canManageUser($user, $target)));
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
