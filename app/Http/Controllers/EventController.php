<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;

class EventController extends Controller
{
    public function index(Request $request)
    {
        // Try to support multiple possible column names without breaking your app.
        $startsAtColumn = $this->firstExistingColumn('events', [
            'starts_at',
            'start_at',
            'start_date',
            'date',
            'event_date',
        ]);

        $endsAtColumn = $this->firstExistingColumn('events', [
            'ends_at',
            'end_at',
            'end_date',
        ]);

        $titleColumn = $this->firstExistingColumn('events', [
            'title',
            'name',
        ]) ?? 'name';

        $locationColumn = $this->firstExistingColumn('events', [
            'location',
            'place',
            'city',
        ]);

        $descriptionColumn = $this->firstExistingColumn('events', [
            'description',
            'content',
        ]);

        $shortDescriptionColumn = $this->firstExistingColumn('events', [
            'short_description',
            'summary',
            'excerpt',
        ]);

        $headerImageColumn = $this->firstExistingColumn('events', [
            'header_image_path',
            'header_image',
            'cover_image_path',
            'cover_image',
            'image_path',
            'image',
            'banner_path',
        ]);

        $mapUrlColumn = $this->firstExistingColumn('events', [
            'map_path',
            'map_url',
            'plattegrond_path',
            'plattegrond_url',
        ]);

        $galleryUrlColumn = $this->firstExistingColumn('events', [
            'google_photos_album_url',
            'photo_gallery_url',
            'gallery_url',
        ]);

        $now = Carbon::now();

        $query = Event::query();

        if ($startsAtColumn) {
            // Upcoming first (soonest first), past later (most recent first)
            // We will split after fetching.
            $query->orderBy($startsAtColumn);
        } else {
            $query->latest('id');
        }

        // (Removed withCount blocks)

        // Keep it lightweight: only select columns we need.
        $select = ['id'];
        if ($startsAtColumn) $select[] = $startsAtColumn;
        if ($endsAtColumn) $select[] = $endsAtColumn;
        if ($titleColumn) $select[] = $titleColumn;
        if ($locationColumn) $select[] = $locationColumn;
        if ($shortDescriptionColumn) $select[] = $shortDescriptionColumn;
        if ($descriptionColumn) $select[] = $descriptionColumn;
        if ($headerImageColumn) $select[] = $headerImageColumn;
        if ($mapUrlColumn) $select[] = $mapUrlColumn;
        if ($galleryUrlColumn) $select[] = $galleryUrlColumn;

        $events = $query->get($select)->map(function ($event) use (
            $startsAtColumn,
            $endsAtColumn,
            $titleColumn,
            $locationColumn,
            $shortDescriptionColumn,
            $descriptionColumn,
            $headerImageColumn,
            $mapUrlColumn,
            $galleryUrlColumn
        ) {
            $startsAt = $startsAtColumn ? $event->{$startsAtColumn} : null;
            $endsAt = $endsAtColumn ? $event->{$endsAtColumn} : null;

            $title = $titleColumn ? (string) ($event->{$titleColumn} ?? '') : '';

            $headerImageUrl = null;
            if ($headerImageColumn && !empty($event->{$headerImageColumn})) {
                $path = (string) $event->{$headerImageColumn};
                $headerImageUrl = str_starts_with($path, 'http') ? $path : asset($path);
            }

            $mapUrl = null;
            if ($mapUrlColumn && !empty($event->{$mapUrlColumn})) {
                $path = (string) $event->{$mapUrlColumn};
                $mapUrl = str_starts_with($path, 'http') ? $path : asset($path);
            }

            $galleryUrl = null;
            if ($galleryUrlColumn && !empty($event->{$galleryUrlColumn})) {
                $galleryUrl = (string) $event->{$galleryUrlColumn};
            }

            // Route will be implemented later.
            $editionUrl = '/edities/' . $event->id;

            return [
                'id' => $event->id,
                'title' => $title,
                'starts_at' => $startsAt ? Carbon::parse($startsAt)->toIso8601String() : null,
                'ends_at' => $endsAt ? Carbon::parse($endsAt)->toIso8601String() : null,
                'location' => $locationColumn ? $event->{$locationColumn} : null,
                'short_description' => $shortDescriptionColumn ? $event->{$shortDescriptionColumn} : null,
                'description_html' => $this->asHtmlDescription($descriptionColumn ? $event->{$descriptionColumn} : null),
                'header_image_url' => $headerImageUrl,
                'edition_url' => $editionUrl,
                'gallery_url' => $galleryUrl,
                'map_url' => $mapUrl,
            ];
        })->values();

        $upcoming = $events
            ->filter(function ($e) use ($now) {
                if (!$e['starts_at']) return false;
                return Carbon::parse($e['starts_at'])->greaterThanOrEqualTo($now->startOfDay());
            })
            ->sortBy('starts_at')
            ->values();

        $past = $events
            ->filter(function ($e) use ($now) {
                if (!$e['starts_at']) return true;
                return Carbon::parse($e['starts_at'])->lessThan($now->startOfDay());
            })
            ->sortByDesc('starts_at')
            ->values();

        return Inertia::render('Events', [
            'upcoming' => $upcoming,
            'past' => $past,
        ]);
    }

