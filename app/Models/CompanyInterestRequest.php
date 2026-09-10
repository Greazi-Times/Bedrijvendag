<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyInterestRequest extends Model
{
    protected $fillable = [
        'event_id',
        'company_name',
        'website_url',
        'contact_name',
        'contact_email',
        'message',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
