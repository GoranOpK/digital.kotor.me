@extends('layouts.app')

@section('content')
<style>
    :root {
        --primary: #0B3D91;
        --primary-dark: #0A347B;
    }
    .archive-page {
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
    .status-completed {
        background: #d1fae5;
        color: #065f46;
    }
    .btn-link {
        color: var(--primary);
        text-decoration: none;
        font-weight: 600;
    }
    .btn-link:hover {
        text-decoration: underline;
    }
    .btn-delete {
        color: #ef4444;
        text-decoration: none;
        font-weight: 600;
        margin-left: 12px;
        padding: 4px 8px;
        border-radius: 4px;
        transition: all 0.2s;
    }
    .btn-delete:hover {
        background: #fee2e2;
        text-decoration: none;
    }
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #6b7280;
    }
    .empty-state-icon {
        font-size: 48px;
        margin-bottom: 16px;
    }
</style>

<div class="archive-page">
    <div class="container mx-auto px-4">
        <div class="page-header">
            <h1>Arhiva konkursa</h1>
        </div>

        <div class="table-card">
            @if($competitions->count() > 0)
                <table>
                    <thead>
                        <tr>
                            <th>Naziv konkursa</th>
                            <th>Godina</th>
                            <th>Broj prijava</th>
                            <th>Datum zatvaranja</th>
                            <th>Status</th>
                            <th>Akcije</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($competitions as $competition)
                            <tr>
                                <td>
                                    <strong>{{ $competition->title }}</strong>
                                </td>
                                <td>{{ $competition->year ?? 'N/A' }}</td>
                                <td>{{ $competition->applications_count }}</td>
                                <td>
                                    @if($competition->closed_at)
                                        {{ $competition->closed_at->format('d.m.Y') }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    <span class="status-badge status-completed">Završen</span>
                                </td>
                                <td>
                                    @php
                                        $user = auth()->user();
                                        $isAdmin = $user->role && in_array($user->role->name, ['admin', 'konkurs_admin', 'superadmin']);
                                        $isCompetitionAdmin = $user->role && $user->role->name === 'konkurs_admin';
                                        $isCommissionMember = $user->role && $user->role->name === 'komisija';
                                    @endphp
                                    @if($isAdmin || $isCommissionMember)
                                        <a href="{{ route('admin.competitions.show', $competition) }}" class="btn-link">Pregled</a>
                                    @else
                                        <a href="{{ route('competitions.show', $competition) }}" class="btn-link">Pregled</a>
                                    @endif
                                    @if($isCompetitionAdmin)
                                        <form action="{{ route('admin.competitions.destroy', $competition) }}" method="POST" style="display: inline;" onsubmit="return confirm('Da li ste sigurni da želite da obrišete ovaj konkurs iz arhive? Ova akcija je nepovratna.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-delete">Obriši</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div style="margin-top: 20px;">
                    {{ $competitions->links() }}
                </div>
            @else
                <div class="empty-state">
                    <div class="empty-state-icon">📁</div>
                    <h3 style="color: #374151; margin-bottom: 8px;">Arhiva je prazna</h3>
                    <p style="color: #6b7280;">Trenutno nema završenih konkursa u arhivi.</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
