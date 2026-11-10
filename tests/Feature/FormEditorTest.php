<?php

use App\Filament\Resources\Forms\Pages\EditForm;
use App\Models\Form;
use App\Support\Forms\FormEngine;
use App\Support\Forms\FormSchema;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use Livewire\Livewire;

/** Opens the real editor for a form as an admin who may edit it. */
function editor(Form $form)
{
    test()->actingAs(reviewerWithout(['forms.view', 'forms.update', 'forms.create', 'forms.delete']));

    return Livewire::test(EditForm::class, ['record' => $form->getKey()]);
}

/** Edits the editor's sections the way the UI does (repeater items keyed by uuid), then saves the draft. */
function editSections($lw, callable $edit): void
{
    $sections = $lw->get('data.draft_schema.sections');
    $edit($sections);
    $lw->set('data.draft_schema.sections', $sections)->call('save')->assertHasNoFormErrors();
}

function fieldKeys(array $schema): array
{
    return collect($schema['sections'])->flatMap(fn ($s) => collect($s['fields'])->pluck('key'))->all();
}

test('the editor round-trips a form without changing it', function () {
    $event = standEvent();
    $form = $event->enrollmentForm;
    $before = $form->draft_schema;

    editor($form)->call('save')->assertHasNoFormErrors();

    expect($form->fresh()->versions()->count())->toBe(1);
    expect($form->fresh()->publish()->id)->toBe($form->latestVersion->id); // nothing changed: no new version
    expect(fieldKeys($form->fresh()->draft_schema))->toBe(fieldKeys($before));
});

test('an administrator can add, rename and use a new question without touching code', function () {
    $event = standEvent();
    $form = $event->enrollmentForm;
    $lw = editor($form);

    editSections($lw, function (&$sections) {
        $first = array_key_first($sections);
        $sections[$first]['fields'][(string) Illuminate\Support\Str::uuid()] = ['key' => 'fav_colour', 'type' => 'select', 'label' => 'Favourite colour', 'required' => true,
            'options' => [(string) Illuminate\Support\Str::uuid() => ['value' => 'red', 'label' => 'Red'], (string) Illuminate\Support\Str::uuid() => ['value' => 'blue', 'label' => 'Blue']]];
        $fieldKey = array_key_first($sections[$first]['fields']);
        $sections[$first]['fields'][$fieldKey]['label'] = 'Company name (renamed)';
    });

    // saving only changes the draft
    expect($form->fresh()->versions()->count())->toBe(1)->and(fieldKeys($form->fresh()->latestVersion->schema))->not->toContain('fav_colour');

    $lw->callAction('publish')->assertNotified();
    $form->refresh();
    expect($form->versions()->count())->toBe(2)->and(fieldKeys($form->latestVersion->schema))->toContain('fav_colour');

    // the public page and validation now use the new version, and the key stays stable although the label changed
    $this->get(route('enrollment.show', $event->enrollment_token))->assertInertia(fn (Assert $p) => $p->where('form.version_id', $form->latestVersion->id));
    expect(fn () => (new FormEngine)->validate($form->latestVersion->schema, enrollmentAnswers()))->toThrow(ValidationException::class); // new required question
    expect((new FormEngine)->validate($form->latestVersion->schema, enrollmentAnswers(['fav_colour' => 'red'])))->toHaveKey('fav_colour');
    expect(collect($form->latestVersion->schema['sections'][0]['fields'])->firstWhere('key', 'company_name')['label'])->toBe('Company name (renamed)');
});

test('an optional question can be removed, a mandatory one cannot be published away', function () {
    $event = standEvent();
    $form = $event->enrollmentForm;

    // add a custom question, publish, then remove it again
    $lw = editor($form);
    editSections($lw, function (&$sections) {
        $first = array_key_first($sections);
        $sections[$first]['fields'][(string) Illuminate\Support\Str::uuid()] = ['key' => 'optional_q', 'type' => 'text', 'label' => 'Optional'];
    });
    $lw->callAction('publish');
    editSections($lw, function (&$sections) {
        foreach ($sections as $sk => $section) {
            foreach ($section['fields'] as $fk => $field) {
                if ($field['key'] === 'optional_q') {
                    unset($sections[$sk]['fields'][$fk]);
                }
            }
        }
    });
    $lw->callAction('publish');
    expect(fieldKeys($form->fresh()->latestVersion->schema))->not->toContain('optional_q')->and($form->fresh()->versions()->count())->toBe(3);

    // removing a mandatory mapped question is refused at publish time and leaves the published form alone
    $publishedBefore = $form->fresh()->latestVersion->id;
    editSections($lw, function (&$sections) {
        foreach ($sections as $sk => $section) {
            foreach ($section['fields'] as $fk => $field) {
                if ($field['key'] === 'company_website') {
                    unset($sections[$sk]['fields'][$fk]);
                }
            }
        }
    });
    $lw->callAction('publish')->assertNotified('Cannot publish');
    expect($form->fresh()->latestVersion->id)->toBe($publishedBefore);
});

