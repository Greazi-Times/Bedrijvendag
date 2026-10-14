<?php

namespace App\Policies;

use App\Models\User;
use App\Support\Permissions;

/**
 * Maps Filament's abilities onto "module.action" permissions. Every ability
 * is defined, because Filament allows any ability a policy does not define.
 * An action the module does not register (e.g. permissions.create) is denied.
 */
abstract class ModelPolicy
{
    protected string $module;

    public function viewAny(User $user): bool
    {
        return $this->allows($user, 'view');
    }

    public function view(User $user): bool
    {
        return $this->allows($user, 'view');
    }

    public function create(User $user): bool
    {
        return $this->allows($user, 'create');
    }

    public function update(User $user): bool
    {
        return $this->allows($user, 'update');
    }

    public function review(User $user): bool
    {
        return $this->allows($user, 'review');
    }

    public function delete(User $user): bool
    {
        return $this->allows($user, 'delete');
    }

    public function deleteAny(User $user): bool
    {
        return $this->allows($user, 'delete');
    }

    public function restore(User $user): bool
    {
        return $this->allows($user, 'delete');
    }

    public function restoreAny(User $user): bool
    {
        return $this->allows($user, 'delete');
    }

    public function forceDelete(User $user): bool
    {
        return $this->allows($user, 'delete');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $this->allows($user, 'delete');
    }

    public function replicate(User $user): bool
    {
        return $this->allows($user, 'create');
    }

    public function reorder(User $user): bool
    {
        return $this->allows($user, 'update');
    }

    protected function allows(User $user, string $action): bool
    {
        // Pages that only offer one combined screen use a single "manage" permission.
        if (Permissions::MODULES[$this->module]['actions'] === ['manage']) {
            $action = 'manage';
        }

        return $user->can("{$this->module}.{$action}");
    }
}
