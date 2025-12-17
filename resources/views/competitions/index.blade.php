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
    .competitions-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 24px;
    }
    @media (min-width: 768px) {
        .competitions-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    .competition-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        transition: all 0.2s;
        text-decoration: none;
        display: block;
    }
    .competition-card:hover {
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        transform: translateY(-2px);
        border-color: var(--primary);
    }
    .competition-card h3 {
        font-size: 20px;
        font-weight: 700;
        color: var(--primary);
        margin: 0 0 12px;
    }
    .competition-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 16px;
        margin-bottom: 16px;
        font-size: 14px;
        color: #6b7280;
    }
    .meta-item {
        display: flex;
        align-items: center;
        gap: 6px;
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
    .deadline-info {
        background: #fef3c7;
        border-left: 4px solid #f59e0b;
        padding: 12px 16px;
        border-radius: 8px;
        margin-top: 16px;
    }
    .deadline-info strong {
        color: #92400e;
    }
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        background: #fff;
        border-radius: 16px;
        border: 1px solid #e5e7eb;
    }
    .empty-state-icon {
        font-size: 64px;
        margin-bottom: 16px;
    }
</style>

<div class="competitions-page">
    <div class="container mx-auto px-4">
        <div class="page-header">
            <h1>Konkursi za podršku ženskom preduzetništvu</h1>
            <p style="color: rgba(255,255,255,0.9); margin: 0;">Pregled aktivnih konkursa za podršku ženskom preduzetništvu iz budžeta Opštine Kotor</p>
        </div>

        @if($competitions->count() > 0)
            <div class="competitions-grid">
                @foreach($competitions as $competition)
                    <a href="{{ route('competitions.show', $competition) }}" class="competition-card">
                        <h3>{{ $competition->title }}</h3>
                        
                        <div class="competition-meta">
                            <div class="meta-item">
                                <span>📅</span>
                                <span>Objavljen: {{ $competition->published_at ? $competition->published_at->format('d.m.Y') : 'N/A' }}</span>
                            </div>
                            <div class="meta-item">
                                <span>💰</span>
                                <span>Budžet: {{ number_format($competition->budget ?? 0, 2, ',', '.') }} €</span>
                            </div>
                            <div class="meta-item">
                                <span class="status-badge {{ $competition->is_open ? 'status-open' : 'status-closed' }}">
                                    {{ $competition->is_open ? 'Otvoren' : 'Zatvoren' }}
                                </span>
                            </div>
                        </div>

                        @if($competition->description)
                            <p style="color: #374151; margin: 0 0 16px; line-height: 1.6;">
                                {{ Str::limit($competition->description, 150) }}
                            </p>
                        @endif

                        @if($competition->is_open && $competition->days_remaining > 0)
                            <div class="deadline-info">
                                <strong>Preostalo vreme za prijavu: {{ $competition->days_remaining }} {{ $competition->days_remaining == 1 ? 'dan' : 'dana' }}</strong>
                                <div style="font-size: 12px; color: #92400e; margin-top: 4px;">
                                    Rok za prijave: {{ $competition->deadline->format('d.m.Y H:i') }}
                                </div>
                            </div>
                        @elseif(!$competition->is_open)
                            <div style="background: #fee2e2; border-left: 4px solid #ef4444; padding: 12px 16px; border-radius: 8px; margin-top: 16px;">
                                <strong style="color: #991b1b;">Konkurs je zatvoren</strong>
                            </div>
                        @endif
                    </a>
                @endforeach
            </div>
        @else
            <div class="empty-state">
                <div class="empty-state-icon">📋</div>
                <h3 style="color: #374151; margin-bottom: 8px;">Trenutno nema aktivnih konkursa</h3>
                <p style="color: #6b7280; margin: 0;">Novi konkursi će biti objavljeni kada budu raspisani.</p>
            </div>
        @endif
    </div>
</div>
@endsection
