<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Education;
use App\Models\Event;
use App\Models\Sector;
use App\Services\CompanyMatcher;
use App\Services\EnrollmentWorkflow;
use App\Services\FormResolver;
use App\Support\RichText;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class EnrollmentController extends Controller
{
    public function show(string $token): Response
    {
        $event = $this->event($token);
        $state = $event->enrollmentState();
        $version = $state === 'open' ? app(FormResolver::class)->version($event, 'enrollment') : null;

        return Inertia::render('Enrollment/Show', [
            'event' => ['name' => $event->name, 'date' => $event->date?->toDateString()],
            'state' => $version ? 'open' : ($state === 'open' ? 'disabled' : $state),
            'closedMessage' => __('enrollment.closed.'.($version ? 'disabled' : ($state === 'open' ? 'disabled' : $state))),
            'form' => $version ? ['version_id' => $version->id, 'schema' => $version->schema] : null,
            'fee' => ['cents' => $event->fee_cents, 'currency' => $event->currency],
            'termsHtml' => RichText::sanitize($event->terms_html),
            'options' => [
                'educations' => Education::query()->orderBy('name')->get(['id', 'name']),
                'sectors' => Sector::query()->orderBy('name')->get(['id', 'name']),
            ],
            'settings' => ['max_lunch_participants' => $event->max_lunch_participants],
            'submissionKey' => (string) Str::uuid(),
            'urls' => [
                'store' => route('enrollment.store', $token),
                'search' => route('enrollment.search', $token),
                'company' => route('enrollment.company', [$token, '__ID__']),
            ],
            'ui' => __('enrollment.ui'),
        ]);
    }

    /** Names only. No contact details, tokens or invoice data ever leave through this endpoint. */
    public function search(Request $request, string $token, CompanyMatcher $matcher): JsonResponse
    {
        $this->event($token);
        $name = trim((string) $request->query('q', ''));
        abort_if(mb_strlen($name) > 100, 422);

        $matches = $matcher->matches($name);
        $contains = mb_strlen($name) >= 2
            ? Company::query()->where('name', 'like', '%'.addcslashes($name, '%_\\').'%')->orderBy('name')->limit(8)->get(['id', 'name', 'website_url'])
            : collect();

        $companies = $matches['exact']->merge($matches['similar'])->merge($contains)->unique('id')->take(8);

        return response()->json($companies->map(fn (Company $c): array => [
            'id' => $c->id, 'name' => $c->name, 'website_url' => $c->website_url, 'exact' => $matches['exact']->contains('id', $c->id),
        ])->values());
    }

    /** The already-public profile of one company, used to prefill. Selecting proves nothing about who the applicant is. */
    public function company(string $token, Company $company): JsonResponse
    {
        $this->event($token);

        return response()->json([
            'id' => $company->id,
            'name' => $company->name,
            'website_url' => $company->website_url,
            'description' => strip_tags((string) $company->description_nl),
            'logo_url' => $company->logo_path ? Storage::disk('public')->url($company->logo_path) : null,
            'education_ids' => $company->educations()->pluck('education.id'),
            'sector_ids' => $company->sectors()->pluck('sectors.id'),
        ]);
    }

    public function store(Request $request, string $token, EnrollmentWorkflow $workflow): RedirectResponse
    {
        $event = $this->event($token);
        $workflow->submit($event, $request->all());

        return redirect()->route('enrollment.received', $token);
    }

    public function received(string $token): Response
    {
        $event = $this->event($token);

        return Inertia::render('Enrollment/Received', ['event' => ['name' => $event->name], 'ui' => __('enrollment.ui')]);
    }

    private function event(string $token): Event
    {
        return Event::query()->where('enrollment_token', $token)->firstOrFail();
    }
}
