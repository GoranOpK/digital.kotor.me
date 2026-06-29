@extends('layouts.app')

@section('content')
<style>
    :root {
        --primary: #0B3D91;
        --primary-dark: #0A347B;
    }
    .admin-page {
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
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .page-header h1 {
        color: #fff;
        font-size: 28px;
        font-weight: 700;
        margin: 0;
    }
    .btn-primary {
        background: #fff;
        color: var(--primary);
        padding: 10px 20px;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.2s;
    }
    .btn-primary:hover {
        background: #f3f4f6;
    }
    .table-card {
        background: #fff;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    table {
        width: 100%;
        border-collapse: collapse;
    }
    th, td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid #e5e7eb;
    }
    th {
        font-weight: 600;
        color: #374151;
        font-size: 12px;
        text-transform: uppercase;
    }
    .status-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 9999px;
        font-size: 12px;
        font-weight: 600;
    }
    .status-draft { background: #fef3c7; color: #92400e; }
    .status-published { background: #d1fae5; color: #065f46; }
    .status-evaluating { background: #fef3c7; color: #92400e; }
    .status-closed { background: #fee2e2; color: #991b1b; }
    .status-completed { background: #dbeafe; color: #1e40af; }
    .btn-sm {
        padding: 6px 12px;
        font-size: 12px;
        border-radius: 6px;
        text-decoration: none;
        margin-right: 4px;
    }
    .btn-view { background: #3b82f6; color: #fff; }
    .btn-edit { background: #f59e0b; color: #fff; }
    .btn-delete { background: #ef4444; color: #fff; border: none; cursor: pointer; }
    .countdown-timer {
        font-family: monospace;
        font-weight: 700;
        color: #ef4444;
        font-size: 13px;
    }
    .program-context {
        margin-bottom: 16px;
    }
    .program-context a {
        color: var(--primary);
        text-decoration: none;
        font-weight: 600;
        font-size: 14px;
    }
    .program-context a:hover {
        text-decoration: underline;
    }
    .page-header-text h1 {
        margin: 0 0 4px;
    }
    .page-header-text p {
        margin: 0;
        opacity: 0.9;
        font-size: 14px;
    }
    .program-feature-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 24px;
        margin-bottom: 24px;
    }
    @media (min-width: 992px) {
        .program-feature-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    .program-feature-card {
        background: #fff;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        height: 100%;
    }
    .program-feature-card h2 {
        font-size: 20px;
        font-weight: 700;
        color: #111827;
        margin: 0 0 16px;
        padding-bottom: 12px;
        border-bottom: 2px solid #e5e7eb;
    }
    .program-feature-card p {
        margin: 0 0 10px;
        color: #374151;
        font-size: 14px;
        line-height: 1.5;
    }
    .program-feature-card .featured-title {
        font-size: 16px;
        font-weight: 700;
        color: var(--primary);
        margin-bottom: 12px;
    }
    .program-description {
        color: #374151;
        line-height: 1.8;
        white-space: pre-wrap;
        font-size: 14px;
    }
</style>

@php
    $listQuery = array_filter(['tab' => $tab ?? 'active', 'type' => $type ?? null]);
@endphp

<div class="admin-page">
    <div class="container mx-auto px-4">
        @if(!empty($typeLabel))
            <div class="program-context">
                <a href="{{ route('admin.dashboard') }}">← Konkursi</a>
            </div>
        @endif

        <div class="page-header">
            <div class="page-header-text">
                <h1>{{ !empty($typeLabel) ? $typeLabel : 'Upravljanje konkursima' }}</h1>
                @if(!empty($typeLabel))
                    <p>Lista konkursa za odabrani program</p>
                @endif
            </div>
            @if(isset($isAdmin) && $isAdmin)
                <a href="{{ route('admin.competitions.create', $type ? ['type' => $type] : []) }}" class="btn-primary">+ Novi konkurs</a>
            @endif
        </div>

        @if(!empty($type) && isset($featuredCompetition) && $featuredCompetition)
            @php
                $fc = $featuredCompetition;
                $fcStatusLabels = ['draft' => 'Nacrt', 'published' => 'Objavljen', 'closed' => 'Zatvoren', 'completed' => 'Završen'];
                if ($fc->is_open) {
                    $fcStatusText = 'Otvoren za prijave';
                    $fcStatusColor = '#065f46';
                } elseif ($fc->is_upcoming) {
                    $fcStatusText = 'Uskoro počinje';
                    $fcStatusColor = '#1e40af';
                } elseif ($fc->isApplicationDeadlinePassed()) {
                    $fcStatusText = 'Zatvoren za prijave';
                    $fcStatusColor = '#92400e';
                } else {
                    $fcStatusText = $fcStatusLabels[$fc->status] ?? $fc->status;
                    $fcStatusColor = '#374151';
                }
                $fcDaysLeft = $fc->getDaysUntilApplicationDeadline();
            @endphp
            <div class="program-feature-grid">
                <div class="program-feature-card">
                    <h2>Osnovne informacije</h2>
                    <p class="featured-title">
                        <a href="{{ route('admin.competitions.show', $fc) }}" style="color: var(--primary); text-decoration: none;">
                            {{ $fc->title }}
                        </a>
                    </p>
                    <p><strong>Status:</strong> <span style="color: {{ $fcStatusColor }}; font-weight: 600;">{{ $fcStatusText }}</span></p>
                    <p><strong>Broj konkursa:</strong> {{ $fc->upNumber?->number ?? 'N/A' }}</p>
                    <p><strong>Budžet:</strong> {{ number_format($fc->budget ?? 0, 2, ',', '.') }} €</p>
                    @if($fc->published_at)
                        <p><strong>Datum objave:</strong> {{ $fc->published_at->format('d.m.Y H:i') }}</p>
                    @endif
                    @if($fc->start_date)
                        <p><strong>Datum početka:</strong> {{ $fc->start_date->format('d.m.Y') }}</p>
                    @endif
                    @if($fc->deadline)
                        <p><strong>Rok za prijave:</strong> {{ $fc->deadline->format('d.m.Y H:i') }}</p>
                    @endif
                    @if($fcDaysLeft !== null && !$fc->isApplicationDeadlinePassed())
                        <p><strong>Preostalo za prijave:</strong> {{ $fcDaysLeft }} {{ $fcDaysLeft == 1 ? 'dan' : 'dana' }}</p>
                    @endif
                    @if($fc->commission)
                        <p><strong>Komisija:</strong>
                            <a href="{{ route('admin.commissions.show', $fc->commission) }}" style="color: var(--primary);">
                                {{ $fc->commission->name }} ({{ $fc->commission->year }})
                            </a>
                        </p>
                    @endif
                </div>
                <div class="program-feature-card">
                    <h2>Opis konkursa</h2>
                    @if($fc->description)
                        <div class="program-description">{{ $fc->description }}</div>
                    @else
                        <p style="color: #6b7280; margin: 0;">Opis nije unesen za trenutno aktivni konkurs.</p>
                    @endif
                </div>
            </div>
        @elseif(!empty($type))
            <div class="program-feature-card" style="margin-bottom: 24px;">
                <p style="color: #6b7280; margin: 0;">Trenutno nema objavljenog aktivnog konkursa za ovaj program.</p>
            </div>
        @endif

        <!-- Tabovi za aktivne i arhivirane konkursi -->
        <div style="background: #fff; border-radius: 16px; padding: 0; margin-bottom: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="display: flex; border-bottom: 2px solid #e5e7eb;">
                <a href="{{ route('admin.competitions.index', array_merge($listQuery, ['tab' => 'active'])) }}" 
                   style="flex: 1; padding: 16px 24px; text-align: center; text-decoration: none; font-weight: 600; color: {{ $tab === 'active' ? 'var(--primary)' : '#6b7280' }}; border-bottom: 3px solid {{ $tab === 'active' ? 'var(--primary)' : 'transparent' }}; transition: all 0.2s;">
                    Aktivni konkursi
                </a>
                <a href="{{ route('admin.competitions.index', array_merge($listQuery, ['tab' => 'archive'])) }}" 
                   style="flex: 1; padding: 16px 24px; text-align: center; text-decoration: none; font-weight: 600; color: {{ $tab === 'archive' ? 'var(--primary)' : '#6b7280' }}; border-bottom: 3px solid {{ $tab === 'archive' ? 'var(--primary)' : 'transparent' }}; transition: all 0.2s;">
                    Arhiva konkursa
                </a>
                <a href="{{ route('admin.competitions.index', array_merge($listQuery, ['tab' => 'all'])) }}" 
                   style="flex: 1; padding: 16px 24px; text-align: center; text-decoration: none; font-weight: 600; color: {{ $tab === 'all' ? 'var(--primary)' : '#6b7280' }}; border-bottom: 3px solid {{ $tab === 'all' ? 'var(--primary)' : 'transparent' }}; transition: all 0.2s;">
                    Svi konkursi
                </a>
            </div>
        </div>

        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>Naziv</th>
                        @if(empty($type))
                        <th>Tip</th>
                        @endif
                        <th>Preostalo vrijeme</th>
                        <th>Budžet</th>
                        <th>Status</th>
                        <th>Prijave</th>
                        <th>Akcije</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($competitions as $competition)
                        <tr>
                            <td>{{ $competition->title }}</td>
                            @if(empty($type))
                            <td>
                                @if($competition->type === 'zensko') Žensko preduzetništvo
                                @elseif($competition->type === 'omladinsko') Omladinsko preduzetništvo
                                @else Ostalo
                                @endif
                            </td>
                            @endif
                            <td>
                                @php
                                    $daysUntilApplicationDeadline = $competition->getDaysUntilApplicationDeadline();
                                    $daysUntilEvaluationDeadline = $competition->getDaysUntilEvaluationDeadline();
                                    $isApplicationDeadlinePassed = $competition->isApplicationDeadlinePassed();
                                    $isEvaluationDeadlinePassed = $competition->isEvaluationDeadlinePassed();
                                @endphp
                                @if($competition->status === 'published' && $daysUntilApplicationDeadline !== null)
                                    @if($competition->is_upcoming)
                                        <span style="color: #3b82f6; font-size: 13px; font-weight: 600;">Počinje za:</span>
                                        <div class="countdown-timer" data-deadline="{{ $competition->start_date->startOfDay()->format('Y-m-d H:i:s') }}" style="color: #3b82f6;">
                                            Učitavanje...
                                        </div>
                                    @elseif($isApplicationDeadlinePassed && $daysUntilEvaluationDeadline !== null)
                                        {{-- Prijave zatvorene, prikaži rok za donošenje odluke (30 dana) --}}
                                        <div style="display: flex; flex-direction: column; gap: 4px;">
                                            <span style="font-size: 12px; color: #6b7280; font-weight: 600;">Rok za odluku:</span>
                                            @if($isEvaluationDeadlinePassed)
                                                <span style="color: #991b1b; font-weight: 600; font-size: 14px;">❌ Isteklo (0 dana)</span>
                                            @else
                                                <span style="color: {{ $daysUntilEvaluationDeadline <= 3 ? '#991b1b' : ($daysUntilEvaluationDeadline <= 7 ? '#92400e' : '#065f46') }}; font-weight: 600; font-size: 14px;">
                                                    ⏰ {{ $daysUntilEvaluationDeadline }} {{ $daysUntilEvaluationDeadline == 1 ? 'dan' : ($daysUntilEvaluationDeadline < 5 ? 'dana' : 'dana') }}
                                                </span>
                                            @endif
                                            <div style="font-size: 11px; color: #6b7280;">
                                                Do: {{ $competition->getEvaluationDeadlineDate() ? $competition->getEvaluationDeadlineDate()->format('d.m.Y H:i') : 'N/A' }}
                                            </div>
                                        </div>
                                    @else
                                        <div style="display: flex; flex-direction: column; gap: 4px;">
                                            <span style="font-size: 12px; color: #6b7280; font-weight: 600;">Rok za prijave:</span>
                                            @if($isApplicationDeadlinePassed)
                                                <span style="color: #991b1b; font-weight: 600; font-size: 14px;">⚠️ Isteklo (0 dana)</span>
                                            @else
                                                <span style="color: {{ $daysUntilApplicationDeadline <= 3 ? '#991b1b' : ($daysUntilApplicationDeadline <= 7 ? '#92400e' : '#065f46') }}; font-weight: 600; font-size: 14px;">
                                                    📅 {{ $daysUntilApplicationDeadline }} {{ $daysUntilApplicationDeadline == 1 ? 'dan' : ($daysUntilApplicationDeadline < 5 ? 'dana' : 'dana') }}
                                                </span>
                                            @endif
                                            <div style="font-size: 11px; color: #6b7280;">
                                                Do: {{ $competition->deadline ? $competition->deadline->format('d.m.Y H:i') : 'N/A' }}
                                            </div>
                                        </div>
                                    @endif
                                @elseif($competition->status === 'closed' && $daysUntilEvaluationDeadline !== null)
                                    <div style="display: flex; flex-direction: column; gap: 4px;">
                                        <span style="font-size: 12px; color: #6b7280; font-weight: 600;">Rok za odluku:</span>
                                        @if($isEvaluationDeadlinePassed)
                                            <span style="color: #991b1b; font-weight: 600; font-size: 14px;">❌ Isteklo (0 dana)</span>
                                        @else
                                            <span style="color: {{ $daysUntilEvaluationDeadline <= 3 ? '#991b1b' : ($daysUntilEvaluationDeadline <= 7 ? '#92400e' : '#065f46') }}; font-weight: 600; font-size: 14px;">
                                                ⏰ {{ $daysUntilEvaluationDeadline }} {{ $daysUntilEvaluationDeadline == 1 ? 'dan' : ($daysUntilEvaluationDeadline < 5 ? 'dana' : 'dana') }}
                                            </span>
                                        @endif
                                        <div style="font-size: 11px; color: #6b7280;">
                                            Do: {{ $competition->closed_at ? $competition->closed_at->copy()->addDays(45)->format('d.m.Y H:i') : 'N/A' }}
                                        </div>
                                    </div>
                                @else
                                    <span style="color: #6b7280; font-size: 13px;">Predviđeno: {{ $competition->deadline_days }} dana</span>
                                @endif
                            </td>
                            <td>{{ number_format($competition->budget ?? 0, 2, ',', '.') }} €</td>
                            <td>
                                <span class="status-badge status-{{ $competition->status === 'published' && $isApplicationDeadlinePassed ? 'evaluating' : $competition->status }}">
                                    @if($competition->status === 'draft') Nacrt
                                    @elseif($competition->status === 'published' && $isApplicationDeadlinePassed) Zatvoren za prijave
                                    @elseif($competition->status === 'published') Objavljen
                                    @elseif($competition->status === 'closed') Zatvoren
                                    @elseif($competition->status === 'completed') Završen
                                    @else {{ $competition->status }}
                                    @endif
                                </span>
                            </td>
                            <td>{{ $competition->applications_count }}</td>
                            <td>
                                <a href="{{ route('admin.competitions.show', $competition) }}" class="btn-sm btn-view">Pregled</a>
                                @if(isset($isAdmin) && $isAdmin && !in_array($competition->status, ['closed', 'completed']))
                                    <a href="{{ route('admin.competitions.edit', $competition) }}" class="btn-sm btn-edit">Izmijeni</a>
                                    <form action="{{ route('admin.competitions.destroy', $competition) }}" method="POST" style="display: inline;" onsubmit="return confirm('Da li ste sigurni da želite da obrišete ovaj konkurs?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-sm btn-delete">Obriši</button>
                                    </form>
                                @endif
                                @if(isset($isAdmin) && $isAdmin && in_array($competition->status, ['closed', 'completed']))
                                    <form action="{{ route('admin.competitions.destroy', $competition) }}" method="POST" style="display: inline;" onsubmit="return confirm('Da li ste sigurni da želite da obrišete ovaj konkurs iz arhive? Ova akcija je nepovratna.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-sm btn-delete">Obriši</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ empty($type) ? 7 : 6 }}" style="text-align: center; padding: 40px; color: #6b7280;">
                                @if($tab === 'archive')
                                    Nema arhiviranih konkursa.
                                @elseif($tab === 'all')
                                    Nema konkursa u sistemu.
                                @else
                                    Nema aktivnih konkursa.
                                    @if(isset($isAdmin) && $isAdmin)
                                        <a href="{{ route('admin.competitions.create', $type ? ['type' => $type] : []) }}">Kreiraj prvi konkurs</a>
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div style="margin-top: 20px;">
                {{ $competitions->links() }}
            </div>
        </div>
    </div>
</div>

<script>
    function updateCountdowns() {
        const timers = document.querySelectorAll('.countdown-timer');
        
        timers.forEach(timer => {
            const deadlineStr = timer.getAttribute('data-deadline');
            const deadline = new Date(deadlineStr).getTime();
            const now = new Date().getTime();
            const distance = deadline - now;
            
            if (distance < 0) {
                timer.innerHTML = "ISTEKLO";
                timer.style.color = "#dc2626";
                return;
            }
            
            const days = Math.floor(distance / (1000 * 60 * 60 * 24));
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);
            
            let html = "";
            if (days > 0) html += days + "d ";
            html += (hours < 10 ? "0" + hours : hours) + "h " + 
                    (minutes < 10 ? "0" + minutes : minutes) + "m " + 
                    (seconds < 10 ? "0" + seconds : seconds) + "s";
            
            timer.innerHTML = html;
            
            // Boja upozorenja ako je manje od 24h
            if (distance < (1000 * 60 * 60 * 24)) {
                timer.style.color = "#ef4444";
            } else {
                timer.style.color = "#059669";
            }
        });
    }

    // Pokreni odmah i postavi interval
    updateCountdowns();
    setInterval(updateCountdowns, 1000);
</script>
@endsection

