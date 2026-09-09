<?php

namespace App\Jobs;

use App\Contracts\TranslatesText;
use App\Models\ContentTranslation;
use App\Support\TranslationContent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class TranslateModelContent implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, string>  $fieldHashes
     */
    public function __construct(
        public string $modelClass,
        public int|string $modelId,
        public string $targetLocale,
        public array $fieldHashes,
    ) {}

    public function handle(TranslatesText $translator): void
    {
        if (! is_subclass_of($this->modelClass, Model::class)) {
            return;
        }

        /** @var Model&object{translations: mixed} $model */
        $model = $this->modelClass::query()->find($this->modelId);

        if (! $model || ! method_exists($model, 'automaticTranslationFields')) {
            return;
        }

        $sourceLocale = (string) config('services.translation.source_locale', 'nl');
        $groups = ['plain' => [], 'html' => []];

        foreach ($this->fieldHashes as $field => $expectedHash) {
            $source = TranslationContent::stringify($model->getAttribute($field));

            if ($source === '' || TranslationContent::hash($source) !== $expectedHash) {
                continue;
            }

            $groups[TranslationContent::containsHtml($source) ? 'html' : 'plain'][$field] = $source;
        }

        try {
            foreach ($groups as $format => $sources) {
                if ($sources === []) {
                    continue;
                }

                $values = $translator->translate(
                    array_values($sources),
                    $sourceLocale,
                    $this->targetLocale,
                    $format === 'html',
                );

                foreach (array_keys($sources) as $index => $field) {
                    $model->translations()
                        ->where('field', $field)
                        ->where('locale', $this->targetLocale)
                        ->where('source_hash', $this->fieldHashes[$field])
                        ->update([
                            'value' => $values[$index],
                            'status' => 'completed',
                            'error' => null,
                        ]);
                }
            }
        } catch (Throwable $exception) {
            ContentTranslation::query()
                ->where('translatable_type', $model->getMorphClass())
                ->where('translatable_id', $model->getKey())
                ->where('locale', $this->targetLocale)
                ->whereIn('field', array_keys($this->fieldHashes))
                ->update([
                    'status' => 'failed',
                    'error' => mb_substr($exception->getMessage(), 0, 2000),
                ]);

            Log::warning('Automatic content translation failed.', [
                'model' => $this->modelClass,
                'id' => $this->modelId,
                'locale' => $this->targetLocale,
                'exception' => $exception,
            ]);
        }
    }
}
