<?php

namespace App\Mail;

use App\Models\CompanyInterestRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CompanyInterestMessageMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public CompanyInterestRequest $interestRequest)
    {
        $this->interestRequest->loadMissing('event');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            replyTo: [new Address($this->interestRequest->contact_email, $this->interestRequest->contact_name)],
            subject: 'Bedrijfsinteresse: '.$this->interestRequest->company_name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.company-interest-message',
        );
    }
}
