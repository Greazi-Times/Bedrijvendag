<?php

use App\Filament\Resources\Enrollments\Pages\ViewEnrollment;
use App\Mail\EnrollmentContactProposedMail;
use App\Mail\EnrollmentContactVerificationMail;
use App\Mail\EnrollmentInfoReceivedMail;
use App\Mail\EnrollmentInvitationMail;
use App\Models\EnrollmentContactChange;
use App\Services\ContactVerification;
use App\Services\InvitationService;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;
use Livewire\Livewire;

/** Submits the second form naming a different contact; returns the plain verification token from the queued mail. */
function proposeContact(string $token, $event, string $name, string $email): ?string
{
    Mail::fake();
    submitInfo($token, $event, ['contact_name' => $name, 'contact_email' => $email]);
    $plain = null;
    Mail::assertQueued(EnrollmentContactVerificationMail::class, function ($m) use (&$plain, $email) {
        if ($m->hasTo($email)) {
            $plain = $m->token;
        }

        return true;
    });

    return $plain;
}

test('without a second form the operational contact is the original one', function () {
    [$e] = approvedEnrollment();

    $c = $e->operationalContact();

    expect($c['email'])->toBe('jane@acme.example')->and($c['source'])->toBe('original')->and($c['differs'])->toBeFalse()->and($c['pending'])->toBeNull();
});

test('a new address on the second form is an unverified proposal and gets no operational mail', function () {
    [$e, $token, $event] = approvedEnrollment();
    Mail::fake();

    submitInfo($token, $event, ['contact_name' => 'Planner Pat', 'contact_email' => 'planner@acme.example']);

    $c = $e->fresh()->operationalContact();
    expect($c['email'])->toBe('jane@acme.example')->and($c['source'])->toBe('original')
        ->and($c['pending']['email'])->toBe('planner@acme.example')
        ->and($e->fresh()->contact_email)->toBe('jane@acme.example');

    // the confirmation and the "someone proposed a contact" notice go to the trusted contact only
    Mail::assertQueued(EnrollmentInfoReceivedMail::class, fn ($m) => $m->hasTo('jane@acme.example') && ! $m->hasTo('planner@acme.example'));
    Mail::assertQueued(EnrollmentContactProposedMail::class, fn ($m) => $m->hasTo('jane@acme.example'));
    // the proposed address gets the verification message and nothing else
    Mail::assertQueued(EnrollmentContactVerificationMail::class, fn ($m) => $m->hasTo('planner@acme.example'));
    Mail::assertQueued(EnrollmentInfoReceivedMail::class, 1);
    Mail::assertNotQueued(EnrollmentInvitationMail::class);
});

test('the verification mail carries no enrollment details beyond company and edition and is encrypted in the queue', function () {
    [$e, $token, $event] = approvedEnrollment(null, ['invoice_address' => 'Secret Street 99, 1234AB Breda, NL']);
    $plain = proposeContact($token, $event, 'Planner Pat', 'planner@acme.example');

    $html = (new EnrollmentContactVerificationMail($e->load('event'), $plain, 'Planner Pat'))->render();

    expect($html)->toContain('Acme BV')->toContain($e->event->name)->toContain(route('contact.verify', $plain))
        ->not->toContain('Secret Street')->not->toContain('jane@acme.example')->not->toContain('fin@acme.example');
    expect(EnrollmentContactVerificationMail::class)->toImplement(ShouldBeEncrypted::class);
});

