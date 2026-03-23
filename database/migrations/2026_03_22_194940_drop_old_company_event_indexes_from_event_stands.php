<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_stands', function (Blueprint $table) {
            $table->dropUnique('company_event_event_id_stand_number_unique');
            $table->dropUnique('company_event_event_id_company_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('event_stands', function (Blueprint $table) {
            $table->unique(
                ['event_id', 'stand_number'],
                'company_event_event_id_stand_number_unique'
            );

            $table->unique(
                ['event_id', 'company_id'],
                'company_event_event_id_company_id_unique'
            );
        });
    }
};
