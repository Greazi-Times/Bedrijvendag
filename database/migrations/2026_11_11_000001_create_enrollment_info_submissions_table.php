<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Append-only: every save is a new revision, so earlier answers and the form version they used stay readable.
        Schema::create('enrollment_info_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('form_version_id')->constrained('form_versions')->restrictOnDelete();
            $table->unsignedInteger('revision');
            $table->json('answers');
            $table->json('files')->nullable();
            $table->string('profile_status', 30)->nullable(); // confirmed | correction_requested
            $table->json('profile_proposal')->nullable(); // what the company typed; the live profile is never touched
            $table->foreignId('profile_submission_id')->nullable()->constrained('company_profile_submissions')->nullOnDelete();
            $table->timestamp('submitted_at');
            $table->timestamps();

            $table->unique(['enrollment_id', 'revision']);
        });

        Schema::table('events', function (Blueprint $table) {
            $table->timestamp('info_closes_at')->nullable(); // after this the second form is read-only
        });
    }

    public function down(): void
    {
        Schema::table('events', fn (Blueprint $table) => $table->dropColumn('info_closes_at'));
        Schema::dropIfExists('enrollment_info_submissions');
    }
};
