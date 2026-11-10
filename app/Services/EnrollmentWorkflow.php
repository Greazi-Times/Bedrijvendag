<?php

namespace App\Services;

use App\Exceptions\AlreadyReviewedException;
use App\Mail\EnrollmentApprovedMail;
use App\Mail\EnrollmentReceivedMail;
use App\Mail\EnrollmentRejectedMail;
use App\Models\Company;
use App\Models\CompanyProfileSubmission;
use App\Models\Education;
use App\Models\Enrollment;
use App\Models\EnrollmentInvitation;
use App\Models\Event;
use App\Models\FormVersion;
use App\Models\Sector;
use App\Models\User;
use App\Support\Forms\FormEngine;
use App\Support\Forms\FormSchema;
use App\Support\MailAfterCommit;
use App\Support\RichText;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/** All enrollment business rules: submit, approve, reject. Controllers and Filament only call this. */
class EnrollmentWorkflow
{
    public const PRIVATE_DISK = 'local';

    public function __construct(private FormEngine $engine, private CompanyMatcher $matcher, private FormResolver $forms) {}

    /**
     * @param  array<string, mixed>  $input  answers (incl. uploads), selected_company_id, submission_key, form_version_id
     *
     * @throws ValidationException
     */
    public function submit(Event $event, array $input): Enrollment
    {
        abort_unless($event->enrollmentState() === 'open', 403);
        $version = $this->forms->version($event, 'enrollment');
        abort_unless($version, 404);

        // The server decides which form applies. A client that rendered an older version must reload.
        if ((int) ($input['form_version_id'] ?? 0) !== $version->id) {
            throw ValidationException::withMessages(['form' => __('enrollment.form_changed')]);
        }

        $submissionKey = (string) ($input['submission_key'] ?? '');
        validator(['k' => $submissionKey], ['k' => 'required|uuid'])->validate();

        if ($existing = Enrollment::query()->where('event_id', $event->id)->where('submission_key', $submissionKey)->first()) {
            return $existing; // double click / resubmitted form: same enrollment, no second mail
        }

        $answers = $this->prefixErrors(fn () => $this->engine->validate(
            $version->schema, (array) ($input['answers'] ?? []), ['max_lunch_participants' => $event->max_lunch_participants],
        ));

        $get = fn (string $map): mixed => $answers[FormSchema::mappedKey($version->schema, $map)] ?? null;
        $name = trim((string) $get('company.name'));
        $email = mb_strtolower(trim((string) $get('contact.email')));
        $selectedId = $input['selected_company_id'] ?? null;
        $selected = $selectedId ? Company::query()->find((int) $selectedId) : null;

        if ($selectedId && ! $selected) {
            throw ValidationException::withMessages(['selected_company_id' => __('enrollment.company_unknown')]);
        }

        $lockKey = 'enrollment-submit:'.$event->id.':'.sha1($this->matcher->key($name).'|'.$email.'|'.($selected?->id ?? 0));

        try {
            return Cache::lock($lockKey, 15)->block((int) config('enrollment.lock_wait', 10), fn (): Enrollment => $this->store($event, $version, $answers, $get, $selected, $name, $email, $submissionKey));
        } catch (LockTimeoutException) {
            throw ValidationException::withMessages(['form' => __('enrollment.busy')]);
        }
    }

    /** Duplicate check and insert, run while holding the per-applicant lock. */
    private function store(Event $event, FormVersion $version, array $answers, \Closure $get, ?Company $selected, string $name, string $email, string $submissionKey): Enrollment
    {
        $existing = Enrollment::query()->where('event_id', $event->id)->where('submission_key', $submissionKey)->first();

        if ($existing) {
            return $existing;
        }

        $this->guardDuplicates($event, $selected, $name, $email);

        return DB::transaction(function () use ($event, $version, $answers, $get, $selected, $name, $email, $submissionKey): Enrollment {
            $files = [];

            foreach ($answers as $key => $value) {
                if ($value instanceof UploadedFile) {
                    $path = $value->store("enrollments/{$event->id}", self::PRIVATE_DISK);
                    $files[$key] = ['path' => $path, 'name' => $this->safeName($value->getClientOriginalName()), 'mime' => (string) $value->getMimeType(), 'size' => $value->getSize()];
                    $answers[$key] = null;
                }
            }

            try {
                $saved = Enrollment::query()->create([
                    'event_id' => $event->id,
                    'form_version_id' => $version->id,
                    'selected_company_id' => $selected?->id,
                    'company_name' => $name,
                    'name_key' => $this->matcher->key($name),
                    'contact_name' => trim((string) $get('contact.name')) ?: null, // optional: the default forms do not ask for it
                    'contact_email' => $email,
                    'answers' => $answers,
                    'files' => $files ?: null,
                    // Frozen at the moment of acceptance: later edits to the edition never rewrite this.
                    'fee_cents_accepted' => $event->fee_cents,
                    'currency_accepted' => $event->currency,
                    'terms_version_accepted' => $event->terms_version,
                    'terms_html_accepted' => RichText::sanitize($event->terms_html),
                    'accepted_at' => now(),
                    'submission_key' => $submissionKey,
                    'submitted_at' => now(),
                ]);
            } catch (UniqueConstraintViolationException) {
                // Lost a race with an identical submission; hand back the winner.
                return Enrollment::query()->where('event_id', $event->id)->where('submission_key', $submissionKey)->firstOrFail();
            }

            $this->queueAfterCommit($saved->contact_email, new EnrollmentReceivedMail($saved));

            return $saved;
        });
    }

