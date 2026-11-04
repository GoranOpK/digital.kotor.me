<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <title>Podnesen je eliminatorni prigovor</title>
</head>
<body style="font-family: Arial, sans-serif; font-size: 14px; color: #111827; line-height: 1.6;">
    <p>Poštovani,</p>

    <p>Podnesen je prigovor na eliminatorno odbijenu prijavu. Prigovor čeka odluku Komisije.</p>

    <ul>
        <li><strong>Konkurs:</strong> {{ $competitionTitle }}</li>
        <li><strong>Broj prijave:</strong> {{ $applicationNumber }}</li>
        <li><strong>Datum i vrijeme podnošenja:</strong> {{ $submittedAtFormatted }}</li>
    </ul>

    @if($reviewUrl)
        <p>
            Prigovor možete otvoriti na Platformi:
            <a href="{{ $reviewUrl }}">{{ $reviewUrl }}</a>
        </p>
    @endif

    <p>Ovaj e-mail je obavještenje. Vanjski e-mail nije kanal za odluku po prigovoru.</p>
</body>
</html>
