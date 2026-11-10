<?php

use App\Services\GlobalForms;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Creates the two global default forms and lets editions that still hold their untouched generated copy inherit
     * them. Idempotent: existing global forms are never reset, customised editions are never changed.
     */
    public function up(): void
    {
        $result = app(GlobalForms::class)->initialize();

        $kept = collect($result['editions'])->flatMap(fn (array $kinds, int $event) => collect($kinds)->filter(fn ($o) => str_starts_with($o, 'kept'))->map(fn ($o, $kind) => "event {$event} {$kind}: {$o}"))->values()->all();

        if ($kept !== []) {
            Log::info('Editions kept their own form during the global forms migration.', ['editions' => $kept]);
        }
    }

    public function down(): void
    {
        // Intentionally empty: the global forms and any answers given against them are never removed by a rollback.
    }
};
