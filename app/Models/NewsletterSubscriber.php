<?php

namespace App\Models;

use App\Models\Concerns\Audited;
use Illuminate\Database\Eloquent\Model;

class NewsletterSubscriber extends Model
{
    use Audited;

    /** Sign-ups are not logged, only their deletion, without the email address. */
    protected static $recordEvents = ['deleted'];

    protected array $auditAttributes = ['subscribed_at'];

    protected $fillable = ['email', 'subscribed_at'];
}
