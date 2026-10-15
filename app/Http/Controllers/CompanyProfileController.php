<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\CompanyProfileSubmission;
use App\Models\Education;
use App\Models\Sector;
use App\Support\RichText;
use Closure;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CompanyProfileController extends Controller
{
    private const DESCRIPTION_MAX_LENGTH = 5000;

    public function edit(string $token): Response
    {
        $company = $this->findCompanyForToken($token);
        $pendingSubmission = $this->latestPendingSubmission($company);
        $latestSubmission = $company->profileSubmissions()
            ->where('status', '!=', CompanyProfileSubmission::STATUS_SUPERSEDED)
            ->latest('submitted_at')
            ->latest('id')
            ->first();
        $profile = $this->currentProposal($company, $pendingSubmission);

        $educations = Education::query()->orderBy('name')->get(['id', 'name']);
        $sectors = Sector::query()->orderBy('name')->get(['id', 'name']);

        return Inertia::render('CompanyProfile/Edit', [
            // While a submission awaits review the form shows that proposal, so a reload
            // shows what was sent and an identical resend is recognised as a duplicate.
            'company' => [
                'name' => $profile['name'],
                'logo_url' => $profile['logo_path'] ? Storage::url($profile['logo_path']) : null,
                'website_url' => $profile['website_url'],
                'description' => $profile['description'],
                'education_ids' => array_values(array_intersect($profile['education_ids'], $educations->pluck('id')->all())),
                'sector_ids' => array_values(array_intersect($profile['sector_ids'], $sectors->pluck('id')->all())),
                'new_sector_names' => $profile['new_sector_names'],
            ],
            'options' => [
                'educations' => $educations,
                'sectors' => $sectors,
            ],
            'pendingSubmission' => $pendingSubmission ? [
                'submitted_at' => optional($pendingSubmission->submitted_at)->toIso8601String(),
            ] : null,
            'latestSubmission' => $latestSubmission ? [
                'status' => $latestSubmission->status,
                'submitted_at' => optional($latestSubmission->submitted_at)->toIso8601String(),
                'reviewed_at' => optional($latestSubmission->reviewed_at)->toIso8601String(),
            ] : null,
            'submissionResult' => session('company_profile_result'),
            'submitUrl' => route('company-profile.update', ['token' => $token]),
        ]);
    }

    public function update(Request $request, string $token): RedirectResponse
    {
        $company = $this->findCompanyForToken($token);

        $validated = $request->validate([
            'contact_name' => ['required', 'string', 'max:255'],
            'contact_email' => ['required', 'email', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'max:4096'],
            'website_url' => ['nullable', 'url', 'max:255'],
            'description' => [
                'nullable',
                'string',
                'max:60000',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if ($this->visibleTextLength($value) > self::DESCRIPTION_MAX_LENGTH) {
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
        ]);

        try {
            // One submission per company at a time: double clicks, retries and other tabs
            // wait here and then compare against the submission that just went in.
            $result = Cache::lock("company-profile-submission:{$company->id}", 15)
                ->block(10, fn (): string => $this->storeSubmission($request, $company, $validated));
        } catch (LockTimeoutException) {
            throw ValidationException::withMessages(['form' => __('messages.company_profile_busy')]);
        }

        if ($result === 'unchanged') {
            throw ValidationException::withMessages(['form' => __('messages.company_profile_unchanged')]);
        }

        return back()->with('company_profile_result', $result);
    }

    /**
     * Stores the proposal unless it matches the pending submission or, when
     * nothing is pending, the published profile. A company has at most one
     * pending submission: an update supersedes it (kept as history) and the
     * new version continues the same review cycle with the next revision.
     *
     * @param  array<string, mixed>  $validated
     * @return 'created'|'updated'|'duplicate'|'unchanged'
     */
    private function storeSubmission(Request $request, Company $company, array $validated): string
    {
        try {
            return DB::transaction(fn (): string => $this->storeSubmissionVersion($request, $company, $validated));
        } catch (UniqueConstraintViolationException) {
            // Another request made a pending submission for this company at the same moment.
            throw ValidationException::withMessages(['form' => __('messages.company_profile_busy')]);
        }
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return 'created'|'updated'|'duplicate'|'unchanged'
     */
    private function storeSubmissionVersion(Request $request, Company $company, array $validated): string
    {
        // Locked so an administrator's approval or rejection of this version waits for us, and the other way round.
        $pendingSubmission = $this->latestPendingSubmission($company, lock: true);
        $current = $this->currentProposal($company, $pendingSubmission);
        $logo = $request->file('logo');
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
            return $pendingSubmission ? 'duplicate' : 'unchanged';
        }

        if ($logoChanged) {
            $proposal['logo_path'] = $logo->store('company-logos', 'public');
        }

        $pendingSubmission?->forceFill(['status' => CompanyProfileSubmission::STATUS_SUPERSEDED])->save();

        CompanyProfileSubmission::query()->create([
            'company_id' => $company->id,
            'status' => CompanyProfileSubmission::STATUS_PENDING,
            'revision' => $pendingSubmission ? $pendingSubmission->revision + 1 : 1,
            'contact_name' => $validated['contact_name'] ?? null,
            'contact_email' => $validated['contact_email'] ?? null,
            'proposed_name' => $proposal['name'],
            'proposed_logo_path' => $proposal['logo_path'],
            'proposed_website_url' => $proposal['website_url'],
            'proposed_description' => $proposal['description'],
            'proposed_education_ids' => $proposal['education_ids'],
            'proposed_sector_ids' => $proposal['sector_ids'],
            'proposed_new_sector_names' => $proposal['new_sector_names'],
            // Points the reviewer at history whose changes this version may not contain.
            'attention_note' => $pendingSubmission?->attention_note,
            'submitted_at' => now(),
        ]);

        return $pendingSubmission ? 'updated' : 'created';
    }

    private function latestPendingSubmission(Company $company, bool $lock = false): ?CompanyProfileSubmission
    {
        return $company->profileSubmissions()
            ->where('status', CompanyProfileSubmission::STATUS_PENDING)
            ->latest('submitted_at')
            ->latest('id')
            ->when($lock, fn ($query) => $query->lockForUpdate())
            ->first();
    }

    /**
     * The profile a new submission is measured against: the latest pending proposal, or the published profile.
     *
     * @return array{name: string, logo_path: ?string, website_url: ?string, description: ?string, education_ids: array<int, int>, sector_ids: array<int, int>, new_sector_names: array<int, string>}
     */
    private function currentProposal(Company $company, ?CompanyProfileSubmission $pendingSubmission): array
    {
        if ($pendingSubmission) {
            return [
                'name' => (string) $pendingSubmission->proposed_name,
                'logo_path' => $pendingSubmission->proposed_logo_path ?: $company->logo_path,
                'website_url' => $pendingSubmission->proposed_website_url,
                'description' => RichText::sanitize($pendingSubmission->proposed_description),
                'education_ids' => $this->normalizeIds($pendingSubmission->proposed_education_ids ?? []),
                'sector_ids' => $this->normalizeIds($pendingSubmission->proposed_sector_ids ?? []),
                'new_sector_names' => array_values($pendingSubmission->proposed_new_sector_names ?? []),
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
     * Reduces a proposal to the values that matter for "did anything change", so
     * whitespace, ordering and letter case of proposed sectors are not a change.
     *
     * @param  array<string, mixed>  $proposal
     * @return array<string, mixed>
     */
    private function comparable(array $proposal): array
    {
        $names = collect($proposal['new_sector_names'])->map(fn (string $name): string => mb_strtolower($name))->sort()->values()->all();

        return [
            'name' => trim($proposal['name']),
            'logo_path' => $proposal['logo_path'],
            'website_url' => filled($proposal['website_url']) ? trim($proposal['website_url']) : null,
            'description' => $proposal['description'],
            'education_ids' => $proposal['education_ids'],
            'sector_ids' => $proposal['sector_ids'],
            'new_sector_names' => $names,
        ];
    }

    /**
     * @param  array<int, int|string>  $ids
     * @return array<int, int>
     */
    private function normalizeIds(array $ids): array
    {
        return collect($ids)->map(fn (int|string $id): int => (int) $id)->unique()->sort()->values()->all();
    }

    /** Re-uploading the logo that is already proposed or published is not a change. */
    private function sameFile(UploadedFile $file, ?string $storedPath): bool
    {
        $disk = Storage::disk('public');

        if (! $storedPath || ! $disk->exists($storedPath)) {
            return false;
        }

        return hash_file('sha256', $file->getRealPath()) === hash('sha256', (string) $disk->get($storedPath));
    }

    private function findCompanyForToken(string $token): Company
    {
        $company = Company::query()
            ->where('profile_token', $token)
            ->firstOrFail();

        if ($company->profile_token_expires_at && $company->profile_token_expires_at->isPast()) {
            abort(410);
        }

        return $company;
    }

    private function visibleTextLength(mixed $html): int
    {
        if (! is_string($html)) {
            return 0;
        }

        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return mb_strlen(trim($text));
    }

    /**
     * @param  array<int, string>  $names
     * @return array<int, string>
     */
    private function normalizeNewSectorNames(array $names): array
    {
        $existingNames = Sector::query()
            ->pluck('name')
            ->map(fn (string $name): string => mb_strtolower(trim($name)))
            ->all();

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
