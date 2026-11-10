<!doctype html>
<html lang="nl">
<head><meta charset="utf-8"><title>Informatie ontvangen</title></head>
<body style="font-family: Arial, sans-serif; color: #111827; line-height: 1.6;">
    <p>Beste {{ $contactName ?? $enrollment->contact_name ?: 'relatie' }},</p>
    <p>Bedankt, we hebben de aanvullende informatie van <strong>{{ $enrollment->company_name }}</strong> voor <strong>{{ $enrollment->event->name }}</strong> ontvangen.</p>
    @if ($enrollment->event->infoIsEditable())
        <p>Je kunt je antwoorden nog aanpassen via de persoonlijke link uit de eerdere e-mail{{ $enrollment->event->info_closes_at ? ' tot '.$enrollment->event->info_closes_at->format('d-m-Y H:i') : '' }}.</p>
    @endif
    <p>Met vriendelijke groet,<br>ATIx Bedrijvendag</p>
</body>
</html>
