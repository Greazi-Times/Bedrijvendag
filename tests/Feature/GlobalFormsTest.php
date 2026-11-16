<?php

use App\Filament\Resources\Enrollments\Pages\ViewEnrollment;
use App\Filament\Resources\Events\Pages\CreateEvent;
use App\Filament\Resources\Events\Pages\EditEvent;
use App\Filament\Resources\Forms\Pages\EditForm;
use App\Filament\Resources\Forms\Pages\ListForms;
use App\Models\Company;
use App\Models\CompanyProfileSubmission;
use App\Models\Enrollment;
use App\Models\Event;
use App\Models\Form;
use App\Models\Sector;
use App\Services\EditionForms;
use App\Services\FormResolver;
use App\Services\GlobalForms;
use App\Support\EnrollmentPresenter;
use App\Support\Forms\DefaultForms;
use App\Support\Forms\FieldType;
use App\Support\Forms\FormEngine;
use App\Support\Forms\FormSchema;
use App\Support\Forms\LegacyDefaultForms;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use Livewire\Livewire;

function globalForm(string $kind): Form
{
    return app(GlobalForms::class)->get($kind);
}

/** What the old system did: a per-edition copy of the default forms, wired in with a raw update (no model guard). */
function legacyEditionForm(Event $event, string $kind, string $how = 'untouched'): Form
{
    $schema = $kind === 'enrollment' ? LegacyDefaultForms::enrollment() : LegacyDefaultForms::info();
    $form = Form::create(['name' => "{$event->name} legacy {$kind}", 'kind' => $kind, 'event_id' => $event->id, 'draft_schema' => $schema]);
    $form->publish();

    if ($how === 'edited-draft') {
        $draft = $schema;
        $draft['sections'][0]['fields'][0]['label'] = 'Edited by an admin';
        $form->update(['draft_schema' => $draft]);
    }

    if ($how === 'republished') {
        $draft = $schema;
        $draft['sections'][0]['fields'][0]['label'] = 'Edited and published';
        $form->update(['draft_schema' => $draft]);
        $form->fresh()->publish();
    }

    DB::table('events')->where('id', $event->id)->update([FormResolver::COLUMNS[$kind] => $form->id]);

    return $form->fresh();
}

function publishNewGlobalVersion(string $kind, string $label = 'Company name (v2)'): Form
{
    $form = globalForm($kind);
    $draft = $form->draft_schema;
    $draft['sections'][0]['fields'][1]['label'] = $label;
    $form->update(['draft_schema' => $draft]);
    $form->fresh()->publish();

    return $form->fresh();
}

/* ------------------------------------------------------------ the two global forms exist */

test('both global forms are created automatically with a published first version', function () {
    $forms = Form::whereNotNull('system_key')->whereNotIn('kind', Form::FEEDBACK_KINDS)->get()->keyBy('system_key');

    expect($forms->keys()->sort()->values()->all())->toBe(['default_enrollment', 'default_info']);

    foreach (['default_enrollment' => 'enrollment', 'default_info' => 'info'] as $key => $kind) {
        $form = $forms[$key];
        expect($form->kind)->toBe($kind)->and($form->event_id)->toBeNull()->and($form->is_template)->toBeFalse()->and($form->isGlobal())->toBeTrue()
            ->and($form->latestVersion->version)->toBe(1)->and($form->latestVersion->published_at)->not->toBeNull();
        expect(FormSchema::problems($form->latestVersion->schema, $kind))->toBe([]);
    }
});

test('the global enrollment form contains the required questions and sections', function () {
    $schema = globalForm('enrollment')->latestVersion->schema;
    $titles = collect($schema['sections'])->pluck('title')->all();
    $maps = collect($schema['sections'])->flatMap(fn ($s) => collect($s['fields'])->pluck('maps_to', 'key'))->filter();

    expect($titles)->toBe(['Company information', 'Contact', 'Invoice information', 'Participation fee and terms']);
    expect($maps->values()->sort()->values()->all())->toBe(collect([
        'company.name', 'company.description', 'company.logo', 'company.website', 'company.educations', 'company.sectors', 'contact.email',
        'invoice.street', 'invoice.postal_code', 'invoice.city', 'invoice.country', 'invoice.contact_email', 'invoice.reference', 'terms.accept',
    ])->sort()->values()->all());

    $fields = collect($schema['sections'])->flatMap(fn ($s) => $s['fields'])->keyBy('key');
    expect($fields['company_logo']['type'])->toBe('file')->and($fields['educations']['options_source'])->toBe('educations')->and($fields['sectors']['options_source'])->toBe('sectors')
        ->and($fields['terms_accept']['type'])->toBe('terms')->and($fields['invoice_reference']['required'])->toBeFalse()->and($fields['invoice_street']['required'])->toBeTrue();
});

