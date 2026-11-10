<?php

namespace App\Console\Commands;

use App\Support\Privacy\EmailBackfill;
use App\Support\Privacy\EmailCrypto;
use Illuminate\Console\Command;

class EncryptEmails extends Command
{
    protected $signature = 'privacy:encrypt-emails {--dry-run : Only count what would change} {--rotate : Re-encrypt already encrypted values under the current key and recompute lookup hashes} {--batch=200}';

    protected $description = 'Encrypt existing plaintext email addresses and answers in place, verifying each value first (safe to repeat)';

    public function handle(EmailBackfill $backfill): int
    {
        $keys = EmailCrypto::usesDedicatedKeys();

        if (! $keys['encryption'] || ! $keys['lookup']) {
            $this->warn('EMAIL_ENCRYPTION_KEY and/or EMAIL_LOOKUP_KEY are not set; keys derived from APP_KEY are used. Set dedicated keys before running this in production (php artisan privacy:generate-keys).');
        }

        $dry = (bool) $this->option('dry-run');
        $stats = $backfill->run($dry, (bool) $this->option('rotate'), (int) $this->option('batch'));

        $this->table(['Table', $dry ? 'Would encrypt' : 'Encrypted', 'Hashes set', 'Skipped (failed check)', $dry ? 'Plaintext left (est.)' : 'Plaintext left'], collect($stats)->map(fn (array $s, string $table): array => [$table, $s['encrypted'], $s['rehashed'], $s['skipped'], $s['remaining_plaintext']])->values()->all());

        $skipped = collect($stats)->sum('skipped');
        $dry ? $this->info('Dry run: nothing was changed.') : $this->info($skipped === 0 ? 'Done. Verify the application, then keep the keys safe: losing them makes the data unreadable.' : "Done, but {$skipped} value(s) failed the round-trip check and were left untouched.");

        return $skipped === 0 ? self::SUCCESS : self::FAILURE;
    }
}
