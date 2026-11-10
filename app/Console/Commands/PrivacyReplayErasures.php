<?php

namespace App\Console\Commands;

use App\Support\Privacy\ErasureLedger;
use App\Support\Privacy\PrivacyRetention;
use Illuminate\Console\Command;

class PrivacyReplayErasures extends Command
{
    protected $signature = 'privacy:replay-erasures {--dry-run} {--without-ledger : Continue although no ledger file exists (only if nothing was erased since the backup)}';

    protected $description = 'After restoring a database backup: re-apply recorded erasures and the time-based retention. Idempotent';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $path = (string) config('privacy.erasure_ledger.path');

        if (! is_file($path) && ! $this->option('without-ledger')) {
            $this->error('The erasure ledger file was not found. Recover it from its separate backup first, or pass --without-ledger if you can confirm no erasure happened since this backup. Refusing to continue so erased data is not silently left in place.');

            return self::FAILURE;
        }

        str_starts_with(realpath(dirname($path)) ?: $path, storage_path()) && $this->warn('The ledger lives inside storage/. If the whole server was restored from one snapshot, it may be as old as the database.');
        $stats = ErasureLedger::replay($dry) + (new PrivacyRetention)->run($dry);

        $this->table(['Item', $dry ? 'Would process' : 'Processed'], collect($stats)->map(fn ($n, $k) => [str_replace('_', ' ', $k), $n])->values()->all());

        return self::SUCCESS;
    }
}
