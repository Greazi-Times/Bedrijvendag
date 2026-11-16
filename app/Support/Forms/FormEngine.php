<?php

namespace App\Support\Forms;

use App\Models\Education;
use App\Models\Sector;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Turns a stored schema plus raw input into validated answers. Rules are generated
 * from the schema on the server, so the browser can never loosen them. Hidden
 * (condition not met) fields are not validated and are dropped from the answers.
 */
class FormEngine
{
    public const FILE_MIMES = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];

    public const FILE_MAX_KB = 4096;

    /**
     * @param  array<string, mixed>  $schema
     * @param  array<string, mixed>  $input
     * @param  array<string, mixed>  $settings  edition settings such as max_lunch_participants
     * @return array<string, mixed> validated answers keyed by stable field key
     *
     * @throws ValidationException
     */
    public function validate(array $schema, array $input, array $settings = []): array
    {
        $rules = [];
        $attributes = [];
        $answers = [];

        foreach ($this->fields($schema) as $field) {
            $this->collect($field, $input, $input, '', $rules, $attributes, $answers, $settings);
        }

        Validator::make($input, $rules, [], $attributes)->validate();

        return $this->extract($schema, $input, $settings);
    }

    /** @return array<string, mixed> */
    private function extract(array $schema, array $input, array $settings): array
    {
        $out = [];

        foreach ($this->fields($schema) as $field) {
            $this->take($field, $input, $input, $out, $settings);
        }

        return $out;
    }

    private function take(array $field, array $scope, array $root, array &$out, array $settings): void
    {
        $type = FieldType::from($field['type']);

        if ($type->isDisplayOnly() || ! $this->visible($field, $scope, $root)) {
            return;
        }

        $key = $field['key'];
        $value = $scope[$key] ?? null;

        if ($type === FieldType::Repeater) {
            $rows = [];

            foreach (array_values((array) $value) as $row) {
                $clean = [];

                foreach ($field['children'] as $child) {
                    $this->take($child, (array) $row, $root, $clean, $settings);
                }
                $rows[] = $clean;
            }
            $out[$key] = $rows;

            return;
        }

        $out[$key] = match (true) {
            $type === FieldType::Checkbox, $type === FieldType::Terms => (bool) $value,
            $type === FieldType::Rating && is_numeric($value) => (int) $value,
            $type === FieldType::Number && $value !== null && $value !== '' => $value + 0,
            $value === '' => null,
            default => $value,
        };
    }

    private function collect(array $field, array $scope, array $root, string $prefix, array &$rules, array &$attributes, array &$answers, array $settings): void
    {
        $type = FieldType::from($field['type']);

        if ($type->isDisplayOnly() || ! $this->visible($field, $scope, $root)) {
            return;
        }

        $path = $prefix.$field['key'];
        $attributes[$path] = $type === FieldType::Terms ? __('enrollment.terms_attribute') : ($field['label'] ?? $field['key']);
        $required = ! empty($field['required']) || $type === FieldType::Terms;
        $base = $required ? ['required'] : ['nullable'];

        if ($type === FieldType::Repeater) {
            $max = $this->maxItems($field, $settings);
            $rules[$path] = array_merge($base, ['array', 'max:'.$max]);

            // A repeater tied to a count question must hold exactly that many entries.
            $countKey = $field['count_from'] ?? null;
            $count = $countKey ? ($scope[$countKey] ?? $root[$countKey] ?? null) : null;

            if ($countKey && is_numeric($count) && (int) $count >= 0) {
                $rules[$path][] = 'size:'.min((int) $count, $max);
            }

            foreach (array_values((array) ($scope[$field['key']] ?? [])) as $i => $row) {
                foreach ($field['children'] as $child) {
                    $this->collect($child, (array) $row, $root, "{$path}.{$i}.", $rules, $attributes, $answers, $settings);
                }
            }

            return;
        }

        $rules[$path] = array_merge($base, $this->typeRules($field, $type, $settings));

        if ($type === FieldType::Multiselect) {
            $rules[$path.'.*'] = ['string', Rule::in($this->allowedValues($field))];
        }
    }

    /** @return list<mixed> */
    private function typeRules(array $field, FieldType $type, array $settings = []): array
    {
        $min = $field['min'] ?? null;
        // The edition can cap a number question (e.g. the lunch limit); it can only lower, never raise, the form's own cap.
        $cap = isset($field['max_setting'], $settings[$field['max_setting']]) ? (int) $settings[$field['max_setting']] : null;
        $max = $cap !== null ? ($field['max'] ?? null ? min((int) $field['max'], $cap) : $cap) : ($field['max'] ?? null);
        $extra = fn (string $base): array => array_values(array_filter([
            $base,
            $min !== null && $min !== '' ? "min:{$min}" : null,
            $max !== null && $max !== '' ? "max:{$max}" : null,
        ]));

        return match ($type) {
            FieldType::Text => array_values(array_filter(['string', $min ? "min:{$min}" : null, 'max:'.($max ?: 255)])),
            FieldType::Textarea => ['string', 'max:'.($max ?? 5000)],
            FieldType::Email => ['email:rfc', 'max:255'],
            FieldType::Phone => ['string', 'max:32', 'regex:/^\+?[0-9 ()\-]{6,32}$/'],
            FieldType::Url => ['url:http,https', 'max:255'],
            FieldType::Number => $extra('numeric'),
            FieldType::Date => ['date'],
            // "na" is only accepted when the question explicitly allows "not applicable".
            FieldType::Rating => [Rule::in([...range(1, (int) ($field['max'] ?? 5)), ...(! empty($field['allow_na']) ? ['na'] : [])])],
            FieldType::Checkbox => ['boolean'],
            FieldType::Terms => ['accepted'],
            FieldType::Radio, FieldType::Select => ['string', Rule::in($this->allowedValues($field))],
            FieldType::Multiselect => ['array', 'max:50'],
            FieldType::File => [
                'file',
                // A company logo is copied to the public logo folder on approval, so it must be a real raster image.
                ($field['maps_to'] ?? null) === 'company.logo' ? 'mimes:jpg,jpeg,png,webp' : 'mimes:'.implode(',', self::FILE_MIMES),
                'max:'.min((int) ($max ?: self::FILE_MAX_KB), self::FILE_MAX_KB),
            ],
            default => [],
        };
    }

    /** @return list<string> */
    public function allowedValues(array $field): array
    {
        return match ($field['options_source'] ?? null) {
            'educations' => Education::query()->pluck('id')->map(fn ($id) => (string) $id)->all(),
            'sectors' => Sector::query()->pluck('id')->map(fn ($id) => (string) $id)->all(),
            default => array_map('strval', array_column($field['options'] ?? [], 'value')),
        };
    }

    private function maxItems(array $field, array $settings): int
    {
        $limit = (int) ($field['max_items'] ?? 10);
        $fromSetting = $field['max_items_setting'] ?? $field['max_setting'] ?? null;

        // The edition setting wins over the template's default, so admins can raise or lower it per edition.
        return max(1, $fromSetting && isset($settings[$fromSetting]) ? min(20, (int) $settings[$fromSetting]) : $limit);
    }

    /** Condition check; a child condition looks in its own repeater row first. */
    public function visible(array $field, array $scope, array $root): bool
    {
        $cond = $field['visible_if'] ?? null;

        if (! $cond) {
            return true;
        }

        $actual = array_key_exists($cond['field'], $scope) ? $scope[$cond['field']] : ($root[$cond['field']] ?? null);
        $actual = $actual instanceof UploadedFile ? 'file' : $actual;
        $wanted = $cond['value'] ?? null;
        $has = fn ($v): bool => is_array($actual) ? in_array((string) $v, array_map('strval', $actual), true) : (string) $actual === (string) $v;

        return match ($cond['operator']) {
            'eq' => $has($wanted),
            'neq' => ! $has($wanted),
            'in' => collect((array) $wanted)->contains(fn ($v) => $has($v)),
            'filled' => filled($actual),
            'empty' => blank($actual),
            default => true,
        };
    }

    /** @return list<array<string, mixed>> */
    public function fields(array $schema): array
    {
        return collect($schema['sections'] ?? [])->flatMap(fn ($section) => $section['fields'] ?? [])->all();
    }
}
