<?php

namespace App\Models;

use App\Models\Concerns\Audited;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyInterestRequest extends Model
{
    use Audited;

    /** Public submissions are not logged, only their deletion, without contact details. */
    protected static $recordEvents = ['deleted'];

    protected array $auditAttributes = ['event_id', 'company_name'];

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
