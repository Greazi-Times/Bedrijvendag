<?php

namespace App\Mail;

use App\Models\CompanyProfileSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CompanyProfileRejectedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public CompanyProfileSubmission $submission)
    {
        $this->submission->loadMissing('company');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Jullie Bedrijvendag bedrijfsprofiel vraagt nog aandacht',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.company-profile-rejected',
        );
    }
}
