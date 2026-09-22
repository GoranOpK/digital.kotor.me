@php
    $youthEqualScoreVotingBoard = $youthEqualScoreVotingBoard ?? null;
@endphp
@if($youthEqualScoreVotingBoard && ($youthEqualScoreVotingBoard['visible'] ?? false) && ($youthEqualScoreVotingBoard['ranking_ready'] ?? false) && ($youthEqualScoreVotingBoard['groups'] ?? []) !== [])
    <div class="info-card" style="background: #fff; border-radius: 12px; padding: 16px 20px; margin-bottom: 20px; border-left: 4px solid #0f766e; box-shadow: 0 1px 3px rgba(0,0,0,0.1);" data-testid="youth-equal-score-voting">
        <h3 style="margin: 0 0 12px; font-size: 16px; font-weight: 600; color: #111827;">Izjednačene grupe — glasanje Komisije</h3>
        @foreach($youthEqualScoreVotingBoard['groups'] as $group)
            @php
                $canEditGroup = ($youthEqualScoreVotingBoard['can_edit'] ?? false) && ($group['can_create_round'] ?? false);
                $activeRound = $group['active_round'] ?? null;
                $displayApps = $activeRound['applications'] ?? ($group['applications'] ?? []);
                $displayVotes = $activeRound['votes'] ?? [];
                $votesBySeat = collect($displayVotes)->keyBy('canonical_seat_no');
                $selectedIds = collect($displayApps)->where('selected', true)->pluck('application_id')->all();
            @endphp
            <div style="margin-bottom: 20px; padding-bottom: 12px; border-bottom: 1px solid #e5e7eb;" data-testid="youth-equal-score-group">
                <p style="margin: 0 0 8px; color: #374151;">
                    Pozicija {{ $group['ranking_position'] }},
                    puni rezultat {{ $group['full_score'] }}.
                    Raspoloživo prije predloga: {{ number_format((float) $group['remaining_before'], 2) }} EUR.
                </p>
                @if(! empty($group['block_reason']))
                    <p style="margin: 0 0 8px; color: #92400e; font-weight: 600;">{{ $group['block_reason'] }}</p>
                @endif
                @if($canEditGroup)
                    <form method="POST" action="{{ route('evaluation.youth-equal-score-round', $youthEqualScoreCompetition) }}" class="no-print" data-testid="youth-equal-score-round-form">
                        @csrf
                        <input type="hidden" name="group_key" value="{{ $group['group_key'] }}">
                        <table style="width: 100%; border-collapse: collapse; margin-bottom: 12px;">
                            <thead>
                                <tr>
                                    <th style="text-align: left; padding: 8px; border-bottom: 1px solid #e5e7eb;">Prijava</th>
                                    <th style="text-align: left; padding: 8px; border-bottom: 1px solid #e5e7eb;">Faza</th>
                                    <th style="text-align: right; padding: 8px; border-bottom: 1px solid #e5e7eb;">Traženo</th>
                                    <th style="text-align: right; padding: 8px; border-bottom: 1px solid #e5e7eb;">Nacrt</th>
                                    <th style="text-align: right; padding: 8px; border-bottom: 1px solid #e5e7eb;">Limit</th>
                                    <th style="text-align: center; padding: 8px; border-bottom: 1px solid #e5e7eb;">Podržati</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($displayApps as $item)
                                    <tr>
                                        <td style="padding: 8px;">{{ $item['business_plan_name'] }}</td>
                                        <td style="padding: 8px;">{{ $item['business_stage'] }}</td>
                                        <td style="padding: 8px; text-align: right;">{{ $item['requested_amount'] !== null ? number_format((float) $item['requested_amount'], 2) : '—' }}</td>
                                        <td style="padding: 8px; text-align: right;">{{ number_format((float) $item['draft_amount'], 2) }}</td>
                                        <td style="padding: 8px; text-align: right;">{{ $item['applied_cap_percent'] !== null ? $item['applied_cap_percent'].'%' : '—' }}</td>
                                        <td style="padding: 8px; text-align: center;">
                                            <input type="checkbox" name="selected[]" value="{{ $item['application_id'] }}" {{ in_array((int) $item['application_id'], array_map('intval', $selectedIds), true) ? 'checked' : '' }}>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <label style="display: block; margin-bottom: 12px;">
                            Obrazloženje
                            <textarea name="justification" required style="width: 100%; min-height: 72px; margin-top: 6px;">{{ old('justification', $activeRound['justification'] ?? '') }}</textarea>
                        </label>
                        <div style="display: flex; flex-wrap: wrap; gap: 16px; margin-bottom: 12px;">
                            @foreach([1, 2, 3] as $seatNo)
                                @php $currentVote = old('votes.'.$seatNo, $votesBySeat[$seatNo]['vote_value'] ?? ''); @endphp
                                <fieldset style="border: 1px solid #e5e7eb; padding: 8px 12px;">
                                    <legend>Mjesto {{ $seatNo }}</legend>
                                    <label style="margin-right: 8px;">
                                        <input type="radio" name="votes[{{ $seatNo }}]" value="for" {{ $currentVote === 'for' ? 'checked' : '' }}>
                                        Za
                                    </label>
                                    <label>
                                        <input type="radio" name="votes[{{ $seatNo }}]" value="against" {{ $currentVote === 'against' ? 'checked' : '' }}>
                                        Protiv
                                    </label>
                                </fieldset>
                            @endforeach
                        </div>
                        <p style="margin: 0 0 8px; color: #6b7280; font-size: 12px;">
                            Status kruga: {{ $activeRound ? 'Nacrt' : 'Nema nacrta' }}.
                            Zaključavanje je moguće tek sa sva tri glasa.
                        </p>
                        <button type="submit" class="btn-primary">Sačuvaj predlog i glasove</button>
                    </form>
                    @if($activeRound)
                        <form method="POST" action="{{ route('evaluation.youth-equal-score-round.lock', [$youthEqualScoreCompetition, $activeRound['id']]) }}" class="no-print" style="margin-top: 8px;" data-testid="youth-equal-score-round-lock-form">
                            @csrf
                            <button type="submit" class="btn-primary">Zaključi krug</button>
                        </form>
                    @endif
                    @error('justification')
                        <div class="error-message">{{ $message }}</div>
                    @enderror
                    @error('selected')
                        <div class="error-message">{{ $message }}</div>
                    @enderror
                    @error('votes')
                        <div class="error-message">{{ $message }}</div>
                    @enderror
                @elseif(($group['history'] ?? []) !== [] || $activeRound)
                    @php $lockedLatest = collect($group['history'] ?? [])->last(); @endphp
                    @if($lockedLatest)
                        <p style="margin: 0 0 8px;">Zaključani krug {{ $lockedLatest['round_no'] }}: {{ $lockedLatest['outcome'] === 'adopted' ? 'Usvojen' : 'Nije usvojen' }}.</p>
                        <table style="width: 100%; border-collapse: collapse; margin-bottom: 12px;">
                            <thead>
                                <tr>
                                    <th style="text-align: left; padding: 8px; border-bottom: 1px solid #e5e7eb;">Prijava</th>
                                    <th style="text-align: right; padding: 8px; border-bottom: 1px solid #e5e7eb;">Nacrt</th>
                                    <th style="text-align: center; padding: 8px; border-bottom: 1px solid #e5e7eb;">Podržana</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($lockedLatest['applications'] as $item)
                                    <tr>
                                        <td style="padding: 8px;">{{ $item['business_plan_name'] }}</td>
                                        <td style="padding: 8px; text-align: right;">{{ number_format((float) $item['draft_amount'], 2) }}</td>
                                        <td style="padding: 8px; text-align: center;">{{ $item['selected'] ? 'Da' : 'Ne' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <p style="margin: 0 0 8px; font-size: 13px;">
                            @foreach($lockedLatest['votes'] as $vote)
                                Mjesto {{ $vote['canonical_seat_no'] }} ({{ $vote['member_name'] }}): {{ $vote['vote_value'] === 'for' ? 'Za' : 'Protiv' }}@if(! $loop->last); @endif
                            @endforeach
                        </p>
                    @endif
                @endif
                @if(($group['history'] ?? []) !== [])
                    <div data-testid="youth-equal-score-history">
                        <h4 style="margin: 12px 0 8px; font-size: 14px;">Istorija zaključanih krugova</h4>
                        <ul style="margin: 0; padding-left: 18px; color: #4b5563;">
                            @foreach($group['history'] as $historyRound)
                                <li>
                                    Krug {{ $historyRound['round_no'] }} —
                                    {{ $historyRound['outcome'] === 'adopted' ? 'usvojen' : 'nije usvojen' }}
                                    ({{ number_format((float) $historyRound['proposal_total'], 2) }} EUR).
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        @endforeach
    </div>
@endif
