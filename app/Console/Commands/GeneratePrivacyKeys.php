<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GeneratePrivacyKeys extends Command
{
    protected $signature = 'privacy:generate-keys';

    protected $description = 'Print new email encryption and lookup keys (nothing is written anywhere)';

    public function handle(): int
    {
        $this->line('Add these to the server environment (never to git or the database). Keep a secure offline copy: without them the encrypted emails cannot be read.');
        $this->newLine();
        $this->line('EMAIL_ENCRYPTION_KEY=base64:'.base64_encode(random_bytes(32)));
        $this->line('EMAIL_LOOKUP_KEY=base64:'.base64_encode(random_bytes(32)));
        $this->line('PRIVACY_ERASURE_KEY=base64:'.base64_encode(random_bytes(32)).'   (stable: do NOT rotate with the others)');

        return self::SUCCESS;
    }
}
