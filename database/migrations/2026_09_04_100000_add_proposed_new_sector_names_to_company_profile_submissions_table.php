<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_profile_submissions', function (Blueprint $table) {
            $table->json('proposed_new_sector_names')->nullable()->after('proposed_sector_ids');
        });
    }

    public function down(): void
    {
        Schema::table('company_profile_submissions', function (Blueprint $table) {
            $table->dropColumn('proposed_new_sector_names');
        });
    }
};
