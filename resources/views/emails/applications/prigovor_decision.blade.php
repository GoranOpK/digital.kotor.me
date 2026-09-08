<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <title>Obavještenje o odluci po Prigovoru</title>
</head>
<body style="font-family: Arial, sans-serif; font-size: 14px; color: #111827; line-height: 1.6;">
    <p>Poštovana {{ $recipientName }},</p>

    <p>
        Obavještavamo Vas da je Komisija donijela odluku po Vašem Prigovoru na eliminatornu provjeru.
        Ishod odluke: <strong>{{ $decisionLabel }}</strong>.
    </p>

    <p>
        Autoritativni zapis odluke, obrazloženje i posljedica za nastavak postupka dostupni su
        isključivo na Platformi:
        <a href="{{ $applicationUrl }}">{{ $applicationUrl }}</a>
    </p>

    <p>
        Ovaj e-mail je obavještenje. Vanjski e-mail nije kanal za podnošenje ili izmjenu Prigovora.
    </p>

    <p>
        Sa poštovanjem,<br>
        Komisija za raspodjelu sredstava za podršku ženskom preduzetništvu
    </p>
</body>
</html>
