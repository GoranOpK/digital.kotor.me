@php
    $youthRankingView = $youthRankingView ?? null;
    $youthRankingTitle = $youthRankingTitle ?? null;
    $canEditYouthAllocationDraft = $canEditYouthAllocationDraft ?? false;
    $youthAboveFirst = is_array($youthRankingView) ? ($youthRankingView['above'][0] ?? null) : null;
    $youthBelowFirst = is_array($youthRankingView) ? ($youthRankingView['below'][0] ?? null) : null;
    $youthRankingCompetition = $youthEqualScoreCompetition
        ?? $youthAboveFirst?->competition
        ?? $youthBelowFirst?->competition;
    $youthRankingCompetition?->loadMissing('upNumber');
    $youthApplicantTypeLabel = static function ($application): string {
        $commercialForm = null;
        foreach ([
            $application->company_legal_form ?? null,
            \App\Support\KnCommercialCompanyForm::fromRegistrationForm($application->registration_form ?? null),
            \App\Support\KnCommercialCompanyForm::fromUserType($application->registration_form ?? null),
            is_string($application->registration_form ?? null) ? strtolower((string) $application->registration_form) : null,
        ] as $candidate) {
            if (! is_string($candidate) || $candidate === '') {
                continue;
            }
            $label = \App\Support\KnCommercialCompanyForm::headingCode(strtolower($candidate));
            if ($label !== null) {
                $commercialForm = $label;
                break;
            }
        }

        return match ((string) ($application->applicant_type ?? '')) {
            'preduzetnik' => 'Preduzetnik',
            'fizicko_lice' => 'Fizičko lice',
            'doo' => 'DOO',
            'privredno_drustvo' => $commercialForm ?? 'Privredno društvo',
            'ostalo' => $commercialForm ?? 'Ostalo',
            default => 'Ostalo',
        };
    };
