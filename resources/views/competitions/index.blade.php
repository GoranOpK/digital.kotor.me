@extends('layouts.app')

@section('content')
<style>
    :root {
        --primary: #0B3D91;
        --primary-dark: #0A347B;
        --secondary: #B8860B;
    }
    .competitions-page {
        background: #f9fafb;
        min-height: 100vh;
        padding: 16px 0;
    }
    @media (min-width: 768px) {
        .competitions-page { padding: 24px 0; }
    }
    .page-header {
        background: linear-gradient(90deg, var(--primary), var(--primary-dark));
        color: #fff;
        padding: 20px;
        border-radius: 12px;
        margin-bottom: 20px;
    }
    @media (min-width: 768px) {
        .page-header { padding: 24px; border-radius: 16px; margin-bottom: 24px; }
    }
    .page-header h1 {
        color: #fff; font-size: 20px; font-weight: 700; margin: 0 0 4px;
    }
    @media (min-width: 768px) {
        .page-header h1 { font-size: 28px; }
    }
    .competitions-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 16px;
    }
    @media (min-width: 768px) {
        .competitions-grid { grid-template-columns: repeat(2, 1fr); gap: 24px; }
    }
    .competition-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 16px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        transition: all 0.2s;
        text-decoration: none;
        display: flex;
        flex-direction: column;
    }
    @media (min-width: 768px) {
        .competition-card { padding: 24px; border-radius: 16px; }
    }
    .competition-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        border-color: var(--primary);
    }
    .competition-card h3 {
        font-size: 16px; font-weight: 700; color: var(--primary); margin: 0 0 10px; line-height: 1.4;
    }
    @media (min-width: 768px) {
        .competition-card h3 { font-size: 18px; }
    }
    .meta-row {
        display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 12px; font-size: 12px; color: #6b7280;
    }
    .status-badge {
        display: inline-block; padding: 2px 8px; border-radius: 9999px; font-size: 10px; font-weight: 700;
    }
    .status-open { background: #d1fae5; color: #065f46; }
    .status-upcoming { background: #dbeafe; color: #1e40af; }
    .status-closed { background: #fee2e2; color: #991b1b; }

    .deadline-box {
        background: #fef3c7; padding: 10px; border-radius: 8px; margin-top: auto; border-left: 3px solid #f59e0b;
    }
    .deadline-box strong { font-size: 12px; color: #92400e; display: block; }
</style>

<div class="competitions-page">
    <div class="container mx-auto px-4">
        <div class="page-header">
            <h1>Aktuelni konkursi</h1>
            <p style="color: rgba(255,255,255,0.9); margin: 0; font-size: 13px;">Prijavite se za podršku ženskom preduzetništvu</p>
        </div>

        @if($competitions->count() > 0)
            <div class="competitions-grid">
                @foreach($competitions as $comp)
                    <a href="{{ route('competitions.show', $comp) }}" class="competition-card">
                        <h3>{{ $comp->title }}</h3>
                        <div class="meta-row">
                            <span>💰 {{ number_format($comp->budget ?? 0, 0, ',', '.') }} €</span>
                            <span class="status-badge {{ $comp->is_open ? 'status-open' : ($comp->is_upcoming ? 'status-upcoming' : 'status-closed') }}">
                                {{ $comp->is_open ? 'OTVOREN' : ($comp->is_upcoming ? 'USKORO' : 'ZATVOREN') }}
                            </span>
                        </div>
                        
                        <p style="font-size: 13px; color: #4b5563; margin-bottom: 15px; line-height: 1.5;">
                            {{ Str::limit($comp->description, 100) }}
                        </p>

                        @if($comp->is_upcoming)
                            <div class="deadline-box" style="background:#eff6ff; border-color:#3b82f6;">
                                <strong style="color:#1e40af;">Počinje: {{ $comp->start_date->format('d.m.Y') }}</strong>
                            </div>
                        @elseif($comp->is_open)
                            <div class="deadline-box" data-deadline="{{ $comp->deadline->format('Y-m-d H:i:s') }}">
                                <strong>Preostalo: <span class="cd-{{ $comp->id }}">...</span></strong>
                            </div>
                        @else
                            <div class="deadline-box" style="background:#f3f4f6; border-color:#9ca3af;">
                                <strong style="color:#4b5563;">Konkurs je završen</strong>
                            </div>
                        @endif
                    </a>
                @endforeach
            </div>
        @else
            <div style="text-align:center; padding:40px; background:#fff; border-radius:12px; border:1px solid #e5e7eb;">
                <p style="color:#6b7280;">Trenutno nema objavljenih konkursa.</p>
            </div>
        @endif
    </div>
</div>

<script>
    (function() {
        document.querySelectorAll('[data-deadline]').forEach(el => {
            const deadline = new Date(el.getAttribute('data-deadline')).getTime();
            const span = el.querySelector('span');
            function up() {
                const now = new Date().getTime();
                const d = deadline - now;
                if (d < 0) { span.textContent = 'Isteklo'; return; }
                const days = Math.floor(d / (86400000));
                const hours = Math.floor((d % 86400000) / 3600000);
                const mins = Math.floor((d % 3600000) / 60000);
                span.textContent = `${days}d ${hours}h ${mins}m`;
            }
            up(); setInterval(up, 60000);
        });
    })();
</script>
@endsection
