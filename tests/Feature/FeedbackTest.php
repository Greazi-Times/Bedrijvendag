<?php

use App\Models\Event;
use App\Models\FeedbackAnswer;
use App\Models\FeedbackResponse;
use App\Models\FeedbackSurvey;
use App\Models\Form;
use App\Services\Analytics\FeedbackStats;
use App\Services\FeedbackSurveys;
use App\Services\GlobalForms;
use App\Support\Forms\DefaultFeedbackForms;
use App\Support\Forms\FormSchema;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;

function studentAnswers(array $over = []): array
{
    return ['answers' => $over + ['overall_rating' => 4, 'company_variety' => 5, 'conversation_usefulness' => 'na', 'most_valuable' => ['networking', 'internships'], 'liked_most' => 'The atmosphere', 'recommend' => 'yes']];
}

// ---------------------------------------------------------------- provisioning

test('a new edition automatically gets exactly one student and one company survey, as drafts with a published form', function () {
    $event = openEvent();

    expect($event->feedbackSurveys()->count())->toBe(2);
    expect($event->feedbackSurveys->pluck('audience')->sort()->values()->all())->toBe(['companies', 'students']);

    foreach ($event->feedbackSurveys as $survey) {
        expect($survey->status)->toBe('draft')
            ->and($survey->form->kind)->toBe(FeedbackSurvey::KINDS[$survey->audience])
            ->and($survey->form->event_id)->toBe($event->id)
            ->and($survey->form->latestVersion)->not->toBeNull();
    }

    expect(survey($event)->form->name)->toBe('Student Feedback 2027');
    expect(survey($event, 'companies')->form->name)->toBe('Company Feedback 2027');
});

test('provisioning is idempotent and never overwrites a customised survey', function () {
    $event = openEvent();
    $form = survey($event)->form;
    $form->forceFill(['draft_schema' => ['sections' => [['key' => 'x1', 'title' => 'Own', 'fields' => [['key' => 'own_q', 'type' => 'text', 'label' => 'Mine']]]]]])->save();
    $form->publish();
    $before = [Form::count(), FeedbackSurvey::count()];

    app(FeedbackSurveys::class)->provision($event);
    $event->update(['name' => 'Renamed']); // editing never regenerates

    expect([Form::count(), FeedbackSurvey::count()])->toBe($before);
    expect(survey($event)->form->fresh()->latestVersion->schema['sections'][0]['key'])->toBe('x1');
});

test('the database refuses a second survey of the same audience in one edition', function () {
    $event = openEvent();
    $other = Form::create(['name' => 'x', 'kind' => 'feedback_student', 'event_id' => $event->id, 'draft_schema' => DefaultFeedbackForms::student()]);

    expect(fn () => (new FeedbackSurvey)->forceFill(['event_id' => $event->id, 'audience' => 'students', 'form_id' => $other->id, 'status' => 'draft'])->save())
        ->toThrow(UniqueConstraintViolationException::class);
});

test('existing editions are backfilled without touching what they have', function () {
    $event = openEvent();
    $kept = survey($event, 'companies');
    DB::table('feedback_surveys')->where('id', survey($event)->id)->delete(); // an edition from before this feature
    $legacy = Event::query()->insertGetId(['name' => 'Old', 'date' => '2020-01-01', 'enrollment_token' => 'tok', 'created_at' => now(), 'updated_at' => now()]);

    expect(app(FeedbackSurveys::class)->backfill())->toBe(2);
    expect(FeedbackSurvey::query()->where('event_id', $legacy)->count())->toBe(2)
        ->and(FeedbackSurvey::query()->where('event_id', $event->id)->count())->toBe(2)
        ->and($kept->fresh()->form_id)->toBe($kept->form_id);
    expect(app(FeedbackSurveys::class)->backfill())->toBe(0);
});

test('a failing provisioning rolls the whole edition back', function () {
    $this->mock(FeedbackSurveys::class)->shouldReceive('provision')->andThrow(new RuntimeException('boom'));

    expect(fn () => Event::create(['name' => 'Broken', 'date' => '2030-01-01']))->toThrow(RuntimeException::class);
    expect(Event::query()->where('name', 'Broken')->exists())->toBeFalse();
});

