<?php

use App\Mail\CompanyInterestMessageMail;
use App\Models\BorrelEnrollment;
use App\Models\Company;
use App\Models\CompanyAccessRequest;
use App\Models\CompanyInterestRequest;
use App\Models\Education;
use App\Models\Event;
use App\Models\NewsletterSubscriber;
use App\Support\Privacy\PrivacyRetention;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia as Assert;

function openBorrelEvent(): Event
{
    return Event::create(['name' => 'Next edition', 'date' => now()->addMonth()->toDateString()]);
}

/* ------------------------------------------------------------------ borrel */

it('registers for the drinks with an email address only and without any marketing subscription', function () {
    $event = openBorrelEvent();

    $this->post('/borrel-signup', ['event_id' => $event->id, 'email' => 'guest@x.example'])->assertRedirect()->assertSessionHasNoErrors();

    $guest = BorrelEnrollment::query()->whereEmail('email', 'guest@x.example')->sole();
    expect($guest->name)->toBeNull()->and($guest->email)->toBe('guest@x.example')
        ->and(NewsletterSubscriber::count())->toBe(0);
});

it('does not accept or store a name sent along by an old client', function () {
    $event = openBorrelEvent();

    $this->post('/borrel-signup', ['event_id' => $event->id, 'email' => 'old@x.example', 'name' => 'Should Not Be Stored'])->assertRedirect();

    expect(BorrelEnrollment::sole()->name)->toBeNull();
});

it('offers an unchecked, optional consent box and the exact text that is recorded', function () {
    openBorrelEvent();

    $this->get('/')->assertInertia(fn (Assert $p) => $p->where('marketingConsentText', __('messages.marketing_consent_text')));

    $vue = file_get_contents(resource_path('js/pages/Home.vue'));
    expect($vue)->toContain('marketing_consent: false')->not->toContain("name: '',\n    email")->not->toContain('borrelForm.name');
});

it('records consent with time, version, text and source when the box is ticked, separate from the registration', function () {
    $event = openBorrelEvent();

    $this->post('/borrel-signup', ['event_id' => $event->id, 'email' => 'Fan@X.example', 'marketing_consent' => true])->assertRedirect();

    $sub = NewsletterSubscriber::query()->whereEmail('email', 'fan@x.example')->sole();
    expect($sub->subscribed_at)->not->toBeNull()->and($sub->unsubscribed_at)->toBeNull()
        ->and($sub->consent_version)->toBe(config('privacy.marketing_consent.version'))
        ->and($sub->consent_text)->toBe(__('messages.marketing_consent_text'))->and($sub->consent_source)->toBe('borrel')
        ->and(DB::table('newsletter_subscribers')->value('email'))->not->toContain('x.example')
        ->and(NewsletterSubscriber::subscribed()->count())->toBe(1);
    // nothing event specific lives on the subscription
    expect(Schema::hasColumn('newsletter_subscribers', 'event_id'))->toBeFalse();
});

it('does not subscribe when the box is not ticked, even for an address that is already subscribed elsewhere', function () {
    $event = openBorrelEvent();
    NewsletterSubscriber::giveConsent('both@x.example', 'website');
    $before = NewsletterSubscriber::sole()->consent_source;

    $this->post('/borrel-signup', ['event_id' => $event->id, 'email' => 'both@x.example'])->assertRedirect();

    expect(NewsletterSubscriber::sole()->consent_source)->toBe($before)->and(BorrelEnrollment::count())->toBe(1);
});

it('keeps an independently valid subscription when the event email is cleaned up', function () {
    $event = Event::create(['name' => 'Past', 'date' => now()->subDays(3)->toDateString()]);
    BorrelEnrollment::create(['event_id' => $event->id, 'email' => 'keep@x.example']);
    NewsletterSubscriber::giveConsent('keep@x.example', 'borrel');

    (new PrivacyRetention)->run();

    expect(BorrelEnrollment::sole()->email)->toBeNull()
        ->and(NewsletterSubscriber::subscribed()->count())->toBe(1)
        ->and(NewsletterSubscriber::query()->whereEmail('email', 'keep@x.example')->exists())->toBeTrue();
});