test('the global information form contains lunch, drinks, promotional materials and the profile verification', function () {
    $schema = globalForm('info')->latestVersion->schema;
    $fields = collect($schema['sections'])->flatMap(fn ($s) => $s['fields'])->keyBy('key');

    expect(collect($schema['sections'])->pluck('title')->all())->toBe(['Company and contact', 'Lunch', 'Closing drinks reception', 'Promotional materials', 'Company details']);
    expect($fields['company_identity']['maps_to'])->toBe('company.identity')->and($fields['contact_email']['maps_to'])->toBe('contact.email')
        ->and($fields['lunch_count']['max_setting'])->toBe('max_lunch_participants')
        ->and($fields['lunch_participants']['type'])->toBe('repeater')->and($fields['lunch_participants']['count_from'])->toBe('lunch_count');
    expect(collect($fields['lunch_participants']['children'])->firstWhere('key', 'lunch_diet')['options'])->toHaveCount(7);
    expect(collect($fields['lunch_participants']['children'])->firstWhere('key', 'lunch_diet_other')['visible_if']['value'])->toBe('other');
    expect(collect($fields['drinks_attending']['options'])->pluck('label')->all())->toBe(['Yes', 'No', 'Not sure yet'])
        ->and($fields['drinks_count']['visible_if']['value'])->toBe('yes')->and($fields['drinks_count']['required'])->toBeFalse();
    expect(collect($fields['promo_materials']['options'])->pluck('label')->all())->toBe([
        'Small: Desk materials', 'Small: Merchandise', 'Small: Brochures', 'Medium: Banners', 'Medium: Small prototypes', 'Medium: Medium promotional displays',
        'Large: Screens', 'Large: Event walls', 'Large: Medium or large prototypes',
    ]);
    expect($fields['company_verification']['type'])->toBe('profile_verification')->and($fields['company_verification']['required'])->toBeTrue();
});

test('initialising again never duplicates a global form or resets an administrator edit', function () {
    $edited = publishNewGlobalVersion('enrollment', 'Changed by an admin');
    $draft = $edited->draft_schema;
    $draft['sections'][0]['fields'][1]['label'] = 'Unpublished draft change';
    $edited->update(['draft_schema' => $draft]);

    $first = app(GlobalForms::class)->initialize();
    $second = app(GlobalForms::class)->initialize();
    $this->artisan('forms:initialize')->assertSuccessful();

    expect($first['created'])->toBe([])->and($second['created'])->toBe([]);
    expect(Form::whereNotNull('system_key')->count())->toBe(4);
    $again = globalForm('enrollment');
    expect($again->versions()->count())->toBe(2)->and($again->latestVersion->schema['sections'][0]['fields'][1]['label'])->toBe('Changed by an admin')
        ->and($again->draft_schema['sections'][0]['fields'][1]['label'])->toBe('Unpublished draft change');
});

test('a missing global form is recreated lazily and creation is idempotent under a race', function () {
    DB::table('forms')->where('system_key', 'default_info')->delete();
    expect(Form::where('system_key', 'default_info')->exists())->toBeFalse();

    $form = app(GlobalForms::class)->get('info');

    expect($form->system_key)->toBe('default_info')->and($form->latestVersion->version)->toBe(1);
    expect(app(GlobalForms::class)->ensure())->toBe([])->and(Form::whereNotNull('system_key')->count())->toBe(4);
});

/* ------------------------------------------------------------ inheritance */

test('a new edition inherits both global forms automatically and its link works at once', function () {
    $event = openEvent();

    expect($event->enrollmentForm->id)->toBe(globalForm('enrollment')->id)->and($event->infoForm->id)->toBe(globalForm('info')->id)
        ->and($event->enrollment_form_id)->toBeNull()->and($event->info_form_id)->toBeNull()
        ->and(Form::whereNotNull('event_id')->whereNotIn('kind', Form::FEEDBACK_KINDS)->count())->toBe(0);

    $this->get(route('enrollment.show', $event->enrollment_token))->assertInertia(fn (Assert $p) => $p
        ->where('state', 'open')->where('form.version_id', globalForm('enrollment')->latestVersion->id));
    expect(submitEnrollment($event))->toBeInstanceOf(Enrollment::class);
});

