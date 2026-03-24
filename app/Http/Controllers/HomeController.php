<?php

namespace App\Http\Controllers;

use App\Models\BorrelEnrollment;
use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use App\Models\Partner;

class HomeController extends Controller
{
    public function index()
    {
        $today = Carbon::today();

        // Upcoming event = first event with a future date
        $upcomingEvent = Event::query()
            ->whereDate('date', '>=', $today)
            ->orderBy('date')
            ->first();

        // Fallback = last event that happened
        $lastEvent = Event::query()
            ->whereDate('date', '<', $today)
            ->orderByDesc('date')
            ->first();

        $highlightEvent = $upcomingEvent ?? $lastEvent;

        // 3 most recent events by event date
        $recentEvents = Event::query()
            ->orderByDesc('date')
            ->limit(3)
            ->get(['id', 'name', 'date', 'description', 'header_image_path'])
            ->map(fn (Event $e) => [
                'id' => $e->id,
                'name' => $e->name,
                'date' => optional($e->date)->toDateString(), // YYYY-MM-DD
                'description' => $e->description, // you cast it to array
                'image_url' => $e->header_image_path ? Storage::url($e->header_image_path) : null,
            ]);

        $partners = $highlightEvent
            ? $highlightEvent->partners()
                ->orderBy('name')
                ->get(['partners.id', 'partners.name', 'partners.url', 'partners.image'])
                ->map(fn (Partner $p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'url' => $p->url,
                    'image_url' => $p->image ? Storage::url($p->image) : null,
                ])
            : collect();

        $highlightEventPayload = $highlightEvent ? [
            'id' => $highlightEvent->id,
            'name' => $highlightEvent->name,
            'date' => optional($highlightEvent->date)->toDateString(),
            'description' => $highlightEvent->description,
            'image_url' => $highlightEvent->header_image_path ? Storage::url($highlightEvent->header_image_path) : null,
        ] : null;

        // Borrel enrollments count for the highlight event (upcoming, else last)
        $closingBorrelCount = $highlightEvent
            ? BorrelEnrollment::query()->where('event_id', $highlightEvent->id)->count()
            : 0;

        return Inertia::render('Home', [
            'recentEvents' => $recentEvents,
            'highlightEvent' => $highlightEventPayload,
            'closingBorrelCount' => $closingBorrelCount,
            'partners' => $partners,
        ]);
    }

    public function partners()
    {
        $event = Event::query()
            ->nextOrLatest()
            ->with(['partners' => fn ($q) => $q->orderBy('name')])
            ->first();

        $eventPayload = $event ? [
            'id' => $event->id,
            'name' => $event->name,
            'date' => optional($event->date)->toDateString(),
        ] : null;

        $mapPartner = fn (Partner $p) => [
            'id' => $p->id,
            'name' => $p->name,
            'url' => $p->url,
            'image_url' => $p->image ? Storage::url($p->image) : null,
            'description' => $p->description ?? null,
            'type' => $p->partner_type ?? $p->type ?? $p->category ?? $p->kind ?? null,
            'has_stand' => (bool) ($p->has_stand ?? false),
            'stand_number' => $p->stand_number ?? null,
        ];

        $isStandPartner = function (Partner $p): bool {
            $type = strtolower((string) ($p->partner_type ?? $p->type ?? $p->category ?? $p->kind ?? ''));

            if (in_array($type, ['stand', 'stands', 'event', 'at_event', 'booth', 'exhibitor'], true)) {
                return true;
            }

            if (!empty($p->stand_number)) {
                return true;
            }

            return (bool) ($p->has_stand ?? false);
        };

        $supportPartners = $event
            ? $event->partners
                ->filter(fn (Partner $p) => ! $isStandPartner($p))
                ->map($mapPartner)
                ->values()
            : collect();

        $standPartners = $event
            ? $event->partners
                ->filter(fn (Partner $p) => $isStandPartner($p))
                ->map($mapPartner)
                ->values()
            : collect();

        return Inertia::render('Partners', [
            'event' => $eventPayload,
            'supportPartners' => $supportPartners,
            'standPartners' => $standPartners,
            'partners' => $supportPartners->concat($standPartners)->values(),
        ]);
    }
}
