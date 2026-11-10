<!doctype html>
<html lang="nl">
<head><meta charset="utf-8"><title>Bevestig je e-mailadres</title></head>
<body style="font-family: Arial, sans-serif; color: #111827; line-height: 1.6;">
    <p>Beste {{ $contactName ?: 'relatie' }},</p>
    @php($url = route('contact.verify', $token))
    <p>Iemand heeft dit e-mailadres opgegeven als contactpersoon voor <strong>{{ $enrollment->company_name }}</strong> bij <strong>{{ $enrollment->event->name }}</strong>.</p>
    <p>Klopt dat? Bevestig dan je e-mailadres. Pas daarna sturen we je berichten over de dag zelf.</p>
    <p><a href="{{ $url }}" style="color: #ea580c; font-weight: bold;">E-mailadres bevestigen</a></p>
    <p>Deze link is {{ \App\Services\ContactVerification::DAYS_VALID }} dagen geldig. Herken je dit niet? Dan hoef je niets te doen; er wordt dan niets gewijzigd.</p>
    <p>Werkt de link niet? Kopieer dan deze link:<br><span style="word-break: break-all;">{{ $url }}</span></p>
    <p>Met vriendelijke groet,<br>ATIx Bedrijvendag</p>
</body>
</html>
