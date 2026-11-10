<?php

namespace App\Models;

use App\Models\Concerns\Audited;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/** Personal link to the second form. Only the hash is stored. */
class EnrollmentInvitation extends Model
{
    use Audited;

    /** Never the hash: the log must not help anyone reconstruct access. */
    protected array $auditAttributes = ['enrollment_id', 'expires_at', 'queued_at', 'revoked_at'];

    protected $fillable = ['enrollment_id', 'token_hash', 'expires_at', 'queued_at', 'revoked_at'];

    protected $casts = ['expires_at' => 'datetime', 'queued_at' => 'datetime', 'revoked_at' => 'datetime'];

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public static function hash(string $token): string
    {
        return hash('sha256', $token);
    }

    /** @return array{0: self, 1: string} the invitation and its plain token (shown exactly once) */
    public static function issue(Enrollment $enrollment, int $days = 60): array
    {
        foreach ($enrollment->invitations()->whereNull('revoked_at')->get() as $previous) {
            $previous->forceFill(['revoked_at' => now()])->save();
        }

        $token = Str::random(48);

        return [self::create([
            'enrollment_id' => $enrollment->id,
            'token_hash' => self::hash($token),
            'expires_at' => now()->addDays($days),
        ]), $token];
    }

    public function isUsable(): bool
    {
        return $this->revoked_at === null && $this->expires_at->isFuture();
    }
}
