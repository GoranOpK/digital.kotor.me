<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <title>Imenovanje u Komisiju za podršku ženskom preduzetništvu</title>
</head>
<body style="font-family: Arial, sans-serif; font-size: 14px; color: #111827;">
    <p>Poštovani/a {{ $member->name }},</p>

    <p>
        Obavještavamo Vas da ste imenovani za člana
        <strong>Komisije za raspodjelu bespovratnih sredstava namijenjenih za podršku ženskom preduzetništvu</strong>
        za sljedeći konkurs:
    </p>

    <p style="margin-left: 16px;">
        <strong>Naziv konkursa:</strong> {{ $competition->title }}<br>
        @if(!empty($competition->competition_number) || !empty($competition->year))
            <strong>Konkurs broj:</strong>
            {{ $competition->competition_number ?? '—' }}{{ !empty($competition->year) ? ' / ' . $competition->year : '' }}<br>
        @endif
        @if(!empty($commission->name))
            <strong>Komisija:</strong> {{ $commission->name }}
        @endif
    </p>

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