    public function show(Request $request, Event $event)
    {
        $event = $event->fresh();

        $startsAtColumn = $this->firstExistingColumn('events', ['starts_at','start_at','start_date','date','event_date']);
        $endsAtColumn = $this->firstExistingColumn('events', ['ends_at','end_at','end_date']);
        $titleColumn = $this->firstExistingColumn('events', ['title','name']) ?? 'name';
        $locationColumn = $this->firstExistingColumn('events', ['location','place','city']);
        $descriptionColumn = $this->firstExistingColumn('events', ['description','content']);

        $headerImageColumn = $this->firstExistingColumn('events', [
            'header_image_path','header_image','cover_image_path','cover_image','image_path','image','banner_path',
        ]);

        $mapUrlColumn = $this->firstExistingColumn('events', [
            'map_path','map_url','plattegrond_path','plattegrond_url',
        ]);

        $galleryUrlColumn = $this->firstExistingColumn('events', [
            'google_photos_album_url','photo_gallery_url','gallery_url',
        ]);

        $startsAt = $startsAtColumn ? $event->{$startsAtColumn} : null;
        $endsAt = $endsAtColumn ? $event->{$endsAtColumn} : null;
        $title = (string) ($event->{$titleColumn} ?? '');

        $headerImageUrl = null;
        if ($headerImageColumn && !empty($event->{$headerImageColumn})) {
            $path = (string) $event->{$headerImageColumn};
            $headerImageUrl = str_starts_with($path, 'http') ? $path : asset($path);
        }

        $mapUrl = null;
        if ($mapUrlColumn && !empty($event->{$mapUrlColumn})) {
            $path = (string) $event->{$mapUrlColumn};
            $mapUrl = str_starts_with($path, 'http') ? $path : asset($path);
        }

        $galleryUrl = null;
        if ($galleryUrlColumn && !empty($event->{$galleryUrlColumn})) {
            $galleryUrl = (string) $event->{$galleryUrlColumn};
        }

        $companies = [];
        if (method_exists($event, 'companies')) {
            $companies = $event->companies()
                ->with(['sectors', 'educations'])
                ->orderBy('name')
                ->get()
                ->map(function ($company) {
                    $logoPath = $company->logo_path ?? null;
                    $logoUrl = $logoPath ? (str_starts_with($logoPath, 'http') ? $logoPath : asset($logoPath)) : null;

                    $sectors = [];
                    if (method_exists($company, 'sectors') && $company->relationLoaded('sectors')) {
                        $sectors = $company->sectors->pluck('name')->filter()->values()->all();
                    }

                    $educations = [];
                    if (method_exists($company, 'educations') && $company->relationLoaded('educations')) {
                        $educations = $company->educations->pluck('name')->filter()->values()->all();
                    }

                    $standNumber = null;
                    if (isset($company->pivot) && isset($company->pivot->stand_number)) {
                        $standNumber = $company->pivot->stand_number;
                    }

                    return [
                        'id' => $company->id,
                        'name' => $company->name ?? '',
                        'website_url' => $company->website_url ?? null,
                        'logo_url' => $logoUrl,
                        'description_html' => $this->asHtmlDescription($company->description ?? null),
                        'sectors' => $sectors,
                        'educations' => $educations,
                        'stand_number' => $standNumber,
                    ];
                })
                ->values()
                ->all();
        }

        return Inertia::render('Edition', [
            'event' => [
                'id' => $event->id,
                'title' => $title,
                'starts_at' => $startsAt ? Carbon::parse($startsAt)->toIso8601String() : null,
                'ends_at' => $endsAt ? Carbon::parse($endsAt)->toIso8601String() : null,
                'location' => $locationColumn ? $event->{$locationColumn} : null,
                'description_html' => $this->asHtmlDescription($descriptionColumn ? $event->{$descriptionColumn} : null),
                'header_image_url' => $headerImageUrl,
                'map_url' => $mapUrl,
                'gallery_url' => $galleryUrl,
            ],
            'companies' => $companies,
        ]);
    }

    private function asHtmlDescription(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            return trim($value) !== '' ? $value : null;
        }

        // If your model casts it to array/json, try common keys.
        if (is_array($value)) {
            $candidate = $value['html'] ?? $value['content'] ?? $value['value'] ?? null;

            if (is_string($candidate) && trim($candidate) !== '') {
                return $candidate;
            }

            // Fallback: if it is a simple list, join.
            $flat = collect($value)
                ->filter(fn ($v) => is_string($v) && trim($v) !== '')
                ->implode("\n\n");

            return trim($flat) !== '' ? $flat : null;
        }

        return null;
    }

    private function firstExistingColumn(string $table, array $candidates): ?string
    {
        foreach ($candidates as $column) {
            if (Schema::hasColumn($table, $column)) {
                return $column;
            }
        }

        return null;
    }
}
