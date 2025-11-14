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
                'companyLogo' => null,
                'companyName' => null,
                'standNumber' => null,
                'eventLogo'   => public_path('img/bedrijvendag-footer.png'),
                'sectors'     => [],
            ]);
        }

        $standPages = [];

        foreach ($stands as $stand) {
            $company = $stand->company;
            $companyLogo = $company && $company->logo_path
                ? public_path('storage/' . $company->logo_path)
                : null;

            $companyName = $company ? $company->name : '';
            $standNumber = $stand->number;

            $assignedSectors = $company
                ? $company->sectors->pluck('name')->toArray()
                : [];

            $standPages[] = view('pdf.stands', [
                'stands'      => [$stand],
                'sectorSlots' => $sectorSlots,
                'companyLogo' => $companyLogo,
                'companyName' => $companyName,
                'standNumber' => $standNumber,
                'eventLogo'   => public_path('img/bedrijvendag-footer.png'),
                'sectors'     => $assignedSectors,
            ])->render();
        }

        $html = implode('<div class="page-break"></div>', $standPages);

        $fileName = 'stands-' . now()->format('Ymd-His') . '.pdf';
        $path = storage_path('app/tmp/' . $fileName);

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0775, true);
        }

        $pdf = \PDF::loadHTML($html)
            ->setPaper('a4')
            ->setOption('margin-top', '10mm')
            ->setOption('margin-right', '10mm')
            ->setOption('margin-bottom', '10mm')
            ->setOption('margin-left', '10mm')
            ->setOption('enable-local-file-access', true);

        return $pdf->inline('stands.pdf');
    }
}
