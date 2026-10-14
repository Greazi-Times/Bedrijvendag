<?php

namespace App\Models\Concerns;

use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Logs created/updated/deleted for the model through spatie/laravel-activitylog.
 * Only the attributes in $auditAttributes are logged (the fillable ones when
 * the model does not declare it), only when they changed, and never the keys in
 * config('audit.sensitive_attributes'). The category is $auditCategory, which
 * matches the model's permission module, e.g. "events".
 */
trait Audited
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName(property_exists($this, 'auditCategory') ? $this->auditCategory : str($this->getTable())->replace('_', '-')->toString())
            ->logOnly(property_exists($this, 'auditAttributes') ? $this->auditAttributes : $this->getFillable())
            ->logExcept(config('audit.sensitive_attributes'))
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $event): string => str(class_basename($this))->headline()." #{$this->getKey()} {$event}");
    }
}
