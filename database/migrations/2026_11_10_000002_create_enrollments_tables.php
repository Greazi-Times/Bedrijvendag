<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            // The exact published version the company answered; versions are never deleted while enrollments exist.
            $table->foreignId('form_version_id')->constrained('form_versions')->restrictOnDelete();

            // What the applicant claimed (unverified) vs. what an administrator resolved on approval.
            $table->foreignId('selected_company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            // Set only while approved, so the database itself refuses a second approved enrollment per company+edition.
            $table->foreignId('approved_company_id')->nullable()->constrained('companies')->nullOnDelete();

            $table->string('status', 20)->default('pending')->index();
            $table->string('info_status', 20)->default('not_invited');

            $table->string('company_name');
            $table->string('name_key')->index(); // CompanyMatcher::key(): a lookup hint, never a uniqueness rule
            $table->string('contact_name');
            $table->string('contact_email')->index();

            $table->json('answers');
            $table->json('files')->nullable(); // private-disk uploads by field key

            // Immutable record of what was accepted, independent of later edits to the edition.
            $table->unsignedInteger('fee_cents_accepted');
            $table->string('currency_accepted', 3);
            $table->unsignedInteger('terms_version_accepted');
            $table->longText('terms_html_accepted')->nullable();
            $table->timestamp('accepted_at');

            $table->uuid('submission_key');
            $table->timestamp('submitted_at');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('review_note')->nullable();
            $table->foreignId('profile_submission_id')->nullable()->constrained('company_profile_submissions')->nullOnDelete();
            $table->timestamps();

            $table->unique(['event_id', 'submission_key']);
            $table->unique(['event_id', 'approved_company_id']);
            $table->index(['event_id', 'status']);
        });

        Schema::create('enrollment_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained()->cascadeOnDelete();
            $table->char('token_hash', 64)->unique(); // sha256; the plain token only ever lives in the email
            $table->timestamp('expires_at');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollment_invitations');
        Schema::dropIfExists('enrollments');
    }
};