test('verifying the address makes it the current contact, replacing the original for mail', function () {
    [$e, $token, $event] = approvedEnrollment();
    $plain = proposeContact($token, $event, 'Planner Pat', 'planner@acme.example');

    // opening the link changes nothing
    $this->get(route('contact.verify', $plain))->assertInertia(fn (Assert $p) => $p->where('state', 'ready')->where('company', 'Acme BV'));
    expect($e->fresh()->operationalContact()['source'])->toBe('original');

    $this->post(route('contact.verify.store', $plain))->assertRedirect(route('contact.verify', $plain));

    $c = $e->fresh()->operationalContact();
    expect($c['email'])->toBe('planner@acme.example')->and($c['name'])->toBeNull()->and($c['source'])->toBe('verified')->and($c['pending'])->toBeNull()
        ->and($e->fresh()->contact_email)->toBe('jane@acme.example');

    // reopening the link (or confirming twice) only says "confirmed"; it changes nothing
    $this->get(route('contact.verify', $plain))->assertInertia(fn (Assert $p) => $p->where('state', 'verified'));
    $this->post(route('contact.verify.store', $plain));
    expect(EnrollmentContactChange::count())->toBe(1)->and($e->fresh()->operationalContact()['email'])->toBe('planner@acme.example');

    // later confirmations go to the verified contact
    Mail::fake();
    submitInfo($token, $event, ['contact_name' => 'Planner Pat', 'contact_email' => 'planner@acme.example', 'drinks_attending' => 'unsure']);
    Mail::assertQueued(EnrollmentInfoReceivedMail::class, fn ($m) => $m->hasTo('planner@acme.example'));
});

test('invalid, expired and superseded verification links change nothing', function () {
    [$e, $token, $event] = approvedEnrollment();
    $first = proposeContact($token, $event, 'Planner Pat', 'planner@acme.example');

    $this->get(route('contact.verify', 'x'.str_repeat('a', 47)))->assertInertia(fn (Assert $p) => $p->where('state', 'invalid')->where('company', null));
    $this->post(route('contact.verify.store', 'short'))->assertRedirect();

    // a second, different proposal replaces the first (and the first link is then dead)
    $second = proposeContact($token, $event, 'Other Person', 'other@acme.example');
    $this->post(route('contact.verify.store', $first));
    expect($e->fresh()->operationalContact()['source'])->toBe('original');

    // expired
    EnrollmentContactChange::whereNull('superseded_at')->update(['expires_at' => now()->subMinute()]);
    $this->get(route('contact.verify', $second))->assertInertia(fn (Assert $p) => $p->where('state', 'expired')->where('name', null)->where('company', null));
    $this->post(route('contact.verify.store', $second));
    expect($e->fresh()->operationalContact()['source'])->toBe('original')->and($e->fresh()->operationalContact()['pending'])->toBeNull();
});

test('repeating the same proposal does not send more verification mail', function () {
    [$e, $token, $event] = approvedEnrollment();
    proposeContact($token, $event, 'Planner Pat', 'planner@acme.example');
    Mail::fake();

    submitInfo($token, $event, ['contact_name' => 'Planner Pat', 'contact_email' => 'planner@acme.example']); // identical revision: nothing
    submitInfo($token, $event, ['contact_name' => 'Planner Pat', 'contact_email' => 'planner@acme.example', 'drinks_attending' => 'unsure']); // new revision, same proposal

    Mail::assertNotQueued(EnrollmentContactVerificationMail::class);
    expect(EnrollmentContactChange::count())->toBe(1);
});

test('submitting the already trusted address again needs no verification', function () {
    [$e, $token, $event] = approvedEnrollment();
    Mail::fake();

    submitInfo($token, $event, ['contact_email' => 'JANE@acme.example']);

    $c = $e->fresh()->operationalContact();
    expect($c['email'])->toBe('jane@acme.example')->and($c['source'])->toBe('original')->and($c['pending'])->toBeNull();
    Mail::assertNotQueued(EnrollmentContactVerificationMail::class);
    Mail::assertNotQueued(EnrollmentContactProposedMail::class);
});

