<!doctype html>
<html lang="nl">
<head><meta charset="utf-8"><title>Nieuwe contactpersoon voorgesteld</title></head>
<body style="font-family: Arial, sans-serif; color: #111827; line-height: 1.6;">
    <p>Beste {{ $recipientName ?: 'relatie' }},</p>
    <p>Voor de inschrijving van <strong>{{ $enrollment->company_name }}</strong> voor <strong>{{ $enrollment->event->name }}</strong> is via de aanvullende informatie een andere contactpersoon voorgesteld: <strong>{{ $proposedName ? $proposedName.' ('.$proposedEmail.')' : $proposedEmail }}</strong>.</p>
    <p>Dat adres moet eerst bevestigd worden. Tot die tijd blijven alle berichten naar jou gaan. Herken je dit niet? Neem dan contact op met de organisatie via info@atixbedrijvendag.nl.</p>
    <p>Met vriendelijke groet,<br>ATIx Bedrijvendag</p>
</body>
</html>
