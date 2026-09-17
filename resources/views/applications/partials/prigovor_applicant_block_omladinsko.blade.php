{{-- Isolated applicant Prigovor / notice for omladinsko. Not part of Obrazac 3. --}}
@php
    $notice = $application->eliminatoryNotice;
    $prigovor = $application->prigovor;
    $canSubmitPrigovor = $canSubmitPrigovor ?? false;
    $check = $application->eliminatoryCheck;
    $profile = \App\Support\EliminatoryProfileConfig::for($application->competition?->type);
    $activatedReasons = $profile->activatedReasonDetails($check);
    $submittedExplanations = \App\Support\YouthPrigovorObrazlozenje::parse($prigovor?->obrazlozenje);
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
    .kn-prigovor-applicant-card li,
    .kn-prigovor-applicant-card label {
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
        margin-top: 8px;
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
    .kn-prigovor-criterion {
        margin: 16px 0;
        padding-top: 12px;
        border-top: 1px solid #e5e7eb;
    }
</style>

<div class="kn-prigovor-applicant-card">
    <h2>Obavještenje i Prigovor</h2>

    @if($notice)
        <p>Komisija je aktivirala jedan ili više eliminatornih kriterijuma. Prijava ostaje podnesena dok traje rok za prigovor.</p>
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
            Imate pravo na jedan Prigovor putem Platforme u roku od 3 dana od slanja obavještenja.
            Rok ističe {{ $notice->prigovorDeadlineAt()->format('d.m.Y. H:i') }}.
        </p>
        <p>Vanjski e-mail nije važeći kanal podnošenja Prigovora. Prigovor nije dopuna prijave i njime se ne mogu dodavati, zamjenjivati ni brisati dokumenti, niti mijenjati obrasci ili podaci prijave.</p>
    @endif

    @if($prigovor)
        <p><strong>Stanje Vašeg Prigovora:</strong> {{ $prigovor->statusLabel() }}</p>
        @if($prigovor->submitted_at)
            <p>Podnesen: {{ $prigovor->submitted_at->format('d.m.Y. H:i') }}</p>
        @endif
        <p><strong>Osporeni razlozi i obrazloženja:</strong></p>
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
            <p>Prigovor je podnesen Komisiji.</p>
        @endif
    @elseif($canSubmitPrigovor)
        <form method="POST" action="{{ route('applications.prigovor.store', $application) }}">
            @csrf
            <p><strong>Osporite jedan, više ili sve aktivirane razloge.</strong> Za svaki osporeni razlog unesite obrazloženje.</p>
            @error('contested')
                <p style="color: #ef4444;">{{ $message }}</p>
            @enderror
            @foreach($activatedReasons as $reason)
                @php $n = $reason['number']; @endphp
                <div class="kn-prigovor-criterion">
                    <label>
                        <input type="checkbox" name="contested[]" value="{{ $n }}"
                            {{ in_array((string) $n, array_map('strval', (array) old('contested', [])), true) ? 'checked' : '' }}>
                        Ospori: {{ $reason['statement'] }}
                    </label>
                    <label for="kn-prigovor-obrazlozenje-{{ $n }}"><strong>Obrazloženje</strong></label>
                    <textarea id="kn-prigovor-obrazlozenje-{{ $n }}"
                        name="criterion_obrazlozenja[{{ $n }}]"
                        class="kn-prigovor-textarea">{{ old('criterion_obrazlozenja.'.$n) }}</textarea>
                    @error('criterion_obrazlozenja.'.$n)
                        <p style="color: #ef4444;">{{ $message }}</p>
                    @enderror
                </div>
            @endforeach
            <button type="submit" class="kn-prigovor-submit">Podnesi Prigovor</button>
        </form>
    @elseif($notice && ! $notice->prigovorWindowIsOpen())
        <p>Rok za Prigovor je istekao.</p>
    @endif
</div>
@endif
