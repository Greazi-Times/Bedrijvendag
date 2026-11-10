<!doctype html>
<html lang="nl">
<head><meta charset="utf-8"><title>Update over je inschrijving</title></head>
<body style="font-family: Arial, sans-serif; color: #111827; line-height: 1.6;">
    <p>Beste {{ $enrollment->contact_name ?: 'relatie' }},</p>
    <p>Helaas hebben we de inschrijving van <strong>{{ $enrollment->company_name }}</strong> voor <strong>{{ $enrollment->event->name }}</strong> niet kunnen goedkeuren.</p>
    @if (filled($enrollment->rejection_reason))
        <p><strong>Toelichting van de organisatie:</strong></p>
        <p>{!! nl2br(e($enrollment->rejection_reason)) !!}</p>
    @endif
    <p>Heb je vragen? Neem dan contact met ons op via info@atixbedrijvendag.nl.</p>
    <p>Met vriendelijke groet,<br>ATIx Bedrijvendag</p>
</body>
</html>
