<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Additive only; no rows are changed or deleted. Safe to run again after a partial failure (columns are checked first).
| - newsletter_subscribers: the consent that was given (version, the exact text shown, where it was given) and the
|   withdrawal time. A withdrawn row stays as a suppression record so a withdrawn address is not mailed again.
| - Names on interest requests become optional: the public forms no longer ask for them; legacy rows keep theirs.
*/
return new class extends Migration
{
    public function up(): void
    {
        $columns = [
            'consent_version' => fn (Blueprint $t) => $t->string('consent_version', 40)->nullable(),
            'consent_text' => fn (Blueprint $t) => $t->text('consent_text')->nullable(),
            'consent_source' => fn (Blueprint $t) => $t->string('consent_source', 40)->nullable(),
            'unsubscribed_at' => fn (Blueprint $t) => $t->timestamp('unsubscribed_at')->nullable(),
        ];

        foreach ($columns as $name => $definition) {
            Schema::hasColumn('newsletter_subscribers', $name) || Schema::table('newsletter_subscribers', $definition);
        }

        Schema::table('borrel_enrollments', fn (Blueprint $t) => $t->text('name')->nullable()->change()); // no longer asked; legacy rows keep theirs
        Schema::table('company_interest_requests', fn (Blueprint $t) => $t->text('contact_name')->nullable()->change());
        Schema::table('company_access_requests', fn (Blueprint $t) => $t->text('contact_name')->nullable()->change());
    }

    public function down(): void
    {
        // Intentionally empty: removing consent records would destroy the proof of consent.
    }
};
