<?php

namespace App\Http\Controllers;

use App\Models\CompanyInterestRequest;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CompanyInterestController extends Controller
{
    public function create(): Response
    {
        $upcomingEvent = Event::query()
            ->with('translations')
            ->whereDate('date', '>=', today())
            ->orderBy('date')
            ->first();

        return Inertia::render('CompanyInterest/Create', [
            'submitUrl' => route('company-interest.store'),
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

        $upcomingEvent = Event::query()
            ->whereDate('date', '>=', today())
            ->orderBy('date')
            ->first();

        CompanyInterestRequest::query()->create([
            ...$validated,
            'event_id' => $upcomingEvent?->id,
            'company_name' => trim($validated['company_name']),
            'message' => strip_tags($validated['message'] ?? ''),
        ]);

        return back()->with('success', __('messages.company_interest_success'));
    }
}
