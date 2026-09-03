<?php

namespace App\Mail;

use App\Models\CompanyProfileSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CompanyProfileApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public CompanyProfileSubmission $submission)
    {
        $this->submission->loadMissing('company');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Jullie Bedrijvendag bedrijfsprofiel is goedgekeurd',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.company-profile-approved',
        );
    }
}
