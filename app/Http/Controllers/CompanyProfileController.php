<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\CompanyProfileSubmission;
use App\Models\Education;
use App\Models\Sector;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CompanyProfileController extends Controller
{
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
                'description' => $this->htmlDescription($company->description),
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
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'max:4096'],
            'website_url' => ['nullable', 'url', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'education_ids' => ['array'],
            'education_ids.*' => ['integer', Rule::exists('education', 'id')],
            'sector_ids' => ['array'],
            'sector_ids.*' => ['integer', Rule::exists('sectors', 'id')],
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
            'proposed_description' => $this->sanitizeSubmittedDescription($validated['description'] ?? ''),
            'proposed_education_ids' => $validated['education_ids'] ?? [],
            'proposed_sector_ids' => $validated['sector_ids'] ?? [],
            'submitted_at' => now(),
        ]);

        return back()->with('success', 'Je bedrijfsinformatie is verstuurd. We controleren de wijzigingen voordat ze zichtbaar worden op de website.');
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

    private function htmlDescription(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            return trim($value) ?: null;
        }

        if (is_array($value)) {
            $candidate = $value['html'] ?? $value['content'] ?? $value['value'] ?? null;

            if (is_string($candidate)) {
                return trim($candidate) ?: null;
            }

            $flat = collect($value)
                ->filter(fn ($item) => is_string($item) && trim($item) !== '')
                ->implode("\n\n");

            return trim($flat) ?: null;
        }

        return null;
    }

    private function sanitizeSubmittedDescription(?string $description): ?string
    {
        $description = trim((string) $description);

        if ($description === '') {
            return null;
        }

        $allowedTags = [
            'a',
            'b',
            'blockquote',
            'br',
            'em',
            'h2',
            'h3',
            'hr',
            'i',
            'li',
            'ol',
            'p',
            's',
            'strong',
            'u',
            'ul',
        ];

        $description = preg_replace('/<(script|style|iframe|object|embed)\b[^>]*>.*?<\/\1>/is', '', $description) ?? $description;
        $description = strip_tags($description, '<'.implode('><', $allowedTags).'>');
        $description = preg_replace('/\s+on[a-z]+\s*=\s*(["\']).*?\1/is', '', $description) ?? $description;
        $description = preg_replace('/\s+(style|class|id)\s*=\s*(["\']).*?\2/is', '', $description) ?? $description;
        $description = preg_replace_callback('/<a\b([^>]*)>/i', function (array $matches): string {
            if (! preg_match('/\shref\s*=\s*(["\'])(.*?)\1/i', $matches[1], $hrefMatch)) {
                return '<a>';
            }

            $href = trim(html_entity_decode($hrefMatch[2]));

            if (! preg_match('/^(https?:\/\/|mailto:)/i', $href)) {
                return '<a>';
            }

            return '<a href="'.e($href).'" target="_blank" rel="noopener noreferrer">';
        }, $description) ?? $description;
        $description = preg_replace('/\s+href\s*=\s*(["\'])\s*javascript:.*?\1/is', '', $description) ?? $description;

        return trim($description) ?: null;
    }
}
