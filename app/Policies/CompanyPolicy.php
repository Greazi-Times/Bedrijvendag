<?php

namespace App\Policies;

use App\Models\User;

class CompanyPolicy extends ModelPolicy
{
    protected string $module = 'companies';

    /** Verification links give edit access to a company profile. */
    public function links(User $user): bool
    {
        return $this->allows($user, 'links');
    }
}
