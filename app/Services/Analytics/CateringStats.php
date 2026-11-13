<?php

namespace App\Services\Analytics;

use App\Models\Enrollment;
use App\Support\Forms\FormSchema;

/**
 * Lunch, dietary, closing-drinks and promotional-material figures from the LATEST second-form submission of each
 * approved enrollment (earlier revisions are never summed). Answers are encrypted, so this is computed in PHP, only
 * for the requested edition, and only totals leave this class; no answer is tied to a person.
 *
 * Retention: dietary answers are removed shortly after an edition and the rest of the second form with the personal
 * data of the enrollment. For such editions there is no submission left and the figures are reported as unavailable
 * (`available = false`), never as zero.
 *
 * Question keys come from the `maps_to` mapping of the submission's own form version; keys of unmapped child
 * questions (diet, drinks count) fall back to the stable keys of the default form.
 */
class CateringStats
{
    public const DIETS = ['vegetarian' => 'Vegetarisch', 'vegan' => 'Veganistisch', 'halal' => 'Halal', 'gluten_free' => 'Glutenvrij', 'lactose_free' => 'Lactosevrij', 'other' => 'Overig'];

    public function __construct(private Analytics $analytics) {}

    /**
     * @return array{available: bool, approved: int, responded: int, missing: int, lunch: array<string, mixed>, drinks: array<string, mixed>, promo: array<string, mixed>}
     */
    public function forEvent(int $eventId): array
    {
        return $this->analytics->remember("catering:{$eventId}", function () use ($eventId): array {
            $enrollments = Enrollment::query()->where('event_id', $eventId)->where('status', Enrollment::STATUS_APPROVED)
                ->with(['latestInfoSubmission.formVersion:id,schema', 'company:id,name'])->get(['id', 'company_id', 'info_status']);

            $lunch = ['participants' => 0, 'companies' => 0, 'missing' => 0, 'diet' => array_fill_keys(array_keys(self::DIETS), 0), 'diet_known' => 0, 'none' => 0, 'over_time' => []];
            $drinks = ['yes' => 0, 'no' => 0, 'unsure' => 0, 'missing' => 0, 'people' => 0, 'people_missing' => 0];
            $promo = ['small' => 0, 'medium' => 0, 'large' => 0, 'multiple' => 0, 'screens' => 0, 'prototypes' => 0, 'missing' => 0, 'special' => []];
            $responded = 0;

            foreach ($enrollments as $enrollment) {
                $submission = $enrollment->latestInfoSubmission;

                if (! $submission || $submission->answers === null) {
                    $lunch['missing']++;
                    $drinks['missing']++;
                    $promo['missing']++;

                    continue;
                }

                $responded++;
                $schema = $submission->formVersion?->schema ?? [];
                $answers = $submission->answers;
                $this->lunch($schema, $answers, $lunch, $submission->submitted_at?->toDateString());
                $this->drinks($schema, $answers, $drinks);
                $this->promo($schema, $answers, $promo, $enrollment->company_id, (string) $enrollment->company?->name);
            }

            return [
                'available' => $responded > 0,
                'approved' => $enrollments->count(),
                'responded' => $responded,
                'missing' => $enrollments->count() - $responded,
                'lunch' => $lunch,
                'drinks' => $drinks,
                'promo' => $promo,
            ];
        });
    }

    /**
     * Compact per-edition totals for the historical charts; editions without a stored second form are skipped.
     *
     * @return array<int, array{lunch: ?int, drinks: ?int}>
     */
    public function perEdition(): array
    {
        return $this->analytics->remember('catering-per-edition', function (): array {
            $ids = \Illuminate\Support\Facades\DB::table('enrollment_info_submissions')->join('enrollments', 'enrollments.id', '=', 'enrollment_info_submissions.enrollment_id')
                ->distinct()->pluck('enrollments.event_id');
            $out = [];

            foreach ($ids as $id) {
                $stats = $this->forEvent((int) $id);
                $out[(int) $id] = ['lunch' => $stats['lunch']['participants'], 'drinks' => $stats['drinks']['people']];
            }

            return $out;
        });
    }

