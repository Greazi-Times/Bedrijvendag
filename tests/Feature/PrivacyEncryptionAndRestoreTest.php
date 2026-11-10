<?php

use App\Models\BorrelEnrollment;
use App\Models\Enrollment;
use App\Models\NewsletterSubscriber;
use App\Models\User;
use App\Services\EnrollmentWorkflow;
use App\Support\Privacy\EmailBackfill;
use App\Support\Privacy\EmailCrypto;
use App\Support\Privacy\ErasureLedger;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Grammars\MySqlGrammar;
use Illuminate\Support\Facades\DB;

function key32(): string
{
    return 'base64:'.base64_encode(random_bytes(32));
}

function useKeys(?string $enc, ?string $look, string $prevEnc = '', string $prevLook = ''): void
{
    config(['privacy.email.encryption_key' => $enc, 'privacy.email.lookup_key' => $look, 'privacy.email.previous_encryption_keys' => $prevEnc, 'privacy.email.previous_lookup_keys' => $prevLook]);
    EmailCrypto::flush();
}

it('fails safely in production without keys and rejects invalid keys', function () {
    useKeys(null, null);
    app()->detectEnvironment(fn () => 'production');

    expect(fn () => EmailCrypto::encrypt('a@b.nl'))->toThrow(RuntimeException::class, 'EMAIL_ENCRYPTION_KEY');
    expect(fn () => EmailCrypto::hash('a@b.nl'))->toThrow(RuntimeException::class, 'EMAIL_LOOKUP_KEY');

    app()->detectEnvironment(fn () => 'testing');
    useKeys('base64:!!!notbase64', key32());
    expect(fn () => EmailCrypto::encrypt('a@b.nl'))->toThrow(RuntimeException::class);

    useKeys('base64:'.base64_encode('short'), key32());
    expect(fn () => EmailCrypto::encrypt('a@b.nl'))->toThrow(RuntimeException::class);
});

it('does not decrypt with the wrong key and never silently returns ciphertext as an address', function () {
    useKeys(key32(), key32());
    $cipher = EmailCrypto::encrypt('a@b.nl');
    useKeys(key32(), key32());

    expect(EmailCrypto::isEncrypted($cipher))->toBeFalse();
});

it('rotates encryption and lookup keys, supports restart after an interrupted rotation and recovery from backed-up keys', function () {
    $old = [key32(), key32()];
    useKeys($old[0], $old[1]);
    $a = BorrelEnrollment::create(['event_id' => openEvent()->id, 'name' => 'A', 'email' => 'a@x.example']);
    $b = BorrelEnrollment::create(['event_id' => openEvent(['name' => 'E2', 'date' => '2028-01-01'])->id, 'name' => 'B', 'email' => 'b@x.example']);
    $oldCipher = DB::table('borrel_enrollments')->where('id', $a->id)->value('email');

    $new = [key32(), key32()];
    useKeys($new[0], $new[1], $old[0], $old[1]);
    expect(BorrelEnrollment::whereEmail('email', 'A@x.example')->count())->toBe(1) // old hash still found during rotation
        ->and(BorrelEnrollment::find($a->id)->email)->toBe('a@x.example');

    // Interrupted: only the first record is rotated.
    DB::table('borrel_enrollments')->where('id', $a->id)->update(['email' => EmailCrypto::encrypt('a@x.example'), 'email_hash' => EmailCrypto::hash('a@x.example')]);
    expect(BorrelEnrollment::find($b->id)->email)->toBe('b@x.example'); // the rest still readable via previous keys

    (new EmailBackfill)->run(rotate: true);
    useKeys($new[0], $new[1]); // old keys removed after completion
    expect(BorrelEnrollment::find($a->id)->email)->toBe('a@x.example')->and(BorrelEnrollment::find($b->id)->email)->toBe('b@x.example')
        ->and(BorrelEnrollment::whereEmail('email', 'b@x.example')->count())->toBe(1);

    // Recovery: a restored backup holding the old ciphertext is readable again with the backed-up old keys.
    useKeys($old[0], $old[1]);
    expect(EmailCrypto::decrypt($oldCipher))->toBe('a@x.example');
});