test('creating an edition in the admin needs no form setup', function () {
    $this->actingAs(reviewerWithout(['events.view', 'events.create', 'events.update']));

    Livewire::test(CreateEvent::class)->fillForm(['name' => 'Bedrijvendag 2029', 'date' => '2029-03-01', 'description' => ['type' => 'doc', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Hallo']]]]]])
        ->call('create')->assertHasNoFormErrors();

    $event = Event::where('name', 'Bedrijvendag 2029')->firstOrFail();
    expect($event->enrollment_token)->toHaveLength(40)->and($event->enrollmentForm->isGlobal())->toBeTrue()->and($event->infoForm->isGlobal())->toBeTrue()
        ->and(Form::whereNotNull('event_id')->whereNotIn('kind', Form::FEEDBACK_KINDS)->count())->toBe(0);
});

test('existing editions holding an untouched generated copy move onto the global forms', function () {
    $event = openEvent();
    $old = legacyEditionForm($event, 'enrollment');
    $oldInfo = legacyEditionForm($event, 'info');
    $before = submitEnrollment($event->refresh(), ['invoice_address' => 'Old street 1, 1000AA Town, NL']); // answered against the old copy's version
    expect($before->form_version_id)->toBe($old->latestVersion->id);

    $report = app(GlobalForms::class)->adoptEditions();

    expect($report[$event->id])->toBe(['enrollment' => 'adopted', 'info' => 'adopted']);
    $event->refresh();
    expect($event->enrollment_form_id)->toBeNull()->and($event->info_form_id)->toBeNull()->and($event->enrollmentForm->isGlobal())->toBeTrue();
    // nothing is deleted: the generated copies and the enrollment's reference stay
    expect(Form::find($old->id))->not->toBeNull()->and(Form::find($oldInfo->id))->not->toBeNull()
        ->and($before->fresh()->form_version_id)->toBe($old->latestVersion->id)->and($before->fresh()->mapped('company.name'))->toBe('Acme BV');
});

test('editions with an edited, republished, shared or unrecognisable form keep it', function () {
    $edited = openEvent(['name' => 'Edited draft']);
    $republished = openEvent(['name' => 'Republished']);
    $shared = openEvent(['name' => 'Shared']);
    $foreign = openEvent(['name' => 'Foreign copy']);
    $a = legacyEditionForm($edited, 'enrollment', 'edited-draft');
    $b = legacyEditionForm($republished, 'enrollment', 'republished');
    $source = legacyEditionForm($shared, 'info');
    DB::table('events')->where('id', $foreign->id)->update(['info_form_id' => $source->id]); // another edition's form

    $report = app(GlobalForms::class)->adoptEditions();

    expect($report[$edited->id]['enrollment'])->toBe('kept: customised')->and($report[$republished->id]['enrollment'])->toBe('kept: customised')
        ->and($report[$shared->id]['info'])->toBe('adopted')->and($report[$foreign->id]['info'])->toBe('kept: not an edition copy');
    expect($edited->fresh()->enrollment_form_id)->toBe($a->id)->and($republished->fresh()->enrollment_form_id)->toBe($b->id)->and($foreign->fresh()->info_form_id)->toBe($source->id);
    // an unrelated pointer is also never trusted by the resolver
    expect(app(FormResolver::class)->resolve($foreign->fresh(), 'info')->isGlobal())->toBeTrue();
    // and repeating the run changes nothing
    expect(app(GlobalForms::class)->adoptEditions()[$edited->id]['enrollment'])->toBe('kept: customised');
});

/* ------------------------------------------------------------ versions: drafts, publishing, in-progress */

test('a global draft edit changes nothing for companies until it is published', function () {
    $event = openEvent();
    $published = globalForm('enrollment')->latestVersion;
    $draft = globalForm('enrollment')->draft_schema;
    $draft['sections'][0]['fields'][1]['label'] = 'Only a draft';
    globalForm('enrollment')->update(['draft_schema' => $draft]);

    expect(app(FormResolver::class)->version($event, 'enrollment')->id)->toBe($published->id);
    $this->get(route('enrollment.show', $event->enrollment_token))->assertInertia(fn (Assert $p) => $p->where('form.version_id', $published->id)->whereNot('form.schema', fn ($s) => str_contains(json_encode($s), 'Only a draft')));
});

test('publishing a global form gives all inheriting editions the new version for future submissions only', function () {
    $a = openEvent(['name' => 'A']);
    $b = openEvent(['name' => 'B']);
    $v1 = globalForm('enrollment')->latestVersion;
    $early = submitEnrollment($a);

    publishNewGlobalVersion('enrollment');
    $v2 = globalForm('enrollment')->latestVersion;

    expect($v2->version)->toBe(2)->and($v2->id)->not->toBe($v1->id);
    foreach ([$a, $b] as $event) {
        expect(app(FormResolver::class)->version($event->fresh(), 'enrollment')->id)->toBe($v2->id);
        $this->get(route('enrollment.show', $event->enrollment_token))->assertInertia(fn (Assert $p) => $p->where('form.version_id', $v2->id));
    }
    $late = submitEnrollment($b->fresh(), ['contact_email' => 'late@x.example']);
    expect($late->form_version_id)->toBe($v2->id)->and($early->fresh()->form_version_id)->toBe($v1->id)
        ->and($early->fresh()->formVersion->schema['sections'][0]['fields'][1]['label'])->toBe('Company description');
});

test('a page opened before a global republish is told to reload, for both forms', function () {
    Mail::fake();
    [$e, $token, $event] = approvedEnrollment();
    $oldEnrollmentVersion = globalForm('enrollment')->latestVersion->id;
    $oldInfoVersion = globalForm('info')->latestVersion->id;

    publishNewGlobalVersion('enrollment');
    $draft = globalForm('info')->draft_schema;
    $draft['sections'][3]['fields'][1]['label'] = 'Describe your materials';
    globalForm('info')->update(['draft_schema' => $draft]);
    globalForm('info')->fresh()->publish();

    expect(fn () => app(App\Services\EnrollmentWorkflow::class)->submit($event, enrollmentPayload($event, [], ['form_version_id' => $oldEnrollmentVersion])))->toThrow(ValidationException::class);
    expect(fn () => app(App\Services\InformationWorkflow::class)->submit($token, infoPayload($event, [], [], ['form_version_id' => $oldInfoVersion])))->toThrow(ValidationException::class);
    expect(Enrollment::count())->toBe(1)->and($e->infoSubmissions()->count())->toBe(0);
});

test('an enrollment answered under an older global version stays readable in the admin', function () {
    $e = submitEnrollment(openEvent(), ['company_name' => 'Readable BV', 'contact_email' => 'r@x.example']);
    publishNewGlobalVersion('enrollment', 'Renamed question');
    $this->actingAs(reviewer());

    Livewire::test(ViewEnrollment::class, ['record' => $e->getKey()])->assertSee('Readable BV')->assertSee('Company description')->assertDontSee('Renamed question');
});

test('enrollments answered against the old per-edition structure stay readable', function () {
    $event = openEvent();
    $legacy = legacyEditionForm($event, 'enrollment');
    $e = Enrollment::create([
        'event_id' => $event->id, 'form_version_id' => $legacy->latestVersion->id, 'company_name' => 'Legacy BV', 'name_key' => 'legacybv#', 'contact_name' => 'L', 'contact_email' => 'l@x.example',
        'answers' => ['company_name' => 'Legacy BV', 'company_description' => 'Old', 'invoice_address' => 'Old street 1, 1000AA Town, NL', 'terms_accept' => true],
        'fee_cents_accepted' => 25000, 'currency_accepted' => 'EUR', 'terms_version_accepted' => 1, 'accepted_at' => now(), 'submission_key' => (string) Illuminate\Support\Str::uuid(), 'submitted_at' => now(),
    ]);

    expect($e->mapped('invoice.address'))->toBe('Old street 1, 1000AA Town, NL')->and($e->mapped('company.name'))->toBe('Legacy BV');
    $rows = collect(EnrollmentPresenter::answers($e))->pluck('value', 'label');
    expect($rows['Invoice address'])->toBe('Old street 1, 1000AA Town, NL');
});

/* ------------------------------------------------------------ edition-specific overrides */

test('customizing an edition copies the published global form into an independent form', function () {
    $a = openEvent(['name' => 'A']);
    $b = openEvent(['name' => 'B']);
    $global = globalForm('enrollment');

    $copy = app(EditionForms::class)->customize($a, 'enrollment');

    expect($copy->isEditionSpecific())->toBeTrue()->and($copy->isGlobal())->toBeFalse()->and($copy->event_id)->toBe($a->id)->and($copy->latestVersion->version)->toBe(1)
        ->and($copy->latestVersion->schema)->toBe($global->latestVersion->schema)->and($a->fresh()->enrollment_form_id)->toBe($copy->id);
    expect(app(FormResolver::class)->isCustomized($a->fresh(), 'enrollment'))->toBeTrue()->and(app(FormResolver::class)->isCustomized($b->fresh(), 'enrollment'))->toBeFalse();
    expect(fn () => app(EditionForms::class)->customize($a->fresh(), 'enrollment'))->toThrow(ValidationException::class);
});

test('global updates never reach a customized edition, and its edits never reach others', function () {
    $a = openEvent(['name' => 'A']);
    $b = openEvent(['name' => 'B']);
    $c = openEvent(['name' => 'C']);
    $forms = app(EditionForms::class);
    $copyA = $forms->customize($a, 'enrollment');
    $copyB = $forms->customize($b, 'enrollment');

    // an edit published in A's own form
    $draft = $copyA->draft_schema;
    $draft['sections'][0]['fields'][1]['label'] = 'Only for edition A';
    $copyA->update(['draft_schema' => $draft]);
    $copyA->fresh()->publish();
    // a global republish
    $newGlobal = publishNewGlobalVersion('enrollment', 'New global wording')->latestVersion;

    $labelOf = fn (Event $e) => collect(app(FormResolver::class)->version($e->fresh(), 'enrollment')->schema['sections'][0]['fields'])->firstWhere('key', 'company_description')['label'];
    expect($labelOf($a))->toBe('Only for edition A')->and($labelOf($b))->toBe('Company description')->and($labelOf($c))->toBe('New global wording');
    expect(app(FormResolver::class)->version($b->fresh(), 'enrollment')->id)->toBe($copyB->latestVersion->id)->and(app(FormResolver::class)->version($c->fresh(), 'enrollment')->id)->toBe($newGlobal->id);
});

test('an edition can only point at its own custom form of the right kind', function () {
    $a = openEvent(['name' => 'A']);
    $b = openEvent(['name' => 'B']);
    $copyB = app(EditionForms::class)->customize($b, 'enrollment');
    $template = Form::create(['name' => 'Template', 'kind' => 'enrollment', 'is_template' => true, 'draft_schema' => DefaultForms::enrollment()]);

    foreach ([$copyB->id, globalForm('enrollment')->id, $template->id, globalForm('info')->id] as $bad) {
        expect(fn () => $a->update(['enrollment_form_id' => $bad]))->toThrow(ValidationException::class);
    }
    expect($a->fresh()->enrollment_form_id)->toBeNull();
    // a forged pointer written around the guard is still ignored when resolving
    DB::table('events')->where('id', $a->id)->update(['enrollment_form_id' => $copyB->id]);
    expect(app(FormResolver::class)->resolve($a->fresh(), 'enrollment')->isGlobal())->toBeTrue();
});

test('an unpublished custom form is never silently replaced by the global one', function () {
    $event = openEvent();
    $draftOnly = Form::create(['name' => 'Draft only', 'kind' => 'enrollment', 'event_id' => $event->id, 'draft_schema' => DefaultForms::enrollment()]);
    $event->update(['enrollment_form_id' => $draftOnly->id]);

    expect(app(FormResolver::class)->version($event->fresh(), 'enrollment'))->toBeNull();
    $this->get(route('enrollment.show', $event->enrollment_token))->assertInertia(fn (Assert $p) => $p->where('state', 'disabled')->where('form', null));
});

test('going back to the global default keeps the custom form, its versions and every answer', function () {
    $event = openEvent();
    $copy = app(EditionForms::class)->customize($event, 'enrollment');
    $answered = submitEnrollment($event->fresh());
    expect($answered->form_version_id)->toBe($copy->latestVersion->id);

    app(EditionForms::class)->revert($event->fresh(), 'enrollment');

    $event->refresh();
    expect($event->enrollment_form_id)->toBeNull()->and($event->enrollmentForm->isGlobal())->toBeTrue()
        ->and(Form::find($copy->id))->not->toBeNull()->and($copy->fresh()->versions()->count())->toBe(1)
        ->and($answered->fresh()->form_version_id)->toBe($copy->latestVersion->id)->and($answered->fresh()->mapped('company.name'))->toBe('Acme BV');
    expect($copy->fresh()->isInUse())->toBeTrue();
    expect(fn () => $copy->fresh()->delete())->toThrow(LogicException::class);
});

test('the edition page shows which form is used and offers customize and revert', function () {
    $event = openEvent();
    $this->actingAs(reviewerWithout(['events.view', 'events.update']));

    $page = Livewire::test(EditEvent::class, ['record' => $event->getKey()])
        ->assertFormSet(['enrollment_form_status' => 'Global default (version 1)', 'info_form_status' => 'Global default (version 1)'])
        ->assertActionVisible('customize_enrollment')->assertActionHidden('global_enrollment')
        ->callAction('customize_enrollment')->assertNotified();

    expect($event->fresh()->enrollment_form_id)->not->toBeNull();
    expect($page->get('data.enrollment_form_status'))->toStartWith('Custom for this edition')->toContain('(version 1)');
    $page->assertActionVisible('global_enrollment')->assertActionHidden('customize_enrollment')
        ->callAction('global_enrollment')->assertNotified();
    expect($event->fresh()->enrollment_form_id)->toBeNull();
});

test('a new edition can start from another edition custom form', function () {
    $source = openEvent(['name' => 'Source']);
    app(EditionForms::class)->customize($source, 'info');
    $draft = $source->fresh()->infoForm->draft_schema;
    $draft['sections'][2]['title'] = 'Drinks (edition wording)';
    $source->fresh()->infoForm->update(['draft_schema' => $draft]);
    $source->fresh()->infoForm->fresh()->publish();
    $this->actingAs(reviewerWithout(['events.view', 'events.create', 'events.update']));

    Livewire::test(CreateEvent::class)->fillForm(['name' => 'Copy edition', 'date' => '2030-03-01', 'description' => ['type' => 'doc', 'content' => []], 'copy_forms_from' => $source->id])->call('create');

    $copy = Event::where('name', 'Copy edition')->firstOrFail();
    expect($copy->infoForm->isEditionSpecific())->toBeTrue()->and($copy->infoForm->id)->not->toBe($source->infoForm->id)
        ->and($copy->infoForm->latestVersion->schema['sections'][2]['title'])->toBe('Drinks (edition wording)')
        ->and($copy->enrollmentForm->isGlobal())->toBeTrue(); // the source inherits this one, so the copy does too
});

/* ------------------------------------------------------------ protecting the global forms */

test('the global forms cannot be deleted, re-homed or turned into something else', function () {
    $global = globalForm('enrollment');
    $event = openEvent();

    expect(fn () => $global->delete())->toThrow(LogicException::class);
    expect(fn () => $global->update(['event_id' => $event->id]))->toThrow(LogicException::class);
    expect(fn () => $global->update(['is_template' => true]))->toThrow(LogicException::class);
    expect(fn () => $global->update(['kind' => 'info']))->toThrow(LogicException::class);
    expect(fn () => $global->forceFill(['system_key' => null])->save())->toThrow(LogicException::class);
    // an ordinary form cannot be turned into a global one, and the database itself allows each key only once
    expect(fn () => Form::create(['name' => 'x', 'kind' => 'info', 'draft_schema' => DefaultForms::info()])->forceFill(['system_key' => 'default_info'])->save())->toThrow(LogicException::class);
    expect(fn () => DB::table('forms')->insert(['name' => 'dup', 'kind' => 'info', 'system_key' => 'default_info', 'is_template' => false, 'draft_schema' => '{}', 'created_at' => now(), 'updated_at' => now()]))->toThrow(Illuminate\Database\UniqueConstraintViolationException::class);
    expect(Form::whereNotNull('system_key')->count())->toBe(4);

    // an ordinary form can never become global through mass assignment
    $plain = Form::create(['name' => 'Plain', 'kind' => 'info', 'draft_schema' => DefaultForms::info(), 'system_key' => 'default_info']);
    expect($plain->system_key)->toBeNull();
});

test('the global forms are listed first, editable but not deletable in the admin', function () {
    $event = openEvent();
    $custom = app(EditionForms::class)->customize($event, 'info');
    $spare = Form::create(['name' => 'Spare custom form', 'kind' => 'info', 'event_id' => $event->id, 'draft_schema' => DefaultForms::info()]);
    $this->actingAs(reviewerWithout(['forms.view', 'forms.create', 'forms.update', 'forms.delete']));

    Livewire::test(ListForms::class)->assertSuccessful()
        ->assertCanSeeTableRecords(Form::whereNotNull('system_key')->whereNotIn('kind', Form::FEEDBACK_KINDS)->get())->assertCanNotSeeTableRecords(collect([$custom]))
        ->set('activeTab', 'custom')->assertCanSeeTableRecords(collect([$custom]))->assertCanNotSeeTableRecords(Form::whereNotNull('system_key')->whereNotIn('kind', Form::FEEDBACK_KINDS)->get());

    Livewire::test(EditForm::class, ['record' => globalForm('enrollment')->getKey()])->assertSuccessful()->assertSee('Global default form')
        ->assertActionHidden('delete')->assertActionVisible('publish')->assertActionVisible('versionHistory')->assertActionVisible('publishedVersion')->assertActionVisible('preview');
    Livewire::test(EditForm::class, ['record' => $spare->getKey()])->assertSuccessful()->assertActionVisible('delete');
    // the form an edition is actively using as its override cannot be deleted from under it
    Livewire::test(EditForm::class, ['record' => $custom->getKey()])->assertActionHidden('delete');
    // forms.delete alone is not enough
    expect($this->app->make(Illuminate\Contracts\Auth\Access\Gate::class)->forUser(auth()->user())->allows('delete', globalForm('info')))->toBeFalse();
});

test('a global form can be edited and published in the editor, creating a new version', function () {
    $this->actingAs(reviewerWithout(['forms.view', 'forms.update']));
    $global = globalForm('info');

    $lw = Livewire::test(EditForm::class, ['record' => $global->getKey()]);
    $sections = $lw->get('data.draft_schema.sections');
    $first = array_key_first($sections);
    $sections[$first]['fields'][(string) Illuminate\Support\Str::uuid()] = ['key' => 'parking', 'type' => 'text', 'label' => 'Parking plate'];
    $lw->set('data.draft_schema.sections', $sections)->call('save')->assertHasNoFormErrors();
    expect($global->fresh()->versions()->count())->toBe(1);

    $lw->callAction('publish')->assertNotified();
    expect($global->fresh()->versions()->count())->toBe(2);
    $lw->assertActionVisible('versionHistory');
    $html = view('filament.forms.versions', ['form' => $global->fresh()->load('versions')])->render();
    expect($html)->toContain('Version 2')->toContain('Version 1')->toContain('current')->toContain('never changed or deleted');
});

/* ------------------------------------------------------------ profile verification component */

test('the profile verification is a protected, single, information-form-only component', function () {
    $info = DefaultForms::info();
    $problems = fn (array $schema, string $kind = 'info') => implode('|', FormSchema::problems($schema, $kind));
    $find = fn (array &$s) => collect($s['sections'])->flatMap(fn ($sec, $si) => collect($sec['fields'])->keys()->map(fn ($fi) => [$si, $fi]))->first(fn ($p) => $s['sections'][$p[0]]['fields'][$p[1]]['key'] === 'company_verification');

    // removal
    $removed = $info;
    [$si, $fi] = $find($removed);
    unset($removed['sections'][$si]['fields'][$fi]);
    expect($problems($removed))->toContain('company.verification');

    // duplicates
    $double = $info;
    $double['sections'][0]['fields'][] = ['key' => 'second_check', 'type' => 'profile_verification', 'label' => 'Again', 'maps_to' => 'company.verification'];
    expect($problems($double))->toContain('only one company profile verification');

    // tampering: retyped, mapping dropped, or placed in the enrollment form
    $retyped = $info;
    $retyped['sections'][$si]['fields'][$fi]['type'] = 'text';
    expect($problems($retyped))->toContain('company.verification');
    $unmapped = $info;
    unset($unmapped['sections'][$si]['fields'][$fi]['maps_to']);
    expect($problems($unmapped))->toContain('must stay a system component');
    $enrollment = DefaultForms::enrollment();
    $enrollment['sections'][0]['fields'][] = ['key' => 'verify_here', 'type' => 'profile_verification', 'label' => 'Check', 'maps_to' => 'company.verification'];
    expect($problems($enrollment, 'enrollment'))->toContain('only available in the information form');

    // its heading, explanation and position can change freely
    $edited = $info;
    $edited['sections'][$si]['fields'][$fi]['label'] = 'Still accurate?';
    $edited['sections'][$si]['fields'][$fi]['description'] = 'Other words';
    expect($problems($edited))->toBe('');
    expect(FieldType::ProfileVerification->isDisplayOnly())->toBeTrue();
});

test('the editor offers the component as a system type and maps it automatically', function () {
    expect(FieldType::labels())->toHaveKey('profile_verification');
    $this->actingAs(reviewerWithout(['forms.view', 'forms.update']));

    Livewire::test(EditForm::class, ['record' => globalForm('info')->getKey()])->assertActionVisible('preview');
    $html = view('filament.forms.preview', ['schema' => globalForm('info')->draft_schema, 'problems' => []])->render();
    expect($html)->toContain('System component')->toContain('Are your company details still correct?')->toContain('profile_verification');
});

test('the profile verification shows the stored profile of exactly this company', function () {
    Mail::fake();
    $edu = App\Models\Education::create(['name' => 'ICT']);
    $mine = Company::create(['name' => 'Alpha BV', 'website_url' => 'https://alpha.example', 'description_nl' => '<p>Alpha text</p>']);
    $mine->educations()->sync([$edu->id]);
    $other = Company::create(['name' => 'Beta BV', 'website_url' => 'https://beta.example', 'description_nl' => '<p>Beta text</p>']);
    [$a, $tokenA] = approvedEnrollment(null, ['company_name' => 'Alpha BV', 'contact_email' => 'a@alpha.example', 'company_description' => 'Alpha text', 'company_website' => 'https://alpha.example'], $mine);
    [$b, $tokenB] = approvedEnrollment(null, ['company_name' => 'Beta BV', 'contact_email' => 'b@beta.example', 'company_description' => 'Beta text', 'company_website' => 'https://beta.example'], $other);

    $this->get(route('information.show', $tokenA))->assertInertia(fn (Assert $p) => $p
        ->where('verification.current.name', 'Alpha BV')->where('verification.current.website_url', 'https://alpha.example')->where('verification.current.educations', ['ICT'])
        ->where('verification.current.description', 'Alpha text')->where('company.name', 'Alpha BV')->where('form.schema.sections.4.fields.0.type', 'profile_verification'));
    $this->get(route('information.show', $tokenB))->assertInertia(fn (Assert $p) => $p->where('verification.current.name', 'Beta BV')->where('verification.current.educations', []));
});

test('confirming creates no review item; changes create a pending submission and never touch the live profile', function () {
    Storage::fake('public');
    $sector = Sector::create(['name' => 'Tech']);
    $company = Company::create(['name' => 'Alpha BV', 'website_url' => 'https://alpha.example', 'description_nl' => '<p>Alpha text</p>']);
    [$e, $token, $event] = approvedEnrollment(null, ['company_name' => 'Alpha BV', 'contact_email' => 'a@alpha.example', 'company_description' => 'Alpha text', 'company_website' => 'https://alpha.example'], $company);

    submitInfo($token, $event, [], ['choice' => 'confirmed']);
    expect(CompanyProfileSubmission::count())->toBe(0)->and($e->infoSubmissions()->sole()->profile_status)->toBe('confirmed');

    submitInfo($token, $event, ['drinks_attending' => 'unsure'], [
        'choice' => 'correction', 'name' => 'Alpha Group BV', 'website_url' => 'https://alpha-group.example', 'description' => '<p>New <strong>text</strong></p>',
        'education_ids' => [], 'sector_ids' => [$sector->id], 'new_sector_names' => ['Biotechnologie'], 'logo' => UploadedFile::fake()->image('new.png'),
    ]);

    $review = CompanyProfileSubmission::sole();
    expect($review->status)->toBe('pending')->and($review->company_id)->toBe($company->id)->and($review->proposed_name)->toBe('Alpha Group BV')
        ->and($review->proposed_website_url)->toBe('https://alpha-group.example')->and($review->proposed_description)->toContain('<strong>text</strong>')
        ->and($review->proposed_sector_ids)->toBe([$sector->id])->and($review->proposed_new_sector_names)->toBe(['Biotechnologie'])
        ->and($e->infoSubmissions()->first()->profile_submission_id)->toBe($review->id);
    Storage::disk('public')->assertExists($review->proposed_logo_path);

    $company->refresh();
    expect($company->name)->toBe('Alpha BV')->and($company->website_url)->toBe('https://alpha.example')->and($company->description_nl)->toBe('<p>Alpha text</p>')->and($company->sectors)->toHaveCount(0)->and($company->logo_path)->toBeNull();
    expect(Sector::where('name', 'Biotechnologie')->exists())->toBeFalse();
});

test('the profile check is a required answer and an unsafe logo or website is refused', function () {
    [$e, $token, $event] = approvedEnrollment();

    expect(fn () => app(App\Services\InformationWorkflow::class)->submit($token, ['form_version_id' => globalForm('info')->latestVersion->id, 'answers' => infoAnswers(), 'verification' => []]))->toThrow(ValidationException::class);
    expect(fn () => submitInfo($token, $event, [], ['choice' => 'correction', 'logo' => UploadedFile::fake()->create('x.png', 5, 'application/x-php')]))->toThrow(ValidationException::class);
    expect(fn () => submitInfo($token, $event, [], ['choice' => 'correction', 'website_url' => 'javascript:alert(1)']))->toThrow(ValidationException::class);
    expect(fn () => submitInfo($token, $event, [], ['choice' => 'correction', 'name' => str_repeat('x', 300)]))->toThrow(ValidationException::class);
    expect(CompanyProfileSubmission::count())->toBe(0)->and($e->infoSubmissions()->count())->toBe(0);
});

test('the same profile proposal is recognised instead of queueing a duplicate', function () {
    [$e, $token, $event] = approvedEnrollment();
    $correction = ['choice' => 'correction', 'website_url' => 'https://new.example'];

    submitInfo($token, $event, [], $correction);
    submitInfo($token, $event, ['drinks_attending' => 'unsure'], $correction);

    expect(CompanyProfileSubmission::where('status', 'pending')->count())->toBe(1)->and(CompanyProfileSubmission::count())->toBe(1)
        ->and($e->infoSubmissions()->count())->toBe(2)->and($e->infoSubmissions()->get()->pluck('profile_submission_id')->unique()->count())->toBe(1);
});

test('one company cannot change another companys profile through its form', function () {
    Mail::fake();
    $event = openEvent();
    [$a, $tokenA] = approvedEnrollment($event, ['company_name' => 'Alpha BV', 'contact_email' => 'a@alpha.example']);
    [$b, $tokenB] = approvedEnrollment($event, ['company_name' => 'Beta BV', 'contact_email' => 'b@beta.example']);
    $beta = Company::where('name', 'Beta BV')->first();

    app(App\Services\InformationWorkflow::class)->submit($tokenA, infoPayload($event, [], ['choice' => 'correction', 'name' => 'Hijacked', 'website_url' => 'https://evil.example'], ['company_id' => $beta->id, 'enrollment_id' => $b->id]));

    expect($beta->fresh()->name)->toBe('Beta BV')->and($beta->fresh()->website_url)->toBeNull()->and($b->infoSubmissions()->count())->toBe(0);
    expect(CompanyProfileSubmission::sole()->company_id)->toBe($a->fresh()->company_id);
});

test('the profile editor of /bedrijf-profiel and the form share one validation and comparison', function () {
    $company = Company::create(['name' => 'Gamma BV', 'website_url' => 'https://gamma.example', 'description_nl' => '<p>Gamma</p>']);
    $service = app(App\Services\CompanyProfileSubmissions::class);

    expect(array_keys(App\Services\CompanyProfileSubmissions::rules()))->toContain('name', 'logo', 'website_url', 'description', 'education_ids.*', 'new_sector_names.*');
    // a plain paragraph and the same text in a <p> wrapper are not a change; bold is
    $plain = ['name' => 'Gamma BV', 'logo_path' => null, 'website_url' => 'https://gamma.example', 'description' => 'Gamma', 'education_ids' => [], 'sector_ids' => [], 'new_sector_names' => []];
    $wrapped = ['description' => '<p>Gamma</p>'] + $plain;
    $bold = ['description' => '<p><strong>Gamma</strong></p>'] + $plain;
    expect($service->comparable($plain))->toBe($service->comparable($wrapped))->and($service->comparable($plain))->not->toBe($service->comparable($bold));
    // the existing link-based route submits through the very same service
    $outcome = $service->submit($company, ['name' => 'Gamma BV', 'website_url' => 'https://changed.example', 'description' => '<p>Gamma</p>'], null, 'C', 'c@x.example');
    expect($outcome['result'])->toBe('created')->and($outcome['submission']->proposed_website_url)->toBe('https://changed.example');
});

/* ------------------------------------------------------------ edition driven rules */

test('the edition lunch limit caps the count question and the repeater, and the repeater follows the count', function () {
    $event = openEvent(['max_lunch_participants' => 2]);
    $engine = new FormEngine;
    $schema = globalForm('info')->latestVersion->schema;
    $base = ['contact_name' => 'J', 'contact_email' => 'j@x.nl', 'drinks_attending' => 'no'];
    $row = fn (string $n) => ['lunch_name' => $n, 'lunch_diet' => ['none']];
    $settings = ['max_lunch_participants' => $event->max_lunch_participants];

    expect(fn () => $engine->validate($schema, $base + ['lunch_count' => 3, 'lunch_participants' => [$row('a'), $row('b'), $row('c')]], $settings))->toThrow(ValidationException::class);
    expect(fn () => $engine->validate($schema, $base + ['lunch_count' => 2, 'lunch_participants' => [$row('a')]], $settings))->toThrow(ValidationException::class); // must match the count
    expect($engine->validate($schema, $base + ['lunch_count' => 2, 'lunch_participants' => [$row('a'), $row('b')]], $settings))->toHaveKey('lunch_participants');
    expect($engine->validate($schema, $base + ['lunch_count' => 0, 'lunch_participants' => []], $settings)['lunch_participants'])->toBe([]);
    $event->update(['max_lunch_participants' => 3]);
    expect($engine->validate($schema, $base + ['lunch_count' => 3, 'lunch_participants' => [$row('a'), $row('b'), $row('c')]], ['max_lunch_participants' => 3]))->toHaveKey('lunch_participants');
});

test('the accepted fee, currency and terms come from the edition, not from the form', function () {
    $event = openEvent(['fee_cents' => 31500, 'currency' => 'USD', 'terms_html' => '<p>Edition terms</p>']);

    $e = submitEnrollment($event);

    expect($e->fee_cents_accepted)->toBe(31500)->and($e->currency_accepted)->toBe('USD')->and($e->terms_html_accepted)->toContain('Edition terms')->and($e->terms_version_accepted)->toBe(1);
    expect(json_encode(globalForm('enrollment')->latestVersion->schema))->not->toContain('31500')->not->toContain('Edition terms');
    $this->get(route('enrollment.show', $event->enrollment_token))->assertInertia(fn (Assert $p) => $p->where('fee.cents', 31500)->where('fee.currency', 'USD')->where('settings.max_lunch_participants', 3));
});

test('the structured invoice address is required, and the old single address question still counts', function () {
    $engine = new FormEngine;
    $schema = globalForm('enrollment')->latestVersion->schema;

    foreach (['invoice_street', 'invoice_postal_code', 'invoice_city', 'invoice_country'] as $missing) {
        expect(fn () => $engine->validate($schema, array_diff_key(enrollmentAnswers(), [$missing => 1])))->toThrow(ValidationException::class);
    }
    expect($engine->validate($schema, enrollmentAnswers()))->toHaveKey('invoice_city');

    $partial = DefaultForms::enrollment();
    $partial['sections'][2]['fields'] = array_values(array_filter($partial['sections'][2]['fields'], fn ($f) => $f['key'] !== 'invoice_city'));
    expect(implode('|', FormSchema::problems($partial, 'enrollment')))->toContain('invoice address questions cannot be removed');
    expect(FormSchema::problems(LegacyDefaultForms::enrollment(), 'enrollment'))->toBe([]); // the single-question address is still a valid alternative
});

test('the enrollment page and search work on the inherited form, with images only for the logo', function () {
    $event = openEvent();
    Company::create(['name' => 'C.S.I Incorporated']);

    $this->getJson(route('enrollment.search', [$event->enrollment_token, 'q' => 'CSI Incorporated']))->assertOk()->assertJsonPath('0.exact', true);
    $schema = globalForm('enrollment')->latestVersion->schema;
    expect(fn () => (new FormEngine)->validate($schema, enrollmentAnswers(['company_logo' => UploadedFile::fake()->create('logo.pdf', 20, 'application/pdf')])))->toThrow(ValidationException::class);
});

/* ------------------------------------------------------------ MySQL compatibility of the new migrations (offline) */

test('the new migrations compile to valid MySQL DDL', function () {
    $connection = new Illuminate\Database\MySqlConnection(fn () => throw new RuntimeException('never connects'), 'x', '', ['driver' => 'mysql', 'prefix' => '']);
    $grammar = new Illuminate\Database\Schema\Grammars\MySqlGrammar($connection);
    $connection->setSchemaGrammar($grammar);
    $blueprint = new Illuminate\Database\Schema\Blueprint($connection, 'forms');
    $blueprint->string('system_key', 40)->nullable()->unique()->after('kind');

    $sql = implode(';', $blueprint->toSql());

    expect($sql)->toContain('alter table `forms` add `system_key` varchar(40) null after `kind`')->toContain('add unique `forms_system_key_unique`(`system_key`)');
});
