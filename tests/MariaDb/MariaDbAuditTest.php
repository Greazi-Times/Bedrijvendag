<?php

/*
| Integration checks on a real MariaDB/MySQL server. NOT part of the default suites (those use in-memory SQLite and may
| wipe their database). Run on purpose, against a development/test database only:
|
|   MARIADB_AUDIT=1 DB_CONNECTION=mysql DB_DATABASE=<name ending in _test> php artisan test tests/MariaDb
|
| Safety: refuses to run unless the flag is set and the database name ends in "_test"; every test runs inside a transaction
| that is rolled back (data only, no DDL), uses synthetic records, never calls the global retention run for real and never
| touches files. Auto-increment counters advance by the rows inserted (not rolled back by InnoDB).
*/

use App\Models\BorrelEnrollment;
use App\Models\CompanyAccessRequest;
use App\Models\CompanyInterestRequest;
use App\Models\Event;
use App\Models\NewsletterSubscriber;
use App\Support\Privacy\PrivacyRetention;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

uses(Tests\TestCase::class, DatabaseTransactions::class);

beforeEach(function () {
    $database = DB::connection()->getDatabaseName();

    if (getenv('MARIADB_AUDIT') !== '1' || ! in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true) || ! str_ends_with($database, '_test')) {
        $this->markTestSkipped('MariaDB audit is opt-in and only runs on a *_test MySQL/MariaDB database.');
    }
});

function liveEvent(string $name = 'Audit edition'): Event
{
    return Event::create(['name' => $name.' '.uniqid(), 'date' => now()->addMonths(3)->toDateString(), 'description' => ['type' => 'doc', 'content' => []], 'enrollment_enabled' => true, 'fee_cents' => 1000, 'currency' => 'EUR', 'terms_html' => '<p>t</p>'])->refresh(); // column defaults (terms_version) are read back
}

it('runs on MariaDB/MySQL with a strict SQL mode', function () {
    $row = DB::selectOne('select @@sql_mode as m, @@version as v');

    expect($row->m)->toContain('STRICT_TRANS_TABLES')->and(DB::connection()->getDriverName())->toBeIn(['mysql', 'mariadb']);
});

it('stores borrel emails encrypted, enforces uniqueness per event on the hash and allows repeated NULL hashes', function () {
    $a = liveEvent();
    $b = liveEvent('Other');

    $guest = BorrelEnrollment::create(['event_id' => $a->id, 'email' => 'Audit.Guest@Example.test']);
    expect(DB::table('borrel_enrollments')->where('id', $guest->id)->value('email'))->not->toContain('example.test')
        ->and(strlen(DB::table('borrel_enrollments')->where('id', $guest->id)->value('email_hash')))->toBe(64)
        ->and(BorrelEnrollment::where('event_id', $a->id)->whereEmail('email', ' audit.guest@example.TEST ')->exists())->toBeTrue();

    expect(fn () => DB::transaction(fn () => BorrelEnrollment::create(['event_id' => $a->id, 'email' => 'audit.guest@example.test'])))->toThrow(QueryException::class);
    BorrelEnrollment::create(['event_id' => $b->id, 'email' => 'audit.guest@example.test']); // other event: allowed

    foreach (range(1, 3) as $i) {
        DB::table('borrel_enrollments')->insert(['event_id' => $a->id, 'name' => null, 'email' => null, 'email_hash' => null, 'created_at' => now(), 'updated_at' => now()]);
    }
    expect(BorrelEnrollment::where('event_id', $a->id)->whereNull('email_hash')->count())->toBe(3);
});

it('keeps one subscription per address, honours withdrawal and never overwrites consent', function () {
    $first = NewsletterSubscriber::giveConsent('Sub.Audit@example.test', 'website', 'first');
    NewsletterSubscriber::giveConsent('SUB.AUDIT@example.test', 'borrel');

    expect(NewsletterSubscriber::query()->whereEmail('email', 'sub.audit@example.test')->count())->toBe(1)
        ->and($first->fresh()->consent_text)->toBe('first');

    $first->withdraw();
    expect(NewsletterSubscriber::subscribed()->whereEmail('email', 'sub.audit@example.test')->exists())->toBeFalse();
    expect(fn () => DB::transaction(fn () => NewsletterSubscriber::create(['email' => 'sub.audit@example.test', 'subscribed_at' => now()])))->toThrow(QueryException::class);
});

