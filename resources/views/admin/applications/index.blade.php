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
    .status-draft { background: #fef3c7; color: #92400e; }
    .status-submitted { background: #dbeafe; color: #1e40af; }
    .status-evaluated { background: #d1fae5; color: #065f46; }
    .status-approved { background: #d1fae5; color: #065f46; }
    .status-rejected { background: #fee2e2; color: #991b1b; }
    .btn-sm {
        padding: 6px 12px;
        font-size: 12px;
        border-radius: 6px;
        text-decoration: none;
        background: #3b82f6;
        color: #fff;
    }
</style>

<div class="admin-page">
    <div class="container mx-auto px-4">
        <div class="page-header">
            <h1>Pregled prijava</h1>
        </div>

        <div class="filters">
            <form method="GET" action="{{ route('admin.applications.index') }}">
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control">
                        <option value="">Svi statusi</option>
                        <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Nacrt</option>
                        <option value="submitted" {{ request('status') === 'submitted' ? 'selected' : '' }}>Podnesena</option>
                        <option value="evaluated" {{ request('status') === 'evaluated' ? 'selected' : '' }}>Ocjenjena</option>
                        <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Odobrena</option>
                        <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Odbijena</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Konkurs</label>
                    <select name="competition_id" class="form-control">
                        <option value="">Svi konkursi</option>
                        @foreach($competitions as $comp)
                            <option value="{{ $comp->id }}" {{ request('competition_id') == $comp->id ? 'selected' : '' }}>
                                {{ $comp->title }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Pretraga</label>
                    <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Naziv biznis plana...">
                </div>
                <div class="form-group">
                    <button type="submit" class="btn-primary">Filtriraj</button>
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
                        <th>Tip</th>
                        <th>Traženi iznos</th>
                        <th>Status</th>
                        <th>Ocjena</th>
                        <th>Akcije</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($applications as $application)
                        <tr>
                            <td>{{ $application->business_plan_name }}</td>
                            <td>{{ $application->user->name ?? 'N/A' }}</td>
                            <td>{{ $application->competition->title ?? 'N/A' }}</td>
                            <td>
                                {{ $application->applicant_type === 'preduzetnica' ? 'Preduzetnica' : 'DOO' }} - 
                                {{ $application->business_stage === 'započinjanje' ? 'Započinjanje' : 'Razvoj' }}
                            </td>
                            <td>{{ number_format($application->requested_amount, 2, ',', '.') }} €</td>
                            <td>
                                <span class="status-badge status-{{ $application->status }}">
                                    @if($application->status === 'draft') Nacrt
                                    @elseif($application->status === 'submitted') Podnesena
                                    @elseif($application->status === 'evaluated') Ocjenjena
                                    @elseif($application->status === 'approved') Odobrena
                                    @elseif($application->status === 'rejected') Odbijena
                                    @else {{ $application->status }}
                                    @endif
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
                                <a href="{{ route('admin.applications.show', $application) }}" class="btn-sm">Pregled</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 40px; color: #6b7280;">
                                Nema prijava.
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

