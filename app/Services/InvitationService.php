<?php

namespace App\Services;

use App\Mail\EnrollmentInvitationMail;
use App\Models\Enrollment;
use App\Models\EnrollmentInvitation;
use App\Support\MailAfterCommit;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Policy: an enrollment has at most one usable invitation. Issuing a new one revokes the previous one inside
 * the same locked transaction, so two valid links never exist side by side. Only the hash is stored; a "resend"
 * is therefore always a replacement link.
 */
class InvitationService
{
    /** A link issued this recently is not replaced again unless forced, so double clicks do not mint links. */
    public const COOLDOWN_MINUTES = 5;

    public function reissue(Enrollment $enrollment, bool $force = false): EnrollmentInvitation
    {
        return DB::transaction(function () use ($enrollment, $force): EnrollmentInvitation {
            $fresh = Enrollment::query()->lockForUpdate()->findOrFail($enrollment->id);

            if ($fresh->status !== Enrollment::STATUS_APPROVED) {
                throw ValidationException::withMessages(['invitation' => 'Only approved enrollments can be invited.']);
            }

            $active = $fresh->activeInvitation();

            if ($active && ! $force && $active->created_at->gt(now()->subMinutes(self::COOLDOWN_MINUTES))) {
                throw ValidationException::withMessages(['invitation' => 'An invitation was issued less than '.self::COOLDOWN_MINUTES.' minutes ago. Please wait before sending another.']);
            }

            [$invitation, $token] = EnrollmentInvitation::issue($fresh);
            MailAfterCommit::queue($fresh->contact_email, new EnrollmentInvitationMail($fresh->loadMissing('event'), $token), fn () => $invitation->forceFill(['queued_at' => now()])->save());

            return $invitation;
        });
    }

    /** @return int number of invitations revoked */
    public function revoke(Enrollment $enrollment): int
    {
        return DB::transaction(function () use ($enrollment): int {
            Enrollment::query()->lockForUpdate()->findOrFail($enrollment->id);
            $count = 0;

            foreach ($enrollment->invitations()->whereNull('revoked_at')->get() as $invitation) {
                $invitation->forceFill(['revoked_at' => now()])->save(); // per row so the audit log records it
                $count++;
            }

            return $count;
        });
    }
}
