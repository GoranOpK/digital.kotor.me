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
                    </tr>
                </thead>
                <tbody>
                    @forelse($youthRankingView['above'] as $ranked)
                        @php
                            $score = (float) $ranked->final_score;
                            $scoreClass = $score >= 40 ? 'score-high' : 'score-medium';
                            $youthType = match ($ranked->applicant_type) {
                                'preduzetnik' => 'Preduzetnik',
                                'doo' => 'DOO',
                                'fizicko_lice' => 'Fizičko lice',
                                default => 'Ostalo',
                            };
                            $youthStage = $ranked->business_stage === 'započinjanje' ? 'Započinjanje' : ($ranked->business_stage === 'razvoj' ? 'Razvoj' : null);
                        @endphp
                        <tr>
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
                                @if($ranked->approved_amount)
                                    <strong>{{ number_format((float) $ranked->approved_amount, 2, ',', '.') }} €</strong>
                                @else
                                    <span style="color: #6b7280;">-</span>
                                @endif
                            </td>
                        </tr>
                        @if(($youthRankingView['ranking_ready'] ?? false) && $canEditYouthAllocationDraft)
                            @php
                                $youthDraftService = app(\App\Services\Competitions\YouthAllocationDraftService::class);
                                $remainingForDraft = $youthDraftService->remainingBudget($ranked->competition, $ranked->id);
                                $youthCap = $youthDraftService->capSnapshot($ranked);
                                $startupValue = old('youth_innovative_tech_startup', $ranked->getAttributes()['youth_innovative_tech_startup'] ?? null);
                                $priorValue = old('youth_prior_municipal_youth_funding', $ranked->getAttributes()['youth_prior_municipal_youth_funding'] ?? null);
                            @endphp
                            <tr class="youth-ranking-no-print no-print">
                                <td colspan="7" style="padding: 8px 8px 16px;">
                                    <form method="POST" action="{{ route('evaluation.youth-allocation-draft', $ranked) }}" class="no-print" data-testid="youth-allocation-draft-form">
                                        @csrf
                                        <div style="display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-end;">
                                            <label style="display: flex; align-items: center; gap: 6px;">
                                                <input type="radio" name="commission_decision" value="podrzava_potpuno" {{ old('commission_decision', $ranked->commission_decision) === 'podrzava_potpuno' ? 'checked' : '' }} required>
                                                Podržava
                                            </label>
                                            <label style="display: flex; align-items: center; gap: 6px;">
                                                <input type="radio" name="commission_decision" value="odbija" {{ old('commission_decision', $ranked->commission_decision) === 'odbija' ? 'checked' : '' }} required>
                                                Odbija
                                            </label>
                                            <label>
                                                Iznos
                                                <input type="number" step="0.01" min="0" name="approved_amount" value="{{ old('approved_amount', $ranked->approved_amount) }}" style="width: 140px; margin-left: 6px;">
                                            </label>
                                            <label style="flex: 1; min-width: 220px;">
                                                Obrazloženje
                                                <input type="text" name="commission_justification" value="{{ old('commission_justification', $ranked->commission_justification) }}" style="width: 100%; margin-left: 6px;">
                                            </label>
                                            <button type="submit" class="btn-primary">Sačuvaj nacrt</button>
                                        </div>
                                        <div style="display: flex; flex-wrap: wrap; gap: 12px; margin-top: 12px;" data-testid="youth-allocation-facts">
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
                                        @if($youthCap['facts_confirmed'])
                                            <div style="margin-top: 10px; color: #374151; font-size: 13px;" data-testid="youth-allocation-cap-summary">
                                                <p style="margin: 0 0 4px;">Inovativni tehnološki start-up: {{ $youthCap['startup'] ? 'Da' : 'Ne' }}</p>
                                                <p style="margin: 0 0 4px;">Ranije dodijeljena sredstva Opštine Kotor za podršku preduzetništvu mladih: {{ $youthCap['prior_funding'] ? 'Da' : 'Ne' }}</p>
                                                <p style="margin: 0 0 4px;">Primijenjeni maksimum: {{ $youthCap['percent'] }}% ({{ number_format((float) $youthCap['percent_max'], 2) }} EUR)</p>
                                                <p style="margin: 0 0 4px;">{{ \App\Services\Competitions\YouthAllocationDraftService::CAP_IS_NOT_AUTOMATIC_AWARD_MESSAGE }}</p>
                                                @if($youthCap['confirmed_by_name'])
                                                    <p style="margin: 0;">Potvrdio: {{ $youthCap['confirmed_by_name'] }}@if($youthCap['confirmed_at']) — {{ $youthCap['confirmed_at'] }}@endif</p>
                                                @endif
                                            </div>
                                        @endif
                                        <p style="margin: 8px 0 0; color: #6b7280; font-size: 12px;">
                                            Traženo: {{ $ranked->requested_amount !== null ? number_format((float) $ranked->requested_amount, 2) : '—' }} EUR.
                                            Raspoloživo za ovu prijavu: {{ number_format((float) $remainingForDraft, 2) }} EUR.
                                            Nacrt ne mijenja status prijave.
                                        </p>
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
                                    </form>
                                </td>
                            </tr>
                        @elseif($ranked->commission_decision)
                            <tr class="youth-ranking-no-print no-print">
                                <td colspan="7" style="padding: 0 8px 12px; color: #4b5563; font-size: 13px;">
                                    Nacrt:
                                    {{ $ranked->commission_decision === 'podrzava_potpuno' ? 'Podržava' : 'Odbija' }}
                                    @if($ranked->approved_amount)
                                        — {{ number_format((float) $ranked->approved_amount, 2) }} EUR
                                    @endif
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="7" style="padding: 8px; color: #6b7280;">Nema prijava iznad praga.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
            <div class="below-minimum-section" style="margin-top: 48px; padding-top: 32px; border-top: 4px solid #374151; box-shadow: 0 -2px 8px rgba(0,0,0,0.08);">
                <div class="below-minimum-heading" style="background: #374151; color: #fff; padding: 14px 24px; margin-top: -32px; margin-bottom: 24px; border-radius: 8px; font-weight: 700; font-size: 16px; text-align: center; display: inline-block; width: 100%; box-sizing: border-box;">
                    ——— Prijave ispod minimuma (30 bodova) ———
                </div>
                <h3 style="font-size: 16px; font-weight: 600; color: #6b7280; margin-bottom: 16px;">Ispod praga</h3>
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
                                $belowType = match ($below->applicant_type) {
                                    'preduzetnik' => 'Preduzetnik',
                                    'doo' => 'DOO',
                                    'fizicko_lice' => 'Fizičko lice',
                                    default => 'Ostalo',
                                };
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
            <p style="margin: 16px 0 0; color: #6b7280; font-size: 13px;">Ova lista nije konačna odluka o podršci. Prijave ispod praga ne dobijaju odluku o raspodjeli.</p>
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
