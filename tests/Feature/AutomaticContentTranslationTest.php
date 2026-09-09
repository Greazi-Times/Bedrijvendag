<?php

use App\Contracts\TranslatesText;
use App\Jobs\TranslateCompanyDescription;
use App\Jobs\TranslateModelContent;
use App\Models\Company;
use App\Models\ContentTranslation;
use App\Models\Event;
use App\Support\TranslationContent;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    config([
        'services.deepl.key' => 'test-key:fx',
        'services.deepl.url' => 'https://api-free.deepl.test/v2',
        'services.translation.source_locale' => 'nl',
        'services.translation.target_locales' => ['en'],
    ]);
});

test('queues and stores event name and description translations once', function () {
    Bus::fake();

    $event = Event::query()->create([
        'name' => 'Techniekdag',
        'date' => now()->addMonth()->toDateString(),
        'description' => ['html' => '<p>Welkom bij het evenement.</p>'],
    ]);

    Bus::assertDispatched(TranslateModelContent::class, fn (TranslateModelContent $job) => $job->modelClass === Event::class
        && $job->modelId === $event->id
        && $job->targetLocale === 'en'
        && array_keys($job->fieldHashes) === ['name', 'description']);

    $this->assertDatabaseHas('content_translations', [
        'translatable_type' => Event::class,
        'translatable_id' => $event->id,
        'field' => 'name',
        'locale' => 'en',
        'status' => 'pending',
    ]);

    Http::fakeSequence()
        ->push(['translations' => [['text' => 'Technology Day']]])
        ->push(['translations' => [['text' => '<p>Welcome to the event.</p>']]]);

    $job = new TranslateModelContent(Event::class, $event->id, 'en', [
        'name' => TranslationContent::hash('Techniekdag'),
        'description' => TranslationContent::hash('<p>Welkom bij het evenement.</p>'),
    ]);
    $job->handle(app(TranslatesText::class));

    $event->refresh()->load('translations');

    expect($event->translated('name', 'en'))->toBe('Technology Day')
        ->and($event->translated('description', 'en'))->toBe('<p>Welcome to the event.</p>')
        ->and($event->queueAutomaticTranslations())->toBe(0);

    Http::assertSentCount(2);
    Bus::assertDispatchedTimes(TranslateModelContent::class, 1);
});

test('only invalidates the translated field that changed', function () {
    Bus::fake();

    $event = Event::query()->create([
        'name' => 'Bedrijvendag',
        'date' => now()->addMonth()->toDateString(),
        'description' => ['html' => '<p>Oude tekst</p>'],
    ]);

    $event->translations()->where('field', 'name')->update([
        'value' => 'Company Day',
        'status' => 'completed',
    ]);
    $event->translations()->where('field', 'description')->update([
        'value' => '<p>Old text</p>',
        'status' => 'completed',
    ]);

    Bus::fake();
    $event->update(['description' => ['html' => '<p>Nieuwe tekst</p>']]);

    Bus::assertDispatched(TranslateModelContent::class, fn (TranslateModelContent $job) => array_keys($job->fieldHashes) === ['description']);

    $nameTranslation = $event->translations()->where('field', 'name')->firstOrFail();
    $descriptionTranslation = $event->translations()->where('field', 'description')->firstOrFail();

    expect($nameTranslation->value)->toBe('Company Day')
        ->and($nameTranslation->status)->toBe('completed')
        ->and($descriptionTranslation->value)->toBeNull()
        ->and($descriptionTranslation->status)->toBe('pending');
});

test('stores company descriptions in explicit language fields without translation jobs', function () {
    Bus::fake();

    $company = Company::query()->create([
        'name' => 'Van Dijk Techniek',
        'website_url' => 'https://example.test',
        'logo_path' => 'company-logos/van-dijk.svg',
        'description_nl' => '<p>Wij bouwen machines.</p>',
        'description_en' => '<p>We build machines.</p>',
    ]);

    Bus::assertNothingDispatched();

    expect($company->name)->toBe('Van Dijk Techniek')
        ->and($company->website_url)->toBe('https://example.test')
        ->and($company->logo_path)->toBe('company-logos/van-dijk.svg')
        ->and($company->localizedDescription('nl'))->toBe('<p>Wij bouwen machines.</p>')
        ->and($company->localizedDescription('en'))->toBe('<p>We build machines.</p>');
});

test('automatically translates a changed Dutch company description into its English field', function () {
    Bus::fake();

    $company = Company::query()->create([
        'name' => 'Van Dijk Techniek',
        'description_nl' => '<p>Wij bouwen machines.</p>',
    ]);

    Bus::assertDispatched(TranslateCompanyDescription::class, fn (TranslateCompanyDescription $job) => $job->companyId === $company->id
        && $job->sourceHash === TranslationContent::hash('<p>Wij bouwen machines.</p>'));

    Http::fake([
        '*' => Http::response([
            'translations' => [['text' => '<p>We build machines.</p>']],
        ]),
    ]);

    $job = new TranslateCompanyDescription(
        $company->id,
        TranslationContent::hash('<p>Wij bouwen machines.</p>'),
    );
    $job->handle(app(TranslatesText::class));

    expect($company->refresh()->description_en)->toBe('<p>We build machines.</p>');

    Http::assertSent(fn ($request) => $request['source_lang'] === 'NL'
        && $request['target_lang'] === 'EN'
        && $request['tag_handling'] === 'html');

    Bus::fake();
    $company->update(['description_nl' => '<p>Wij ontwerpen robots.</p>']);

    Bus::assertDispatched(TranslateCompanyDescription::class, fn (TranslateCompanyDescription $queuedJob) => $queuedJob->sourceHash === TranslationContent::hash('<p>Wij ontwerpen robots.</p>'));
});

test('serves stored event translations for English and source text for Dutch', function () {
    config(['services.deepl.key' => null]);

    $event = Event::query()->create([
        'name' => 'Bedrijvendag',
        'date' => now()->addMonth()->toDateString(),
        'description' => ['html' => '<p>Ontmoet bedrijven.</p>'],
    ]);

    $event->translations()->createMany([
        [
            'field' => 'name',
            'source_locale' => 'nl',
            'locale' => 'en',
            'source_hash' => TranslationContent::hash('Bedrijvendag'),
            'value' => 'Company Day',
            'status' => 'completed',
        ],
        [
            'field' => 'description',
            'source_locale' => 'nl',
            'locale' => 'en',
            'source_hash' => TranslationContent::hash('<p>Ontmoet bedrijven.</p>'),
            'value' => '<p>Meet companies.</p>',
            'status' => 'completed',
        ],
    ]);

    $this->withSession(['locale' => 'en'])
        ->get(route('events'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('upcoming.0.title', 'Company Day')
            ->where('upcoming.0.description_html', '<p>Meet companies.</p>')
        );

    $this->withSession(['locale' => 'nl'])
        ->get(route('events'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('upcoming.0.title', 'Bedrijvendag')
            ->where('upcoming.0.description_html', '<p>Ontmoet bedrijven.</p>')
        );
});

test('keeps source content available when DeepL is not configured', function () {
    config(['services.deepl.key' => null]);
    Bus::fake();

    $event = Event::query()->create([
        'name' => 'Bedrijvendag',
        'date' => now()->addMonth()->toDateString(),
        'description' => ['html' => '<p>Nederlandse tekst</p>'],
    ]);

    Bus::assertNothingDispatched();

    expect($event->translated('name', 'en'))->toBe('Bedrijvendag')
        ->and($event->translated('description', 'en'))->toBe('<p>Nederlandse tekst</p>')
        ->and(ContentTranslation::query()->count())->toBe(0);
});
