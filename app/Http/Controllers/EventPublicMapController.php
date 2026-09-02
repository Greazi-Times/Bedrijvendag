<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventMapPoint;
use App\Support\PageMedia;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class EventPublicMapController extends Controller
{
    public function show()
    {
        $event = Event::query()
            ->nextOrLatest()
            ->with([
                'stands.company' => fn ($q) => $q
                    ->with([
                        'educations:id,name',
                        'sectors:id,name',
                    ]),
                'stands.partner' => fn ($q) => $q
                    ->with([
                        'educations:id,name',
                    ]),
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
                'image_url' => PageMedia::eventMapUrl($event->map_path),
            ],
            'stands' => $event->stands
                ->sortBy([
                    ['type', 'asc'],
                    ['stand_number', 'asc'],
                ])
                ->map(function ($stand) {
                    $company = $stand->company;
                    $partner = $stand->partner;
                    $entity = $company ?? $partner;
                    $isCompany = $stand->type === 'company';

                    return [
                        'id' => (string) $stand->id,
                        'code' => (string) ($stand->stand_number ?? '—'),
                        'stand_type' => (string) $stand->type,
                        'company_name' => $entity?->name,
                        'company_logo' => $isCompany
                            ? ($company?->logo_path ? Storage::url($company->logo_path) : null)
                            : (data_get($partner, 'image') ? Storage::url(data_get($partner, 'image')) : null),
                        'company_description' => $entity?->description,
                        'company_website_url' => $entity?->website_url,
                        'company_educations' => $isCompany
                            ? $company?->educations?->pluck('name')->filter()->values()->all()
                            : ($partner?->educations?->pluck('name')->filter()->values()->all() ?? []),
                        'company_sectors' => $isCompany
                            ? $company?->sectors?->pluck('name')->filter()->values()->all()
                            : [],
                        'x_percent' => $stand->x_percent !== null ? (float) $stand->x_percent : null,
                        'y_percent' => $stand->y_percent !== null ? (float) $stand->y_percent : null,
                    ];
                })
                ->values(),
            'mapPoints' => EventMapPoint::query()
                ->where('event_id', $event->id)
                ->whereNotNull('x_percent')
                ->whereNotNull('y_percent')
                ->orderBy('sort_order')
                ->orderBy('label')
                ->get()
                ->map(fn (EventMapPoint $point): array => [
                    'id' => (string) $point->id,
                    'label' => $this->formatMapPointLabel($point),
                    'type' => $point->type,
                    'x_percent' => (float) $point->x_percent,
                    'y_percent' => (float) $point->y_percent,
                ])
                ->values(),
            'backHref' => route('home'),
            'enableZoom' => false,
        ]);
    }

    private function formatMapPointLabel(EventMapPoint $point): string
    {
        return match ($point->type) {
            'bar' => 'Bar',
            'info' => 'Info',
            'lunch' => 'Lunch',
            'entrance' => 'Entrance',
            default => 'Other',
        };
    }
}
