<?php

namespace App\Filament\Resources\Forms\Schemas;

use App\Models\Event;
use App\Models\Form;
use App\Support\Forms\DefaultForms;
use App\Support\Forms\FieldType;
use App\Support\Forms\FormSchema;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FormBuilderSchema
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(fn (?Form $record): string => $record?->isGlobal() ? 'Global default form' : 'Form')
                ->description(fn (?Form $record): ?string => $record?->isGlobal() ? 'Applies to every edition that has not been given its own form. Publishing creates a new version that future submissions use; earlier submissions keep the version they were answered against.' : null)
                ->schema([
                    TextInput::make('name')->required()->maxLength(255),
                    Select::make('kind')
                        ->options(['enrollment' => 'Initial enrollment', 'info' => 'Information after approval'])
                        ->required()
                        ->disabled(fn (?object $record): bool => $record !== null)
                        ->dehydrated(),
                    Select::make('event_id')
                        ->label('Edition')
                        ->placeholder('None (reusable template)')
                        ->options(fn (): array => Event::query()->orderByDesc('date')->pluck('name', 'id')->all())
                        ->searchable()
                        ->hidden(fn (?Form $record): bool => (bool) $record?->isGlobal()),
                    Toggle::make('is_template')->label('Offer as template')->hidden(fn (?Form $record): bool => (bool) $record?->isGlobal()),
                ])->columns(2),

            Repeater::make('draft_schema.sections')
                ->label('Sections')
                ->addActionLabel('Add section')
                ->reorderableWithDragAndDrop()
                ->collapsible()
                ->cloneable(false)
                ->helperText('Sections and questions marked "system" feed the enrollment process and cannot be deleted. You can relabel and reorder them.')
                ->deleteAction(fn (Action $action) => $action->hidden(fn (array $arguments, Repeater $component): bool => self::containsMapped($component->getRawItemState($arguments['item'])['fields'] ?? [])))
                ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                ->columnSpanFull()
                ->schema([
                    // Internal identifier: generated once, kept in the saved state, never shown or editable.
                    Hidden::make('key')->default(fn (): string => FormSchema::newKey('s'))->dehydrated(),
                    TextInput::make('title')->required(),
                    Textarea::make('description')->rows(2),
                    self::fields('fields', nested: false),
                ]),
        ]);
    }

    private static function fields(string $name, bool $nested): Repeater
    {
        return Repeater::make($name)
            ->label($nested ? 'Questions in each entry' : 'Questions')
            ->addActionLabel('Add question')
            ->reorderableWithDragAndDrop()
            ->collapsible()
            ->collapsed()
            ->cloneable(false)
            ->deleteAction(fn (Action $action) => $action->hidden(fn (array $arguments, Repeater $component): bool => self::containsMapped([$component->getRawItemState($arguments['item'])])))
            ->itemLabel(fn (array $state): ?string => (filled($state['maps_to'] ?? null) ? '🔒 ' : '').(($state['label'] ?? null) ?: 'New question'))
            ->schema([
                Hidden::make('key')->default(fn (): string => FormSchema::newKey())->dehydrated(),
                Select::make('type')->options(FieldType::labels())->required()->live()
                    ->afterStateUpdated(fn ($state, $set) => $state === 'profile_verification' ? $set('maps_to', 'company.verification') : null)
                    ->disabled(fn ($get): bool => filled($get('maps_to')))->dehydrated(),
                TextInput::make('label')->required(fn ($get): bool => $get('type') !== 'terms'),
                Hidden::make('maps_to')->dehydrated(),
                Placeholder::make('system_note')->label('System question')->content('This question feeds the enrollment process. You can relabel it but not remove it.')
                    ->visible(fn ($get): bool => filled($get('maps_to'))),
                Textarea::make('description')->label('Description / help text')->rows(2),
                TextInput::make('placeholder'),
                Toggle::make('required'),
                TextInput::make('default')->label('Default value'),
                TextInput::make('min')->numeric()->label('Min (length or value)'),
                TextInput::make('max')->numeric()->label('Max (length, value or KB)'),
                Select::make('options_source')->label('Options from')->placeholder('Custom list')
                    ->options(['educations' => 'Educations', 'sectors' => 'Sectors'])
                    ->visible(fn ($get): bool => in_array($get('type'), ['select', 'multiselect', 'radio'], true)),
                Repeater::make('options')->label('Options')->addActionLabel('Add option')->reorderableWithDragAndDrop()
                    ->visible(fn ($get): bool => in_array($get('type'), ['select', 'multiselect', 'radio'], true) && blank($get('options_source')))
                    ->schema([
                        TextInput::make('value')->required()->alphaDash()->helperText('Stored value; keep stable.'),
                        TextInput::make('label')->required(),
                    ])->columns(2)->columnSpanFull(),
                TextInput::make('max_items')->numeric()->minValue(1)->label('Max entries')
                    ->visible(fn ($get): bool => $get('type') === 'repeater'),
                Select::make('max_setting')->label('Cap from edition setting')->placeholder('No edition cap')
                    ->options(['max_lunch_participants' => 'Lunch participants limit (per edition)'])
                    ->visible(fn ($get): bool => in_array($get('type'), ['number', 'repeater'], true)),
                Select::make('count_from')->label('Number of entries follows question')->placeholder('Free number of entries')
                    ->options(fn ($livewire): array => self::siblingKeys($livewire))
                    ->visible(fn ($get): bool => $get('type') === 'repeater'),
                Select::make('visible_if.field')->label('Show only when question')->placeholder('Always show')
                    ->options(fn ($livewire): array => self::siblingKeys($livewire)),
                Select::make('visible_if.operator')->label('is')
                    ->options(['eq' => 'equal to', 'neq' => 'not equal to', 'filled' => 'answered', 'empty' => 'empty'])
                    ->visible(fn ($get): bool => filled($get('visible_if.field'))),
                TextInput::make('visible_if.value')->label('Value')
                    ->visible(fn ($get): bool => in_array($get('visible_if.operator'), ['eq', 'neq'], true)),
                ...($nested ? [] : [self::fields('children', nested: true)->visible(fn ($get): bool => $get('type') === 'repeater')->columnSpanFull()]),
            ])->columns(2);
    }

    /** True when any of these fields (or their sub-questions) feeds the enrollment process. */
    private static function containsMapped(array $fields): bool
    {
        foreach ($fields as $field) {
            if (filled($field['maps_to'] ?? null) || self::containsMapped($field['children'] ?? [])) {
                return true;
            }
        }

        return false;
    }

    /** @return array<string, string> keys of all questions in the draft, for the condition picker. */
    private static function siblingKeys($livewire): array
    {
        $sections = data_get($livewire->data ?? [], 'draft_schema.sections', []);
        $out = [];

        foreach ($sections as $section) {
            foreach ($section['fields'] ?? [] as $field) {
                $out[$field['key'] ?? ''] = ($field['label'] ?? '') ?: 'Untitled question';

                foreach ($field['children'] ?? [] as $child) {
                    $out[$child['key'] ?? ''] = '↳ '.(($child['label'] ?? '') ?: 'Untitled question');
                }
            }
        }

        return array_filter($out, fn ($label, $key): bool => $key !== '', ARRAY_FILTER_USE_BOTH);
    }

    /** Filament keys repeater items by uuid; stored schemas must be plain lists. */
    public static function normalize(array $schema): array
    {
        $listify = function (array $items) use (&$listify): array {
            return array_values(array_map(function ($item) use (&$listify) {
                if (! is_array($item)) {
                    return $item;
                }
                foreach (['fields', 'children', 'options'] as $nested) {
                    if (isset($item[$nested]) && is_array($item[$nested])) {
                        $item[$nested] = $listify($item[$nested]);
                    }
                }
                if (isset($item['visible_if']) && blank($item['visible_if']['field'] ?? null)) {
                    unset($item['visible_if']);
                }
                // The editor sends every untouched input as null/''; drop them so an unedited form stays byte-identical.
                $item = array_filter($item, fn ($v): bool => $v !== null && $v !== '' && $v !== []);

                return $item;
            }, $items));
        };

        return self::ensureKeys(['sections' => $listify($schema['sections'] ?? [])]);
    }

    /**
     * Every section and question keeps the key it has. Only a missing, malformed or repeated key (a new question, an
     * imported or copied one) gets a fresh generated key, so existing answers always stay attached to their question.
     *
     * @param  array<string, mixed>  $schema
     * @return array<string, mixed>
     */
    public static function ensureKeys(array $schema): array
    {
        $seen = [];
        $assign = function (array $item, string $prefix) use (&$seen): array {
            $key = $item['key'] ?? null;

            while (! is_string($key) || ! preg_match('/^[a-z0-9_]{2,64}$/', $key) || isset($seen[$key])) {
                $key = FormSchema::newKey($prefix);
            }

            $seen[$key] = true;
            $item['key'] = $key;

            return $item;
        };

        foreach ($schema['sections'] ?? [] as $s => $section) {
            $schema['sections'][$s] = $assign($section, 's');

            foreach ($section['fields'] ?? [] as $f => $field) {
                $schema['sections'][$s]['fields'][$f] = $assign($field, 'q');

                foreach ($field['children'] ?? [] as $c => $child) {
                    $schema['sections'][$s]['fields'][$f]['children'][$c] = $assign($child, 'q');
                }
            }
        }

        return $schema;
    }

    public static function defaultFor(string $kind): array
    {
        return $kind === 'info' ? DefaultForms::info() : DefaultForms::enrollment();
    }
}
