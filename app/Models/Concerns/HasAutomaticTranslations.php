<?php

namespace App\Models\Concerns;

use App\Jobs\TranslateModelContent;
use App\Models\ContentTranslation;
use App\Services\DeepLTranslator;
use App\Support\TranslationContent;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasAutomaticTranslations
{
    public static function bootHasAutomaticTranslations(): void
    {
        static::saved(function (self $model): void {
            $fields = collect($model->automaticTranslationFields())
                ->filter(fn (string $field): bool => $model->wasRecentlyCreated || $model->wasChanged($field))
                ->values()
                ->all();

            if ($fields !== [] && app(DeepLTranslator::class)->configured()) {
                $model->queueAutomaticTranslations($fields);
            }
        });

        static::deleted(function (self $model): void {
            $model->translations()->delete();
        });
    }

    public function translations(): MorphMany
    {
        return $this->morphMany(ContentTranslation::class, 'translatable');
    }

    /** @return list<string> */
    abstract public function automaticTranslationFields(): array;

    public function translated(string $field, ?string $locale = null): ?string
    {
        $source = TranslationContent::stringify($this->getAttribute($field));

        if ($source === '') {
            return null;
        }

        $sourceLocale = (string) config('services.translation.source_locale', 'nl');
        $locale ??= app()->getLocale();

        if ($locale === $sourceLocale) {
            return $source;
        }

        $translations = $this->relationLoaded('translations')
            ? $this->getRelation('translations')
            : $this->translations()->get();

        $translation = $translations->first(fn (ContentTranslation $translation): bool => $translation->field === $field
            && $translation->locale === $locale
            && $translation->source_hash === TranslationContent::hash($source)
            && $translation->status === 'completed');

        return $translation?->value ?: $source;
    }

    /**
     * @param  list<string>|null  $fields
     */
    public function queueAutomaticTranslations(?array $fields = null, bool $force = false): int
    {
        $sourceLocale = (string) config('services.translation.source_locale', 'nl');
        $targetLocales = config('services.translation.target_locales', ['en']);
        $fields ??= $this->automaticTranslationFields();
        $queued = 0;

        foreach ($targetLocales as $targetLocale) {
            if ($targetLocale === $sourceLocale) {
                continue;
            }

            $hashes = [];

            foreach ($fields as $field) {
                if (! in_array($field, $this->automaticTranslationFields(), true)) {
                    continue;
                }

                $source = TranslationContent::stringify($this->getAttribute($field));

                if ($source === '') {
                    $this->translations()->where('field', $field)->where('locale', $targetLocale)->delete();

                    continue;
                }

                $hash = TranslationContent::hash($source);
                $existing = $this->translations()
                    ->where('field', $field)
                    ->where('locale', $targetLocale)
                    ->first();

                if (! $force && $existing?->status === 'completed' && $existing->source_hash === $hash) {
                    continue;
                }

                $this->translations()->updateOrCreate(
                    ['field' => $field, 'locale' => $targetLocale],
                    [
                        'source_locale' => $sourceLocale,
                        'source_hash' => $hash,
                        'value' => null,
                        'status' => 'pending',
                        'error' => null,
                    ],
                );

                $hashes[$field] = $hash;
            }

            if ($hashes !== []) {
                TranslateModelContent::dispatch(static::class, $this->getKey(), $targetLocale, $hashes)->afterCommit();
                $queued++;
            }
        }

        $this->unsetRelation('translations');

        return $queued;
    }
}
