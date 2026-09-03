<!doctype html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <title>Bedrijfsprofiel goedgekeurd</title>
</head>
<body style="font-family: Arial, sans-serif; color: #111827; line-height: 1.6;">
    <p>Beste {{ $submission->contact_name ?: 'relatie' }},</p>

    <p>De wijzigingen voor het Bedrijvendag bedrijfsprofiel van <strong>{{ $submission->company?->name ?? $submission->proposed_name }}</strong> zijn goedgekeurd.</p>

    <p>De aangepaste informatie is nu verwerkt op de website.</p>

    @if ($submission->review_note)
        <p><strong>Opmerking van de organisatie:</strong><br>{{ $submission->review_note }}</p>
    @endif

    <p>Met vriendelijke groet,<br>ATIx Bedrijvendag</p>
</body>
</html>
