<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\CompanyAccessRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CompanyAccessController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('CompanyAccess/Create', [
            'companies' => Company::query()
                ->orderBy('name')
                ->get(['id', 'name', 'website_url'])
                ->map(fn (Company $company): array => [
                    'id' => $company->id,
                    'name' => $company->name,
                    'website_url' => $company->website_url,
                ]),
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
            'contact_name' => ['required', 'string', 'max:255'],
            'contact_email' => ['required', 'email', 'max:255'],
            'message' => ['nullable', 'string', 'max:2000'],
        ]);

        $company = null;

        if ($validated['type'] === CompanyAccessRequest::TYPE_EXISTING) {
            $company = Company::query()->findOrFail($validated['company_id']);
        }

        CompanyAccessRequest::query()->create([
            'type' => $validated['type'],
            'status' => CompanyAccessRequest::STATUS_PENDING,
            'company_id' => $company?->id,
            'company_name' => $company?->name ?? $validated['company_name'],
            'website_url' => $company?->website_url ?? ($validated['website_url'] ?? null),
            'contact_name' => $validated['contact_name'],
            'contact_email' => $validated['contact_email'],
            'message' => strip_tags($validated['message'] ?? ''),
            'submitted_at' => now(),
        ]);

        return back()->with('success', 'Je aanvraag is ontvangen. De organisatie controleert deze voordat er toegang wordt gegeven.');
    }
}
