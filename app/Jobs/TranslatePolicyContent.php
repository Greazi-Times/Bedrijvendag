<?php

namespace App\Jobs;

use App\Contracts\TranslatesText;
use App\Support\PolicyContentTranslation;
use App\Support\TranslationContent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class TranslatePolicyContent implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $settingsClass,
        public string $sourceHash,
    ) {}

    public function handle(TranslatesText $translator): void
    {
        if (! PolicyContentTranslation::supports($this->settingsClass)) {
            return;
        }

        $settings = app($this->settingsClass);
        $source = trim((string) $settings->content);

        if ($source === '' || TranslationContent::hash($source) !== $this->sourceHash) {
            return;
        }

        try {
            $translation = $translator->translate([$source], 'nl', 'en', true)[0];

            // Reload before saving so an older queued job cannot overwrite a newer edit.
            $settings = app()->make($this->settingsClass);
            $currentSource = trim((string) $settings->content);

            if (TranslationContent::hash($currentSource) !== $this->sourceHash) {
                return;
            }

            $settings->content_en = $translation;
            $settings->content_en_source_hash = $this->sourceHash;
            $settings->save();
        } catch (Throwable $exception) {
            Log::warning('Automatic policy translation failed.', [
                'settings' => $this->settingsClass,
                'exception' => $exception,
            ]);
        }
    }
}