test('deleting an edition removes its surveys and responses but not the global templates', function () {
    $event = openEvent();
    $survey = openSurvey($event);
    $this->post(feedbackUrl($survey), studentAnswers());
    $templates = Form::query()->whereNotNull('system_key')->count();

    $event->delete();

    expect(FeedbackSurvey::count())->toBe(0)->and(FeedbackResponse::count())->toBe(0)->and(FeedbackAnswer::count())->toBe(0)
        ->and(Form::query()->whereNotNull('system_key')->count())->toBe($templates);
});

// ---------------------------------------------------------------- templates

test('the two default templates ship with the specified questions and are valid', function () {
    $student = app(GlobalForms::class)->get('feedback_student');
    $company = app(GlobalForms::class)->get('feedback_company');

    expect($student->name)->toBe('Default Student Feedback')->and($company->name)->toBe('Default Company Feedback');
    expect(collect($student->latestVersion->schema['sections'][0]['fields'])->pluck('key')->all())->toBe(['overall_rating', 'company_variety', 'conversation_usefulness', 'most_valuable', 'liked_most', 'improve', 'recommend']);
    expect($company->latestVersion->schema['sections'][0]['fields'])->toHaveCount(8);
    expect(FormSchema::problems($student->draft_schema, 'feedback_student'))->toBe([])
        ->and(FormSchema::problems($company->draft_schema, 'feedback_company'))->toBe([]);
});

test('editions copy the template at creation and later template changes do not reach existing editions', function () {
    $before = openEvent();
    $template = app(GlobalForms::class)->get('feedback_student');
    $schema = $template->draft_schema;
    $schema['sections'][0]['fields'][0]['label'] = 'Brand new wording';
    $template->forceFill(['draft_schema' => $schema])->save();
    $template->publish();

    $after = openEvent(['name' => 'Next', 'date' => '2028-03-01']);

    expect(survey($before)->form->latestVersion->schema['sections'][0]['fields'][0]['label'])->toBe('How would you rate the ATIx Bedrijvendag overall?')
        ->and(survey($after)->form->latestVersion->schema['sections'][0]['fields'][0]['label'])->toBe('Brand new wording');
});

test('an edition survey can be customised on its own and reset to the template without touching responses', function () {
    $event = openEvent();
    $survey = openSurvey($event);
    $this->post(feedbackUrl($survey), studentAnswers())->assertRedirect();
    $form = $survey->form;
    $schema = $form->draft_schema;
    $schema['sections'][0]['fields'][0]['label'] = 'Edited';
    $form->forceFill(['draft_schema' => $schema])->save();
    $form->publish();
    expect(survey($event, 'companies')->form->latestVersion->schema['sections'][0]['fields'][0]['label'])->not->toBe('Edited');

    app(FeedbackSurveys::class)->reset($survey->fresh());

    expect($form->fresh()->latestVersion->schema['sections'][0]['fields'][0]['label'])->toBe('How would you rate the ATIx Bedrijvendag overall?')
        ->and($form->fresh()->versions()->count())->toBe(3)
        ->and(FeedbackResponse::count())->toBe(1);
});

test('a template or survey with identifying or invalid questions cannot be published', function () {
    $form = survey(openEvent())->form;

    foreach ([
        ['key' => 'mail', 'type' => 'email', 'label' => 'Your email'],
        ['key' => 'upload', 'type' => 'file', 'label' => 'File'],
        ['key' => 'scale', 'type' => 'rating', 'label' => 'Too wide', 'max' => 50],
    ] as $field) {
        $form->forceFill(['draft_schema' => ['sections' => [['key' => 'aa', 'title' => 'T', 'fields' => [$field]]]]])->save();
        expect(fn () => $form->publish())->toThrow(ValidationException::class);
    }

    $form->forceFill(['draft_schema' => ['sections' => [['key' => 'aa', 'title' => 'T', 'fields' => [['key' => 'hd', 'type' => 'heading', 'label' => 'Only text']]]]]])->save();
    expect(fn () => $form->publish())->toThrow(ValidationException::class);
});

// ---------------------------------------------------------------- public submissions

test('anyone can open the public form without logging in', function () {
    $survey = openSurvey(openEvent());

    $this->get(feedbackUrl($survey))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('Feedback/Show')->where('state', 'open')->where('audience.key', 'students')
        ->has('form.schema.sections.0.fields', 7)->has('minutes'));
});

