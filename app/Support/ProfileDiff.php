<?php

namespace App\Support;

use Illuminate\Support\HtmlString;

class ProfileDiff
{
    public static function render(string $field, array $change): array
    {
        $before = (string) $change['before'];
        $after = (string) $change['after'];

        if (in_array($field, ['educations', 'sectors'], true)) {
            $old = $change['before_items'] ?? ($before === '' ? [] : explode(', ', $before));
            $new = $change['after_items'] ?? ($after === '' ? [] : explode(', ', $after));
            $render = fn (array $items, array $other, string $tag): string => implode('<br>', array_map(
                fn (string $item): string => in_array($item, $other, true) ? e($item) : self::mark($item, $tag), $items,
            ));

            return ['before' => new HtmlString($render($old, $new, 'del')), 'after' => new HtmlString($render($new, $old, 'ins'))];
        }

        if ($field === 'description') {
            $before = self::plainText($before);
            $after = self::plainText($after);
        }

        $tokens = fn (string $text): array => preg_split('/(\s+|[\p{L}\p{N}_]+|[^\p{L}\p{N}_\s])/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) ?: [];
        $old = $tokens($before);
        $new = $tokens($after);
        // Keep only one row of scores; compact direction strings support backtracking.
        $previous = array_fill(0, count($new) + 1, 0);
        $directions = [];
        foreach ($old as $i => $token) {
            $row = [0];
            $directions[$i] = '';
            foreach ($new as $j => $candidate) {
                if ($token === $candidate) {
                    $row[] = $previous[$j] + 1;
                    $directions[$i] .= '=';
                } elseif ($previous[$j + 1] >= $row[$j]) {
                    $row[] = $previous[$j + 1];
                    $directions[$i] .= '-';
                } else {
                    $row[] = $row[$j];
                    $directions[$i] .= '+';
                }
            }
            $previous = $row;
        }

        $operations = [];
        $i = count($old);
        $j = count($new);
        while ($i > 0 || $j > 0) {
            $direction = $i === 0 ? '+' : ($j === 0 ? '-' : $directions[$i - 1][$j - 1]);
            if ($direction === '=') {
                $operations[] = ['=', $old[--$i]];
                $j--;
            } elseif ($direction === '-') {
                $operations[] = ['-', $old[--$i]];
            } else {
                $operations[] = ['+', $new[--$j]];
            }
        }

        $groups = [];
        foreach (array_reverse($operations) as [$operation, $text]) {
            $last = count($groups) - 1;
            if ($last >= 0 && $groups[$last][0] === $operation) {
                $groups[$last][1] .= $text;
            } else {
                $groups[] = [$operation, $text];
            }
        }
        $result = ['before' => '', 'after' => ''];
        foreach ($groups as [$operation, $text]) {
            if ($operation !== '+') {
                $result['before'] .= $operation === '-' ? self::mark($text, 'del') : e($text);
            }
            if ($operation !== '-') {
                $result['after'] .= $operation === '+' ? self::mark($text, 'ins') : e($text);
            }
        }

        return array_map(fn (string $html): HtmlString => new HtmlString('<span class="profile-diff-text">'.$html.'</span>'), $result);
    }

    private static function mark(string $text, string $tag): string
    {
        $label = $tag === 'del' ? 'Removed' : 'Added';

        return '<'.$tag.' class="profile-diff-'.$tag.'" title="'.$label.'">'.e($text).'</'.$tag.'>';
    }

    private static function plainText(string $html): string
    {
        $html = preg_replace('/<(script|style)\b[^>]*>.*?<\/\1>/is', '', $html) ?? $html;
        $html = preg_replace('/<br\s*\/?\s*>|<\/(p|div|h[1-6]|li|blockquote)>/i', "\n", $html) ?? $html;

        return trim(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }
}
