<!doctype html>
<html lang="nl">
<head><meta charset="utf-8"><title>Nieuwe link</title></head>
<body style="font-family: Arial, sans-serif; color: #111827; line-height: 1.6;">
    <p>Beste {{ $enrollment->contact_name ?: 'relatie' }},</p>
    @php($url = route('information.show', $token))
    <p>Hierbij een nieuwe persoonlijke link voor de aanvullende informatie van <strong>{{ $enrollment->company_name }}</strong> voor <strong>{{ $enrollment->event->name }}</strong>. Eerder verstuurde links werken niet meer.</p>
    <p><a href="{{ $url }}" style="color: #ea580c; font-weight: bold;">Aanvullende informatie invullen</a></p>
    <p>Werkt de link niet? Kopieer dan deze link:<br><span style="word-break: break-all;">{{ $url }}</span></p>
    <p>Met vriendelijke groet,<br>ATIx Bedrijvendag</p>
</body>
</html>
