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
        padding: 24px 0;
    }
    .page-header {
        background: linear-gradient(90deg, var(--primary), var(--primary-dark));
        color: #fff;
        padding: 24px;
        border-radius: 16px;
        margin-bottom: 24px;
    }
    .page-header h1 {
        color: #fff;
        font-size: 32px;
        font-weight: 700;
        margin: 0 0 8px;
    }
    .info-card {
        background: #fff;
        border-radius: 12px;
        padding: 24px;
        margin-bottom: 24px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    .info-card h2 {
        font-size: 20px;
        font-weight: 700;
        color: var(--primary);
        margin: 0 0 16px;
        padding-bottom: 12px;
        border-bottom: 2px solid #e5e7eb;
    }
    .info-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 16px;
    }
    @media (min-width: 768px) {
        .info-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    .info-item {
        display: flex;
        flex-direction: column;
    }
    .info-label {
        font-size: 12px;
        font-weight: 600;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 4px;
    }
    .info-value {
        font-size: 14px;
        color: #111827;
        font-weight: 500;
    }
    .status-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 9999px;
        font-size: 12px;
        font-weight: 600;
    }
    .status-open {
        background: #d1fae5;
        color: #065f46;
    }
    .status-closed {
        background: #fee2e2;
        color: #991b1b;
    }
    .deadline-alert {
        background: #fef3c7;
        border-left: 4px solid #f59e0b;
        padding: 16px 20px;
        border-radius: 8px;
        margin-bottom: 24px;
    }
    .deadline-alert strong {
        color: #92400e;
        font-size: 16px;
    }
    .documents-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    .documents-list li {
        padding: 12px 16px;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .documents-list li:last-child {
        border-bottom: none;
    }
    .documents-list li::before {
        content: "📄";
        font-size: 20px;
    }
    .btn-primary {
        background: var(--primary);
        color: #fff;
        padding: 12px 24px;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        font-size: 16px;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
        transition: background 0.2s;
    }
    .btn-primary:hover {
        background: var(--primary-dark);
    }
    .btn-primary:disabled {
        background: #9ca3af;
        cursor: not-allowed;
    }
    .alert {
        border-radius: 12px;
        padding: 16px 20px;
        margin-bottom: 20px;
        border: 1px solid;
    }
    .alert-info {
        background: #dbeafe;
        border-color: #3b82f6;
        color: #1e40af;
    }
</style>

<div class="competition-detail-page">
    <div class="container mx-auto px-4">
        <div class="page-header">
            <h1>{{ $competition->title }}</h1>
            <p style="color: rgba(255,255,255,0.9); margin: 0;">Detalji konkursa za podršku ženskom preduzetništvu</p>
        </div>

        @if($isOpen && $daysRemaining > 0)
            <div class="deadline-alert">
                <strong>⚠️ Preostalo vreme za prijavu: {{ $daysRemaining }} {{ $daysRemaining == 1 ? 'dan' : 'dana' }}</strong>
                <div style="font-size: 14px; color: #92400e; margin-top: 4px;">
                    Rok za podnošenje prijava: {{ $deadline->format('d.m.Y H:i') }}
                </div>
            </div>
        @elseif(!$isOpen)
            <div class="alert" style="background: #fee2e2; border-color: #ef4444; color: #991b1b;">
                <strong>Konkurs je zatvoren</strong> - rok za prijave je istekao.
            </div>
        @endif

        @if($userApplication)
            <div class="alert alert-info">
                <strong>Već ste podneli prijavu na ovaj konkurs.</strong>
                <a href="{{ route('applications.show', $userApplication) }}" style="color: #1e40af; text-decoration: underline; margin-left: 8px;">
                    Pregledajte status prijave
                </a>
            </div>
        @endif

        <!-- Osnovne informacije -->
        <div class="info-card">
            <h2>Osnovne informacije</h2>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Status konkursa</span>
                    <span class="info-value">
                        <span class="status-badge {{ $isOpen ? 'status-open' : 'status-closed' }}">
                            {{ $isOpen ? 'Otvoren' : 'Zatvoren' }}
                        </span>
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label">Broj konkursa</span>
                    <span class="info-value">{{ $competition->competition_number ?? 'N/A' }}. konkurs {{ $competition->year ?? date('Y') }}. godine</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Ukupan budžet</span>
                    <span class="info-value">{{ number_format($competition->budget ?? 0, 2, ',', '.') }} €</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Maksimalna podrška po biznis planu</span>
                    <span class="info-value">{{ $competition->max_support_percentage ?? 30 }}% ({{ number_format(($competition->budget ?? 0) * (($competition->max_support_percentage ?? 30) / 100), 2, ',', '.') }} €)</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Datum objavljivanja</span>
                    <span class="info-value">{{ $competition->published_at ? $competition->published_at->format('d.m.Y H:i') : 'N/A' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Rok za prijave</span>
                    <span class="info-value">{{ $deadline ? $deadline->format('d.m.Y H:i') : 'N/A' }}</span>
                </div>
            </div>
        </div>

        <!-- Opis konkursa -->
        @if($competition->description)
        <div class="info-card">
            <h2>Opis konkursa</h2>
            <div style="color: #374151; line-height: 1.8; white-space: pre-wrap;">
                {{ $competition->description }}
            </div>
        </div>
        @endif

        <!-- Obavezna dokumentacija -->
        <div class="info-card">
            <h2>Obavezna dokumentacija</h2>
            <p style="color: #6b7280; margin-bottom: 16px;">
                Prilikom prijave na konkurs, potrebno je priložiti sledeće dokumente:
            </p>
            <ul class="documents-list">
                @foreach($requiredDocuments as $document)
                    <li>{{ $document }}</li>
                @endforeach
            </ul>
            <p style="color: #6b7280; font-size: 12px; margin-top: 16px; font-style: italic;">
                Napomena: Lista dokumenata zavisi od tipa prijave (preduzetnica/DOO, započinjanje/razvoj). 
                Tačna lista će biti prikazana u formi za prijavu.
            </p>
        </div>

        <!-- Akcije -->
        <div class="info-card" style="text-align: center;">
            @if($isOpen && !$userApplication && auth()->check())
                <a href="{{ route('applications.create', $competition) }}" class="btn-primary">
                    Prijavi se na konkurs
                </a>
            @elseif(!auth()->check())
                <p style="color: #6b7280; margin-bottom: 16px;">
                    Za prijavu na konkurs potrebno je da budete prijavljeni.
                </p>
                <a href="{{ route('login') }}" class="btn-primary">Prijavite se</a>
            @elseif($userApplication)
                <a href="{{ route('applications.show', $userApplication) }}" class="btn-primary">
                    Pregledajte vašu prijavu
                </a>
            @else
                <button class="btn-primary" disabled>Konkurs je zatvoren</button>
            @endif
        </div>
    </div>
</div>
@endsection