test('the information form works and the contact is identified by address alone when no name is asked', function () {
    [$e, $token, $event] = approvedEnrollment();
    Mail::fake();

    submitInfo($token, $event, ['contact_email' => 'planner@acme.example']);

    $change = EnrollmentContactChange::sole();
    expect($change->name)->toBeNull()->and($change->email)->toBe('planner@acme.example')->and($change->verified_at)->toBeNull();
    Mail::assertQueued(EnrollmentContactVerificationMail::class, fn ($m) => $m->hasTo('planner@acme.example') && $m->contactName === null);
    Mail::assertQueued(EnrollmentContactProposedMail::class, fn ($m) => $m->hasTo('jane@acme.example') && $m->proposedName === null);
    expect($e->fresh()->operationalContact()['email'])->toBe('jane@acme.example'); // unverified: mail still goes to the trusted contact
});

test('contact mails without a name render a neutral greeting and the address', function () {
    [$e] = approvedEnrollment();

    $verify = (new EnrollmentContactVerificationMail($e->load('event'), str_repeat('a', 48)))->render();
    $proposed = (new EnrollmentContactProposedMail($e, null, null, 'planner@acme.example'))->render();

    expect($verify)->toContain('Beste relatie,')->and($proposed)->toContain('Beste relatie,')->toContain('planner@acme.example')->not->toContain('()');
});

test('a name that a customised form still collects is kept, encrypted, and a changed name for a trusted address is recorded without verification', function () {
    [$e] = approvedEnrollment();
    Mail::fake();

    $change = app(ContactVerification::class)->propose($e, 'Jane Q. Doe', 'JANE@acme.example');

    expect($change->verified_by)->toBe('trusted_email')->and($e->fresh()->operationalContact()['name'])->toBe('Jane Q. Doe')
        ->and(DB::table('enrollment_contact_changes')->value('name'))->not->toContain('Jane');
    Mail::assertNotQueued(EnrollmentContactVerificationMail::class);
});

test('a verified address brings no name along from the previous contact', function () {
    [$e, $token, $event] = approvedEnrollment(null, ['contact_name' => 'Jane']);
    $plain = proposeContact($token, $event, '', 'planner@acme.example');

    $this->post(route('contact.verify.store', $plain));

    expect($e->fresh()->operationalContact())->toMatchArray(['email' => 'planner@acme.example', 'name' => null, 'source' => 'verified']);
});

test('a link holder cannot redirect mail: resend and invitation mails stay with the trusted contact until verified', function () {
    [$e, $token, $event] = approvedEnrollment();
    proposeContact($token, $event, 'Attacker', 'attacker@evil.example');
    Mail::fake();

    $e->invitations()->update(['created_at' => now()->subMinutes(10)]);
    app(InvitationService::class)->reissue($e->fresh());

    Mail::assertQueued(EnrollmentInvitationMail::class, fn ($m) => $m->hasTo('jane@acme.example') && ! $m->hasTo('attacker@evil.example'));
});

test('the admin page distinguishes original, current, proposed and verified contacts', function () {
    [$e, $token, $event] = approvedEnrollment();
    $this->actingAs(reviewer());

    $plain = proposeContact($token, $event, 'Planner Pat', 'planner@acme.example');
    Livewire::test(ViewEnrollment::class, ['record' => $e->getKey()])
        ->assertSee('Original enrollment contact')->assertSee('jane@acme.example')->assertSee('trusted, never changed')
        ->assertSee('Current event contact')->assertSee('same as original')
        ->assertSee('Proposed contact')->assertSee('planner@acme.example')->assertSee('unverified')->assertSee('delivery is not tracked');

    app(ContactVerification::class)->verify($plain);
    Livewire::test(ViewEnrollment::class, ['record' => $e->getKey()])
        ->assertSee('verified')->assertSee('None pending')->assertSee('verified (by email link)');
});

test('the tokens of invitations and verifications are never stored in plain text', function () {
    [$e, $token, $event] = approvedEnrollment();
    $plain = proposeContact($token, $event, 'Planner Pat', 'planner@acme.example');

    $dump = json_encode([DB::table('enrollment_invitations')->get(), DB::table('enrollment_contact_changes')->get(), DB::table('activity_log')->get()]);

    expect($dump)->not->toContain($token)->not->toContain($plain);
});
