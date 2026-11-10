<!doctype html>
<html lang="nl">
<head><meta charset="utf-8"><title>Inschrijving ontvangen</title></head>
<body style="font-family: Arial, sans-serif; color: #111827; line-height: 1.6;">
    <p>Beste {{ $enrollment->contact_name ?: 'relatie' }},</p>
    <p>Bedankt voor de inschrijving van <strong>{{ $enrollment->company_name }}</strong> voor <strong>{{ $enrollment->event->name }}</strong>. We hebben je aanmelding ontvangen.</p>
    <p>De organisatie beoordeelt je aanmelding. Zodra deze is goedgekeurd, ontvang je een e-mail met een persoonlijke link voor de aanvullende informatie. Tot die tijd hoef je niets te doen.</p>
    <p>Met vriendelijke groet,<br>ATIx Bedrijvendag</p>
</body>
</html>
