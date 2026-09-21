@php
    $youthRankingView = $youthRankingView ?? null;
    $youthRankingTitle = $youthRankingTitle ?? null;
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
            <p style="margin: 0; color: #6b7280; font-size: 13px;">Ova lista nije konačna odluka o podršci.</p>
        @endif
    </div>
@endif
