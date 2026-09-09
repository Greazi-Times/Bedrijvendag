<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_translations', function (Blueprint $table) {
            $table->id();
            $table->string('translatable_type');
            $table->unsignedBigInteger('translatable_id');
            $table->string('field', 100);
            $table->string('source_locale', 10)->default('nl');
            $table->string('locale', 10);
            $table->string('source_hash', 64);
            $table->longText('value')->nullable();
            $table->string('status', 20)->default('pending');
            $table->text('error')->nullable();
            $table->timestamps();

            $table->unique(
                ['translatable_type', 'translatable_id', 'field', 'locale'],
                'content_translations_unique',
            );
            $table->index(
                ['translatable_type', 'translatable_id'],
                'content_translations_lookup',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_translations');
    }
};
