<?php

namespace App\Filament\Widgets;

use App\Models\Event;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;

class UpcomingEventWidget extends Widget
{
    protected static ?int $sort = 2;

    protected string $view = 'filament.widgets.upcoming-event-widget';

    protected int|string|array $columnSpan = 1;

    public function getUpcomingEvent(): ?Event
    {
        $dateColumn = 'date';

        return Event::query()
            ->whereDate($dateColumn, '>=', now()->toDateString())
            ->orderBy($dateColumn)
            ->first();
    }

    // Backwards compatibility: older Blade templates may still call `$this->getEvent()`.
    public function getEvent(): ?Event
    {
        return $this->getUpcomingEvent();
    }

    protected function getViewData(): array
    {
        $event = $this->getUpcomingEvent();

        return [
            'event' => $event,
            'countdown' => $event ? $this->formatCountdown($event) : null,
            'emptyMessage' => $event ? null : "When is the next event? Even the calendar doesn't know.",
        ];
    }

    private function formatCountdown(Event $event): string
    {
        // If your `date` column is already cast to a date/datetime, this will still work.
        $eventDate = $event->date instanceof Carbon
            ? $event->date
            : Carbon::parse($event->date);

        $days = now()->startOfDay()->diffInDays($eventDate->startOfDay(), false);

        if ($days < 0) {
            // Safety fallback: if something slips through, show a readable relative time.
            return $eventDate->diffForHumans();
        }

        if ($days === 0) {
            return 'Today';
        }

        if ($days === 1) {
            return '1 day to go';
        }

        return $days . ' days to go';
    }
}
