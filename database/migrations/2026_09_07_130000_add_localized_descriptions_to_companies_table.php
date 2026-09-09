<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->longText('description_nl')->nullable()->after('description');
            $table->longText('description_en')->nullable()->after('description_nl');
        });

        DB::table('companies')
            ->select(['id', 'description'])
            ->orderBy('id')
            ->each(function (object $company): void {
                $descriptionNl = $this->legacyDescriptionToHtml($company->description);
                $descriptionEn = null;

                if (Schema::hasTable('content_translations')) {
                    $descriptionEn = DB::table('content_translations')
                        ->where('translatable_type', 'App\\Models\\Company')
                        ->where('translatable_id', $company->id)
                        ->where('field', 'description')
                        ->where('locale', 'en')
                        ->where('status', 'completed')
                        ->value('value');
                }

                DB::table('companies')
                    ->where('id', $company->id)
                    ->update([
                        'description_nl' => $descriptionNl,
                        'description_en' => filled($descriptionEn) ? $descriptionEn : null,
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['description_nl', 'description_en']);
        });
    }

    private function legacyDescriptionToHtml(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $decoded = json_decode($value, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return trim($value);
        }

        if (is_string($decoded)) {
            return trim($decoded) ?: null;
        }

        if (! is_array($decoded)) {
            return null;
        }

        foreach (['html', 'content', 'value'] as $key) {
            if (isset($decoded[$key]) && is_string($decoded[$key])) {
                return trim($decoded[$key]) ?: null;
            }
        }

        return collect($decoded)
            ->filter(fn (mixed $item): bool => is_string($item) && trim($item) !== '')
            ->map(fn (string $item): string => trim($item))
            ->implode("\n\n") ?: null;
    }
};
