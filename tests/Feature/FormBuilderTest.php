<?php

use App\Filament\Resources\Events\Pages\EditEvent;
use App\Filament\Resources\Forms\Pages\EditForm;
use App\Models\Company;
use App\Models\Education;
use App\Models\Event;
use App\Models\FormVersion;
use App\Models\User;
use App\Services\CompanyMatcher;
use App\Support\Forms\DefaultForms;
use App\Support\Forms\FormEngine;
use App\Support\Forms\FormSchema;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Jeffgreco13\FilamentBreezy\Middleware\MustTwoFactor;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

function makeEvent(array $attrs = []): Event
{
    return Event::create($attrs + ['name' => 'Bedrijvendag 2027', 'date' => '2027-03-01', 'description' => ['type' => 'doc', 'content' => []]]);
}

function adminUser(): User
{
    test()->seed(RolesAndPermissionsSeeder::class);
    Filament::setCurrentPanel(Filament::getPanel('dashboard'));
    test()->withoutMiddleware(MustTwoFactor::class);
    $user = User::factory()->create();
    $role = Role::create(['name' => 'FormAdmin', 'guard_name' => 'web']);
    $role->givePermissionTo(Permission::whereIn('name', ['forms.view', 'forms.create', 'forms.update', 'forms.delete', 'events.view', 'events.update'])->get());
    $user->assignRole($role);

    return $user;
}

test('every edition gets a unique enrollment token and regenerating invalidates the old one', function () {
    $a = makeEvent();
    $b = makeEvent(['name' => 'Other']);

    expect($a->enrollment_token)->toHaveLength(40)->not->toBe($b->enrollment_token);

    $old = $a->enrollment_token;
    $a->regenerateEnrollmentToken();

    expect($a->fresh()->enrollment_token)->not->toBe($old)
        ->and(Event::where('enrollment_token', $old)->exists())->toBeFalse();
});

test('enrollment state reflects the switch and the opening window', function () {
    $e = makeEvent();
    expect($e->enrollmentState())->toBe('disabled');

    $e->update(['enrollment_enabled' => true]);
    expect($e->enrollmentState())->toBe('open');

    $e->update(['enrollment_opens_at' => now()->addDay()]);
    expect($e->enrollmentState())->toBe('not_open_yet');

    $e->update(['enrollment_opens_at' => now()->subDays(2), 'enrollment_closes_at' => now()->subDay()]);
    expect($e->enrollmentState())->toBe('closed');
});

test('the token cannot be mass assigned and editing terms bumps the terms version', function () {
    $e = makeEvent(['enrollment_token' => 'attacker-chosen']);
    expect($e->enrollment_token)->not->toBe('attacker-chosen');

    $e->update(['terms_html' => '<p>v2</p>']);
    expect($e->fresh()->terms_version)->toBe(2);
});

test('default forms are publishable and every edition inherits the same global forms', function () {
    expect(FormSchema::problems(DefaultForms::enrollment(), 'enrollment'))->toBe([])
        ->and(FormSchema::problems(DefaultForms::info(), 'info'))->toBe([]);

    $first = makeEvent();
    $second = makeEvent(['name' => 'Next']);

    expect($first->enrollmentForm->id)->toBe($second->enrollmentForm->id)
        ->and($first->enrollmentForm->isGlobal())->toBeTrue()->and($first->enrollmentForm->event_id)->toBeNull()
        ->and($first->enrollmentForm->latestVersion->version)->toBe(1);
});

test('system fields cannot be removed or made optional', function () {
    $schema = DefaultForms::enrollment();
    $schema['sections'][0]['fields'] = array_values(array_filter($schema['sections'][0]['fields'], fn ($f) => $f['key'] !== 'company_website'));
    expect(FormSchema::problems($schema, 'enrollment'))->toContain('The system field "company.website" cannot be removed.');

    $schema = DefaultForms::enrollment();
    $schema['sections'][0]['fields'][0]['required'] = false;
    expect(FormSchema::problems($schema, 'enrollment'))->toContain('The system field "company.name" must stay required.');
});

test('schema validation catches duplicate keys, bad options and forward conditions', function () {
    $schema = DefaultForms::enrollment();
    $schema['sections'][0]['fields'][] = ['key' => 'company_name', 'type' => 'text', 'label' => 'dup'];
    $schema['sections'][0]['fields'][] = ['key' => 'pick', 'type' => 'select', 'label' => 'Pick', 'options' => [['value' => 'a', 'label' => 'A'], ['value' => 'a', 'label' => 'B']]];
    $schema['sections'][0]['fields'][] = ['key' => 'cond', 'type' => 'text', 'label' => 'C', 'visible_if' => ['field' => 'later', 'operator' => 'eq', 'value' => 'x']];

    $problems = implode('|', FormSchema::problems($schema, 'enrollment'));

    expect($problems)->toContain('used twice')->toContain('unique, non-empty options')->toContain('earlier question');
});

