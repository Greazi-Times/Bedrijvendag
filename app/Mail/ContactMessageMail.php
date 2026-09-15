<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactMessageMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array{name: string, email: string, phone?: string|null, subject: string, message: string}  $contactMessage
     */
    public function __construct(public array $contactMessage) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            replyTo: [new Address($this->contactMessage['email'], $this->contactMessage['name'])],
            subject: 'Contactformulier: '.$this->contactMessage['subject'],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.contact-message',
        );
    }
}
