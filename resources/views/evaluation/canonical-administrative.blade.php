@extends('layouts.app')

@section('content')
@php
    $box = 'border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; margin-bottom: 16px; background: #fff;';
    $warn = 'background: #fffbeb; border: 1px solid #f59e0b; padding: 10px; border-radius: 8px; margin-bottom: 8px;';
    $isCommission = $audience === 'commission';
@endphp
<div class="container mx-auto px-4 py-6" style="max-width: 1000px;">
    <h1 style="font-size: 24px; font-weight: 700; margin-bottom: 4px;">Predlog → Sekretarijat → formalna Odluka → objava → zatvaranje</h1>
    <p style="color: #4b5563; margin-bottom: 16px;">
        {{ $competition->title }}. Interni prikaz.
        @if($competition->isCanonicalTestMode())
            <strong>TESTNI MMSP:</strong> završna „objava“ služi samo za testiranje toka i ne kreira javni rezultat niti javno Obavještenje.
        @else
            Javno se objavljuje isključivo potpisani primjerak formalne Odluke.
        @endif
        Ugovori, isplate i praćenje korisnika nijesu dio ovog toka.
    </p>

    @if(session('success'))
        <div style="background: #ecfdf5; border: 1px solid #10b981; padding: 12px; border-radius: 8px; margin-bottom: 16px;">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div style="background: #fef2f2; border: 1px solid #ef4444; padding: 12px; border-radius: 8px; margin-bottom: 16px;">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div style="{{ $box }}">
        <h2 style="font-size: 18px; font-weight: 600;">1. Predlog</h2>
        @if($predlog === null)
            <p>Predlog još nije pripremljen (potrebni su potvrđen ishod i potpisi 3/3).</p>
        @elseif($predlog->finalized_at)
            <p>Finalizovan {{ $predlog->finalized_at->format('d.m.Y H:i') }}. Otisak: <code>{{ $predlog->fingerprint }}</code></p>
        @else
            <p>Pripremljen {{ $predlog->prepared_at->format('d.m.Y H:i') }}, nije finalizovan.</p>
            @if($finalizeBlock)
                <div style="{{ $warn }}">{{ $finalizeBlock }}</div>
            @elseif($isCommission)
                <form method="POST" action="{{ route('evaluation.canonical-administrative.predlog.finalize', $competition) }}">
                    @csrf
                    <button type="submit" class="btn btn-primary">Finalizuj Predlog</button>
                </form>
            @endif
        @endif
    </div>

    <div style="{{ $box }}">
        <h2 style="font-size: 18px; font-weight: 600;">2. Predaja Sekretarijatu</h2>
        @if($handoff)
            <p>Predato {{ $handoff->handed_off_at->format('d.m.Y H:i') }} — {{ $handoff->recipient }}@if($handoff->reference) ({{ $handoff->reference }})@endif.</p>
        @elseif($handoffBlock)
            <div style="{{ $warn }}">{{ $handoffBlock }}</div>
        @elseif($isCommission)
            <form method="POST" action="{{ route('evaluation.canonical-administrative.handoff', $competition) }}">
                @csrf
                <input type="text" name="recipient" placeholder="Nadležni Sekretarijat" required>
                <input type="text" name="reference" placeholder="Broj akta (opciono)">
                <textarea name="note" placeholder="Napomena (opciono)"></textarea>
                <button type="submit" class="btn btn-primary">Predaj Predlog</button>
            </form>
        @endif
    </div>

    <div style="{{ $box }}">
        <h2 style="font-size: 18px; font-weight: 600;">3. Formalna Odluka i potpisani primjerak</h2>
        @if($decision)
            <p>Odluka br. {{ $decision->decision_number }} od {{ $decision->decision_date->format('d.m.Y') }}.</p>
            <p>Potpisani primjerak: {{ $decision->official_decision_copy_id ? 'priložen' : 'nije priložen' }}.</p>
            @if(! $decision->official_decision_copy_id && ! $isCommission)
                <form method="POST" action="{{ route('admin.competitions.canonical-decision.signed-copy', $competition) }}" enctype="multipart/form-data">
                    @csrf
                    <input type="file" name="signed_copy" accept="application/pdf" required>
                    <button type="submit" class="btn btn-primary">Priloži potpisani PDF</button>
                </form>
            @endif
        @elseif($recordBlock)
            <div style="{{ $warn }}">{{ $recordBlock }}</div>
        @elseif(! $isCommission)
            <form method="POST" action="{{ route('admin.competitions.canonical-decision.record', $competition) }}">
                @csrf
                <input type="text" name="decision_number" placeholder="Broj Odluke" required>
                <input type="date" name="decision_date" required>
                <input type="text" name="issuer" placeholder="Donosilac (opciono)">
                <textarea name="notes" placeholder="Napomena (opciono)"></textarea>
                <button type="submit" class="btn btn-primary">Evidentiraj Odluku</button>
            </form>
        @endif
    </div>

    <div style="{{ $box }}">
        <h2 style="font-size: 18px; font-weight: 600;">4. Objava</h2>
        @if($testPublication)
            <div style="background:#ecfdf5; border:1px solid #10b981; padding:10px; border-radius:8px;">
                TESTNA objava je evidentirana {{ $testPublication->created_at?->format('d.m.Y H:i') }}.
                <strong>Nije kreiran javni rezultat niti javno Obavještenje.</strong>
            </div>
        @elseif($publicResult)
            <p>Objavljeno. <a href="{{ route('competitions.canonical-result', $competition) }}">Javni rezultat</a></p>
        @elseif($publishBlock)
            <div style="{{ $warn }}">{{ $publishBlock }}</div>
        @elseif(! $isCommission)
            <form method="POST" action="{{ route('admin.competitions.canonical-decision.publish', $competition) }}">
                @csrf
                @if($competition->isCanonicalTestMode())
                    <button type="submit" class="btn btn-success">Evidentiraj TESTNU objavu (nije javno)</button>
                @else
                    <button type="submit" class="btn btn-success">Objavi potpisani primjerak (jednokratno)</button>
                @endif
            </form>
        @endif
    </div>

    <div style="{{ $box }}">
        <h2 style="font-size: 18px; font-weight: 600;">5. Zatvaranje i arhiviranje</h2>
        @if(\App\Services\Competitions\Decision\CanonicalAdministrativeGate::isClosed($competition))
            <p>Poziv je zatvoren {{ $competition->closed_at?->format('d.m.Y H:i') }}. Ponovno otvaranje i drugi poziv nijesu mogući.</p>
        @elseif($closeBlock)
            <div style="{{ $warn }}">{{ $closeBlock }}</div>
        @elseif($isCommission)
            <form method="POST" action="{{ route('evaluation.canonical-administrative.close', $competition) }}">
                @csrf
                <button type="submit" class="btn btn-danger">Zatvori Poziv</button>
            </form>
        @endif
    </div>

    <div style="{{ $box }}">
        <h2 style="font-size: 18px; font-weight: 600;">Revizijski trag</h2>
        <ul>
            @forelse($events as $event)
                <li>{{ $event->created_at?->format('d.m.Y H:i') }} — {{ $event->action }} (#{{ $event->subject_id }})</li>
            @empty
                <li>Nema zapisa.</li>
            @endforelse
        </ul>
    </div>
</div>
@endsection
