{{-- Isolated applicant Prigovor / notice. Not part of Obrazac 3. --}}
@php
    $notice = $application->eliminatoryNotice;
    $prigovor = $application->prigovor;
    $canSubmitPrigovor = $canSubmitPrigovor ?? false;
@endphp

@if($notice || $prigovor)
<style>
    .kn-prigovor-applicant-card {
        background: #fff;
        border-radius: 16px;
        padding: 32px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        margin-bottom: 24px;
        box-sizing: border-box;
    }
    .kn-prigovor-applicant-card h2 {
        font-size: 20px;
        font-weight: 700;
        color: #0B3D91;
        margin: 0 0 20px;
        padding-bottom: 12px;
        border-bottom: 2px solid #e5e7eb;
    }
    .kn-prigovor-applicant-card p,
    .kn-prigovor-applicant-card li {
        font-size: 14px;
        color: #111827;
        line-height: 1.5;
    }
    .kn-prigovor-applicant-card textarea.kn-prigovor-textarea {
        width: 100%;
        min-height: 140px;
        padding: 10px 12px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        font-size: 14px;
        box-sizing: border-box;
    }
    .kn-prigovor-submit {
        margin-top: 16px;
        background: #0B3D91;
        color: #fff;
        padding: 10px 18px;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        font-size: 14px;
    }
</style>

<div class="kn-prigovor-applicant-card">
    <h2>Obavještenje i Prigovor</h2>

    @if($notice)
        @if($canSubmitPrigovor)
            <p><strong>Eliminatorno odbijena — rok za Prigovor</strong></p>
        @endif
        <p>Vaša prijava je odbijena po eliminatornoj provjeri Obrasca 3.</p>
        @if(!empty($notice->reasons_snapshot))
            <p><strong>Utvrđeni eliminatorni razlozi:</strong></p>
            <ul>
                @foreach($notice->reasons_snapshot as $reason)
                    <li>{{ $reason }}</li>
                @endforeach
            </ul>
        @endif
        @if($notice->note_snapshot)
            <p><strong>Napomena Komisije:</strong></p>
            <p style="white-space: pre-wrap;">{{ $notice->note_snapshot }}</p>
        @endif
        <p>
            Imate pravo na Prigovor putem Platforme u roku od 3 dana od slanja obavještenja.
            Rok ističe {{ $notice->prigovorDeadlineAt()->format('d.m.Y. H:i') }}.
        </p>
        <p>Vanjski e-mail nije važeći kanal podnošenja Prigovora. Prigovorom se ne mogu dodavati ni mijenjati dokumenti ni obrasci prijave.</p>
    @endif

    @if($prigovor)
        <p><strong>Stanje Vašeg Prigovora:</strong> {{ $prigovor->statusLabel() }}</p>
        @if($prigovor->submitted_at)
            <p>Podnesen: {{ $prigovor->submitted_at->format('d.m.Y. H:i') }}</p>
        @endif
        <p><strong>Vaše obrazloženje:</strong></p>
        <p style="white-space: pre-wrap;">{{ $prigovor->obrazlozenje }}</p>
        @if($prigovor->isFinished())
            <p>
                Odluka Komisije: {{ $prigovor->statusLabel() }}
                @if($prigovor->decided_at)
                    ({{ $prigovor->decided_at->format('d.m.Y. H:i') }})
                @endif
            </p>
            @if($prigovor->decision_note)
                <p><strong>Obrazloženje odluke:</strong></p>
                <p style="white-space: pre-wrap;">{{ $prigovor->decision_note }}</p>
            @endif
            @if($prigovor->isAccepted() && $prigovor->liftsEliminatoryBar())
                <p>Eliminatorna prepreka je otklonjena. Prijava može nastaviti u individualno bodovanje.</p>
            @elseif($prigovor->isAccepted())
                <p>Prigovor je Prihvaćen, ali ostaje najmanje jedan eliminatorni razlog. Prijava ne nastavlja u bodovanje.</p>
                @if(count($prigovor->remainingReasonLabels()) > 0)
                    <p><strong>Eliminatorni razlozi koji ostaju:</strong></p>
                    <ul>
                        @foreach($prigovor->remainingReasonLabels() as $reason)
                            <li>{{ $reason }}</li>
                        @endforeach
                    </ul>
                @endif
            @elseif($prigovor->isRejected())
                <p>Prigovor je Odbijen. Eliminatorna odluka ostaje. Individualno bodovanje nije dostupno.</p>
            @endif
        @else
            <p>Komisija odlučuje o Prigovoru. Čeka se odluka. Rok Komisije: 7 dana od prijema (do {{ $prigovor->komisijaDeadlineAt()->format('d.m.Y. H:i') }}).</p>
        @endif
    @elseif($canSubmitPrigovor)
        <form method="POST" action="{{ route('applications.prigovor.store', $application) }}">
            @csrf
            <label for="kn-prigovor-obrazlozenje"><strong>Obrazloženje Prigovora</strong></label>
            <textarea id="kn-prigovor-obrazlozenje" name="obrazlozenje" class="kn-prigovor-textarea" required>{{ old('obrazlozenje') }}</textarea>
            @error('obrazlozenje')
                <p style="color: #ef4444;">{{ $message }}</p>
            @enderror
            <button type="submit" class="kn-prigovor-submit">Podnesi Prigovor</button>
        </form>
    @elseif($notice && ! $notice->prigovorWindowIsOpen())
        <p>Rok za Prigovor je istekao. Odbijanje po eliminatornoj provjeri je konačno za ovu fazu.</p>
    @endif
</div>
@endif
