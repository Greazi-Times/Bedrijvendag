<?php

use App\Filament\Resources\Forms\Schemas\FormBuilderSchema;
use App\Support\Forms\DefaultForms;
use App\Support\Forms\FormSchema;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;

function keysEditor(App\Models\Form $form)
{
    test()->actingAs(reviewerWithout(['forms.view', 'forms.update', 'forms.create', 'forms.delete']));

    return Livewire\Livewire::test(App\Filament\Resources\Forms\Pages\EditForm::class, ['record' => $form->getKey()]);
}

function keysEdit($lw, callable $edit): void
{
    $sections = $lw->get('data.draft_schema.sections');
    $edit($sections);
    $lw->set('data.draft_schema.sections', $sections)->call('save')->assertHasNoFormErrors();
}

function allFields(array $schema): array
{
    $out = [];
    $walk = function (array $fields) use (&$walk, &$out): void {
        foreach ($fields as $f) {
            $out[] = $f;
            $walk($f['children'] ?? []);
        }
    };
    foreach ($schema['sections'] as $section) {
        $walk($section['fields']);
    }

    return $out;
}

it('does not ask for individual names in the default forms at all', function () {
    foreach ([DefaultForms::enrollment(), DefaultForms::info()] as $schema) {
        foreach (allFields($schema) as $field) {
            $personal = preg_match('/(first|last|full|participant|invoice contact)\s*name|voornaam|achternaam/i', (string) ($field['label'] ?? ''))
                || in_array($field['key'], ['lunch_name', 'invoice_contact_name'], true);
            expect($personal)->toBeFalse("{$field['key']} asks for a personal name");
        }
    }

    // Contacts are identified by email address: no default form maps a name at all.
    foreach ([DefaultForms::enrollment(), DefaultForms::info()] as $schema) {
        $maps = collect(allFields($schema))->pluck('maps_to')->filter();
        expect($maps->contains('contact.name'))->toBeFalse()->and($maps->contains('invoice.contact_name'))->toBeFalse()->and($maps->contains('contact.email'))->toBeTrue();
    }
});

it('has no example names, placeholders or defaults that look like personal data', function () {
    foreach ([DefaultForms::enrollment(), DefaultForms::info()] as $schema) {
        foreach (allFields($schema) as $field) {
            foreach (['placeholder', 'default'] as $attribute) {
                $value = (string) ($field[$attribute] ?? '');
                expect($value === '' || in_array($value, ['https://', 'Nederland'], true))->toBeTrue("{$field['key']} has {$attribute} '{$value}'");
            }
        }
    }
});

it('still validates the new defaults and old forms that kept the removed fields', function () {
    expect(FormSchema::problems(DefaultForms::enrollment(), 'enrollment'))->toBe([])
        ->and(FormSchema::problems(DefaultForms::info(), 'info'))->toBe([]);

    $old = DefaultForms::enrollment();
    $old['sections'][2]['fields'][] = ['key' => 'invoice_contact_name', 'type' => 'text', 'label' => 'Invoice contact name', 'required' => true, 'maps_to' => 'invoice.contact_name'];

    expect(FormSchema::problems($old, 'enrollment'))->toBe([]);
});

it('does not change a global form an administrator already has', function () {
    standEvent();
    $form = App\Models\Form::query()->where('system_key', App\Models\Form::GLOBAL_KEYS['enrollment'])->firstOrFail();
    $before = $form->draft_schema;
    $form->draft_schema = FormBuilderSchema::ensureKeys($before);
    $form->save();

    expect($form->fresh()->draft_schema)->toBe($before);
});

