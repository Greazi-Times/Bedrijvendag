<?php

namespace App\Services\Analytics;

use App\Models\FeedbackAnswer;
use App\Models\FeedbackResponse;
use App\Models\FeedbackSurvey;
use App\Models\FormVersion;
use App\Support\Forms\FieldType;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Feedback statistics, aggregated in SQL (a few grouped queries per survey, never one row per answer in PHP).
 *
 * A "group" is one question with one definition: its stable key plus a signature of what it means (type, rating
 * scale, answer options). Answers are only ever added up within a group, so a question whose scale or options changed
 * between editions becomes a separate group instead of silently mixing incompatible numbers. Rewording a question
 * does not change its signature; changing what it measures should be done by replacing the question (a new key).
 */
class FeedbackStats
{
    /** @var array<int, array<string, array<string, mixed>>> request-level memo, by survey id */
    private array $memo = [];

    /**
     * @return array<string, array<string, mixed>> groups keyed "question_key|signature", with raw counts
     */
    public function survey(FeedbackSurvey $survey): array
    {
        return $this->memo[$survey->id] ??= $this->compute($survey);
    }

    /**
     * The same question groups of several surveys added together, each also keeping its per-survey numbers.
     *
     * @param  Collection<int, FeedbackSurvey>  $surveys
     * @return array<string, array<string, mixed>>
     */
    public function merge(Collection $surveys): array
    {
        $merged = [];

        foreach ($surveys as $survey) {
            foreach ($this->survey($survey) as $gid => $group) {
                $merged[$gid] ??= ['dist' => [], 'counts' => [], 'na' => 0, 'respondents' => 0, 'asked' => 0, 'texts' => 0, 'per_survey' => []] + $group;
                $merged[$gid]['label'] = $group['label'];
                $merged[$gid]['options'] = $group['options'] + $merged[$gid]['options'];

                foreach (['dist', 'counts'] as $map) {
                    foreach ($group[$map] as $value => $count) {
                        $merged[$gid][$map][$value] = ($merged[$gid][$map][$value] ?? 0) + $count;
                    }
                }

                foreach (['na', 'respondents', 'asked', 'texts'] as $sum) {
                    $merged[$gid][$sum] += $group[$sum];
                }
                $merged[$gid]['per_survey'][$survey->id] = $group;
            }
        }

        return $merged;
    }

    /** @return array{n: int, na: int, average: ?float, median: ?float, distribution: list<array{value: int, count: int, percent: float}>} */
    public static function rating(array $group): array
    {
        $n = array_sum($group['dist']);
        $distribution = [];
        $sum = 0;

        foreach (range(1, (int) $group['max']) as $value) {
            $count = (int) ($group['dist'][$value] ?? 0);
            $sum += $value * $count;
            $distribution[] = ['value' => $value, 'count' => $count, 'percent' => $n ? round($count / $n * 100, 1) : 0.0];
        }

        // The k-th smallest rating, read from the cumulative distribution.
        $kth = function (int $k) use ($distribution): int {
            $seen = 0;

            foreach ($distribution as $row) {
                $seen += $row['count'];

                if ($seen >= $k) {
                    return $row['value'];
                }
            }

            return 0;
        };
        $median = $n ? ($kth((int) floor(($n + 1) / 2)) + $kth((int) ceil(($n + 1) / 2))) / 2.0 : null;

        return ['n' => $n, 'na' => (int) $group['na'], 'average' => $n ? round($sum / $n, 2) : null, 'median' => $median, 'distribution' => $distribution];
    }

    /**
     * Option counts with percentages of $group['respondents'] (people who answered this question), which is also the
     * denominator for multi-select, where percentages add up to more than 100.
     *
     * @return list<array{value: string, label: string, count: int, percent: float}>
     */
    public static function choices(array $group): array
    {
        $rows = [];

        foreach ($group['options'] + array_diff_key($group['counts'], $group['options']) as $value => $label) {
            $count = (int) ($group['counts'][$value] ?? 0);
            $rows[] = ['value' => (string) $value, 'label' => is_string($label) ? $label : (string) $value, 'count' => $count, 'percent' => $group['respondents'] ? round($count / $group['respondents'] * 100, 1) : 0.0];
        }

        return $rows;
    }

    /** 0..1 position on the question's own scale, so differently scaled ratings can be ranked. */
    public static function score(array $group): ?float
    {
        $average = self::rating($group)['average'];

        return $average === null ? null : ($average - 1) / max(1, (int) $group['max'] - 1);
    }

    /**
     * Written answers, newest first, optionally of one question and filtered by a search term.
     *
     * @param  list<int>  $surveyIds
     */
    public function texts(array $surveyIds, ?string $question, ?string $search, int $perPage = 15): LengthAwarePaginator
    {
        $textKeys = FeedbackSurvey::query()->whereIn('id', $surveyIds)->get()
            ->flatMap(fn (FeedbackSurvey $s) => collect($this->survey($s))->whereIn('type', ['text', 'textarea']))
            ->pluck('key')->unique()->values()->all();

        return FeedbackAnswer::query()
            ->join('feedback_responses as r', 'r.id', '=', 'feedback_answers.feedback_response_id')
            ->whereIn('r.feedback_survey_id', $surveyIds)
            ->whereIn('feedback_answers.question_key', $question ? array_intersect([$question], $textKeys) : $textKeys)
            ->when($search, fn ($q) => $q->where('feedback_answers.value_text', 'like', '%'.addcslashes($search, '%_\\').'%'))
            ->select('feedback_answers.*', 'r.submitted_on', 'r.feedback_survey_id')
            ->orderByDesc('r.submitted_on')->orderByDesc('feedback_answers.id')
            ->paginate($perPage);
    }

