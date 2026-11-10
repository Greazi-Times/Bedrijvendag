<?php

namespace App\Services;

use App\Mail\EnrollmentInfoReceivedMail;
use App\Models\Company;
use App\Models\Enrollment;
use App\Models\EnrollmentInfoSubmission;
use App\Models\EnrollmentInvitation;
use App\Support\Forms\FormEngine;
use App\Support\Forms\FormSchema;
use App\Support\MailAfterCommit;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** The second form: resolve an invitation, validate and store a new revision, propose profile corrections. */
class InformationWorkflow
{
    public function __construct(private FormEngine $engine, private CompanyProfileSubmissions $profiles, private ContactVerification $contacts, private FormResolver $forms) {}

    /**
     * Resolves a plain token. Only a token whose hash exists reveals expired/revoked; anything else is just "invalid".
     *
     * @return array{state: string, invitation: ?EnrollmentInvitation}
     */
    public function resolve(string $token): array
    {
        $invitation = preg_match('/^[A-Za-z0-9]{48}$/', $token)
            ? EnrollmentInvitation::query()->where('token_hash', EnrollmentInvitation::hash($token))->with('enrollment.event')->first()
            : null;

        $state = match (true) {
            $invitation === null => 'invalid',
            $invitation->revoked_at !== null => 'revoked',
            $invitation->expires_at->isPast() => 'expired',
            $invitation->enrollment->status !== Enrollment::STATUS_APPROVED || ! $invitation->enrollment->company_id => 'invalid',
            default => 'ready',
        };

        if ($state === 'ready') {
            $event = $invitation->enrollment->event;
            $submitted = $invitation->enrollment->info_status === Enrollment::INFO_SUBMITTED;
            // Past the deadline nobody can start or change the form; the submitted answers stay as they are.
            $state = $event->infoIsEditable() ? 'ready' : ($submitted ? 'locked' : 'closed');
        }

        return ['state' => $state, 'invitation' => $state === 'invalid' ? null : $invitation];
    }

    /**
     * @param  array<string, mixed>  $input  form_version_id, answers, verification
     *
     * @throws ValidationException
     */
    public function submit(string $token, array $input): EnrollmentInfoSubmission
    {
        // Resolved again at write time: the link may have been revoked or the deadline passed since the page loaded.
        $resolved = $this->resolve($token);
        abort_unless($resolved['state'] === 'ready', 403);
        $invitation = $resolved['invitation'];

        $enrollment = $invitation->enrollment;
        $event = $enrollment->event;
        $version = $this->forms->version($event, 'info');
        abort_unless($version, 404);

        if ((int) ($input['form_version_id'] ?? 0) !== $version->id) {
            throw ValidationException::withMessages(['form' => __('enrollment.form_changed')]);
        }

        $answers = $this->prefix('answers', fn () => $this->engine->validate($version->schema, (array) ($input['answers'] ?? []), ['max_lunch_participants' => $event->max_lunch_participants]));
        $verification = $this->validateVerification($version->schema, (array) ($input['verification'] ?? []));

        return DB::transaction(function () use ($enrollment, $version, $answers, $verification): EnrollmentInfoSubmission {
            $locked = Enrollment::query()->with('company')->lockForUpdate()->findOrFail($enrollment->id);
            $latest = $locked->infoSubmissions()->first();

            [$answers, $uploads] = $this->splitUploads($answers, $locked);
            $files = collect($latest?->files ?? [])->only(array_keys($answers))->merge($uploads)->all();

            $verificationKey = FormSchema::mappedKey($version->schema, 'company.verification');

            if ($verificationKey && $verification['choice']) {
                $answers[$verificationKey] = $verification['choice'];
            }

            $proposal = $verification['choice'] === 'correction' ? $verification['proposal'] : null;
            $logoUploaded = $verification['logo'] instanceof UploadedFile;

            if ($latest && ! $uploads && ! $logoUploaded
                && $latest->form_version_id === $version->id
                && $latest->answers == $answers
                && $latest->profile_proposal == $proposal
                && $latest->profile_status === $verification['status']) {
                return $latest; // identical resubmission: no new revision, no second email
            }

            $submission = $locked->infoSubmissions()->create([
                'form_version_id' => $version->id,
                'revision' => ($latest?->revision ?? 0) + 1,
                'answers' => $answers,
                'files' => $files ?: null,
                'profile_status' => $verification['status'],
                'profile_proposal' => $proposal,
                'submitted_at' => now(),
            ]);

            if ($proposal !== null) {
                // The same code path as /bedrijf-profiel: validated fields, plain-text-safe comparison, supersede, review queue.
                $logo = $verification['logo'] instanceof UploadedFile ? $verification['logo'] : null;
                $outcome = $this->profiles->storeVersion($locked->company, $this->mergeWithStored($locked->company, $proposal), $logo, $locked->contact_name, $locked->contact_email, "From the information form of enrollment #{$locked->id}");

                if ($outcome['result'] === 'unchanged') {
                    throw ValidationException::withMessages(['verification.choice' => __('messages.company_profile_unchanged')]);
                }

                // "duplicate": the same proposal is already waiting for review, so link to it instead of creating another.
                $review = $outcome['submission'] ?? $this->profiles->latestPending($locked->company);
                $review && $submission->forceFill(['profile_submission_id' => $review->id])->save();
            }

            $locked->forceFill(['info_status' => Enrollment::INFO_SUBMITTED])->save();
            // A contact typed here is only a proposal until its address is verified; mail keeps going to the trusted contact.
            $this->contacts->propose(
                $locked,
                ($key = FormSchema::mappedKey($version->schema, 'contact.name')) ? ($answers[$key] ?? null) : null,
                ($key = FormSchema::mappedKey($version->schema, 'contact.email')) ? ($answers[$key] ?? null) : null,
                $submission,
            );

            $contact = $locked->operationalContact();
            MailAfterCommit::queue($contact['email'], new EnrollmentInfoReceivedMail($locked->loadMissing('event'), $contact['name']));

            return $submission;
        });
    }

