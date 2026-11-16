<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Anonymous by design: no user, company, address or device is recorded, only the survey, the form version and the day. */
class FeedbackResponse extends Model
{
    public $timestamps = false;

    protected $guarded = ['*']; // created by FeedbackSubmissions only

    protected $casts = ['submitted_on' => 'date'];

    public function survey(): BelongsTo
    {
        return $this->belongsTo(FeedbackSurvey::class, 'feedback_survey_id');
    }

    public function formVersion(): BelongsTo
    {
        return $this->belongsTo(FormVersion::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(FeedbackAnswer::class);
    }
}
