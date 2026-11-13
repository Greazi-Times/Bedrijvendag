<?php

namespace App\Services\Analytics;

use App\Models\Event;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Cache for dashboard statistics. The key contains a cheap fingerprint of the tables the figures are derived from
 * (row count + latest update), so any change to enrollments, stands, submissions, editions or companies makes the old
 * entries unreachable without observers. The short TTL covers changes made through bulk queries that skip timestamps.
 * Only plain numbers are cached; permissions are applied when the figures are shown.
 */
class Analytics
{
    public const TTL_SECONDS = 600;

    /** table => has an updated_at column worth fingerprinting */
    private const TABLES = ['enrollments' => true, 'enrollment_info_submissions' => true, 'event_stands' => true, 'events' => true, 'companies' => true, 'company_sector' => false, 'company_education' => false, 'sectors' => false, 'education' => false];

    private ?string $fingerprint = null;

    /** @var array<string, mixed> request-level memo so several widgets share one computation */
    private array $memo = [];

    public function remember(string $key, \Closure $compute): mixed
    {
        $full = "analytics:{$this->fingerprint()}:{$key}";

        return $this->memo[$full] ??= Cache::remember($full, self::TTL_SECONDS, $compute);
    }

    public function flush(): void
    {
        $this->fingerprint = null;
        $this->memo = [];
    }

    private function fingerprint(): string
    {
        return $this->fingerprint ??= md5(collect(self::TABLES)->map(fn (bool $timestamps, string $table): array => [
            $table, DB::table($table)->count(), $timestamps ? DB::table($table)->max('updated_at') : null,
        ])->values()->toJson());
    }

    /** The edition shown by default: the next upcoming one, else the most recent. */
    public function defaultEvent(): ?Event
    {
        return Event::query()->nextOrLatest()->first();
    }
}
