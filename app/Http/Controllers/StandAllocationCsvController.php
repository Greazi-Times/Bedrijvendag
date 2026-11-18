<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventStand;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StandAllocationCsvController extends Controller
{
    public function __invoke(Event $event): StreamedResponse
    {
        Gate::authorize('viewAny', EventStand::class); // same gate as the Event Map page

        $rows = $event->stands()
            ->with(['company', 'partner'])
            ->get()
            ->map(fn (EventStand $stand): array => [
                ($stand->type === 'partner' ? 'P' : '').$stand->stand_number,
                $stand->company?->name ?? $stand->partner?->name ?? '',
            ])
            ->sortBy(0, SORT_NATURAL);

        $filename = 'stand-allocations-'.(Str::slug($event->name) ?: $event->id).'.csv';

        return response()->streamDownload(function () use ($rows): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel detects the encoding

            fputcsv($out, ['Stand Number', 'Company Name'], ';');

            foreach ($rows as [$number, $name]) {
                // Neutralise spreadsheet formulas in user-supplied names.
                fputcsv($out, [$number, preg_match('/^[=+\-@\t\r]/', $name) ? "'".$name : $name], ';');
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
