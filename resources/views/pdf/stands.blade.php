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
            height: 360mm;
            min-height: 360mm;
        }

        .stand-inner {
            border: none;
            border-radius: 4mm;
            padding: 0;
            height: 360mm;
            min-height: 360mm;
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

        .educations {
            position: absolute;
            top: 43mm;
            left: 0;
            right: 0;
            height: 265mm;
            min-height: 265mm;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .education-slot {
            width: 90%;
            height: 30mm;
            min-height: 18mm;
            margin-bottom: 9mm;
            border-radius: 5mm;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 32pt;
            font-weight: 900;
            color: #FFFFFF;
            text-shadow: 0 0 12px rgba(0, 0, 0, 0.8);
            box-shadow: 0 0 12px rgba(0, 0, 0, 0.8);

            text-align: center;
            padding: 0 4mm;
            line-height: 1.1;
            word-break: break-word;
            overflow: hidden;
        }

        .education-slot:last-child {
            margin-bottom: 0;
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
            ? $company->educations->filter(fn ($education) => filled($education->name))->values()
            : collect();
    @endphp

    @if(request()->has('debugpaths'))
        <pre style="font-size:10pt; color:#000;">
Company logo DB field: {{ $company?->logo_path ?? $company?->logo ?? 'N/A' }}
Company Logo Path: {{ $companyLogo }}
Exists: {{ $companyLogo ? 'YES' : 'NO' }}
Static Logo Path: {{ $staticLogo }}
Exists: {{ $staticLogo ? 'YES' : 'NO' }}
Storage Dir: {{ public_path('storage/logos') }}
Static logo file exists: {{ file_exists(str_replace('file://', '', $staticLogo ?? '')) ? 'YES' : 'NO' }}
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
                        $backgroundColor = filled($education->color) ? $education->color : '#CCCCCC';
                        $normalizedBackgroundColor = strtoupper($backgroundColor);

                        $textColor = '#FFFFFF';
                        $textShadow = '0 0 12px rgba(0, 0, 0, 0.8)';

                        if ($normalizedBackgroundColor === '#99FFFF') {
                            $textColor = '#1a2a3a';
                            $textShadow = '0 0 8px rgba(255, 255, 255, 0.8)';
                        }
                    @endphp
                    <div
                        class="education-slot"
                        style="background-color: {{ $backgroundColor }}; color: {{ $textColor }}; text-shadow: {{ $textShadow }};"
                    >
                        {{ $education->name }}
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
