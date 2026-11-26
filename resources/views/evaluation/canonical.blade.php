@extends('layouts.app')

@section('content')
@include('partials.canonical-ui')
<div class="container mx-auto px-4 py-6 canonical-ui-page" style="max-width: 1100px;">
    <h1 style="font-size: 24px; font-weight: 700; margin-bottom: 4px;">Bodovanje plana ulaganja (P3)</h1>
    <p style="color: #4b5563; margin-bottom: 16px;">
        {{ $competition->title }} — prijava #{{ $application->redni_broj ?? $application->id }}
        ({{ $application->business_plan_name ?? $application->founder_name }})
    </p>
    @include('evaluation.partials.mmsp_administrative_review')

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
        @php
            $canonicalFinalRanking = \App\Models\CanonicalFinalRanking::query()->where('competition_id', $competition->id)->first();
            $canonicalRankingIsFinal = $canonicalFinalRanking !== null;
            $canonicalChair = ($commissionMember->canonical_seat ?? null) === \App\Services\Competitions\Commission\CanonicalCommissionGovernance::ROLE_PREDSJEDNIK
                && ($commissionMember->position ?? null) === 'predsjednik'
                && ($commissionMember->status ?? null) === 'active';
            $outcomeRows = app(\App\Services\Competitions\Outcome\CanonicalOutcomeService::class)->rankedRows($competition);
            $outcomeRecords = app(\App\Services\Competitions\Outcome\CanonicalOutcomeService::class)->outcomesFor($competition);
            $reasonLabels = \App\Models\CanonicalOutcome::REASON_LABELS;
            $signatories = \App\Models\CommissionMember::query()
                ->where('commission_id', $competition->commission_id)
                ->where('status', 'active')
                ->orderBy('canonical_seat_no')
                ->get();
        @endphp
        <h2 data-testid="mmsp-ranking-title" style="font-size: 20px; font-weight: 700; margin: 24px 0 8px;">{{ $canonicalRankingIsFinal ? 'Konačna rang-lista' : 'Preliminarna rang-lista' }}</h2>
        <p style="color: #4b5563; margin-bottom: 8px;">
            Planovi ispod 30 bodova ostaju na listi i ne mogu biti podržani.
            Isti rang znači stvarno jednak konačni rezultat; redoslijed unutar istog ranga je tehnički i ne daje prednost.
        </p>
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr>
                    <th style="padding: 8px;">Rang</th>
                    <th style="text-align: left; padding: 8px;">Podnosilac</th>
                    <th style="text-align: left; padding: 8px;">Vrsta subvencije</th>
                    <th style="padding: 8px;">Bodovi</th>
                    <th style="padding: 8px;">Potrebna sredstva</th>
                    <th style="padding: 8px;">Odobrena sredstva</th>
                    <th style="text-align: left; padding: 8px;">Odluka</th>
                </tr>
            </thead>
            <tbody>
                @foreach($outcomeRows as $row)
                    @php
                        $outcome = $outcomeRecords->get($row->id());
                        $applicantForm = $row->application->canonicalApplicantForm;
                        $applicantName = $applicantForm?->legal_form === \App\Support\CanonicalLegalForm::DRUSTVO
                            ? trim((string) $applicantForm->company_name)
                            : trim((string) ($applicantForm->person_name ?? ''));
                        if ($applicantName === '') {
                            $applicantName = $row->application->founder_name ?? '—';
                        }
                    @endphp
                    <tr style="border-top: 1px solid #e5e7eb; vertical-align: top;">
                        <td style="padding: 8px; text-align: center;">{{ $row->rank }}</td>
                        <td style="padding: 8px;">{{ $applicantName }}</td>
                        <td style="padding: 8px;">{{ $row->subsidyType && \App\Support\CanonicalSubsidyType::isValid($row->subsidyType) ? \App\Support\CanonicalSubsidyType::label($row->subsidyType) : '—' }}</td>
                        <td style="padding: 8px; text-align: center;">{{ $row->display }}</td>
                        <td style="padding: 8px; text-align: right;">{{ $row->totalInvestment !== null ? number_format((float) $row->totalInvestment, 2, ',', '.') : '—' }}</td>
                        <td style="padding: 8px; text-align: right;">{{ $outcome?->isSupport() ? number_format((float) $outcome->approved_amount, 2, ',', '.') : '—' }}</td>
                        <td style="padding: 8px;">
                            @if(bccomp((string) $row->display, '30', 2) < 0)
                                <div>Ispod praga od 30 bodova. Ne može se podržati.</div>
                            @endif
                            @if($outcome)
                                <div><strong>{{ $outcome->isSupport() ? 'Podržava' : 'Odbija' }}</strong></div>
                                @if(filled($outcome->justification))<div style="white-space: pre-wrap;">{{ $outcome->justification }}</div>@endif
                                @if(filled($outcome->remarks))<div style="white-space: pre-wrap;">{{ $outcome->remarks }}</div>@endif
                            @endif
                            @if($canonicalChair && ! $canonicalRankingIsFinal)
                                <form method="POST" action="{{ route('evaluation.canonical-outcome.draft', [$competition, $row->application]) }}" style="margin-top: 6px;">
                                    @csrf
                                    <select name="decision" required>
                                        <option value="support" @selected($outcome?->decision === 'support')>Podržava</option>
                                        <option value="reject" @selected($outcome?->decision === 'reject')>Odbija</option>
                                    </select>
                                    <input type="text" name="amount" inputmode="decimal" placeholder="Iznos €" value="{{ $outcome?->approved_amount }}" style="width: 110px;">
                                    <select name="reason">
                                        <option value="">— razlog odbijanja —</option>
                                        @foreach($reasonLabels as $code => $label)
                                            <option value="{{ $code }}" @selected($outcome?->rejection_reason === $code)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <textarea name="justification" rows="2" placeholder="Obrazloženje" style="width: 100%;">{{ $outcome?->justification }}</textarea>
                                    <textarea name="remarks" rows="1" placeholder="Napomene i mišljenja" style="width: 100%;">{{ $outcome?->remarks }}</textarea>
                                    <button type="submit" class="btn-secondary">Evidentiraj</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        @if($canonicalRankingIsFinal)
            <div id="signature-block" data-testid="mmsp-ranking-signatures" style="margin-top: 16px;">
                <h3 style="font-size: 18px; margin-bottom: 12px;">Potpis komisije</h3>
                <p style="margin-bottom: 14px;">Kotor, _______________ god.</p>
                @php $clanNum = 0; @endphp
                @foreach($signatories as $member)
                    <div style="margin-bottom: 6px;">
                        <div style="font-weight: 600;">{{ $member->position === 'predsjednik' ? 'Predsjednik Komisije' : 'Član '.(++$clanNum) }}</div>
                        <div>{{ $member->name }}</div>
                        <div style="width: 200px; border-bottom: 1px solid #111; margin-top: 8px;"></div>
                    </div>
                @endforeach
            </div>
            <div class="no-print" style="margin: 24px 0 8px 0; text-align: center;">
                <button type="button" onclick="window.print()" data-testid="mmsp-ranking-print">Štampaj</button>
                @if($canonicalChair)
                    <a href="{{ route('evaluation.mmsp-decision', $competition) }}" data-testid="mmsp-decision-open" class="btn-primary" style="margin-left: 8px;">Generiši Word odluku</a>
                @endif
            </div>
        @endif
    @endif
</div>
@endsection
