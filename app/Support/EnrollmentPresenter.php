<?php

namespace App\Support;

use App\Models\Company;
use App\Models\Education;
use App\Models\Enrollment;
use App\Models\Sector;
use App\Services\CompanyMatcher;

/** Read-only views of an enrollment for reviewers; nothing here writes. */
class EnrollmentPresenter
{
    /** @return list<array{label: string, system: ?string, type: string, value: mixed, file: ?string, depth: int}> */
    public static function answers(Enrollment $enrollment): array
    {
        return self::rows($enrollment->formVersion->schema ?? [], $enrollment->answers ?? [], $enrollment->files ?? []);
    }

    /**
     * Answers of one stored submission, labelled by the schema it was answered against.
     *
     * @param  array<string, mixed>  $schema
     * @param  array<string, mixed>  $answers
     * @param  array<string, mixed>  $files
     * @return list<array{label: string, system: ?string, type: string, value: mixed, file: ?string, depth: int}>
     */
    public static function rows(array $schema, array $answers, array $files): array
    {
        $rows = [];

        foreach ($schema['sections'] ?? [] as $section) {
            $rows[] = ['label' => $section['title'] ?? '', 'type' => 'section', 'system' => null, 'value' => null, 'file' => null, 'depth' => 0];

            foreach ($section['fields'] ?? [] as $field) {
                self::row($files, $field, $answers, $rows, 0);
            }
        }

        return $rows;
    }

    private static function row(array $files, array $field, array $scope, array &$rows, int $depth): void
    {
        $type = $field['type'];

        if (in_array($type, ['info', 'heading'], true) && ($field['maps_to'] ?? null) !== 'company.verification') {
            return;
        }

        $value = $scope[$field['key']] ?? null;
        $rows[] = [
            'label' => $field['label'] ?? $field['key'], 'system' => $field['maps_to'] ?? null, 'type' => $type, 'depth' => $depth,
            'file' => $type === 'file' && isset($files[$field['key']]) ? $field['key'] : null,
            'value' => self::format($field, $value, $files),
        ];

        if ($type === 'repeater') {
            foreach (array_values((array) $value) as $i => $entry) {
                $rows[] = ['label' => '#'.($i + 1), 'type' => 'entry', 'system' => null, 'value' => null, 'file' => null, 'depth' => $depth + 1];

                foreach ($field['children'] as $child) {
                    self::row($files, $child, (array) $entry, $rows, $depth + 2);
                }
            }
        }
    }

    private static function format(array $field, mixed $value, array $files): string
    {
        $type = $field['type'];

        if ($type === 'file') {
            return $files[$field['key']]['name'] ?? '—';
        }
        if (($field['maps_to'] ?? null) === 'company.verification') {
            return match ($value) {
                'confirmed' => 'Company confirmed the details are correct',
                'correction' => 'Company requested corrections (see profile review)',
                default => '—',
            };
        }
        if ($type === 'repeater') {
            return count((array) $value).' entries';
        }
        if (in_array($type, ['checkbox', 'terms'], true)) {
            return $value ? 'Yes' : 'No';
        }
        if (blank($value)) {
            return '—';
        }

        $labels = match ($field['options_source'] ?? null) {
            'educations' => Education::query()->pluck('name', 'id')->all(),
            'sectors' => Sector::query()->pluck('name', 'id')->all(),
            default => collect($field['options'] ?? [])->pluck('label', 'value')->all(),
        };

        return collect((array) $value)->map(fn ($v) => $labels[$v] ?? (string) $v)->implode(', ');
    }

    /** @return array{exact: \Illuminate\Support\Collection<int, Company>, similar: \Illuminate\Support\Collection<int, Company>} */
    public static function matches(Enrollment $enrollment): array
    {
        return app(CompanyMatcher::class)->matches($enrollment->company_name);
    }

    /**
     * Submitted public profile vs. the stored company. Empty when there is no company to compare to.
     *
     * @return list<array{field: string, current: string, submitted: string, differs: bool}>
     */
    public static function comparison(Enrollment $enrollment, ?Company $company): array
    {
        if (! $company) {
            return [];
        }

        $company->loadMissing(['educations', 'sectors']);
        $names = fn (string $model, mixed $ids) => $ids ? $model::query()->whereIn('id', (array) $ids)->orderBy('name')->pluck('name')->implode(', ') : '';
        $plain = fn (?string $html) => trim(preg_replace('/\s+/', ' ', strip_tags((string) $html)) ?? '');

        $rows = [
            ['Company name', $company->name, $enrollment->company_name],
            ['Website', (string) $company->website_url, (string) $enrollment->mapped('company.website')],
            ['Description', $plain($company->description_nl), $plain($enrollment->mapped('company.description'))],
            ['Educations', $company->educations->pluck('name')->sort()->implode(', '), $names(Education::class, $enrollment->mapped('company.educations'))],
            ['Sectors', $company->sectors->pluck('name')->sort()->implode(', '), $names(Sector::class, $enrollment->mapped('company.sectors'))],
            ['Logo', $company->logo_path ? 'On file' : 'None', $enrollment->mappedFile('company.logo') ? 'New upload' : '(none submitted)'],
        ];

        return array_map(fn (array $r): array => [
            'field' => $r[0], 'current' => $r[1] ?: '—', 'submitted' => $r[2] ?: '—',
            // an empty submission never proposes clearing a value
            'differs' => $r[0] === 'Logo' ? $r[2] === 'New upload' : ($r[2] !== '' && $r[1] !== $r[2]),
        ], $rows);
    }
}
