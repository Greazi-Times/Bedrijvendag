<html>
<head>
    <meta charset="utf-8">
    <style>

        @page {
            margin: 10mm;
        }

        body {
            font-family: sans-serif;
            position: relative;
        }

        /* Header Logo (company) */
        .company-logo {
            position: absolute;
            top: 20mm;
            left: 20mm;
            max-height: 20mm;
            max-width: 30mm;
        }

        /* Header Stand Number Circle */
        .stand-number-top {
            position: absolute;
            top: 20mm;
            right: 20mm;
            width: 20mm;
            height: 20mm;
            border-radius: 50%;
            background: #c4c4c4;
            color: black;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10mm;
            font-weight: bold;
        }

        /* Centered company name */
        .company-name {
            position: absolute;
            top: 30mm; /* halfway of 20mm circle */
            left: 0;
            width: 100%;
            text-align: center;
            font-size: 12mm;
            font-weight: bold;
            transform: translateY(-50%);
        }

        /* Sector Wrapper */
        .sectors {
            position: absolute;
            top: 60mm;
            left: 20mm;
            width: calc(100% - 40mm);
        }

        /* Each sector */
        .sector-item {
            height: 23mm;
            margin-bottom: 6mm;
            border-radius: 3mm;
            display: table;
            width: 100%;
        }

        .sector-label {
            display: table-cell;
            vertical-align: middle;
            text-align: center;
            font-size: 6mm;
            padding: 0 5mm;
        }

        /* Footer */
        .stand-number-bottom {
            position: absolute;
            bottom: 20mm;
            left: 20mm;
            width: 20mm;
            height: 20mm;
            border-radius: 50%;
            background: #c4c4c4;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10mm;
            font-weight: bold;
        }

        .footer-event-logo {
            position: absolute;
            bottom: 20mm;
            left: 50%;
            transform: translateX(-50%);
            max-height: 18mm;
        }

        .footer-company-logo {
            position: absolute;
            bottom: 20mm;
            right: 20mm;
            max-height: 18mm;
            max-width: 30mm;
        }
    </style>

</head>
<body>


<!-- Header logos + stand number -->
@if($companyLogo)
    <img src="{{ $companyLogo }}" class="company-logo">
@endif

<div class="stand-number-top">{{ $standNumber }}</div>

<div class="company-name">
    {{ $companyName }}
</div>

<!-- Sectors -->
<div class="sectors">
    @foreach($sectorSlots as $slot)
        @php
            $isActive = in_array($slot['name'] ?? '', $sectors);
            $bgColor = $isActive ? ($slot['color'] ?? '#ffffff') : '#ffffff';
        @endphp

        <div class="sector-item" style="background: {{ $bgColor }};">
            <div class="sector-label">
                {{ $slot['name'] ?? '' }}
            </div>
        </div>
    @endforeach
</div>

<!-- Footer -->
<div class="stand-number-bottom">{{ $standNumber }}</div>

@if($eventLogo)
    <img src="{{ $eventLogo }}" class="footer-event-logo">
@endif

@if($companyLogo)
    <img src="{{ $companyLogo }}" class="footer-company-logo">
@endif

</body>
</html>
