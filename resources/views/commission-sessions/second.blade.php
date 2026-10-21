@extends('layouts.app')

@section('content')
<style>
    .session-page { background: #f9fafb; min-height: 100vh; padding: 24px 0; }
    .session-card { background: #fff; border-radius: 16px; padding: 32px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); max-width: 720px; margin: 0 auto; }
    .session-card h1 { font-size: 24px; font-weight: 700; color: #0B3D91; margin: 0 0 16px; }
</style>
<div class="session-page">
    <div class="session-card">
        <h1>Usmeno predstavljanje</h1>
        <p style="color:#4b5563; font-size:14px; margin-bottom:20px;">
            {{ $competition->title }} — Sjednice Komisije vode se van Digitala. Platforma ne traži unos datuma, prisustva ni kvoruma sjednice.
        </p>

        @if($gateMessage)
            <div style="background:#fef3c7; border:1px solid #f59e0b; color:#92400e; padding:12px; border-radius:8px; margin-bottom:16px;">
                {{ $gateMessage }}
            </div>
        @endif

        @if(session('success'))
            <div style="background:#d1fae5; border:1px solid #10b981; color:#065f46; padding:12px; border-radius:8px; margin-bottom:16px;">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div style="background:#fee2e2; border:1px solid #ef4444; color:#991b1b; padding:12px; border-radius:8px; margin-bottom:16px;">
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <h2 style="font-size:18px; font-weight:700; color:#0B3D91; margin:8px 0 12px;">Prijave za usmeno predstavljanje</h2>
        @if($eligibleApplications->isEmpty())
            <p style="color:#6b7280;">Nema prijava koje trenutno ispunjavaju uslove za usmeno predstavljanje.</p>
        @else
            <ul>
                @foreach($eligibleApplications as $eligibleApplication)
                    @php $oral = $eligibleApplication->oralPresentation; @endphp
                    <li style="margin-bottom:8px;">
                        {{ $eligibleApplication->business_plan_name }}
                        —
                        @if($oral?->isCompleted())
                            završeno
                        @elseif($oral)
                            nacrt
                        @else
                            nije evidentirano
                        @endif
                        <a href="{{ route('commission-sessions.oral.edit', [$competition, $eligibleApplication]) }}">Evidencija usmenog</a>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
@endsection