it('backfills legacy plaintext, never prints addresses, and resumes after interruption', function () {
    $event = openEvent();
    DB::table('newsletter_subscribers')->insert([
        ['email' => 'one@x.example', 'created_at' => now(), 'updated_at' => now()],
        ['email' => 'two@x.example', 'created_at' => now(), 'updated_at' => now()],
    ]);

    $dry = (new EmailBackfill)->run(dryRun: true);
    expect($dry['newsletter_subscribers']['encrypted'])->toBe(2)
        ->and(DB::table('newsletter_subscribers')->pluck('email')->all())->toBe(['one@x.example', 'two@x.example']);

    // Interrupt: only the first row converted by hand, then the full run completes the rest.
    $first = DB::table('newsletter_subscribers')->first();
    DB::table('newsletter_subscribers')->where('id', $first->id)->update(['email' => EmailCrypto::encrypt($first->email), 'email_hash' => EmailCrypto::hash($first->email)]);
    $run = (new EmailBackfill)->run();

    expect($run['newsletter_subscribers']['encrypted'])->toBe(1)->and($run['newsletter_subscribers']['remaining_plaintext'])->toBe(0)
        ->and((new EmailBackfill)->run()['newsletter_subscribers']['encrypted'])->toBe(0);

    $this->artisan('privacy:encrypt-emails --dry-run')->doesntExpectOutputToContain('x.example')->assertSuccessful();
});

it('keeps queued approval mail free of plaintext addresses and tokens, and still delivers', function () {
    config(['queue.default' => 'database']);
    $event = openEvent();
    $e = submitEnrollment($event, ['contact_email' => 'leak-check@acme.example']);
    app(EnrollmentWorkflow::class)->approve($e, reviewer(), ['mode' => 'new']);

    $payloads = DB::table('jobs')->pluck('payload')->implode('|');
    expect($payloads)->not->toBe('')->and($payloads)->not->toContain('leak-check')->and($payloads)->not->toContain('acme.example');
});

it('replays erasures after restoring an older backup without touching newer enrollments', function () {
    $event = openEvent();
    $old = submitEnrollment($event, ['contact_email' => 'erase-me@acme.example']);
    $keep = submitEnrollment($event, ['contact_email' => 'keep@acme.example', 'company_name' => 'Keep BV']);
    $oldId = $old->id;
    $backupRow = (array) DB::table('enrollments')->where('id', $oldId)->first();

    $old->delete(); // privacy request: recorded in the ledger file outside the database
    expect(ErasureLedger::entries())->not->toBeEmpty()->and(file_get_contents(config('privacy.erasure_ledger.path')))->not->toContain('erase-me');

    // "Restore" an older backup: the row is back and the erasure table row is gone, but the ledger file survives.
    DB::table('privacy_erasures')->delete();
    DB::table('enrollments')->insert($backupRow);
    DB::table('enrollments')->where('id', $oldId)->update(['created_at' => now()->subMonth()]);

    expect(ErasureLedger::replay(true)['enrollments_erased'])->toBe(1)->and(Enrollment::find($oldId))->not->toBeNull(); // dry run changes nothing

    expect(ErasureLedger::replay()['enrollments_erased'])->toBe(1)->and(Enrollment::find($oldId))->toBeNull()->and(Enrollment::find($keep->id))->not->toBeNull()
        ->and(ErasureLedger::replay()['enrollments_erased'])->toBe(0);
});

it('does not erase a newer enrollment that reuses an erased id', function () {
    $event = openEvent();
    $e = submitEnrollment($event);
    $id = $e->id;
    $e->delete();

    $fresh = submitEnrollment($event, ['contact_email' => 'new@x.example']);
    DB::table('enrollments')->where('id', $fresh->id)->update(['id' => $id, 'created_at' => now()->addDay()]);

    expect(ErasureLedger::replay()['enrollments_erased'])->toBe(0)->and(Enrollment::find($id))->not->toBeNull();
});

