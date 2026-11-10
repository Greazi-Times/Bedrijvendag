<?php

namespace App\Console\Commands;

use App\Support\Privacy\ErasureLedger;
use Illuminate\Console\Command;

class PrivacyErase extends Command
{
    protected $signature = 'privacy:erase-email {email : The address to erase (not stored; only a keyed hash is recorded)} {--dry-run}';

    protected $description = 'Erase personal data tied to an email address (privacy request) and record it so it can be replayed after a restore';

    public function handle(): int
    {
        $email = (string) $this->argument('email');
        $hash = \App\Support\Privacy\EmailCrypto::hash($email);

        if (! $hash) {
            $this->error('Empty address.');

            return self::FAILURE;
        }

        $dry = (bool) $this->option('dry-run');
        $count = ErasureLedger::eraseEmail($email, now(), $dry);

        $dry || ErasureLedger::recordEmail($email);
        $this->line(($dry ? 'Would erase ' : 'Erased ').$count.' record(s). Staff accounts (users) and company profile contacts are not touched; handle those in the admin panel.');

        return self::SUCCESS;
    }
}
