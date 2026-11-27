@extends('layouts.app')

@section('content')
<style>
    .mmsp-ranking-page { max-width: 1180px; margin: 0 auto; }
    .mmsp-ranking-page h1 { font-size: 22px; text-align: center; letter-spacing: .04em; margin: 18px 0 4px; }
    .mmsp-ranking-page h2 { font-size: 16px; text-align: center; font-weight: 600; margin: 0 0 16px; }
    .mmsp-ranking-memo { text-align: center; font-size: 13px; line-height: 1.45; margin-bottom: 8px; }
    .mmsp-ranking-memo img { width: 72px; height: auto; }
    .mmsp-ranking-table { width: 100%; border-collapse: collapse; font-size: 13px; }
    .mmsp-ranking-table th, .mmsp-ranking-table td { border: 1px solid #d1d5db; padding: 8px; vertical-align: top; }
    .mmsp-ranking-table th { background: #f3f4f6; text-align: left; }
    .mmsp-ranking-below { margin-top: 28px; }
    .mmsp-ranking-note { margin-top: 6px; font-size: 12px; color: #6b7280; }
    .mmsp-ranking-block { background: #fff7ed; border: 1px solid #fdba74; border-radius: 8px; padding: 16px; }
    .mmsp-ranking-error { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; border-radius: 8px; padding: 12px; margin-bottom: 16px; }
    .mmsp-ranking-actions { display: flex; gap: 12px; justify-content: center; margin: 24px 0; }
    @media print {
        .mmsp-ranking-actions, .no-print { display: none !important; }
    }
</style>

<div class="mmsp-ranking-page" data-testid="mmsp-ranking-page">
    <div class="no-print" style="margin-bottom: 16px;">
        <a href="{{ route('evaluation.index') }}">← Nazad na ocjenjivanje</a>
    </div>

    @if(session('success'))
        <div style="background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46;border-radius:8px;padding:12px;margin-bottom:16px;">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="mmsp-ranking-error" data-testid="mmsp-ranking-error">{{ $errors->first() }}</div>
    @endif

    @if(! ($rankingView['visible'] ?? false))
        <div class="mmsp-ranking-block" data-testid="mmsp-ranking-blocked">
            <strong>{{ $competition->title }}</strong>
            <p style="margin: 8px 0 0;">{{ $rankingView['block_reason'] ?? \App\Services\CanonicalIndividualScoringService::CANONICAL_CYCLE_INCOMPLETE_MESSAGE }}</p>
        </div>
    @else
        @php
            $supportThreshold = \App\Services\CanonicalIndividualScoringService::CANONICAL_SUPPORT_THRESHOLD;
            $above = [];
            $below = [];
            foreach ($outcomeRows as $row) {
                if (bccomp((string) $row->display, $supportThreshold, 2) >= 0) {
                    $above[] = $row;
                } else {
                    $below[] = $row;
                }
            }
        @endphp

        <div class="mmsp-ranking-memo">
            <img src="{{ asset('images/coat-of-arms.png') }}" alt="Grb">
            <div>OPŠTINA KOTOR</div>
            <div>Sekretarijat za preduzetništvo, razvoj i investicije</div>
        </div>
        <h1>RANG LISTA BIZNIS PLANOVA</h1>
        <h2 data-testid="mmsp-ranking-title">{{ $canonicalRankingIsFinal ? 'Konačna rang-lista' : 'Preliminarna rang-lista' }} — {{ $competition->title }}</h2>
        <p style="color: #4b5563; margin-bottom: 8px;">
            Planovi ispod 30 bodova ostaju na listi i ne mogu biti podržani.
            Isti rang znači stvarno jednak konačni rezultat; redoslijed unutar istog ranga je tehnički i ne daje prednost.
        </p>

        @foreach(['above' => $above, 'below' => $below] as $section => $rows)
            @continue($section === 'below' && $rows === [])
            <div class="{{ $section === 'below' ? 'mmsp-ranking-below' : '' }}">
                @if($section === 'below')
                    <h2>Ispod praga od 30 bodova</h2>
                @endif
                <table class="mmsp-ranking-table">
                    <thead>
                        <tr>
                            <th>Poz.</th>
                            <th>Vrsta subvencije</th>
                            <th>Podnosilac</th>
                            <th>Ocjena /50</th>
                            <th>Potrebna sredstva</th>
                            <th>Odobrena sredstva</th>
                            <th>Odluka</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $row)
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
                            <tr data-testid="mmsp-ranking-row">
                                <td>{{ $row->rank }}</td>
                                <td>{{ $row->subsidyType && \App\Support\CanonicalSubsidyType::isValid($row->subsidyType) ? \App\Support\CanonicalSubsidyType::label($row->subsidyType) : '—' }}</td>
                                <td>{{ $applicantName }}</td>
                                <td>{{ $row->display }}</td>
                                <td>{{ $row->totalInvestment !== null ? number_format((float) $row->totalInvestment, 2, ',', '.') : '—' }}</td>
                                <td>{{ $outcome?->isSupport() ? number_format((float) $outcome->approved_amount, 2, ',', '.') : '—' }}</td>
                                <td>
                                    @if(bccomp((string) $row->display, '30', 2) < 0)
                                        <div class="mmsp-ranking-note">Ispod praga od 30 bodova. Ne može se podržati.</div>
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
            </div>
        @endforeach

        <div class="mmsp-ranking-actions no-print">
            <button type="button" onclick="window.print()" data-testid="mmsp-ranking-print">Štampaj rang-listu</button>
        </div>

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
            @if($canonicalChair)
                <div class="no-print" style="margin: 24px 0 8px 0; text-align: center;">
                    <a href="{{ route('evaluation.mmsp-decision', $competition) }}" data-testid="mmsp-decision-open" class="btn-primary">Generiši Word odluku</a>
                </div>
            @endif
        @endif
    @endif
</div>
@endsection
