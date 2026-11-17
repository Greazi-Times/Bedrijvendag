<?php

namespace App\Models;

use App\Models\Concerns\Audited;
use App\Support\Availability;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

/** One anonymous questionnaire of an edition: exactly one per audience. Its questions live in a (versioned) Form. */
class FeedbackSurvey extends Model
{
    use Audited;

    public const STUDENTS = 'students';

    public const COMPANIES = 'companies';

    /** audience => form kind */
    public const KINDS = [self::STUDENTS => 'feedback_student', self::COMPANIES => 'feedback_company'];

    public const LABELS = [self::STUDENTS => 'Student Feedback', self::COMPANIES => 'Company Feedback'];

    // The relations (event, form, audience) are set by the provisioning service only.
    protected $fillable = ['enabled', 'opens_at', 'closes_at'];

    protected $casts = ['enabled' => 'boolean', 'opens_at' => 'datetime', 'closes_at' => 'datetime'];

    protected array $auditAttributes = ['enabled', 'opens_at', 'closes_at'];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    public function responses(): HasMany
    {
        return $this->hasMany(FeedbackResponse::class);
    }

    public function audienceLabel(): string
    {
        return self::LABELS[$this->audience];
    }

    protected static function booted(): void
    {
        // Same rule as the form fields, enforced for every code path.
        static::saving(function (self $survey): void {
            if ($survey->opens_at && $survey->closes_at && $survey->closes_at->lte($survey->opens_at)) {
                throw ValidationException::withMessages(['closes_at' => 'The closing date must be after the opening date.']);
            }
        });
    }

    /** 'disabled' | 'not_open_yet' | 'closed' | 'open': the only place that decides whether answers are accepted. */
    public function state(): string
    {
        return Availability::state($this->enabled, $this->opens_at, $this->closes_at);
    }

    public function isOpen(): bool
    {
        return $this->state() === 'open';
    }

    public function url(): string
    {
        return route('feedback.show', ['event' => $this->event_id, 'audience' => $this->audience]);
    }
}