test('a valid anonymous submission is stored without any identifier', function () {
    $survey = openSurvey(openEvent());

    $this->post(feedbackUrl($survey), studentAnswers())->assertRedirect(feedbackUrl($survey));

    $response = FeedbackResponse::firstOrFail();
    expect(array_keys($response->getAttributes()))->toBe(['id', 'feedback_survey_id', 'form_version_id', 'submitted_on']);
    expect($response->form_version_id)->toBe($survey->form->latestVersion->id);
    $answers = $response->answers->groupBy('question_key');
    expect($answers['overall_rating'][0]->value_number)->toBe(4)
        ->and($answers['conversation_usefulness'][0]->value_text)->toBe('na')->and($answers['conversation_usefulness'][0]->value_number)->toBeNull()
        ->and($answers['most_valuable']->pluck('value_text')->sort()->values()->all())->toBe(['internships', 'networking'])
        ->and($answers->has('improve'))->toBeFalse(); // unanswered: no row
    expect(Schema::getColumnListing('feedback_answers'))->not->toContain('ip_address', 'user_id', 'user_agent');
    $this->get(feedbackUrl($survey))->assertInertia(fn (Assert $page) => $page->where('submitted', true));
});

test('required, type, option and question-key rules are enforced on the server', function () {
    $survey = openSurvey(openEvent());
    $reject = fn (array $payload) => $this->post(feedbackUrl($survey), $payload)->assertSessionHasErrors();

    $reject(['answers' => ['company_variety' => 3]]); // overall rating is required
    $reject(studentAnswers(['overall_rating' => 6]));
    $reject(studentAnswers(['overall_rating' => 'abc']));
    $reject(studentAnswers(['company_variety' => 'na'])); // N/A only where the question allows it
    $reject(studentAnswers(['recommend' => 'definitely']));
    $reject(studentAnswers(['most_valuable' => ['hacking']]));
    $reject(studentAnswers(['forged_question' => 'x']));
    $reject(studentAnswers(['liked_most' => str_repeat('a', 2001)]));

    expect(FeedbackResponse::count())->toBe(0);
});

test('a rejected submission never flashes the typed answers back into the session', function () {
    $survey = openSurvey(openEvent());

    $this->post(feedbackUrl($survey), studentAnswers(['overall_rating' => 9, 'liked_most' => 'my secret remark']));

    expect(session()->getOldInput())->toBe([]);
});

test('drafts are invisible, scheduled and closed surveys refuse answers', function () {
    $event = openEvent();
    $draft = survey($event);
    $this->get(feedbackUrl($draft))->assertNotFound();
    $this->post(feedbackUrl($draft), studentAnswers())->assertNotFound();

    $open = openSurvey($event);
    $open->forceFill(['opens_at' => now()->addDay()])->save();
    $this->get(feedbackUrl($open))->assertInertia(fn (Assert $page) => $page->where('state', 'not_open_yet')->missing('form'));
    $this->post(feedbackUrl($open), studentAnswers())->assertSessionHasErrors('form');

    $open->forceFill(['opens_at' => null, 'closes_at' => now()->subMinute()])->save();
    $this->get(feedbackUrl($open))->assertInertia(fn (Assert $page) => $page->where('state', 'closed')->missing('form'));
    $this->post(feedbackUrl($open), studentAnswers())->assertSessionHasErrors('form');

    $open->forceFill(['closes_at' => null])->save();
    app(FeedbackSurveys::class)->close($open);
    $this->post(feedbackUrl($open), studentAnswers())->assertSessionHasErrors('form');

    app(FeedbackSurveys::class)->unpublish($open);
    $this->get(feedbackUrl($open))->assertNotFound();
    expect(FeedbackResponse::count())->toBe(0);
});

test('the honeypot pretends success and stores nothing', function () {
    $survey = openSurvey(openEvent());

    $this->post(feedbackUrl($survey), studentAnswers() + ['website' => 'http://spam.example'])->assertRedirect(feedbackUrl($survey))->assertSessionHasNoErrors();

    expect(FeedbackResponse::count())->toBe(0);
});

test('a form that was updated while someone filled it in asks them to reload', function () {
    $survey = openSurvey(openEvent());
    $old = $survey->form->latestVersion->id;
    $schema = $survey->form->draft_schema;
    $schema['sections'][0]['fields'][0]['label'] = 'Changed';
    $survey->form->forceFill(['draft_schema' => $schema])->save();
    $survey->form->publish();

    $this->post(feedbackUrl($survey), studentAnswers() + ['form_version_id' => $old])->assertSessionHasErrors('form');
    expect(FeedbackResponse::count())->toBe(0);
});

