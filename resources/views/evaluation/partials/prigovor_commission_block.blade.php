{{-- Isolated Prigovor block. Must not wrap or restyle Obrazac 3. --}}
@php
    $eliminatoryNotice = $eliminatoryNotice ?? ($application->eliminatoryNotice ?? null);
    $prigovor = $prigovor ?? ($application->prigovor ?? null);
    $canDecidePrigovor = $canDecidePrigovor ?? false;
@endphp

@if($eliminatoryNotice || $prigovor)
<style>
    .kn-prigovor-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 24px;
        margin: 24px auto 0;
        max-width: 1200px;
        box-sizing: border-box;
    }
    .kn-prigovor-card h2 {
        margin: 0 0 12px;
        font-size: 18px;
        font-weight: 700;
        color: #111827;
    }
    .kn-prigovor-card p,
    .kn-prigovor-card li {
        font-size: 14px;
        color: #374151;
        line-height: 1.5;
    }
    .kn-prigovor-card textarea.kn-prigovor-textarea {
        width: 100%;
        min-height: 120px;
        padding: 10px 12px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        font-size: 14px;
        box-sizing: border-box;
    }
    .kn-prigovor-actions {
        margin-top: 16px;
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
    }
    .kn-prigovor-btn {
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

<div class="kn-prigovor-card no-print">
    <h2>Prigovor</h2>

    @if($eliminatoryNotice)
        <p>Prijava je odbijena po eliminatornoj provjeri Obrasca 3.</p>
        @if(!empty($eliminatoryNotice->reasons_snapshot))
            <p><strong>Utvrđeni eliminatorni razlozi:</strong></p>
            <ul>
                @foreach($eliminatoryNotice->reasons_snapshot as $reason)
                    <li>{{ $reason }}</li>
                @endforeach
            </ul>
        @endif
        <p>
            Rok za Prigovor: {{ $eliminatoryNotice->prigovorDeadlineAt()->format('d.m.Y. H:i') }}
            (3 dana od slanja obavještenja {{ $eliminatoryNotice->sent_at?->format('d.m.Y. H:i') }}).
        </p>
    @endif

    @if($prigovor)
        <p><strong>Stanje Prigovora:</strong> {{ $prigovor->statusLabel() }}</p>
        <p><strong>Obrazloženje podnositeljke:</strong></p>
        <p style="white-space: pre-wrap;">{{ $prigovor->obrazlozenje }}</p>
        <p>Podnesen: {{ $prigovor->submitted_at?->format('d.m.Y. H:i') }}</p>
        <p>Rok Komisije za odluku: {{ $prigovor->komisijaDeadlineAt()->format('d.m.Y. H:i') }} (7 dana od prijema).</p>

        @if($prigovor->isFinished())
            <p>
                Odluka: {{ $prigovor->statusLabel() }}
                @if($prigovor->decided_by_name)
                    — evidentirao u ime Komisije: {{ $prigovor->decided_by_name }}
                @endif
                @if($prigovor->decided_at)
                    ({{ $prigovor->decided_at->format('d.m.Y. H:i') }})
                @endif
            </p>
            @if($prigovor->decision_note)
                <p style="white-space: pre-wrap;">{{ $prigovor->decision_note }}</p>
            @endif
        @elseif($canDecidePrigovor)
            <form method="POST" action="{{ route('evaluation.prigovor.decide', $application) }}">
                @csrf
                <label for="kn-prigovor-odluka"><strong>Odluka Komisije</strong></label>
                <div class="kn-prigovor-actions">
                    <label>
                        <input type="radio" name="odluka" value="prihvacen" required>
                        Prihvaćen
                    </label>
                    <label>
                        <input type="radio" name="odluka" value="odbijen" required>
                        Odbijen
                    </label>
                </div>
                <label for="kn-prigovor-decision-note">Napomena odluke (nije obavezna)</label>
                <textarea id="kn-prigovor-decision-note" name="decision_note" class="kn-prigovor-textarea">{{ old('decision_note') }}</textarea>
                @error('odluka')
                    <p style="color: #ef4444;">{{ $message }}</p>
                @enderror
                <div class="kn-prigovor-actions">
                    <button type="submit" class="kn-prigovor-btn">Evidentiraj odluku Komisije</button>
                </div>
            </form>
        @endif
    @elseif($eliminatoryNotice && $eliminatoryNotice->prigovorWindowIsOpen())
        <p>Prigovor još nije podnesen. Rok je otvoren.</p>
    @elseif($eliminatoryNotice)
        <p>Prigovor nije podnesen u roku od 3 dana. Odbijanje po eliminatornoj provjeri je konačno za ovu fazu.</p>
    @endif
</div>
@endif
