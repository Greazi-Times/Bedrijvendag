<!doctype html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <title>Bedrijfsprofiel vraagt aandacht</title>
</head>
<body style="font-family: Arial, sans-serif; color: #111827; line-height: 1.6;">
    <p>Beste {{ $submission->contact_name ?: 'relatie' }},</p>

    <p>De ingestuurde wijzigingen voor het Bedrijvendag bedrijfsprofiel van <strong>{{ $submission->company?->name ?? $submission->proposed_name }}</strong> zijn nog niet goedgekeurd.</p>

    @if ($submission->review_note)
        <p><strong>Opmerking van de organisatie:</strong><br>{{ $submission->review_note }}</p>
    @else
        <p>Neem contact op met de organisatie als je wilt weten welke aanpassing nodig is.</p>
    @endif

    @if ($submission->company?->profileVerificationUrl())
        <p>Je kunt via jullie persoonlijke link opnieuw wijzigingen insturen:</p>
        <p>
            <a href="{{ $submission->company->profileVerificationUrl() }}" style="color: #ea580c; font-weight: bold;">
                Bedrijfsprofiel aanpassen
            </a>
        </p>
    @endif

    <p>Met vriendelijke groet,<br>ATIx Bedrijvendag</p>
</body>
</html>