it('allows the same address to register again for the next edition and keeps duplicate detection per event', function () {
    $a = openBorrelEvent();
    $b = Event::create(['name' => 'Later', 'date' => now()->addMonths(2)->toDateString()]);

    $this->post('/borrel-signup', ['event_id' => $a->id, 'email' => 'dup@x.example'])->assertSessionHasNoErrors();
    $this->post('/borrel-signup', ['event_id' => $a->id, 'email' => 'DUP@x.example'])->assertSessionHasErrors('email');
    $this->post('/borrel-signup', ['event_id' => $b->id, 'email' => 'dup@x.example'])->assertSessionHasNoErrors();

    expect(BorrelEnrollment::count())->toBe(2);
});

/* ------------------------------------------------------------------ unsubscribe */

it('unsubscribes through a signed link, only after confirming, and keeps a suppression record', function () {
    $sub = NewsletterSubscriber::giveConsent('bye@x.example', 'website');
    $url = $sub->unsubscribeUrl();

    $this->get($url)->assertOk()->assertSee(__('messages.unsubscribe_button'));
    expect($sub->fresh()->unsubscribed_at)->toBeNull(); // opening the link (a mail scanner) changes nothing

    $store = URL::signedRoute('newsletter.unsubscribe.store', ['subscriber' => $sub->id]);
    $this->post($store)->assertOk()->assertSee(__('messages.unsubscribe_done'));

    expect($sub->fresh()->unsubscribed_at)->not->toBeNull()->and(NewsletterSubscriber::subscribed()->count())->toBe(0)
        ->and(NewsletterSubscriber::count())->toBe(1);
    $this->post($store)->assertOk(); // repeating is harmless
});

it('rejects unsigned or tampered unsubscribe links', function () {
    $sub = NewsletterSubscriber::giveConsent('safe@x.example', 'website');
    $other = NewsletterSubscriber::giveConsent('other@x.example', 'website');

    $this->get("/newsletter/unsubscribe/{$sub->id}")->assertForbidden();
    $this->post("/newsletter/unsubscribe/{$sub->id}")->assertForbidden();
    $this->get(str_replace('/'.$sub->id.'?', '/'.$other->id.'?', $sub->unsubscribeUrl()))->assertForbidden();

    expect($sub->fresh()->unsubscribed_at)->toBeNull()->and($other->fresh()->unsubscribed_at)->toBeNull();
});

it('lets someone re-subscribe after withdrawing, with fresh consent', function () {
    $sub = NewsletterSubscriber::giveConsent('again@x.example', 'website');
    $sub->withdraw();

    NewsletterSubscriber::giveConsent('again@x.example', 'website', __('messages.newsletter_consent_text'));

    $sub = NewsletterSubscriber::sole();
    expect($sub->unsubscribed_at)->toBeNull()->and($sub->consent_text)->toBe(__('messages.newsletter_consent_text'));
});

it('keeps legacy subscribers without a consent record readable and lists them as subscribed', function () {
    DB::table('newsletter_subscribers')->insert(['email' => 'legacy@x.example', 'subscribed_at' => now()->subYear(), 'created_at' => now(), 'updated_at' => now()]);

    $legacy = NewsletterSubscriber::sole();
    expect($legacy->consent_version)->toBeNull()->and(NewsletterSubscriber::subscribed()->count())->toBe(1);
});

/* ------------------------------------------------------------------ access and interest requests */

