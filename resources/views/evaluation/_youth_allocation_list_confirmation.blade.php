@php
    $youthAllocationConfirmationBoard = $youthAllocationConfirmationBoard ?? null;
@endphp
@if($youthAllocationConfirmationBoard && ($youthAllocationConfirmationBoard['visible'] ?? false) && ($youthAllocationConfirmationBoard['ranking_ready'] ?? false))
    <div class="info-card" style="background: #fff; border-radius: 12px; padding: 16px 20px; margin: 16px 0 0; border-left: 4px solid #7c3aed; box-shadow: 0 1px 3px rgba(0,0,0,0.1);" data-testid="youth-allocation-list-confirmation">
        <h3 style="margin: 0 0 12px; font-size: 16px; font-weight: 600; color: #111827;">Konačna lista raspodjele za mlade</h3>
        @if($youthAllocationConfirmationBoard['confirmed'] ?? false)
            <p style="margin: 0 0 8px; color: #065f46; font-weight: 600;">Lista je potvrđena i zaključana.</p>
            <p style="margin: 0 0 12px; color: #374151; font-size: 13px;">
                Potvrdio: {{ $youthAllocationConfirmationBoard['confirmed_by_name'] }}
                @if($youthAllocationConfirmationBoard['confirmed_at'])
                    — {{ $youthAllocationConfirmationBoard['confirmed_at'] }}
                @endif
            </p>
        @elseif($youthAllocationConfirmationBoard['block_reason'] ?? null)
            <p style="margin: 0 0 12px; color: #92400e; font-weight: 600;">{{ $youthAllocationConfirmationBoard['block_reason'] }}</p>
        @endif
        <p style="margin: 0 0 8px; font-size: 13px; color: #374151;">Podržane prijave mladih: {{ count($youthAllocationConfirmationBoard['supported'] ?? []) }}</p>
        <ul style="margin: 0 0 12px; padding-left: 18px; font-size: 13px;">
            @forelse($youthAllocationConfirmationBoard['supported'] ?? [] as $supported)
                <li>{{ $supported['business_plan_name'] }} — {{ number_format((float) $supported['amount'], 2, ',', '.') }} EUR</li>
            @empty
                <li>Nema podržanih prijava.</li>
            @endforelse
        </ul>
        <p style="margin: 0 0 8px; font-size: 13px; color: #374151;">Odbijene prijave: {{ count($youthAllocationConfirmationBoard['rejected'] ?? []) }}</p>
        <p style="margin: 0 0 4px; font-size: 13px;">Zbir raspodjele: {{ number_format((float) ($youthAllocationConfirmationBoard['total_allocation'] ?? 0), 2, ',', '.') }} EUR</p>
        <p style="margin: 0 0 4px; font-size: 13px;">Preostali budžet: {{ number_format((float) ($youthAllocationConfirmationBoard['remaining_budget'] ?? 0), 2, ',', '.') }} EUR</p>
        <p style="margin: 0 0 12px; font-size: 13px;">Riješene granične grupe: {{ (int) ($youthAllocationConfirmationBoard['resolved_groups'] ?? 0) }}</p>
        @if($youthAllocationConfirmationBoard['can_confirm'] ?? false)
            <p style="margin: 0 0 12px; color: #991b1b; font-weight: 600;">Potvrda zaključava cijelu listu raspodjele za mlade i iznose. Izmjena nacrta, krugova i glasova poslije potvrde nije dozvoljena.</p>
            <form method="POST" action="{{ route('evaluation.youth-allocation-list', $youthEqualScoreCompetition) }}" class="no-print" data-testid="youth-allocation-list-confirm-form" onsubmit="return confirm('Potvrda zaključava cijelu listu raspodjele za mlade i iznose. Da li želite da nastavite?');">
                @csrf
                <button type="submit" class="btn-primary">Potvrdi konačnu listu raspodjele za mlade</button>
            </form>
        @endif
    </div>
@endif