it('accepts access and interest requests without names and encrypted names up to a long length', function () {
    $access = CompanyAccessRequest::create(['type' => 'new_company', 'status' => 'pending', 'company_name' => 'Audit BV', 'contact_email' => 'a@example.test', 'submitted_at' => now()]);
    $interest = CompanyInterestRequest::create(['company_name' => 'Audit BV', 'contact_email' => 'i@example.test']);
    $long = CompanyAccessRequest::create(['type' => 'new_company', 'status' => 'pending', 'company_name' => 'Long BV', 'contact_name' => str_repeat('N', 400), 'contact_email' => 'l@example.test', 'submitted_at' => now()]);

    expect($access->fresh()->contact_name)->toBeNull()->and($interest->fresh()->contact_name)->toBeNull()
        ->and($long->fresh()->contact_name)->toBe(str_repeat('N', 400))
        ->and(DB::table('company_access_requests')->where('id', $long->id)->value('contact_name'))->not->toContain('NNNN');
});

it('runs the enrollment, approval and nameless contact change flow, then purges only the personal data', function () {
    Mail::fake();
    $event = liveEvent();
    $payload = enrollmentPayload($event, ['contact_email' => 'flow@example.test', 'company_name' => 'Audit Flow BV']);
    // Forms published before the privacy changes may still ask for a name; this database's global form decides.
    $asksName = collect($event->enrollmentForm->latestVersion->schema['sections'])->flatMap(fn ($s) => $s['fields'])->contains(fn ($f) => ($f['maps_to'] ?? null) === 'contact.name');
    $asksName || $payload['answers'] = array_diff_key($payload['answers'], ['contact_name' => 1]);
    fwrite(STDERR, '[info] global enrollment form still asks for a contact name: '.($asksName ? 'yes' : 'no')."\n");

    $enrollment = app(App\Services\EnrollmentWorkflow::class)->submit($event, $payload);
    $asksName || expect($enrollment->contact_name)->toBeNull();

    app(App\Services\EnrollmentWorkflow::class)->approve($enrollment, reviewer(), ['mode' => 'new']);
    $enrollment = $enrollment->fresh();
    expect($enrollment->status)->toBe('approved')->and($enrollment->company_id)->not->toBeNull();

    $change = app(App\Services\ContactVerification::class)->propose($enrollment, null, 'other-flow@example.test');
    expect($change->name)->toBeNull()->and($change->email)->toBe('other-flow@example.test');

    (new PrivacyRetention)->purge($enrollment);

    $after = $enrollment->fresh();
    expect($after->contact_email)->toBeNull()->and($after->answers)->toBeNull()->and($after->status)->toBe('approved')
        ->and($after->company_id)->toBe($enrollment->company_id)->and($after->event_id)->toBe($event->id)
        ->and(App\Models\Company::find($enrollment->company_id))->not->toBeNull()
        ->and(DB::table('enrollment_contact_changes')->where('enrollment_id', $enrollment->id)->count())->toBe(0);
});

it('previews the retention run without changing any row, including pre-existing ones', function () {
    $tables = ['enrollments', 'enrollment_info_submissions', 'borrel_enrollments', 'newsletter_subscribers', 'company_access_requests', 'companies', 'events'];
    $snapshot = fn () => collect($tables)->mapWithKeys(fn ($t) => [$t => md5(json_encode(DB::table($t)->orderBy('id')->get()))])->all();
    $before = $snapshot();

    $stats = (new PrivacyRetention)->run(true);

    expect($stats)->toHaveKeys(['borrel_guests_purged', 'enrollments_purged_after_event', 'access_requests_purged'])->and($snapshot())->toBe($before);
});

it('reports the encryption state of existing rows without exposing addresses', function () {
    $plain = DB::table('borrel_enrollments')->whereNotNull('email')->where('email', 'not like', 'eyJ%')->count();

    // Informational: legacy plaintext is expected until `privacy:encrypt-emails` has been run on this database.
    expect($plain)->toBeInt();
    fwrite(STDERR, "\n[info] borrel rows still plaintext: {$plain}\n");
});
