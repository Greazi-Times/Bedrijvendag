<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_map_points', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->string('type')->default('other');
            $table->text('description')->nullable();
            $table->decimal('x_percent', 5, 2)->nullable();
            $table->decimal('y_percent', 5, 2)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_map_points');
    }
};
