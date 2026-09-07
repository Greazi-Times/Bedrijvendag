<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('partners', 'image') || Schema::hasColumn('partners', 'logo')) {
            return;
        }

        Schema::table('partners', fn (Blueprint $table) => $table->renameColumn('image', 'logo'));
    }

    public function down(): void
    {
        if (! Schema::hasColumn('partners', 'logo') || Schema::hasColumn('partners', 'image')) {
            return;
        }

        Schema::table('partners', fn (Blueprint $table) => $table->renameColumn('logo', 'image'));
    }
};
