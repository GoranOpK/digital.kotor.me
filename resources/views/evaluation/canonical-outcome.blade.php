@extends('layouts.app')

@section('content')
@php
    $reasonLabels = \App\Models\CanonicalOutcome::REASON_LABELS;
    $isChair = $member->canonical_seat === \App\Services\Competitions\Commission\CanonicalCommissionGovernance::ROLE_PREDSJEDNIK;
    $confirmed = $finalRanking !== null;
@endphp
<div class="container mx-auto px-4 py-6" style="max-width: 1200px;">
    <h1 style="font-size: 24px; font-weight: 700; margin-bottom: 4px;">Treća sjednica Komisije — ishod, iznosi i konačna rang lista</h1>
    <p style="color: #4b5563; margin-bottom: 16px;">
        {{ $competition->title }} — budžet Poziva {{ number_format((float) $competition->budget, 2, ',', '.') }} €.
        Interni prikaz Komisije. Ništa sa ovog ekrana se ne objavljuje; ovo nije Odluka, ugovor niti nalog za isplatu.
    </p>

    @if(session('success'))
        <div style="background: #ecfdf5; border: 1px solid #10b981; padding: 12px; border-radius: 8px; margin-bottom: 16px;">{{ session('success') }}</div>
    @endif
    @if(session('warning'))
        <div style="background: #fffbeb; border: 1px solid #f59e0b; padding: 12px; border-radius: 8px; margin-bottom: 16px;">{{ session('warning') }}</div>
    @endif
    @if($errors->any())
        <div style="background: #fef2f2; border: 1px solid #ef4444; padding: 12px; border-radius: 8px; margin-bottom: 16px;">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    @if($session?->late)
        <div style="background: #fffbeb; border: 1px solid #f59e0b; padding: 12px; border-radius: 8px; margin-bottom: 16px;">{{ $session->compliance_warning }}</div>
    @endif

    <p style="margin-bottom: 12px;"><a href="{{ route('evaluation.canonical-sessions.show', $competition) }}">Sjednice Komisije</a></p>

    @if(! $rankingFormed)
        <div style="background: #fffbeb; border: 1px solid #f59e0b; padding: 12px; border-radius: 8px;">{{ \App\Services\Competitions\Outcome\CanonicalOutcomeService::RANKING_REQUIRED_MESSAGE }}</div>
    @elseif($sessionBlockReason !== null)
        <div style="background: #fffbeb; border: 1px solid #f59e0b; padding: 12px; border-radius: 8px;">{{ $sessionBlockReason }}</div>
    @else
        <p style="color: #374151; margin-bottom: 12px;">
            Redoslijed finansiranja prati zaključanu rang listu. Isti rang znači stvarno jednak rezultat; redoslijed unutar istog ranga je tehnički i ne daje prednost.
            Zakonski maksimum: min(20% budžeta, 80% / 50% ukupne vrijednosti ulaganja iz Obrasca P2).
        </p>

        <table style="width: 100%; border-collapse: collapse; margin-bottom: 24px;">
            <thead>
                <tr>
                    <th style="padding: 6px;">Rang</th>
                    <th style="text-align: left; padding: 6px;">Prijava</th>
                    <th style="padding: 6px;">Bodovi</th>
                    <th style="text-align: left; padding: 6px;">Vrsta subvencije</th>
                    <th style="padding: 6px;">Ulaganje (P2)</th>
                    <th style="padding: 6px;">Zakonski maksimum</th>
                    <th style="text-align: left; padding: 6px;">Ishod</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $row)
                    @php $outcome = $outcomes->get($row->id()); @endphp
                    <tr style="border-top: 1px solid #e5e7eb; vertical-align: top;">
                        <td style="padding: 6px; text-align: center;">{{ $row->rank }}</td>
                        <td style="padding: 6px;">#{{ $row->application->redni_broj ?? $row->id() }} {{ $row->application->business_plan_name ?? $row->application->founder_name }}</td>
                        <td style="padding: 6px; text-align: center;">{{ $row->display }}</td>
                        <td style="padding: 6px;">{{ $row->subsidyType && \App\Support\CanonicalSubsidyType::isValid($row->subsidyType) ? \App\Support\CanonicalSubsidyType::label($row->subsidyType) : '—' }}</td>
                        <td style="padding: 6px; text-align: right;">{{ $row->totalInvestment !== null ? number_format((float) $row->totalInvestment, 2, ',', '.') : '—' }}</td>
                        <td style="padding: 6px; text-align: right;">{{ $row->legalMaximum !== null ? number_format((float) $row->legalMaximum, 2, ',', '.') : '—' }}</td>
                        <td style="padding: 6px;">
                            @if($outcome)
                                <div>
                                    <strong>{{ $outcome->isSupport() ? 'Podržava' : 'Odbija' }}</strong>
                                    @if($outcome->isSupport()) — {{ number_format((float) $outcome->approved_amount, 2, ',', '.') }} €@endif
                                    @if($outcome->rejection_reason) — {{ $reasonLabels[$outcome->rejection_reason] ?? $outcome->rejection_reason }}@endif
                                    @if($outcome->confirmed_at) <small>(potvrđeno)</small>@else <small>(nacrt)</small>@endif
                                </div>
                            @endif
                            @if($isChair && ! $confirmed)
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
                                    <textarea name="remarks" rows="1" placeholder="Interne napomene" style="width: 100%;">{{ $outcome?->remarks }}</textarea>
                                    <button type="submit" class="btn-secondary">Sačuvaj nacrt</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <h2 style="font-size: 18px; font-weight: 700; margin-bottom: 8px;">Jednak rang na granici sredstava</h2>
        @forelse($resolutions as $resolution)
            <div style="border: 1px solid #e5e7eb; border-radius: 8px; padding: 10px; margin-bottom: 8px;">
                Rang {{ $resolution->shared_rank }} — skup #{{ implode(', #', $resolution->tiedIds()) }}; izabrano #{{ implode(', #', $resolution->selectedIds()) }};
                status: {{ $resolution->status }}; glasova: {{ $resolution->votes->count() }}/3
                @if($resolution->status === \App\Models\CanonicalTieResolution::PROPOSED && ! $confirmed && ! $resolution->votes->contains('commission_member_id', $member->id))
                    <form method="POST" action="{{ route('evaluation.canonical-outcome.tie.vote', [$competition, $resolution]) }}" style="display: inline;">
                        @csrf
                        <button type="submit" name="in_favour" value="1" class="btn-secondary">Za</button>
                        <button type="submit" name="in_favour" value="0" class="btn-secondary">Protiv</button>
                    </form>
                @endif
            </div>
        @empty
            <p style="color: #6b7280;">Nema prijedloga.</p>
        @endforelse
        @if($isChair && ! $confirmed)
            <form method="POST" action="{{ route('evaluation.canonical-outcome.tie.propose', $competition) }}" style="margin-bottom: 24px;">
                @csrf
                <input type="number" name="shared_rank" min="1" placeholder="Rang" required style="width: 80px;">
                @foreach($rows as $row)
                    <label style="margin-right: 8px;"><input type="checkbox" name="tied_application_ids[]" value="{{ $row->id() }}"> skup #{{ $row->id() }}</label>
                    <label style="margin-right: 12px;"><input type="checkbox" name="selected_application_ids[]" value="{{ $row->id() }}"> izbor #{{ $row->id() }}</label>
                @endforeach
                <button type="submit" class="btn-secondary">Predloži odluku</button>
            </form>
        @endif

        <h2 style="font-size: 18px; font-weight: 700; margin-bottom: 8px;">Konačna potvrda i potpisi</h2>
        @if(! $confirmed)
            @if($isChair)
                <form method="POST" action="{{ route('evaluation.canonical-outcome.confirm', $competition) }}">
                    @csrf
                    <button type="submit" class="btn-primary">Konačno potvrdi kompletan ishod</button>
                </form>
            @endif
        @else
            <p>Potvrđeno {{ $finalRanking->confirmed_at->timezone('Europe/Belgrade')->format('d.m.Y H:i') }}; ukupno podržano {{ number_format((float) $finalRanking->total_approved, 2, ',', '.') }} €; potpisa {{ $finalRanking->signatures->count() }}/3.</p>
            @if(! $finalRanking->isFinal() && ! $finalRanking->signatures->contains('commission_member_id', $member->id))
                <form method="POST" action="{{ route('evaluation.canonical-outcome.sign', $competition) }}">
                    @csrf
                    <button type="submit" class="btn-primary">Potpiši konačnu rang listu</button>
                </form>
            @endif
            <p style="margin-top: 8px;">
                @if($predlogBlockReason === null && $predlog)
                    <span>Predlog je spreman (interni skup podataka, nije objavljen).</span>
                    @if($isChair)
                        <a href="{{ route('evaluation.canonical-administrative.show', $competition) }}"
                           style="display:inline-block; margin-left:8px; padding:8px 12px; background:#0b3d91; color:#fff; border-radius:6px; text-decoration:none; font-weight:600;">
                            Finalizacija Predloga, predaja Sekretarijatu i zatvaranje
                        </a>
                    @else
                        <span style="display:block; margin-top:6px; color:#6b7280;">
                            Finalizaciju Predloga nastavlja predsjednik Komisije.
                        </span>
                    @endif
                @else
                    {{ $predlogBlockReason }}
                @endif
            </p>
        @endif
    @endif
</div>
@endsection
