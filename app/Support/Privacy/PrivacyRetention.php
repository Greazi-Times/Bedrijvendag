<?php

namespace App\Support\Privacy;

use App\Models\BorrelEnrollment;
use App\Models\CompanyAccessRequest;
use App\Models\CompanyProfileSubmission;
use App\Models\Enrollment;
use App\Models\EnrollmentInfoSubmission;
use App\Services\EnrollmentWorkflow;
use App\Support\Audit;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Enforces the retention periods in config/privacy.php. Everything is state based and idempotent: a row is processed
 * once (a marker column records it) and running the cleanup again, or after a backup restore, simply catches up.
 *
 * What stays forever: the enrollment record (event, company link, status, dates, accepted fee and terms), the company
 * and its public profile, and every stand allocation. What goes: contact details, the full submitted answers, uploads,
 * invitations, second-form submissions and, earlier, dietary answers.
 *
 * Never touched here: users (staff accounts), companies.profile_contact_email (the contact of the company's own
 * profile link) and anything that is still pending review.
 */
class PrivacyRetention
{
    public function __construct(private ?CarbonInterface $now = null) {}

    /** @return array<string, int> counts only; never any personal data */
    public function run(bool $dryRun = false): array
    {
        $now = $this->now ?? now();
        $stats = array_merge(
            $this->dietary($now, $dryRun),
            $this->enrollments($now, $dryRun),
            $this->borrel($now, $dryRun),
            $this->accessRequests($now, $dryRun),
        );

        $dryRun || ErasureLedger::prune();
        DB::table('privacy_runs')->insert(['task' => 'cleanup', 'dry_run' => $dryRun, 'stats' => json_encode($stats), 'created_at' => now()]);

        if (! $dryRun && array_sum($stats) > 0) {
            Audit::log('privacy.cleanup.completed', 'Retention cleanup completed', properties: ['counts' => $stats]);
        }

        return $stats;
    }

    /** Events whose date is on or before this day have been over for more than $days full days. */
    public static function eventCutoff(int $days, ?CarbonInterface $now = null): string
    {
        return ($now ?? now())->copy()->timezone(config('privacy.timezone'))->subDays($days + 1)->toDateString();
    }

    /** @return array<string, int> */
    private function dietary(CarbonInterface $now, bool $dryRun): array
    {
        $cutoff = self::eventCutoff((int) config('privacy.retention.dietary_days_after_event'), $now);
        $stats = ['dietary_submissions_cleaned' => 0, 'dietary_enrollments_cleaned' => 0];

        EnrollmentInfoSubmission::query()->whereNull('sensitive_purged_at')
            ->whereHas('enrollment.event', fn (Builder $q) => $q->whereDate('date', '<=', $cutoff))
            ->with('formVersion')->chunkById((int) config('privacy.batch_size'), function ($subs) use ($dryRun, $now, &$stats): void {
                foreach ($subs as $sub) {
                    $stats['dietary_submissions_cleaned']++;
                    $dryRun || $sub->forceFill(['answers' => SensitiveAnswers::strip($sub->formVersion?->schema ?? [], (array) $sub->answers), 'sensitive_purged_at' => $now])->saveQuietly();
                }
            });

        // A first form may carry a dietary question too if an administrator added one.
        Enrollment::query()->whereNull('personal_data_purged_at')->whereNotNull('answers')
            ->whereHas('event', fn (Builder $q) => $q->whereDate('date', '<=', $cutoff))
            ->with('formVersion')->chunkById((int) config('privacy.batch_size'), function ($rows) use ($dryRun, &$stats): void {
                foreach ($rows as $e) {
                    $schema = $e->formVersion?->schema ?? [];

                    if (SensitiveAnswers::contains($schema, (array) $e->answers)) {
                        $stats['dietary_enrollments_cleaned']++;
                        $dryRun || $e->forceFill(['answers' => SensitiveAnswers::strip($schema, (array) $e->answers)])->saveQuietly();
                    }
                }
            });

        return $stats;
    }

