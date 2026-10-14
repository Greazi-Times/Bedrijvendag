<?php

namespace App\Console\Commands;

use App\Support\AuditRetention;
use Illuminate\Console\Command;

class PruneAuditLog extends Command
{
    protected $signature = 'audit:prune {--dry-run : Only count what would be removed}';

    protected $description = 'Remove audit entries older than their configured retention period';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $counts = AuditRetention::prune($dryRun);

        if (! $counts) {
            $this->info('Nothing has passed its retention period.');

            return self::SUCCESS;
        }

        $this->table(['Category', $dryRun ? 'Would remove' : 'Removed'], collect($counts)->map(fn (int $count, string $category): array => [$category, $count])->values()->all());

        return self::SUCCESS;
    }
}
