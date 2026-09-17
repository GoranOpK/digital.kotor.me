{{-- Isolated Prigovor block for omladinsko. Must not wrap or restyle Obrazac 3. --}}
@php
    $eliminatoryNotice = $eliminatoryNotice ?? ($application->eliminatoryNotice ?? null);
    $prigovor = $prigovor ?? ($application->prigovor ?? null);
    $eliminatoryCheck = $application->eliminatoryCheck ?? null;
    $profile = \App\Support\EliminatoryProfileConfig::for($application->competition?->type);
    $activatedReasons = $profile->activatedReasonDetails($eliminatoryCheck);
    $submittedExplanations = \App\Support\YouthPrigovorObrazlozenje::parse($prigovor?->obrazlozenje);
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
        <p><strong>Osporeni razlozi i obrazloženja podnosioca:</strong></p>
        <ul>
            @foreach($activatedReasons as $reason)
                @if($prigovor->criterionIsContested($reason['number']))
                    <li>
                        {{ $reason['statement'] }}
                        <br>{{ $submittedExplanations[$reason['number']] }}
                    </li>
                @endif
            @endforeach
        </ul>
        @if($prigovor->isPodnesen())
            <p>Prigovor je podnesen. Odluka Komisije nije dostupna u ovom koraku.</p>
        @endif
    @elseif($eliminatoryNotice && $eliminatoryNotice->prigovorWindowIsOpen())
        <p>Prigovor još nije podnesen. Rok je otvoren.</p>
    @elseif($eliminatoryNotice)
        <p>Rok za Prigovor je istekao.</p>
    @endif
</div>
@endif
