<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_event', function (Blueprint $table) {
            // Drop the existing foreign key first (name might differ)
            // If you used Laravel conventions, it's usually: company_event_company_id_foreign
            $table->dropForeign(['company_id']);

            $table->foreignId('company_id')->nullable()->change();

            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('company_event', function (Blueprint $table) {
            $table->dropForeign(['company_id']);

            $table->foreignId('company_id')->nullable(false)->change();

            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->cascadeOnDelete();
        });
    }
};
