<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * One pending submission per company. A newer update supersedes the pending
 * row instead of sitting next to it, and `revision` counts the updates within
 * one review cycle. The unique index on the generated `pending_company_id`
 * enforces the rule in the database while history rows stay unrestricted.
 *
 * Companies that already have several pending submissions are reconciled
 * first: the latest stays pending, the older ones become superseded history.
 * Nothing is deleted, approved or rejected. When an older submission proposed
 * something the latest one no longer contains, the latest one gets an
 * attention note so a reviewer checks the history before approving.
 * Running it again changes nothing, because only duplicates are touched.
 */
return new class extends Migration
{
    private const FIELDS = [
        'proposed_name' => 'Company name',
        'proposed_logo_path' => 'Logo',
        'proposed_website_url' => 'Website',
        'proposed_description' => 'Description',
        'proposed_education_ids' => 'Study programmes',
        'proposed_sector_ids' => 'Sectors',
        'proposed_new_sector_names' => 'New sectors',
    ];

    public function up(): void
    {
        Schema::table('company_profile_submissions', function (Blueprint $table) {
            if (! Schema::hasColumn('company_profile_submissions', 'revision')) {
                $table->unsignedInteger('revision')->default(1)->after('status');
            }

            if (! Schema::hasColumn('company_profile_submissions', 'attention_note')) {
                $table->text('attention_note')->nullable()->after('review_note');
            }
        });

        $this->reconcileDuplicatePendingSubmissions();

        if (! Schema::hasColumn('company_profile_submissions', 'pending_company_id')) {
            Schema::table('company_profile_submissions', function (Blueprint $table) {
                $table->unsignedBigInteger('pending_company_id')
                    ->nullable()
                    ->virtualAs("case when status = 'pending' then company_id end");
                $table->unique('pending_company_id');
            });
        }
    }

    public function down(): void
    {
        Schema::table('company_profile_submissions', function (Blueprint $table) {
            $table->dropUnique(['pending_company_id']);
        });

        Schema::table('company_profile_submissions', function (Blueprint $table) {
            $table->dropColumn(['pending_company_id', 'revision', 'attention_note']);
        });

        // Before this migration superseded submissions were simply pending.
        DB::table('company_profile_submissions')->where('status', 'superseded')->update(['status' => 'pending']);
    }

    private function reconcileDuplicatePendingSubmissions(): void
    {
        $companyIds = DB::table('company_profile_submissions')
            ->where('status', 'pending')
            ->groupBy('company_id')
            ->havingRaw('count(*) > 1')
            ->pluck('company_id');

        foreach ($companyIds as $companyId) {
            DB::transaction(function () use ($companyId): void {
                $pending = DB::table('company_profile_submissions')
                    ->where('company_id', $companyId)
                    ->where('status', 'pending')
                    ->orderBy('submitted_at')
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

                $latest = $pending->pop();

                foreach ($pending->values() as $index => $older) {
                    DB::table('company_profile_submissions')->where('id', $older->id)->update([
                        'status' => 'superseded',
                        'revision' => $index + 1,
                    ]);
                }

                DB::table('company_profile_submissions')->where('id', $latest->id)->update([
                    'revision' => $pending->count() + 1,
                    'attention_note' => $this->conflictNote($companyId, $pending, $latest),
                ]);
            });
        }
    }

    /**
     * Lists the older submissions that proposed a value which is neither published nor in the latest submission.
     *
     * @param  Collection<int, object>  $older
     */
    private function conflictNote(int $companyId, Collection $older, object $latest): ?string
    {
        $company = DB::table('companies')->where('id', $companyId)->first();
        $published = [
            'proposed_name' => $company?->name,
            'proposed_logo_path' => $company?->logo_path,
            'proposed_website_url' => $company?->website_url,
            'proposed_description' => $company?->description_nl,
            'proposed_education_ids' => DB::table('company_education')->where('company_id', $companyId)->pluck('education_id')->all(),
            'proposed_sector_ids' => DB::table('company_sector')->where('company_id', $companyId)->pluck('sector_id')->all(),
            'proposed_new_sector_names' => [],
        ];

        $lines = $older->map(function (object $submission) use ($published, $latest): ?string {
            $lost = collect(self::FIELDS)
                ->filter(function (string $label, string $field) use ($submission, $published, $latest): bool {
                    $value = $this->normalize($field, $submission->{$field});

                    return $value !== $this->normalize($field, $latest->{$field})
                        && $value !== $this->normalize($field, $published[$field]);
                })
                ->values();

            return $lost->isEmpty() ? null : "Submission #{$submission->id}: ".$lost->implode(', ');
        })->filter();

        if ($lines->isEmpty()) {
            return null;
        }

        return 'This company sent several separate submissions before updates were combined. '
            .'Older submissions proposed changes that this latest version does not contain. '
            .'Check the submission history before approving. '
            .$lines->implode('; ').'.';
    }

    private function normalize(string $field, mixed $value): mixed
    {
        if (in_array($field, ['proposed_education_ids', 'proposed_sector_ids', 'proposed_new_sector_names'], true)) {
            $values = is_array($value) ? $value : (json_decode((string) $value, true) ?: []);
            $values = array_map(fn ($item) => is_string($item) ? mb_strtolower(trim($item)) : (int) $item, $values);
            sort($values);

            return $values;
        }

        if ($field === 'proposed_logo_path' && blank($value)) {
            // A submission without a logo kept the published logo.
            return null;
        }

        return blank($value) ? null : trim((string) $value);
    }
};
