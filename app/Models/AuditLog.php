<?php

namespace App\Models;

use App\Support\Audit;
use App\Support\SecurityAlerts;
use LogicException;
use Spatie\Activitylog\Models\Activity;

/**
 * One audit entry. Written through spatie/laravel-activitylog (model events
 * and App\Support\Audit), enriched with request metadata when it is created.
 * Entries are append-only in the application: they cannot be updated, and
 * are only removed by the retention command (audit:prune).
 */
class AuditLog extends Activity
{
    /** Nothing is mass assignable; the logger sets attributes one by one. */
    public $guarded = ['*'];

    protected static function booted(): void
    {
        static::creating(fn (AuditLog $log) => Audit::enrich($log));
        static::created(fn (AuditLog $log) => SecurityAlerts::evaluate($log));

        static::updating(fn () => throw new LogicException('Audit entries cannot be changed.'));
        static::deleting(fn () => throw new LogicException('Audit entries are only removed by audit:prune.'));
    }
}
