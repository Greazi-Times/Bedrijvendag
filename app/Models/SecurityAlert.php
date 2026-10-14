<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** An open or acknowledged security alert, raised by App\Support\SecurityAlerts. */
class SecurityAlert extends Model
{
    protected $guarded = ['*'];

    protected function casts(): array
    {
        return [
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'acknowledged_at' => 'datetime',
        ];
    }

    public function acknowledgedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    public function ruleLabel(): string
    {
        return config("audit.alerts.rules.{$this->rule}.label") ?? str($this->rule)->headline()->toString();
    }
}
