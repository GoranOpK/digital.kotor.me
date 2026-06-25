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
    .programs-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        gap: 24px;
        margin-bottom: 24px;
    }
    .program-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 1px 2px rgba(0,0,0,.06);
        display: flex;
        flex-direction: column;
        gap: 16px;
        transition: box-shadow 0.2s, transform 0.2s;
        text-decoration: none;
        color: inherit;
    }
    a.program-card:hover {
        box-shadow: 0 8px 24px rgba(11, 61, 145, 0.12);
        transform: translateY(-2px);
    }
    .program-card.is-development {
        border-style: dashed;
        border-color: #d1d5db;
    }
    .program-card-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
    }
    .program-card-icon {
        font-size: 36px;
        line-height: 1;
    }
    .program-card h2 {
        font-size: 20px;
        font-weight: 700;
        color: #111827;
        margin: 0 0 8px;
    }
    .program-card p {
        color: #6b7280;
        font-size: 14px;
        margin: 0;
        line-height: 1.5;
    }
    .program-badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 9999px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        white-space: nowrap;
    }
    .program-badge-active {
        background: #d1fae5;
        color: #065f46;
    }
    .program-badge-development {
        background: #fef3c7;
        color: #92400e;
    }
    .program-stats {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 12px;
        padding-top: 8px;
        border-top: 1px solid #f3f4f6;
    }
    .program-stat-label {
        display: block;
        font-size: 11px;
        font-weight: 600;
        color: #9ca3af;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        margin-bottom: 4px;
    }
    .program-stat-value {
        font-size: 22px;
        font-weight: 800;
        color: var(--primary);
        line-height: 1;
    }
    .program-deadline {
        font-size: 13px;
        color: #374151;
        background: #f9fafb;
        border-radius: 8px;
        padding: 10px 12px;
    }
</style>

<div class="admin-dashboard">
    <div class="container mx-auto px-4">
        <!-- Header -->
        <div class="admin-header">
            <h1>@if($isCompetitionAdmin) Konkursi @else Administratorski Dashboard @endif</h1>
        </div>

        @if($isCompetitionAdmin && !empty($competitionPrograms))
        <div class="programs-grid">
            @foreach($competitionPrograms as $program)
                <a href="{{ route('admin.competitions.index', ['type' => $program['type'], 'tab' => 'active']) }}" class="program-card {{ $program['status'] === 'development' ? 'is-development' : '' }}">
                    <div class="program-card-header">
                        <div>
                            <div class="program-card-icon">{{ $program['icon'] }}</div>
                        </div>
                        <span class="program-badge {{ $program['status'] === 'active' ? 'program-badge-active' : 'program-badge-development' }}">
                            {{ $program['status'] === 'active' ? 'Aktivan modul' : 'U razvoju' }}
                        </span>
                    </div>
                    <div>
                        <h2>{{ $program['title'] }}</h2>
                        <p>{{ $program['description'] }}</p>
                    </div>
                    <div class="program-stats">
                        <div>
                            <span class="program-stat-label">Aktivni</span>
                            <span class="program-stat-value">{{ $program['active_competitions'] }}</span>
                        </div>
                        <div>
                            <span class="program-stat-label">Ukupno</span>
                            <span class="program-stat-value">{{ $program['total_competitions'] }}</span>
                        </div>
                        <div>
                            <span class="program-stat-label">Prijave</span>
                            <span class="program-stat-value">{{ $program['total_applications'] }}</span>
                        </div>
                    </div>
                    @if($program['next_deadline_competition'] && $program['next_deadline_days'] !== null)
                        <div class="program-deadline">
                            Najbliži rok: <strong>{{ $program['next_deadline_competition']->title }}</strong>
                            — {{ $program['next_deadline_days'] }} {{ $program['next_deadline_days'] == 1 ? 'dan' : 'dana' }}
                        </div>
                    @endif
                </a>
            @endforeach
        </div>
        @else

        <!-- Statistike -->
        <div class="stats-grid">
            @if(!$isCompetitionAdmin)
            <div class="stat-card">
                <h3>Ukupno korisnika</h3>
                <p class="number">{{ $stats['total_users'] ?? 0 }}</p>
                <p class="subtitle">Aktivnih: {{ $stats['active_users'] ?? 0 }}</p>
            </div>
            @endif

            <div class="stat-card">
                <h3>Konkursi</h3>
                <p class="number">{{ $stats['total_competitions'] }}</p>
            </div>

            <div class="stat-card">
                <h3>Prijave</h3>
                <p class="number">{{ $stats['total_applications'] }}</p>
            </div>

            @if(!$isCompetitionAdmin)
            <div class="stat-card">
                <h3>Tenderi</h3>
                <p class="number">{{ $stats['total_tenders'] ?? 0 }}</p>
            </div>
            @else
            <div class="stat-card">
                <h3>Komisije</h3>
                <p class="number">{{ $stats['total_commissions'] }}</p>
                <p class="subtitle">Aktivnih: {{ $stats['active_commissions'] }}</p>
            </div>
            @endif
        </div>

        <!-- Brzi linkovi -->
        <div class="content-card" style="margin-bottom: 20px;">
            <div class="content-card-header">
                <h2>Brzi linkovi</h2>
            </div>
            <div class="content-card-body" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
                <a href="{{ route('admin.competitions.index') }}" class="link-primary" style="padding: 12px; background: #f9fafb; border-radius: 8px; text-align: center;">
                    📋 Konkursi
                </a>
                <a href="{{ route('admin.commissions.index') }}" class="link-primary" style="padding: 12px; background: #f9fafb; border-radius: 8px; text-align: center;">
                    👥 Komisija
                </a>
                @if($isCompetitionAdmin)
                <a href="{{ route('competitions.archive') }}" class="link-primary" style="padding: 12px; background: #f9fafb; border-radius: 8px; text-align: center;">
                    📁 Arhiva konkursa
                </a>
                @endif
                @if(!$isCompetitionAdmin)
                <a href="{{ route('admin.applications.index') }}" class="link-primary" style="padding: 12px; background: #f9fafb; border-radius: 8px; text-align: center;">
                    📝 Prijave
                </a>
                <a href="{{ route('admin.users.index') }}" class="link-primary" style="padding: 12px; background: #f9fafb; border-radius: 8px; text-align: center;">
                    👤 Korisnici
                </a>
                @endif
            </div>
        </div>

        <!-- Sadržaj -->
        <div class="content-grid" style="grid-template-columns: 1.2fr 0.8fr;">
            @if(!$isCompetitionAdmin && $recent_users)
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
            @endif

            {{-- Najnovije prijave se ne prikazuju administratorima --}}
        </div>
        @endif
    </div>
</div>
@endsection
