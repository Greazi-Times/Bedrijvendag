<?php

namespace App\Support\Privacy;

/**
 * Removes dietary and allergy answers from stored form answers, wherever the form put them.
 * A field counts as sensitive when its schema entry carries `sensitive: true` or its stable key is listed in
 * config('privacy.sensitive_field_keys'); labels are never compared, so renaming a question changes nothing.
 * A repeater entry that contains a sensitive answer is removed as a whole, so a participant is not kept next to
 * a sensitive answer that was only half removed.
 */
class SensitiveAnswers
{
    /** @param array<string, mixed> $schema @return list<string> */
    public static function keys(array $schema): array
    {
        $keys = (array) config('privacy.sensitive_field_keys', []);

        $walk = function (array $fields) use (&$walk, &$keys): void {
            foreach ($fields as $field) {
                if (! empty($field['sensitive']) && isset($field['key'])) {
                    $keys[] = $field['key'];
                }

                $walk($field['children'] ?? []);
            }
        };

        foreach ($schema['sections'] ?? [] as $section) {
            $walk($section['fields'] ?? []);
        }

        return array_values(array_unique($keys));
    }

    /**
     * @param  array<string, mixed>  $schema
     * @param  array<string, mixed>  $answers
     * @return array<string, mixed>
     */
    public static function strip(array $schema, array $answers): array
    {
        return self::clean($answers, self::keys($schema));
    }

    public static function contains(array $schema, array $answers): bool
    {
        return self::strip($schema, $answers) !== $answers;
    }

    /** @param array<string, mixed> $answers @param list<string> $keys */
    private static function clean(array $answers, array $keys): array
    {
        foreach ($answers as $key => $value) {
            if (in_array($key, $keys, true)) {
                unset($answers[$key]);

                continue;
            }

            if (is_array($value) && array_is_list($value) && $value !== [] && is_array($value[0] ?? null)) {
                $hasSensitive = collect($value)->contains(fn ($row) => is_array($row) && array_intersect(array_keys($row), $keys) !== []);
                $answers[$key] = $hasSensitive ? [] : array_map(fn ($row) => is_array($row) ? self::clean($row, $keys) : $row, $value);
            }
        }

        return $answers;
    }
}
