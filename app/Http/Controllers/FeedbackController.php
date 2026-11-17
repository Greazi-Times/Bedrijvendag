<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\FeedbackSurvey;
use App\Services\FeedbackSubmissions;
use App\Support\Forms\FieldType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Public, anonymous and without login. Nothing in a response is tied to a person: no session id, user, IP address or
 * user agent is stored, and a rejected submission never flashes the typed answers back into the session.
 */
class FeedbackController extends Controller
{
    private const MAX_BYTES = 65536;

    public function show(Request $request, Event $event, string $audience): Response
    {
        $survey = $this->survey($event, $audience, hideDisabled: true);
        $state = $survey->state();
        $base = ['state' => $state, 'ui' => __('feedback.ui'), 'event' => ['name' => $event->name], 'audience' => ['key' => $audience, 'label' => __("feedback.audience.{$audience}")]];

        if ($state !== 'open' || ! ($version = $survey->form->latestVersion)) {
            return Inertia::render('Feedback/Show', $base + ['state' => $state === 'open' ? 'closed' : $state, 'stateMessage' => __('feedback.states.'.($state === 'open' ? 'closed' : $state))]);
        }

        $questions = collect($version->schema['sections'] ?? [])->flatMap(fn (array $s) => $s['fields'] ?? [])
            ->filter(fn (array $f): bool => ! FieldType::from($f['type'])->isDisplayOnly())->count();

        return Inertia::render('Feedback/Show', $base + [
            'intro' => __("feedback.intro.{$audience}"),
            'minutes' => max(1, (int) ceil($questions * 20 / 60)),
            'form' => ['version_id' => $version->id, 'schema' => $version->schema],
            'submitted' => (bool) $request->session()->pull('feedback_submitted'),
            'urls' => ['store' => route('feedback.store', ['event' => $event->id, 'audience' => $audience])],
        ]);
    }

    public function store(Request $request, Event $event, string $audience, FeedbackSubmissions $submissions): RedirectResponse
    {
        abort_if((int) $request->header('Content-Length', 0) > self::MAX_BYTES, 413);

        // A disabled survey answers a visitor who had it open with a message, not a 404; nothing is stored either way.
        $survey = $this->survey($event, $audience);
        $done = redirect()->route('feedback.show', ['event' => $event->id, 'audience' => $audience])->with('feedback_submitted', true);

        if (! $survey->isOpen()) {
            return back()->withErrors(['form' => __('feedback.ui.unavailable')]);
        }

        // Bots fill in the hidden field; they get the same friendly answer and nothing is stored.
        if (filled($request->input('website'))) {
            return $done;
        }

        try {
            $submissions->submit($survey, $request->only(['answers', 'form_version_id']));
        } catch (ValidationException $e) {
            // withErrors only: never withInput(), the answers must not end up in the session.
            return back()->withErrors(collect($e->errors())->mapWithKeys(fn (array $m, string $key): array => [$key === 'form' ? $key : "answers.{$key}" => $m[0]])->all());
        }

        return $done;
    }

    /** A disabled survey does not exist as far as the public page is concerned. */
    private function survey(Event $event, string $audience, bool $hideDisabled = false): FeedbackSurvey
    {
        abort_unless(isset(FeedbackSurvey::KINDS[$audience]), 404);

        $survey = FeedbackSurvey::query()->with('form.latestVersion')->where('event_id', $event->id)->where('audience', $audience)->firstOrFail();
        abort_if($hideDisabled && $survey->state() === 'disabled', 404);

        return $survey;
    }
}
