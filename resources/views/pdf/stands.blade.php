<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8" />
    <style>
        @page {
            size: A4 portrait;
            margin: 0;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: sans-serif;
        }

        /* Full page container (A4 = 297mm height) */
        .page {
            width: 210mm;
            height: 297mm;
            position: relative;
            background: #f0f0f0;
        }

        /* Block we measure — intended to be EXACTLY 277mm */
        .calibration-block {
            position: absolute;
            top: 10mm;
            left: 0;
            width: 210mm;
            height: 277mm;
            background: rgba(0, 128, 255, 0.2);
            border: 1mm solid #0077cc;
            box-sizing: border-box;
        }

        /* Header marker (25mm) */
        .header-marker {
            position: absolute;
            top: 0;
            left: 0;
            width: 210mm;
            height: 25mm;
            background: rgba(0, 255, 0, 0.2);
            border-bottom: 0.5mm dashed #00aa00;
            box-sizing: border-box;
        }

        /* Footer marker (25mm) */
        .footer-marker {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 210mm;
            height: 25mm;
            background: rgba(255, 0, 0, 0.2);
            border-top: 0.5mm dashed #aa0000;
            box-sizing: border-box;
        }

        /* Text labels */
        .label {
            position: absolute;
            left: 5mm;
            font-size: 14pt;
            font-weight: bold;
        }

        .label.header {
            top: 5mm;
        }

        .label.footer {
            bottom: 5mm;
        }

        .label.block {
            top: 140mm;
        }
    </style>
</head>
<body>

<div class="page">

    <div class="header-marker"></div>
    <div class="footer-marker"></div>

    <div class="calibration-block"></div>

    <div class="label header">Header: expected 25mm</div>
    <div class="label footer">Footer: expected 25mm</div>
    <div class="label block">Calibration Block: expected 277mm</div>

</div>

</body>
</html>
