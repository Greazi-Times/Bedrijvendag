<?php

namespace App\Models;

use App\Models\Concerns\Audited;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
    protected $fillable = ['status', 'opens_at', 'closes_at'];

    protected $casts = ['opens_at' => 'datetime', 'closes_at' => 'datetime'];

    protected array $auditAttributes = ['status', 'opens_at', 'closes_at'];

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

    /** 'draft' | 'not_open_yet' | 'closed' | 'open': the only place that decides whether answers are accepted. */
    public function state(): string
    {
        return match (true) {
            $this->status === 'draft' => 'draft',
            $this->status === 'closed' || $this->closes_at?->isPast() => 'closed',
            $this->opens_at?->isFuture() => 'not_open_yet',
            default => 'open',
        };
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
