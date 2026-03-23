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

        html,
        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #ffffff;
        }

        body {
            font-size: 12pt;
        }

        .stand-page {
            position: relative;
            width: 100%;
            height: 277mm;
            page-break-after: always;
            overflow: hidden;
        }

        .stand-page:last-child {
            page-break-after: auto;
        }

        .header {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 30mm;
        }

        .company-logo-top {
            position: absolute;
            top: 4mm;
            left: 0;
            width: 42mm;
            height: 22mm;
            display: flex;
            align-items: center;
            justify-content: flex-start;
        }

        .company-logo-top img {
            max-width: 100%;
            max-height: 22mm;
            object-fit: contain;
        }

        .company-name {
            position: absolute;
            left: 45mm;
            top: 4mm;
            width: 100mm;
            height: 22mm;
            display: table;
            table-layout: fixed;
            overflow: hidden;
        }

        .company-name span {
            display: table-cell;
            width: 100mm;
            height: 22mm;
            vertical-align: middle;
            text-align: center;
            font-weight: 700;
            line-height: 1.1;
            word-break: break-word;
            padding-top: 0.5mm;
        }

        .stand-badge-top {
            position: absolute;
            top: 0;
            right: 0;
            width: 30mm;
            height: 30mm;
            display: flex;
            align-items: center;
            justify-content: flex-end;
        }

        .badge-circle {
            position: relative;
            width: 30mm;
            height: 30mm;
            border-radius: 50%;
            background: #F39C12;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .badge-circle span {
            position: absolute;
            inset: 0;
            display: block;
            width: 30mm;
            height: 30mm;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 56pt;
            font-weight: 900;
            line-height: 30mm;
            text-align: center;
            white-space: nowrap;
            font-variant-numeric: tabular-nums;
            font-feature-settings: "tnum" 1, "lnum" 1;
            padding-top: 1mm;
        }

        .educations {
            position: absolute;
            top: 40mm;
            left: 0;
            right: 0;
            height: 189mm;
        }

        .education-slot {
            position: absolute;
            left: 8mm;
            width: 174mm;
            height: 21mm;
            border-radius: 10mm;
            display: table;
            table-layout: fixed;
            text-align: center;
            font-size: 22pt;
            font-weight: 800;
            color: #ffffff;
            line-height: 1;
            overflow: hidden;
            box-shadow: 0 3mm 6mm rgba(0, 0, 0, 0.16);
        }

        .education-slot span {
            display: table-cell;
            width: 100%;
            height: 21mm;
            vertical-align: middle;
            text-align: center;
            padding: 0 8mm;      /* keep side spacing inside text box */
            padding-top: 0.5mm;  /* small optical adjustment */
        }

        .education-missing {
            position: absolute;
            left: 8mm;
            width: 174mm;
            height: 21mm;
            border-radius: 10mm;
            background: transparent;
            box-shadow: none;
        }

        .footer {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            height: 30mm;
        }

        .stand-badge-bottom {
            position: absolute;
            left: 0;
            top: 2mm;
            width: 26mm;
            height: 26mm;
        }

        .badge-circle-bottom {
            position: relative;
            width: 26mm;
            height: 26mm;
            border-radius: 50%;
            background: #F39C12;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .badge-circle-bottom span {
            position: absolute;
            inset: 0;
            display: block;
            width: 26mm;
            height: 26mm;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 44pt;
            font-weight: 900;
            line-height: 26mm;
            text-align: center;
            white-space: nowrap;
            font-variant-numeric: tabular-nums;
            font-feature-settings: "tnum" 1, "lnum" 1;
            padding-top: 1mm;
        }

        .footer-center-logo {
            position: absolute;
            left: 69mm;
            top: 5mm;
            width: 52mm;
            height: 20mm;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .footer-center-logo img {
            max-width: 100%;
            max-height: 20mm;
            object-fit: contain;
            display: block;
            transform: translateX(1.5mm);
        }

        .company-logo-bottom {
            position: absolute;
            right: 0;
            top: 5mm;
            width: 32mm;
            height: 20mm;
            display: flex;
            align-items: center;
            justify-content: flex-end;
        }

        .company-logo-bottom img {
            max-height: 20mm;
            width: auto;
            height: auto;
            display: block;
            margin-left: auto;
        }

        .debug-box {
            position: absolute;
            border: 0.3mm dashed rgba(255, 0, 0, 0.55);
            pointer-events: none;
        }
        .debug-center-line-horizontal {
            position: absolute;
            height: 0;
            border-top: 0.3mm dashed rgba(0, 0, 255, 0.7);
            pointer-events: none;
        }

        .debug-center-line-vertical {
            position: absolute;
            width: 0;
            border-left: 0.3mm dashed rgba(0, 0, 255, 0.7);
            pointer-events: none;
        }

        .debug-badge-span {
            position: absolute;
            inset: 0;
            border: 0.3mm dashed rgba(0, 180, 0, 0.7);
            pointer-events: none;
        }
        .debug-education-text-box {
            position: absolute;
            inset: 0;
            border: 0.3mm dashed rgba(0, 180, 0, 0.7);
            pointer-events: none;
        }
        .debug-footer-inner-box {
            position: absolute;
            inset: 0;
            border: 0.3mm dashed rgba(0, 180, 0, 0.7);
            pointer-events: none;
        }
    </style>
</head>
<body>

@foreach($stands as $stand)
    @php
        $company = $stand->company;
        $partner = $stand->partner;
        $entity = $company ?? $partner;

        $standNumber = $stand->display_stand_number ?? $stand->stand_number ?? $stand->number ?? '—';

        $logoPath = $company?->logo_path
            ?? $company?->logo
            ?? $partner?->logo_path
            ?? $partner?->logo
            ?? $partner?->image
            ?? null;
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
        $staticLogo = file_exists($staticLogoPath) ? 'file://' . $staticLogoPath : null;

        $allEducations = \App\Models\Education::query()
            ->whereNotNull('name')
            ->orderBy('id')
            ->take(7)
            ->get();

        $companyEducationIds = $company
            ? $company->educations
                ->filter(fn ($education) => filled($education->name))
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all()
            : [];

        $companyName = trim($stand->display_name ?? $entity?->name ?? 'Geen organisatie');
        $companyNameLength = mb_strlen($companyName);

        if ($companyNameLength <= 26) {
            $companyNameFont = '16pt';
        } elseif ($companyNameLength <= 42) {
            $companyNameFont = '13pt';
        } else {
            $companyNameFont = '11pt';
        }

        $displayEducations = collect($stand->display_educations ?? []);

        $slotTopPositions = [
            0,
            28,
            56,
            84,
            112,
            140,
            168,
        ];

        $educationSlots = [];

        foreach ($allEducations as $index => $education) {
            $hasEducation = $displayEducations->isNotEmpty()
                ? $displayEducations->contains(fn ($displayEducation) => (int) data_get($displayEducation, 'id') === (int) $education->id)
                : in_array((int) $education->id, $companyEducationIds, true);
            $backgroundColor = filled($education->color) ? $education->color : '#CCCCCC';
            $normalizedColor = strtoupper($backgroundColor);
            $textColor = '#FFFFFF';

            if ($normalizedColor === '#99FFFF') {
                $textColor = '#1A2A3A';
            }

            $educationSlots[] = [
                'name' => $education->name,
                'has' => $hasEducation,
                'background' => $backgroundColor,
                'text_color' => $textColor,
                'top' => $slotTopPositions[$index] ?? 0,
            ];
        }
    @endphp

    <div class="stand-page">
        <div class="header">
            <div class="company-logo-top">
                @if($companyLogo)
                    <img src="{{ $companyLogo }}" alt="Logo">
                @endif
            </div>

            <div class="company-name" style="font-size: {{ $companyNameFont }};">
                <span>{{ $companyName }}</span>
            </div>

            <div class="stand-badge-top">
                <div class="badge-circle">
                    <span>{{ $standNumber }}</span>

                    @if(request()->boolean('debuglayout'))
                        <div class="debug-badge-span"></div>
                        <div class="debug-center-line-horizontal" style="top: 15mm; left: 0; width: 30mm;"></div>
                        <div class="debug-center-line-vertical" style="left: 15mm; top: 0; height: 30mm;"></div>
                    @endif
                </div>
            </div>

            @if(request()->boolean('debuglayout'))
                {{-- Header box --}}
                <div class="debug-box" style="top: 0; left: 0; right: 0; height: 30mm;"></div>

                {{-- Header center lines --}}
                <div class="debug-center-line-horizontal" style="top: 15mm; left: 0; right: 0;"></div>
                <div class="debug-center-line-vertical" style="left: 50%; top: 0; height: 30mm;"></div>

                {{-- Logo box --}}
                <div class="debug-box" style="top: 4mm; left: 0; width: 42mm; height: 22mm;"></div>

                {{-- Company name box --}}
                <div class="debug-box" style="top: 4mm; left: 45mm; width: 100mm; height: 22mm;"></div>

                {{-- Badge box --}}
                <div class="debug-box" style="top: 0; right: 0; width: 30mm; height: 30mm;"></div>
            @endif
        </div>

        <div class="educations">
            @foreach($educationSlots as $slot)
                @if($slot['has'])
                    <div
                        class="education-slot"
                        style="top: {{ $slot['top'] }}mm; background: {{ $slot['background'] }}; color: {{ $slot['text_color'] }};"
                    >
                        <span>{{ $slot['name'] }}</span>

                        @if(request()->boolean('debuglayout'))
                            <div class="debug-education-text-box"></div>
                            <div class="debug-center-line-horizontal" style="top: 10.5mm; left: 0; right: 0;"></div>
                            <div class="debug-center-line-vertical" style="left: 50%; top: 0; height: 21mm;"></div>
                        @endif
                    </div>
                @else
                    <div class="education-missing" style="top: {{ $slot['top'] }}mm;"></div>
                @endif
            @endforeach
        </div>

        @if(request()->boolean('debuglayout'))
            <div class="debug-box" style="top: 40mm; left: 0; right: 0; height: 189mm;"></div>
            <div class="debug-center-line-horizontal" style="top: 134.5mm; left: 0; right: 0;"></div>
            <div class="debug-center-line-vertical" style="left: 50%; top: 40mm; height: 189mm;"></div>

            @foreach($educationSlots as $slot)
                <div class="debug-box" style="top: {{ 40 + $slot['top'] }}mm; left: 8mm; width: 174mm; height: 21mm;"></div>
            @endforeach
        @endif

        @if(request()->boolean('debuglayout'))
            <div class="debug-box" style="left: 0; right: 0; bottom: 0; height: 30mm;"></div>
            <div class="debug-center-line-horizontal" style="top: 262mm; left: 0; right: 0;"></div>
            <div class="debug-center-line-vertical" style="left: 50%; top: 247mm; height: 30mm;"></div>

            <div class="debug-box" style="left: 0; top: 249mm; width: 26mm; height: 26mm;"></div>
            <div class="debug-box" style="left: 69mm; top: 252mm; width: 52mm; height: 20mm;"></div>
            <div class="debug-box" style="right: 0; top: 252mm; width: 32mm; height: 20mm;"></div>
        @endif

        <div class="footer">
            <div class="stand-badge-bottom">
                <div class="badge-circle-bottom">
                    <span>{{ $standNumber }}</span>

                    @if(request()->boolean('debuglayout'))
                        <div class="debug-footer-inner-box"></div>
                        <div class="debug-center-line-horizontal" style="top: 13mm; left: 0; width: 26mm;"></div>
                        <div class="debug-center-line-vertical" style="left: 13mm; top: 0; height: 26mm;"></div>
                    @endif
                </div>
            </div>

            <div class="footer-center-logo">
                @if($staticLogo)
                    <img src="{{ $staticLogo }}" alt="ATIx Bedrijvendag">
                @endif

                @if(request()->boolean('debuglayout'))
                    <div class="debug-footer-inner-box"></div>
                    <div class="debug-center-line-horizontal" style="top: 10mm; left: 0; width: 52mm;"></div>
                    <div class="debug-center-line-vertical" style="left: 26mm; top: 0; height: 20mm;"></div>
                @endif
            </div>

            <div class="company-logo-bottom">
                @if($companyLogo)
                    <img src="{{ $companyLogo }}" alt="Logo">
                @endif

                @if(request()->boolean('debuglayout'))
                    <div class="debug-footer-inner-box"></div>
                    <div class="debug-center-line-horizontal" style="top: 10mm; left: 0; width: 32mm;"></div>
                    <div class="debug-center-line-vertical" style="left: 16mm; top: 0; height: 20mm;"></div>
                @endif
            </div>
        </div>

    </div>
@endforeach

</body>
</html>
