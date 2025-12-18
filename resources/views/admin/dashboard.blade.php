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
        padding: 16px 0;
    }
    @media (min-width: 768px) {
        .admin-dashboard {
            padding: 24px 0;
        }
    }
    .admin-header {
        background: linear-gradient(90deg, var(--primary), var(--primary-dark));
        color: #fff;
        padding: 16px 20px;
        border-radius: 12px;
        margin-bottom: 20px;
    }
    @media (min-width: 768px) {
        .admin-header {
            padding: 20px;
            border-radius: 16px;
        }
    }
    .admin-header h1 {
        color: #fff;
        font-size: 20px;
        font-weight: 700;
        margin: 0;
    }
    @media (min-width: 768px) {
        .admin-header h1 {
            font-size: 28px;
        }
    }
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
        margin-bottom: 20px;
    }
    @media (min-width: 768px) {
        .stats-grid {
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }
    }
    .stat-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 16px;
        box-shadow: 0 1px 2px rgba(0,0,0,.06);
    }
    @media (min-width: 768px) {
        .stat-card {
            border-radius: 16px;
            padding: 24px;
        }
    }
    .stat-card h3 {
        color: #6b7280;
        font-size: 10px;
        font-weight: 600;
        margin: 0 0 8px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    @media (min-width: 768px) {
        .stat-card h3 {
            font-size: 12px;
            margin-bottom: 12px;
        }
    }
    .stat-card .number {
        font-size: 24px;
        font-weight: 800;
        color: var(--primary);
        margin: 0;
        line-height: 1;
    }
    @media (min-width: 768px) {
        .stat-card .number {
            font-size: 32px;
        }
    }
    .stat-card .subtitle {
        color: #6b7280;
        font-size: 11px;
        margin-top: 4px;
    }
    .content-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 16px;
    }
    @media (min-width: 1024px) {
        .content-grid {
            grid-template-columns: 1.2fr 0.8fr;
            gap: 20px;
        }
    }
    .content-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        box-shadow: 0 1px 2px rgba(0,0,0,.06);
        overflow: hidden;
        margin-bottom: 16px;
    }
    @media (min-width: 768px) {
        .content-card {
            border-radius: 16px;
            margin-bottom: 0;
        }
    }
    .content-card-header {
        padding: 12px 16px;
        border-bottom: 1px solid #e5e7eb;
        background: #f9fafb;
    }
    @media (min-width: 768px) {
        .content-card-header {
            padding: 16px 20px;
        }
    }
    .content-card-header h2 {
        font-size: 16px;
        font-weight: 700;
        color: #111827;
        margin: 0;
    }
    .content-card-body {
        padding: 12px;
    }
    @media (min-width: 768px) {
        .content-card-body {
            padding: 20px;
        }
    }
    .table-responsive {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    .table {
        width: 100%;
        border-collapse: collapse;
        min-width: 500px; /* Minimum width to ensure horizontal scroll on very small screens */
    }
    .table th {
        padding: 10px 12px;
        text-align: left;
        font-size: 11px;
        font-weight: 600;
        color: #6b7280;
        text-transform: uppercase;
        border-bottom: 1px solid #e5e7eb;
    }
    .table td {
        padding: 10px 12px;
        border-bottom: 1px solid #e5e7eb;
        color: #111827;
        font-size: 13px;
    }
    .link-primary {
        color: var(--primary);
        text-decoration: none;
        font-weight: 600;
    }
    .application-item {
        padding: 12px 0;
        border-bottom: 1px solid #e5e7eb;
    }
    .application-item:last-child {
        border-bottom: none;
    }
    .application-item .name {
        font-weight: 600;
        color: #111827;
        margin: 0 0 2px;
        font-size: 13px;
    }
    .application-item .competition {
        color: #6b7280;
        font-size: 12px;
        margin: 0 0 2px;
    }
    .application-item .date {
        color: #9ca3af;
        font-size: 11px;
        margin: 0;
    }
</style>

<div class="admin-dashboard">
    <div class="container mx-auto px-4">
        <!-- Header -->
        <div class="admin-header">
            <h1>@if($isCompetitionAdmin) Konkursi - Panel @else Admin Panel @endif</h1>
        </div>

        <!-- Statistike -->
        <div class="stats-grid">
            @if(!$isCompetitionAdmin)
            <div class="stat-card">
                <h3>Korisnici</h3>
                <p class="number">{{ $stats['total_users'] ?? 0 }}</p>
                <p class="subtitle">Aktivno: {{ $stats['active_users'] ?? 0 }}</p>
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
                <p class="subtitle">Aktivno: {{ $stats['active_commissions'] }}</p>
            </div>
            @endif
        </div>

        <!-- Brzi linkovi -->
        <div class="content-card" style="margin-bottom: 20px;">
            <div class="content-card-header">
                <h2>Brzi linkovi</h2>
            </div>
            <div class="content-card-body" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px;">
                @if(!$isCompetitionAdmin)
                    <a href="{{ route('admin.users.index') }}" class="link-primary" style="padding: 10px; background: #f9fafb; border-radius: 8px; text-align: center; font-size: 13px;">👤 Korisnici</a>
                    <a href="{{ route('admin.applications.index') }}" class="link-primary" style="padding: 10px; background: #f9fafb; border-radius: 8px; text-align: center; font-size: 13px;">📝 Prijave</a>
                @endif
                <a href="{{ route('admin.competitions.index') }}" class="link-primary" style="padding: 10px; background: #f9fafb; border-radius: 8px; text-align: center; font-size: 13px;">📋 Konkursi</a>
                <a href="{{ route('admin.commissions.index') }}" class="link-primary" style="padding: 10px; background: #f9fafb; border-radius: 8px; text-align: center; font-size: 13px;">👥 Komisija</a>
                @if(!$isCompetitionAdmin)
                    <a href="{{ route('admin.feedback.index') }}" class="link-primary" style="padding: 10px; background: #f9fafb; border-radius: 8px; text-align: center; font-size: 13px;">💬 Povratne informacije</a>
                @endif
            </div>
        </div>

        <!-- Sadržaj -->
        <div class="content-grid" style="grid-template-columns: @if($isCompetitionAdmin) 1fr @else 1.2fr 0.8fr @endif;">
            @if($isCompetitionAdmin)
            <!-- Aktivni konkursi (Pregled trajanja) -->
            <div class="content-card">
                <div class="content-card-header">
                    <h2>Trajanje konkursa</h2>
                </div>
                <div class="content-card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Konkurs</th>
                                    <th>Ističe</th>
                                    <th>Preostalo</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($active_competitions as $comp)
                                    @php
                                        $deadline = $comp->published_at ? $comp->published_at->addDays($comp->deadline_days) : null;
                                        $daysLeft = $deadline ? now()->diffInDays($deadline, false) : 0;
                                    @endphp
                                    <tr>
                                        <td>
                                            <a href="{{ route('admin.competitions.show', $comp) }}" class="link-primary">{{ Str::limit($comp->title, 30) }}</a>
                                        </td>
                                        <td>{{ $deadline ? $deadline->format('d.m.Y') : '-' }}</td>
                                        <td>
                                            @if($daysLeft > 0)
                                                <span style="color: #059669; font-weight: 600;">{{ ceil($daysLeft) }}d</span>
                                            @else
                                                <span style="color: #dc2626; font-weight: 600;">Isteklo</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" style="text-align: center; color: #6b7280; padding: 20px;">Nema aktivnih konkursa</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif

            @if(!$isCompetitionAdmin && isset($recent_users))
            <!-- Najnoviji korisnici -->
            <div class="content-card">
                <div class="content-card-header">
                    <h2>Zadnji korisnici</h2>
                </div>
                <div class="content-card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Ime</th>
                                    <th>Status</th>
                                    <th>Link</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recent_users as $user)
                                    <tr>
                                        <td>{{ Str::limit($user->name, 20) }}</td>
                                        <td>
                                            <span style="font-size: 10px; padding: 2px 8px; border-radius: 9999px; font-weight: 600; {{ $user->activation_status === 'active' ? 'background: #d1fae5; color: #065f46;' : 'background: #fee2e2; color: #991b1b;' }}">
                                                {{ $user->activation_status === 'active' ? 'Aktivan' : 'Neaktivan' }}
                                            </span>
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.users.show', $user) }}" class="link-primary">Pregled</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" style="text-align: center;">Nema korisnika</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif

            <!-- Najnovije prijave -->
            <div class="content-card">
                <div class="content-card-header">
                    <h2>Zadnje prijave</h2>
                </div>
                <div class="content-card-body">
                    @forelse($recent_applications->take(5) as $application)
                        <div class="application-item">
                            <p class="name">{{ Str::limit($application->user->name, 25) ?? 'N/A' }}</p>
                            <p class="competition">{{ Str::limit($application->competition->title, 35) ?? 'N/A' }}</p>
                            <p class="date">{{ $application->created_at->format('d.m.Y H:i') }}</p>
                        </div>
                    @empty
                        <p style="color: #6b7280; text-align: center; padding: 20px;">Nema prijava</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
