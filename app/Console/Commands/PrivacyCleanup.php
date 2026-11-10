<?php

namespace App\Console\Commands;

use App\Support\Privacy\PrivacyRetention;
use Illuminate\Console\Command;

class PrivacyCleanup extends Command
{
    protected $signature = 'privacy:cleanup {--dry-run : Show what would be removed without changing anything}';

    protected $description = 'Enforce the data retention periods (config/privacy.php). Idempotent; safe to run daily and after a backup restore';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $stats = (new PrivacyRetention)->run($dry);

        $this->table(['Item', $dry ? 'Would process' : 'Processed'], collect($stats)->map(fn (int $n, string $k): array => [str_replace('_', ' ', $k), $n])->values()->all());
        $this->line($dry ? 'Dry run: nothing was changed.' : 'Cleanup finished. Statistics contain counts only.');

        return self::SUCCESS;
    }
}