    /** @return array<string, int> */
    private function enrollments(CarbonInterface $now, bool $dryRun): array
    {
        $eventCutoff = self::eventCutoff((int) config('privacy.retention.enrollment_contact_days_after_event'), $now);
        $rejectedBefore = $now->copy()->subMonthsNoOverflow((int) config('privacy.retention.rejected_months_after_handling'));
        $stats = ['enrollments_purged_after_event' => 0, 'enrollments_purged_rejected' => 0, 'pending_left_alone' => 0];

        // Applications still under review are never touched, however old.
        $stats['pending_left_alone'] = Enrollment::query()->where('status', Enrollment::STATUS_PENDING)->whereNull('personal_data_purged_at')
            ->whereHas('event', fn (Builder $q) => $q->whereDate('date', '<=', $eventCutoff))->count();

        Enrollment::query()->whereNull('personal_data_purged_at')->where('status', '!=', Enrollment::STATUS_PENDING)
            ->where(fn (Builder $q) => $q
                ->whereHas('event', fn (Builder $e) => $e->whereDate('date', '<=', $eventCutoff))
                ->orWhere(fn (Builder $r) => $r->where('status', Enrollment::STATUS_REJECTED)->whereNotNull('reviewed_at')->where('reviewed_at', '<=', $rejectedBefore)))
            ->with('event')->chunkById((int) config('privacy.batch_size'), function ($rows) use ($dryRun, $eventCutoff, &$stats, $now): void {
                foreach ($rows as $enrollment) {
                    $ended = $enrollment->event && $enrollment->event->date->toDateString() <= $eventCutoff;
                    $ended ? $stats['enrollments_purged_after_event']++ : $stats['enrollments_purged_rejected']++;
                    $dryRun || $this->purge($enrollment, $now);
                }
            });

        return $stats;
    }

    public function purge(Enrollment $enrollment, ?CarbonInterface $now = null): void
    {
        try {
            DB::transaction(function () use ($enrollment, $now): void {
                $disk = Storage::disk(EnrollmentWorkflow::PRIVATE_DISK);
                $submissions = $enrollment->infoSubmissions()->get();
                $profileIds = $submissions->pluck('profile_submission_id')->push($enrollment->profile_submission_id)->filter()->unique()->all();

                foreach (array_merge([$enrollment->files ?? []], $submissions->pluck('files')->all()) as $files) {
                    foreach ((array) $files as $file) {
                        is_array($file) && ! empty($file['path']) && $disk->delete($file['path']);
                    }
                }

                // Reviewed profile corrections keep their content (public company data) but not who sent them.
                CompanyProfileSubmission::query()->whereIn('id', $profileIds)->where('status', '!=', CompanyProfileSubmission::STATUS_PENDING)
                    ->get()->each(fn (CompanyProfileSubmission $s) => $s->forceFill(['contact_name' => null, 'contact_email' => null])->saveQuietly());

                $enrollment->infoSubmissions()->delete();
                $enrollment->invitations()->delete();
                $enrollment->contactChanges()->delete();

                $enrollment->forceFill([
                    'contact_name' => null, 'contact_email' => null, 'contact_email_hash' => null, 'answers' => null, 'files' => null,
                    'rejection_reason' => null, 'review_note' => null, 'personal_data_purged_at' => $now ?? now(),
                ])->saveQuietly();
            });
        } catch (Throwable $e) {
            // One broken record must not stop the rest; it is retried on the next run. The message may name data, so it is not logged.
            report(new \RuntimeException('Privacy purge failed for an enrollment ('.$e::class.').'));
        }
    }

    /** @return array<string, int> */
    private function borrel(CarbonInterface $now, bool $dryRun): array
    {
        $cutoff = self::eventCutoff((int) config('privacy.retention.borrel_days_after_event'), $now);
        $stats = ['borrel_guests_purged' => 0];

        BorrelEnrollment::query()->whereNull('personal_data_purged_at')->whereHas('event', fn (Builder $q) => $q->whereDate('date', '<=', $cutoff))
            ->chunkById((int) config('privacy.batch_size'), function ($rows) use ($dryRun, $now, &$stats): void {
                foreach ($rows as $guest) {
                    $stats['borrel_guests_purged']++;
                    // The row stays so attendance counts per event still add up.
                    $dryRun || $guest->forceFill(['name' => null, 'email' => null, 'email_hash' => null, 'personal_data_purged_at' => $now])->saveQuietly();
                }
            });

        return $stats;
    }

    /** @return array<string, int> */
    private function accessRequests(CarbonInterface $now, bool $dryRun): array
    {
        $before = $now->copy()->subMonthsNoOverflow((int) config('privacy.retention.access_request_months_after_handling'));
        $stats = ['access_requests_purged' => 0];

        // Handled requests only; the staff or company account created from an approval is separate data and is not touched.
        CompanyAccessRequest::query()->whereNull('personal_data_purged_at')->where('status', '!=', CompanyAccessRequest::STATUS_PENDING)
            ->whereNotNull('reviewed_at')->where('reviewed_at', '<=', $before)
            ->chunkById((int) config('privacy.batch_size'), function ($rows) use ($dryRun, $now, &$stats): void {
                foreach ($rows as $request) {
                    $stats['access_requests_purged']++;
                    $dryRun || $request->forceFill(['contact_name' => null, 'contact_email' => null, 'message' => null, 'review_note' => null, 'personal_data_purged_at' => $now])->saveQuietly();
                }
            });

        return $stats;
    }
}
