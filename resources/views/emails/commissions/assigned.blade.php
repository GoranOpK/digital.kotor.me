<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <title>{{ $copy->htmlTitle() }}</title>
</head>
<body style="font-family: Arial, sans-serif; font-size: 14px; color: #111827;">
    <p>Poštovani/a {{ $member->name }},</p>

    <p>
        Obavještavamo Vas da ste imenovani za člana
        <strong>{{ $copy->assignedCommissionPhrase() }}</strong>
        {{ $copy->assignedCallPhrase() }}
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
        Molimo Vas da se prijavite na {{ $copy->loginSystemPhrase() }}
        kako biste imali uvid u dodijeljene konkurse i prijave.
    </p>

    <p>
        Pristup sistemu: <a href="{{ url('/') }}">{{ url('/') }}</a>
    </p>

    <p>Sa poštovanjem,<br>
        Opština Kotor<br>
        {{ $copy->signatureSystemPhrase() }}
    </p>
</body>
</html>
