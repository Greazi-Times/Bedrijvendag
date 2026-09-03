<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_profile_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('pending')->index();
            $table->string('contact_name')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('proposed_name');
            $table->string('proposed_logo_path')->nullable();
            $table->string('proposed_website_url')->nullable();
            $table->text('proposed_description')->nullable();
            $table->json('proposed_education_ids')->nullable();
            $table->json('proposed_sector_ids')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('review_note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_profile_submissions');
    }
};