test('publishing creates immutable versions and old submissions stay interpretable', function () {
    $event = makeEvent();
    $form = $event->enrollmentForm;
    $v1 = $form->latestVersion;

    expect($form->publish()->id)->toBe($v1->id); // unchanged draft: no new version

    $draft = $form->draft_schema;
    $draft['sections'][0]['fields'][0]['label'] = 'Organisation';
    $draft['sections'][0]['fields'][] = ['key' => 'extra_q', 'type' => 'text', 'label' => 'Extra'];
    $form->update(['draft_schema' => $draft]);
    $v2 = $form->publish();

    expect($v2->version)->toBe(2)
        ->and($v1->fresh()->schema['sections'][0]['fields'][0]['label'])->toBe('Company name');

    expect(fn () => $v1->update(['version' => 9]))->toThrow(LogicException::class);

    $draft['sections'][0]['fields'] = array_values(array_filter($draft['sections'][0]['fields'], fn ($f) => $f['key'] !== 'company_website'));
    $form->update(['draft_schema' => $draft]);
    expect(fn () => $form->publish())->toThrow(ValidationException::class);
    expect(FormVersion::where('form_id', $form->id)->count())->toBe(2);
});

test('engine validates generated rules from the stored schema', function () {
    $engine = new FormEngine;
    $edu = Education::create(['name' => 'Mechatronica']);
    $schema = DefaultForms::enrollment();

    $answers = $engine->validate($schema, enrollmentAnswers(['company_website' => 'https://acme.example', 'educations' => [(string) $edu->id]]));
    expect($answers['company_name'])->toBe('Acme BV')->and($answers['terms_accept'])->toBeTrue();

    foreach ([
        ['terms_accept' => false],
        ['contact_email' => 'nope'],
        ['company_website' => 'javascript:alert(1)'],
        ['educations' => ['9999']],
        ['company_name' => ''],
    ] as $bad) {
        expect(fn () => $engine->validate($schema, enrollmentAnswers($bad)))->toThrow(ValidationException::class);
    }
});

test('engine rejects dangerous uploads', function () {
    $engine = new FormEngine;
    $schema = DefaultForms::enrollment();

    expect(fn () => $engine->validate($schema, enrollmentAnswers(['company_logo' => UploadedFile::fake()->create('x.php', 10, 'application/x-php')])))->toThrow(ValidationException::class);
    expect(fn () => $engine->validate($schema, enrollmentAnswers(['company_logo' => UploadedFile::fake()->create('x.svg', 10, 'image/svg+xml')])))->toThrow(ValidationException::class);
    expect(fn () => $engine->validate($schema, enrollmentAnswers(['company_logo' => UploadedFile::fake()->image('big.png')->size(5000)])))->toThrow(ValidationException::class);
    expect($engine->validate($schema, enrollmentAnswers(['company_logo' => UploadedFile::fake()->image('ok.png')])))->toHaveKey('company_logo');
});

test('conditional fields and repeaters follow the schema and the edition lunch limit', function () {
    $engine = new FormEngine;
    $schema = DefaultForms::info();
    $base = ['contact_name' => 'J', 'contact_email' => 'j@x.nl', 'drinks_attending' => 'no'];

    // hidden "other" text is neither required nor kept
    $out = $engine->validate($schema, $base + ['lunch_participants' => [['lunch_diet' => ['vegan'], 'lunch_diet_other' => 'ignored']]], ['max_lunch_participants' => 3]);
    expect($out['lunch_participants'][0])->toBe(['lunch_diet' => ['vegan']])
        ->and($out)->not->toHaveKey('drinks_count');

    // shown and required when "other" is selected
    expect(fn () => $engine->validate($schema, $base + ['lunch_participants' => [['lunch_name' => 'Ann', 'lunch_diet' => ['other']]]]))->toThrow(ValidationException::class);

    // the drinks count is optional and only asked for "yes": validated when given, dropped when hidden
    expect($engine->validate($schema, ['drinks_attending' => 'yes'] + $base)['drinks_count'])->toBeNull();
    expect(fn () => $engine->validate($schema, ['drinks_attending' => 'yes', 'drinks_count' => 0] + $base))->toThrow(ValidationException::class);
    expect($engine->validate($schema, ['drinks_attending' => 'yes', 'drinks_count' => 4] + $base))->toHaveKey('drinks_count');
    expect($engine->validate($schema, ['drinks_attending' => 'no', 'drinks_count' => 4] + $base))->not->toHaveKey('drinks_count');

    // lunch limit comes from the edition
    $four = array_fill(0, 4, ['lunch_name' => 'A', 'lunch_diet' => ['none']]);
    expect(fn () => $engine->validate($schema, $base + ['lunch_participants' => $four], ['max_lunch_participants' => 3]))->toThrow(ValidationException::class);
    expect($engine->validate($schema, $base + ['lunch_participants' => $four], ['max_lunch_participants' => 4]))->toHaveKey('lunch_participants');
});

