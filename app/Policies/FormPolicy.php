<?php

namespace App\Policies;

use App\Models\Form;
use App\Models\User;

class FormPolicy extends ModelPolicy
{
    protected string $module = 'forms';

    // The global defaults can be edited and published like any form, but never deleted.
    public function delete(User $user, ?Form $form = null): bool
    {
        return ($form === null || ! $form->isGlobal()) && parent::delete($user);
    }

    public function forceDelete(User $user, ?Form $form = null): bool
    {
        return ($form === null || ! $form->isGlobal()) && parent::forceDelete($user);
    }
}