@endphp
@if($youthRankingView)
<style>
    .youth-ranking-print-root {
        --youth-ranking-primary: #0B3D91;
        margin-bottom: 24px;
    }
    .youth-ranking-print-root.is-blocked {
        background: #fff;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        border-left: 4px solid #8b5cf6;
    }
    .youth-ranking-list-card {
        background: #fff;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    .youth-ranking-print-root .obrazac-zaglavlje {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 20px 24px;
        margin-bottom: 24px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.06);
    }
    .youth-ranking-print-root .obrazac-zaglavlje-top {
        display: flex;
        align-items: flex-start;
        gap: 20px;
    }
    .youth-ranking-print-root .obrazac-grb {
        flex-shrink: 0;
        line-height: 0;
    }
    .youth-ranking-print-root .obrazac-grb img {
        height: 2cm;
        width: auto;
        display: block;
    }
    .youth-ranking-print-root .obrazac-org {
        flex: 1;
        font-size: 13px;
        line-height: 1.5;
        color: #111;
    }
    .youth-ranking-print-root .obrazac-org p { margin: 0 0 2px 0; }
    .youth-ranking-print-root .obrazac-contact {
        text-align: right;
        font-size: 13px;
        line-height: 1.5;
        color: #111;
    }
    .youth-ranking-print-root .obrazac-contact p { margin: 0 0 2px 0; }
    .youth-ranking-print-root .obrazac-broj-i-naslov {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
        margin-top: 12px;
        padding-top: 12px;
        border-top: 1px solid #e5e7eb;
    }
    .youth-ranking-print-root .obrazac-broj-prijave {
        font-size: 13px;
        color: #374151;
        font-weight: 500;
    }
    .youth-ranking-print-root .obrazac-naslov-prijava {
        width: 100%;
        text-align: center;
        font-size: 22px;
        font-weight: 700;
        letter-spacing: 0.02em;
        color: #111;
        margin: 8px 0 0 0;
        text-transform: uppercase;
    }
    .youth-ranking-print-root .obrazac-podnaslov {
        text-align: center;
        font-size: 13px;
        line-height: 1.5;
        color: #374151;
        margin: 4px 0 0 0;
    }
    .youth-ranking-list-card > h2 {
        font-size: 20px;
        font-weight: 700;
        color: var(--youth-ranking-primary);
        margin: 0 0 20px;
        padding-bottom: 12px;
        border-bottom: 2px solid #e5e7eb;
    }
    .youth-ranking-print-root .btn-primary {
        background: var(--youth-ranking-primary);
        color: #fff;
        padding: 10px 16px;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
    }
    .youth-ranking-print-root .error-message {
        color: #ef4444;
        font-size: 12px;
        margin-top: 4px;
    }
    .youth-ranking-print-root .youth-allocation-draft-form input[type="text"],
    .youth-ranking-print-root .youth-allocation-draft-form input[type="number"],
    .youth-ranking-print-root .youth-allocation-draft-form select {
        background: #ffffff;
        color: #111827;
        border: 1px solid #9ca3af;
        border-radius: 6px;
        padding: 8px 10px;
        font-size: 14px;
        line-height: 1.4;
        box-sizing: border-box;
    }
    .youth-ranking-print-root .youth-allocation-draft-form input[type="text"]:focus,
    .youth-ranking-print-root .youth-allocation-draft-form input[type="number"]:focus,
    .youth-ranking-print-root .youth-allocation-draft-form select:focus {
        outline: 2px solid #2563eb;
        outline-offset: 1px;
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.25);
    }
    .youth-ranking-print-root .youth-allocation-draft-form .youth-allocation-decision-row {
        display: flex;
        flex-wrap: nowrap;
        align-items: flex-end;
        gap: 12px;
        width: 100%;
        min-width: 0;
    }
    .youth-ranking-print-root .youth-allocation-decision-choices {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 12px;
        flex: 0 0 auto;
        min-height: 38px;
    }
    .youth-ranking-print-root .youth-allocation-choice {
        display: flex;
        align-items: center;
        gap: 6px;
        margin: 0;
        white-space: nowrap;
    }
    .youth-ranking-print-root .youth-allocation-field {
        display: flex;
        flex-direction: column;
        justify-content: flex-end;
        gap: 4px;
        margin: 0;
        min-width: 0;
    }
    .youth-ranking-print-root .youth-allocation-field-amount {
        flex: 0 0 140px;
        width: 140px;
    }
    .youth-ranking-print-root .youth-allocation-field-justification {
        flex: 1 1 auto;
    }
    .youth-ranking-print-root .youth-allocation-field input[type="text"],
    .youth-ranking-print-root .youth-allocation-field input[type="number"] {
        width: 100%;
        margin: 0;
    }
    .youth-ranking-print-root .youth-allocation-decision-row .btn-primary {
        flex: 0 0 auto;
        white-space: nowrap;
    }
    .youth-ranking-table-wrap {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    .youth-ranking-print-root table {
        width: 100%;
        min-width: 640px;
        border-collapse: collapse;
    }
    .youth-ranking-print-root th,
    .youth-ranking-print-root td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid #e5e7eb;
    }
    .youth-ranking-print-root th {
        font-weight: 600;
        color: #374151;
        font-size: 12px;
        text-transform: uppercase;
        background: #f9fafb;
    }
    .youth-ranking-print-root .ranking-badge {
        display: inline-block;
        width: 32px;
        height: 32px;
        background: var(--youth-ranking-primary);
        color: #fff;
        border-radius: 50%;
        text-align: center;
        line-height: 32px;
        font-weight: 700;
        font-size: 14px;
    }
    .youth-ranking-print-root .score-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 9999px;
        font-size: 14px;
        font-weight: 600;
        white-space: nowrap;
    }
    .youth-ranking-print-root .score-high {
        background: #d1fae5;
        color: #065f46;
    }
    .youth-ranking-print-root .score-medium {
        background: #fef3c7;
        color: #92400e;
    }
    .youth-ranking-print-root .score-low {
        background: #fee2e2;
        color: #991b1b;
    }
    .youth-ranking-print-root .youth-ranking-print-btn {
        background: #6b7280;
        color: #fff;
        border: none;
        cursor: pointer;
        padding: 10px 20px;
        border-radius: 8px;
        font-weight: 600;
    }
    @media (max-width: 768px) {
        .youth-ranking-print-root .obrazac-zaglavlje-top {
            flex-wrap: wrap;
        }
        .youth-ranking-print-root .obrazac-contact {
            text-align: left;
        }
        .youth-ranking-print-root .obrazac-naslov-prijava {
            font-size: 18px;
        }
        .youth-ranking-print-root th,
        .youth-ranking-print-root td {
            padding: 8px;
            font-size: 12px;
        }
        .youth-ranking-print-root .ranking-badge {
            width: 28px;
            height: 28px;
            line-height: 28px;
            font-size: 12px;
        }
        .youth-ranking-print-root .youth-allocation-draft-form .youth-allocation-decision-row {
            flex-wrap: wrap;
            align-items: stretch;
        }
        .youth-ranking-print-root .youth-allocation-decision-choices,
        .youth-ranking-print-root .youth-allocation-field-amount,
        .youth-ranking-print-root .youth-allocation-field-justification,
        .youth-ranking-print-root .youth-allocation-decision-row .btn-primary {
            flex: 1 1 100%;
            width: 100%;
            min-height: 0;
        }
        .youth-ranking-print-root .youth-decision-amount-grid {
            grid-template-columns: 1fr !important;
        }
    }
    @media print {
        body.youth-ranking-print nav,
        body.youth-ranking-print header,
        body.youth-ranking-print footer,
        body.youth-ranking-print .page-header,
        body.youth-ranking-print .form-card,
        body.youth-ranking-print .filters,
        body.youth-ranking-print .youth-ranking-no-print,
        body.youth-ranking-print .container > *:not(.youth-ranking-print-root) {
            display: none !important;
        }
        body.youth-ranking-print .youth-ranking-print-root:not(.youth-ranking-print-target) {
            display: none !important;
        }
        body.youth-ranking-print .youth-ranking-print-root.youth-ranking-print-target {
            position: static;
            box-shadow: none;
            border: none;
            border-radius: 0;
            padding: 0;
            margin: 0;
            background: #fff;
        }
        body.youth-ranking-print .youth-ranking-print-root .obrazac-zaglavlje,
        body.youth-ranking-print .youth-ranking-list-card {
            box-shadow: none;
            border: none !important;
            border-radius: 0;
            padding: 0 0 8px 0;
            margin-bottom: 8px;
        }
        body.youth-ranking-print .youth-ranking-print-root table {
            min-width: 0;
            font-size: 8pt;
        }
        body.youth-ranking-print .youth-ranking-print-root th,
        body.youth-ranking-print .youth-ranking-print-root td {
            border: 1px solid #000 !important;
            padding: 3px 4px !important;
            font-size: 8pt !important;
            background: transparent !important;
            color: #000 !important;
        }
        body.youth-ranking-print .youth-ranking-print-root .ranking-badge {
            background: transparent !important;
            color: #000 !important;
            border: 1px solid #000;
            width: 22px;
            height: 22px;
            line-height: 22px;
            font-size: 8pt;
        }
        body.youth-ranking-print .youth-ranking-print-root .score-badge {
            background: transparent !important;
            color: #000 !important;
            border: none;
            padding: 0;
            font-size: 8pt;
        }
        body.youth-ranking-print .youth-ranking-print-root .below-minimum-heading {
            background: transparent !important;
            color: #000 !important;
            border: none !important;
            box-shadow: none !important;
            padding: 0 0 6px 0 !important;
            font-size: 9pt !important;
        }
        body.youth-ranking-print .youth-ranking-table-wrap {
            overflow: visible;
        }
    }
