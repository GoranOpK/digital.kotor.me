@php
    $youthEqualScoreVotingBoard = $youthEqualScoreVotingBoard ?? null;
@endphp
@if($youthEqualScoreVotingBoard && ($youthEqualScoreVotingBoard['visible'] ?? false) && ($youthEqualScoreVotingBoard['ranking_ready'] ?? false) && ($youthEqualScoreVotingBoard['groups'] ?? []) !== [])
    <div class="info-card" style="background: #fff; border-radius: 12px; padding: 16px 20px; margin-bottom: 20px; border-left: 4px solid #0f766e; box-shadow: 0 1px 3px rgba(0,0,0,0.1);" data-testid="youth-equal-score-voting">
        <h3 style="margin: 0 0 12px; font-size: 16px; font-weight: 600; color: #111827;">Izjednačeni kandidati</h3>
        @foreach($youthEqualScoreVotingBoard['groups'] as $group)
            @php
                $displayApps = $group['applications'] ?? [];
            @endphp
            <div style="margin-bottom: 20px; padding-bottom: 12px; border-bottom: 1px solid #e5e7eb;" data-testid="youth-equal-score-group">
                <p style="margin: 0 0 8px; color: #374151;">
                    Pozicija {{ $group['ranking_position'] }},
                    puni rezultat {{ $group['full_score'] }}.
                    Raspoloživo: {{ number_format((float) $group['remaining_before'], 2) }} EUR.
                </p>
                <table style="width: 100%; border-collapse: collapse; margin-bottom: 12px;">
                    <thead>
                        <tr>
                            <th style="text-align: left; padding: 8px; border-bottom: 1px solid #e5e7eb;">Prijava</th>
                            <th style="text-align: left; padding: 8px; border-bottom: 1px solid #e5e7eb;">Faza</th>
                            <th style="text-align: right; padding: 8px; border-bottom: 1px solid #e5e7eb;">Traženo</th>
                            <th style="text-align: right; padding: 8px; border-bottom: 1px solid #e5e7eb;">Odobreni iznos</th>
                            <th style="text-align: right; padding: 8px; border-bottom: 1px solid #e5e7eb;">Limit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($displayApps as $item)
                            <tr>
                                <td style="padding: 8px;">{{ $item['business_plan_name'] }}</td>
                                <td style="padding: 8px;">{{ $item['business_stage'] }}</td>
                                <td style="padding: 8px; text-align: right;">{{ $item['requested_amount'] !== null ? number_format((float) $item['requested_amount'], 2) : '—' }}</td>
                                <td style="padding: 8px; text-align: right;">
                                    @if(($item['approved_amount'] ?? null) !== null && ($item['approved_amount'] ?? '') !== '')
                                        {{ number_format((float) $item['approved_amount'], 2, ',', '.') }} €
                                    @else
                                        <span style="color: #6b7280;">-</span>
                                    @endif
                                </td>
                                <td style="padding: 8px; text-align: right;">{{ $item['applied_cap_percent'] !== null ? $item['applied_cap_percent'].'%' : '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endforeach
    </div>
@endif
