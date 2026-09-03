<!doctype html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <title>Toegang bedrijfsprofiel</title>
</head>
<body style="font-family: Arial, sans-serif; color: #111827; line-height: 1.6;">
    @php($verificationUrl = $accessRequest->verificationUrl())

    <p>Beste {{ $accessRequest->contact_name }},</p>

    <p>Je aanvraag voor toegang tot het Bedrijvendag bedrijfsprofiel van <strong>{{ $accessRequest->company?->name ?? $accessRequest->company_name }}</strong> is goedgekeurd.</p>

    <p>Via de onderstaande persoonlijke link kunnen jullie de bedrijfsinformatie controleren en wijzigingen insturen:</p>

    <p>
        <a href="{{ $verificationUrl }}" style="color: #ea580c; font-weight: bold;">
            Bedrijfsprofiel controleren
        </a>
    </p>

    <p>Deze link is persoonlijk voor jullie bedrijf. Deel deze alleen met mensen die namens jullie organisatie de bedrijfsinformatie mogen aanpassen.</p>

    <p>
        Werkt de link hierboven niet? Kopieer dan deze volledige link:<br>
        <span style="word-break: break-all;">{{ $verificationUrl }}</span>
    </p>

    <p>Met vriendelijke groet,<br>ATIx Bedrijvendag</p>
</body>
</html>
