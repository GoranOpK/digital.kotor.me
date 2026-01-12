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
    .status-active {
        background: #d1fae5;
        color: #065f46;
    }
    .status-inactive {
        background: #fee2e2;
        color: #991b1b;
    }
    .btn-sm {
        padding: 6px 12px;
        font-size: 12px;
        border-radius: 6px;
        text-decoration: none;
        margin-right: 4px;
    }
    .btn-view {
        background: #3b82f6;
        color: #fff;
    }
    .btn-edit {
        background: #f59e0b;
        color: #fff;
    }
    .btn-delete {
        background: #ef4444;
        color: #fff;
        border: none;
        cursor: pointer;
    }
    .btn-delete:hover {
        background: #dc2626;
    }
</style>

<div class="admin-page">
    <div class="container mx-auto px-4">
        <div class="page-header">
            <h1>Upravljanje komisijom</h1>
            <a href="{{ route('admin.commissions.create') }}" class="btn-primary">+ Nova komisija</a>
        </div>

        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>Naziv</th>
                        <th>Godina</th>
                        <th>Mandat</th>
                        <th>Članovi</th>
                        <th>Status</th>
                        <th>Akcije</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($commissions as $commission)
                        <tr>
                            <td>{{ $commission->name }}</td>
                            <td>{{ $commission->year }}</td>
                            <td>
                                {{ $commission->start_date->format('d.m.Y') }} - 
                                {{ $commission->end_date->format('d.m.Y') }}
                            </td>
                            <td>
                                {{ $commission->active_members_count }} / {{ $commission->members_count }} aktivnih
                            </td>
                            <td>
                                <span class="status-badge status-{{ $commission->status }}">
                                    {{ $commission->status === 'active' ? 'Aktivna' : 'Neaktivna' }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('admin.commissions.show', $commission) }}" class="btn-sm btn-view">Pregled</a>
                                <a href="{{ route('admin.commissions.edit', $commission) }}" class="btn-sm btn-edit">Izmijeni</a>
                                <form action="{{ route('admin.commissions.destroy', $commission) }}" method="POST" style="display: inline;" onsubmit="return confirm('Da li ste sigurni da želite da obrišete ovu komisiju? Ova akcija je nepovratna.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-sm btn-delete">Obriši</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 40px; color: #6b7280;">
                                Nema komisija. <a href="{{ route('admin.commissions.create') }}">Kreiraj prvu komisiju</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div style="margin-top: 20px;">
                {{ $commissions->links() }}
            </div>
        </div>
    </div>
</div>
@endsection