test('company matcher normalises names and stays conservative', function () {
    $m = new CompanyMatcher;
    $csi = Company::create(['name' => 'C.S.I Incorporated']);
    $bom = Company::create(['name' => 'BOM Engineering B.V.']);
    $abc = Company::create(['name' => 'A.B.C. Technology']);
    $cafe = Company::create(['name' => 'Café Zuid']);
    $other = Company::create(['name' => 'ABC Holding']);

    expect($m->matches('CSI Incorporated')['exact']->pluck('id')->all())->toBe([$csi->id])
        ->and($m->matches('bom  engineering bv')['exact']->pluck('id')->all())->toBe([$bom->id])
        ->and($m->matches('ABC Technology')['exact']->pluck('id')->all())->toBe([$abc->id])
        ->and($m->matches('CAFE zuid')['exact']->pluck('id')->all())->toBe([$cafe->id])
        ->and($m->matches('ABC Technology')['exact']->pluck('id'))->not->toContain($other->id);

    // near-miss is a "similar" hint, never exact; unrelated names do not match at all
    $near = $m->matches('BOM Enginering');
    expect($near['exact'])->toBeEmpty()->and($near['similar']->pluck('id')->all())->toBe([$bom->id]);
    expect($m->matches('Zzz Unrelated')['similar'])->toBeEmpty();
    expect($m->matches('AB')['exact'])->toBeEmpty(); // too short to say anything
});

test('form editor requires permission and saves a draft without publishing', function () {
    $event = makeEvent();
    $form = $event->enrollmentForm;

    $this->actingAs(User::factory()->create());
    Livewire::test(EditForm::class, ['record' => $form->getKey()])->assertForbidden();

    $this->actingAs(adminUser());
    Livewire::test(EditForm::class, ['record' => $form->getKey()])
        ->assertSuccessful()
        ->callAction('publish')
        ->assertNotified();

    expect($form->versions()->count())->toBe(1);
});

test('event page shows the enrollment link and regenerating changes it', function () {
    $event = makeEvent();
    $this->actingAs(adminUser());
    $old = $event->enrollment_token;

    Livewire::test(EditEvent::class, ['record' => $event->getKey()])
        ->assertSuccessful()
        ->assertSee($old)
        ->callAction('regenerateLink');

    expect($event->fresh()->enrollment_token)->not->toBe($old);
});

test('legal suffix handling: equivalents are exact, missing or different forms are only possible matches', function () {
    $m = new CompanyMatcher;
    $acmeBv = Company::create(['name' => 'Acme B.V.']);
    $acmeInc = Company::create(['name' => 'Acme Incorporated']);
    $plain = Company::create(['name' => 'Plain']);

    $ids = fn (string $n, string $k) => $m->matches($n)[$k]->pluck('id')->sort()->values()->all();

    expect($ids('Acme BV', 'exact'))->toBe([$acmeBv->id])
        ->and($ids('ACME inc.', 'exact'))->toBe([$acmeInc->id])
        ->and($ids('Acme BV', 'similar'))->toBe([$acmeInc->id]) // different legal form: hint only
        ->and($ids('Acme', 'exact'))->toBe([])            // missing suffix: never exact
        ->and($ids('Acme', 'similar'))->toBe([$acmeBv->id, $acmeInc->id])
        ->and($ids('Plain BV', 'exact'))->toBe([])
        ->and($ids('Plain BV', 'similar'))->toBe([$plain->id]);
});

test('saving a draft never changes the published version companies are answering', function () {
    $event = makeEvent();
    $form = $event->enrollmentForm;
    $published = $form->latestVersion->schema;

    $this->actingAs(adminUser());
    $draft = $form->draft_schema;
    $draft['sections'][0]['fields'][0]['label'] = 'Renamed draft label';
    $form->update(['draft_schema' => $draft]);

    Livewire::test(EditForm::class, ['record' => $form->getKey()])->call('save')->assertHasNoFormErrors();

    $form->refresh();
    expect($form->versions()->count())->toBe(1)
        ->and($form->latestVersion->schema)->toBe($published)
        ->and($form->draft_schema['sections'][0]['fields'][0]['label'])->toBe('Renamed draft label');
});
