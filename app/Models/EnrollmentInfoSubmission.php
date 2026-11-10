<?php

namespace App\Models;

use App\Casts\EncryptedPayload;
use App\Models\Concerns\Audited;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnrollmentInfoSubmission extends Model
{
    use Audited;

    /** Answers stay in the row; the log only records that a revision happened. */
    protected array $auditAttributes = ['enrollment_id', 'revision', 'profile_status'];

    protected $fillable = ['enrollment_id', 'form_version_id', 'revision', 'answers', 'files', 'profile_status', 'profile_proposal', 'profile_submission_id', 'submitted_at'];

    protected $casts = ['answers' => EncryptedPayload::class, 'files' => 'array', 'profile_proposal' => 'array', 'submitted_at' => 'datetime'];

    protected $hidden = ['answers', 'files', 'profile_proposal'];

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function formVersion(): BelongsTo
    {
        return $this->belongsTo(FormVersion::class);
    }

    public function profileSubmission(): BelongsTo
    {
        return $this->belongsTo(CompanyProfileSubmission::class, 'profile_submission_id');
    }
}