    /** Same existing company, or same name + same contact, may not have a second live enrollment. */
    private function guardDuplicates(Event $event, ?Company $selected, string $name, string $email): void
    {
        $live = Enrollment::query()->where('event_id', $event->id)->whereIn('status', [Enrollment::STATUS_PENDING, Enrollment::STATUS_APPROVED]);

        $duplicate = ($selected && (clone $live)->where(fn ($q) => $q->where('selected_company_id', $selected->id)->orWhere('company_id', $selected->id))->exists())
            || (clone $live)->where('name_key', $this->matcher->key($name))->whereEmail('contact_email', $email)->exists();

        if ($duplicate) {
            throw ValidationException::withMessages(['form' => __('enrollment.duplicate')]);
        }
    }

    /**
     * @param  array{mode: string, company_id?: int|null, confirm_distinct?: bool}  $resolution
     * @return bool false when it was already reviewed (repeat calls are harmless)
     *
     * @throws ValidationException when the chosen resolution is not allowed
     */
    public function approve(Enrollment $enrollment, User $by, array $resolution, ?string $note = null): bool
    {
        if ($enrollment->status !== Enrollment::STATUS_PENDING) {
            return false;
        }

        try {
            return Cache::lock('enrollment-approval', 15)->block((int) config('enrollment.lock_wait', 10), function () use ($enrollment, $by, $resolution, $note): bool {
                return DB::transaction(function () use ($enrollment, $by, $resolution, $note): bool {
                    $fresh = Enrollment::query()->with('formVersion')->lockForUpdate()->findOrFail($enrollment->id);

                    if ($fresh->status !== Enrollment::STATUS_PENDING) {
                        throw new AlreadyReviewedException;
                    }

                    $company = ($resolution['mode'] ?? '') === 'existing'
                        ? $this->existingCompany($fresh, (int) ($resolution['company_id'] ?? 0))
                        : $this->createCompany($fresh, (bool) ($resolution['confirm_distinct'] ?? false));

                    $profileSubmission = $company->wasRecentlyCreated ? null : $this->proposeProfileChanges($fresh, $company);

                    $fresh->forceFill([
                        'status' => Enrollment::STATUS_APPROVED,
                        'info_status' => Enrollment::INFO_AWAITING,
                        'company_id' => $company->id,
                        'approved_company_id' => $company->id,
                        'reviewed_by' => $by->id,
                        'reviewed_at' => now(),
                        'review_note' => $note,
                        'profile_submission_id' => $profileSubmission?->id,
                    ])->save();

                    [$invitation, $token] = EnrollmentInvitation::issue($fresh);
                    $this->queueAfterCommit($fresh->contact_email, new EnrollmentApprovedMail($fresh, $token), onSent: fn () => $invitation->forceFill(['queued_at' => now()])->save());

                    $enrollment->setRawAttributes($fresh->getAttributes(), true);

                    return true;
                });
            });
        } catch (AlreadyReviewedException) {
            $enrollment->refresh();

            return false;
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['company_id' => __('enrollment.company_already_approved')]);
        } catch (LockTimeoutException) {
            // Another approval is running; nothing was changed and the admin can simply try again.
            throw ValidationException::withMessages(['enrollment' => __('enrollment.busy')]);
        }
    }

    public function reject(Enrollment $enrollment, User $by, ?string $reason = null, ?string $note = null): bool
    {
        if ($enrollment->status !== Enrollment::STATUS_PENDING) {
            return false;
        }

        try {
            return DB::transaction(function () use ($enrollment, $by, $reason, $note): bool {
                $fresh = Enrollment::query()->lockForUpdate()->findOrFail($enrollment->id);

                if ($fresh->status !== Enrollment::STATUS_PENDING) {
                    throw new AlreadyReviewedException;
                }

                $fresh->forceFill([
                    'status' => Enrollment::STATUS_REJECTED,
                    'reviewed_by' => $by->id,
                    'reviewed_at' => now(),
                    'rejection_reason' => filled($reason) ? trim($reason) : null,
                    'review_note' => $note,
                ])->save();

                $this->queueAfterCommit($fresh->contact_email, new EnrollmentRejectedMail($fresh));
                $enrollment->setRawAttributes($fresh->getAttributes(), true);

                return true;
            });
        } catch (AlreadyReviewedException) {
            $enrollment->refresh();

            return false;
        }
    }

    private function existingCompany(Enrollment $enrollment, int $companyId): Company
    {
        $company = Company::query()->find($companyId)
            ?? throw ValidationException::withMessages(['company_id' => __('enrollment.company_unknown')]);

        $taken = Enrollment::query()->where('event_id', $enrollment->event_id)->where('status', Enrollment::STATUS_APPROVED)
            ->where('approved_company_id', $company->id)->exists();

        if ($taken) {
            throw ValidationException::withMessages(['company_id' => __('enrollment.company_already_approved')]);
        }

        return $company;
    }

    private function createCompany(Enrollment $enrollment, bool $confirmDistinct): Company
    {
        $matches = $this->matcher->matches($enrollment->company_name);

        if ($matches['exact']->isNotEmpty() && ! $confirmDistinct) {
            throw ValidationException::withMessages(['confirm_distinct' => __('enrollment.confirm_distinct_required')]);
        }

        $company = Company::query()->create([
            'name' => $enrollment->company_name,
            'website_url' => $enrollment->mapped('company.website'),
            'profile_contact_email' => $enrollment->contact_email,
            'description_nl' => $enrollment->mapped('company.description'),
            'logo_path' => $this->publishLogo($enrollment),
        ]);
        $company->educations()->sync($this->existingIds(Education::class, $enrollment->mapped('company.educations')));
        $company->sectors()->sync($this->existingIds(Sector::class, $enrollment->mapped('company.sectors')));

        return $company;
    }

    /** Differences with an existing company go through the existing profile review queue; nothing is applied here. */
    private function proposeProfileChanges(Enrollment $enrollment, Company $company): ?CompanyProfileSubmission
    {
        return app(CompanyProfileProposer::class)->propose($company, [
            'website_url' => $enrollment->mapped('company.website'),
            'description' => $enrollment->mapped('company.description'),
            'logo_path' => $this->publishLogo($enrollment),
            'education_ids' => $this->existingIds(Education::class, $enrollment->mapped('company.educations')),
            'sector_ids' => $this->existingIds(Sector::class, $enrollment->mapped('company.sectors')),
        ], $enrollment->contact_name, $enrollment->contact_email, "From enrollment #{$enrollment->id} ({$enrollment->event?->name})", supersede: false);
    }

    /** Copies the private upload to the public logo directory; the private original stays with the enrollment. */
    private function publishLogo(Enrollment $enrollment): ?string
    {
        $file = $enrollment->mappedFile('company.logo');

        if (! $file || ! Storage::disk(self::PRIVATE_DISK)->exists($file['path'])) {
            return null;
        }

        $target = 'company-logos/'.basename($file['path']);
        Storage::disk('public')->put($target, Storage::disk(self::PRIVATE_DISK)->get($file['path']));

        return $target;
    }

    /** @return list<int> ids that really exist; the form is never trusted for ids. */
    private function existingIds(string $model, mixed $ids): array
    {
        return $ids ? $model::query()->whereIn('id', (array) $ids)->pluck('id')->map(fn ($id) => (int) $id)->all() : [];
    }

    private function queueAfterCommit(string $to, Mailable $mail, ?\Closure $onSent = null): void
    {
        MailAfterCommit::queue($to, $mail, $onSent);
    }

    private function safeName(string $name): string
    {
        return mb_substr(preg_replace('/[^\p{L}\p{N}._ -]+/u', '_', basename($name)) ?? 'file', 0, 120);
    }

    /** Engine errors are keyed by field; the public form posts them under "answers.". */
    private function prefixErrors(\Closure $run): array
    {
        try {
            return $run();
        } catch (ValidationException $e) {
            throw ValidationException::withMessages(collect($e->errors())->mapWithKeys(fn ($m, $k) => ["answers.{$k}" => $m])->all());
        }
    }
}
