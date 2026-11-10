<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanyProfileSubmission;
use App\Models\Sector;
use App\Support\RichText;
use Closure;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * The company profile proposal logic behind /bedrijf-profiel, shared with the information form's profile
 * verification so both validate, compare, supersede and queue for review in exactly the same way. A proposal never
 * touches the live company; an administrator approves the resulting CompanyProfileSubmission.
 */
class CompanyProfileSubmissions
{
    public const DESCRIPTION_MAX_LENGTH = 5000;

    /** @return array<string, mixed> validation rules of the editable profile fields */
    public static function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'max:4096'],
            // http(s) only: the website is rendered as a link on the public site, so javascript:, data: and the like are refused.
            'website_url' => ['nullable', 'url:http,https', 'max:255'],
            'description' => [
                'nullable',
                'string',
                'max:60000',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (self::visibleTextLength($value) > self::DESCRIPTION_MAX_LENGTH) {
                        $fail('validation.max.string')->translate(['max' => self::DESCRIPTION_MAX_LENGTH]);
                    }
                },
            ],
            'education_ids' => ['array'],
            'education_ids.*' => ['integer', Rule::exists('education', 'id')],
            'sector_ids' => ['array'],
            'sector_ids.*' => ['integer', Rule::exists('sectors', 'id')],
            'new_sector_names' => ['array', 'max:10'],
            'new_sector_names.*' => ['string', 'max:80'],
        ];
    }

    /**
     * One submission per company at a time: double clicks, retries and other tabs wait here and then compare against
     * the submission that just went in.
     *
     * @param  array<string, mixed>  $validated
     * @return array{result: string, submission: ?CompanyProfileSubmission}
     */
    public function submit(Company $company, array $validated, ?UploadedFile $logo, ?string $contactName = null, ?string $contactEmail = null, ?string $attention = null): array
    {
        try {
            return Cache::lock("company-profile-submission:{$company->id}", 15)->block(10, function () use ($company, $validated, $logo, $contactName, $contactEmail, $attention): array {
                try {
                    return DB::transaction(fn (): array => $this->storeVersion($company, $validated, $logo, $contactName, $contactEmail, $attention));
                } catch (UniqueConstraintViolationException) {
                    // Another request made a pending submission for this company at the same moment.
                    throw ValidationException::withMessages(['form' => __('messages.company_profile_busy')]);
                }
            });
        } catch (LockTimeoutException) {
            throw ValidationException::withMessages(['form' => __('messages.company_profile_busy')]);
        }
    }

    /**
     * Stores the proposal unless it matches the pending submission or, when nothing is pending, the published profile.
     * A company has at most one pending submission: an update supersedes it (kept as history) and the new version
     * continues the same review cycle with the next revision. Call inside a transaction.
     *
     * @param  array<string, mixed>  $validated
     * @return array{result: 'created'|'updated'|'duplicate'|'unchanged', submission: ?CompanyProfileSubmission}
     */
    public function storeVersion(Company $company, array $validated, ?UploadedFile $logo, ?string $contactName, ?string $contactEmail, ?string $attention = null): array
    {
        // Locked so an administrator's approval or rejection of this version waits for us, and the other way round.
        $pending = $this->latestPending($company, lock: true);
        $current = $this->currentProposal($company, $pending);
        $logoChanged = $logo instanceof UploadedFile && ! $this->sameFile($logo, $current['logo_path']);

        $proposal = [
            'name' => trim($validated['name']),
            'logo_path' => $current['logo_path'],
            'website_url' => filled($validated['website_url'] ?? null) ? trim($validated['website_url']) : null,
            'description' => RichText::sanitize($validated['description'] ?? null),
            'education_ids' => $this->normalizeIds($validated['education_ids'] ?? []),
            'sector_ids' => $this->normalizeIds($validated['sector_ids'] ?? []),
            'new_sector_names' => $this->normalizeNewSectorNames($validated['new_sector_names'] ?? []),
        ];

        if (! $logoChanged && $this->comparable($proposal) === $this->comparable($current)) {
            return ['result' => $pending ? 'duplicate' : 'unchanged', 'submission' => null];
        }

        if ($logoChanged) {
            $proposal['logo_path'] = $logo->store('company-logos', 'public');
        }

        $pending?->forceFill(['status' => CompanyProfileSubmission::STATUS_SUPERSEDED])->save();

        $submission = CompanyProfileSubmission::query()->create([
            'company_id' => $company->id,
            'status' => CompanyProfileSubmission::STATUS_PENDING,
            'revision' => $pending ? $pending->revision + 1 : 1,
            'contact_name' => $contactName,
            'contact_email' => $contactEmail,
            'proposed_name' => $proposal['name'],
            'proposed_logo_path' => $proposal['logo_path'],
            'proposed_website_url' => $proposal['website_url'],
            'proposed_description' => $proposal['description'],
            'proposed_education_ids' => $proposal['education_ids'],
            'proposed_sector_ids' => $proposal['sector_ids'],
            'proposed_new_sector_names' => $proposal['new_sector_names'],
            // Points the reviewer at history whose changes this version may not contain.
            'attention_note' => $pending?->attention_note ?? $attention,
            'submitted_at' => now(),
        ]);

        return ['result' => $pending ? 'updated' : 'created', 'submission' => $submission];
    }

    public function latestPending(Company $company, bool $lock = false): ?CompanyProfileSubmission
    {
        return $company->profileSubmissions()
            ->where('status', CompanyProfileSubmission::STATUS_PENDING)
            ->latest('submitted_at')
            ->latest('id')
            ->when($lock, fn (Builder $query) => $query->lockForUpdate())
            ->first();
    }

    /**
     * The profile a new submission is measured against: the latest pending proposal, or the published profile.
     *
     * @return array{name: string, logo_path: ?string, website_url: ?string, description: ?string, education_ids: array<int, int>, sector_ids: array<int, int>, new_sector_names: array<int, string>}
     */
    public function currentProposal(Company $company, ?CompanyProfileSubmission $pending): array
    {
        if ($pending) {
            return [
                'name' => (string) $pending->proposed_name,
                'logo_path' => $pending->proposed_logo_path ?: $company->logo_path,
                'website_url' => $pending->proposed_website_url,
                'description' => RichText::sanitize($pending->proposed_description),
                'education_ids' => $this->normalizeIds($pending->proposed_education_ids ?? []),
                'sector_ids' => $this->normalizeIds($pending->proposed_sector_ids ?? []),
                'new_sector_names' => array_values($pending->proposed_new_sector_names ?? []),
            ];
        }

        return [
            'name' => $company->name,
            'logo_path' => $company->logo_path,
            'website_url' => $company->website_url,
            'description' => $company->localizedDescription('nl'),
            'education_ids' => $this->normalizeIds($company->educations()->pluck('education.id')->all()),
            'sector_ids' => $this->normalizeIds($company->sectors()->pluck('sectors.id')->all()),
            'new_sector_names' => [],
        ];
    }

    /**
     * Reduces a proposal to the values that matter for "did anything change", so whitespace, ordering and letter case
     * of proposed sectors are not a change.
     *
     * @param  array<string, mixed>  $proposal
     * @return array<string, mixed>
     */
    public function comparable(array $proposal): array
    {
        $names = collect($proposal['new_sector_names'])->map(fn (string $name): string => mb_strtolower($name))->sort()->values()->all();

        return [
            'name' => trim($proposal['name']),
            'logo_path' => $proposal['logo_path'],
            'website_url' => filled($proposal['website_url']) ? trim($proposal['website_url']) : null,
            'description' => $this->normalizeHtml($proposal['description']),
            'education_ids' => $proposal['education_ids'],
            'sector_ids' => $proposal['sector_ids'],
            'new_sector_names' => $names,
        ];
    }

    /**
     * For comparing only: a bare paragraph and the same text in a <p> wrapper, or extra whitespace between tags, are
     * not a change. Real formatting (bold, lists, links) still is.
     */
    public function normalizeHtml(?string $html): ?string
    {
        $html = trim((string) $html);

        if ($html === '') {
            return null;
        }

        if (! str_starts_with($html, '<')) {
            $html = '<p>'.$html.'</p>';
        }

        return trim(preg_replace(['/>\s+</', '/\s+/'], ['><', ' '], $html) ?? $html);
    }

    /**
     * @param  array<int, int|string>  $ids
     * @return array<int, int>
     */
    public function normalizeIds(array $ids): array
    {
        return collect($ids)->map(fn (int|string $id): int => (int) $id)->unique()->sort()->values()->all();
    }

    /** Re-uploading the logo that is already proposed or published is not a change. */
    public function sameFile(UploadedFile $file, ?string $storedPath): bool
    {
        $disk = Storage::disk('public');

        if (! $storedPath || ! $disk->exists($storedPath)) {
            return false;
        }

        return hash_file('sha256', $file->getRealPath()) === hash('sha256', (string) $disk->get($storedPath));
    }

    public static function visibleTextLength(mixed $html): int
    {
        if (! is_string($html)) {
            return 0;
        }

        return mb_strlen(trim(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    }

    /**
     * @param  array<int, string>  $names
     * @return array<int, string>
     */
    public function normalizeNewSectorNames(array $names): array
    {
        $existingNames = Sector::query()->pluck('name')->map(fn (string $name): string => mb_strtolower(trim($name)))->all();
        $seen = [];

        return collect($names)
            ->map(fn (string $name): string => trim(preg_replace('/\s+/', ' ', $name) ?? $name))
            ->filter(fn (string $name): bool => $name !== '')
            ->reject(fn (string $name): bool => in_array(mb_strtolower($name), $existingNames, true))
            ->filter(function (string $name) use (&$seen): bool {
                $key = mb_strtolower($name);

                if (isset($seen[$key])) {
                    return false;
                }

                $seen[$key] = true;

                return true;
            })
            ->values()
            ->all();
    }
}