    /** @param array<string, mixed> $schema @param array<string, mixed> $answers @param array<string, mixed> $lunch */
    private function lunch(array $schema, array $answers, array &$lunch, ?string $submittedOn): void
    {
        $repeaterKey = FormSchema::mappedKey($schema, 'lunch.participants') ?? 'lunch_participants';
        $countKey = FormSchema::mappedKey($schema, 'lunch.count') ?? 'lunch_count';
        $entries = is_array($answers[$repeaterKey] ?? null) ? $answers[$repeaterKey] : [];
        $declared = $answers[$countKey] ?? null;
        $count = is_numeric($declared) ? (int) $declared : count($entries);

        $lunch['participants'] += $count;
        $lunch['companies'] += $count > 0 ? 1 : 0;

        if ($submittedOn && $count > 0) {
            $lunch['over_time'][$submittedOn] = ($lunch['over_time'][$submittedOn] ?? 0) + $count;
        }

        // An entry without a diet answer had it removed by the retention policy; it is neither "none" nor a restriction.
        foreach ($entries as $entry) {
            $diets = is_array($entry['lunch_diet'] ?? null) ? $entry['lunch_diet'] : (isset($entry['lunch_diet']) ? [$entry['lunch_diet']] : null);

            if ($diets === null) {
                continue;
            }

            $lunch['diet_known']++;
            $restrictions = array_values(array_intersect($diets, array_keys(self::DIETS)));
            $restrictions === [] ? $lunch['none']++ : array_map(function (string $diet) use (&$lunch): void {
                $lunch['diet'][$diet]++;
            }, $restrictions);
        }
    }

    /** @param array<string, mixed> $schema @param array<string, mixed> $answers @param array<string, int> $drinks */
    private function drinks(array $schema, array $answers, array &$drinks): void
    {
        $choice = $answers[FormSchema::mappedKey($schema, 'drinks.attending') ?? 'drinks_attending'] ?? null;

        if (! in_array($choice, ['yes', 'no', 'unsure'], true)) {
            $drinks['missing']++;

            return;
        }

        $drinks[$choice]++;

        if ($choice === 'yes') {
            $people = $answers['drinks_count'] ?? null;
            is_numeric($people) ? $drinks['people'] += (int) $people : $drinks['people_missing']++;
        }
    }

    /**
     * Size and special equipment follow the option VALUES (small_*, medium_*, large_*, *screen*, *prototype*), never
     * the translated labels. Values that fit no pattern count as neither.
     *
     * @param  array<string, mixed>  $schema
     * @param  array<string, mixed>  $answers
     * @param  array<string, mixed>  $promo
     */
    private function promo(array $schema, array $answers, array &$promo, ?int $companyId, string $name): void
    {
        $key = FormSchema::mappedKey($schema, 'promo.materials') ?? 'promo_materials';
        $values = is_array($answers[$key] ?? null) ? array_values(array_filter($answers[$key], 'is_string')) : null;

        if ($values === null) {
            $promo['missing']++;

            return;
        }

        $labels = $this->optionLabels($schema, $key);
        $sizes = array_values(array_unique(array_filter(array_map(fn (string $v): ?string => in_array($first = strtok($v, '_'), ['small', 'medium', 'large'], true) ? $first : null, $values))));
        $screens = (bool) array_filter($values, fn (string $v): bool => str_contains($v, 'screen'));
        $prototypes = (bool) array_filter($values, fn (string $v): bool => str_contains($v, 'prototype'));

        foreach ($sizes as $size) {
            $promo[$size]++;
        }

        $promo['multiple'] += count($sizes) > 1 ? 1 : 0;
        $promo['screens'] += $screens ? 1 : 0;
        $promo['prototypes'] += $prototypes ? 1 : 0;

        if (in_array('large', $sizes, true) || $screens || $prototypes) {
            $promo['special'][] = ['company_id' => $companyId, 'name' => $name, 'items' => array_map(fn (string $v): string => $labels[$v] ?? $v, $values)];
        }
    }

    /** @param array<string, mixed> $schema @return array<string, string> */
    private function optionLabels(array $schema, string $key): array
    {
        foreach ($schema['sections'] ?? [] as $section) {
            foreach ($section['fields'] ?? [] as $field) {
                if (($field['key'] ?? null) === $key) {
                    return collect($field['options'] ?? [])->pluck('label', 'value')->all();
                }
            }
        }

        return [];
    }
}
