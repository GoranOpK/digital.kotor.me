@extends('layouts.app')

@section('content')
<style>
    :root {
        --primary: #0B3D91;
        --primary-dark: #0A347B;
    }
    .evaluation-page {
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
        font-size: 28px;
        font-weight: 700;
        margin: 0;
    }
    .filters {
        background: #fff;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 24px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    .filters form {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
        align-items: end;
    }
    .form-group {
        display: flex;
        flex-direction: column;
    }
    .form-label {
        font-size: 12px;
        font-weight: 600;
        color: #374151;
        margin-bottom: 6px;
    }
    .form-control {
        padding: 8px 12px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 14px;
    }
    .btn-primary {
        background: var(--primary);
        color: #fff;
        padding: 8px 16px;
        border: none;
        border-radius: 6px;
        font-weight: 600;
        cursor: pointer;
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
    .status-submitted { background: #dbeafe; color: #1e40af; }
    .status-evaluated { background: #d1fae5; color: #065f46; }
    .status-rejected { background: #fee2e2; color: #991b1b; }
    .btn-sm {
        padding: 6px 12px;
        font-size: 12px;
        border-radius: 6px;
        text-decoration: none;
        background: #3b82f6;
        color: #fff;
    }
    .btn-sm.evaluated {
        background: #10b981;
    }
</style>

<div class="evaluation-page">
    <div class="container mx-auto px-4">
        <div class="page-header">
            <h1>Ocjenjivanje prijava</h1>
            @if(isset($competitionsWithAllEvaluated) && $competitionsWithAllEvaluated->isNotEmpty())
                <div style="margin-top: 16px; display: flex; gap: 12px; flex-wrap: wrap;">
                    @foreach($competitionsWithAllEvaluated as $comp)
                        <a href="{{ route('admin.competitions.ranking', $comp) }}" 
                           class="btn-sm" 
                           style="background: #8b5cf6; color: #fff; font-weight: 600; padding: 10px 20px;">
                            📊 Rang lista - {{ $comp->title }}
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="filters">
            <form method="GET" action="{{ route('evaluation.index') }}" id="filterForm">
                <div class="form-group">
                    <label class="form-label">Filtriraj</label>
                    <select name="filter" class="form-control" onchange="document.getElementById('filterForm').submit();">
                        <option value="">Sve prijave</option>
                        <option value="pending" {{ request('filter') === 'pending' ? 'selected' : '' }}>Čeka ocjenjivanje</option>
                        <option value="evaluated" {{ request('filter') === 'evaluated' ? 'selected' : '' }}>Ocjenjene</option>
                        <option value="rejected" {{ request('filter') === 'rejected' ? 'selected' : '' }}>Odbijene prijave</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Konkurs</label>
                    <select name="competition_id" class="form-control" onchange="document.getElementById('filterForm').submit();">
                        <option value="">Svi konkursi</option>
                        @foreach($competitions as $comp)
                            <option value="{{ $comp->id }}" {{ request('competition_id') == $comp->id ? 'selected' : '' }}>
                                {{ $comp->title }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn-primary">Filtriraj</button>
                    @if(request('filter') || request('competition_id'))
                        <a href="{{ route('evaluation.index') }}" class="btn-primary" style="margin-left: 8px; background: #6b7280; text-decoration: none; display: inline-block;">Resetuj</a>
                    @endif
                </div>
            </form>
        </div>

        @if(isset($competitions) && $competitions->isNotEmpty())
            @foreach($competitions as $comp)
                @php
                    $daysUntilApplicationDeadline = $comp->getDaysUntilApplicationDeadline();
                    $daysUntilEvaluationDeadline = $comp->getDaysUntilEvaluationDeadline();
                    $isApplicationDeadlinePassed = $comp->isApplicationDeadlinePassed();
                    $isEvaluationDeadlinePassed = $comp->isEvaluationDeadlinePassed();
                @endphp
                @if($comp->status === 'published' && !$isApplicationDeadlinePassed && $daysUntilApplicationDeadline !== null)
                    <div class="info-card" style="background: #fff; border-radius: 12px; padding: 16px 20px; margin-bottom: 20px; border-left: 4px solid {{ $daysUntilApplicationDeadline <= 3 ? '#ef4444' : ($daysUntilApplicationDeadline <= 7 ? '#f59e0b' : '#10b981') }}; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
                            <span style="font-size: 20px;">📅</span>
                            <h3 style="margin: 0; font-size: 16px; font-weight: 600; color: #111827;">{{ $comp->title }} - Rok za prijave</h3>
                        </div>
                        <p style="color: {{ $daysUntilApplicationDeadline <= 3 ? '#991b1b' : ($daysUntilApplicationDeadline <= 7 ? '#92400e' : '#065f46') }}; font-weight: 600; font-size: 16px; margin: 4px 0;">
                            Preostalo vremena: <strong>{{ $daysUntilApplicationDeadline }} {{ $daysUntilApplicationDeadline == 1 ? 'dan' : ($daysUntilApplicationDeadline < 5 ? 'dana' : 'dana') }}</strong>
                        </p>
                        <p style="color: #6b7280; font-size: 13px; margin: 4px 0;">
                            Rok za prijave: {{ $comp->deadline ? $comp->deadline->format('d.m.Y H:i') : 'N/A' }}
                        </p>
                    </div>
                @elseif(($isApplicationDeadlinePassed || $comp->status === 'closed') && $daysUntilEvaluationDeadline !== null)
                    @php
                        $evalDeadlineDate = $comp->getEvaluationDeadlineDate();
                        $yearLabel = $comp->year ?? $comp->deadline?->year ?? $evalDeadlineDate?->year ?? now()->format('Y');
                    @endphp
                    <div class="info-card" style="background: #fff; border-radius: 12px; padding: 16px 20px; margin-bottom: 20px; border-left: 4px solid {{ $isEvaluationDeadlinePassed ? '#991b1b' : ($daysUntilEvaluationDeadline <= 3 ? '#ef4444' : ($daysUntilEvaluationDeadline <= 7 ? '#f59e0b' : '#10b981')) }}; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
                            <span style="font-size: 20px;">⏰</span>
                            <h3 style="margin: 0; font-size: 16px; font-weight: 600; color: #111827;">{{ $yearLabel }} - Rok za donošenje odluke</h3>
                        </div>
                        @if($isEvaluationDeadlinePassed)
                            <p style="color: #991b1b; font-weight: 600; font-size: 14px; margin: 4px 0;">
                                ❌ Rok za donošenje odluke je istekao (0 dana)
                            </p>
                        @else
                            <p style="color: {{ $daysUntilEvaluationDeadline <= 3 ? '#991b1b' : ($daysUntilEvaluationDeadline <= 7 ? '#92400e' : '#065f46') }}; font-weight: 600; font-size: 16px; margin: 4px 0;">
                                Preostalo vremena: <strong>{{ $daysUntilEvaluationDeadline }} {{ $daysUntilEvaluationDeadline == 1 ? 'dan' : ($daysUntilEvaluationDeadline < 5 ? 'dana' : 'dana') }}</strong>
                            </p>
                        @endif
                        <p style="color: #6b7280; font-size: 13px; margin: 4px 0;">
                            Komisija je dužna donijeti odluku u roku od 30 dana od dana zatvaranja prijava. Rok: {{ $evalDeadlineDate ? $evalDeadlineDate->format('d.m.Y H:i') : 'N/A' }}
                        </p>
                    </div>
                @endif
            @endforeach
        @endif

        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>Naziv biznis plana</th>
                        <th>Podnosilac</th>
                        <th>Konkurs</th>
                        <th>Status</th>
                        <th>Ocjena</th>
                        <th>Akcije</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($applications as $application)
                        @php
                            $isEvaluated = \App\Models\EvaluationScore::where('application_id', $application->id)
                                ->where('commission_member_id', $commissionMember->id)
                                ->exists();
                            
                            // Provjeri da li su svi članovi komisije ocjenili prijavu
                            $commission = $commissionMember->commission;
                            $totalMembers = $commission->activeMembers()->count();
                            
                            // Broj različitih članova koji su ocjenili prijavu
                            $evaluatedMemberIds = \App\Models\EvaluationScore::where('application_id', $application->id)
                                ->whereIn('commission_member_id', $commission->activeMembers()->pluck('id'))
                                ->pluck('commission_member_id')
                                ->unique()
                                ->count();
                            
                            $allEvaluated = $evaluatedMemberIds >= $totalMembers;
                            
                            // Odredi status za prikaz
                            if ($application->status === 'rejected') {
                                $displayStatus = 'Odbijena prijava';
                                $statusClass = 'status-rejected';
                            } elseif ($allEvaluated) {
                                $displayStatus = 'Ocjenjena prijava';
                                $statusClass = 'status-evaluated';
                            } else {
                                $displayStatus = 'U ocjenjivanju';
                                $statusClass = 'status-submitted';
                            }
                        @endphp
                        <tr>
                            <td>{{ $application->business_plan_name }}</td>
                            <td>{{ $application->user->name ?? 'N/A' }}</td>
                            <td>{{ $application->competition->title ?? 'N/A' }}</td>
                            <td>
                                @if($application->status === 'rejected')
                                    <a href="{{ route('evaluation.create', $application) }}" class="status-badge {{ $statusClass }}" style="text-decoration: none; cursor: pointer; display: inline-block;">
                                        {{ $displayStatus }}
                                    </a>
                                @else
                                    <span class="status-badge {{ $statusClass }}">
                                        {{ $displayStatus }}
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if($application->status === 'rejected')
                                    {{ number_format($application->getDisplayScore(), 2) }} / 50
                                @elseif($allEvaluated && $application->final_score)
                                    {{ number_format($application->final_score, 2) }} / 50
                                @else
                                    Ocjenjivanje u toku
                                @endif
                            </td>
                            <td>
                                @php
                                    $isChairman = $commissionMember->position === 'predsjednik';
                                @endphp
                                
                                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                    @if($application->status === 'rejected')
                                        {{-- Odbijene prijave - svi članovi mogu pristupiti (read-only) --}}
                                        <a href="{{ route('evaluation.create', $application) }}" class="btn-sm" style="background: #dc2626; color: #fff;">
                                            Pregledaj
                                        </a>
                                    @elseif($allEvaluated)
                                        {{-- Kada su svi članovi ocjenili, svi članovi komisije vide "Ocjenjena prijava" --}}
                                        <a href="{{ route('evaluation.create', $application) }}" class="btn-sm evaluated" style="background: #10b981; color: #fff;">
                                            Ocjenjena prijava
                                        </a>
                                    @else
                                        {{-- Dok nisu svi ocjenili, normalni flow --}}
                                        <a href="{{ route('evaluation.create', $application) }}" class="btn-sm {{ $isEvaluated ? 'evaluated' : '' }}">
                                            {{ $isEvaluated ? 'Pregledaj ocjenu' : 'Ocjeni' }}
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 40px; color: #6b7280;">
                                Nema prijava za ocjenjivanje.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div style="margin-top: 20px;">
                {{ $applications->links() }}
            </div>
        </div>
    </div>
</div>
@endsection

