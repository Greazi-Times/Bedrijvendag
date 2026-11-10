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
        Schema::create('forms', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('kind', 20); // enrollment | info
            $table->boolean('is_template')->default(false);
            // Edition forms are independent copies; deleting an edition removes its copies, never a template.
            $table->foreignId('event_id')->nullable()->constrained()->cascadeOnDelete();
            $table->json('draft_schema');
            $table->timestamps();

            $table->index(['kind', 'is_template']);
        });

        // Published versions are immutable: submissions point at the exact schema they were answered against.
        Schema::create('form_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->json('schema');
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at');
            $table->timestamps();

            $table->unique(['form_id', 'version']);
        });

        Schema::table('events', function (Blueprint $table) {
            $table->string('enrollment_token', 64)->nullable()->unique();
            $table->boolean('enrollment_enabled')->default(false);
            $table->timestamp('enrollment_opens_at')->nullable();
            $table->timestamp('enrollment_closes_at')->nullable();
            $table->unsignedInteger('fee_cents')->default(0);
            $table->string('currency', 3)->default('EUR');
            $table->longText('terms_html')->nullable();
            $table->unsignedInteger('terms_version')->default(1);
            $table->unsignedTinyInteger('max_lunch_participants')->default(3);
            $table->foreignId('enrollment_form_id')->nullable()->constrained('forms')->nullOnDelete();
            $table->foreignId('info_form_id')->nullable()->constrained('forms')->nullOnDelete();
        });

        foreach (DB::table('events')->pluck('id') as $id) {
            DB::table('events')->where('id', $id)->update(['enrollment_token' => Str::random(40)]);
        }
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropConstrainedForeignId('enrollment_form_id');
            $table->dropConstrainedForeignId('info_form_id');
            $table->dropColumn(['enrollment_token', 'enrollment_enabled', 'enrollment_opens_at', 'enrollment_closes_at', 'fee_cents', 'currency', 'terms_html', 'terms_version', 'max_lunch_participants']);
        });
        Schema::dropIfExists('form_versions');
        Schema::dropIfExists('forms');
    }
};
