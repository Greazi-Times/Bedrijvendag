<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('education_partner', function (Blueprint $table) {
            $table->id();

            $table->foreignId('education_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('partner_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->unique(['education_id', 'partner_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('education_partner');
    }
};
