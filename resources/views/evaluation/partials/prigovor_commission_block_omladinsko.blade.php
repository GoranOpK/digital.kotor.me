{{-- Isolated Prigovor block for omladinsko. Must not wrap or restyle Obrazac 3. --}}
@php
    $eliminatoryNotice = $eliminatoryNotice ?? ($application->eliminatoryNotice ?? null);
    $prigovor = $prigovor ?? ($application->prigovor ?? null);
    $canDecidePrigovor = $canDecidePrigovor ?? false;
    $eliminatoryCheck = $application->eliminatoryCheck ?? null;
    $profile = \App\Support\EliminatoryProfileConfig::for($application->competition?->type);
    $activatedReasons = $profile->activatedReasonDetails($eliminatoryCheck);
    $submittedExplanations = \App\Support\YouthPrigovorObrazlozenje::parse($prigovor?->obrazlozenje);
    $deadlinePassed = $prigovor?->isFinished()
        ? $prigovor->wasDecidedAfterKomisijaDeadline()
        : $prigovor?->komisijaDeadlineHasPassed();
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
        <p>Komisija je aktivirala jedan ili više eliminatornih kriterijuma. Prijava ostaje podnesena dok traje rok za prigovor ili dok je prigovor neriješen.</p>
        @if(count($activatedReasons) > 0)
            <p><strong>Aktivirani eliminatorni kriterijumi:</strong></p>
            <ul>
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
            Rok za Prigovor: {{ $eliminatoryNotice->prigovorDeadlineAt()->format('d.m.Y. H:i') }}
            (3 dana od slanja obavještenja {{ $eliminatoryNotice->sent_at?->format('d.m.Y. H:i') }}).
        </p>
    @endif

    @if($prigovor)
        <p><strong>Stanje Prigovora:</strong> {{ $prigovor->statusLabel() }}</p>
        <p>Podnesen: {{ $prigovor->submitted_at?->format('d.m.Y. H:i') }}</p>
        <p>Rok Komisije za odluku: {{ $prigovor->komisijaDeadlineAt()->format('d.m.Y. H:i') }} (7 kalendarskih dana od prijema).</p>
        @if($deadlinePassed)
            <p><strong>Rok od 7 dana je prekoračen.</strong> Odluka je i dalje dozvoljena. Platforma ne donosi automatsku odluku.</p>
        @endif

        <p><strong>Aktivirani razlozi Obrasca 3 i osporavanja podnosioca:</strong></p>
        <ul>
            @foreach($activatedReasons as $reason)
                <li>
                    {{ $reason['statement'] }}
                    @if($prigovor->criterionIsContested($reason['number']))
                        — osporen
                        <br>Obrazloženje podnosioca: {{ $submittedExplanations[$reason['number']] }}
                    @else
                        — nije osporen (ostaje)
                    @endif
                </li>
            @endforeach
        </ul>

        @if($prigovor->isFinished())
            <p>
                Odluka Komisije: {{ $prigovor->statusLabel() }}
                @if($prigovor->decided_by_name)
                    — evidentirao u ime Komisije: {{ $prigovor->decided_by_name }}
                @endif
                @if($prigovor->decided_at)
                    ({{ $prigovor->decided_at->format('d.m.Y. H:i') }})
                @endif
            </p>
            @if($prigovor->wasDecidedAfterKomisijaDeadline())
                <p>Odluka je evidentirana nakon isteka roka od 7 dana.</p>
            @endif
            @if($prigovor->decision_note)
                <p><strong>Obrazloženje odluke:</strong></p>
                <p style="white-space: pre-wrap;">{{ $prigovor->decision_note }}</p>
            @endif
            <p>Ishod po aktiviranim razlozima (Obrazac 3 ostaje istorijski nepromijenjen):</p>
            <ul>
                @foreach($activatedReasons as $reason)
                    <li>
                        {{ $reason['statement'] }}
                        — {{ $prigovor->criterionOutcomeLabel($reason['number']) ?? 'Ostaje' }}
                        @if(! $prigovor->criterionIsContested($reason['number']))
                            (nije osporen)
                        @endif
                    </li>
                @endforeach
            </ul>
        @elseif($canDecidePrigovor)
            <form method="POST" action="{{ route('evaluation.prigovor.decide', $application) }}">
                @csrf
                <p>
                    Za svaki osporeni razlog označite <strong>Otklonjen</strong> ili <strong>Ostaje</strong>.
                    Neosporeni aktivirani razlog ostaje. Konačni ishod izračunava Platforma.
                </p>
                @foreach($activatedReasons as $reason)
                    @php $n = $reason['number']; @endphp
                    <div class="kn-prigovor-outcome">
                        <p><strong>{{ $reason['statement'] }}</strong></p>
                        @if($prigovor->criterionIsContested($n))
                            <p>Osporeno. Obrazloženje podnosioca:</p>
                            <p style="white-space: pre-wrap;">{{ $submittedExplanations[$n] }}</p>
                            <div class="kn-prigovor-actions">
                                <label>
                                    <input type="radio"
                                        name="criterion_outcomes[{{ $n }}]"
                                        value="otklonjen"
                                        required
                                        {{ old('criterion_outcomes.'.$n) === 'otklonjen' ? 'checked' : '' }}>
                                    Otklonjen
                                </label>
                                <label>
                                    <input type="radio"
                                        name="criterion_outcomes[{{ $n }}]"
                                        value="ostaje"
                                        required
                                        {{ old('criterion_outcomes.'.$n) === 'ostaje' ? 'checked' : '' }}>
                                    Ostaje
                                </label>
                            </div>
                            @error('criterion_outcomes.'.$n)
                                <p style="color: #ef4444;">{{ $message }}</p>
                            @enderror
                        @else
                            <p>Nije osporen. Ishod: <strong>Ostaje</strong></p>
                        @endif
                    </div>
                @endforeach

                <label for="kn-prigovor-decision-note"><strong>Obrazloženje odluke Komisije</strong></label>
                <textarea id="kn-prigovor-decision-note" name="decision_note" class="kn-prigovor-textarea" required>{{ old('decision_note') }}</textarea>
                @error('decision_note')
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
        <p>Rok za Prigovor je istekao.</p>
    @endif
</div>
@endif
