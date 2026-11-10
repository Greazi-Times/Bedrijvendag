<?php

namespace App\Services;

use App\Models\Company;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Central company-name normalisation and matching. A match is only ever a hint for
 * a human reviewer: it never proves identity and nothing may merge on it alone.
 */
class CompanyMatcher
{
    /** Legal forms recognised at the END of a name, mapped to one canonical spelling ("incorporated" == "inc"). */
    private const LEGAL_SUFFIXES = [
        'bv' => 'bv', 'nv' => 'nv', 'vof' => 'vof', 'cv' => 'cv', 'bvba' => 'bvba', 'gmbh' => 'gmbh', 'ag' => 'ag', 'sarl' => 'sarl', 'llc' => 'llc',
        'inc' => 'inc', 'incorporated' => 'inc', 'corp' => 'corp', 'corporation' => 'corp', 'ltd' => 'ltd', 'limited' => 'ltd',
    ];

    /** Lowercase, accent- and punctuation-free: "BOM Engineering B.V." == "bomengineeringbv". */
    public function normalize(string $name): string
    {
        return preg_replace('/[^a-z0-9]+/', '', Str::lower(Str::ascii($name))) ?? '';
    }

    /**
     * Splits a name into its core and canonical legal form: "C.S.I Incorporated" => ['csi', 'inc'],
     * "Acme" => ['acme', ''].
     *
     * @return array{0: string, 1: string}
     */
    public function parts(string $name): array
    {
        $ascii = Str::lower(Str::ascii($name));
        // Dotted initials ("B.V.", "C.S.I", "A.B.C.") collapse into one word before splitting.
        $ascii = preg_replace_callback('/\b[a-z](?:\.[a-z])+\.?/', fn (array $m): string => preg_replace('/[^a-z]/', '', $m[0]).' ', $ascii) ?? $ascii;
        $words = preg_split('/[^a-z0-9]+/', $ascii, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $suffix = '';

        if (count($words) > 1 && isset(self::LEGAL_SUFFIXES[end($words)])) {
            $suffix = self::LEGAL_SUFFIXES[array_pop($words)];
        }

        return [implode('', $words), $suffix];
    }

    /** Identity key: formatting differences vanish, but a different or missing legal form stays different. */
    public function key(string $name): string
    {
        return implode('#', $this->parts($name));
    }

    /**
     * @return array{exact: Collection<int, Company>, similar: Collection<int, Company>}
     */
    public function matches(string $name, ?Collection $companies = null): array
    {
        [$base, $suffix] = $this->parts($name);
        $none = ['exact' => collect(), 'similar' => collect()];

        if (strlen($base) < 3) {
            return $none;
        }

        $companies ??= Company::query()->orderBy('name')->get(['id', 'name', 'website_url']);
        $parts = $companies->mapWithKeys(fn (Company $c): array => [$c->id => $this->parts($c->name)]);

        $exact = $companies->filter(fn (Company $c): bool => $parts[$c->id] === [$base, $suffix]);
        $similar = $companies->diff($exact)->filter(function (Company $c) use ($base, $parts): bool {
            $other = $parts[$c->id][0];
            $shortest = min(strlen($base), strlen($other));

            // Possible match only: same core with a different or missing legal form ("Acme" / "Acme BV" / "Acme Inc"),
            // a small edit distance, or one core wholly inside the other (min 5 chars). A human decides.
            return $shortest >= 3
                && ($base === $other
                    || levenshtein($base, $other) <= intdiv($shortest, 6)
                    || ($shortest >= 5 && (str_contains($other, $base) || str_contains($base, $other))));
        });

        return ['exact' => $exact->values(), 'similar' => $similar->values()];
    }
}
