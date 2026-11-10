<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Schema changes only; no data is changed or deleted here. Existing plaintext emails stay readable until the operator
| runs `php artisan privacy:encrypt-emails` (see docs/privacy/email-encryption.md). Encrypted values are longer and are
| not unique per address, so the old unique indexes on email columns are replaced by keyed lookup-hash columns.
|
| MySQL/MariaDB DDL is not transactional, so a failed run can leave the schema half changed. Every step therefore looks at
| the real schema first (Schema::getIndexes / hasColumn / hasTable) and only does what is still missing, so the migration
| can be run again after a partial failure. Indexes are found by their columns, not by a guessed name. Nothing is
| suppressed: an unexpected database error still stops the migration.
*/
return new class extends Migration
{
    public function up(): void
    {
        // enrollments: the plain index on the (soon text) column goes first; ciphertext cannot be indexed and lookups use the hash.
        $this->dropIndexesOn('enrollments', ['contact_email']);

        Schema::table('enrollments', function (Blueprint $table) {
            $table->text('contact_name')->nullable()->change(); // ciphertext, and optional: the default forms no longer ask for it
            $table->text('contact_email')->nullable()->change();
            $table->longText('answers')->nullable()->change(); // holds encrypted answers; json cannot store ciphertext
        });
        $this->addColumn('enrollments', 'contact_email_hash', fn (Blueprint $t) => $t->char('contact_email_hash', 64)->nullable()->after('contact_email'));
        $this->addColumn('enrollments', 'personal_data_purged_at', fn (Blueprint $t) => $t->timestamp('personal_data_purged_at')->nullable());
        $this->addIndex('enrollments', ['contact_email_hash']);
        $this->addIndex('enrollments', ['personal_data_purged_at']);

        Schema::table('enrollment_info_submissions', fn (Blueprint $t) => $t->longText('answers')->nullable()->change());
        $this->addColumn('enrollment_info_submissions', 'sensitive_purged_at', fn (Blueprint $t) => $t->timestamp('sensitive_purged_at')->nullable());
        $this->addIndex('enrollment_info_submissions', ['sensitive_purged_at']);

        Schema::table('enrollment_contact_changes', function (Blueprint $t) {
            $t->text('email')->change();
            $t->text('name')->nullable()->change(); // a contact change is identified by its address; a name is optional
        });
        $this->addColumn('enrollment_contact_changes', 'email_hash', fn (Blueprint $t) => $t->char('email_hash', 64)->nullable()->after('email'));
        $this->addIndex('enrollment_contact_changes', ['email_hash']);

        // borrel: the new per-event unique index on the hash is created BEFORE the old (event_id, email) one is dropped,
        // because MySQL refuses to drop an index that the event_id foreign key still depends on.
        $this->addColumn('borrel_enrollments', 'email_hash', fn (Blueprint $t) => $t->char('email_hash', 64)->nullable()->after('email'));
        $this->addIndex('borrel_enrollments', ['event_id', 'email_hash'], unique: true);
        $this->dropIndexesOn('borrel_enrollments', ['event_id', 'email'], unique: true);
        Schema::table('borrel_enrollments', function (Blueprint $table) {
            $table->text('name')->nullable()->change();
            $table->text('email')->nullable()->change();
        });
        $this->addColumn('borrel_enrollments', 'personal_data_purged_at', fn (Blueprint $t) => $t->timestamp('personal_data_purged_at')->nullable());

        // newsletter: one subscription per address, enforced on the hash.
        $this->dropIndexesOn('newsletter_subscribers', ['email'], unique: true);
        Schema::table('newsletter_subscribers', fn (Blueprint $t) => $t->text('email')->nullable()->change());
        $this->addColumn('newsletter_subscribers', 'email_hash', fn (Blueprint $t) => $t->char('email_hash', 64)->nullable()->after('email'));
        $this->addIndex('newsletter_subscribers', ['email_hash'], unique: true);

        Schema::table('company_access_requests', function (Blueprint $table) {
            $table->text('contact_name')->nullable()->change();
            $table->text('contact_email')->nullable()->change();
        });
        $this->addColumn('company_access_requests', 'personal_data_purged_at', fn (Blueprint $t) => $t->timestamp('personal_data_purged_at')->nullable());
        Schema::table('company_interest_requests', function (Blueprint $table) {
            $table->text('contact_name')->change();
            $table->text('contact_email')->change();
        });
        Schema::table('company_profile_submissions', function (Blueprint $table) {
            $table->text('contact_name')->nullable()->change();
            $table->text('contact_email')->nullable()->change();
        });
        Schema::table('companies', fn (Blueprint $table) => $table->text('profile_contact_email')->nullable()->change());

        // Cleanup statistics and account reviews. Counts only, never personal data.
        if (! Schema::hasTable('privacy_runs')) {
            Schema::create('privacy_runs', function (Blueprint $table) {
                $table->id();
                $table->string('task', 40)->index();
                $table->boolean('dry_run')->default(false);
                $table->json('stats')->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }

        // Manual erasures (privacy requests). Only ids and keyed hashes. Mirrored in a ledger file outside the database (ErasureLedger), so a restored backup can be brought back to the erased state.
        if (! Schema::hasTable('privacy_erasures')) {
            Schema::create('privacy_erasures', function (Blueprint $table) {
                $table->id();
                $table->string('subject_type', 60);
                $table->unsignedBigInteger('subject_id')->nullable(); // enrollment id
                $table->char('subject_hash', 64)->nullable(); // keyed email hash (never the address)
                $table->string('reason', 60)->default('request');
                $table->timestamp('erased_at')->useCurrent();
            });
        }

        $this->addIndex('privacy_runs', ['task']);
        $this->addIndex('privacy_erasures', ['subject_type', 'subject_id'], unique: true);
        $this->addIndex('privacy_erasures', ['subject_hash']);
    }

    public function down(): void
    {
        // Intentionally empty: narrowing columns again would truncate encrypted values, and rolling back must never destroy data.
    }

    private function addColumn(string $table, string $column, \Closure $definition): void
    {
        Schema::hasColumn($table, $column) || Schema::table($table, $definition);
    }

    /** @param list<string> $columns */
    private function findIndexes(string $table, array $columns, ?bool $unique = null): array
    {
        return array_values(array_filter(
            Schema::getIndexes($table),
            fn (array $index): bool => ! $index['primary'] && $index['columns'] === $columns && ($unique === null || (bool) $index['unique'] === $unique),
        ));
    }

    /** Adds an index on exactly these columns unless one already exists (whatever its name). */
    private function addIndex(string $table, array $columns, bool $unique = false): void
    {
        if ($this->findIndexes($table, $columns, $unique) !== []) {
            return;
        }

        Schema::table($table, fn (Blueprint $t) => $unique ? $t->unique($columns) : $t->index($columns));
    }

    /** Drops every index on exactly these columns, found in the real schema. A missing index is not an error. */
    private function dropIndexesOn(string $table, array $columns, ?bool $unique = null): void
    {
        foreach ($this->findIndexes($table, $columns, $unique) as $index) {
            Schema::table($table, fn (Blueprint $t) => $t->dropIndex($index['name']));
        }
    }
};
