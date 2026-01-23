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
        </div>

        <div class="filters">
            <form method="GET" action="{{ route('evaluation.index') }}" id="filterForm">
                <div class="form-group">
                    <label class="form-label">Filtriraj</label>
                    <select name="filter" class="form-control" onchange="document.getElementById('filterForm').submit();">
                        <option value="">Sve prijave</option>
                        <option value="pending" {{ request('filter') === 'pending' ? 'selected' : '' }}>Čeka ocjenjivanje</option>
                        <option value="evaluated" {{ request('filter') === 'evaluated' ? 'selected' : '' }}>Ocjenjene</option>
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
                            if ($allEvaluated) {
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
                                <span class="status-badge {{ $statusClass }}">
                                    {{ $displayStatus }}
                                </span>
                            </td>
                            <td>
                                @if($application->final_score)
                                    {{ number_format($application->final_score, 2) }} / 50
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                @php
                                    $isChairman = $commissionMember->position === 'predsjednik';
                                @endphp
                                
                                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                    @if($allEvaluated)
                                        {{-- Kada su svi članovi ocjenili, svi članovi komisije vide "Ocjenjena prijava" --}}
                                        @if($isChairman)
                                            {{-- Predsjednik vidi oba badge-a --}}
                                            <a href="{{ route('evaluation.create', $application) }}" class="btn-sm" style="background: #6b7280; color: #fff;">
                                                Lista za ocjenjivanje
                                            </a>
                                            <a href="{{ route('evaluation.chairman-review', $application) }}" class="btn-sm" style="background: var(--primary); color: #fff;">
                                                Pregled i zaključak
                                            </a>
                                        @else
                                            {{-- Ostali članovi vide "Ocjenjena prijava" --}}
                                            <a href="{{ route('evaluation.create', $application) }}" class="btn-sm evaluated" style="background: #10b981; color: #fff;">
                                                Ocjenjena prijava
                                            </a>
                                        @endif
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

