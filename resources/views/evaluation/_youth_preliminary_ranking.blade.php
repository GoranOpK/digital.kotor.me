@php
    $youthRankingView = $youthRankingView ?? null;
    $youthRankingTitle = $youthRankingTitle ?? null;
    $canEditYouthAllocationDraft = $canEditYouthAllocationDraft ?? false;
@endphp
@if($youthRankingView)
    <div class="info-card" style="background: #fff; border-radius: 12px; padding: 16px 20px; margin-bottom: 20px; border-left: 4px solid #8b5cf6; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        @if($youthRankingTitle)
            <h3 style="margin: 0 0 8px; font-size: 16px; font-weight: 600; color: #111827;">{{ $youthRankingTitle }}</h3>
        @endif
        @if(! $youthRankingView['cycle_complete'])
            <p style="margin: 0; color: #92400e; font-weight: 600;">{{ \App\Services\CanonicalIndividualScoringService::YOUTH_CYCLE_INCOMPLETE_MESSAGE }}</p>
        @elseif(! $youthRankingView['ranking_ready'])
            <p style="margin: 0; color: #92400e; font-weight: 600;">{{ $youthRankingView['block_reason'] }}</p>
        @else
            <h3 style="margin: 0 0 12px; font-size: 16px; font-weight: 600; color: #111827;">Preliminarna rang-lista</h3>
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 16px;">
                <thead>
                    <tr>
                        <th style="text-align: left; padding: 8px; border-bottom: 1px solid #e5e7eb;">Pozicija</th>
                        <th style="text-align: left; padding: 8px; border-bottom: 1px solid #e5e7eb;">Prijava</th>
                        <th style="text-align: left; padding: 8px; border-bottom: 1px solid #e5e7eb;">Podnosilac</th>
                        <th style="text-align: right; padding: 8px; border-bottom: 1px solid #e5e7eb;">Ocjena</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($youthRankingView['above'] as $ranked)
                        <tr>
                            <td style="padding: 8px;">{{ $ranked->ranking_position }}</td>
                            <td style="padding: 8px;">{{ $ranked->business_plan_name }}</td>
                            <td style="padding: 8px;">{{ $ranked->user->name ?? 'N/A' }}</td>
                            <td style="padding: 8px; text-align: right;">{{ number_format((float) $ranked->final_score, 2) }}</td>
                        </tr>
                        @if($canEditYouthAllocationDraft)
                            @php
                                $youthDraftService = app(\App\Services\Competitions\YouthAllocationDraftService::class);
                                $remainingForDraft = $youthDraftService->remainingBudget($ranked->competition, $ranked->id);
                                $youthCap = $youthDraftService->capSnapshot($ranked);
                                $startupValue = old('youth_innovative_tech_startup', $ranked->getAttributes()['youth_innovative_tech_startup'] ?? null);
                                $priorValue = old('youth_prior_municipal_youth_funding', $ranked->getAttributes()['youth_prior_municipal_youth_funding'] ?? null);
                            @endphp
                            <tr>
                                <td colspan="4" style="padding: 8px 8px 16px;">
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
                            <tr>
                                <td colspan="4" style="padding: 0 8px 12px; color: #4b5563; font-size: 13px;">
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
                            <td colspan="4" style="padding: 8px; color: #6b7280;">Nema prijava iznad praga.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <h3 style="margin: 0 0 12px; font-size: 16px; font-weight: 600; color: #111827;">Ispod praga</h3>
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 12px;">
                <thead>
                    <tr>
                        <th style="text-align: left; padding: 8px; border-bottom: 1px solid #e5e7eb;">Prijava</th>
                        <th style="text-align: left; padding: 8px; border-bottom: 1px solid #e5e7eb;">Podnosilac</th>
                        <th style="text-align: right; padding: 8px; border-bottom: 1px solid #e5e7eb;">Ocjena</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($youthRankingView['below'] as $below)
                        <tr>
                            <td style="padding: 8px;">{{ $below->business_plan_name }}</td>
                            <td style="padding: 8px;">{{ $below->user->name ?? 'N/A' }}</td>
                            <td style="padding: 8px; text-align: right;">{{ number_format((float) $below->final_score, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" style="padding: 8px; color: #6b7280;">Nema prijava ispod praga.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <p style="margin: 0; color: #6b7280; font-size: 13px;">Ova lista nije konačna odluka o podršci. Prijave ispod praga ne dobijaju odluku o raspodjeli.</p>
        @endif
    </div>
@endif
