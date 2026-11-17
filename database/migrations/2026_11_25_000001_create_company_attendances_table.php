<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per company and edition: the unique key is what rules out a duplicate check-in. Reversing a check-in clears
        // the timestamp (the audit log keeps who did what). No stand or dietary data lives here; both are read from their source.
        Schema::create('company_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->timestamp('checked_in_at')->nullable();
            $table->foreignId('checked_in_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['event_id', 'company_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_attendances');
    }
};
