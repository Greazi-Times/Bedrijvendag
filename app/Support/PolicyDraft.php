<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Str;

/**
 * Drafts live in policies/*.md. An administrator can load one into the editor (nothing is saved), and a policy that
 * still contains an open-item marker cannot be saved, so an unfinished draft is never published by accident.
 */
class PolicyDraft
{
    public const MARKER = '/\[(CONFIRM|TE BEVESTIGEN|BEVESTIGEN)|DRAFT FOR (ORGANISATIONAL|REVIEW)|CONCEPT VOOR BEOORDELING|Schrijf hier je/i';

    public static function html(string $file): ?string
    {
        $path = base_path("policies/{$file}.md");

        return is_file($path) ? Str::markdown((string) file_get_contents($path), ['html_input' => 'strip']) : null;
    }

    /** The editor's state is a rich-text document (array) while it is edited and HTML once stored; check the words in both. */
    public static function plainText(mixed $value): string
    {
        if (is_string($value)) {
            return strip_tags($value);
        }

        $text = [];
        array_walk_recursive($value, function ($item, $key) use (&$text): void {
            if ($key === 'text' && is_string($item)) {
                $text[] = $item;
            }
        });

        return implode(' ', $text);
    }

    public static function rule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (preg_match(self::MARKER, self::plainText($value))) {
                $fail('This text still contains open items ([CONFIRM …] or a draft notice). Resolve them with the responsible organisation before saving.');
            }
        };
    }
}