it('generates valid unique keys for new, keyless and duplicated questions and keeps existing ones', function () {
    $schema = ['sections' => [[
        'key' => 'intro', 'title' => 'A',
        'fields' => [
            ['key' => 'q_keepme01', 'type' => 'text', 'label' => 'Renamed label'],
            ['type' => 'text', 'label' => 'Brand new'],
            ['key' => 'q_keepme01', 'type' => 'text', 'label' => 'Copy of the first'],
            ['key' => 'rep', 'type' => 'repeater', 'label' => 'R', 'children' => [['type' => 'text', 'label' => 'child'], ['key' => 'rep', 'type' => 'text', 'label' => 'dup of parent']]],
        ],
    ], ['title' => 'No key section', 'fields' => []]]];

    $out = FormBuilderSchema::ensureKeys($schema);
    $fields = $out['sections'][0]['fields'];
    $all = collect(allFields($out))->pluck('key')->push(...collect($out['sections'])->pluck('key'));

    expect($fields[0]['key'])->toBe('q_keepme01')                    // untouched, even though its label changed
        ->and($fields[2]['key'])->not->toBe('q_keepme01')           // duplicate gets a new identifier
        ->and($fields[3]['key'])->toBe('rep')
        ->and($fields[3]['children'][1]['key'])->not->toBe('rep')
        ->and($all->unique()->count())->toBe($all->count())
        ->and($all->every(fn ($k) => (bool) preg_match('/^[a-z0-9_]{2,64}$/', $k)))->toBeTrue()
        ->and($out['sections'][0]['key'])->toBe('intro')
        ->and($out['sections'][1]['key'])->toStartWith('s_');

    expect(FormBuilderSchema::ensureKeys($out))->toBe($out); // idempotent: never regenerates keys that are fine
});

it('keeps keys through renaming, reordering and saving in the editor, and never shows them', function () {
    $form = standEvent()->enrollmentForm;
    $lw = keysEditor($form);
    $before = $form->draft_schema;

    keysEdit($lw, function (&$sections) {
        $first = array_key_first($sections);
        $fields = $sections[$first]['fields'];
        $k = array_key_first($fields);
        $fields[$k]['label'] = 'Company (renamed)';
        $sections[$first]['fields'] = array_reverse($fields, true);
    });

    $after = $form->fresh()->draft_schema;
    $keysBefore = collect(allFields($before))->pluck('key')->sort()->values()->all();
    $keysAfter = collect(allFields($after))->pluck('key')->sort()->values()->all();
    expect($keysAfter)->toBe($keysBefore);

    // No key control is a visible input; the key lives only in hidden state.
    $components = collect($lw->instance()->form->getFlatComponents(withHidden: true));
    $keyInputs = $components->filter(fn ($c) => method_exists($c, 'getName') && $c->getName() === 'key');
    expect($keyInputs->isNotEmpty())->toBeTrue()->and($keyInputs->every(fn ($c) => $c instanceof Hidden))->toBeTrue()
        ->and($components->contains(fn ($c) => $c instanceof TextInput && in_array($c->getName(), ['key', 'maps_to'], true)))->toBeFalse();

    $lw->assertDontSee('Stable internal id')->assertDontSee('System property');
});

it('adds a question in the editor with an automatic key that survives later edits', function () {
    $form = standEvent()->enrollmentForm;
    $lw = keysEditor($form);
    $existing = collect(allFields($form->draft_schema))->pluck('key')->all();

    keysEdit($lw, function (&$sections) {
        $first = array_key_first($sections);
        $sections[$first]['fields'][(string) Illuminate\Support\Str::uuid()] = ['type' => 'text', 'label' => 'Parking needs', 'required' => false];
    });

    $new = collect(allFields($form->fresh()->draft_schema))->firstWhere('label', 'Parking needs');
    expect($new['key'])->toMatch('/^q_[a-z0-9]{8}$/')->and($existing)->not->toContain($new['key']);

    keysEdit($lw, function (&$sections) {
        foreach ($sections as $s => $section) {
            foreach ($section['fields'] as $id => $field) {
                if (($field['label'] ?? null) === 'Parking needs') {
                    $sections[$s]['fields'][$id]['label'] = 'Parking requirements';
                }
            }
        }
    });

    $renamed = collect(allFields($form->fresh()->draft_schema))->firstWhere('label', 'Parking requirements');
    expect($renamed['key'])->toBe($new['key']);
});

