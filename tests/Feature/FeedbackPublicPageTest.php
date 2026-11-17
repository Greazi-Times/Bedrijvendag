<?php

use App\Models\FeedbackAnswer;
use App\Models\FeedbackResponse;
use App\Services\Analytics\FeedbackStats;
use App\Services\FeedbackSurveys;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;

/** The "enabled" migration is what the public pages need: without it the model reads a missing column. */
function enabledMigration(): object
{
    return require database_path('migrations/2026_11_24_000001_replace_feedback_status_with_enabled_flag.php');
}

test('both enabled feedback forms open with HTTP 200 and render their own published questionnaire', function () {
    $event = openEvent();

    foreach (['students' => 'overall_rating', 'companies' => 'stand_facilities'] as $audience => $expectedKey) {
        $survey = openSurvey($event, $audience);
        $version = $survey->form->latestVersion;

        $this->get(feedbackUrl($survey))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Feedback/Show')->where('state', 'open')->where('event.name', $event->name)->where('audience.key', $audience)
            ->where('form.version_id', $version->id)->where('form.schema', $version->schema)
            ->where('form.schema.sections.0.fields', fn ($fields) => collect($fields)->pluck('key')->contains($expectedKey)));
    }
});

test('an edition-specific customisation is what the page renders, while older responses stay readable', function () {
    $survey = openSurvey(openEvent(), 'companies');
    $this->post(feedbackUrl($survey), ['answers' => ['overall_rating' => 4]])->assertSessionHasNoErrors();
    $schema = $survey->form->draft_schema;
    $schema['sections'][0]['fields'][0]['label'] = 'Customised for this edition';
    $survey->form->forceFill(['draft_schema' => $schema])->save();
    $v2 = $survey->form->publish();

    $this->get(feedbackUrl($survey))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('form.version_id', $v2->id)->where('form.schema.sections.0.fields.0.label', 'Customised for this edition'));

    expect(FeedbackResponse::count())->toBe(1)->and(FeedbackResponse::first()->form_version_id)->not->toBe($v2->id);
    $groups = collect(app(FeedbackStats::class)->survey($survey->fresh()))->where('key', 'overall_rating');
    expect($groups->sum(fn ($g) => array_sum($g['dist'])))->toBe(1); // the old answer is still counted
});

test('every availability state answers both GET and POST without a server error, for both audiences', function () {
    $event = openEvent();

    foreach (['students', 'companies'] as $audience) {
        $survey = survey($event, $audience);
        $payload = ['answers' => ['overall_rating' => 3]];

        // Disabled: hidden page, polite rejection of a late submit.
        $this->get(feedbackUrl($survey))->assertNotFound();
        $this->post(feedbackUrl($survey), $payload)->assertSessionHasErrors('form');

        // Scheduled and closed: explanatory page, rejected submit.
        app(FeedbackSurveys::class)->setEnabled($survey, true);
        $survey->update(['opens_at' => now()->addDay(), 'closes_at' => null]);
        $this->get(feedbackUrl($survey))->assertOk()->assertInertia(fn (Assert $p) => $p->where('state', 'not_open_yet')->missing('form'));
        $this->post(feedbackUrl($survey), $payload)->assertSessionHasErrors('form');
        $survey->update(['opens_at' => null, 'closes_at' => now()->subMinute()]);
        $this->get(feedbackUrl($survey))->assertOk()->assertInertia(fn (Assert $p) => $p->where('state', 'closed')->missing('form'));
        $this->post(feedbackUrl($survey), $payload)->assertSessionHasErrors('form');

        // Open: renders and accepts. Switched off by hand while the window is still running: rejected again.
        $survey->update(['closes_at' => now()->addDay()]);
        $this->get(feedbackUrl($survey))->assertOk()->assertInertia(fn (Assert $p) => $p->where('state', 'open')->has('form'));
        app(FeedbackSurveys::class)->setEnabled($survey, false);
        $this->post(feedbackUrl($survey), $payload)->assertSessionHasErrors('form');
    }

    expect(FeedbackResponse::count())->toBe(0);
});

test('missing editions, unknown audiences and a survey without a published questionnaire never cause a server error', function () {
    $survey = openSurvey(openEvent());

    $this->get('/feedback/99999/students')->assertNotFound();
    $this->get("/feedback/{$survey->event_id}/teachers")->assertNotFound();

    DB::table('form_versions')->where('form_id', $survey->form_id)->delete(); // nothing to render
    $this->get(feedbackUrl($survey))->assertOk()->assertInertia(fn (Assert $page) => $page->where('state', 'closed')->missing('form'));
    $this->post(feedbackUrl($survey), ['answers' => ['overall_rating' => 3]])->assertSessionHasErrors('form');
});

test('upgrading a database that still has the old status column keeps every survey and response working', function () {
    $event = openEvent();
    $other = openEvent(['name' => 'Second', 'date' => '2028-03-01']);
    $published = openSurvey($event);
    $this->post(feedbackUrl($published), studentAnswers())->assertSessionHasNoErrors();
    $migration = enabledMigration();

    // Put the table back in its previous shape, with surveys in each of the three old states.
    $migration->down();
    expect(Schema::hasColumn('feedback_surveys', 'enabled'))->toBeFalse()->and(Schema::hasColumn('feedback_surveys', 'status'))->toBeTrue();
    DB::table('feedback_surveys')->where('id', $published->id)->update(['status' => 'published', 'opens_at' => null, 'closes_at' => null]);
    DB::table('feedback_surveys')->where('id', survey($event, 'companies')->id)->update(['status' => 'closed', 'closes_at' => null, 'updated_at' => now()->subDay()]);
    DB::table('feedback_surveys')->where('id', survey($other)->id)->update(['status' => 'draft']);

    $migration->up();

    expect(survey($event)->state())->toBe('open')->and(survey($event, 'companies')->state())->toBe('closed')->and(survey($other)->state())->toBe('disabled');
    $this->get(feedbackUrl(survey($event)))->assertOk()->assertInertia(fn (Assert $page) => $page->where('state', 'open')->has('form'));
    $this->get(feedbackUrl(survey($event, 'companies')))->assertOk()->assertInertia(fn (Assert $page) => $page->where('state', 'closed'));
    $this->get(feedbackUrl(survey($other)))->assertNotFound();
    expect(FeedbackResponse::count())->toBe(1)->and(FeedbackAnswer::count())->toBeGreaterThan(0); // nothing was lost
    expect(Schema::hasColumn('feedback_surveys', 'status'))->toBeFalse();
});

test('the public page works on a database where the migration has run, and fails loudly where it has not', function () {
    $survey = openSurvey(openEvent());
    $this->get(feedbackUrl($survey))->assertOk();

    enabledMigration()->down(); // what an un-migrated database looks like

    // Not hidden behind a friendly page: the application and its schema disagree, and that has to be visible.
    $this->withoutExceptionHandling();
    expect(fn () => $this->get(feedbackUrl($survey)))->toThrow(TypeError::class, 'Argument #1 ($enabled) must be of type bool, null given');
});
