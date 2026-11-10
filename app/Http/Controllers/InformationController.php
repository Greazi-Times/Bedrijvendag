<?php

namespace App\Http\Controllers;

use App\Models\Education;
use App\Models\Sector;
use App\Services\FormResolver;
use App\Services\InformationWorkflow;
use App\Support\Forms\FormSchema;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class InformationController extends Controller
{
    public function show(Request $request, string $token, InformationWorkflow $workflow): Response
    {
        ['state' => $state, 'invitation' => $invitation] = $workflow->resolve($token);

        if ($state !== 'ready') {
            // Nothing about the company or event is sent unless the link itself is usable.
            return Inertia::render('Information/Show', [
                'state' => $state, 'stateMessage' => __('information.states.'.$state), 'ui' => __('information.ui'),
            ]);
        }

        $enrollment = $invitation->enrollment->loadMissing(['event', 'company.educations', 'company.sectors']);
        $event = $enrollment->event;
        $version = app(FormResolver::class)->version($event, 'info');
        $latest = $enrollment->infoSubmissions()->first();
        $company = $enrollment->company;

        $initial = $latest?->answers ?? [];

        // Prefilled with the trusted operational contact (verified change, else the original), never an unverified proposal.
        $contact = $enrollment->operationalContact();

        foreach (['contact.name' => $contact['name'], 'contact.email' => $contact['email']] as $map => $value) {
            $key = $version ? FormSchema::mappedKey($version->schema, $map) : null;
            if ($key && ! array_key_exists($key, $initial)) {
                $initial[$key] = $value;
            }
        }

        return Inertia::render('Information/Show', [
            'state' => $version ? 'ready' : 'closed',
            'stateMessage' => $version ? null : __('information.states.closed'),
            'event' => ['name' => $event->name, 'date' => $event->date?->toDateString()],
            'company' => ['name' => $company->name],
            'form' => $version ? ['version_id' => $version->id, 'schema' => $version->schema] : null,
            'initial' => (object) $initial,
            'verification' => [
                'current' => [
                    'name' => $company->name,
                    'description' => trim(strip_tags((string) $company->description_nl)),
                    'description_html' => (string) $company->localizedDescription('nl'),
                    'website_url' => $company->website_url,
                    'logo_url' => $company->logo_path ? Storage::disk('public')->url($company->logo_path) : null,
                    'educations' => $company->educations->pluck('name')->sort()->values(),
                    'sectors' => $company->sectors->pluck('name')->sort()->values(),
                    'education_ids' => $company->educations->pluck('id'),
                    'sector_ids' => $company->sectors->pluck('id'),
                ],
                'choice' => $latest?->profile_status === 'correction_requested' ? 'correction' : $latest?->profile_status,
                'proposal' => $latest?->profile_proposal,
            ],
            'options' => [
                'educations' => Education::query()->orderBy('name')->get(['id', 'name']),
                'sectors' => Sector::query()->orderBy('name')->get(['id', 'name']),
            ],
            'settings' => ['max_lunch_participants' => $event->max_lunch_participants],
            'submitted' => $latest ? ['revision' => $latest->revision, 'at' => $latest->submitted_at->toIso8601String()] : null,
            'editableUntil' => $event->info_closes_at?->toIso8601String(),
            'saved' => (bool) $request->session()->get('information_saved'),
            'urls' => ['store' => route('information.store', $token)],
            'ui' => __('information.ui'),
        ]);
    }

    public function store(Request $request, string $token, InformationWorkflow $workflow): RedirectResponse
    {
        $workflow->submit($token, $request->all());

        return redirect()->route('information.show', $token)->with('information_saved', true);
    }
}
