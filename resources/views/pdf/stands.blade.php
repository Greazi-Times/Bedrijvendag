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
            height: 450mm;
            min-height: 450mm;
        }

        .stand-inner {
            border: none;
            border-radius: 4mm;
            padding: 0;
            height: 450mm;
            min-height: 450mm;
            position: relative;
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
            height: 18mm;
            min-height: 18mm;
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
            font-size: 25pt;
            font-weight: 800;
            color: #ffffff;
            text-shadow: 0 0 8px rgba(0, 0, 0, 0.8);
        }

        /* Middle section between header and footer */
        .sectors {
            position: absolute;
            top: 53mm;      /* header (33mm) + spacing (13mm) */
            left: 0;
            right: 0;
            height: 318mm;
            min-height: 318mm;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        /* Sector bars */
        .sector-slot {
            width: 90%;
            height: 30mm;                   /* exact height */
            min-height: 18mm;
            margin-bottom: 9mm;         /* exact spacing */
            border-radius: 5mm;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 20pt;
            font-weight: 900;
            color: #FFFFFF;
            text-shadow: 0 0 12px rgba(0, 0, 0, 0.8);
        }

        /* Remove spacing below the last sector */
        .sector-slot:last-child {
            margin-bottom: 0;
        }

        .sector-mechatronica {
            background-color: #FFAE66; /* softer tone */
        }

        .sector-werktuigbouwkunde {
            background-color: #66FF66; /* softer tone */
        }

        .sector-ict {
            background-color: #3F5F87; /* softer tone */
        }

        .sector-elektrotechniek {
            background-color: #99FFFF; /* softer tone */
        }

        .sector-bitm {
            background-color: #C266FF; /* softer tone */
        }

        .sector-tbk {
            background-color: #664DFF; /* softer tone */
        }

        .sector-industrial {
            background-color: #FF6666; /* softer tone */
        }

        .sector-missing {
            background-color: #FFFFFF;
            border: 1px solid #FFFFFF;
            color: transparent;
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
            height: 18mm;
            min-height: 18mm;
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
        $companyLogo = $company && $company->logo_path ? public_path('storage/logos/' . $company->logo_path) : null;
        $staticLogo = public_path('images/bedrijvendag-logo.png');

        // Pre-calc which DB sector names the company has for quick lookups
        $companySectorNames = $company ? $company->sectors->pluck('name')->all() : [];
    @endphp

    <div class="stand-page">
        <div class="stand-inner">
            <div class="header">
                <div class="company-logo-top">
                    @if($companyLogo && file_exists($companyLogo))
                        <img src="{{ $companyLogo }}" alt="Bedrijfslogo">
                    @endif
                </div>

                <div class="company-name">
                    {{ $company?->name ?? 'Geen bedrijf' }}
                </div>

                <div class="stand-badge-top">
                    {{ $stand->number }}
                </div>
            </div>

            <div class="sectors">
                @foreach($sectorSlots as $slot)
                    @php
                        $hasSector = in_array($slot['db_name'], $companySectorNames, true);
                        $classes = 'sector-slot ' . ($hasSector ? $slot['class'] : 'sector-missing');
                    @endphp
                    <div class="{{ $classes }}">
                        @if($hasSector)
                            {{ $slot['label'] }}
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="footer">
                <div class="stand-badge-bottom">
                    {{ $stand->number }}
                </div>

                <div class="footer-center-logo">
                    @if($staticLogo && file_exists($staticLogo))
                        <img src="{{ $staticLogo }}" alt="ATIx Bedrijvendag">
                    @endif
                </div>

                <div class="company-logo-bottom">
                    @if($companyLogo && file_exists($companyLogo))
                        <img src="{{ $companyLogo }}" alt="Bedrijfslogo">
                    @endif
                </div>
            </div>
        </div>
    </div>
@endforeach

</body>
</html>
