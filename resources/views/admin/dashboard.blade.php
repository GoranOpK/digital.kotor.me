@extends('layouts.app')

@section('content')
<style>
    :root {
        --primary: #0B3D91;
        --primary-dark: #0A347B;
        --secondary: #B8860B;
    }
    .admin-dashboard {
        background: #f9fafb;
        min-height: 100vh;
        padding: 24px 0;
    }
    .admin-header {
        background: linear-gradient(90deg, var(--primary), var(--primary-dark));
        color: #fff;
        padding: 20px;
        border-radius: 16px;
        margin-bottom: 24px;
    }
    .admin-header h1 {
        color: #fff;
        font-size: 32px;
        font-weight: 700;
        margin: 0;
    }
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 24px;
    }
    .stat-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 1px 2px rgba(0,0,0,.06);
    }
    .stat-card h3 {
        color: #6b7280;
        font-size: 14px;
        font-weight: 600;
        margin: 0 0 12px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .stat-card .number {
        font-size: 36px;
        font-weight: 800;
        color: var(--primary);
        margin: 0;
        line-height: 1;
    }
    .stat-card .subtitle {
        color: #6b7280;
        font-size: 13px;
        margin-top: 8px;
    }
    .content-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 20px;
    }
    @media (min-width: 1024px) {
        .content-grid {
            grid-template-columns: 1.2fr 0.8fr;
        }
    }
    .content-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        box-shadow: 0 1px 2px rgba(0,0,0,.06);
        overflow: hidden;
    }
    .content-card-header {
        padding: 20px;
        border-bottom: 1px solid #e5e7eb;
        background: #f9fafb;
    }
    .content-card-header h2 {
        font-size: 20px;
        font-weight: 700;
        color: #111827;
        margin: 0;
    }
    .content-card-body {
        padding: 20px;
    }
    .table {
        width: 100%;
        border-collapse: collapse;
    }
    .table thead {
        background: #f9fafb;
    }
    .table th {
        padding: 12px 16px;
        text-align: left;
        font-size: 12px;
        font-weight: 600;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 1px solid #e5e7eb;
    }
    .table td {
        padding: 12px 16px;
        border-bottom: 1px solid #e5e7eb;
        color: #111827;
    }
    .table tbody tr:hover {
        background: #f9fafb;
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
    .link-primary {
        color: var(--primary);
        text-decoration: none;
        font-weight: 600;
    }
    .link-primary:hover {
        text-decoration: underline;
    }
    .application-item {
        padding: 16px 0;
        border-bottom: 1px solid #e5e7eb;
    }
    .application-item:last-child {
        border-bottom: none;
    }
    .application-item .name {
        font-weight: 600;
        color: #111827;
        margin: 0 0 4px;
    }
    .application-item .competition {
        color: #6b7280;
        font-size: 14px;
        margin: 0 0 4px;
    }
    .application-item .date {
        color: #9ca3af;
        font-size: 12px;
        margin: 0;
    }
    .view-all-link {
        display: inline-block;
        margin-top: 16px;
        color: var(--primary);
        text-decoration: none;
        font-weight: 600;
    }
    .view-all-link:hover {
        text-decoration: underline;
    }
</style>

<div class="admin-dashboard">
    <div class="container mx-auto px-4">
        <!-- Header -->
        <div class="admin-header">
            <h1>Administratorski Dashboard</h1>
        </div>

        <!-- Statistike -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Ukupno korisnika</h3>
                <p class="number">{{ $stats['total_users'] }}</p>
                <p class="subtitle">Aktivnih: {{ $stats['active_users'] }}</p>
            </div>

            <div class="stat-card">
                <h3>Konkursi</h3>
                <p class="number">{{ $stats['total_competitions'] }}</p>
            </div>

            <div class="stat-card">
                <h3>Prijave</h3>
                <p class="number">{{ $stats['total_applications'] }}</p>
            </div>

            <div class="stat-card">
                <h3>Tenderi</h3>
                <p class="number">{{ $stats['total_tenders'] }}</p>
            </div>
        </div>

        <!-- Sadržaj -->
        <div class="content-grid">
            <!-- Najnoviji korisnici -->
            <div class="content-card">
                <div class="content-card-header">
                    <h2>Najnoviji korisnici</h2>
                </div>
                <div class="content-card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Ime</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Akcije</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recent_users as $user)
                                <tr>
                                    <td>{{ $user->name }}</td>
                                    <td>{{ $user->email }}</td>
                                    <td>
                                        <span class="status-badge {{ $user->activation_status === 'active' ? 'status-active' : 'status-inactive' }}">
                                            {{ $user->activation_status === 'active' ? 'Aktivan' : 'Deaktiviran' }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.users.show', $user) }}" class="link-primary">Pregled</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" style="text-align: center; color: #6b7280; padding: 24px;">Nema korisnika</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    <a href="{{ route('admin.users.index') }}" class="view-all-link">Prikaži sve korisnike →</a>
                </div>
            </div>

            <!-- Najnovije prijave -->
            <div class="content-card">
                <div class="content-card-header">
                    <h2>Najnovije prijave na konkurse</h2>
                </div>
                <div class="content-card-body">
                    @forelse($recent_applications as $application)
                        <div class="application-item">
                            <p class="name">{{ $application->user->name ?? 'N/A' }}</p>
                            <p class="competition">{{ $application->competition->title ?? 'N/A' }}</p>
                            <p class="date">{{ $application->created_at->format('d.m.Y H:i') }}</p>
                        </div>
                    @empty
                        <p style="color: #6b7280; text-align: center; padding: 24px;">Nema prijava</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
