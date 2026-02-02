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
</style>

<div class="admin-page">
    <div class="container mx-auto px-4">
        <div class="page-header">
            <h1>Upravljanje konkursima</h1>
            @if(isset($isAdmin) && $isAdmin)
                <a href="{{ route('admin.competitions.create') }}" class="btn-primary">+ Novi konkurs</a>
            @endif
        </div>

        <!-- Tabovi za aktivne i arhivirane konkursi -->
        <div style="background: #fff; border-radius: 16px; padding: 0; margin-bottom: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="display: flex; border-bottom: 2px solid #e5e7eb;">
                <a href="{{ route('admin.competitions.index', ['tab' => 'active']) }}" 
                   style="flex: 1; padding: 16px 24px; text-align: center; text-decoration: none; font-weight: 600; color: {{ $tab === 'active' ? 'var(--primary)' : '#6b7280' }}; border-bottom: 3px solid {{ $tab === 'active' ? 'var(--primary)' : 'transparent' }}; transition: all 0.2s;">
                    Aktivni konkursi
                </a>
                <a href="{{ route('admin.competitions.index', ['tab' => 'archive']) }}" 
                   style="flex: 1; padding: 16px 24px; text-align: center; text-decoration: none; font-weight: 600; color: {{ $tab === 'archive' ? 'var(--primary)' : '#6b7280' }}; border-bottom: 3px solid {{ $tab === 'archive' ? 'var(--primary)' : 'transparent' }}; transition: all 0.2s;">
                    Arhiva konkursa
                </a>
                <a href="{{ route('admin.competitions.index', ['tab' => 'all']) }}" 
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
                        <th>Tip</th>
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
                            <td>
                                @if($competition->type === 'zensko') Žensko preduzetništvo
                                @elseif($competition->type === 'omladinsko') Omladinsko preduzetništvo
                                @else Ostalo
                                @endif
                            </td>
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
                                            Do: {{ $competition->closed_at ? $competition->closed_at->copy()->addDays(30)->format('d.m.Y H:i') : 'N/A' }}
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
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px; color: #6b7280;">
                                @if($tab === 'archive')
                                    Nema arhiviranih konkursa.
                                @elseif($tab === 'all')
                                    Nema konkursa u sistemu.
                                @else
                                    Nema aktivnih konkursa.
                                    @if(isset($isAdmin) && $isAdmin)
                                        <a href="{{ route('admin.competitions.create') }}">Kreiraj prvi konkurs</a>
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

