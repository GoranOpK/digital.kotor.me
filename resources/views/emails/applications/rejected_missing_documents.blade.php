<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <title>Obavještenje o nepotpunoj dokumentaciji</title>
</head>
<body style="font-family: Arial, sans-serif; font-size: 14px; color: #111827; line-height: 1.6;">
    <p>Poštovana {{ $recipientName }},</p>

    <p>
        Obavještavamo Vas da je <strong>Komisija za raspodjelu sredstava za podršku ženskom preduzetništvu</strong>
        na administrativnoj provjeri utvrdila da <strong>Vaša prijava nije kompletna</strong> u dijelu priložene dokumentacije,
        u skladu sa Odlukom o podršci ženskom preduzetništvu.
    </p>

    <p><strong>Podaci o prijavi:</strong></p>
    <ul style="margin-top: 0;">
        <li><strong>Konkurs:</strong> {{ $competitionTitle }}</li>
        <li><strong>Naziv biznis plana:</strong> {{ $businessPlanName }}</li>
        <li><strong>Redni broj prijave:</strong> {{ $applicationNumber }}</li>
        <li><strong>Datum podnošenja:</strong> {{ $submittedAtFormatted }}</li>
    </ul>

    @if(count($missingDocumentLabels) > 0)
        <p><strong>Nedostaju sljedeći dokumenti:</strong></p>
        <ol style="margin-top: 0;">
            @foreach($missingDocumentLabels as $label)
                <li>{{ $label }}</li>
            @endforeach
        </ol>
    @endif

    @if($chairmanNotes)
        <p><strong>Napomena komisije:</strong></p>
        <p style="margin-top: 0; margin-bottom: 0;">Komisija je konstatovala da nedostaju sledeći akti:</p>
        <p style="margin-top: 0;">{{ $chairmanNotes }}</p>
    @endif

    <p>
        U skladu sa članom 17, stav 3 Odluke o podršci ženskom preduzetništvu (“Sl.list CG - Opštinski propisi” br.027/26 od 22. 06.2026. god.), Vašu prijavu Komisija neće dalje razmatrati.
    </p>

    <p>
        Imate pravo na Prigovor Komisiji putem e-mail-a:
        <a href="mailto:privreda@kotor.me">privreda@kotor.me</a>
        u roku od tri dana od dana slanja ovoga obavještenja.
    </p>

    <p>
        Pristup Vašoj prijavi:
        <a href="{{ $applicationUrl }}">{{ $applicationUrl }}</a>
    </p>

    <p>
        Sa poštovanjem,<br>
        Komisija za raspodjelu sredstava za podršku ženskom preduzetništvu
    </p>
</body>
</html>
