<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\Education;
use App\Models\Event;
use App\Models\Partner;
use App\Models\Sector;
use App\Services\DeepLTranslator;
use App\Support\PolicyContentTranslation;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;

class SyncContentTranslations extends Command
{
    protected $signature = 'translations:sync {--force : Regenerate translations even when the source has not changed}';

    protected $description = 'Queue missing or outdated translations for public database content';

    /** @var list<class-string<Model>> */
    private array $models = [
        Event::class,
        Partner::class,
        Education::class,
        Sector::class,
    ];

    public function handle(DeepLTranslator $translator): int
    {
        if (! $translator->configured()) {
            $this->error('Set DEEPL_API_KEY before syncing translations.');

            return self::FAILURE;
        }

        $jobs = 0;

        Company::query()->chunkById(100, function ($companies) use (&$jobs): void {
            foreach ($companies as $company) {
                $jobs += (int) $company->queueEnglishDescriptionTranslation(
                    overwrite: (bool) $this->option('force'),
                );
            }
        });

        foreach ($this->models as $modelClass) {
            $modelClass::query()->chunkById(100, function ($records) use (&$jobs): void {
                foreach ($records as $record) {
                    $jobs += $record->queueAutomaticTranslations(force: (bool) $this->option('force'));
                }
            });
        }

        foreach (PolicyContentTranslation::SETTINGS_CLASSES as $settingsClass) {
            $jobs += (int) PolicyContentTranslation::queue(
                $settingsClass,
                force: (bool) $this->option('force'),
            );
        }

        $this->info("Queued {$jobs} translation job(s).");

        return self::SUCCESS;
    }
}
