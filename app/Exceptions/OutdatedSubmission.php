<?php

namespace App\Exceptions;

use App\Models\CompanyProfileSubmission;
use RuntimeException;

/**
 * A review of a profile submission that is no longer the pending version:
 * the company sent a newer update, or another administrator reviewed it first.
 */
class OutdatedSubmission extends RuntimeException
{
    public function __construct(public readonly CompanyProfileSubmission $submission, public readonly ?string $currentStatus)
    {
        parent::__construct($currentStatus === CompanyProfileSubmission::STATUS_SUPERSEDED
            ? 'This submission has been updated since you opened it. Please review the latest changes before continuing.'
            : 'This submission has already been reviewed. Please check its current status before continuing.');
    }

    /** The version that is waiting for review now, if any. */
    public function latestPending(): ?CompanyProfileSubmission
    {
        return $this->submission->company?->pendingProfileSubmission()->first();
    }
}
