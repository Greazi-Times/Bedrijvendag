<?php

namespace App\Services;

use App\Mail\EnrollmentContactProposedMail;
use App\Mail\EnrollmentContactVerificationMail;
use App\Models\Enrollment;
use App\Models\EnrollmentContactChange;
use App\Models\EnrollmentInfoSubmission;
use App\Support\MailAfterCommit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * The enrollment contact is the trusted address. A different address typed on the second form is only a proposal:
 * it receives a verification link and nothing else, the trusted contact is told, and the address becomes the operational
 * contact only after its owner confirms. A new name for an already trusted address needs no verification.
 */
class ContactVerification
{
    public const DAYS_VALID = 7;

    /** Call inside the transaction that stores the submission (the enrollment row is locked there). */
    public function propose(Enrollment $enrollment, ?string $name, ?string $email, ?EnrollmentInfoSubmission $submission = null): ?EnrollmentContactChange
    {
        // A contact is identified by the address. A name is optional and only kept if the form happens to ask for one.
        $name = trim((string) $name) ?: null;
        $email = mb_strtolower(trim((string) $email));

        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        $current = $enrollment->operationalContact();
        $trusted = [mb_strtolower($enrollment->contact_email), mb_strtolower($current['email'])];

        if (in_array($email, $trusted, true)) {
            // Same trusted address: nothing to verify. Only an explicitly given, different name is recorded, and a name alone cannot redirect any mail.
            if ($name === null || $name === $current['name']) {
                return null;
            }

            $this->supersedePending($enrollment);

            return $this->record($enrollment, $submission, $name, $email, verifiedBy: 'trusted_email');
        }

        $pending = $enrollment->contactChanges()->whereNull('verified_at')->whereNull('superseded_at')->where('expires_at', '>', now())->latest('id')->first();

        if ($pending && mb_strtolower($pending->email) === $email && $pending->name === $name) {
            return $pending; // repeated identical submission: no second verification email
        }

        $this->supersedePending($enrollment);
        $token = Str::random(48);
        $change = $this->record($enrollment, $submission, $name, $email, token: $token);

        MailAfterCommit::queue($email, new EnrollmentContactVerificationMail($enrollment->loadMissing('event'), $token, $name), fn () => $change->forceFill(['queued_at' => now()])->save());
        MailAfterCommit::queue($current['email'], new EnrollmentContactProposedMail($enrollment, $current['name'], $name, $email));

        return $change;
    }

    /** @return array{state: string, change: ?EnrollmentContactChange} state: ready | invalid | expired | verified */
    public function resolve(string $token): array
    {
        $change = preg_match('/^[A-Za-z0-9]{48}$/', $token)
            ? EnrollmentContactChange::query()->where('token_hash', hash('sha256', $token))->with('enrollment.event')->first()
            : null;

        return match (true) {
            $change === null => ['state' => 'invalid', 'change' => null],
            $change->superseded_at !== null => ['state' => 'invalid', 'change' => null], // a newer proposal replaced it
            $change->verified_at !== null => ['state' => 'verified', 'change' => $change],
            $change->expires_at->isPast() => ['state' => 'expired', 'change' => null],
            default => ['state' => 'ready', 'change' => $change],
        };
    }

    /** Verifies the address. Repeating it is harmless. */
    public function verify(string $token): string
    {
        $resolved = $this->resolve($token);

        if ($resolved['state'] !== 'ready') {
            return $resolved['state'];
        }

        DB::transaction(function () use ($resolved): void {
            $change = EnrollmentContactChange::query()->lockForUpdate()->find($resolved['change']->id);

            if (! $change || $change->verified_at !== null || $change->superseded_at !== null || $change->expires_at->isPast()) {
                return;
            }

            // Earlier verified contacts stay as history but are no longer current.
            foreach ($change->enrollment->contactChanges()->whereNotNull('verified_at')->whereNull('superseded_at')->get() as $previous) {
                $previous->forceFill(['superseded_at' => now()])->save();
            }

            // The hash stays so the link can still say "confirmed"; once verified it grants nothing but that message.
            $change->forceFill(['verified_at' => now(), 'verified_by' => 'email'])->save();
        });

        return 'verified';
    }

    private function supersedePending(Enrollment $enrollment): void
    {
        foreach ($enrollment->contactChanges()->whereNull('verified_at')->whereNull('superseded_at')->get() as $pending) {
            $pending->forceFill(['superseded_at' => now(), 'token_hash' => null])->save();
        }
    }

    private function record(Enrollment $enrollment, ?EnrollmentInfoSubmission $submission, ?string $name, string $email, ?string $token = null, ?string $verifiedBy = null): EnrollmentContactChange
    {
        if ($verifiedBy) {
            // A verified change replaces the previously current one.
            foreach ($enrollment->contactChanges()->whereNotNull('verified_at')->whereNull('superseded_at')->get() as $previous) {
                $previous->forceFill(['superseded_at' => now()])->save();
            }
        }

        return $enrollment->contactChanges()->create([
            'info_submission_id' => $submission?->id,
            'name' => $name,
            'email' => $email,
            'token_hash' => $token ? hash('sha256', $token) : null,
            'expires_at' => $token ? now()->addDays(self::DAYS_VALID) : null,
            'verified_at' => $verifiedBy ? now() : null,
            'verified_by' => $verifiedBy,
        ]);
    }
}
