@extends('layouts.app')

@section('content')
@include('partials.canonical-ui')
@php
    $G = \App\Services\Competitions\Commission\CanonicalCommissionGovernance::class;
    $box = 'border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; margin-bottom: 16px; background: #fff;';
    $warn = 'background: #fffbeb; border: 1px solid #f59e0b; padding: 10px; border-radius: 8px; margin-bottom: 8px;';
    $bad = 'background: #fef2f2; border: 1px solid #ef4444; padding: 10px; border-radius: 8px; margin-bottom: 8px;';
    $ok = 'background: #ecfdf5; border: 1px solid #10b981; padding: 10px; border-radius: 8px; margin-bottom: 8px;';
    $fmt = fn ($at) => $at ? $at->copy()->timezone($timezone)->format('d.m.Y H:i:s') : '—';
    $canAct = ! $readOnly && $actionBlock === null;
@endphp
<div class="container mx-auto px-4 py-6 canonical-ui-page" style="max-width: 1100px;">
    <h1 style="font-size: 24px; font-weight: 700; margin-bottom: 4px;">Sjednice kanonske Komisije</h1>
    <p style="color: #4b5563; margin-bottom: 16px;">
        {{ $competition->title }}. Sjednicu evidentira i potvrđuje predsjednik Komisije. Intervju i treća sjednica zahtijevaju prisustvo sva 3 važeća člana
        (lično, daljinski ili elektronski); redovna sjednica najmanje 2 od 3. Potvrđena sjednica je zaključana.
    </p>
    @if($readOnly)
        <div style="{{ $warn }}">Pregled za administratora: samo čitanje. Radnje sjednice vrši isključivo Komisija.</div>
    @elseif($actionBlock !== null)
        <div style="{{ $warn }}">{{ $actionBlock }}</div>
    @endif

    @if(session('success'))
        <div style="{{ $ok }}">{{ session('success') }}</div>
    @endif
    @if(session('warning'))
        <div style="{{ $warn }}">{{ session('warning') }}</div>
    @endif
    @if($errors->any())
        <div style="{{ $bad }}">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div style="{{ $box }}" data-section="canonical-session-seats">
        <h2 style="font-size: 18px; font-weight: 600;">Trenutni sastav Komisije</h2>
        <ul>
            @foreach($seats as $seatNo => $member)
                <li>
                    Mjesto {{ $seatNo }}:
                    @if($member)
                        {{ $member->name }} — {{ $G::roleLabel($member->canonical_seat) }}{{ $member->is_substitute ? ' (zamjenski član)' : '' }}
                    @else
                        upražnjeno / nepopunjeno
                    @endif
                </li>
            @endforeach
        </ul>
    </div>

    @foreach($stages as $purpose => $stage)
        @php $session = $stage['session']; @endphp
        <div style="{{ $box }}" data-session-purpose="{{ $purpose }}">
            <h2 style="font-size: 18px; font-weight: 600;">{{ $stage['label'] }}</h2>
            <p>
                Status:
                @if($session === null)
                    nije evidentirana
                @elseif($session->isConfirmed())
                    <strong>potvrđena i zaključana</strong>
                @else
                    evidentirana — čeka potvrdu
                @endif
                · Potrebno prisustvo: {{ $stage['required'] }} od 3
            </p>

            @if($purpose === $G::PURPOSE_FINAL_DECISION && $finalDueAt)
                <p>Rok za treću sjednicu (7 dana od potvrde intervjua, tačno vrijeme): {{ $fmt($finalDueAt) }}</p>
                @if($finalOverdueNow)
                    <div style="{{ $warn }}">Rok je istekao. Sjednica se i dalje evidentira, uz trajno upozorenje o kašnjenju.</div>
                @endif
            @endif

            @if($session)
                <p>
                    Održana: {{ $fmt($session->held_at) }} · evidentirao: {{ $session->recordedBy?->name ?? '—' }}
                    @if($session->due_at) · rok: {{ $fmt($session->due_at) }} @endif
                </p>
                @if($session->late)
                    <div style="{{ $warn }}" data-late-warning>Kašnjenje: {{ $session->compliance_warning }}</div>
                @endif

                <table style="width: 100%; border-collapse: collapse; margin: 8px 0;">
                    <thead><tr><th align="left">Mjesto</th><th align="left">Član</th><th align="left">Prisustvo</th><th align="left">Važenje</th></tr></thead>
                    <tbody>
                        @foreach($session->attendances->sortBy(fn ($row) => $row->member?->canonical_seat_no) as $row)
                            <tr>
                                <td>{{ $row->member?->canonical_seat_no ?? '—' }}</td>
                                <td>{{ $row->member?->name ?? '—' }}</td>
                                <td>{{ $row->present ? ($attendanceModes[$row->attendance_mode] ?? $row->attendance_mode) : 'Odsutan' }}</td>
                                <td>
                                    @if(! $session->isConfirmed() && ($row->member?->status !== 'active' || $row->member?->replaced_at))
                                        ne računa se (član više nije važeći)
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <p>Važećih prisutnih: {{ $stage['presentCount'] }} od potrebnih {{ $stage['required'] }}.</p>
                @if($stage['missingSeats'] !== [])
                    <p>Nedostaje prisustvo za mjesto: {{ implode(', ', $stage['missingSeats']) }}</p>
                @endif

                @if($session->isConfirmed())
                    <div style="{{ $ok }}">
                        Potvrđena: {{ $fmt($session->completed_at) }}@if($stage['confirmedBy']) · potvrdio: {{ $stage['confirmedBy'] }}@endif.
                        Prisustvo, datum i potvrda se više ne mogu mijenjati.
                    </div>
                @else
                    @if($stage['quorumBlock'])
                        <div style="{{ $warn }}">{{ $stage['quorumBlock'] }}</div>
                    @endif
                    @if($canAct)
                        <form method="POST" action="{{ route('evaluation.canonical-sessions.attendance', [$competition, $session]) }}" style="margin-bottom: 8px;">
                            @csrf
                            @include('evaluation.partials.canonical-session-attendance', ['current' => $session->attendances->keyBy('commission_member_id')])
                            <button type="submit" class="btn btn-secondary">Ažuriraj prisustvo</button>
                        </form>
                        <form method="POST" action="{{ route('evaluation.canonical-sessions.confirm', [$competition, $session]) }}">
                            @csrf
                            <button type="submit" class="btn btn-primary">Potvrdi i zaključaj sjednicu</button>
                        </form>
                    @endif
                @endif
            @else
                @if($stage['prerequisiteBlock'])
                    <div style="{{ $warn }}">{{ $stage['prerequisiteBlock'] }}</div>
                @elseif($canAct)
                    <form method="POST" action="{{ route('evaluation.canonical-sessions.record', $competition) }}">
                        @csrf
                        <input type="hidden" name="purpose" value="{{ $purpose }}">
                        <label>Datum i vrijeme održavanja
                            <input type="datetime-local" name="held_at" step="1" required value="{{ old('purpose') === $purpose ? old('held_at') : '' }}">
                        </label>
                        @include('evaluation.partials.canonical-session-attendance', ['current' => collect()])
                        <label>Napomena <textarea name="notes" rows="2" maxlength="5000" style="width: 100%;"></textarea></label>
                        <button type="submit" class="btn btn-primary">Evidentiraj sjednicu</button>
                    </form>
                @endif
            @endif
        </div>
    @endforeach

    @unless($readOnly)
        <p><a href="{{ route('evaluation.canonical-outcome.show', $competition) }}">Treća sjednica — ishod i iznosi</a></p>
    @endunless
</div>
@endsection
