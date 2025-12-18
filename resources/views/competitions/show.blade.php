@extends('layouts.app')

@section('content')
<style>
    :root {
        --primary: #0B3D91;
        --primary-dark: #0A347B;
        --secondary: #B8860B;
    }
    .competition-detail-page {
        background: #f9fafb;
        min-height: 100vh;
        padding: 16px 0;
    }
    @media (min-width: 768px) {
        .competition-detail-page { padding: 24px 0; }
    }
    .page-header {
        background: linear-gradient(90deg, var(--primary), var(--primary-dark));
        color: #fff;
        padding: 20px;
        border-radius: 12px;
        margin-bottom: 20px;
    }
    @media (min-width: 768px) {
        .page-header { padding: 24px; border-radius: 16px; }
    }
    .page-header h1 {
        color: #fff; font-size: 18px; font-weight: 700; line-height: 1.3; margin: 0 0 4px;
    }
    @media (min-width: 768px) {
        .page-header h1 { font-size: 24px; }
    }
    .info-card {
        background: #fff; border-radius: 12px; padding: 16px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 16px;
    }
    @media (min-width: 768px) {
        .info-card { border-radius: 16px; padding: 24px; margin-bottom: 24px; }
    }
    .info-card h2 {
        font-size: 16px; font-weight: 700; color: var(--primary);
        margin: 0 0 16px; padding-bottom: 10px; border-bottom: 2px solid #e5e7eb;
    }
    .summary-grid {
        display: grid; grid-template-columns: 1fr; gap: 16px; margin-bottom: 16px;
    }
    @media (min-width: 1024px) {
        .summary-grid { grid-template-columns: 1.5fr 1fr; gap: 24px; }
    }
    .info-item { display: flex; flex-direction: column; margin-bottom: 12px; }
    .info-label { font-size: 10px; font-weight: 600; color: #6b7280; text-transform: uppercase; margin-bottom: 2px; }
    .info-value { font-size: 13px; color: #111827; font-weight: 500; }

    .documents-list { list-style: none; padding: 0; margin: 0; }
    .documents-list li {
        padding: 8px 0; border-bottom: 1px solid #f3f4f6;
        display: flex; align-items: flex-start; gap: 10px; font-size: 12px; color: #374151;
    }
    .documents-list li::before { content: "📄"; font-size: 14px; flex-shrink: 0; }

    .btn-primary {
        background: var(--primary); color: #fff; padding: 12px 20px;
        border-radius: 8px; font-weight: 600; font-size: 15px;
        text-decoration: none; display: block; text-align: center;
    }
    @media (min-width: 640px) { .btn-primary { width: auto; padding: 12px 40px; margin: 0 auto; } }
</style>

<div class="competition-detail-page">
    <div class="container mx-auto px-4">
        <div class="page-header">
            <h1>{{ $competition->title }}</h1>
            <p style="color:rgba(255,255,255,0.8); font-size:12px; margin:0;">Kotor Digital • Konkursi</p>
        </div>

        @if($isUpcoming)
            <div class="info-card" style="background:#eff6ff; border-left:4px solid #3b82f6;">
                <strong style="color:#1e40af; font-size:14px;">🔜 Konkurs uskoro počinje</strong>
                <p style="margin:4px 0 0; font-size:13px; color:#1e40af;">Prijave se otvaraju: {{ $competition->start_date->format('d.m.Y') }}</p>
            </div>
        @elseif($isOpen)
            <div class="info-card" style="background:#fef3c7; border-left:4px solid #f59e0b;">
                <strong style="color:#92400e; font-size:14px;">⚠️ Preostalo za prijavu: <span id="cd-timer">...</span></strong>
                <p style="margin:4px 0 0; font-size:12px; color:#92400e;">Rok: {{ $deadline->format('d.m.Y H:i') }}</p>
            </div>
            <script>
                (function(){
                    const d = new Date('{{ $deadline->format('Y-m-d H:i:s') }}').getTime();
                    const el = document.getElementById('cd-timer');
                    function u(){
                        const n = new Date().getTime(); const dist = d - n;
                        if(dist < 0){ el.textContent = 'Isteklo'; return; }
                        const dd = Math.floor(dist/86400000); const hh = Math.floor((dist%86400000)/3600000); const mm = Math.floor((dist%3600000)/60000);
                        el.textContent = `${dd}d ${hh}h ${mm}m`;
                    } u(); setInterval(u, 60000);
                })();
            </script>
        @endif

        <div class="summary-grid">
            <div class="info-card">
                <h2>Osnovne informacije</h2>
                <div style="display:grid; grid-template-columns: repeat(2, 1fr); gap:15px;">
                    <div class="info-item">
                        <span class="info-label">Budžet</span>
                        <span class="info-value" style="color:var(--primary); font-weight:700;">{{ number_format($competition->budget ?? 0, 0, ',', '.') }} €</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Maks. podrška</span>
                        <span class="info-value">{{ $competition->max_support_percentage ?? 30 }}%</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Datum objave</span>
                        <span class="info-value">{{ $competition->published_at ? $competition->published_at->format('d.m.Y') : '-' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Broj</span>
                        <span class="info-value">{{ $competition->competition_number ?? 'N/A' }}/{{ $competition->year }}</span>
                    </div>
                </div>
            </div>

            <div class="info-card">
                <h2>Potrebna dokumentacija</h2>
                <ul class="documents-list">
                    @foreach($requiredDocuments as $doc)
                        <li>{{ Str::limit($doc, 45) }}</li>
                    @endforeach
                </ul>
            </div>
        </div>

        <div class="info-card">
            <h2>Opis i prioriteti</h2>
            <div style="font-size:13px; color:#4b5563; line-height:1.6; white-space:pre-wrap; margin-bottom:15px;">
                {{ $competition->description }}
            </div>
            <div style="background:#f9fafb; padding:12px; border-radius:8px; font-size:12px;">
                <p><strong>Napomena:</strong> Prijave se podnose isključivo elektronskim putem kroz ovaj portal.</p>
            </div>
        </div>

        <div style="margin-bottom:40px;">
            @if($isOpen && !$userApplication && auth()->check())
                <a href="{{ route('applications.create', $competition) }}" class="btn-primary">KREIRAJ PRIJAVU →</a>
            @elseif($userApplication)
                <a href="{{ route('applications.show', $userApplication) }}" class="btn-primary" style="background:var(--secondary);">POGLEDAJ MOJU PRIJAVU</a>
            @elseif(!auth()->check())
                <a href="{{ route('login') }}" class="btn-primary">PRIJAVI SE ZA APLICIRANJE</a>
            @else
                <button class="btn-primary" style="background:#9ca3af; cursor:not-allowed;" disabled>PRIJAVE SU ZATVORENE</button>
            @endif
        </div>
    </div>
</div>
@endsection
