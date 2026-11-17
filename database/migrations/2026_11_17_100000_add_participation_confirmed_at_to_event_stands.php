<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Set only by a Super Admin historical override: the stand records participation without an approved enrollment.
        Schema::table('event_stands', function (Blueprint $table) {
            $table->timestamp('participation_confirmed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('event_stands', function (Blueprint $table) {
            $table->dropColumn('participation_confirmed_at');
        });
    }
};
