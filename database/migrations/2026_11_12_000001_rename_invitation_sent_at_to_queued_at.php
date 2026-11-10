<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // The column only records that the email was handed to the queue; delivery is not tracked.
    public function up(): void
    {
        Schema::table('enrollment_invitations', fn (Blueprint $table) => $table->renameColumn('sent_at', 'queued_at'));
    }

    public function down(): void
    {
        Schema::table('enrollment_invitations', fn (Blueprint $table) => $table->renameColumn('queued_at', 'sent_at'));
    }
};