    /** True when the answer belongs to a free-text question, the only kind that can be redacted or removed. */
    public function isText(FeedbackAnswer $answer): bool
    {
        $version = $answer->response->formVersion;

        foreach ($version->schema['sections'] ?? [] as $section) {
            foreach ($section['fields'] ?? [] as $field) {
                if ($field['key'] === $answer->question_key) {
                    return in_array($field['type'], ['text', 'textarea'], true);
                }
            }
        }

        return false;
    }

    /** What makes two questions comparable: type, scale and the set of answer options. */
    public static function signature(array $field): string
    {
        $options = collect($field['options'] ?? [])->pluck('value')->map(fn ($v) => (string) $v)->sort()->values()->all();

        return substr(sha1(json_encode([$field['type'], $field['type'] === 'rating' ? (int) ($field['max'] ?? 5) : null, $options])), 0, 10);
    }

    /** @return array<string, array<string, mixed>> */
    private function compute(FeedbackSurvey $survey): array
    {
        $perVersion = FeedbackResponse::query()->where('feedback_survey_id', $survey->id)->groupBy('form_version_id')->selectRaw('form_version_id, COUNT(*) as c')->pluck('c', 'form_version_id');
        $current = $survey->form()->first()?->latestVersion()->value('id');
        $versions = FormVersion::query()->whereIn('id', $perVersion->keys()->push($current)->filter()->unique())->orderBy('version')->get();

        $groups = [];
        $byVersion = []; // version id => question key => group id
        $choiceKeys = []; // version id => keys whose answers are numbers or option values (not free text)
        $textKeys = [];

        foreach ($versions as $version) {
            foreach ($version->schema['sections'] ?? [] as $section) {
                foreach ($section['fields'] ?? [] as $field) {
                    if (FieldType::from($field['type'])->isDisplayOnly()) {
                        continue;
                    }

                    $gid = $field['key'].'|'.self::signature($field);
                    $groups[$gid] ??= ['key' => $field['key'], 'type' => $field['type'], 'signature' => substr($gid, -10), 'max' => (int) ($field['max'] ?? 5),
                        'options' => [], 'dist' => [], 'counts' => [], 'na' => 0, 'respondents' => 0, 'asked' => 0, 'texts' => 0];
                    $groups[$gid]['label'] = $field['label']; // newest version wins
                    $groups[$gid]['asked'] += (int) ($perVersion[$version->id] ?? 0);

                    foreach ($field['options'] ?? [] as $option) {
                        $groups[$gid]['options'][(string) $option['value']] = $option['label'];
                    }

                    $byVersion[$version->id][$field['key']] = $gid;
                    in_array($field['type'], ['text', 'textarea'], true) ? $textKeys[$version->id][] = $field['key'] : $choiceKeys[$version->id][] = $field['key'];
                }
            }
        }

        $scoped = function ($query, array $keysByVersion) {
            return $query->where(function ($q) use ($keysByVersion): void {
                foreach ($keysByVersion as $versionId => $keys) {
                    $q->orWhere(fn ($w) => $w->where('r.form_version_id', $versionId)->whereIn('a.question_key', $keys));
                }
            });
        };
        $base = fn () => DB::table('feedback_answers as a')->join('feedback_responses as r', 'r.id', '=', 'a.feedback_response_id')->where('r.feedback_survey_id', $survey->id);

        if ($choiceKeys !== []) {
            $rows = $scoped($base(), $choiceKeys)->groupBy('r.form_version_id', 'a.question_key', 'a.value_number', 'a.value_text')
                ->selectRaw('r.form_version_id as v, a.question_key as k, a.value_number as n, a.value_text as t, COUNT(*) as c')->get();

            foreach ($rows as $row) {
                $group = &$groups[$byVersion[$row->v][$row->k]];

                if ($group['type'] === 'rating') {
                    $row->n !== null ? $group['dist'][(int) $row->n] = ($group['dist'][(int) $row->n] ?? 0) + $row->c : $group['na'] += $row->c;
                } else {
                    $group['counts'][(string) $row->t] = ($group['counts'][(string) $row->t] ?? 0) + $row->c;
                }
                unset($group);
            }

            $people = $scoped($base(), $choiceKeys)->groupBy('r.form_version_id', 'a.question_key')
                ->selectRaw('r.form_version_id as v, a.question_key as k, COUNT(DISTINCT a.feedback_response_id) as c')->get();

            foreach ($people as $row) {
                $groups[$byVersion[$row->v][$row->k]]['respondents'] += $row->c;
            }
        }

        if ($textKeys !== []) {
            $texts = $scoped($base()->whereNull('a.redacted_at'), $textKeys)->groupBy('r.form_version_id', 'a.question_key')
                ->selectRaw('r.form_version_id as v, a.question_key as k, COUNT(*) as c')->get();

            foreach ($texts as $row) {
                $groups[$byVersion[$row->v][$row->k]]['texts'] += $row->c;
                $groups[$byVersion[$row->v][$row->k]]['respondents'] += $row->c;
            }
        }

        return $groups;
    }
}
