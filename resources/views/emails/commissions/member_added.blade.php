<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <title>Imenovanje u Komisiju za podršku ženskom preduzetništvu</title>
</head>
<body style="font-family: Arial, sans-serif; font-size: 14px; color: #111827;">
    <p>Poštovani/a {{ $member->name }},</p>

    @if(!empty($member->is_substitute) && $replacedMember)
        <p>
            Obavještavamo Vas da ste imenovani za <strong>zamjenskog člana</strong>
            <strong>Komisije za raspodjelu sredstava za podršku ženskom preduzetništvu</strong>.
        </p>
        <p>
            <strong>Privremeno mijenjate:</strong> {{ $replacedMember->name }}<br>
            <strong>Uloga:</strong> {{ \App\Models\CommissionMember::replacementSlotLabel((int)($member->replaces_member_number ?? 0)) }}<br>
            <strong>Napomena:</strong> Ovo je privremena zamjena u slučaju opravdanog odsustva redovnog člana komisije.
            Vaša uloga i prava odgovaraju ulozi člana kojeg mijenjate.
        </p>
    @else
        <p>
            Obavještavamo Vas da ste imenovani za člana
            <strong>Komisije za raspodjelu sredstava za podršku ženskom preduzetništvu</strong>.
        </p>
    @endif

    @if(!empty($commission->name))
        <p>
            <strong>Naziv komisije:</strong> {{ $commission->name }}<br>
            <strong>Mandat:</strong>
            @if($commission->start_date)
                {{ $commission->start_date->format('d.m.Y') }}
            @else
                —
            @endif
            –
            @if($commission->end_date)
                {{ $commission->end_date->format('d.m.Y') }}
            @else
                —
            @endif
        </p>
    @endif

    <p>
        Molimo Vas da se prijavite na sistem za podršku ženskom preduzetništvu Opštine Kotor
        kako biste imali uvid u dodijeljene konkurse i prijave.
    </p>

    <p>
        Pristup sistemu: <a href="{{ url('/') }}">{{ url('/') }}</a>
    </p>

    <p>Sa poštovanjem,<br>
        Opština Kotor<br>
        Sistem za podršku ženskom preduzetništvu
    </p>
</body>
</html>
