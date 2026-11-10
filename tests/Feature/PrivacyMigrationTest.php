<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

function privacyMigration(): object
{
    return require base_path('database/migrations/2026_11_20_000001_prepare_privacy_retention_and_email_encryption.php');
}

function indexOn(string $table, array $columns, ?bool $unique = null): bool
{
    foreach (Schema::getIndexes($table) as $index) {
        if (! $index['primary'] && $index['columns'] === $columns && ($unique === null || (bool) $index['unique'] === $unique)) {
            return true;
        }
    }

    return false;
}

/** The schema the application relies on after this migration. */
function expectIntendedSchema(): void
{
    expect(indexOn('enrollments', ['contact_email_hash'], false))->toBeTrue()
        ->and(indexOn('enrollments', ['contact_email']))->toBeFalse()
        ->and(indexOn('borrel_enrollments', ['event_id', 'email_hash'], true))->toBeTrue()
        ->and(indexOn('borrel_enrollments', ['event_id', 'email']))->toBeFalse()
        ->and(indexOn('newsletter_subscribers', ['email_hash'], true))->toBeTrue()
        ->and(indexOn('newsletter_subscribers', ['email']))->toBeFalse()
        ->and(indexOn('enrollment_contact_changes', ['email_hash']))->toBeTrue()
        ->and(indexOn('privacy_erasures', ['subject_type', 'subject_id'], true))->toBeTrue()
        ->and(indexOn('privacy_erasures', ['subject_hash']))->toBeTrue()
        ->and(Schema::hasColumns('enrollments', ['contact_email_hash', 'personal_data_purged_at']))->toBeTrue()
        ->and(Schema::hasColumns('borrel_enrollments', ['email_hash', 'personal_data_purged_at']))->toBeTrue()
        ->and(Schema::hasColumn('enrollment_info_submissions', 'sensitive_purged_at'))->toBeTrue()
        ->and(Schema::hasColumn('company_access_requests', 'personal_data_purged_at'))->toBeTrue()
        ->and(Schema::hasTable('privacy_runs'))->toBeTrue();
}

it('produces the intended schema', function () {
    expectIntendedSchema();
});

it('runs again when the index it wants to drop is already gone (error 1091 scenario)', function () {
    // After the first run, enrollments_contact_email_index no longer exists; a retry must not try to drop it.
    expect(Schema::hasIndex('enrollments', 'enrollments_contact_email_index'))->toBeFalse();

    privacyMigration()->up();

    expectIntendedSchema();
});

it('drops a plain index on the email column whatever it is called', function () {
    Schema::table('enrollments', fn (Blueprint $t) => $t->index('contact_email', 'some_other_name'));
    expect(indexOn('enrollments', ['contact_email']))->toBeTrue();

    privacyMigration()->up();

    expect(indexOn('enrollments', ['contact_email']))->toBeFalse();
    expectIntendedSchema();
});

it('recovers from a partially applied migration without losing data', function () {
    $event = openEvent();
    DB::table('newsletter_subscribers')->insert(['email' => 'a@x.example', 'email_hash' => str_repeat('a', 64), 'created_at' => now(), 'updated_at' => now()]);
    DB::table('borrel_enrollments')->insert(['event_id' => $event->id, 'name' => 'Pat', 'email' => 'p@x.example', 'email_hash' => str_repeat('b', 64), 'created_at' => now(), 'updated_at' => now()]);
    $enrollment = submitEnrollment($event, ['contact_email' => 'keep@acme.example']);

    // Simulate a run that stopped half way: later artefacts missing, early ones done.
    Schema::table('newsletter_subscribers', fn (Blueprint $t) => $t->dropUnique(['email_hash']));
    Schema::table('borrel_enrollments', fn (Blueprint $t) => $t->dropUnique(['event_id', 'email_hash']));
    Schema::table('enrollments', fn (Blueprint $t) => $t->dropIndex(['contact_email_hash']));
    Schema::drop('privacy_erasures');
    Schema::drop('privacy_runs');

    privacyMigration()->up();

    expectIntendedSchema();
    expect(DB::table('newsletter_subscribers')->count())->toBe(1)
        ->and(DB::table('borrel_enrollments')->value('email_hash'))->toBe(str_repeat('b', 64))
        ->and(App\Models\Enrollment::find($enrollment->id)->contact_email)->toBe('keep@acme.example');
});

it('keeps the uniqueness rules the application needs', function () {
    $a = openEvent();
    $b = openEvent(['name' => 'B', 'date' => '2028-02-02']);
    $row = fn ($event, $hash) => ['event_id' => $event->id, 'name' => 'P', 'email' => 'x', 'email_hash' => $hash, 'created_at' => now(), 'updated_at' => now()];

    DB::table('borrel_enrollments')->insert($row($a, 'h1'));
    DB::table('borrel_enrollments')->insert($row($b, 'h1')); // same address, another event: allowed
    DB::table('borrel_enrollments')->insert($row($a, null)); // purged guests have no hash: several allowed
    DB::table('borrel_enrollments')->insert($row($a, null));

    expect(fn () => DB::table('borrel_enrollments')->insert($row($a, 'h1')))->toThrow(Illuminate\Database\QueryException::class);

    DB::table('newsletter_subscribers')->insert(['email' => 'x', 'email_hash' => 'n1', 'created_at' => now(), 'updated_at' => now()]);
    expect(fn () => DB::table('newsletter_subscribers')->insert(['email' => 'y', 'email_hash' => 'n1', 'created_at' => now(), 'updated_at' => now()]))->toThrow(Illuminate\Database\QueryException::class);
});

it('never deletes or truncates rows', function () {
    $source = file_get_contents(base_path('database/migrations/2026_11_20_000001_prepare_privacy_retention_and_email_encryption.php'));

    expect($source)->not->toMatch('/->(delete|truncate)\(|DB::table|dropColumn|Schema::drop(?!Index)/');
});

it('stores every encrypted name in an optional text column', function () {
    foreach ([['enrollments', 'contact_name'], ['enrollment_contact_changes', 'name'], ['company_profile_submissions', 'contact_name'], ['borrel_enrollments', 'name'], ['company_access_requests', 'contact_name'], ['company_interest_requests', 'contact_name']] as [$table, $column]) {
        $definition = collect(Schema::getColumns($table))->firstWhere('name', $column);

        expect($definition['type_name'])->toBe('text', "{$table}.{$column} must be text")->and($definition['nullable'])->toBeTrue("{$table}.{$column} must be nullable");
    }
});

it('widens the name columns on a database that applied the earlier privacy migration', function () {
    // The state found on a real MariaDB: names still varchar and NOT NULL.
    Schema::table('enrollment_contact_changes', fn (Blueprint $t) => $t->string('name')->nullable(false)->change());
    Schema::table('enrollments', fn (Blueprint $t) => $t->string('contact_name')->nullable()->change());
    $enrollment = submitEnrollment(openEvent());

    (require base_path('database/migrations/2026_11_22_000001_widen_encrypted_name_columns.php'))->up();
    (require base_path('database/migrations/2026_11_22_000001_widen_encrypted_name_columns.php'))->up(); // and again

    $column = fn ($table, $name) => collect(Schema::getColumns($table))->firstWhere('name', $name);
    expect($column('enrollment_contact_changes', 'name')['nullable'])->toBeTrue()->and($column('enrollments', 'contact_name')['type_name'])->toBe('text')
        ->and(App\Models\Enrollment::find($enrollment->id)->contact_email)->toBe('jane@acme.example');
});
