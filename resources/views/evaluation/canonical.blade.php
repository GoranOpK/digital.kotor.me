@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6" style="max-width: 1100px;">
    <h1 style="font-size: 24px; font-weight: 700; margin-bottom: 4px;">Bodovanje plana ulaganja (P3)</h1>
    <p style="color: #4b5563; margin-bottom: 16px;">
        {{ $competition->title }} — prijava #{{ $application->redni_broj ?? $application->id }}
        ({{ $application->business_plan_name ?? $application->founder_name }})
    </p>
    <p style="margin-bottom: 12px;"><a href="{{ route('evaluation.canonical-sessions.show', $competition) }}">Sjednice Komisije (intervju, konačna odluka)</a></p>
    <p style="margin-bottom: 12px;"><a href="{{ route('evaluation.canonical-review.index', $competition) }}">Provjera potpunosti i prigovori</a></p>

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

    <p style="color: #374151; margin-bottom: 12px;">
        Skala: 1 = {{ \App\Support\ScoringProfileConfig::SCALE_MIN_LABEL }}; 5 = {{ \App\Support\ScoringProfileConfig::SCALE_MAX_LABEL }}.
        Tokom bodovanja svaki član vidi samo svoje ocjene.
    </p>

    @if($ownScore === null && $scoreBlockReason === null)
        <form method="POST" action="{{ route('evaluation.store', $application) }}">
            @csrf
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 16px;">
                <thead>
                    <tr><th style="text-align: left; padding: 8px;">Kriterijum</th><th style="padding: 8px;">Ocjena (1–5)</th></tr>
                </thead>
                <tbody>
                    @foreach($scoringProfile->criteria as $number => $label)
                        <tr style="border-top: 1px solid #e5e7eb;">
                            <td style="padding: 8px;">{{ $number }}. {{ $label }}</td>
                            <td style="padding: 8px; text-align: center;">
                                <select name="criterion_{{ $number }}" required>
                                    <option value=""></option>
                                    @for($value = 1; $value <= 5; $value++)
                                        <option value="{{ $value }}" @selected((string) old('criterion_'.$number) === (string) $value)>{{ $value }}</option>
                                    @endfor
                                </select>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <label style="display: block; margin-bottom: 8px;">Napomena
                <textarea name="notes" rows="3" style="width: 100%;">{{ old('notes') }}</textarea>
            </label>
            <label style="display: block; margin-bottom: 12px;">
                <input type="checkbox" name="scoring_confirmed" value="1" required>
                Potvrđujem da je ocjena konačna. Nakon potvrde ne može se mijenjati.
            </label>
            <button type="submit" class="btn-primary">Završi bodovanje</button>
        </form>
    @elseif($ownScore === null)
        <div style="background: #fffbeb; border: 1px solid #f59e0b; padding: 12px; border-radius: 8px; margin-bottom: 16px;">{{ $scoreBlockReason }}</div>
    @endif

    @if($ownScore !== null || $rankingFormed)
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 16px;">
            <thead>
                <tr>
                    <th style="text-align: left; padding: 8px;">Kriterijum</th>
                    @foreach($members as $member)
                        <th style="padding: 8px;">{{ $member->id === $commissionMember->id ? 'Moja ocjena' : $member->name }}</th>
                    @endforeach
                    @if($aggregate)
                        <th style="padding: 8px;">Prosječna ocjena</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach($scoringProfile->criteria as $number => $label)
                    <tr style="border-top: 1px solid #e5e7eb;">
                        <td style="padding: 8px;">{{ $number }}. {{ $label }}</td>
                        @foreach($members as $member)
                            <td style="padding: 8px; text-align: center;">{{ $visibleScores->get($member->id)?->{'criterion_'.$number} }}</td>
                        @endforeach
                        @if($aggregate)
                            <td style="padding: 8px; text-align: center;">{{ \App\Support\CompetitionRanking::roundHalfUp($aggregate['criterion_averages'][$number], 2) }}</td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>
        @if($ownScore !== null && ! $rankingFormed)
            <p style="color: #374151;">Vaša ocjena je konačna i zaključana. Zbirni rezultati i ocjene ostalih članova biće vidljivi kada sva 3 člana ocijene sve prijave i formira se preliminarna rang-lista.</p>
        @endif
        @if($aggregate)
            <p style="font-weight: 600;">Konačna ocjena: {{ $aggregate['final_score_display'] }}</p>
        @endif
    @endif

    @if($rankingView['visible'])
        <h2 style="font-size: 20px; font-weight: 700; margin: 24px 0 8px;">Preliminarna rang-lista</h2>
        <p style="color: #4b5563; margin-bottom: 8px;">
            Bez utvrđenih iznosa. Podrška ili odbijanje i iznosi utvrđuju se na trećoj sjednici Komisije.
            Isti rang znači stvarno jednak konačni rezultat; redoslijed unutar istog ranga je tehnički i ne daje prednost.
        </p>
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr><th style="padding: 8px;">Rang</th><th style="text-align: left; padding: 8px;">Prijava</th><th style="padding: 8px;">Bodovi</th><th style="text-align: left; padding: 8px;">Napomena</th></tr>
            </thead>
            <tbody>
                @foreach($rankingView['rows'] as $row)
                    <tr style="border-top: 1px solid #e5e7eb;">
                        <td style="padding: 8px; text-align: center;">{{ $row['rank'] }}@if($row['shared']) (dijeljeno)@endif</td>
                        <td style="padding: 8px;">#{{ $row['application']->redni_broj ?? $row['application']->id }} {{ $row['application']->business_plan_name ?? $row['application']->founder_name }}</td>
                        <td style="padding: 8px; text-align: center;">{{ $row['display'] }}</td>
                        <td style="padding: 8px;">@unless($row['meets_support_threshold']) Ispod praga od 30 bodova (odluka na trećoj sjednici). @endunless</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <p style="margin-top: 12px;">
            <a href="{{ route('evaluation.canonical-outcome.show', $competition) }}" class="btn-primary">Treća sjednica — ishod i iznosi</a>
        </p>
    @endif
</div>
@endsection
