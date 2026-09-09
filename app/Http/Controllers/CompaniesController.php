<?php

namespace App\Http\Controllers;

use App\Models\Education;
use App\Models\Event;
use App\Models\Sector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class CompaniesController extends Controller
{
    public function index(Request $request): Response
    {
        $event = Event::query()->nextOrLatest()->with([
            'translations',
            'companies' => function ($q) {
                $q->orderBy('name');
                $q->with(['educations.translations', 'sectors.translations']);
            },
        ])->first();

        // If you have zero events in DB, render empty page gracefully
        if (! $event) {
            return Inertia::render('Companies', [
                'event' => null,
                'companies' => [],
                'eventKind' => null,
            ]);
        }

        $today = now()->startOfDay();
        $eventKind = $event->date && $event->date->startOfDay()->gte($today) ? 'upcoming' : 'most-recent';

        $companies = $event->companies->map(function ($company) {
            // Your logos are in: public/company-logos/<logo_path>
            // So URL becomes: /company-logos/<logo_path>
            $logoUrl = null;
            if (! empty($company->logo_path)) {
                // DB already stores the exact public path (e.g. "company-logos/acme.png" or "/company-logos/acme.png")
                // If it is already an absolute URL, keep it as-is.
                $path = trim($company->logo_path);
                if (preg_match('/^https?:\/\//i', $path)) {
                    $logoUrl = $path;
                } else {
                    $logoUrl = asset(ltrim($path, '/'));
                }
            }

            return [
                'id' => $company->id,
                'name' => $company->name,
                'logo_url' => Storage::url($company->logo_path),
                'website_url' => $company->website_url,
                'booth' => $company->pivot?->stand_number, // from company_event.stand_number
                'description' => $company->localizedDescription(),
                'educations' => $company->educations?->map(fn ($education) => $education->translated('name'))->filter()->values() ?? [],
                'sectors' => $company->sectors?->map(fn ($sector) => $sector->translated('name'))->filter()->values() ?? [],
                // Optional if you want them on the page later:
                // 'x_percent' => $company->pivot?->x_percent,
                // 'y_percent' => $company->pivot?->y_percent,
            ];
        })->values();

        return Inertia::render('Companies', [
            'event' => [
                'id' => $event->id,
                'title' => $event->translated('name'),
                'date' => optional($event->date)->toDateString(),
            ],
            'companies' => $companies,
            'eventKind' => $eventKind,
            'educations' => Education::query()->with('translations')->orderBy('name')->get(['id', 'name'])->map(fn (Education $education) => [
                'id' => $education->id,
                'name' => $education->translated('name'),
            ]),
            'sectors' => Sector::query()->with('translations')->orderBy('name')->get(['id', 'name'])->map(fn (Sector $sector) => [
                'id' => $sector->id,
                'name' => $sector->translated('name'),
            ]),
        ]);
    }
}
