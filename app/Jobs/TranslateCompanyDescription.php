<?php

namespace App\Jobs;

use App\Contracts\TranslatesText;
use App\Models\Company;
use App\Support\TranslationContent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class TranslateCompanyDescription implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int|string $companyId,
        public string $sourceHash,
    ) {}

    public function handle(TranslatesText $translator): void
    {
        $company = Company::query()->find($this->companyId);
        $source = trim((string) $company?->description_nl);

        if (! $company || $source === '' || TranslationContent::hash($source) !== $this->sourceHash) {
            return;
        }

        try {
            $translation = $translator->translate(
                [$source],
                'nl',
                'en',
                TranslationContent::containsHtml($source),
            )[0];

            Company::query()
                ->whereKey($company->getKey())
                ->where('description_nl', $source)
                ->update(['description_en' => $translation]);
        } catch (Throwable $exception) {
            Log::warning('Automatic company description translation failed.', [
                'company_id' => $this->companyId,
                'exception' => $exception,
            ]);
        }
    }
}
