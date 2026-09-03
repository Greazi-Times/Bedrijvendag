<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('profile_token', 80)->nullable()->unique()->after('description');
            $table->timestamp('profile_token_expires_at')->nullable()->after('profile_token');
        });

        DB::table('companies')
            ->whereNull('profile_token')
            ->orderBy('id')
            ->select('id')
            ->each(function (object $company): void {
                DB::table('companies')
                    ->where('id', $company->id)
                    ->update(['profile_token' => Str::random(64)]);
            });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropUnique(['profile_token']);
            $table->dropColumn(['profile_token', 'profile_token_expires_at']);
        });
    }
};
