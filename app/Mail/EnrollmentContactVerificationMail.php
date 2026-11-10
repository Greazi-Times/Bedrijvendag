<?php

namespace App\Mail;

use App\Models\Enrollment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class EnrollmentContactVerificationMail extends Mailable implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(public Enrollment $enrollment, public string $token, public ?string $contactName = null)
    {
        $this->enrollment->loadMissing('event');
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Bevestig je e-mailadres als contactpersoon – Bedrijvendag');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.enrollment-contact-verification');
    }

    /** Observable without leaking the recipient or the token: the failed job stays in failed_jobs for a retry. */
    public function failed(Throwable $exception): void
    {
        Log::error('Enrollment email failed.', ['mail' => static::class, 'enrollment_id' => $this->enrollment->id, 'error' => \App\Support\LogRedactor::scrub($exception->getMessage())]);
    }
}
