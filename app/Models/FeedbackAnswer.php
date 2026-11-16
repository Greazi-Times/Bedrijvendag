<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeedbackAnswer extends Model
{
    public $timestamps = false;

    protected $guarded = ['*']; // created by FeedbackSubmissions only

    protected $casts = ['redacted_at' => 'datetime'];

    public function response(): BelongsTo
    {
        return $this->belongsTo(FeedbackResponse::class, 'feedback_response_id');
    }
}
