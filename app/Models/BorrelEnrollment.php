<?php

namespace App\Models;

use App\Casts\EncryptedEmail;
use App\Models\Concerns\Audited;
use App\Models\Concerns\HasEmailHash;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BorrelEnrollment extends Model
{
    use Audited, HasEmailHash;

    protected array $emailHashes = ['email' => 'email_hash'];

    /** Public sign-ups are not logged; admin changes and deletions are, without the email address. */
    protected static $recordEvents = ['updated', 'deleted'];

    protected array $auditAttributes = ['event_id'];

    protected $casts = ['email' => EncryptedEmail::class, 'name' => \App\Casts\EncryptedText::class];

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
