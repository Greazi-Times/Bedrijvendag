<?php

namespace App\Policies;

use App\Models\User;

/** view: results, QR code and link; update: publish, schedule, reset; delete: remove or redact a written answer. */
class FeedbackSurveyPolicy extends ModelPolicy
{
    protected string $module = 'feedback';

    // Surveys are created with their edition and removed with it; never by hand.
    public function create(User $user): bool
    {
        return false;
    }

    public function replicate(User $user): bool
    {
        return false;
    }
}
