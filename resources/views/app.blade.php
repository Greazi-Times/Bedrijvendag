<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"  @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Inline script to detect system dark mode preference and apply it immediately --}}
        <script>
            (function() {
                const appearance = '{{ $appearance ?? "system" }}';

                if (appearance === 'system') {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                    if (prefersDark) {
                        document.documentElement.classList.add('dark');
                    }
                }
            })();
        </script>

        {{-- Inline style to set the HTML background color based on our theme in app.css --}}
        <style>
            html {
                background-color: oklch(1 0 0);
            }

            html.dark {
                background-color: oklch(0.145 0 0);
            }
        </style>

        <!-- Basic SEO -->
        <title>Bedrijvendag Avans ATI× - Together, We Design The Future</title>
        <meta name="description" content="Op woensdag 19 november opent de Avans Academie voor Technologie en Innovatie x (ATI×) haar deuren voor de jaarlijkse bedrijvendag. Deze dag staat volledig in het teken van ontmoeting en verbinding tussen bedrijven en studenten. Geen lange presentaties, maar echte gesprekken.">
        <meta name="keywords" content="Bedrijvendag, Avans, ATI×, Avans Hogeschool, bedrijven, studenten, technologie, innovatie, Breda">

        <!-- Open Graph (Facebook, WhatsApp, LinkedIn, Discord) -->
        <meta property="og:title" content="Bedrijvendag Avans ATI× - Together, We Design The Future" />
        <meta property="og:description" content="Op woensdag 19 november opent de Avans Academie voor Technologie en Innovatie x (ATI×) haar deuren voor de jaarlijkse bedrijvendag. Ontmoet bedrijven en studenten, geen lange presentaties maar echte gesprekken." />
        <meta property="og:type" content="website" />
        <meta property="og:url" content="https://atixbedrijvendag.nl/" />
        <meta property="og:image" content="/images/about-us.jpeg" />
        <meta property="og:site_name" content="ATIx Bedrijvendag" />

        <!-- Twitter Card -->
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" content="Bedrijvendag Avans ATI× - Together, We Design The Future" />
        <meta name="twitter:description" content="Een dag vol ontmoetingen tussen bedrijven en studenten. Geen lange presentaties, maar echte gesprekken." />
        <meta name="twitter:image" content="/images/about-us.jpeg" />

        <!-- Favicon -->
        <link rel="icon" href="/favicon.ico">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        @vite(['resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
