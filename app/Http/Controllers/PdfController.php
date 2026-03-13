<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Barryvdh\Snappy\Facades\SnappyPdf;

class PdfController extends Controller
{
    public function standsPdf(Event $event)
    {
        $stands = $event->stands()
            ->whereNotNull('company_id')
            ->with(['company.educations'])
            ->orderBy('stand_number')
            ->get();

        $pdf = SnappyPdf::loadView('pdf.stands', [
            'event' => $event,
            'stands' => $stands,
        ]);

        return $pdf->download('event-stands.pdf');
    }
}
