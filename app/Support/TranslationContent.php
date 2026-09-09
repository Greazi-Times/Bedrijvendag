<?php

namespace App\Support;

class TranslationContent
{
    public static function stringify(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_string($value)) {
            return trim($value);
        }

        if (is_array($value)) {
            foreach (['html', 'content', 'value'] as $key) {
                if (isset($value[$key]) && is_string($value[$key])) {
                    return trim($value[$key]);
                }
            }

            return collect($value)
                ->filter(fn (mixed $item): bool => is_string($item) && trim($item) !== '')
                ->map(fn (string $item): string => trim($item))
                ->implode("\n\n");
        }

        return trim((string) $value);
    }

    public static function hash(mixed $value): string
    {
        return hash('sha256', self::stringify($value));
    }

    public static function containsHtml(string $value): bool
    {
        return $value !== strip_tags($value);
    }
}
