<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Feedback surveys get the same switch as enrollment: a manual on/off flag plus an optional opening and closing
     * moment. Published surveys stay on; a survey that was closed by hand stays closed (switched on, closing moment
     * set to when it was changed); drafts stay off. Nothing is deleted.
     */
    public function up(): void
    {
        Schema::table('feedback_surveys', fn (Blueprint $table) => $table->boolean('enabled')->default(false)->after('form_id'));

        DB::table('feedback_surveys')->where('status', 'published')->update(['enabled' => true]);
        DB::table('feedback_surveys')->where('status', 'closed')->update(['enabled' => true, 'closes_at' => DB::raw('COALESCE(closes_at, updated_at)')]);

        Schema::table('feedback_surveys', fn (Blueprint $table) => $table->dropColumn('status'));
    }

    public function down(): void
    {
        Schema::table('feedback_surveys', fn (Blueprint $table) => $table->string('status', 20)->default('draft')->after('form_id'));

        DB::table('feedback_surveys')->where('enabled', true)->update(['status' => 'published']);

        Schema::table('feedback_surveys', fn (Blueprint $table) => $table->dropColumn('enabled'));
    }
};
