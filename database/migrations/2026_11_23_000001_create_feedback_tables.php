<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Exactly one survey per edition and audience. The questions live in an ordinary (edition-specific) form.
        Schema::create('feedback_surveys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('audience', 20); // students | companies
            $table->foreignId('form_id')->constrained('forms')->cascadeOnDelete();
            $table->string('status', 20)->default('draft'); // draft | published | closed
            $table->timestamp('opens_at')->nullable();
            $table->timestamp('closes_at')->nullable();
            $table->timestamps();

            $table->unique(['event_id', 'audience']);
            $table->unique('form_id');
        });

        // Deliberately anonymous: no user, company, enrollment, IP address or user agent, and only the day of submission.
        Schema::create('feedback_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('feedback_survey_id')->constrained()->cascadeOnDelete();
            $table->foreignId('form_version_id')->constrained('form_versions')->cascadeOnDelete();
            $table->date('submitted_on');

            $table->index(['feedback_survey_id', 'submitted_on']);
            $table->index('form_version_id');
        });

        // One row per answered question (one per selected option for multi-select), so statistics are plain SQL.
        // No row at all means "not answered"; a rating of "not applicable" is stored as value_text = 'na'.
        Schema::create('feedback_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('feedback_response_id')->constrained()->cascadeOnDelete();
            $table->string('question_key', 64);
            $table->unsignedSmallInteger('value_number')->nullable();
            $table->text('value_text')->nullable();
            $table->timestamp('redacted_at')->nullable();

            $table->index(['feedback_response_id', 'question_key']);
            $table->index(['question_key', 'value_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedback_answers');
        Schema::dropIfExists('feedback_responses');
        Schema::dropIfExists('feedback_surveys');
    }
};
