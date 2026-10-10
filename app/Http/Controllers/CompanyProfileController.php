<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\CompanyProfileSubmission;
use App\Models\Education;
use App\Models\Sector;
use App\Support\RichText;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CompanyProfileController extends Controller
{
    private const DESCRIPTION_MAX_LENGTH = 5000;

    public function edit(string $token): Response
    {
        $company = $this->findCompanyForToken($token);

        $company->load(['educations:id,name', 'sectors:id,name']);

        $pendingSubmission = $company->profileSubmissions()
            ->where('status', CompanyProfileSubmission::STATUS_PENDING)
            ->latest()
            ->first();

        return Inertia::render('CompanyProfile/Edit', [
            'company' => [
                'name' => $company->name,
                'logo_url' => $company->logo_path ? Storage::url($company->logo_path) : null,
                'website_url' => $company->website_url,
                'description' => $company->localizedDescription('nl'),
                'education_ids' => $company->educations->pluck('id')->values(),
                'sector_ids' => $company->sectors->pluck('id')->values(),
            ],
            'options' => [
                'educations' => Education::query()->orderBy('name')->get(['id', 'name']),
                'sectors' => Sector::query()->orderBy('name')->get(['id', 'name']),
            ],
            'pendingSubmission' => $pendingSubmission ? [
                'submitted_at' => optional($pendingSubmission->submitted_at)->toIso8601String(),
            ] : null,
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

        $logoPath = $company->logo_path;

        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('company-logos', 'public');
        }

        CompanyProfileSubmission::query()->create([
            'company_id' => $company->id,
            'status' => CompanyProfileSubmission::STATUS_PENDING,
            'contact_name' => $validated['contact_name'] ?? null,
            'contact_email' => $validated['contact_email'] ?? null,
            'proposed_name' => $validated['name'],
            'proposed_logo_path' => $logoPath,
            'proposed_website_url' => $validated['website_url'] ?? null,
            'proposed_description' => RichText::sanitize($validated['description'] ?? null),
            'proposed_education_ids' => $validated['education_ids'] ?? [],
            'proposed_sector_ids' => $validated['sector_ids'] ?? [],
            'proposed_new_sector_names' => $this->normalizeNewSectorNames($validated['new_sector_names'] ?? []),
            'submitted_at' => now(),
        ]);

        return back()->with('success', __('messages.company_profile_success'));
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
