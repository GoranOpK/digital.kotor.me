{{-- Isolated Prigovor block. Must not wrap or restyle Obrazac 3. --}}
@php
    $eliminatoryNotice = $eliminatoryNotice ?? ($application->eliminatoryNotice ?? null);
    $prigovor = $prigovor ?? ($application->prigovor ?? null);
    $canDecidePrigovor = $canDecidePrigovor ?? false;
    $eliminatoryCheck = $application->eliminatoryCheck ?? null;
    $originalFailNumbers = [];
    if ($eliminatoryCheck) {
        foreach ([1, 2, 3] as $n) {
            if ($eliminatoryCheck->criterionIsFalse($eliminatoryCheck->{"criterion_{$n}"})) {
                $originalFailNumbers[] = $n;
            }
        }
    }
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
    .kn-prigovor-outcome {
        margin-top: 12px;
        padding-top: 8px;
        border-top: 1px solid #e5e7eb;
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
                <p><strong>Obrazloženje odluke:</strong></p>
                <p style="white-space: pre-wrap;">{{ $prigovor->decision_note }}</p>
            @endif
            @if($prigovor->isAccepted())
                @if($eliminatoryCheck)
                    <p>Ishod Prigovora po originalnim razlozima Ne (Obrazac 3 ostaje istorijski nepromijenjen):</p>
                    <ul>
                        @foreach($originalFailNumbers as $n)
                            <li>
                                {{ \App\Models\ApplicationEliminatoryCheck::CRITERION_LABELS[$n] }}
                                — {{ $prigovor->criterionOutcomeLabel($n) ?? '—' }}
                            </li>
                        @endforeach
                    </ul>
                @endif
                @if($prigovor->liftsEliminatoryBar())
                    <p>Nijedan eliminatorni razlog ne ostaje. Individualno bodovanje je dostupno.</p>
                @else
                    <p>Najmanje jedan eliminatorni razlog ostaje. Individualno bodovanje nije dostupno.</p>
                @endif
            @else
                <p>Prigovor je odbijen. Originalna eliminatorna odluka ostaje. Individualno bodovanje nije dostupno.</p>
            @endif
        @elseif($canDecidePrigovor)
            <form method="POST" action="{{ route('evaluation.prigovor.decide', $application) }}" id="kn-prigovor-decide-form">
                @csrf
                <label for="kn-prigovor-odluka"><strong>Odluka Komisije</strong></label>
                <div class="kn-prigovor-actions">
                    <label>
                        <input type="radio" name="odluka" value="prihvacen" required
                            {{ old('odluka') === 'prihvacen' ? 'checked' : '' }}>
                        Prihvaćen
                    </label>
                    <label>
                        <input type="radio" name="odluka" value="odbijen" required
                            {{ old('odluka') === 'odbijen' ? 'checked' : '' }}>
                        Odbijen
                    </label>
                </div>
                @error('odluka')
                    <p style="color: #ef4444;">{{ $message }}</p>
                @enderror

                <div id="kn-prigovor-outcomes" style="{{ old('odluka') === 'prihvacen' ? '' : 'display: none;' }}">
                    <p>
                        Ako je Prigovor Prihvaćen, za svaki originalni razlog Ne evidentirajte da li je
                        <strong>Otklonjen</strong> ili <strong>Ostaje</strong>.
                        Time se ne mijenja Obrazac 3; Obrazac 3 ostaje istorijski zapis prvobitne provjere.
                    </p>
                    @foreach($originalFailNumbers as $n)
                        <div class="kn-prigovor-outcome">
                            <p><strong>{{ \App\Models\ApplicationEliminatoryCheck::CRITERION_LABELS[$n] }}</strong></p>
                            <div class="kn-prigovor-actions">
                                <label>
                                    <input type="radio"
                                        name="criterion_outcomes[{{ $n }}]"
                                        value="otklonjen"
                                        class="kn-prigovor-outcome-input"
                                        {{ old('criterion_outcomes.'.$n) === 'otklonjen' ? 'checked' : '' }}>
                                    Otklonjen
                                </label>
                                <label>
                                    <input type="radio"
                                        name="criterion_outcomes[{{ $n }}]"
                                        value="ostaje"
                                        class="kn-prigovor-outcome-input"
                                        {{ old('criterion_outcomes.'.$n) === 'ostaje' ? 'checked' : '' }}>
                                    Ostaje
                                </label>
                            </div>
                            @error('criterion_outcomes.'.$n)
                                <p style="color: #ef4444;">{{ $message }}</p>
                            @enderror
                        </div>
                    @endforeach
                </div>

                <label for="kn-prigovor-decision-note"><strong>Obrazloženje odluke</strong></label>
                <textarea id="kn-prigovor-decision-note" name="decision_note" class="kn-prigovor-textarea" required>{{ old('decision_note') }}</textarea>
                @error('decision_note')
                    <p style="color: #ef4444;">{{ $message }}</p>
                @enderror
                <div class="kn-prigovor-actions">
                    <button type="submit" class="kn-prigovor-btn">Evidentiraj odluku Komisije</button>
                </div>
            </form>
            <script>
                (function () {
                    const form = document.getElementById('kn-prigovor-decide-form');
                    if (!form) {
                        return;
                    }
                    const outcomes = document.getElementById('kn-prigovor-outcomes');
                    const outcomeInputs = form.querySelectorAll('.kn-prigovor-outcome-input');

                    function syncOutcomes() {
                        const accepted = form.querySelector('input[name="odluka"]:checked');
                        const show = accepted && accepted.value === 'prihvacen';
                        if (outcomes) {
                            outcomes.style.display = show ? '' : 'none';
                        }
                        outcomeInputs.forEach(function (input) {
                            if (show) {
                                input.setAttribute('required', 'required');
                            } else {
                                input.removeAttribute('required');
                            }
                        });
                    }

                    form.querySelectorAll('input[name="odluka"]').forEach(function (input) {
                        input.addEventListener('change', syncOutcomes);
                    });
                    syncOutcomes();
                })();
            </script>
        @endif
    @elseif($eliminatoryNotice && $eliminatoryNotice->prigovorWindowIsOpen())
        <p>Prigovor još nije podnesen. Rok je otvoren.</p>
    @elseif($eliminatoryNotice)
        <p>Prigovor nije podnesen u roku od 3 dana. Odbijanje po eliminatornoj provjeri je konačno za ovu fazu.</p>
    @endif
</div>
@endif