    /**
     * The profile block is fixed system behaviour; only its presence and "required" come from the form. Fields the
     * company did not send keep what is stored, so confirming or changing one detail never rewrites the rest.
     *
     * @return array{choice: ?string, status: ?string, proposal: ?array, logo: mixed}
     */
    private function validateVerification(array $schema, array $input): array
    {
        $key = FormSchema::mappedKey($schema, 'company.verification');
        $required = false;

        foreach ($schema['sections'] ?? [] as $section) {
            foreach ($section['fields'] ?? [] as $field) {
                $required = $required || ($field['key'] === $key && ! empty($field['required']));
            }
        }

        if (! $key) {
            return ['choice' => null, 'status' => null, 'proposal' => null, 'logo' => null];
        }

        $rules = ['choice' => [$required ? 'required' : 'nullable', Rule::in(['confirmed', 'correction'])]]
            + ['name' => ['nullable', 'string', 'max:255']] + CompanyProfileSubmissions::rules();
        $data = $this->prefix('verification', fn () => Validator::make($input, $rules)->validate());
        $choice = $data['choice'] ?? null;
        $proposal = null;

        if ($choice === 'correction') {
            $proposal = [];

            foreach (['name', 'website_url', 'description'] as $field) {
                if (array_key_exists($field, $input)) {
                    $proposal[$field] = isset($data[$field]) ? (string) $data[$field] : null;
                }
            }

            foreach (['education_ids', 'sector_ids'] as $field) {
                if (array_key_exists($field, $input)) {
                    $proposal[$field] = $this->profiles->normalizeIds($data[$field] ?? []);
                }
            }

            if (array_key_exists('new_sector_names', $input)) {
                $proposal['new_sector_names'] = $this->profiles->normalizeNewSectorNames($data['new_sector_names'] ?? []);
            }
        }

        return [
            'choice' => $choice,
            'status' => $choice === 'correction' ? 'correction_requested' : ($choice === 'confirmed' ? 'confirmed' : null),
            'proposal' => $proposal,
            'logo' => $choice === 'correction' ? ($input['logo'] ?? null) : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $proposal  only the fields the company sent
     * @return array<string, mixed> a complete proposal: sent fields win, everything else stays as stored or already pending
     */
    private function mergeWithStored(Company $company, array $proposal): array
    {
        $base = $this->profiles->currentProposal($company, $this->profiles->latestPending($company));

        return [
            'name' => ($proposal['name'] ?? null) ?: $base['name'],
            'website_url' => array_key_exists('website_url', $proposal) ? $proposal['website_url'] : $base['website_url'],
            'description' => array_key_exists('description', $proposal) ? $proposal['description'] : $base['description'],
            'education_ids' => $proposal['education_ids'] ?? $base['education_ids'],
            'sector_ids' => $proposal['sector_ids'] ?? $base['sector_ids'],
            'new_sector_names' => $proposal['new_sector_names'] ?? $base['new_sector_names'],
        ];
    }

    /** @return array{0: array<string, mixed>, 1: array<string, array{path: string, name: string, mime: string, size: int}>} */
    private function splitUploads(array $answers, Enrollment $enrollment): array
    {
        $files = [];

        foreach ($answers as $key => $value) {
            if ($value instanceof UploadedFile) {
                $path = $value->store("enrollments/{$enrollment->event_id}/info", EnrollmentWorkflow::PRIVATE_DISK);
                $files[$key] = ['path' => $path, 'name' => mb_substr(preg_replace('/[^\p{L}\p{N}._ -]+/u', '_', basename($value->getClientOriginalName())) ?? 'file', 0, 120), 'mime' => (string) $value->getMimeType(), 'size' => $value->getSize()];
                $answers[$key] = null;
            }
        }

        return [$answers, $files];
    }

    private function prefix(string $prefix, \Closure $run): array
    {
        try {
            return $run();
        } catch (ValidationException $e) {
            throw ValidationException::withMessages(collect($e->errors())->mapWithKeys(fn ($m, $k) => [str_starts_with($k, $prefix.'.') ? $k : "{$prefix}.{$k}" => $m])->all());
        }
    }
}
