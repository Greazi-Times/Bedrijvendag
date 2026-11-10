<?php

namespace App\Support;

use Closure;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/** Queues mail only once the surrounding transaction commits. A dispatch failure is logged (no recipient, no token) and never undoes the saved work. */
class MailAfterCommit
{
    public static function queue(string $to, Mailable $mail, ?Closure $onQueued = null): void
    {
        DB::afterCommit(function () use ($to, $mail, $onQueued): void {
            try {
                Mail::to($to)->queue($mail);
                $onQueued?->__invoke();
            } catch (Throwable $e) {
                Log::error('Could not queue enrollment email.', ['mail' => $mail::class, 'enrollment_id' => $mail->enrollment->id ?? null, 'error' => LogRedactor::scrub($e->getMessage())]);
            }
        });
    }
}
