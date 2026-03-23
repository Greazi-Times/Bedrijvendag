<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventStand extends Model
{
    protected $table = 'event_stands';

    protected $fillable = [
        'event_id',
        'company_id',
        'partner_id',
        'type',
        'stand_number',
        'x_percent',
        'y_percent',
    ];

    protected $casts = [
        'x_percent' => 'float',
        'y_percent' => 'float',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $eventStand): void {
            $eventStand->stand_number = (string) ((int) preg_replace('/\D+/', '', (string) $eventStand->stand_number));

            if ($eventStand->type === 'partner' || $eventStand->partner_id) {
                $eventStand->company_id = null;
                $eventStand->type = 'partner';
                return;
            }

            if ($eventStand->type === 'company' || $eventStand->company_id) {
                $eventStand->partner_id = null;
                $eventStand->type = 'company';
            }
        });
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function isPartnerStand(): bool
    {
        return $this->type === 'partner';
    }

    public function isCompanyStand(): bool
    {
        return $this->type === 'company';
    }
}
