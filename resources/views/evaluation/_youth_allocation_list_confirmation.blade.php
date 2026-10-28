@php
    $youthAllocationConfirmationBoard = $youthAllocationConfirmationBoard ?? null;
@endphp
@if($youthAllocationConfirmationBoard && ($youthAllocationConfirmationBoard['visible'] ?? false) && ($youthAllocationConfirmationBoard['ranking_ready'] ?? false))
    <div class="info-card" style="background: #fff; border-radius: 16px; padding: 24px; margin: 24px 0 0; box-shadow: 0 1px 3px rgba(0,0,0,0.1);" data-testid="youth-allocation-list-confirmation">
        <h2 style="font-size: 20px; font-weight: 700; color: #0B3D91; margin: 0 0 20px; padding-bottom: 12px; border-bottom: 2px solid #e5e7eb;">Konačna lista raspodjele za mlade</h2>
        @if($youthAllocationConfirmationBoard['confirmed'] ?? false)
            <p style="margin: 0 0 8px; color: #065f46; font-weight: 600;">Lista je potvrđena i zaključana.</p>
            <p style="margin: 0 0 16px; color: #374151; font-size: 14px;">
                Potvrdio: {{ $youthAllocationConfirmationBoard['confirmed_by_name'] }}
                @if($youthAllocationConfirmationBoard['confirmed_at'])
                    — {{ $youthAllocationConfirmationBoard['confirmed_at'] }}
                @endif
            </p>
        @elseif($youthAllocationConfirmationBoard['block_reason'] ?? null)
            <p style="margin: 0 0 16px; color: #92400e; font-weight: 600;">{{ $youthAllocationConfirmationBoard['block_reason'] }}</p>
        @endif

        <p style="margin: 0 0 8px; font-size: 14px; font-weight: 600; color: #374151;">Podržane prijave</p>
        <ul style="margin: 0 0 16px; padding-left: 18px; font-size: 14px; color: #374151;">
            @forelse($youthAllocationConfirmationBoard['supported'] ?? [] as $supported)
                <li>
                    {{ $supported['business_plan_name'] }} — {{ number_format((float) $supported['amount'], 2, ',', '.') }} €
                    @if(filled($supported['justification'] ?? null))
                        — {{ $supported['justification'] }}
                    @endif
                </li>
            @empty
                <li>Nema podržanih prijava.</li>
            @endforelse
        </ul>

        <p style="margin: 0 0 8px; font-size: 14px; font-weight: 600; color: #374151;">Odbijene prijave</p>
        <ul style="margin: 0 0 16px; padding-left: 18px; font-size: 14px; color: #374151;">
            @forelse($youthAllocationConfirmationBoard['rejected'] ?? [] as $rejected)
                <li>
                    {{ $rejected['business_plan_name'] }} — Odbija
                    @if(filled($rejected['reason'] ?? $rejected['justification'] ?? null))
                        — {{ $rejected['reason'] ?? $rejected['justification'] }}
                    @endif
                </li>
            @empty
                <li>Nema odbijenih prijava.</li>
            @endforelse
        </ul>

        <div class="budget-info" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px;">
            <div class="budget-item" style="padding: 16px; background: #f9fafb; border-radius: 8px; text-align: center;">
                <div class="budget-label" style="font-size: 12px; color: #6b7280; margin-bottom: 8px;">Zbir odobrenih sredstava</div>
                <div class="budget-value" style="font-size: 20px; font-weight: 700; color: #0B3D91;">{{ number_format((float) ($youthAllocationConfirmationBoard['total_allocation'] ?? 0), 2, ',', '.') }} €</div>
            </div>
            <div class="budget-item" style="padding: 16px; background: #f9fafb; border-radius: 8px; text-align: center;">
                <div class="budget-label" style="font-size: 12px; color: #6b7280; margin-bottom: 8px;">Budžet poziva</div>
                <div class="budget-value" style="font-size: 20px; font-weight: 700; color: #0B3D91;">{{ number_format((float) ($youthAllocationConfirmationBoard['call_budget'] ?? 0), 2, ',', '.') }} €</div>
            </div>
            <div class="budget-item" style="padding: 16px; background: #f9fafb; border-radius: 8px; text-align: center;">
                <div class="budget-label" style="font-size: 12px; color: #6b7280; margin-bottom: 8px;">Raspoloživo</div>
                <div class="budget-value" style="font-size: 20px; font-weight: 700; color: #0B3D91;">{{ number_format((float) ($youthAllocationConfirmationBoard['remaining_budget'] ?? 0), 2, ',', '.') }} €</div>
            </div>
        </div>
    </div>
    <style>
        @media (max-width: 768px) {
            [data-testid="youth-allocation-list-confirmation"] .budget-info {
                grid-template-columns: 1fr !important;
            }
        }
    </style>
@endif
