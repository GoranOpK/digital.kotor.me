<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <title>Obavještenje o rezultatu provjere potpunosti</title>
</head>
<body style="font-family: Arial, sans-serif; font-size: 14px; color: #111827; line-height: 1.6;">
    <p>Poštovani/a {{ $recipientName }},</p>

    <p>
        Obavještavamo Vas da je Komisija, nakon provjere potpunosti,
        utvrdila da je dokumentacija Vaše prijave <strong>nepotpuna</strong>.
        Podneseni dosije ostaje neizmijenjen.
    </p>

    <p><strong>Podaci o prijavi:</strong></p>
    <ul style="margin-top: 0;">
        <li><strong>Konkurs:</strong> {{ $competitionTitle }}</li>
        <li><strong>Podnosilac:</strong> {{ $frozenApplicantName }}</li>
        <li><strong>Redni broj prijave:</strong> {{ $applicationNumber }}</li>
        <li><strong>Vrsta subvencije:</strong> {{ $subsidyType }}</li>
    </ul>

    @if($reason)
        <p><strong>Obrazloženje Komisije:</strong></p>
        <p style="white-space: pre-wrap; margin-top: 0;">{{ $reason }}</p>
    @endif

    <p>
        Imate pravo da podnesete <strong>jedan</strong> Prigovor Komisiji
        <strong>isključivo putem Platforme</strong>
        u roku od <strong>{{ $complaintWindowDays }} dana</strong> od slanja ovog obavještenja.
        @if($complaintDeadline)
            Rok ističe {{ $complaintDeadline->format('d.m.Y. H:i') }}.
        @endif
    </p>

    <p>
        Vanjski e-mail nije važeći kanal podnošenja Prigovora.
        @if($complaintUrl)
            Prigovor se podnosi na stranici prijave:
            <a href="{{ $complaintUrl }}">{{ $complaintUrl }}</a>
        @endif
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
