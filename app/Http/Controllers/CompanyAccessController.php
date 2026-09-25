<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\CompanyAccessRequest;
use App\Models\Education;
use App\Models\Sector;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CompanyAccessController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('CompanyAccess/RequestCompanyAccess', [
            'companies' => Company::query()
                ->orderBy('name')
                ->get(['id', 'name', 'website_url'])
                ->map(fn (Company $company): array => [
                    'id' => $company->id,
                    'name' => $company->name,
                    'website_url' => $company->website_url,
                ]),
            'options' => [
                'educations' => Education::query()->orderBy('name')->get(['id', 'name']),
                'sectors' => Sector::query()->orderBy('name')->get(['id', 'name']),
            ],
            'submitUrl' => route('company-access.store'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in([CompanyAccessRequest::TYPE_EXISTING, CompanyAccessRequest::TYPE_NEW])],
            'company_id' => [
                Rule::requiredIf(fn (): bool => $request->input('type') === CompanyAccessRequest::TYPE_EXISTING),
                'nullable',
                'integer',
                Rule::exists('companies', 'id'),
            ],
            'company_name' => [
                Rule::requiredIf(fn (): bool => $request->input('type') === CompanyAccessRequest::TYPE_NEW),
                'nullable',
                'string',
                'max:255',
            ],
            'website_url' => ['nullable', 'url', 'max:255'],
            'logo' => [
                Rule::requiredIf(fn (): bool => $request->input('type') === CompanyAccessRequest::TYPE_NEW),
                'nullable',
                'image',
                'max:4096',
            ],
            'description' => [
                Rule::requiredIf(fn (): bool => $request->input('type') === CompanyAccessRequest::TYPE_NEW),
                'nullable',
                'string',
                'max:5000',
            ],
            'education_ids' => [
                Rule::requiredIf(fn (): bool => $request->input('type') === CompanyAccessRequest::TYPE_NEW),
                'array',
                'min:1',
            ],
            'education_ids.*' => ['integer', Rule::exists('education', 'id')],
            'sector_ids' => ['array'],
            'sector_ids.*' => ['integer', Rule::exists('sectors', 'id')],
            'new_sector_names' => ['array', 'max:10'],
            'new_sector_names.*' => ['string', 'max:80'],
            'contact_name' => ['required', 'string', 'max:255'],
            'contact_email' => ['required', 'email', 'max:255'],
            'message' => ['nullable', 'string', 'max:2000'],
        ]);

        $company = null;

        if ($validated['type'] === CompanyAccessRequest::TYPE_EXISTING) {
            $company = Company::query()->findOrFail($validated['company_id']);
        }

        $logoPath = null;

        if ($validated['type'] === CompanyAccessRequest::TYPE_NEW && $request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('company-logos', 'public');
        }

        CompanyAccessRequest::query()->create([
            'type' => $validated['type'],
            'status' => CompanyAccessRequest::STATUS_PENDING,
            'company_id' => $company?->id,
            'company_name' => $company?->name ?? $validated['company_name'],
            'website_url' => $company?->website_url ?? ($validated['website_url'] ?? null),
            'logo_path' => $logoPath,
            'description' => $validated['type'] === CompanyAccessRequest::TYPE_NEW
                ? trim(strip_tags($validated['description'] ?? ''))
                : null,
            'education_ids' => $validated['type'] === CompanyAccessRequest::TYPE_NEW ? ($validated['education_ids'] ?? []) : [],
            'sector_ids' => $validated['type'] === CompanyAccessRequest::TYPE_NEW ? ($validated['sector_ids'] ?? []) : [],
            'new_sector_names' => $validated['type'] === CompanyAccessRequest::TYPE_NEW
                ? $this->normalizeNewSectorNames($validated['new_sector_names'] ?? [])
                : [],
            'contact_name' => $validated['contact_name'],
            'contact_email' => $validated['contact_email'],
            'message' => strip_tags($validated['message'] ?? ''),
            'submitted_at' => now(),
        ]);

        return back()->with('success', __('messages.company_access_success'));
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