test('oversized requests are refused and unknown audiences do not exist', function () {
    $survey = openSurvey(openEvent());

    $this->call('POST', feedbackUrl($survey), studentAnswers(), [], [], ['HTTP_CONTENT_LENGTH' => 70000])->assertStatus(413);
    $this->get("/feedback/{$survey->event_id}/teachers")->assertNotFound();
    $this->get('/feedback/9999/students')->assertNotFound();
});

test('script tags in written answers are stored as plain text and never rendered as markup', function () {
    $survey = openSurvey(openEvent());
    $payload = '<script>alert(1)</script>';

    $this->post(feedbackUrl($survey), studentAnswers(['liked_most' => $payload]));

    expect(FeedbackAnswer::query()->where('question_key', 'liked_most')->value('value_text'))->toBe($payload);
    // Vue and Blade escape on output; the public page only ever returns the form, never other people's answers.
    $this->get(feedbackUrl($survey))->assertDontSee('alert(1)', false);
});

// ---------------------------------------------------------------- versioning

test('responses keep the version they were answered against and stay readable after the questions change', function () {
    $event = openEvent();
    $survey = openSurvey($event);
    $this->post(feedbackUrl($survey), studentAnswers(['overall_rating' => 2]));
    $v1 = $survey->form->latestVersion;

    // Remove a question, reword another and change the options of the choice question.
    $schema = $survey->form->draft_schema;
    $schema['sections'][0]['fields'] = collect($schema['sections'][0]['fields'])->reject(fn ($f) => $f['key'] === 'company_variety')->map(function ($f) {
        $f['key'] === 'recommend' && $f['options'] = [['value' => 'yes', 'label' => 'Yes'], ['value' => 'no', 'label' => 'No']];

        return $f;
    })->values()->all();
    $survey->form->forceFill(['draft_schema' => $schema])->save();
    $v2 = $survey->form->publish();

    expect($v2->version)->toBe(2)->and($v1->fresh()->schema['sections'][0]['fields'])->toHaveCount(7);
    expect(fn () => $v1->forceFill(['version' => 9])->save())->toThrow(LogicException::class);

    $this->post(feedbackUrl($survey), ['answers' => ['overall_rating' => 5, 'recommend' => 'no']])->assertSessionHasNoErrors();

    $versions = FeedbackResponse::query()->orderBy('id')->pluck('form_version_id')->all();
    expect($versions)->toBe([$v1->id, $v2->id]);
    expect(FeedbackAnswer::query()->where('question_key', 'company_variety')->count())->toBe(1); // history kept
    expect(fn () => $survey->form->forceFill(['event_id' => null])->delete())->toThrow(LogicException::class);
});

// ---------------------------------------------------------------- statistics

test('rating statistics, denominators and not-applicable answers are computed correctly', function () {
    $survey = openSurvey(openEvent());

    foreach ([[5, 'na', ['networking']], [4, 3, ['networking', 'other']], [4, 1, null], [1, null, []]] as [$overall, $usefulness, $valuable]) {
        $answers = ['overall_rating' => $overall];
        $usefulness === null || $answers['conversation_usefulness'] = $usefulness;
        $valuable === null || $answers['most_valuable'] = $valuable;
        $this->post(feedbackUrl($survey), ['answers' => $answers])->assertSessionHasNoErrors();
    }

    $groups = collect(app(FeedbackStats::class)->survey($survey))->keyBy('key');
    $overall = FeedbackStats::rating($groups['overall_rating']);
    expect($overall['n'])->toBe(4)->and($overall['average'])->toBe(3.5)->and($overall['median'])->toBe(4.0)
        ->and(collect($overall['distribution'])->pluck('count')->all())->toBe([1, 0, 0, 2, 1])
        ->and($overall['distribution'][3]['percent'])->toBe(50.0);

    $usefulness = FeedbackStats::rating($groups['conversation_usefulness']);
    expect($usefulness['n'])->toBe(2)->and($usefulness['na'])->toBe(1)->and($usefulness['average'])->toBe(2.0) // N/A is not a rating
        ->and($groups['conversation_usefulness']['asked'])->toBe(4)->and($groups['conversation_usefulness']['respondents'])->toBe(3); // one response skipped it

    // Multi-select: percentages of the 2 people who selected anything, not of all 4 responses.
    $valuable = collect(FeedbackStats::choices($groups['most_valuable']))->keyBy('value');
    expect($groups['most_valuable']['respondents'])->toBe(2)->and($valuable['networking']['count'])->toBe(2)->and($valuable['networking']['percent'])->toBe(100.0)
        ->and($valuable['other']['percent'])->toBe(50.0)->and($valuable['careers']['count'])->toBe(0);
});

