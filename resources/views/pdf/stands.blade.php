<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <title>Stands</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 10mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background-color: #ffffff;
        }

        .stand-page {
            display: block;
            page-break-after: always;
            position: relative;
            height: 277mm;
            min-height: 277mm;
            overflow: hidden;
        }

        .stand-inner {
            border: none;
            border-radius: 4mm;
            padding: 0;
            height: 277mm;
            min-height: 277mm;
            position: relative;
            overflow: hidden;
        }

        .header {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 33mm;
            min-height: 33mm;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .company-logo-top {
            width: 30mm;
            height: 22mm;
            min-height: 22mm;
            display: flex;
            align-items: center;
            justify-content: flex-start;
        }

        .company-logo-top img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .company-name {
            flex: 1;
            text-align: center;
            font-size: 20pt;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .stand-badge-top {
            width: 20mm;
            height: 20mm;
            min-height: 20mm;
            border-radius: 50%;
            background-color: #F39C12;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32pt;
            font-weight: 1000;
            color: #ffffff;
            text-shadow: 0 0 8px rgba(0, 0, 0, 0.8);
        }

        /* Middle section between header and footer */
        .educations {
            position: absolute;
            top: 40mm;
            left: 0;
            right: 0;
            bottom: 40mm;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        /* Education bars */
        .education-slot {
            width: 90%;
            height: 24mm;
            min-height: 18mm;
            margin-bottom: 6mm;
            border-radius: 5mm;
            padding: 0 6mm;

            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;

            font-size: 26pt;
            font-weight: 900;
            color: #FFFFFF;
            text-shadow: 0 0 12px rgba(0, 0, 0, 0.8);
            box-shadow: 0 0 12px rgba(0, 0, 0, 0.8);
        }

        /* Remove spacing below the last education */
        .education-slot:last-child {
            margin-bottom: 0;
        }

        .education-mechatronica {
            background-color: #FFAE66; /* softer tone */
        }

        .education-werktuigbouwkunde {
            background-color: #66FF66; /* softer tone */
        }

        .education-ict {
            background-color: #3F5F87; /* softer tone */
        }

        .education-elektrotechniek {
            background-color: #99FFFF; /* softer tone */
            color: #1a2a3a;
            text-shadow: 0 0 8px rgba(255, 255, 255, 0.8);
        }

        .education-bitm {
            background-color: #C266FF; /* softer tone */
        }

        .education-tbk {
            background-color: #664DFF; /* softer tone */
        }

        .education-industrial {
            background-color: #FF6666; /* softer tone */
        }

        .education-missing {
            background-color: #FFFFFF;
            border: 1px solid #FFFFFF;
            color: transparent;
            box-shadow: none;
        }

        .footer {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 33mm;
            min-height: 33mm;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .stand-badge-bottom {
            width: 20mm;
            height: 20mm;
            min-height: 20mm;
            border-radius: 50%;
            background-color: #F39C12;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 25pt;
            font-weight: 800;
            color: #ffffff;
            text-shadow: 0 0 8px rgba(0, 0, 0, 0.8);
        }

        .footer-center-logo {
            height: 90%;
            min-height: 90%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .footer-center-logo img {
            max-height: 100%;
            min-height: 100%;
            object-fit: contain;
        }

        .company-logo-bottom {
            width: 30mm;
            height: 22mm;
            min-height: 22mm;
            display: flex;
            align-items: center;
            justify-content: flex-end;
        }

        .company-logo-bottom img {
            max-width: 100%;
            max-height: 100%;
            min-height: 100%;
            object-fit: contain;
        }
    </style>
</head>
<body>

@foreach($stands as $stand)
    @php
        $company = $stand->company;

        $standNumber = $stand->stand_number ?? $stand->number ?? '—';

        $logoPath = $company?->logo_path ?? $company?->logo ?? null;
        $companyLogo = null;

        if ($logoPath) {
            $trimmedLogoPath = ltrim($logoPath, '/');

            if (file_exists($logoPath)) {
                $companyLogo = 'file://' . $logoPath;
            } elseif (file_exists(public_path($trimmedLogoPath))) {
                $companyLogo = 'file://' . public_path($trimmedLogoPath);
            } elseif (file_exists(public_path('storage/' . $trimmedLogoPath))) {
                $companyLogo = 'file://' . public_path('storage/' . $trimmedLogoPath);
            }
        }

        $staticLogoPath = public_path('images/bedrijvendag-logo.png');
        $staticLogo = file_exists($staticLogoPath)
            ? 'file://' . $staticLogoPath
            : null;

        $educationSlots = $company
            ? $company->educations->pluck('name')->filter()->values()->all()
            : [];
    @endphp

    @if(request()->has('debugpaths'))
        <pre style="font-size:10pt; color:#000;">
Company logo DB field: {{ $company?->logo_path ?? $company?->logo ?? 'N/A' }}
Company Logo Path: {{ $companyLogo }}
Exists: {{ $companyLogo ? 'YES' : 'NO' }}
Static Logo Path: {{ $staticLogo }}
Exists: {{ $staticLogo ? 'YES' : 'NO' }}
Storage Dir: {{ public_path('storage/logos') }}
        </pre>
    @endif

    <div class="stand-page">
        <div class="stand-inner">
            <div class="header">
                <div class="company-logo-top">
                    @if($companyLogo)
                        <img src="{{ $companyLogo }}" alt="Bedrijfslogo">
                    @endif
                </div>

                <div class="company-name">
                    {{ $company?->name ?? 'Geen bedrijf' }}
                </div>

                <div class="stand-badge-top">
                    {{ $standNumber }}
                </div>
            </div>

            <div class="educations">
                @forelse($educationSlots as $education)
                    @php
                        $classes = 'education-slot ' . match ($education) {
                            'Mechatronica' => 'education-mechatronica',
                            'Werktuigbouwkunde' => 'education-werktuigbouwkunde',
                            'ICT' => 'education-ict',
                            'Elektrotechniek' => 'education-elektrotechniek',
                            'Business IT & Management' => 'education-bitm',
                            'Technische Bedrijfskunde' => 'education-tbk',
                            'Industrial Engineering & Management' => 'education-industrial',
                            default => 'education-missing',
                        };
                    @endphp
                    <div class="{{ $classes }}">
                        {{ $education }}
                    </div>
                @empty
                    <div class="education-slot education-missing"></div>
                @endforelse
            </div>

            <div class="footer">
                <div class="stand-badge-bottom">
                    {{ $standNumber }}
                </div>

                <div class="footer-center-logo">
                    @if($staticLogo)
                        <img src="{{ $staticLogo }}" alt="ATIx Bedrijvendag">
                    @endif
                </div>

                <div class="company-logo-bottom">
                    @if($companyLogo)
                        <img src="{{ $companyLogo }}" alt="Bedrijfslogo">
                    @endif
                </div>
            </div>
        </div>
    </div>
@endforeach

</body>
</html>
