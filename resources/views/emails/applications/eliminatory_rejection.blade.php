<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <title>Obavještenje o odbijanju po eliminatornoj provjeri</title>
</head>
<body style="font-family: Arial, sans-serif; font-size: 14px; color: #111827; line-height: 1.6;">
    <p>Poštovana {{ $recipientName }},</p>

    <p>
        Obavještavamo Vas da je Komisija, nakon eliminatorne provjere Obrasca 3,
        <strong>odbila Vašu prijavu</strong> jer ne ispunjava jedan ili više eliminatornih kriterijuma.
    </p>

    <p><strong>Podaci o prijavi:</strong></p>
    <ul style="margin-top: 0;">
        <li><strong>Konkurs:</strong> {{ $competitionTitle }}</li>
        <li><strong>Naziv biznis plana:</strong> {{ $businessPlanName }}</li>
        <li><strong>Redni broj prijave:</strong> {{ $applicationNumber }}</li>
    </ul>

    @if(count($failedReasons) > 0)
        <p><strong>Utvrđeni eliminatorni razlozi:</strong></p>
        <ul style="margin-top: 0;">
            @foreach($failedReasons as $reason)
                <li>{{ $reason }}</li>
            @endforeach
        </ul>
    @endif

    @if($noteSnapshot)
        <p><strong>Napomena Komisije:</strong></p>
        <p style="white-space: pre-wrap; margin-top: 0;">{{ $noteSnapshot }}</p>
    @endif

    <p>
        Imate pravo da podnesete Prigovor Komisiji <strong>isključivo putem Platforme</strong>
        u roku od <strong>3 dana</strong> od slanja ovog obavještenja.
        Rok ističe {{ $deadlineFormatted }}.
    </p>

    <p>
        Vanjski e-mail nije važeći kanal podnošenja Prigovora.
        Prigovor se podnosi na stranici prijave:
        <a href="{{ $applicationUrl }}">{{ $applicationUrl }}</a>
    </p>

    <p>
        Sa poštovanjem,<br>
        Komisija za raspodjelu sredstava za podršku ženskom preduzetništvu
    </p>
</body>
</html>
