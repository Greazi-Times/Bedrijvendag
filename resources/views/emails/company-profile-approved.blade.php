<!doctype html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <title>Bedrijfsprofiel goedgekeurd</title>
</head>
<body style="font-family: Arial, sans-serif; color: #111827; line-height: 1.6;">
    @php($verificationUrl = $submission->company?->profileVerificationUrl())

    <p>Beste {{ $submission->contact_name ?: 'relatie' }},</p>

    <p>De wijzigingen voor het Bedrijvendag bedrijfsprofiel van <strong>{{ $submission->company?->name ?? $submission->proposed_name }}</strong> zijn goedgekeurd.</p>

    <p>De aangepaste informatie is nu verwerkt op de website.</p>

    @if ($verificationUrl)
        <p>Willen jullie later nog iets aanpassen? Gebruik dan jullie persoonlijke bedrijfsprofiellink:</p>

        <p>
            <a href="{{ $verificationUrl }}" style="color: #ea580c; font-weight: bold;">
                Bedrijfsprofiel aanpassen
            </a>
        </p>
    @endif

    @if ($submission->review_note)
        <p><strong>Opmerking van de organisatie:</strong><br>{{ $submission->review_note }}</p>
    @endif

    @if ($verificationUrl)
        <p>
            Werkt de link hierboven niet? Kopieer dan deze volledige link:<br>
            <span style="word-break: break-all;">{{ $verificationUrl }}</span>
        </p>
    @endif

    <p>Met vriendelijke groet,<br>ATIx Bedrijvendag</p>
</body>
</html>
