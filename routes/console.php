<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Off until AUDIT_RETENTION_SCHEDULED=true; review retention requirements first (config/audit.php).
Schedule::command('audit:prune')->daily()->when(fn (): bool => (bool) config('audit.retention.scheduled'));
