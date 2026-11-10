<!doctype html>
<html lang="nl">
<head><meta charset="utf-8"><title>Inschrijving goedgekeurd</title></head>
<body style="font-family: Arial, sans-serif; color: #111827; line-height: 1.6;">
    <p>Beste {{ $enrollment->contact_name ?: 'relatie' }},</p>
    @php($url = route('information.show', $token))
    <p>Goed nieuws: de inschrijving van <strong>{{ $enrollment->company_name }}</strong> voor <strong>{{ $enrollment->event->name }}</strong> is goedgekeurd.</p>
    <p>Via de persoonlijke link hieronder geef je de aanvullende informatie voor de dag door (lunch, borrel en promotiemateriaal):</p>
    <p><a href="{{ $url }}" style="color: #ea580c; font-weight: bold;">Aanvullende informatie invullen</a></p>
    <p>Deze link is persoonlijk voor jullie bedrijf; deel hem alleen met collega's die namens jullie organisatie mogen reageren.</p>
    <p>Werkt de link niet? Kopieer dan deze link:<br><span style="word-break: break-all;">{{ $url }}</span></p>
    <p>Met vriendelijke groet,<br>ATIx Bedrijvendag</p>
</body>
</html>
