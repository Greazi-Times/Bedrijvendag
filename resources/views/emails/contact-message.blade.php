<!doctype html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <title>Nieuw bericht via het contactformulier</title>
</head>
<body style="font-family: Arial, sans-serif; color: #111827; line-height: 1.6;">
    <h1 style="font-size: 20px;">Nieuw bericht via het contactformulier</h1>

    <p>
        <strong>Naam:</strong> {{ $contactMessage['name'] }}<br>
        <strong>E-mail:</strong> <a href="mailto:{{ $contactMessage['email'] }}">{{ $contactMessage['email'] }}</a><br>
        <strong>Telefoon:</strong> {{ $contactMessage['phone'] ?: 'Niet opgegeven' }}<br>
        <strong>Onderwerp:</strong> {{ $contactMessage['subject'] }}
    </p>

    <p><strong>Bericht:</strong></p>
    <p style="white-space: pre-wrap;">{{ $contactMessage['message'] }}</p>
</body>
</html>
