<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('company_event', function (Blueprint $table) {
            $table->decimal('x_percent', 6, 2)->nullable()->after('stand_number');
            $table->decimal('y_percent', 6, 2)->nullable()->after('x_percent');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('company_event', function (Blueprint $table) {
            $table->dropColumn(['x_percent', 'y_percent']);
        });
    }
};
