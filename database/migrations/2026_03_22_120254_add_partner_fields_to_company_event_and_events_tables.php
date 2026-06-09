<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_event', function (Blueprint $table) {
            $table->foreignId('partner_id')
                ->nullable()
                ->after('company_id')
                ->constrained('partners')
                ->nullOnDelete();

            $table->string('type')
                ->default('company')
                ->after('partner_id');
        });

        Schema::table('events', function (Blueprint $table) {
            $table->unsignedInteger('partner_stand_count')
                ->default(0)
                ->after('max_stands');
        });

        DB::table('company_event')
            ->whereNull('type')
            ->orWhere('type', '')
            ->update(['type' => 'company']);
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('partner_stand_count');
        });

        Schema::table('company_event', function (Blueprint $table) {
            $table->dropConstrainedForeignId('partner_id');
            $table->dropColumn('type');
        });
    }
};
