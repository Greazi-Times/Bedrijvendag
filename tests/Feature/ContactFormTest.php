<?php

use App\Mail\ContactMessageMail;
use Illuminate\Support\Facades\Mail;

test('contact form emails the submitted message', function () {
    Mail::fake();

    config(['mail.contact_to.address' => 'organisation@example.com']);

    $this->post(route('contact.store'), [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'phone' => '+31 6 12345678',
        'subject' => 'Question about the event',
        'message' => 'When will registration open?',
    ])->assertRedirect();

    Mail::assertSent(ContactMessageMail::class, function (ContactMessageMail $mail): bool {
        return $mail->hasTo('organisation@example.com')
            && $mail->hasReplyTo('jane@example.com')
            && $mail->contactMessage['message'] === 'When will registration open?';
    });
});

test('contact form validates required fields', function () {
    Mail::fake();

    $this->post(route('contact.store'))
        ->assertSessionHasErrors(['name', 'email', 'subject', 'message']);

    Mail::assertNothingSent();
});
