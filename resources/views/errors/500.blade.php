{{--
    Deliberately standalone Blade (not the Inertia Error page): a 500 often means the
    database, session or build is what failed, so this needs none of them and no JavaScript.
    $appearance and $styles come from AppServiceProvider::configureErrorPages().
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['dark' => $appearance === 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="robots" content="noindex">
        <script>
            if (@js($appearance) === 'system') {
                document.documentElement.classList.toggle('dark', window.matchMedia('(prefers-color-scheme: dark)').matches);
            }
        </script>
        <style>
            html { background-color: #ffffff; }
            html.dark { background-color: #0a0b0f; }
        </style>
        <title>{{ __('messages.errors.500.title') }} - {{ config('app.name') }}</title>
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=geist:400,500,600&display=swap" rel="stylesheet">
        @if ($styles)
            {{ $styles }}
        @else
            <style>
                body { margin: 0; font-family: 'Geist', ui-sans-serif, system-ui, sans-serif; color: #0b0f19; line-height: 1.5; }
                .dark body { color: #f4f5f7; }
                main { max-width: 40rem; margin: 0 auto; padding: 4rem 1.25rem; }
                .btn { display: inline-block; margin: 0.5rem 0.5rem 0 0; padding: 0.625rem 1.125rem; border-radius: 0.5rem; background: #ff6a00; color: #0b0f19; font-weight: 500; text-decoration: none; }
            </style>
        @endif
    </head>
    <body class="font-sans antialiased">
        <main class="hero-ambient flex min-h-[100dvh] items-center">
            <div class="site-container py-16">
                <a href="{{ url('/') }}" class="inline-flex items-center gap-2.5">
                    <img src="/favicon.svg" alt="" width="32" height="32" class="size-8">
                    <span class="text-[0.9375rem] font-semibold tracking-[-0.02em] text-ink" translate="no">ATIx Bedrijvendag</span>
                </a>
                <p class="t-eyebrow mt-14 flex items-center gap-2">
                    <span class="size-2 rounded-full bg-danger" aria-hidden="true"></span>
                    {{ __('messages.errors.eyebrow', ['code' => 500]) }}
                </p>
                <h1 class="t-h1 mt-4 text-ink">{{ __('messages.errors.500.title') }}</h1>
                <p class="t-lead mt-5 max-w-xl">{{ __('messages.errors.500.message') }}</p>
                <div class="mt-8 flex flex-wrap items-center gap-3">
                    <a href="{{ url('/') }}" class="btn btn-primary btn-lg">{{ __('messages.errors.home') }}</a>
                    @if (request()->isMethod('GET'))
                        <a href="{{ request()->fullUrl() }}" class="btn btn-secondary btn-lg">{{ __('messages.errors.retry') }}</a>
                    @endif
                </div>
            </div>
        </main>
    </body>
</html>
