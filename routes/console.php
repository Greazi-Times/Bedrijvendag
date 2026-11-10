<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// On by default (AUDIT_RETENTION_SCHEDULED=false disables it); periods in config/audit.php.
Schedule::command('audit:prune')->daily()->when(fn (): bool => (bool) config('audit.retention.scheduled'));

// Failed jobs keep an (encrypted) copy of queued invitation emails; drop them after three days.
Schedule::command('queue:prune-failed --hours=72')->daily();

// Retention (config/privacy.php): daily in the privacy timezone; idempotent, so a missed day only delays it.
Schedule::command('privacy:cleanup')->dailyAt('03:15')->timezone(config('privacy.timezone'))->withoutOverlapping()->onOneServer();
