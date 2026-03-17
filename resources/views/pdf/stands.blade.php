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

        .stand-page:last-child {
            page-break-after: auto;
        }

        .stand-inner {
            border: none;
            border-radius: 4mm;
            padding: 6mm 8mm; /* add some inner spacing so content isn't flush to the page edge */
            height: 277mm;
            min-height: 277mm;
            position: relative;
            overflow: hidden;
        }

        .header {
            position: absolute;
            top: 0;
            /* respect the stand-inner horizontal padding (6mm 8mm) */
            left: 8mm;
            right: 8mm;
            height: 28mm;
            min-height: 28mm;
            display: grid;
            grid-template-columns: 40mm 1fr 40mm; /* left logo, center name, right badge (wider side columns) */
            align-items: center;
            gap: 8mm;
            z-index: 20; /* keep header above educations */
        }

        .company-logo-top {
            width: 30mm;
            height: 22mm;
            min-height: 22mm;
            display: flex;
            align-items: center;
            justify-content: flex-start;
            justify-self: start; /* pin to the left cell start */
        }

        .company-logo-top img {
            max-width: 100%;
            height: 22mm; /* explicit height to keep consistent alignment */
            object-fit: contain;
        }

        .company-name {
            /* absolutely center the company name in the page so it visually aligns with reference */
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            font-size: 14pt; /* slightly smaller and more compact to match reference */
            font-weight: 700;
            display: block;
            padding: 0 4mm;
            line-height: 1.05;
            word-break: break-word;
            max-height: 28mm;
            z-index: 25; /* above logo and badge */
            max-width: calc(100% - 96mm); /* leave space for left/right elements (40mm each + gaps) */
            white-space: normal;
            /* limit to two lines */
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .stand-badge-top {
            width: 30mm;
            height: 30mm;
            min-height: 30mm;
            border-radius: 50%;
            background-color: #F39C12;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14pt;
            font-weight: 800;
            color: #ffffff;
            text-shadow: 0 0 8px rgba(0, 0, 0, 0.8);
            justify-self: end; /* pin to the right side of its cell */
        }

        .educations {
            position: absolute;
            top: 64mm; /* push the education stack further down to avoid overlapping header */
            left: 0;
            right: 0;
            bottom: 36mm;
            display: grid;
            grid-template-rows: repeat(7, 40mm); /* taller rows for a more prominent look */
            gap: 18mm; /* larger spacing between blocks to match reference */
            align-items: center;
            justify-items: center;
            align-content: center; /* center the whole column inside the available space */
            overflow: visible;
            padding: 0 18mm; /* wider side padding */
        }

        .education-slot {
            width: 92%;
            height: 40mm; /* larger visual height */
            border-radius: 10mm;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 32pt;
            font-weight: 900;
            color: #FFFFFF;
            /* stronger multi-layer text-shadow + subtle white outline via webkit-text-stroke */
            -webkit-text-stroke: 2px #ffffff;
            text-shadow: -1px -1px 0 #ffffff, 1px -1px 0 #ffffff, -1px 1px 0 #ffffff, 1px 1px 0 #ffffff, 0 10px 18px rgba(0,0,0,0.35);
            box-shadow: 0 10px 18px rgba(0, 0, 0, 0.35);

            text-align: center;
            padding: 0 10mm; /* horizontal padding */
            line-height: 1;
            word-break: break-word;
            overflow: hidden;
        }

        .education-missing {
            background-color: transparent;
            border: none;
            color: transparent;
            box-shadow: none;
            height: 40mm; /* preserve the same height as visible slots */
        }

        .footer {
            position: absolute;
            bottom: 0;
            /* respect the stand-inner horizontal padding */
            left: 8mm;
            right: 8mm;
            height: 28mm;
            min-height: 28mm;
            display: grid;
            grid-template-columns: 30mm 1fr 30mm; /* left badge, center logo, right company logo */
            align-items: center;
            gap: 6mm;
            z-index: 20; /* keep footer above educations */
        }

        .stand-badge-bottom {
            width: 30mm;
            height: 30mm;
            min-height: 30mm;
            border-radius: 50%;
            background-color: #F39C12;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12pt;
            font-weight: 800;
            color: #ffffff;
            text-shadow: 0 0 8px rgba(0, 0, 0, 0.8);
            justify-self: start; /* left cell */
        }

        .footer-center-logo {
            height: 90%;
            min-height: 90%;
            display: flex;
            align-items: center;
            justify-content: center;
            justify-self: center;
        }

        .footer-center-logo img {
            max-height: 100%;
            height: 22mm;
            object-fit: contain;
        }


        .company-logo-bottom {
            width: 30mm;
            height: 22mm;
            min-height: 22mm;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            justify-self: end; /* right cell */
        }

        .company-logo-bottom img {
            max-width: 100%;
            height: 22mm; /* explicit height to match top logo */
            object-fit: contain;
        }

        /* Debug helpers (visible only with ?debuglayout) */
        .debug-overlay {
            position: absolute;
            left: 0;
            top: 0;
            right: 0;
            bottom: 0;
            pointer-events: none;
            z-index: 9999;
        }

        .debug-border {
            position: absolute;
            border: 1px dashed rgba(255,0,0,0.7);
            pointer-events: none;
        }

        .debug-panel {
            position: absolute;
            right: 6mm;
            top: 6mm;
            background: rgba(255,255,255,0.95);
            color: #000;
            font-size: 8pt;
            padding: 6px;
            border-radius: 4px;
            max-width: 80mm;
            z-index: 10000;
            pointer-events: auto;
        }

        .debug-row-overlay {
            position: absolute;
            left: 18mm; /* match education side padding */
            right: 18mm;
            height: 40mm;
            border-radius: 10mm;
            opacity: 0.15;
            pointer-events: none;
            box-shadow: none;
            z-index: 9980;
        }
        /* Lines-only debug helpers (no fill, only outlines) */
        .debug-row-line {
            position: absolute;
            left: 18mm;
            right: 18mm;
            height: 40mm;
            border-radius: 10mm;
            border: 2px dashed rgba(0,0,0,0.35);
            background: transparent;
            pointer-events: none;
            z-index: 9997;
        }

        .debug-center-line {
            position: absolute;
            left: 50%;
            top: 0;
            bottom: 0;
            width: 0;
            border-left: 1px dashed rgba(200,0,0,0.6);
            z-index: 9999;
            pointer-events: none;
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

        // Prepare company display name and an adaptive font-size to avoid overflow
        $companyName = trim($company?->name ?? 'Geen bedrijf');
        $companyNameLength = mb_strlen($companyName);
        if ($companyNameLength <= 30) {
            $companyNameFont = '14pt';
        } elseif ($companyNameLength <= 50) {
            $companyNameFont = '12pt';
        } else {
            $companyNameFont = '10pt';
        }

        // Build a debug array for educations so we can re-use the computed values
        $educationDebug = [];
        foreach ($allEducations as $edu) {
            $has = in_array((int) $edu->id, $companyEducationIds, true);
            $bg = filled($edu->color) ? $edu->color : '#CCCCCC';
            $norm = strtoupper($bg);
            $tc = '#FFFFFF';
            $ts = '-1px -1px 0 #ffffff, 1px -1px 0 #ffffff, -1px 1px 0 #ffffff, 1px 1px 0 #ffffff, 0 6px 10px rgba(0,0,0,0.35)';
            if ($norm === '#99FFFF') {
                $tc = '#1a2a3a';
                $ts = '0 0 8px rgba(255, 255, 255, 0.8)';
            }
            $educationDebug[] = [
                'id' => $edu->id,
                'name' => $edu->name,
                'has' => $has,
                'bg' => $bg,
                'norm' => $norm,
                'tc' => $tc,
                'ts' => $ts,
            ];
        }
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

                <div class="company-name" title="{{ $companyName }}" style="font-size: {{ $companyNameFont }};">
                    {{ $companyName }}
                </div>

                <div class="stand-badge-top">
                    {{ $standNumber }}
                </div>
            </div>

            <div class="educations">
                @foreach($educationDebug as $idx => $debug)
                    @php
                        $rowTop = $idx * (40 + 18) + 0; /* row height + gap; positioned relative inside .educations container */
                    @endphp
                    @if($debug['has'])
                        <div
                            class="education-slot"
                            style="background: linear-gradient(rgba(255,255,255,0.16), rgba(0,0,0,0.06)), {{ $debug['bg'] }}; color: {{ $debug['tc'] }}; text-shadow: {{ $debug['ts'] }}; -webkit-text-stroke: 2px #ffffff;"
                        >
                            {{ $debug['name'] }}
                        </div>
                    @else
                        <div class="education-missing"></div>
                    @endif
                @endforeach
            </div>

            @php $dbgMode = request()->get('debuglayout'); @endphp
            @if($dbgMode === 'lines')
                <div class="debug-overlay">
                    {{-- header/footer outlines only --}}
                    <div class="debug-border" style="top:0; left:8mm; right:8mm; height:28mm; border-color: rgba(0,128,0,0.6);"></div>
                    <div class="debug-border" style="bottom:0; left:8mm; right:8mm; height:28mm; border-color: rgba(0,128,128,0.6);"></div>
                    <div class="debug-center-line"></div>

                    {{-- per-row dashed outlines --}}
                    @foreach($educationDebug as $idx => $debug)
                        @php $rowTopMm = 64 + $idx * (40 + 18); @endphp
                        <div class="debug-row-line" style="top: {{ $rowTopMm }}mm;"></div>
                    @endforeach
                </div>
            @elseif(request()->has('debuglayout'))
                <div class="debug-overlay">
                    {{-- header/f footer bounds --}}
                    <div class="debug-border" style="top:0; left:8mm; right:8mm; height:28mm; border-color: rgba(0,128,0,0.6);"></div>
                    <div class="debug-border" style="bottom:0; left:8mm; right:8mm; height:28mm; border-color: rgba(0,128,128,0.6);"></div>

                    {{-- per-row overlays positioned inside the educations area --}}
                    @foreach($educationDebug as $idx => $debug)
                        @php
                            $rowTopMm = 64 + $idx * (40 + 18); // top offset in mm relative to page
                        @endphp
                        <div class="debug-row-overlay" style="top: {{ $rowTopMm }}mm; background: {{ $debug['bg'] }}; opacity: 0.18; border: 1px solid rgba(0,0,0,0.15);">
                            <div style="position:absolute; left:6mm; top:4mm; font-size:8pt; color:#000; background:rgba(255,255,255,0.85); padding:2px 4px; border-radius:3px;">#{{ $debug['id'] }} {{ $debug['has'] ? 'HAS' : 'MISS' }}</div>
                        </div>
                    @endforeach
                </div>

                <div class="debug-panel">
                    <div><strong>Company</strong>: {{ $companyName }}</div>
                    <div><strong>Font</strong>: {{ $companyNameFont }}</div>
                    <div><strong>Stand #</strong>: {{ $standNumber }}</div>
                    <div><strong>CompanyLogo</strong>: {{ $companyLogo ?? 'N/A' }}</div>
                    <div><strong>StaticLogo</strong>: {{ $staticLogo ?? 'N/A' }}</div>
                    <div style="margin-top:6px"><strong>Educations (debug)</strong>:</div>
                    <pre style="font-size:8pt; max-height:80mm; overflow:auto">{{ json_encode($educationDebug, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                </div>
            @endif

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
