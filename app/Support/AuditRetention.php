<?php

namespace App\Support;

use App\Models\AuditLog;
use App\Models\SecurityAlert;
use Illuminate\Database\Eloquent\Builder;

/**
 * Removes audit entries older than the retention period of their category
 * (config('audit.retention')). Entries with a protected severity are kept
 * until the longest configured period has passed. This is the only code that
 * deletes audit entries; it deletes in batches through the query builder.
 */
final class AuditRetention
{
    /** @return array<string, int> number of entries per category that are (or would be) removed */
    public static function prune(bool $dryRun = true): array
    {
        $config = config('audit.retention');
        $categories = $config['categories'];
        $counts = [];

        foreach ($categories as $category => $days) {
            $counts[$category] = self::remove(self::expired($days)->where('log_name', $category), $dryRun);
        }

        $counts['other'] = self::remove(
            self::expired($config['default'])->where(fn (Builder $query) => $query->whereNotIn('log_name', array_keys($categories))->orWhereNull('log_name')),
            $dryRun,
        );

        $alerts = SecurityAlert::query()->whereNotNull('acknowledged_at')->where('acknowledged_at', '<', now()->subDays($categories['security'] ?? $config['default']));
        $counts['acknowledged alerts'] = $dryRun ? $alerts->count() : $alerts->delete();

        $counts = array_filter($counts);

        if (! $dryRun && $counts) {
            Audit::log('audit.retention.pruned', 'Expired audit entries removed', properties: ['removed' => $counts]);
        }

        return $counts;
    }

    private static function expired(int $days): Builder
    {
        $config = config('audit.retention');
        $longest = max($config['default'], ...array_values($config['categories']));

        return AuditLog::query()
            ->where('created_at', '<', now()->subDays($days))
            ->where(fn (Builder $query) => $query
                ->whereNotIn('severity', $config['protected_severities'])
                ->orWhere('created_at', '<', now()->subDays($longest)));
    }

    private static function remove(Builder $query, bool $dryRun): int
    {
        if ($dryRun) {
            return $query->count();
        }

        $removed = 0;

        // Batches keep each delete short on a large table.
        do {
            $removed += $batch = (clone $query)->limit(1000)->delete();
        } while ($batch > 0);

        return $removed;
    }
}
