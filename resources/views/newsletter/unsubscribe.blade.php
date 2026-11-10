<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ __('messages.unsubscribe_title') }}</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 32rem; margin: 4rem auto; padding: 0 1rem; line-height: 1.5; color: #1a1a1a; }
        button { font: inherit; padding: .6rem 1.2rem; cursor: pointer; }
    </style>
</head>
<body>
    <main>
        <h1>{{ __('messages.unsubscribe_title') }}</h1>
        @if ($done)
            <p role="status">{{ __('messages.unsubscribe_done') }}</p>
        @else
            <p>{{ __('messages.unsubscribe_question') }}</p>
            <form method="post" action="{{ URL::signedRoute('newsletter.unsubscribe.store', ['subscriber' => $subscriber->getKey()]) }}">
                @csrf
                <button type="submit">{{ __('messages.unsubscribe_button') }}</button>
            </form>
        @endif
        <p><a href="/">{{ __('messages.unsubscribe_home') }}</a></p>
    </main>
</body>
</html>
