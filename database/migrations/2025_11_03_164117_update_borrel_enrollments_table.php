<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('borrel_enrollments', function (Blueprint $table) {
            // First drop the foreign key constraint if it exists
            if (Schema::hasColumn('borrel_enrollments', 'user_id')) {
                try {
                    $table->dropForeign(['user_id']);
                } catch (\Throwable $e) {
                    // ignore if already dropped
                }

                $table->dropColumn('user_id');
            }

            // Add name and email columns if they don’t exist yet
            if (!Schema::hasColumn('borrel_enrollments', 'name')) {
                $table->string('name')->after('id');
            }

            if (!Schema::hasColumn('borrel_enrollments', 'email')) {
                $table->string('email')->after('name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('borrel_enrollments', function (Blueprint $table) {
            $table->dropColumn(['name', 'email']);
            $table->unsignedBigInteger('user_id')->nullable();
        });
    }
};
