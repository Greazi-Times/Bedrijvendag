<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\CompanyProfileSubmission;
use App\Models\Education;
use App\Models\Sector;
use App\Services\CompanyProfileSubmissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CompanyProfileController extends Controller
{
    public function edit(string $token, CompanyProfileSubmissions $submissions): Response
    {
        $company = $this->findCompanyForToken($token);
        $pendingSubmission = $submissions->latestPending($company);
        $latestSubmission = $company->profileSubmissions()
            ->where('status', '!=', CompanyProfileSubmission::STATUS_SUPERSEDED)
            ->latest('submitted_at')
            ->latest('id')
            ->first();
        $profile = $submissions->currentProposal($company, $pendingSubmission);

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

    public function update(Request $request, string $token, CompanyProfileSubmissions $submissions): RedirectResponse
    {
        $company = $this->findCompanyForToken($token);

        $validated = $request->validate([
            'contact_name' => ['required', 'string', 'max:255'],
            'contact_email' => ['required', 'email', 'max:255'],
        ] + CompanyProfileSubmissions::rules());

        $outcome = $submissions->submit($company, $validated, $request->file('logo'), $validated['contact_name'] ?? null, $validated['contact_email'] ?? null);

        if ($outcome['result'] === 'unchanged') {
            throw ValidationException::withMessages(['form' => __('messages.company_profile_unchanged')]);
        }

        return back()->with('company_profile_result', $outcome['result']);
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
}
