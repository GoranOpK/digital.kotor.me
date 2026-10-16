<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <title>{{ $copy->htmlTitle() }}</title>
</head>
<body style="font-family: Arial, sans-serif; font-size: 14px; color: #111827;">
    <p>Poštovani/a {{ $member->name }},</p>

    @if($copy->isCanonical())
        <p>
            Obavještavamo Vas da je evidentirano Vaše imenovanje za člana
            <strong>{{ $copy->memberAddedCommissionPhrase() }}</strong>{{ $replacedMember ? ' na upražnjeno mjesto' : '' }}.
        </p>
        <p>
            <strong>Uloga:</strong> {{ \App\Services\Competitions\Commission\CanonicalCommissionGovernance::roleLabel($member->canonical_seat) }}<br>
            <strong>Mjesto u Komisiji:</strong> {{ $member->canonical_seat_no }} od 3<br>
            @if($member->external_category)
                <strong>Kategorija spoljašnjeg člana:</strong> {{ \App\Services\Competitions\Commission\CanonicalCommissionGovernance::externalCategoryLabel($member->external_category) }}<br>
            @endif
            @if($member->appointment_reference)
                <strong>Akt o imenovanju:</strong> {{ $member->appointment_reference }}<br>
            @endif
            @if($competition)
                <strong>Poziv:</strong> {{ $competition->title }}
            @endif
        </p>
        <p>
            Prije prve radnje Komisije evidentiraju se Vaša potpisana izjava o povjerljivosti
            i izjava o nepostojanju sukoba interesa.
        </p>
    @elseif($copy->isNeutral())
        @if(!empty($member->is_substitute) && $replacedMember)
            <p>
                Obavještavamo Vas da ste imenovani za <strong>zamjenskog člana</strong>
                Komisije za sprovođenje konkursa.
            </p>
            <p>
                <strong>Privremeno mijenjate:</strong> {{ $replacedMember->name }}<br>
                <strong>Uloga:</strong> {{ \App\Models\CommissionMember::replacementSlotLabel((int)($member->replaces_member_number ?? 0)) }}<br>
                <strong>Napomena:</strong> Ovo je privremena zamjena u slučaju opravdanog odsustva redovnog člana komisije.
                Vaša uloga i prava odgovaraju ulozi člana kojeg mijenjate.
            </p>
        @else
            <p>Obavještavamo Vas da ste imenovani u Komisiju za sprovođenje konkursa.</p>
        @endif
    @elseif(!empty($member->is_substitute) && $replacedMember)
        <p>
            Obavještavamo Vas da ste imenovani za <strong>zamjenskog člana</strong>
            <strong>{{ $copy->memberAddedCommissionPhrase() }}</strong>.
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
            <strong>{{ $copy->memberAddedCommissionPhrase() }}</strong>.
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
