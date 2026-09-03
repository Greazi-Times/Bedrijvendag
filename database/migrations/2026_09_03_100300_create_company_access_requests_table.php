<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_access_requests', function (Blueprint $table) {
            $table->id();
            $table->string('type')->index();
            $table->string('status')->default('pending')->index();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->string('company_name')->nullable();
            $table->string('website_url')->nullable();
            $table->string('contact_name');
            $table->string('contact_email');
            $table->text('message')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('review_note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_access_requests');
    }
};
