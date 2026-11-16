<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Form;
use App\Support\Forms\DefaultFeedbackForms;
use App\Support\Forms\DefaultForms;
use App\Support\Forms\LegacyDefaultForms;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Creates the two global default forms and moves editions onto them. Everything here is idempotent: running it
 * again never duplicates a form, never resets an administrator's edits and never touches a customised edition.
 */
class GlobalForms
{
    private const NAMES = [
        'enrollment' => 'Default company enrollment form', 'info' => 'Default additional company information form',
        'feedback_student' => 'Default Student Feedback', 'feedback_company' => 'Default Company Feedback',
    ];

    public function get(string $kind): Form
    {
        $find = fn () => Form::query()->where('system_key', Form::GLOBAL_KEYS[$kind])->first();

        if (! ($form = $find())) {
            $this->ensureOne($kind); // lazily self-heals a database that was restored without the defaults
            $form = $find() ?? throw new \RuntimeException("Global {$kind} form could not be created.");
        }

        return $form;
    }

    /** @return array{created: list<string>, editions: array<int, array<string, string>>} */
    public function initialize(): array
    {
        return ['created' => $this->ensure(), 'editions' => $this->adoptEditions()];
    }

    /** @return list<string> kinds that had to be created */
    public function ensure(): array
    {
        return array_values(array_filter(array_keys(Form::GLOBAL_KEYS), fn (string $kind): bool => $this->ensureOne($kind)));
    }

    /**
     * Existing editions that still hold their automatically generated, never-edited copy inherit the global form
     * from now on. Anything else (edited, republished, shared, or simply not recognisable) is kept and reported.
     *
     * @return array<int, array<string, string>> event id => kind => outcome
     */
    public function adoptEditions(): array
    {
        $report = [];

        foreach (Event::query()->where(fn ($q) => $q->whereNotNull('enrollment_form_id')->orWhereNotNull('info_form_id'))->get() as $event) {
            foreach (FormResolver::COLUMNS as $kind => $column) {
                if (! $event->{$column}) {
                    continue;
                }

                $form = Form::query()->with('versions')->find($event->{$column});
                $outcome = $this->classify($event, $form, $kind);

                if ($outcome === 'adopted') {
                    DB::table('events')->where('id', $event->id)->update([$column => null]); // the old copy and its versions stay for history
                }

                $report[$event->id][$kind] = $outcome;
            }
        }

        return $report;
    }

    /** adopted | kept: customised | kept: not an edition copy | kept: missing form */
    private function classify(Event $event, ?Form $form, string $kind): string
    {
        if (! $form) {
            return 'adopted'; // a dangling pointer: nothing to preserve
        }

        if ($form->system_key !== null || $form->event_id !== $event->id || $form->kind !== $kind) {
            return 'kept: not an edition copy';
        }

        $legacy = $kind === 'enrollment' ? LegacyDefaultForms::enrollment() : LegacyDefaultForms::info();
        $versions = $form->versions;

        $untouched = $versions->count() === 1
            && $this->canonical($versions->first()->schema) === $this->canonical($legacy)
            && $this->canonical($form->draft_schema) === $this->canonical($legacy);

        return $untouched ? 'adopted' : 'kept: customised';
    }

    private function ensureOne(string $kind): bool
    {
        $key = Form::GLOBAL_KEYS[$kind];

        if (DB::table('forms')->where('system_key', $key)->exists()) {
            return false;
        }

        $schema = json_encode(match ($kind) {
            'enrollment' => DefaultForms::enrollment(),
            'info' => DefaultForms::info(),
            default => DefaultFeedbackForms::for($kind),
        });
        $now = now();

        try {
            DB::transaction(function () use ($kind, $key, $schema, $now): void {
                $id = DB::table('forms')->insertGetId([
                    'name' => self::NAMES[$kind], 'kind' => $kind, 'system_key' => $key, 'is_template' => false, 'event_id' => null,
                    'draft_schema' => $schema, 'created_at' => $now, 'updated_at' => $now,
                ]);
                DB::table('form_versions')->insert(['form_id' => $id, 'version' => 1, 'schema' => $schema, 'published_by' => null, 'published_at' => $now, 'created_at' => $now, 'updated_at' => $now]);
            });
        } catch (UniqueConstraintViolationException) {
            return false; // a concurrent run created it first
        }

        return true;
    }

    /** Key-order-insensitive form for comparing schemas. */
    private function canonical(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        $value = array_map($this->canonical(...), $value);
        array_is_list($value) || ksort($value);

        return $value;
    }
}