it('erases by email hash through the command and replays it after a restore', function () {
    $event = openEvent();
    NewsletterSubscriber::create(['email' => 'gone@x.example']);
    BorrelEnrollment::create(['event_id' => $event->id, 'name' => 'G', 'email' => 'Gone@x.example']);

    $this->artisan('privacy:erase-email gone@x.example')->assertSuccessful();
    expect(NewsletterSubscriber::count())->toBe(0)->and(BorrelEnrollment::first()->email)->toBeNull()
        ->and(file_get_contents(config('privacy.erasure_ledger.path')))->not->toContain('gone@x.example');

    NewsletterSubscriber::create(['email' => 'gone@x.example']); // as if restored
    DB::table('newsletter_subscribers')->update(['created_at' => now()->subYear()]);
    $this->artisan('privacy:replay-erasures')->assertSuccessful();
    expect(NewsletterSubscriber::count())->toBe(0);
});

it('prunes ledger entries older than the backup window', function () {
    $e = submitEnrollment(openEvent());
    $e->delete();
    DB::table('privacy_erasures')->update(['erased_at' => now()->subDays(40)]);
    file_put_contents(config('privacy.erasure_ledger.path'), json_encode(['type' => 'enrollment', 'id' => 1, 'hash' => null, 'at' => now()->subDays(40)->toIso8601String()])."\n");

    ErasureLedger::prune();

    expect(ErasureLedger::entries())->toBe([]);
});

it('generates MySQL DDL for the privacy migration without error (offline; not a server run)', function () {
    $source = file_get_contents(base_path('database/migrations/2026_11_20_000001_prepare_privacy_retention_and_email_encryption.php'));
    $connection = new Illuminate\Database\MySqlConnection(fn () => null, 'x');
    $grammar = new MySqlGrammar($connection);
    $connection->setSchemaGrammar($grammar);

    // Compile the new-table blueprints and the index definitions against the MySQL grammar.
    $blueprint = new Blueprint($connection, 'privacy_erasures', function (Blueprint $table) {
        $table->create();
        $table->id();
        $table->string('subject_type', 60);
        $table->unsignedBigInteger('subject_id')->nullable();
        $table->char('subject_hash', 64)->nullable();
        $table->unique(['subject_type', 'subject_id']);
        $table->index('subject_hash');
    });
    $sql = implode(';', $blueprint->toSql($grammar));

    expect($sql)->toContain('create table `privacy_erasures`')->and($sql)->toContain('unique')
        ->and($source)->not->toMatch('/->(delete|truncate)\(|DB::table\(.*\)->update/');
});

it('lists accounts for review without changing them and flags a due review', function () {
    $u = User::factory()->create();

    $this->artisan('privacy:account-review')->expectsOutputToContain('review is due')->assertSuccessful();
    $this->artisan('privacy:account-review --confirm')->assertSuccessful();
    $this->artisan('privacy:account-review')->expectsOutputToContain('No review is due')->assertSuccessful();

    expect(User::find($u->id))->not->toBeNull();
});

it('keeps erasure entries matching after the lookup key is rotated', function () {
    useKeys(key32(), key32());
    $event = openEvent();
    NewsletterSubscriber::create(['email' => 'rot@x.example']);
    ErasureLedger::recordEmail('rot@x.example');
    NewsletterSubscriber::query()->delete();

    useKeys(key32(), key32()); // lookup key rotated; the erasure key is stable (config unchanged)
    NewsletterSubscriber::create(['email' => 'ROT@x.example']);
    DB::table('newsletter_subscribers')->update(['created_at' => now()->subYear()]);

    expect(ErasureLedger::replay()['email_records_erased'])->toBe(1)->and(NewsletterSubscriber::count())->toBe(0);
});

it('refuses to replay when the ledger file is missing unless explicitly told otherwise', function () {
    @unlink(config('privacy.erasure_ledger.path'));

    $this->artisan('privacy:replay-erasures')->expectsOutputToContain('ledger file was not found')->assertFailed();
    $this->artisan('privacy:replay-erasures --without-ledger')->assertSuccessful();
});