it('publishes the preview without showing internal identifiers', function () {
    $html = view('filament.forms.preview', ['schema' => DefaultForms::info(), 'problems' => []])->render();

    expect($html)->not->toContain('q_')->not->toContain('lunch_diet')->not->toContain('maps_to')->not->toContain('company.verification');
});

it('accepts an enrollment without any contact name and greets neutrally', function () {
    Illuminate\Support\Facades\Mail::fake();
    $event = openEvent();
    $payload = enrollmentPayload($event);
    unset($payload['answers']['contact_name']);

    $enrollment = app(App\Services\EnrollmentWorkflow::class)->submit($event, $payload);

    expect($enrollment->contact_name)->toBeNull()->and($enrollment->contact_email)->toBe('jane@acme.example');
    Illuminate\Support\Facades\Mail::assertQueued(App\Mail\EnrollmentReceivedMail::class, fn ($m) => $m->hasTo('jane@acme.example') && str_contains($m->render(), 'Beste relatie,'));
});

it('still stores, encrypted, a contact name for a customised form that asks for one, and the approval flow works', function () {
    Illuminate\Support\Facades\Mail::fake();
    $event = openEvent();
    $form = $event->enrollmentForm;
    $schema = $form->draft_schema;
    array_unshift($schema['sections'][1]['fields'], ['key' => 'contact_name', 'type' => 'text', 'label' => 'Contact person name', 'required' => true, 'maps_to' => 'contact.name']);
    $form->forceFill(['draft_schema' => $schema])->save();
    $form->publish();

    $event = $event->fresh();
    $enrollment = app(App\Services\EnrollmentWorkflow::class)->submit($event, enrollmentPayload($event, ['contact_name' => 'Jane Doe']));

    expect($enrollment->fresh()->contact_name)->toBe('Jane Doe')
        ->and(Illuminate\Support\Facades\DB::table('enrollments')->where('id', $enrollment->id)->value('contact_name'))->not->toContain('Jane');

    app(App\Services\EnrollmentWorkflow::class)->approve($enrollment, reviewer(), ['mode' => 'new']);
    Illuminate\Support\Facades\Mail::assertQueued(App\Mail\EnrollmentApprovedMail::class, fn ($m) => str_contains($m->render(), 'Beste Jane Doe,'));
});

it('encrypts legacy plaintext names with the backfill and removes them with the normal retention', function () {
    $event = openEvent(['date' => now(config('privacy.timezone'))->subDays(5)->toDateString()]);
    $enrollment = submitEnrollment($event);
    $enrollment->forceFill(['status' => 'approved'])->save();
    Illuminate\Support\Facades\DB::table('enrollments')->where('id', $enrollment->id)->update(['contact_name' => 'Legacy Name']);

    $run = (new App\Support\Privacy\EmailBackfill)->run();
    expect($run['enrollments']['remaining_plaintext'])->toBe(0)
        ->and(Illuminate\Support\Facades\DB::table('enrollments')->where('id', $enrollment->id)->value('contact_name'))->not->toContain('Legacy')
        ->and($enrollment->fresh()->contact_name)->toBe('Legacy Name');

    (new App\Support\Privacy\PrivacyRetention)->run();

    expect($enrollment->fresh()->contact_name)->toBeNull()->and($enrollment->fresh()->event_id)->toBe($event->id)->and($enrollment->fresh()->status)->toBe('approved');
});

it('keeps names out of the audit trail', function () {
    $guest = App\Models\BorrelEnrollment::create(['event_id' => openEvent()->id, 'name' => 'Audit Guest', 'email' => 'a@x.example']);
    $guest->update(['name' => 'Changed Name']);

    expect(json_encode(Spatie\Activitylog\Models\Activity::all()->map->properties))->not->toContain('Audit Guest')->not->toContain('Changed Name');
});
