<?php

namespace App\Services;

use App\Contracts\TranslatesText;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class DeepLTranslator implements TranslatesText
{
    public function configured(): bool
    {
        return filled(config('services.deepl.key'));
    }

    public function translate(array $texts, string $sourceLocale, string $targetLocale, bool $html = false): array
    {
        $key = (string) config('services.deepl.key');

        if ($key === '') {
            throw new RuntimeException('DEEPL_API_KEY is not configured.');
        }

        $response = $this->request($key)->post($this->endpoint($key), [
            'text' => array_values($texts),
            'source_lang' => strtoupper($sourceLocale),
            'target_lang' => strtoupper($targetLocale),
            ...($html ? ['tag_handling' => 'html'] : []),
        ])->throw();

        $translations = collect($response->json('translations', []))
            ->pluck('text')
            ->map(fn (mixed $text): string => (string) $text)
            ->values()
            ->all();

        if (count($translations) !== count($texts)) {
            throw new RuntimeException('DeepL returned an unexpected number of translations.');
        }

        return $translations;
    }

    private function request(string $key): PendingRequest
    {
        return Http::acceptJson()
            ->withHeaders(['Authorization' => 'DeepL-Auth-Key '.$key])
            ->timeout(20)
            ->retry(2, 500);
    }

    private function endpoint(string $key): string
    {
        $configuredUrl = rtrim((string) config('services.deepl.url'), '/');

        if ($configuredUrl !== '') {
            return $configuredUrl.'/translate';
        }

        $host = str_ends_with($key, ':fx')
            ? 'https://api-free.deepl.com/v2'
            : 'https://api.deepl.com/v2';

        return $host.'/translate';
    }
}
