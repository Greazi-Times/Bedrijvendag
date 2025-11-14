<?php

namespace App\Http\Controllers;

use App\Models\Stand;
use App\Models\Sector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Spatie\Browsershot\Browsershot;

class StandPdfController extends Controller
{
    public function downloadAll(Request $request)
    {
        $stands = Stand::with('company.sectors')
            ->orderBy('id')
            ->get();

        foreach ($stands as $stand) {
            $stand->number = $stand->id >= 100
                ? 'P' . ($stand->id - 99)
                : (string) $stand->id;
        }

        // Map DB sector names in a fixed order to display labels and CSS classes
        $sectorSlots = [
            [
                'db_name' => 'Mechatronica',
                'label'   => 'Mechatronica',
                'class'   => 'sector-mechatronica',
            ],
            [
                'db_name' => 'Werktuigbouwkunde',
                'label'   => 'Werktuigbouwkunde',
                'class'   => 'sector-werktuigbouwkunde',
            ],
            [
                'db_name' => 'ICT',
                'label'   => 'ICT',
                'class'   => 'sector-ict',
            ],
            [
                'db_name' => 'Elektrotechniek',
                'label'   => 'Elektrotechniek',
                'class'   => 'sector-elektrotechniek',
            ],
            [
                'db_name' => 'Bussiness IT & Management',
                'label'   => 'Business IT & Management',
                'class'   => 'sector-bitm',
            ],
            [
                'db_name' => 'Technische Bedrijfskunde',
                'label'   => 'Technische Bedrijfskunde',
                'class'   => 'sector-tbk',
            ],
            [
                'db_name' => 'Industrial Engineering & Management',
                'label'   => 'Industrial Engineering',
                'class'   => 'sector-industrial',
            ],
        ];

        Log::debug('StandPdfController (Browsershot): stands count', ['count' => $stands->count()]);

        // Allow ?debug=1 to see the HTML directly in the browser
        if ($request->boolean('debug')) {
            return view('pdf.stands', [
                'stands'      => $stands,
                'sectorSlots' => $sectorSlots,
            ]);
        }

        $html = view('pdf.stands', [
            'stands'      => $stands,
            'sectorSlots' => $sectorSlots,
        ])->render();

        $fileName = 'stands-' . now()->format('Ymd-His') . '.pdf';
        $path = storage_path('app/tmp/' . $fileName);

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0775, true);
        }

        Browsershot::html($html)
            ->setChromePath('/usr/bin/google-chrome')
            ->noSandbox()
            ->format('A4')
            ->margins(10, 10, 10, 10)
            ->showBackground()
            ->save($path);

        return response()->download($path, 'stands.pdf')->deleteFileAfterSend(true);
    }
}