it('accepts a company access request without a name and keeps matching and approval working', function () {
    Mail::fake();
    $education = Education::create(['name' => 'ICT']);

    $this->post(route('company-access.store'), [
        'type' => CompanyAccessRequest::TYPE_NEW, 'company_name' => 'Nameless BV', 'description' => 'We build.', 'education_ids' => [$education->id],
        'logo' => Illuminate\Http\UploadedFile::fake()->image('logo.png'), 'contact_email' => 'info@nameless.example',
    ])->assertSessionHasNoErrors();

    $request = CompanyAccessRequest::sole();
    expect($request->contact_name)->toBeNull()->and($request->contact_email)->toBe('info@nameless.example');

    // duplicate detection still blocks an existing company name
    Company::create(['name' => 'Taken BV']);
    $this->post(route('company-access.store'), ['type' => CompanyAccessRequest::TYPE_NEW, 'company_name' => 'Taken BV', 'description' => 'x', 'education_ids' => [$education->id],
        'logo' => Illuminate\Http\UploadedFile::fake()->image('l.png'), 'contact_email' => 'a@taken.example'])->assertSessionHasErrors('company_name');
});

it('still accepts a legacy client that sends a name for access and interest requests, stored encrypted', function () {
    Mail::fake();
    $company = Company::create(['name' => 'Existing BV']);

    $this->post(route('company-access.store'), ['type' => CompanyAccessRequest::TYPE_EXISTING, 'company_id' => $company->id, 'contact_name' => 'Legacy Lena', 'contact_email' => 'l@existing.example'])->assertSessionHasNoErrors();
    $this->post(route('company-interest.store'), ['company_name' => 'Interested BV', 'contact_name' => 'Legacy Leo', 'contact_email' => 'leo@interested.example'])->assertSessionHasNoErrors();

    expect(CompanyAccessRequest::sole()->contact_name)->toBe('Legacy Lena')
        ->and(DB::table('company_access_requests')->value('contact_name'))->not->toContain('Lena')
        ->and(CompanyInterestRequest::sole()->contact_name)->toBe('Legacy Leo');
});

it('accepts a company interest request without a name and still notifies the organisation', function () {
    Mail::fake();

    $this->post(route('company-interest.store'), ['company_name' => 'Anon BV', 'contact_email' => 'hi@anon.example', 'message' => 'Interested'])->assertSessionHasNoErrors();

    $request = CompanyInterestRequest::sole();
    expect($request->contact_name)->toBeNull();
    Mail::assertSent(CompanyInterestMessageMail::class, fn ($m) => $m->hasReplyTo('hi@anon.example'));
});

it('no longer shows a name field on the three public forms', function () {
    $home = file_get_contents(resource_path('js/pages/Home.vue'));
    $access = file_get_contents(resource_path('js/pages/CompanyAccess/RequestCompanyAccess.vue'));
    $interest = file_get_contents(resource_path('js/pages/CompanyInterest/RegisterCompanyInterest.vue'));

    expect($home)->not->toContain('id="borrel-name"')
        ->and($access)->not->toContain('contact_name')
        ->and($interest)->not->toContain('contact_name');
});

/* ------------------------------------------------------------------ consent integrity, transactional mail, cleanup */

it('never overwrites an existing consent record when the same address signs up again, in any letter case', function () {
    $first = NewsletterSubscriber::giveConsent('Once@x.example', 'website', 'first text');
    $stamp = $first->subscribed_at->toDateTimeString();
    $this->travel(2)->days();

    NewsletterSubscriber::giveConsent('ONCE@x.example', 'borrel');

    $row = NewsletterSubscriber::sole();
    expect($row->consent_text)->toBe('first text')->and($row->consent_source)->toBe('website')->and($row->subscribed_at->toDateTimeString())->toBe($stamp);
});

