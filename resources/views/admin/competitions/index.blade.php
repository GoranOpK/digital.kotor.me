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
</style>

<div class="admin-page">
    <div class="container mx-auto px-4">
        <div class="page-header">
            <h1>Upravljanje konkursima</h1>
            <a href="{{ route('admin.competitions.create') }}" class="btn-primary">+ Novi konkurs</a>
        </div>

        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>Naziv</th>
                        <th>Tip</th>
                        <th>Godina</th>
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
                            <td>{{ $competition->year ?? date('Y') }}</td>
                            <td>{{ number_format($competition->budget ?? 0, 2, ',', '.') }} €</td>
                            <td>
                                <span class="status-badge status-{{ $competition->status }}">
                                    @if($competition->status === 'draft') Nacrt
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
                                <a href="{{ route('admin.competitions.edit', $competition) }}" class="btn-sm btn-edit">Izmeni</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px; color: #6b7280;">
                                Nema konkursa. <a href="{{ route('admin.competitions.create') }}">Kreiraj prvi konkurs</a>
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
@endsection