test('student and company statistics stay separate per survey and questions of different scales are never merged', function () {
    $event = openEvent();
    $students = openSurvey($event);
    $companies = openSurvey($event, 'companies');
    $this->post(feedbackUrl($students), ['answers' => ['overall_rating' => 5]]);
    $this->post(feedbackUrl($companies), ['answers' => ['overall_rating' => 1]]);

    $stats = app(FeedbackStats::class);
    expect(FeedbackStats::rating($stats->survey($students)[array_key_first($stats->survey($students))])['average'])->toBe(5.0);
    expect(FeedbackStats::rating(collect($stats->survey($companies))->firstWhere('key', 'overall_rating'))['average'])->toBe(1.0);

    // The next edition widens the overall rating to 1-10: same key, other scale, so a separate group.
    $next = openEvent(['name' => 'Next', 'date' => '2028-03-01']);
    $other = openSurvey($next);
    $schema = $other->form->draft_schema;
    $schema['sections'][0]['fields'][0]['max'] = 10;
    $other->form->forceFill(['draft_schema' => $schema])->save();
    $other->form->publish();
    $this->post(feedbackUrl($other), ['answers' => ['overall_rating' => 9]])->assertSessionHasNoErrors();

    $merged = $stats->merge(collect([$students, $other]));
    $overall = collect($merged)->where('key', 'overall_rating');
    expect($overall)->toHaveCount(2);
    expect($overall->every(fn ($g) => count($g['per_survey']) === 1))->toBeTrue();

    // Compatible editions do combine.
    $same = openSurvey(openEvent(['name' => 'Third', 'date' => '2029-03-01']));
    $this->post(feedbackUrl($same), ['answers' => ['overall_rating' => 3]]);
    $both = collect($stats->merge(collect([$students, $same])))->firstWhere('key', 'overall_rating');
    expect(FeedbackStats::rating($both)['average'])->toBe(4.0)->and(array_keys($both['per_survey']))->toBe([$students->id, $same->id]);
});

test('written answers are listed with filters and search, and can be redacted or removed', function () {
    $survey = openSurvey(openEvent());
    $this->post(feedbackUrl($survey), ['answers' => ['overall_rating' => 4, 'liked_most' => 'Great food', 'improve' => 'More chairs']]);
    $this->post(feedbackUrl($survey), ['answers' => ['overall_rating' => 4, 'liked_most' => 'Call me on 0612345678']]);
    $stats = app(FeedbackStats::class);

    expect($stats->texts([$survey->id], null, null)->total())->toBe(3)
        ->and($stats->texts([$survey->id], 'improve', null)->total())->toBe(1)
        ->and($stats->texts([$survey->id], null, 'food')->total())->toBe(1)
        ->and($stats->texts([$survey->id], null, '%')->total())->toBe(0); // wildcards are literal

    $answer = FeedbackAnswer::query()->where('value_text', 'like', 'Call%')->first();
    expect($stats->isText($answer))->toBeTrue()
        ->and($stats->isText(FeedbackAnswer::query()->where('question_key', 'overall_rating')->first()))->toBeFalse();
});

// ---------------------------------------------------------------- retention

test('feedback is kept until a retention period is configured, then removed after the edition ended', function () {
    $old = openEvent(['name' => 'Old', 'date' => now()->subDays(40)->toDateString()]);
    $new = openEvent(['name' => 'New', 'date' => now()->addDays(10)->toDateString()]);

    foreach ([$old, $new] as $event) {
        $this->post(feedbackUrl(openSurvey($event)), studentAnswers())->assertSessionHasNoErrors();
    }

    config(['privacy.retention.feedback_days_after_event' => null]);
    expect((new App\Support\Privacy\PrivacyRetention)->run()['feedback_responses_deleted'])->toBe(0)->and(FeedbackResponse::count())->toBe(2);

    config(['privacy.retention.feedback_days_after_event' => 30]);
    expect((new App\Support\Privacy\PrivacyRetention)->run(dryRun: true)['feedback_responses_deleted'])->toBe(1)->and(FeedbackResponse::count())->toBe(2);
    expect((new App\Support\Privacy\PrivacyRetention)->run()['feedback_responses_deleted'])->toBe(1);
    expect(FeedbackResponse::count())->toBe(1)->and(FeedbackAnswer::count())->toBeGreaterThan(0)->and(FeedbackResponse::first()->survey->event_id)->toBe($new->id);
});
