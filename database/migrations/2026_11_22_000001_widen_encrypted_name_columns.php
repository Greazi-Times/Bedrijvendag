<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Lossless column changes only (varchar -> text, NOT NULL -> NULL); no row is changed or deleted, and it is safe to run again.
|
| Why a separate migration: 2026_11_20_000001 had already been applied to databases before the name columns were added to
| it, and an applied migration is never re-run. Found on a real MariaDB: enrollment_contact_changes.name was still NOT NULL
| (a contact change without a name failed) and the name columns were varchar(255), which an encrypted name can outgrow.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollments', fn (Blueprint $t) => $t->text('contact_name')->nullable()->change());
        Schema::table('enrollment_contact_changes', fn (Blueprint $t) => $t->text('name')->nullable()->change());
        Schema::table('company_profile_submissions', fn (Blueprint $t) => $t->text('contact_name')->nullable()->change());
    }

    public function down(): void
    {
        // Intentionally empty: narrowing would truncate encrypted values.
    }
};
