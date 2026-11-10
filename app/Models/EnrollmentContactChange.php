<?php

namespace App\Models;

use App\Casts\EncryptedEmail;
use App\Models\Concerns\Audited;
use App\Models\Concerns\HasEmailHash;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnrollmentContactChange extends Model
{
    use Audited, HasEmailHash;

    protected array $emailHashes = ['email' => 'email_hash'];

    /** Never the hash. */
    protected array $auditAttributes = ['enrollment_id', 'verified_at', 'verified_by', 'superseded_at'];

    protected $fillable = ['enrollment_id', 'info_submission_id', 'name', 'email', 'token_hash', 'expires_at', 'verified_at', 'verified_by', 'superseded_at', 'queued_at'];

    protected $casts = ['email' => EncryptedEmail::class, 'name' => \App\Casts\EncryptedText::class, 'expires_at' => 'datetime', 'verified_at' => 'datetime', 'superseded_at' => 'datetime', 'queued_at' => 'datetime'];

    protected $hidden = ['token_hash'];

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function isPending(): bool
    {
        return $this->verified_at === null && $this->superseded_at === null && $this->expires_at?->isFuture() === true;
    }
}
