<?php

namespace App\Support\Forms;

use Illuminate\Support\Str;

/** Structural validation of a form schema before it is published. */
class FormSchema
{
    public const OPERATORS = ['eq', 'neq', 'in', 'filled', 'empty'];

    /**
     * @param  array<string, mixed>  $schema
     * @return list<string> human-readable problems; empty when publishable
     */
    public static function problems(array $schema, string $kind): array
    {
        $errors = [];
        $seen = [];
        $maps = [];
        $verifications = 0;
        $sections = $schema['sections'] ?? null;

        if (! is_array($sections) || $sections === []) {
            return ['The form needs at least one section.'];
        }

        foreach ($sections as $s => $section) {
            $sectionKey = $section['key'] ?? null;

            if (! is_string($sectionKey) || ! preg_match('/^[a-z0-9_]{2,64}$/', $sectionKey) || isset($seen[$sectionKey])) {
                $errors[] = 'Section '.($s + 1).' has a missing or duplicate internal identifier.';
            }
            $seen[$sectionKey] = true;

            foreach ($section['fields'] ?? [] as $field) {
                self::checkField($field, $seen, $maps, $errors, $verifications, parent: null);
            }
        }

        foreach (SystemFields::REQUIRED[$kind] ?? [] as $required) {
            if (! isset($maps[$required])) {
                $errors[] = "The system field \"{$required}\" cannot be removed.";
            }
        }

        if ($alternatives = SystemFields::REQUIRED_ONE_OF[$kind] ?? null) {
            $complete = collect($alternatives)->contains(fn (array $set): bool => collect($set)->every(fn (string $map): bool => isset($maps[$map])));

            if (! $complete) {
                $errors[] = 'The invoice address questions cannot be removed ('.implode(' or ', array_map(fn (array $set): string => implode(' + ', $set), $alternatives)).').';
            }
        }

        if ($verifications > 1) {
            $errors[] = 'A form can contain only one company profile verification component.';
        }

        if ($kind !== SystemFields::KIND_INFO && $verifications > 0) {
            $errors[] = 'The company profile verification component is only available in the information form.';
        }

        foreach (SystemFields::ALWAYS_REQUIRED as $mandatory) {
            if (isset($maps[$mandatory]) && empty($maps[$mandatory]['required']) && ($maps[$mandatory]['type'] ?? '') !== 'terms') {
                $errors[] = "The system field \"{$mandatory}\" must stay required.";
            }
        }

        return $errors;
    }

    /**
     * @param  array<string, mixed>  $field
     * @param  array<string, true>  $seen
     * @param  array<string, array<string, mixed>>  $maps
     * @param  list<string>  $errors
     */
    private static function checkField(array $field, array &$seen, array &$maps, array &$errors, int &$verifications, ?string $parent): void
    {
        $key = $field['key'] ?? null;
        $label = (string) ($field['label'] ?? $key ?? '?');

        if (! is_string($key) || ! preg_match('/^[a-z0-9_]{2,64}$/', $key)) {
            $errors[] = "Field \"{$label}\" has an invalid internal identifier.";

            return;
        }

        if (isset($seen[$key])) {
            $errors[] = "Field \"{$label}\" has an internal identifier that is used twice.";
        }
        $seen[$key] = true;

        $type = FieldType::tryFrom((string) ($field['type'] ?? ''));

        if (! $type) {
            $errors[] = "Field \"{$label}\" has an unknown type.";

            return;
        }

        if ($type === FieldType::ProfileVerification || ($field['maps_to'] ?? null) === 'company.verification') {
            $verifications++;
        }

        if ($type === FieldType::ProfileVerification && ($field['maps_to'] ?? null) !== 'company.verification') {
            $errors[] = "The company profile verification component \"{$label}\" must stay a system component.";
        }

        if (blank($field['label'] ?? null) && $type !== FieldType::Terms) {
            $errors[] = "Field \"{$key}\" needs a label.";
        }

        if ($type->hasOptions() && blank($field['options_source'] ?? null)) {
            $values = array_column($field['options'] ?? [], 'value');

            if ($values === [] || count($values) !== count(array_unique($values)) || in_array('', $values, true)) {
                $errors[] = "Field \"{$label}\" needs unique, non-empty options.";
            }
        }

        if ($type === FieldType::Repeater && empty($field['children'])) {
            $errors[] = "Repeater \"{$label}\" needs at least one question.";
        }

        if ($type !== FieldType::Repeater && ! empty($field['children'])) {
            $errors[] = "Only repeaters can contain questions (\"{$label}\").";
        }

        if (($map = $field['maps_to'] ?? null) !== null) {
            if (! in_array($map, SystemFields::allowedMaps(), true)) {
                $errors[] = "Field \"{$label}\" maps to an unknown system property.";
            } elseif (isset($maps[$map])) {
                $errors[] = "System property \"{$map}\" is mapped twice.";
            }
            $maps[$map] = $field;

            if (! in_array($field['type'] ?? '', SystemFields::TYPES[$map] ?? [], true)) {
                $errors[] = "The system field \"{$map}\" must be a ".implode(' or ', SystemFields::TYPES[$map] ?? ['?']).' question.';
            }

            if (isset(SystemFields::OPTION_SOURCES[$map]) && ($field['options_source'] ?? null) !== SystemFields::OPTION_SOURCES[$map]) {
                $errors[] = "The system field \"{$map}\" must keep its options from the ".SystemFields::OPTION_SOURCES[$map].' list.';
            }

            if (in_array($map, SystemFields::ALWAYS_REQUIRED, true) && ! empty($field['visible_if'])) {
                $errors[] = "The system field \"{$map}\" cannot be conditional; it is always asked.";
            }
        }

        if ($cond = $field['visible_if'] ?? null) {
            if (! in_array($cond['operator'] ?? null, self::OPERATORS, true) || blank($cond['field'] ?? null)) {
                $errors[] = "The condition on \"{$label}\" is incomplete.";
            } elseif (! isset($seen[$cond['field']]) || $cond['field'] === $key) {
                $errors[] = "The condition on \"{$label}\" must refer to an earlier question.";
            }
        }

        foreach ($field['children'] ?? [] as $child) {
            self::checkField($child, $seen, $maps, $errors, $verifications, $key);
        }
    }

    /** Field key currently mapped to a system property, e.g. "company.website" => "company_website". */
    public static function mappedKey(array $schema, string $map): ?string
    {
        foreach ($schema['sections'] ?? [] as $section) {
            foreach ($section['fields'] ?? [] as $field) {
                if (($field['maps_to'] ?? null) === $map) {
                    return $field['key'];
                }
            }
        }

        return null;
    }

    /** Stable machine key for a new field; never derived again after creation. */
    public static function newKey(string $prefix = 'q'): string
    {
        return $prefix.'_'.Str::lower(Str::random(8));
    }
}
