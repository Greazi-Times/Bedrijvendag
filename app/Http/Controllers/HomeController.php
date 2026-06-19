<?php

namespace App\Http\Controllers;

use App\Models\BorrelEnrollment;
use App\Models\Event;
use App\Models\Partner;
use App\Support\PageMedia;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

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
        $borrelStatus = match (true) {
            ! $upcomingEvent => 'none',
            $upcomingEvent->date?->isSameDay($today) => 'today',
            default => 'open',
        };

        $borrelEnrollmentEvent = $borrelStatus === 'open' ? $upcomingEvent : null;

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
            ? $highlightEvent->eventPartners()
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

        $borrelEventPayload = $upcomingEvent ? [
            'id' => $upcomingEvent->id,
            'name' => $upcomingEvent->name,
            'date' => optional($upcomingEvent->date)->toDateString(),
            'description' => $upcomingEvent->description,
            'image_url' => $upcomingEvent->header_image_path ? Storage::url($upcomingEvent->header_image_path) : null,
        ] : null;

        // Borrel enrollments are only shown while enrollment is still open.
        $closingBorrelCount = $borrelEnrollmentEvent
            ? BorrelEnrollment::query()->where('event_id', $borrelEnrollmentEvent->id)->count()
            : 0;

        return Inertia::render('Home', [
            'recentEvents' => $recentEvents,
            'highlightEvent' => $highlightEventPayload,
            'borrelEvent' => $borrelEventPayload,
            'borrelStatus' => $borrelStatus,
            'closingBorrelCount' => $closingBorrelCount,
            'partners' => $partners,
            'homeImages' => PageMedia::homeImages(),
            'homeYoutubeUrl' => PageMedia::homeYoutubeUrl(),
        ]);
    }

    public function partners()
    {
        $event = Event::query()
            ->nextOrLatest()
            ->with([
                'eventPartners' => fn ($q) => $q->with('educations')->orderBy('name'),
                'stands' => fn ($q) => $q->where('type', 'partner'),
                'stands.partner' => fn ($q) => $q->with('educations')->orderBy('name'),
            ])
            ->first();

        $eventPayload = $event ? [
            'id' => $event->id,
            'name' => $event->name,
            'date' => optional($event->date)->toDateString(),
        ] : null;

        $mapPartner = fn (Partner $p, $standNumber = null) => [
            'id' => $p->id,
            'name' => $p->name,
            'url' => $p->url,
            'image_url' => $p->image ? Storage::url($p->image) : null,
            'description' => $p->description ?? null,
            'educations' => method_exists($p, 'educations') ? $p->educations->map(fn ($education) => ['id' => $education->id, 'name' => $education->name])->values() : [],
            'stand_number' => $standNumber,
        ];

        // Organising partners come from the event_partner pivot, not event_stands.
        $supportPartners = $event
            ? $event->eventPartners
                ->map(fn (Partner $p) => $mapPartner($p))
                ->values()
            : collect();

        // Stand partners come from event_stands rows with type = partner.
        $standPartners = $event
            ? $event->stands
                ->filter(fn ($stand) => $stand->type === 'partner' && $stand->partner)
                ->map(fn ($stand) => $mapPartner(
                    $stand->partner,
                    $stand->stand_number ?? $stand->number ?? $stand->label ?? null,
                ))
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
