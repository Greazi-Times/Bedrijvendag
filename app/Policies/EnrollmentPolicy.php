<?php

namespace App\Policies;

class EnrollmentPolicy extends ModelPolicy
{
    protected string $module = 'enrollments';

    // Enrollments are created by the public form only; admins review but never edit answers.
    public function create(\App\Models\User $user): bool
    {
        return false;
    }

    public function update(\App\Models\User $user): bool
    {
        return false;
    }
}
