<!doctype html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <title>Nieuwe interesse van een bedrijf</title>
</head>
<body style="font-family: Arial, sans-serif; color: #111827; line-height: 1.6;">
    <h1 style="font-size: 20px;">Nieuwe interesse van een bedrijf</h1>

    <p>
        <strong>Bedrijf:</strong> {{ $interestRequest->company_name }}<br>
        <strong>Website:</strong>
        @if ($interestRequest->website_url)
            <a href="{{ $interestRequest->website_url }}">{{ $interestRequest->website_url }}</a>
        @else
            Niet opgegeven
        @endif
        <br>
        <strong>Contactpersoon:</strong> {{ $interestRequest->contact_name }}<br>
        <strong>E-mail:</strong> <a href="mailto:{{ $interestRequest->contact_email }}">{{ $interestRequest->contact_email }}</a><br>
        <strong>Editie:</strong> {{ $interestRequest->event?->name ?? 'Geen komende editie gevonden' }}
    </p>

    <p><strong>Bericht:</strong></p>
    <p style="white-space: pre-wrap;">{{ $interestRequest->message ?: 'Geen bericht opgegeven' }}</p>
</body>
</html>
