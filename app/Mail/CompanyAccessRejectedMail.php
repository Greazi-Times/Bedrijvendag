<?php

namespace App\Mail;

use App\Models\CompanyAccessRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CompanyAccessRejectedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(public CompanyAccessRequest $accessRequest, public string $customMessage) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Je aanvraag voor Bedrijvendag is afgewezen');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.company-access-rejected');
    }
}
