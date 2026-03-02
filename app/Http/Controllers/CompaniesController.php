<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Education;
use App\Models\Sector;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CompaniesController extends Controller
{
    public function index(Request $request): Response
    {
        $event = Event::query()->nextOrLatest()->with([
            'companies' => function ($q) {
                $q->orderBy('name');
                $q->with(['educations:id,name', 'sectors:id,name']);
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
                'logo_url' => $logoUrl,
                'website_url' => $company->website_url,
                'booth' => $company->pivot?->stand_number, // from company_event.stand_number
                'description' => is_array($company->description) ? implode(' ', array_filter($company->description)) : $company->description,
                'educations' => $company->educations?->pluck('name')->values() ?? [],
                'sectors' => $company->sectors?->pluck('name')->values() ?? [],
                // Optional if you want them on the page later:
                // 'x_percent' => $company->pivot?->x_percent,
                // 'y_percent' => $company->pivot?->y_percent,
            ];
        })->values();

        return Inertia::render('Companies', [
            'event' => [
                'id' => $event->id,
                'title' => $event->name, // keeps your current Companies.vue working
                'date' => optional($event->date)->toDateString(),
            ],
            'companies' => $companies,
            'eventKind' => $eventKind,
            'educations' => Education::query()->orderBy('name')->get(['id', 'name']),
            'sectors' => Sector::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
