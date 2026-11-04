<!doctype html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <title>Aanvraag afgewezen</title>
</head>
<body style="font-family: Arial, sans-serif; color: #111827; line-height: 1.6;">
    <p>Beste {{ $accessRequest->contact_name }},</p>

    <p>Je aanvraag voor <strong>{{ $accessRequest->company_name }}</strong> is afgewezen.</p>

    <p>{!! nl2br(e($customMessage)) !!}</p>

    <p>
        <a href="{{ route('company-access.create') }}" style="color: #ea580c; font-weight: bold;">
            Nieuwe toegangsaanvraag indienen
        </a>
    </p>

    <p>Met vriendelijke groet,<br>ATIx Bedrijvendag</p>
</body>
</html>
