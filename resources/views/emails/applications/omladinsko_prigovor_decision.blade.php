<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <title>Obavještenje o odluci po Prigovoru</title>
</head>
<body style="font-family: Arial, sans-serif; font-size: 14px; color: #111827; line-height: 1.6;">
    <p>Poštovani/a {{ $recipientName }},</p>

    <p>
        Obavještavamo Vas da je Komisija donijela odluku po Vašem Prigovoru.
        Konačni rezultat: <strong>{{ $decisionLabel }}</strong>.
        @if($decidedAtFormatted !== '')
            Datum odluke: {{ $decidedAtFormatted }}.
        @endif
    </p>

    @if($deadlineExceeded)
        <p>Odluka je evidentirana nakon isteka roka od 7 dana. Rok je prekoračen.</p>
    @endif

    @if(count($criterionOutcomes) > 0)
        <p><strong>Ishod po kriterijumima:</strong></p>
        <ul style="margin-top: 0;">
            @foreach($criterionOutcomes as $row)
                <li>
                    {{ $row['statement'] }} — {{ $row['outcome'] }}
                    @if(! $row['contested'])
                        (nije osporen, razlog ostaje)
                    @endif
                </li>
            @endforeach
        </ul>
    @endif

    @if(count($remainingReasons) > 0)
        <p><strong>Razlozi koji ostaju:</strong></p>
        <ul style="margin-top: 0;">
            @foreach($remainingReasons as $reason)
                <li>{{ $reason }}</li>
            @endforeach
        </ul>
    @endif

    @if($decisionNote !== '')
        <p><strong>Obrazloženje Komisije:</strong></p>
        <p style="white-space: pre-wrap;">{{ $decisionNote }}</p>
    @endif

    <p>
        Autoritativni zapis odluke dostupan je isključivo na Platformi:
        <a href="{{ $applicationUrl }}">{{ $applicationUrl }}</a>
    </p>

    <p>
        Ovaj e-mail je obavještenje. Vanjski e-mail nije kanal za podnošenje ili izmjenu Prigovora.
    </p>

    <p>
        Sa poštovanjem,<br>
        {{ $commissionName }}
    </p>
</body>
</html>
