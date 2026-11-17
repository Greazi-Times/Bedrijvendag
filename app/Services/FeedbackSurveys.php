<?php

namespace App\Services;

use App\Models\Event;
use App\Models\FeedbackSurvey;
use App\Models\Form;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Creates, resets and opens the two anonymous feedback surveys of an edition. Everything here is idempotent: running
 * it again never duplicates a survey, never overwrites a customised form and never touches a response.
 */
class FeedbackSurveys
{
    public function __construct(private GlobalForms $globals) {}

    /**
     * Gives the edition the surveys it is missing, each with an independent, published copy of the current global
     * template and switched off. Joins the caller's transaction, so a failure never leaves a half-made edition.
     */
    public function provision(Event $event): void
    {
        foreach (FeedbackSurvey::KINDS as $audience => $kind) {
            if (FeedbackSurvey::query()->where('event_id', $event->id)->where('audience', $audience)->exists()) {
                continue;
            }

            try {
                DB::transaction(function () use ($event, $audience, $kind): void {
                    $template = $this->globals->get($kind);
                    $form = Form::create([
                        'name' => FeedbackSurvey::LABELS[$audience].' '.($event->date?->format('Y') ?? $event->name),
                        'kind' => $kind, 'event_id' => $event->id, 'is_template' => false,
                        'draft_schema' => $template->latestVersion?->schema ?? $template->draft_schema,
                    ]);
                    $form->publish();

                    (new FeedbackSurvey)->forceFill(['event_id' => $event->id, 'audience' => $audience, 'form_id' => $form->id])->save();
                });
            } catch (UniqueConstraintViolationException) {
                // A concurrent run created it first; its form is the one in use.
            }
        }
    }

    /** @return int editions that were missing at least one survey */
    public function backfill(): int
    {
        $count = 0;

        Event::query()->orderBy('id')->each(function (Event $event) use (&$count): void {
            $before = FeedbackSurvey::query()->where('event_id', $event->id)->count();
            $this->provision($event);
            $count += $before < count(FeedbackSurvey::KINDS) ? 1 : 0;
        });

        return $count;
    }

    /**
     * Replaces the survey's questions with the current published global template as a new version. Earlier versions
     * and every response stay as they are.
     */
    public function reset(FeedbackSurvey $survey, ?User $by = null): void
    {
        DB::transaction(function () use ($survey, $by): void {
            $template = $this->globals->get($survey->form->kind);
            $survey->form->forceFill(['draft_schema' => $template->latestVersion?->schema ?? $template->draft_schema])->save();
            $survey->form->publish($by);
        });
    }

    /** Switches the survey on or off. Only the flag changes: no questionnaire version is created and the dates are kept. */
    public function setEnabled(FeedbackSurvey $survey, bool $enabled): void
    {
        $survey->forceFill(['enabled' => $enabled])->save();
    }
}
