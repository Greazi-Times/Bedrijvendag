<?php

namespace App\Models;

use App\Models\Concerns\Audited;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BorrelEnrollment extends Model
{
    use Audited;

    /** Public sign-ups are not logged; admin changes and deletions are, without the email address. */
    protected static $recordEvents = ['updated', 'deleted'];

    protected array $auditAttributes = ['event_id', 'name'];

    protected $fillable = [
        'event_id',
        'name',
        'email',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
