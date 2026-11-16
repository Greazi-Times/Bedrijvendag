<?php

use App\Services\FeedbackSurveys;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /** Gives every existing edition its missing surveys (and the two global templates). Idempotent; never overwrites anything. */
    public function up(): void
    {
        app(FeedbackSurveys::class)->backfill();
    }

    public function down(): void
    {
        // Intentionally empty: surveys and their responses are never removed by a rollback.
    }
};
