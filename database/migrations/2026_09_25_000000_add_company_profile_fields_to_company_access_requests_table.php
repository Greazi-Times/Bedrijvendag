<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_access_requests', function (Blueprint $table) {
            $table->string('logo_path')->nullable()->after('website_url');
            $table->text('description')->nullable()->after('logo_path');
            $table->json('education_ids')->nullable()->after('description');
            $table->json('sector_ids')->nullable()->after('education_ids');
            $table->json('new_sector_names')->nullable()->after('sector_ids');
        });
    }

    public function down(): void
    {
        Schema::table('company_access_requests', function (Blueprint $table) {
            $table->dropColumn([
                'logo_path',
                'description',
                'education_ids',
                'sector_ids',
                'new_sector_names',
            ]);
        });
    }
};
