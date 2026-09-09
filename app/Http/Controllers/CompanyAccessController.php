<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\CompanyAccessRequest;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CompanyAccessController extends Controller
{
    public function create(): Response
    {
        $upcomingEvent = Event::query()
            ->with('translations')
            ->whereDate('date', '>=', today())
            ->orderBy('date')
            ->first();

        return Inertia::render('CompanyAccess/Create', [
            'submitUrl' => route('company-access.store'),
            'upcomingEvent' => $upcomingEvent ? [
                'name' => $upcomingEvent->translated('name'),
                'date' => $upcomingEvent->date?->toDateString(),
            ] : null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'website_url' => ['nullable', 'url', 'max:255'],
            'contact_name' => ['required', 'string', 'max:255'],
            'contact_email' => ['required', 'email', 'max:255'],
            'message' => ['nullable', 'string', 'max:2000'],
        ]);

        $companyName = trim($validated['company_name']);
        $company = Company::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($companyName)])
            ->first();

        CompanyAccessRequest::query()->create([
            'type' => $company ? CompanyAccessRequest::TYPE_EXISTING : CompanyAccessRequest::TYPE_NEW,
            'status' => CompanyAccessRequest::STATUS_PENDING,
            'company_id' => $company?->id,
            'company_name' => $company?->name ?? $companyName,
            'website_url' => $company?->website_url ?? ($validated['website_url'] ?? null),
            'contact_name' => $validated['contact_name'],
            'contact_email' => $validated['contact_email'],
            'message' => strip_tags($validated['message'] ?? ''),
            'submitted_at' => now(),
        ]);

        return back()->with('success', __('messages.company_access_success'));
    }
}
