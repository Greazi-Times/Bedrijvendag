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
        ])->setOption('enable-local-file-access', true)
            ->setOption('encoding', 'UTF-8')
            ->setOption('page-size', 'A4')
            ->setOption('margin-top', '10mm')
            ->setOption('margin-right', '10mm')
            ->setOption('margin-bottom', '10mm')
            ->setOption('margin-left', '10mm')
            ->setOption('disable-smart-shrinking', true)
            ->setOption('print-media-type', true)
            ->setOption('header-spacing', 0)
            ->setOption('footer-spacing', 0)
            ->setOption('no-outline', true)
            ->setOption('zoom', '1.0');

        return $pdf->download('event-stands.pdf');
    }
}
