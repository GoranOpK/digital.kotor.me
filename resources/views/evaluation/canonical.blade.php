@extends('layouts.app')

@section('content')
@include('partials.canonical-ui')
@include('evaluation.partials.mmsp_p3_styles')
@php
    $p3Subject = \App\Support\MmspP3Form::subjectName($application);
    $p3Subsidy = \App\Support\MmspP3Form::subsidyLabel($application);
    $p3Administrative = \App\Support\MmspP3Form::administrativePresentation($application);
    $p3EliminatoryFail = $p3Administrative['recorded']
        && $p3Administrative['result'] === \App\Support\MmspP3Form::RESULT_FAIL
        && ! $p3Administrative['complaint_accepted'];
    $canPrintP3 = $ownScore !== null || $p3EliminatoryFail;
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

        <div class="form-card mmsp-p3-form">
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
                            <tr class="final-score-row" id="mmsp-final-score-row">
                                <td class="criterion-col" style="text-align: center; font-weight: bold !important;">
                                    <strong>UKUPAN ZBIR</strong>
                                </td>
                                @foreach($commissionSeats as $member)
                                    @php
                                        $sumScore = $visibleScores->get($member->id);
                                        $sumIsFinal = $sumScore && $sumScore->completed_at !== null;
                                        $sumValue = $sumIsFinal ? $sumScore->calculateTotalScore() : 0;
                                        $sumIsCurrent = (int) $member->id === (int) $commissionMember->id;
                                        $showSum = $sumIsFinal && $sumValue > 0 && ($sumIsCurrent || $rankingFormed);
                                    @endphp
                                    <td style="font-weight: bold !important;">
                                        @if($showSum)
                                            <strong>{{ $sumValue }}</strong>
                                        @else
                                            <strong style="color: #d1d5db;">—</strong>
                                        @endif
                                    </td>
                                @endforeach
                                <td class="average-col" id="mmsp_final_score" style="font-weight: bold !important;">
                                    @if($rankingFormed && $aggregate && isset($aggregate['final_score_display']))
                                        <strong>{{ $aggregate['final_score_display'] }}</strong>
                                    @else
                                        <strong>—</strong>
                                    @endif
                                </td>
                            </tr>
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
                                        {{ $member->position === 'predsjednik' ? 'Predsjednik' : 'Član mjesta ' . ($noteScore->canonical_seat_no ?? $member->canonical_seat_no ?? '') }}
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
                    @if($canPrintP3)
                        <button type="button" id="mmsp-p3-print" onclick="printMmspSection('p3')" class="btn-primary" style="margin-right: 12px; background: #6b7280;">Štampaj</button>
                    @endif
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

        @include('evaluation.partials.mmsp_p3_complaint')

    </div>
</div>

<script>
    function printMmspSection(section) {
        document.body.classList.remove('mmsp-print-p3', 'mmsp-print-ranking');
        document.body.classList.add(section === 'ranking' ? 'mmsp-print-ranking' : 'mmsp-print-p3');
        const cleanup = function () {
            document.body.classList.remove('mmsp-print-p3', 'mmsp-print-ranking');
            window.removeEventListener('afterprint', cleanup);
        };
        window.addEventListener('afterprint', cleanup);
        window.print();
    }

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
