<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Only a number: the registered participants and their diets stay in the information form. Independent of checked_in_at.
        Schema::table('company_attendances', function (Blueprint $table) {
            $table->unsignedSmallInteger('lunch_checked_in')->default(0)->after('checked_in_by');
        });
    }

    public function down(): void
    {
        Schema::table('company_attendances', function (Blueprint $table) {
            $table->dropColumn('lunch_checked_in');
        });
    }
};
