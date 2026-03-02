<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyEvent extends Model
{
    protected $table = 'company_event';

    protected $fillable = [
        'event_id',
        'company_id',
        'stand_number',
        'x_percent',
        'y_percent',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
