<?php

use App\Filament\Pages\FeedbackResults;
use App\Filament\Resources\Events\Pages\CreateEvent;
use App\Filament\Resources\Events\Pages\EditEvent;
use App\Filament\Resources\Events\RelationManagers\FeedbackSurveysRelationManager;
use App\Filament\Resources\Forms\Pages\EditForm;
use App\Filament\Resources\Forms\Pages\ListForms;
use App\Models\Event;
use App\Models\FeedbackAnswer;
use App\Models\Form;
use App\Services\FeedbackSurveys;
use Livewire\Livewire;

function feedbackAdmin(array $permissions = ['events.view', 'events.update', 'events.create', 'forms.view', 'forms.update', 'feedback.view', 'feedback.update', 'feedback.delete'])
{
    return test()->actingAs(reviewerWithout($permissions));
}

function managerFor(Event $event)
{
    return Livewire::test(FeedbackSurveysRelationManager::class, ['ownerRecord' => $event, 'pageClass' => EditEvent::class]);
}

test('creating an edition in the admin panel creates both surveys', function () {
    feedbackAdmin();

    Livewire::test(CreateEvent::class)
        ->fillForm(['name' => 'Bedrijvendag 2031', 'date' => '2031-03-01', 'description' => ['type' => 'doc', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Hallo']]]]]])
        ->call('create')->assertHasNoFormErrors();

    $event = Event::query()->where('name', 'Bedrijvendag 2031')->firstOrFail();
    expect($event->feedbackSurveys()->count())->toBe(2)->and($event->feedbackSurveys()->pluck('status')->unique()->all())->toBe(['draft']);
});

test('the edition shows both surveys and an administrator can publish, schedule, close and reset them', function () {
    $event = openEvent();
    feedbackAdmin();
    $survey = $event->feedbackSurveys()->where('audience', 'students')->first();

    managerFor($event)->assertCanSeeTableRecords($event->feedbackSurveys)
        ->assertTableActionVisible('publish', $survey)->assertTableActionHidden('unpublish', $survey)
        ->callTableAction('publish', $survey)
        ->assertTableActionVisible('unpublish', $survey)
        ->callTableAction('schedule', $survey, ['opens_at' => now()->addDay(), 'closes_at' => now()->addDays(3)])
        ->callTableAction('close', $survey)
        ->callTableAction('qr', $survey);

    expect($survey->fresh()->status)->toBe('closed')->and($survey->fresh()->opens_at)->not->toBeNull();

    $schema = $survey->form->draft_schema;
    $schema['sections'][0]['fields'][0]['label'] = 'Edited';
    $survey->form->forceFill(['draft_schema' => $schema])->save();
    $survey->form->publish();
    managerFor($event)->callTableAction('reset', $survey);

    expect($survey->form->fresh()->latestVersion->schema['sections'][0]['fields'][0]['label'])->toBe('How would you rate the ATIx Bedrijvendag overall?');
});

test('a survey that cannot be published is reported instead of opened', function () {
    $event = openEvent();
    feedbackAdmin();
    $survey = $event->feedbackSurveys()->first();
    $survey->form->forceFill(['draft_schema' => ['sections' => [['key' => 'aa', 'title' => 'T', 'fields' => [['key' => 'mail', 'type' => 'email', 'label' => 'Email']]]]]])->save();

    managerFor($event)->callTableAction('publish', $survey)->assertNotified('Not possible');

    expect($survey->fresh()->status)->toBe('draft');
});

test('without the feedback permission nobody can change or even see a survey', function () {
    $event = openEvent();
    $survey = $event->feedbackSurveys()->first();
    feedbackAdmin(['events.view', 'feedback.view']);

    managerFor($event)->assertTableActionHidden('publish', $survey)->assertTableActionHidden('reset', $survey);
    Livewire::test(FeedbackResults::class)->assertOk();

    test()->actingAs(reviewerWithout(['events.view', 'forms.view']));
    Livewire::test(FeedbackResults::class)->assertForbidden();
    test()->get(route('feedback.qr', ['survey' => $survey, 'format' => 'svg']))->assertForbidden();
});

test('the QR code downloads for staff and encodes the survey link', function () {
    $survey = openEvent()->feedbackSurveys()->where('audience', 'companies')->first();
    feedbackAdmin();

    $response = test()->get(route('feedback.qr', ['survey' => $survey, 'format' => 'svg']))->assertOk();

    expect($response->headers->get('Content-Type'))->toBe('image/svg+xml')->and($response->getContent())->toContain('<svg');
    $other = openEvent(['name' => 'Other', 'date' => '2028-03-01'])->feedbackSurveys()->where('audience', 'companies')->first();
    expect(\App\Support\Feedback\QrCode::svg($survey->url()))->not->toBe(\App\Support\Feedback\QrCode::svg($other->url()));
    auth()->logout();
    test()->get(route('feedback.qr', ['survey' => $survey, 'format' => 'svg']))->assertRedirect();
});

test('feedback forms are edited with the existing form editor, with rating settings and no identifying question types', function () {
    $survey = openEvent()->feedbackSurveys()->first();
    feedbackAdmin();

    $lw = Livewire::test(EditForm::class, ['record' => $survey->form_id])->assertOk();
    $sections = $lw->get('data.draft_schema.sections');
    $first = array_key_first($sections[array_key_first($sections)]['fields']);
    $sections[array_key_first($sections)]['fields'][$first]['label'] = 'Overall, how was it?';
    $sections[array_key_first($sections)]['fields'][$first]['max'] = 10;
    $lw->set('data.draft_schema.sections', $sections)->call('save')->assertHasNoFormErrors()->callAction('publish');

    $field = $survey->form->fresh()->latestVersion->schema['sections'][0]['fields'][0];
    expect($field['label'])->toBe('Overall, how was it?')->and($field['max'])->toBe(10)->and($field['key'])->toBe('overall_rating')
        ->and($survey->form->fresh()->versions()->count())->toBe(2);
});

test('the global feedback templates are edited in the forms resource and cannot be deleted', function () {
    openEvent();
    feedbackAdmin();
    $template = Form::query()->where('system_key', Form::KEY_STUDENT_FEEDBACK)->firstOrFail();

    Livewire::test(ListForms::class)->set('activeTab', 'feedback')->assertCanSeeTableRecords(Form::query()->whereNotNull('system_key')->whereIn('kind', Form::FEEDBACK_KINDS)->get());
    Livewire::test(EditForm::class, ['record' => $template->id])->assertOk()->assertActionHidden('delete')->assertActionHidden('duplicate');
    expect(fn () => $template->delete())->toThrow(LogicException::class);
});

test('results page: filters, comparison and moderation of written answers', function () {
    $first = openEvent();
    $second = openEvent(['name' => 'Bedrijvendag 2028', 'date' => '2028-03-01']);

    foreach ([[$first, 2], [$second, 4]] as [$event, $rating]) {
        $survey = openSurvey($event);
        test()->post(feedbackUrl($survey), ['answers' => ['overall_rating' => $rating, 'liked_most' => "Nice {$event->name} +31612345678"]])->assertSessionHasNoErrors();
    }
    $answer = FeedbackAnswer::query()->where('question_key', 'liked_most')->orderBy('id')->first();
    feedbackAdmin();

    $page = Livewire::test(FeedbackResults::class, ['events' => [$first->id, $second->id], 'audience' => 'students'])
        ->assertOk()->assertSee('Comparison between editions')->assertSee('Bedrijvendag 2028')->assertSee('+31612345678');

    $page->set('search', '2028')->assertDontSee('Nice Bedrijvendag 2027')->assertSee('Nice Bedrijvendag 2028');
    $page->set('search', '')->call('redact', $answer->id);
    expect($answer->fresh()->value_text)->toBeNull()->and($answer->fresh()->redacted_at)->not->toBeNull();
    $page->call('remove', FeedbackAnswer::query()->where('question_key', 'liked_most')->orderByDesc('id')->value('id'));
    expect(FeedbackAnswer::query()->where('question_key', 'liked_most')->count())->toBe(1);

    // Ratings cannot be removed through the text moderation.
    $page->call('remove', FeedbackAnswer::query()->where('question_key', 'overall_rating')->value('id'));
    expect(FeedbackAnswer::query()->where('question_key', 'overall_rating')->count())->toBe(2);
});

test('a moderator without delete permission cannot redact or remove answers', function () {
    $survey = openSurvey(openEvent());
    test()->post(feedbackUrl($survey), ['answers' => ['overall_rating' => 3, 'liked_most' => 'hello']]);
    $answer = FeedbackAnswer::query()->where('question_key', 'liked_most')->first();
    feedbackAdmin(['feedback.view']);

    Livewire::test(FeedbackResults::class, ['events' => [$survey->event_id]])->assertDontSee('Redact')->call('redact', $answer->id)->assertForbidden();
    expect($answer->fresh()->value_text)->toBe('hello');
});

test('markup in written answers is escaped in the administration', function () {
    $survey = openSurvey(openEvent());
    test()->post(feedbackUrl($survey), ['answers' => ['overall_rating' => 3, 'liked_most' => '<img src=x onerror=alert(1)>']]);
    feedbackAdmin();

    Livewire::test(FeedbackResults::class, ['events' => [$survey->event_id]])->assertDontSeeHtml('<img src=x')->assertSee('&lt;img src=x onerror=alert(1)&gt;', false);
});

test('the results page stays within the Livewire component limit however many questions there are', function () {
    $survey = openSurvey(openEvent());
    $schema = $survey->form->draft_schema;
    foreach (range(1, 60) as $i) {
        $schema['sections'][0]['fields'][] = ['key' => "extra_{$i}", 'type' => 'rating', 'label' => "Extra {$i}", 'max' => 5];
    }
    $survey->form->forceFill(['draft_schema' => $schema])->save();
    $survey->form->publish();
    test()->post(feedbackUrl($survey), ['answers' => ['overall_rating' => 3, 'extra_7' => 5]])->assertSessionHasNoErrors();
    feedbackAdmin();

    $html = Livewire::test(FeedbackResults::class, ['events' => [$survey->event_id]])->assertOk()->html();

    expect(substr_count($html, 'wire:snapshot'))->toBeLessThanOrEqual(1);
    expect(FeedbackSurveys::class)->toBeString();
});
