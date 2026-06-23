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
    @else
        <p>
            Komisija je utvrdila nepotpunost dokumentacije. Molimo Vas da provjerite priloženu dokumentaciju
            u skladu sa uslovima konkursa.
        </p>
    @endif

    @if($chairmanNotes)
        <p><strong>Napomena komisije:</strong></p>
        <p style="margin-top: 0;">{{ $chairmanNotes }}</p>
    @endif

    <p>
        Vaša prijava <strong>neće biti uzeta u dalje razmatranje i ocjenjivanje</strong> po kriterijumima za raspodjelu sredstava.
    </p>

    <p>
        Ukoliko imate prigovor možete ga podnijeti na e-mail adresu
        <a href="mailto:privreda@kotor.me">privreda@kotor.me</a>.
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
