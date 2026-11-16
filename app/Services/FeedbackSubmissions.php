<?php

namespace App\Services;

use App\Models\FeedbackAnswer;
use App\Models\FeedbackResponse;
use App\Models\FeedbackSurvey;
use App\Support\Forms\FieldType;
use App\Support\Forms\FormEngine;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Validates an anonymous submission against the survey's published version and stores it without any identifier. */
class FeedbackSubmissions
{
    public function __construct(private FormEngine $engine) {}

    /**
     * @param  array<string, mixed>  $input  `answers` (by stable question key) and the `form_version_id` the respondent saw
     *
     * @throws ValidationException
     */
    public function submit(FeedbackSurvey $survey, array $input): FeedbackResponse
    {
        $survey->loadMissing('form');
        $version = $survey->form->latestVersion;

        if (! $survey->isOpen() || ! $version) {
            throw ValidationException::withMessages(['form' => __('feedback.ui.unavailable')]);
        }

        // The version only protects against a form that changed while someone was filling it in; it never selects the schema.
        if (isset($input['form_version_id']) && (int) $input['form_version_id'] !== $version->id) {
            throw ValidationException::withMessages(['form' => __('feedback.ui.outdated')]);
        }

        $raw = $input['answers'] ?? [];
        $fields = collect($this->engine->fields($version->schema))->keyBy('key');

        if (! is_array($raw) || array_diff(array_keys($raw), $fields->keys()->all()) !== []) {
            throw ValidationException::withMessages(['form' => __('feedback.ui.invalid')]);
        }

        $answers = $this->engine->validate($version->schema, $raw);

        return DB::transaction(function () use ($survey, $version, $answers, $fields): FeedbackResponse {
            $response = new FeedbackResponse;
            $response->forceFill([
                'feedback_survey_id' => $survey->id,
                'form_version_id' => $version->id,
                'submitted_on' => now(config('privacy.timezone'))->toDateString(),
            ])->save();

            $rows = [];

            foreach ($answers as $key => $value) {
                $type = FieldType::from($fields[$key]['type']);

                foreach ($type === FieldType::Multiselect ? (array) $value : [$value] as $single) {
                    if ($single === null || $single === '' || $single === false) {
                        continue;
                    }

                    $rows[] = [
                        'feedback_response_id' => $response->id,
                        'question_key' => $key,
                        'value_number' => $type === FieldType::Rating && is_int($single) ? $single : null,
                        'value_text' => $type === FieldType::Rating && is_int($single) ? null : $this->clean((string) $single),
                    ];
                }
            }

            $rows === [] || FeedbackAnswer::query()->insert($rows);

            return $response;
        });
    }

    /** Free text is stored as typed (it is escaped wherever it is shown); only control characters are dropped. */
    private function clean(string $text): string
    {
        return trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $text) ?? '');
    }
}