it('fills in the consent record of a legacy subscriber without replacing the original subscription time', function () {
    DB::table('newsletter_subscribers')->insert(['email' => App\Support\Privacy\EmailCrypto::encrypt('old@x.example'), 'email_hash' => App\Support\Privacy\EmailCrypto::hash('old@x.example'), 'subscribed_at' => '2025-01-01 10:00:00', 'created_at' => now(), 'updated_at' => now()]);

    NewsletterSubscriber::giveConsent('old@x.example', 'website');

    $row = NewsletterSubscriber::sole();
    expect($row->consent_version)->not->toBeNull()->and($row->subscribed_at->toDateString())->toBe('2025-01-01');
});

it('does not resubscribe an unsubscribed address through a borrel registration without the box ticked', function () {
    $event = openBorrelEvent();
    NewsletterSubscriber::giveConsent('gone@x.example', 'website')->withdraw();

    $this->post('/borrel-signup', ['event_id' => $event->id, 'email' => 'gone@x.example'])->assertRedirect();

    expect(NewsletterSubscriber::sole()->unsubscribed_at)->not->toBeNull()->and(NewsletterSubscriber::subscribed()->count())->toBe(0)->and(BorrelEnrollment::count())->toBe(1);
});

it('only resubscribes after a fresh, explicit consent and records the new wording', function () {
    $event = openBorrelEvent();
    NewsletterSubscriber::giveConsent('back@x.example', 'website')->withdraw();

    $this->post('/borrel-signup', ['event_id' => $event->id, 'email' => 'back@x.example', 'marketing_consent' => true])->assertRedirect();

    $row = NewsletterSubscriber::sole();
    expect($row->unsubscribed_at)->toBeNull()->and($row->consent_source)->toBe('borrel');
});

it('keeps sending the necessary event emails after marketing consent is withdrawn', function () {
    Mail::fake();
    NewsletterSubscriber::giveConsent('jane@acme.example', 'website')->withdraw();

    [$enrollment] = approvedEnrollment(); // contact jane@acme.example, mail faked inside

    Mail::assertQueued(App\Mail\EnrollmentApprovedMail::class, fn ($m) => $m->hasTo('jane@acme.example'));
    expect($enrollment->status)->toBe('approved');
});

it('runs the retention cleanup, dry-run first, without touching any newsletter row', function () {
    $past = Event::create(['name' => 'Past', 'date' => now()->subDays(40)->toDateString()]);
    BorrelEnrollment::create(['event_id' => $past->id, 'email' => 'event@x.example']);
    $active = NewsletterSubscriber::giveConsent('active@x.example', 'website');
    $gone = NewsletterSubscriber::giveConsent('withdrawn@x.example', 'website');
    $gone->withdraw();
    $before = DB::table('newsletter_subscribers')->get()->map(fn ($r) => (array) $r)->all();

    $dry = (new PrivacyRetention)->run(true);
    expect($dry['borrel_guests_purged'])->toBe(1)->and(BorrelEnrollment::sole()->email)->toBe('event@x.example')
        ->and(DB::table('newsletter_subscribers')->get()->map(fn ($r) => (array) $r)->all())->toBe($before);

    (new PrivacyRetention)->run();

    expect(BorrelEnrollment::sole()->email)->toBeNull()
        ->and(DB::table('newsletter_subscribers')->get()->map(fn ($r) => (array) $r)->all())->toBe($before)
        ->and(NewsletterSubscriber::subscribed()->count())->toBe(1);
});

it('keeps queued access-request mail free of plaintext addresses', function () {
    config(['queue.default' => 'database']);
    $request = CompanyAccessRequest::create(['type' => CompanyAccessRequest::TYPE_EXISTING, 'status' => CompanyAccessRequest::STATUS_PENDING, 'company_id' => Company::create(['name' => 'Q BV'])->id, 'company_name' => 'Q BV', 'contact_email' => 'queue-check@x.example', 'submitted_at' => now()]);

    $request->reject('note', 'Sorry, not this time.');

    $payload = DB::table('jobs')->pluck('payload')->implode('|');
    expect($payload)->not->toBe('')->and($payload)->not->toContain('queue-check');
});
