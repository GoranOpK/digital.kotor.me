<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <title>Obavještenje o aktiviranim eliminatornim kriterijumima</title>
</head>
<body style="font-family: Arial, sans-serif; font-size: 14px; color: #111827; line-height: 1.6;">
    <p>Poštovani/a {{ $recipientName }},</p>

    <p>
        Obavještavamo Vas da je Komisija, nakon eliminatorne provjere, aktivirala jedan ili više
        eliminatornih kriterijuma. Prijava ostaje podnesena dok traje rok za prigovor.
    </p>

    <p><strong>Podaci o prijavi:</strong></p>
    <ul style="margin-top: 0;">
        <li><strong>Konkurs:</strong> {{ $competitionTitle }}</li>
        <li><strong>Naziv biznis plana:</strong> {{ $businessPlanName }}</li>
        <li><strong>Redni broj prijave:</strong> {{ $applicationNumber }}</li>
    </ul>

    @if(count($activatedReasons) > 0)
        <p><strong>Aktivirani eliminatorni kriterijumi:</strong></p>
        <ul style="margin-top: 0;">
            @foreach($activatedReasons as $reason)
                <li>
                    {{ $reason['statement'] }}
                    @if($reason['explanation'] !== '')
                        <br>Obrazloženje Komisije: {{ $reason['explanation'] }}
                    @endif
                </li>
            @endforeach
        </ul>
    @endif

    <p>
        Imate pravo da podnesete <strong>jedan</strong> Prigovor Komisiji
        <strong>isključivo putem Platforme</strong>
        u roku od <strong>3 dana</strong> od slanja ovog obavještenja.
        Rok ističe {{ $deadlineFormatted }}.
    </p>

    <p>
        Vanjski e-mail nije važeći kanal podnošenja Prigovora.
        Prigovor se podnosi na stranici prijave:
        <a href="{{ $applicationUrl }}">{{ $applicationUrl }}</a>
    </p>

    <p>
        Prigovor nije dopuna prijave. Njime se ne mogu dodavati, zamjenjivati ni brisati dokumenti,
        niti mijenjati obrasci ili podaci prijave.
    </p>

    <p>
        Sa poštovanjem,<br>
        {{ $commissionName }}
    </p>
</body>
</html>
