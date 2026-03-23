<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Barryvdh\Snappy\Facades\SnappyPdf;

class PdfController extends Controller
{
    public function standsPdf(Event $event)
    {
        $stands = $event->stands()
            ->where(function ($query) {
                $query->whereNotNull('company_id')
                    ->orWhereNotNull('partner_id');
            })
            ->with([
                'company.educations',
                'partner.educations',
            ])
            ->get()
            ->sortBy(function ($stand) {
                $typeOrder = $stand->type === 'partner' ? 0 : 1;
                $standNumber = (int) ($stand->stand_number ?? 0);

                return sprintf('%s-%05d', $typeOrder, $standNumber);
            })
            ->values()
            ->map(function ($stand) {
                $displayName = $stand->company?->name ?? $stand->partner?->name;
                $displayDescription = $stand->company?->description ?? $stand->partner?->description;
                $displayWebsiteUrl = $stand->company?->website_url ?? $stand->partner?->website_url;
                $displayEducations = $stand->company?->educations
                    ?? $stand->partner?->educations
                    ?? collect();

                $stand->setAttribute('display_name', $displayName);
                $stand->setAttribute('display_description', $displayDescription);
                $stand->setAttribute('display_website_url', $displayWebsiteUrl);
                $stand->setAttribute('display_educations', $displayEducations);
                $stand->setAttribute(
                    'display_stand_number',
                    $stand->type === 'partner'
                        ? 'P' . (string) ($stand->stand_number ?? '')
                        : (string) ($stand->stand_number ?? '')
                );

                return $stand;
            });

        $fileName = 'stands-'.now()->format('Ymd-His').'.pdf';
        $path = storage_path('app/tmp/'.$fileName);

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0775, true);
        }

        SnappyPdf::loadView('pdf.stands', [
            'event' => $event,
            'stands' => $stands,
            'hasPartnerStands' => $stands->contains(fn ($stand) => $stand->type === 'partner'),
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
            ->setOption('zoom', '1.0')
            ->save($path);

        $showInline = config('app.pdf_inline', false);

        if ($showInline) {
            return response()->file($path, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="stands.pdf"',
            ]);
        }

        return response()->download($path, 'stands.pdf')->deleteFileAfterSend(true);
    }
}
