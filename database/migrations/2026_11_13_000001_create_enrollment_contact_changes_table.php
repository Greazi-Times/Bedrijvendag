<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // A contact named on the second form is only a proposal until its address is verified.
    public function up(): void
    {
        Schema::create('enrollment_contact_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('info_submission_id')->nullable()->constrained('enrollment_info_submissions')->nullOnDelete();
            $table->string('name');
            $table->string('email');
            $table->char('token_hash', 64)->nullable()->unique(); // sha256; cleared once verified
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->string('verified_by', 20)->nullable(); // email | trusted_email
            $table->timestamp('superseded_at')->nullable();
            $table->timestamp('queued_at')->nullable(); // verification email handed to the queue (delivery is not tracked)
            $table->timestamps();

            $table->index(['enrollment_id', 'verified_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollment_contact_changes');
    }
};
