@extends('layouts.app')

@section('content')
@include('partials.canonical-ui')
@include('evaluation.partials.mmsp_p3_styles')
@php
    $p3Subject = \App\Support\MmspP3Form::subjectName($application);
    $p3Subsidy = \App\Support\MmspP3Form::subsidyLabel($application);
    $canEnterScores = $ownScore === null && $scoreBlockReason === null;
    $commissionSeats = $commissionSeats ?? collect([$commissionMember]);
@endphp
<div class="mmsp-p3">
    <div class="container mx-auto px-4">
        <div class="page-header">
            <h1>Ocjenjivanje prijave</h1>
            <p class="subtitle">{{ $p3Subject !== '—' ? $p3Subject : $competition->title }}</p>
        </div>

        @if(session('success'))
            <div class="no-print" role="status" style="background: #ecfdf5; border: 1px solid #10b981; color: #065f46; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px;">
                {{ session('success') }}
            </div>
        @endif
        @if($errors->any())
            <div class="no-print" style="background: #fef2f2; border: 1px solid #ef4444; color: #991b1b; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px;">
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <div class="form-card">
            @include('evaluation.partials.mmsp_p3_memorandum')

            <div class="print-document-header">
                <div class="form-title">{{ \App\Support\MmspP3Form::FORM_TITLE }}</div>
                <div class="form-subtitle">{{ \App\Support\MmspP3Form::FORM_SUBTITLE }}</div>
            </div>

            <div class="print-segment-intro">
                <div class="form-section form-section-compact">
                    <label class="form-label form-label-large">1. Preduzetnik / Društvo:</label>
                    <input type="text" class="form-control form-control-readonly" value="{{ $p3Subject }}" readonly>
                </div>
                <div class="form-section form-section-compact">
                    <label class="form-label form-label-large">2. Vrsta subvencije:</label>
                    <input type="text" class="form-control form-control-readonly" value="{{ $p3Subsidy }}" readonly>
                </div>
                @include('evaluation.partials.mmsp_administrative_review')
            </div>

            @if($scoreBlockReason)
                <div class="no-print" style="background: #fff7ed; border: 1px solid #fdba74; color: #9a3412; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px;">
                    {{ $scoreBlockReason }}
                </div>
            @endif

            @if($ownScore === null)
                <form method="POST" action="{{ route('evaluation.store', $application) }}" id="evaluationForm" data-needs-final-confirm="{{ $canEnterScores ? '1' : '0' }}" data-scoring-allowed="{{ $canEnterScores ? '1' : '0' }}" @if(! $canEnterScores) onsubmit="event.preventDefault(); return false;" @endif>
                    @csrf
                    <input type="hidden" name="scoring_confirmed" value="0">
            @endif

                <div class="form-section form-section-scores">
                    <label class="form-label form-label-large">4. Ocjena plana ulaganja u brojkama:</label>
                    <div class="no-print" style="margin-bottom: 12px; color: #374151; font-size: 13px;">
                        Skala: 1 — {{ \App\Support\ScoringProfileConfig::SCALE_MIN_LABEL }}; 5 — {{ \App\Support\ScoringProfileConfig::SCALE_MAX_LABEL }}. Vrijednosti 2, 3 i 4 su međuvrijednosti.
                    </div>

                    <table class="evaluation-table evaluation-table-criteria" id="mmsp-scoring-list">
                        <thead>
                            <tr>
                                <th class="criterion-col">KRITERIJUMI ZA OCJENU</th>
                                @foreach($commissionSeats as $member)
                                    <th style="font-size: 11px;">
                                        {{ $member->position === 'predsjednik' ? 'Predsjednik' : 'Član ' . $loop->index }}
                                    </th>
                                @endforeach
                                <th class="average-col">Prosječna ocjena*</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($scoringProfile->criteria as $number => $label)
                                <tr>
                                    <td class="criterion-col">
                                        <strong>{{ $number }}.</strong> {{ $label }}
                                    </td>
                                    @foreach($commissionSeats as $member)
                                        @php
                                            $memberScore = $visibleScores->get($member->id);
                                            $currentValue = $memberScore ? $memberScore->{'criterion_'.$number} : null;
                                            $isCurrentMember = (int) $member->id === (int) $commissionMember->id;
                                        @endphp
                                        <td>
                                            @if($isCurrentMember && $ownScore === null)
                                                <input
                                                    type="number"
                                                    name="criterion_{{ $number }}"
                                                    class="score-input"
                                                    min="1"
                                                    max="5"
                                                    value="{{ old('criterion_'.$number) }}"
                                                    @if($canEnterScores) required @endif
                                                    @if(! $canEnterScores) disabled @endif
                                                >
                                            @elseif($memberScore && $currentValue !== null && $currentValue !== '')
                                                <span class="score-display">{{ $currentValue }}</span>
                                            @else
                                                <span class="score-display" style="color: #d1d5db;">—</span>
                                            @endif
                                        </td>
                                    @endforeach
                                    <td class="average-col">
                                        @if($aggregate && isset($aggregate['criterion_averages'][$number]))
                                            {{ \App\Support\CompetitionRanking::roundHalfUp($aggregate['criterion_averages'][$number], 2) }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    @for($i = 1; $i <= 10; $i++)
                        @error('criterion_'.$i)
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                    @endfor

                    @if($ownScore !== null)
                        @foreach($commissionSeats as $member)
                            @php
                                $noteScore = $visibleScores->get($member->id);
                                $noteText = is_string($noteScore?->notes) ? trim($noteScore->notes) : '';
                            @endphp
                            @if($noteText !== '')
                                <div style="margin-top: 16px;">
                                    <div style="font-weight: 600; color: #374151; margin-bottom: 8px;">
                                        {{ $member->position === 'predsjednik' ? 'Predsjednik' : 'Član ' . $loop->index }}
                                    </div>
                                    <div style="padding: 12px; background: #f9fafb; border-radius: 8px; border: 1px solid #e5e7eb; white-space: pre-wrap;">{{ $noteText }}</div>
                                </div>
                            @endif
                        @endforeach
                    @endif
                </div>

                @if($ownScore === null)
                    <div class="form-section form-section-notes">
                        <label class="form-label form-label-large">5. Ostale napomene:</label>
                        <textarea
                            name="notes"
                            class="form-control"
                            rows="6"
                            maxlength="5000"
                            placeholder="Unesite dodatne napomene..."
                            @if(! $canEnterScores) disabled @endif
                        >{{ old('notes') }}</textarea>
                    </div>
                @endif

                <div style="margin-top: 32px; text-align: center;" class="no-print">
                    @if($ownScore !== null)
                        <div style="padding: 16px; background: #ecfdf5; border-radius: 8px; margin-bottom: 16px; border: 1px solid #6ee7b7; color: #065f46; font-weight: 600;">
                            Individualna ocjena je zaključana i ne može se mijenjati.
                        </div>
                        @if(! $rankingFormed)
                            <p style="color: #374151; margin-bottom: 16px;">Vaša ocjena je konačna i zaključana. Zbirni rezultati i ocjene ostalih članova biće vidljivi kada sva 3 člana ocijene sve prijave i formira se preliminarna rang-lista.</p>
                        @endif
                    @endif
                    <button type="button" onclick="window.print();" class="btn-primary" style="margin-right: 12px; background: #6b7280;">Štampaj</button>
                    @if($ownScore === null)
                        <button type="submit" id="mmsp-save-scores" class="btn-primary" @if(! $canEnterScores) disabled @endif>Sačuvaj ocjene</button>
                        <a href="{{ route('evaluation.index') }}" style="margin-left: 12px; color: #6b7280; text-decoration: none;">Otkaži</a>
                        @if($canEnterScores)
                            <p style="margin: 8px auto 0; max-width: 42rem; font-size: 13px; font-weight: 400; color: #6b7280; line-height: 1.5;">Čuvanjem ocjena završavate pojedinačno ocjenjivanje. Nakon potvrde, unesene ocjene više nije moguće mijenjati.</p>
                        @endif
                    @else
                        <a href="{{ route('evaluation.index') }}" class="btn-primary" style="text-decoration: none; display: inline-block;">Nazad na listu</a>
                    @endif
                </div>

            @if($ownScore === null)
                </form>
            @endif
        </div>

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
            <div class="form-card">
                <h2 data-testid="mmsp-ranking-title" style="font-size: 20px; font-weight: 700; margin: 0 0 8px;">{{ $canonicalRankingIsFinal ? 'Konačna rang-lista' : 'Preliminarna rang-lista' }}</h2>
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
            </div>
        @endif
    </div>
</div>

<script>
    function acknowledgeMmspAdministrativeConfirm(form) {
        const anyNo = ['criterion_documentation', 'criterion_report', 'criterion_related'].some(function (name) {
            const checked = form.querySelector('input[name="' + name + '"]:checked');
            return checked && checked.value === 'ne';
        });

        if (anyNo && ! window.confirm(@json(\App\Support\MmspP3Form::FAIL_CONFIRMATION_MESSAGE))) {
            return false;
        }

        return true;
    }

    document.getElementById('evaluationForm')?.addEventListener('submit', function (event) {
        const confirmed = this.querySelector('[name="scoring_confirmed"]');
        const scoringAllowed = this.dataset.scoringAllowed === '1';

        if (! scoringAllowed) {
            event.preventDefault();
            return;
        }

        if (this.dataset.needsFinalConfirm !== '1') {
            return;
        }

        if (! window.confirm(@json(\App\Support\MmspP3Form::SCORE_CONFIRMATION_MESSAGE))) {
            event.preventDefault();
            if (confirmed) {
                confirmed.value = '0';
            }
            return;
        }

        if (confirmed) {
            confirmed.value = '1';
        }
    });
</script>
@endsection