test('questions and sections can be reordered and the public form follows the published order', function () {
    $event = standEvent();
    $form = $event->enrollmentForm;
    $original = $form->latestVersion->schema;
    $lw = editor($form);

    editSections($lw, function (&$sections) {
        $sections = array_reverse($sections, true);
        foreach ($sections as $k => $section) {
            $sections[$k]['fields'] = array_reverse($section['fields'], true);
        }
    });

    // draft changed, published unchanged
    expect($form->fresh()->draft_schema['sections'][0]['key'])->toBe(array_reverse($original['sections'])[0]['key'])
        ->and($form->fresh()->latestVersion->schema)->toBe($original);

    $lw->callAction('publish')->assertNotified();
    $this->get(route('enrollment.show', $event->enrollment_token))->assertInertia(fn (Assert $p) => $p->where('form.schema.sections.0.key', array_reverse($original['sections'])[0]['key']));
});

test('conditional questions can be configured in the editor and are enforced', function () {
    $event = standEvent();
    $form = $event->enrollmentForm;
    $lw = editor($form);

    editSections($lw, function (&$sections) {
        $first = array_key_first($sections);
        $sections[$first]['fields'][(string) Illuminate\Support\Str::uuid()] = ['key' => 'vat_number', 'type' => 'text', 'label' => 'VAT number', 'required' => true, 'visible_if' => ['field' => 'company_website', 'operator' => 'filled']];
    });
    $lw->callAction('publish')->assertNotified();
    $schema = $form->fresh()->latestVersion->schema;
    $engine = new FormEngine;

    expect($engine->validate($schema, enrollmentAnswers()))->not->toHaveKey('vat_number'); // no website: question hidden, not required
    expect(fn () => $engine->validate($schema, enrollmentAnswers(['company_website' => 'https://x.example'])))->toThrow(ValidationException::class);
    expect($engine->validate($schema, enrollmentAnswers(['company_website' => 'https://x.example', 'vat_number' => 'NL123'])))->toHaveKey('vat_number');
});

test('nested repeater questions and their options can be edited in the second form', function () {
    $event = standEvent();
    $form = $event->infoForm;
    $lw = editor($form);

    editSections($lw, function (&$sections) {
        foreach ($sections as $sk => $section) {
            foreach ($section['fields'] as $fk => $field) {
                if ($field['key'] === 'lunch_participants') {
                    $sections[$sk]['fields'][$fk]['children'][(string) Illuminate\Support\Str::uuid()] = ['key' => 'lunch_seat', 'type' => 'text', 'label' => 'Preferred seat', 'required' => true];
                }
                foreach ($field['children'] ?? [] as $ck => $child) {
                    if ($child['key'] === 'lunch_diet') {
                        $sections[$sk]['fields'][$fk]['children'][$ck]['options'][(string) Illuminate\Support\Str::uuid()] = ['value' => 'kosher', 'label' => 'Kosher'];
                    }
                }
            }
        }
    });
    $lw->callAction('publish')->assertNotified();
    $schema = $form->fresh()->latestVersion->schema;
    $base = ['contact_name' => 'J', 'contact_email' => 'j@x.nl', 'drinks_attending' => 'no'];
    $engine = new FormEngine;

    expect(fn () => $engine->validate($schema, $base + ['lunch_participants' => [['lunch_name' => 'A', 'lunch_diet' => ['kosher']]]]))->toThrow(ValidationException::class); // new required child
    expect($engine->validate($schema, $base + ['lunch_participants' => [['lunch_name' => 'A', 'lunch_diet' => ['kosher'], 'lunch_seat' => '3']]]))->toHaveKey('lunch_participants');
    expect(FormSchema::problems($schema, 'info'))->toBe([]);
});

test('changing options keeps old answers readable under their original wording', function () {
    $event = standEvent();
    $form = $event->infoForm;
    $before = $form->latestVersion;
    $lw = editor($form);

    editSections($lw, function (&$sections) {
        foreach ($sections as $sk => $section) {
            foreach ($section['fields'] as $fk => $field) {
                if ($field['key'] === 'drinks_attending') {
                    foreach ($field['options'] as $ok => $option) {
                        if ($option['value'] === 'unsure') {
                            $sections[$sk]['fields'][$fk]['options'][$ok]['label'] = 'Maybe';
                        }
                    }
                }
            }
        }
    });
    $lw->callAction('publish')->assertNotified();

    $labelIn = fn ($version) => collect(collect($version->schema['sections'][2]['fields'])->firstWhere('key', 'drinks_attending')['options'])->firstWhere('value', 'unsure')['label'];
    expect($labelIn($before->fresh()))->toBe('Not sure yet')->and($labelIn($form->fresh()->latestVersion))->toBe('Maybe');
});

test('an administrator can duplicate a form into an edition or as a template', function () {
    $event = standEvent(['name' => 'First']);
    $target = standEvent(['name' => 'Second']);
    $form = $event->enrollmentForm;
    $lw = editor($form);

    $lw->callAction('duplicate', ['name' => 'Copy for second', 'event_id' => $target->id]);
    $copy = Form::where('name', 'Copy for second')->firstOrFail();
    expect($copy->event_id)->toBe($target->id)->and($copy->is_template)->toBeFalse()->and($copy->id)->not->toBe($form->id)->and($copy->versions()->count())->toBe(0)
        ->and($copy->draft_schema)->toBe($form->latestVersion->schema);

    $lw->callAction('duplicate', ['name' => 'Reusable', 'event_id' => null]);
    expect(Form::where('name', 'Reusable')->firstOrFail()->is_template)->toBeTrue();
});

test('the editor shows protected fields as protected and never offers their removal', function () {
    $event = standEvent();
    editor($event->enrollmentForm)->assertSee('feeds the enrollment process')->assertSee('cannot be deleted');
});
