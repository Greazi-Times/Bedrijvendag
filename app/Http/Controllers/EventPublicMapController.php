<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class EventPublicMapController extends Controller
{
    public function show()
    {
        $event = Event::query()
            ->nextOrLatest()
            ->with([
                'companies' => fn ($q) => $q
                    ->with([
                        'educations:id,name',
                        'sectors:id,name',
                    ])
                    ->orderBy('name'),
            ])
            ->firstOrFail();

        return Inertia::render('Map', [
            'event' => [
                'id' => $event->id,
                'title' => $event->name,
                'date' => optional($event->date)->toDateString(),
            ],
            'map' => [
                'title' => $event->name,
                'image_url' => $event->map_path ? Storage::url($event->map_path) : '',
            ],
            'stands' => $event->companies->map(fn ($company) => [
                'id' => (string) $company->id,
                'code' => (string) ($company->pivot->stand_number ?? '—'),
                'company_name' => $company->name,
                'company_logo' => $company->logo_path ? Storage::url($company->logo_path) : null,
                'company_description' => $company->description,
                'company_website_url' => $company->website_url,
                'company_educations' => $company->educations->pluck('name')->filter()->values()->all(),
                'company_sectors' => $company->sectors->pluck('name')->filter()->values()->all(),
                'x_percent' => $company->pivot->x_percent !== null ? (float) $company->pivot->x_percent : null,
                'y_percent' => $company->pivot->y_percent !== null ? (float) $company->pivot->y_percent : null,
            ])->values(),
            'backHref' => route('home'),
            'enableZoom' => false,
        ]);
    }
}
