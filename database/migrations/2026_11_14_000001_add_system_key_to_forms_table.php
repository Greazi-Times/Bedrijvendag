<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Marks the two globally managed default forms. A form with a system key belongs to no edition and cannot be deleted.
    public function up(): void
    {
        Schema::table('forms', function (Blueprint $table) {
            $table->string('system_key', 40)->nullable()->unique()->after('kind');
        });
    }

    public function down(): void
    {
        Schema::table('forms', fn (Blueprint $table) => $table->dropColumn('system_key'));
    }
};