</style>
<script>
    window.printYouthPreliminaryRanking = function (button) {
        var root = button && typeof button.closest === 'function'
            ? button.closest('.youth-ranking-print-root')
            : null;
        if (!root) {
            return;
        }
        var cleaned = false;
        var done = function () {
            if (cleaned) {
                return;
            }
            cleaned = true;
            document.body.classList.remove('youth-ranking-print');
            root.classList.remove('youth-ranking-print-target');
            window.removeEventListener('afterprint', done);
        };
        window.addEventListener('afterprint', done);
        document.body.classList.add('youth-ranking-print');
        root.classList.add('youth-ranking-print-target');
        window.print();
    };
</script>
    <div class="youth-ranking-print-root{{ $youthRankingView['ranking_ready'] ? '' : ' is-blocked' }}">
        @if(! $youthRankingView['ranking_ready'])
            @if($youthRankingTitle)
                <h3 style="margin: 0 0 8px; font-size: 16px; font-weight: 600; color: #111827;">{{ $youthRankingTitle }}</h3>
            @endif
            @if(! $youthRankingView['cycle_complete'])
                <p style="margin: 0; color: #92400e; font-weight: 600;">{{ \App\Services\CanonicalIndividualScoringService::YOUTH_CYCLE_INCOMPLETE_MESSAGE }}</p>
            @else
                <p style="margin: 0; color: #92400e; font-weight: 600;">{{ $youthRankingView['block_reason'] }}</p>
            @endif
        @else
            <div class="obrazac-zaglavlje">
                <div class="obrazac-zaglavlje-top">
                    <div class="obrazac-grb">
                        <img src="{{ asset('images/srednji_grb.png') }}" alt="Grb Opštine Kotor" onerror="this.onerror=null; this.src='{{ asset('images/srednji_grb.svg') }}';">
                    </div>
                    <div class="obrazac-org">
                        <p><strong>Crna Gora</strong></p>
                        <p>Opština Kotor</p>
                        <p>Sekretarijat za razvoj preduzetništva</p>
                        <p>komunalne poslove i saobraćaj</p>
                    </div>
                    <div class="obrazac-contact">
                        <p>Stari grad 317</p>
                        <p>85330 Kotor, Crna Gora</p>
                        <p>tel. +382(0)32 325 865</p>
                        <p>privreda@kotor.me</p>
                        <p>www.kotor.me</p>
                    </div>
                </div>
                <div class="obrazac-broj-i-naslov">
                    <div class="obrazac-broj-prijave">
                        Konkurs: {{ $youthRankingCompetition?->upNumber?->number ?? '—' }} / {{ $youthRankingCompetition?->year ?? '' }}
                    </div>
                </div>
                <h1 class="obrazac-naslov-prijava">RANG LISTA BIZNIS PLANOVA</h1>
                <p class="obrazac-podnaslov">
                    {{ $youthRankingCompetition?->title ?? $youthRankingTitle }}
                </p>
            </div>

            <div class="youth-ranking-list-card">
            <h2>Preliminarna rang-lista</h2>
            @if(count($youthRankingView['above'] ?? []) === 0 && count($youthRankingView['below'] ?? []) === 0)
                <div style="text-align: center; padding: 40px; color: #6b7280;">
                    <p style="margin-bottom: 16px; font-size: 16px; font-weight: 600;">Nema ocijenjenih prijava za ovaj konkurs.</p>
                    <p style="margin-bottom: 8px; font-size: 14px;">Prijave odbijene zbog nedostatka dokumenata ne prikazuju se u rang listi.</p>
                </div>
            @else
            @if(count($youthRankingView['above'] ?? []) > 0)
            <div class="youth-ranking-table-wrap">
            <table>
                <thead>
                    <tr>
                        <th style="width: 60px;">Poz.</th>
                        <th>Naziv biznis plana</th>
                        <th>Podnosilac</th>
                        <th>Tip</th>
                        <th style="text-align: center;">Ocjena</th>
                        <th style="text-align: right;">Traženi iznos</th>
                        <th style="text-align: right;">Odobreni iznos</th>
                        <th style="text-align: center;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($youthRankingView['above'] as $ranked)
                        @php
                            $score = (float) $ranked->final_score;
                            $scoreClass = $score >= 40 ? 'score-high' : ($score >= 30 ? 'score-medium' : 'score-low');
                            $youthType = $youthApplicantTypeLabel($ranked);
                            $youthStage = $ranked->business_stage === 'započinjanje' ? 'Započinjanje' : ($ranked->business_stage === 'razvoj' ? 'Razvoj' : null);
                        @endphp
                        <tr style="{{ $ranked->status === 'approved' ? 'background: #d1fae5;' : '' }}">
                            <td>
                                <span class="ranking-badge">{{ $ranked->ranking_position }}</span>
                            </td>
                            <td>{{ $ranked->business_plan_name }}</td>
                            <td>{{ $ranked->user->name ?? 'N/A' }}</td>
                            <td>
                                {{ $youthType }}@if($youthStage) - {{ $youthStage }}@endif
                            </td>
                            <td style="text-align: center;">
                                <span class="score-badge {{ $scoreClass }}">
                                    {{ number_format($score, 2) }} / 56
                                </span>
                            </td>
                            <td style="text-align: right;">
                                {{ $ranked->requested_amount !== null ? number_format((float) $ranked->requested_amount, 2, ',', '.').' €' : '—' }}
                            </td>
                            <td style="text-align: right;">
                                @if($ranked->status === 'approved' && $ranked->approved_amount)
                                    <strong>{{ number_format((float) $ranked->approved_amount, 2, ',', '.') }} €</strong>
                                @else
                                    <span style="color: #6b7280;">-</span>
                                @endif
                            </td>
                            <td style="text-align: center;">
                                @if($ranked->status === 'approved')
                                    <span style="font-weight: 600;">✓ Dobitnik sredstava</span>
                                @else
                                    <span style="color: #6b7280;">-</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
            @php
                $youthHasSavedDecision = collect($youthRankingView['above'])->contains(fn ($row) => filled($row->commission_decision));
            @endphp
            @if($canEditYouthAllocationDraft || $youthHasSavedDecision)
            <div class="info-card commission-decision-section" style="background: transparent; box-shadow: none; padding: 24px 0 0; margin-bottom: 0;">
                <h2>Zaključak komisije i obrazloženje</h2>
                @if($canEditYouthAllocationDraft)
                    <p class="no-print" style="color: #6b7280; margin-bottom: 24px; font-size: 14px;">
                        Za svaku prijavu u rang listi unesite zaključak komisije i obrazloženje.
                    </p>
                @endif
                @foreach($youthRankingView['above'] as $ranked)
                    @if(! $canEditYouthAllocationDraft && ! $ranked->commission_decision)
                        @continue
                    @endif
                    @php
                        $score = (float) $ranked->final_score;
                        $youthDraftService = app(\App\Services\Competitions\YouthAllocationDraftService::class);
                        $remainingForDraft = $youthDraftService->remainingBudget($ranked->competition, $ranked->id);
                        $youthCap = $youthDraftService->capSnapshot($ranked);
                        $youthDecisionLocked = $youthDraftService->decisionIsLocked($ranked);
                        $startupValue = old('youth_innovative_tech_startup', $ranked->getAttributes()['youth_innovative_tech_startup'] ?? null);
                        $priorValue = old('youth_prior_municipal_youth_funding', $ranked->getAttributes()['youth_prior_municipal_youth_funding'] ?? null);
                    @endphp
                    <div class="commission-decision-block{{ $youthDecisionLocked ? ' commission-decision-block-saved' : '' }}" style="background: #f9fafb; padding: 24px; border-radius: 12px; margin-bottom: 24px; border: 1px solid #e5e7eb;">
                        @if($canEditYouthAllocationDraft && ! $youthDecisionLocked)
                            <div class="commission-decision-header no-print" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 16px; border-bottom: 2px solid #e5e7eb; gap: 12px; flex-wrap: wrap;">
                                <div>
                                    <h3 style="font-size: 18px; font-weight: 700; color: #0B3D91; margin: 0 0 4px 0;">
                                        {{ $ranked->business_plan_name }}
                                    </h3>
                                    <p style="font-size: 14px; color: #6b7280; margin: 0;">
                                        Podnosilac: {{ $ranked->user->name ?? 'N/A' }} |
                                        Pozicija: <strong>#{{ $ranked->ranking_position }}</strong> |
                                        Ocjena: <strong>{{ number_format($score, 2) }} / 56</strong>
                                    </p>
                                </div>
                                @if($ranked->commission_decision)
                                    <span style="display: inline-block; padding: 6px 12px; background: #d1fae5; color: #065f46; border-radius: 6px; font-size: 12px; font-weight: 600;">
                                        ✓ Zaključeno
                                    </span>
                                @endif
                            </div>
                            <p class="no-print" data-testid="youth-allocation-decision-lock-notice" style="margin: 0 0 16px; color: #92400e; font-size: 14px; font-weight: 600;">
                                {{ \App\Services\Competitions\YouthAllocationDraftService::DECISION_LOCK_NOTICE }}
                            </p>
                            <form method="POST" action="{{ route('evaluation.youth-allocation-draft', $ranked) }}" class="no-print youth-allocation-draft-form" data-testid="youth-allocation-draft-form">
                                @csrf
                                <div style="margin-bottom: 24px;">
                                    <label style="display: block; font-size: 15px; font-weight: 600; color: #374151; margin-bottom: 12px;">
                                        Zaključak komisije: *
                                    </label>
                                    <div class="youth-allocation-decision-row" data-testid="youth-allocation-decision-row" style="display: flex; gap: 32px; flex-wrap: wrap;">
                                        <label class="youth-allocation-choice" style="display: flex; align-items: center; gap: 10px; cursor: pointer; padding: 12px; background: #fff; border-radius: 8px; border: 2px solid #e5e7eb;">
                                            <input type="radio" name="commission_decision" value="podrzava_potpuno" {{ old('commission_decision', $ranked->commission_decision) === 'podrzava_potpuno' ? 'checked' : '' }} required>
                                            <span style="font-size: 14px; color: #374151; font-weight: 500;">a. Podržava u potpunosti</span>
                                        </label>
                                        <label class="youth-allocation-choice" style="display: flex; align-items: center; gap: 10px; cursor: pointer; padding: 12px; background: #fff; border-radius: 8px; border: 2px solid #e5e7eb;">
                                            <input type="radio" name="commission_decision" value="odbija" {{ old('commission_decision', $ranked->commission_decision) === 'odbija' ? 'checked' : '' }} required>
                                            <span style="font-size: 14px; color: #374151; font-weight: 500;">b. Odbija</span>
                                        </label>
                                    </div>
                                </div>
                                <div class="youth-decision-amount-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px;">
                                    <label class="youth-allocation-field youth-allocation-field-amount">
                                        <span style="display: block; font-size: 15px; font-weight: 600; color: #374151; margin-bottom: 8px;">Iznos odobrenih sredstava:</span>
                                        <input type="number" step="0.01" min="0" name="approved_amount" value="{{ old('approved_amount', $ranked->approved_amount) }}">
                                    </label>
                                    <div>
                                        <span style="display: block; font-size: 15px; font-weight: 600; color: #374151; margin-bottom: 8px;">Traženi iznos:</span>
                                        <div style="padding: 10px 12px; background: #fff; border-radius: 6px; color: #6b7280; font-size: 14px;">
                                            {{ $ranked->requested_amount !== null ? number_format((float) $ranked->requested_amount, 2, ',', '.') : '—' }} €
                                        </div>
                                        <p style="margin: 8px 0 0; color: #6b7280; font-size: 12px;">
                                            Raspoloživo za ovu prijavu: {{ number_format((float) $remainingForDraft, 2, ',', '.') }} €
                                        </p>
                                    </div>
                                </div>
                                <label class="youth-allocation-field youth-allocation-field-justification" style="display: block; margin-bottom: 24px;">
                                    <span style="display: block; font-size: 15px; font-weight: 600; color: #374151; margin-bottom: 8px;">Obrazloženje (obavezno za Odbija; kada je iznos manji od traženog — i za Podržava):</span>
                                    <textarea name="commission_justification" rows="6" style="width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px;">{{ old('commission_justification', $ranked->commission_justification) }}</textarea>
                                </label>
                                <div style="display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 16px;" data-testid="youth-allocation-facts">
                                    <label>
                                        Inovativni tehnološki start-up
                                        <select name="youth_innovative_tech_startup" style="margin-left: 6px;">
                                            <option value="">—</option>
                                            <option value="1" {{ in_array($startupValue, [1, '1', true], true) ? 'selected' : '' }}>Da</option>
                                            <option value="0" {{ in_array($startupValue, [0, '0', false], true) ? 'selected' : '' }}>Ne</option>
                                        </select>
                                    </label>
                                    <label>
                                        Ranije dodijeljena sredstva Opštine Kotor za podršku preduzetništvu mladih
                                        <select name="youth_prior_municipal_youth_funding" style="margin-left: 6px;">
                                            <option value="">—</option>
                                            <option value="1" {{ in_array($priorValue, [1, '1', true], true) ? 'selected' : '' }}>Da</option>
                                            <option value="0" {{ in_array($priorValue, [0, '0', false], true) ? 'selected' : '' }}>Ne</option>
                                        </select>
                                    </label>
                                </div>
                                @error('approved_amount')
                                    <div class="error-message">{{ $message }}</div>
                                @enderror
                                @error('commission_justification')
                                    <div class="error-message">{{ $message }}</div>
                                @enderror
                                @error('commission_decision')
                                    <div class="error-message">{{ $message }}</div>
                                @enderror
                                @error('youth_innovative_tech_startup')
                                    <div class="error-message">{{ $message }}</div>
                                @enderror
                                @error('youth_prior_municipal_youth_funding')
                                    <div class="error-message">{{ $message }}</div>
                                @enderror
                                <div style="text-align: right;">
                                    <button type="submit" class="btn-primary" style="padding: 10px 24px; font-size: 14px; min-width: 160px;">Sačuvaj zaključak</button>
                                </div>
                            </form>
                        @elseif($youthDecisionLocked)
                            <p data-testid="youth-allocation-decision-locked" style="margin: 0 0 16px; color: #065f46; font-weight: 600;">
                                {{ \App\Services\Competitions\YouthAllocationDraftService::DECISION_LOCKED_MESSAGE }}
                            </p>
                        @endif
                        @if($ranked->commission_decision)
                            <table class="commission-decision-table" style="width: 100%; border-collapse: collapse; margin-top: 16px;">
                                <tbody>
                                    <tr>
                                        <th colspan="2" style="text-align: left; font-size: 16px; font-weight: 700; color: #0B3D91; padding: 10px 12px; border: 1px solid #e5e7eb;">{{ $ranked->business_plan_name }}</th>
                                    </tr>
                                    <tr>
                                        <th style="width: 28%; text-align: left; padding: 10px 12px; border: 1px solid #e5e7eb; background: #f9fafb;">Zaključak</th>
                                        <td style="padding: 10px 12px; border: 1px solid #e5e7eb;">{{ $ranked->commission_decision === 'podrzava_potpuno' ? 'Podržava u potpunosti' : 'Odbija' }}</td>
                                    </tr>
                                    <tr>
                                        <th style="text-align: left; padding: 10px 12px; border: 1px solid #e5e7eb; background: #f9fafb;">Iznos odobrenih sredstava</th>
                                        <td style="padding: 10px 12px; border: 1px solid #e5e7eb;">{{ $ranked->status === 'approved' && $ranked->approved_amount ? number_format((float) $ranked->approved_amount, 2, ',', '.').' €' : '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th style="text-align: left; padding: 10px 12px; border: 1px solid #e5e7eb; background: #f9fafb;">Traženi iznos</th>
                                        <td style="padding: 10px 12px; border: 1px solid #e5e7eb;">{{ $ranked->requested_amount !== null ? number_format((float) $ranked->requested_amount, 2, ',', '.') : '—' }} €</td>
                                    </tr>
                                    @if(filled($ranked->commission_justification))
                                        <tr>
                                            <th style="text-align: left; padding: 10px 12px; border: 1px solid #e5e7eb; background: #f9fafb;">Obrazloženje</th>
                                            <td style="padding: 10px 12px; border: 1px solid #e5e7eb; white-space: pre-wrap;">{{ $ranked->commission_justification }}</td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        @endif
                        @if($youthCap['facts_confirmed'])
                            <div style="margin-top: 16px; color: #374151; font-size: 13px;" data-testid="youth-allocation-cap-summary">
                                <p style="margin: 0 0 4px;">Inovativni tehnološki start-up: {{ $youthCap['startup'] ? 'Da' : 'Ne' }}</p>
                                <p style="margin: 0 0 4px;">Ranije dodijeljena sredstva Opštine Kotor za podršku preduzetništvu mladih: {{ $youthCap['prior_funding'] ? 'Da' : 'Ne' }}</p>
                                <p style="margin: 0 0 4px;">Primijenjeni maksimum: {{ $youthCap['percent'] }}% ({{ number_format((float) $youthCap['percent_max'], 2) }} EUR)</p>
                                <p style="margin: 0 0 4px;">{{ \App\Services\Competitions\YouthAllocationDraftService::CAP_IS_NOT_AUTOMATIC_AWARD_MESSAGE }}</p>
                                @if($youthCap['confirmed_by_name'])
                                    <p style="margin: 0;">Potvrdio: {{ $youthCap['confirmed_by_name'] }}@if($youthCap['confirmed_at']) — {{ $youthCap['confirmed_at'] }}@endif</p>
                                @endif
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
            @endif
            @endif
            @endif
            @if(count($youthRankingView['below'] ?? []) > 0)
            <div class="below-minimum-section" style="margin-top: 48px; padding-top: 32px; border-top: 4px solid #374151; box-shadow: 0 -2px 8px rgba(0,0,0,0.08);">
                <div class="below-minimum-heading" style="background: #374151; color: #fff; padding: 14px 24px; margin-top: -32px; margin-bottom: 24px; border-radius: 8px; font-weight: 700; font-size: 16px; text-align: center; display: inline-block; width: 100%; box-sizing: border-box;">
                    ——— Prijave ispod minimuma (30 bodova) ———
                </div>
                <h3 style="font-size: 16px; font-weight: 600; color: #6b7280; margin-bottom: 16px;">Prijave sa manje od 30 bodova</h3>
                <div class="youth-ranking-table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Naziv biznis plana</th>
                            <th>Podnosilac</th>
                            <th>Tip</th>
                            <th style="text-align: center;">Ocjena</th>
                            <th style="text-align: right;">Traženi iznos</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($youthRankingView['below'] as $below)
                            @php
                                $belowType = $youthApplicantTypeLabel($below);
                                $belowStage = $below->business_stage === 'započinjanje' ? 'Započinjanje' : ($below->business_stage === 'razvoj' ? 'Razvoj' : null);
                            @endphp
                            <tr style="background: #f9fafb;">
                                <td>{{ $below->business_plan_name }}</td>
                                <td>{{ $below->user->name ?? 'N/A' }}</td>
                                <td>
                                    {{ $belowType }}@if($belowStage) - {{ $belowStage }}@endif
                                </td>
                                <td style="text-align: center;">
                                    <span class="score-badge score-low">
                                        {{ number_format((float) $below->final_score, 2) }} / 56
                                    </span>
                                </td>
                                <td style="text-align: right;">
                                    {{ $below->requested_amount !== null ? number_format((float) $below->requested_amount, 2, ',', '.').' €' : '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" style="padding: 8px; color: #6b7280;">Nema prijava ispod praga.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>
            @endif
            </div>
            <div class="youth-ranking-no-print no-print" style="margin: 24px 0 8px 0; text-align: center;">
                <button type="button" onclick="printYouthPreliminaryRanking(this);" class="youth-ranking-print-btn" data-testid="youth-ranking-print">
                    Štampaj
                </button>
            </div>
            <div class="youth-ranking-no-print no-print">
            @include('evaluation._youth_equal_score_voting', [
                'youthEqualScoreVotingBoard' => $youthEqualScoreVotingBoard ?? null,
                'youthEqualScoreCompetition' => $youthEqualScoreCompetition ?? $youthAboveFirst?->competition,
            ])
            @include('evaluation._youth_allocation_list_confirmation', [
                'youthAllocationConfirmationBoard' => $youthAllocationConfirmationBoard ?? null,
                'youthEqualScoreCompetition' => $youthEqualScoreCompetition ?? $youthAboveFirst?->competition,
            ])
            </div>
        @endif
    </div>
@endif
