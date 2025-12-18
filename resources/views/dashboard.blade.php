{{-- Korisnički dashboard/panel --}}
@extends('layouts.app')

@section('content')
<style>
    :root {
        --primary: #0B3D91;
        --primary-dark: #0A347B;
        --secondary: #B8860B;
    }
    .user-dashboard {
        background: #f9fafb;
        min-height: 100vh;
        padding: 24px 0;
    }
    .dashboard-header {
        background: linear-gradient(90deg, var(--primary), var(--primary-dark));
        color: #fff;
        padding: 16px 20px;
        border-radius: 12px;
        margin-bottom: 20px;
    }
    .dashboard-header h1 {
        color: #fff;
        font-size: 24px;
        font-weight: 700;
        margin: 0 0 4px;
    }
    .dashboard-header .welcome-text {
        color: rgba(255, 255, 255, 0.9);
        font-size: 14px;
        margin: 0;
    }
    .dashboard-header .user-type-badge {
        display: inline-block;
        background: rgba(255, 255, 255, 0.2);
        padding: 2px 10px;
        border-radius: 9999px;
        font-size: 11px;
        font-weight: 600;
        margin-top: 6px;
    }
    .alert {
        border-radius: 12px;
        padding: 12px 16px;
        margin-bottom: 16px;
        border: 1px solid;
        font-size: 14px;
    }
    .alert-success {
        background: #d1fae5;
        border-color: #10b981;
        color: #065f46;
    }
    .alert-warning {
        background: #fef3c7;
        border-color: #f59e0b;
        color: #92400e;
    }
    .top-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 16px;
        margin-bottom: 20px;
    }
    @media (min-width: 1024px) {
        .top-grid {
            grid-template-columns: repeat(3, 1fr);
            align-items: stretch;
        }
        .top-grid > .info-card {
            height: 100%;
        }
    }
    .services-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 16px;
        margin-top: 16px;
    }
    @media (min-width: 768px) {
        .services-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    @media (min-width: 1024px) {
        .services-grid {
            grid-template-columns: repeat(3, 1fr);
        }
    }
    .service-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 16px;
        box-shadow: 0 1px 2px rgba(0,0,0,.06);
        text-decoration: none;
        display: block;
        transition: all 0.2s;
    }
    .service-card:hover {
        box-shadow: 0 4px 6px rgba(0,0,0,.1);
        transform: translateY(-2px);
        border-color: var(--primary);
    }
    .service-icon {
        width: 36px;
        height: 36px;
        border-radius: 8px;
        border: 2px solid var(--primary);
        color: var(--primary);
        background: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 18px;
        margin-bottom: 12px;
    }
    .service-card h3 {
        font-size: 15px;
        font-weight: 700;
        color: #111827;
        margin: 0 0 6px;
    }
    .service-card p {
        color: #6b7280;
        font-size: 12px;
        margin: 0;
        line-height: 1.4;
    }
    .info-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 16px;
        box-shadow: 0 1px 2px rgba(0,0,0,.06);
        margin-bottom: 16px;
        height: 100%;
        display: flex;
        flex-direction: column;
    }
    .info-card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 12px;
        padding-bottom: 10px;
        border-bottom: 1px solid #e5e7eb;
    }
    .info-card-header h2 {
        font-size: 16px;
        font-weight: 700;
        color: #111827;
        margin: 0;
    }
    .info-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 10px;
    }
    @media (min-width: 768px) {
        .info-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    .info-item {
        display: flex;
        flex-direction: column;
    }
    .info-label {
        font-size: 11px;
        font-weight: 600;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 2px;
    }
    .info-value {
        font-size: 13px;
        color: #111827;
        font-weight: 500;
    }
    .btn-edit {
        background: var(--primary);
        color: #fff;
        padding: 6px 12px;
        border-radius: 6px;
        text-decoration: none;
        font-size: 13px;
        font-weight: 600;
        display: inline-block;
        transition: background-color .2s;
    }
    .btn-edit:hover {
        background: var(--primary-dark);
    }
</style>

@php
    $user = auth()->user();
    $isSuperAdmin = $user->role && $user->role->name === 'superadmin';
    $isCompetitionAdmin = $user->role && $user->role->name === 'konkurs_admin';
    $isPhysicalPerson = $user->user_type === 'Fizičko lice';
    $isResident = $user->residential_status === 'resident';
    $isNonResident = $user->residential_status === 'non-resident';
    $isLegalEntity = $user->user_type !== 'Fizičko lice';
    
    // Određivanje tipa korisnika za prikaz
    if ($isSuperAdmin) {
        $userTypeLabel = 'Super Administrator';
    } elseif ($isCompetitionAdmin) {
        $userTypeLabel = 'Administrator konkursa';
    } elseif ($isPhysicalPerson && $isResident) {
        $userTypeLabel = 'Fizičko lice (Rezident)';
    } elseif ($isPhysicalPerson && $isNonResident) {
        $userTypeLabel = 'Fizičko lice (Nerezident)';
    } else {
        $userTypeLabel = $user->user_type ?? 'Pravno lice';
    }
    
    // Izračunaj korišćen prostor za dokumente
    $usedStorage = $user->used_storage_bytes ?? 0;
    $maxStorage = 20 * 1024 * 1024; // 20 MB
    $usedStorageMB = round($usedStorage / 1024 / 1024, 2);
    $maxStorageMB = 20;
    $storagePercentage = $maxStorage > 0 ? round(($usedStorage / $maxStorage) * 100, 1) : 0;
@endphp

<div class="user-dashboard">
    <div class="container mx-auto px-4">
        <!-- Header -->
        <div class="dashboard-header">
            <h1>Moj Panel</h1>
            <p class="welcome-text">Dobrodošli, {{ $user->name ?? 'Korisnik' }}</p>
            <span class="user-type-badge">{{ $userTypeLabel }}</span>
        </div>

        <!-- Alerts -->
        @if (session('verified'))
            <div class="alert alert-success">
                <strong>Uspješno!</strong> Vaša email adresa je verifikovana.
            </div>
        @endif

        @if (!$user->hasVerifiedEmail())
            <div class="alert alert-warning">
                <strong>Važno:</strong> Molimo vas da <a href="{{ route('verification.notice') }}">verifikujete svoju email adresu</a>.
            </div>
        @endif

        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if (!$isSuperAdmin && !$isCompetitionAdmin)
        <div class="top-grid">
            <!-- 1. Informacije o korisniku -->
            <div class="info-card">
                <div class="info-card-header">
                    <h2>Informacije o korisniku</h2>
                    <a href="{{ route('profile.edit') }}" class="btn-edit">Izmijeni</a>
                </div>
                <div class="info-grid" style="grid-template-columns: 1fr; gap: 8px;">
                    <div class="info-item">
                        <span class="info-label">Ime i prezime</span>
                        <span class="info-value">{{ $user->name ?? 'N/A' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Email adresa</span>
                        <span class="info-value" style="font-size: 12px;">{{ $user->email ?? 'N/A' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Broj telefona</span>
                        <span class="info-value">{{ $user->phone ?? 'N/A' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Adresa</span>
                        <span class="info-value" style="font-size: 12px;">{{ Str::limit($user->address, 40) ?? 'N/A' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Tip korisnika</span>
                        <span class="info-value">{{ $userTypeLabel }}</span>
                    </div>
                    @if($user->jmb)
                        <div class="info-item">
                            <span class="info-label">JMB</span>
                            <span class="info-value">{{ $user->jmb }}</span>
                        </div>
                    @endif
                    @if($user->pib)
                        <div class="info-item">
                            <span class="info-label">PIB</span>
                            <span class="info-value">{{ $user->pib }}</span>
                        </div>
                    @endif
                </div>
            </div>

            <!-- 2. Moja biblioteka dokumenata -->
            <div class="info-card">
                <div class="info-card-header">
                    <h2>Biblioteka dokumenata</h2>
                    <a href="{{ route('documents.index') }}" class="btn-edit">Otvori</a>
                </div>
                <p style="margin: 0 0 12px; color: #6b7280; font-size: 12px; line-height: 1.4;">
                    Čuvajte lična, finansijska i poslovna dokumenta za prijave na konkurse i tendere.
                </p>
                <div style="margin-top: auto; padding-top: 12px; border-top: 1px solid #e5e7eb;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                        <span style="font-size: 10px; font-weight: 600; color: #6b7280; text-transform: uppercase;">Iskorišćeno</span>
                        <span style="font-size: 12px; font-weight: 600; color: var(--primary);">{{ $usedStorageMB ?? 0 }} MB / 20 MB</span>
                    </div>
                    <div style="width: 100%; height: 6px; background: #e5e7eb; border-radius: 3px; overflow: hidden;">
                        <div style="height: 100%; background: linear-gradient(90deg, var(--primary), var(--primary-dark)); width: {{ min($storagePercentage ?? 0, 100) }}%; transition: width 0.3s ease;"></div>
                    </div>
                    <div style="font-size: 10px; color: #6b7280; margin-top: 4px;">
                        {{ $storagePercentage ?? 0 }}% od ukupnog prostora
                    </div>
                </div>
            </div>

            <!-- 3. Moje prijave na konkurse -->
            <div class="info-card">
                <div class="info-card-header">
                    <h2>Moje prijave</h2>
                    <a href="{{ route('competitions.index') }}" class="btn-edit">Novi</a>
                </div>
                @if(isset($applications) && $applications->count() > 0)
                    <div style="overflow-y: auto; max-height: 250px; padding-right: 4px;">
                        @foreach($applications->take(5) as $app)
                            <div style="padding: 8px 0; border-bottom: 1px solid #f3f4f6;">
                                <div style="font-weight: 600; color: #111827; font-size: 12px;">{{ Str::limit($app->business_plan_name, 25) }}</div>
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 4px;">
                                    @php
                                        $statusLabels = ['draft' => 'Nacrt', 'submitted' => 'U obradi', 'evaluated' => 'Ocjenjena', 'approved' => 'Odobrena', 'rejected' => 'Odbijena'];
                                        $statusColors = ['draft' => 'background: #fef3c7; color: #92400e;', 'submitted' => 'background: #dbeafe; color: #1e40af;', 'evaluated' => 'background: #d1fae5; color: #065f46;', 'approved' => 'background: #d1fae5; color: #065f46;', 'rejected' => 'background: #fee2e2; color: #991b1b;'];
                                    @endphp
                                    <span style="display: inline-block; padding: 1px 6px; border-radius: 9999px; font-size: 9px; font-weight: 600; {{ $statusColors[$app->status] ?? '' }}">
                                        {{ $statusLabels[$app->status] ?? $app->status }}
                                    </span>
                                    <div style="display: flex; gap: 6px;">
                                        <a href="{{ route('applications.show', $app) }}" style="color: var(--primary); font-weight: 600; text-decoration: none; font-size: 11px;">Pregled</a>
                                        <form action="{{ route('applications.destroy', $app) }}" method="POST" onsubmit="return confirm('Obrisati prijavu?');" style="display: inline;">
                                            @csrf @method('DELETE')
                                            <button type="submit" style="background: none; border: none; color: #ef4444; font-weight: 600; cursor: pointer; font-size: 11px; padding: 0;">Obriši</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div style="text-align: center; padding: 16px 0;">
                        <p style="color: #6b7280; font-size: 12px; margin-bottom: 8px;">Nemate aktivnih prijava.</p>
                        <a href="{{ route('competitions.index') }}" class="btn-edit" style="font-size: 11px;">Prijavi se</a>
                    </div>
                @endif
            </div>
        </div>

        <!-- Brzi servisi (Plati, Tenderi) -->
        <div style="margin-bottom: 24px;">
            <div class="services-grid" style="margin-top: 0;">
                <a href="{{ route('payments.index') }}" class="service-card">
                    <div class="service-icon">₿</div>
                    <h3>Online plaćanja</h3>
                    <p>Uplate komunalija, taksi i naknada.</p>
                </a>
                <a href="{{ route('tenders.index') }}" class="service-card">
                    <div class="service-icon">§</div>
                    <h3>Tenderi</h3>
                    <p>Otkup i pregled tenderske dokumentacije.</p>
                </a>
                @if($isLegalEntity)
                <div class="service-card" style="border-color: var(--primary); background: linear-gradient(135deg, rgba(11,61,145,0.05), rgba(11,61,145,0.1));">
                    <div class="service-icon">🏢</div>
                    <h3>Servisi za pravna lica</h3>
                    <p>Izvještaji i administrativne procedure.</p>
                </div>
                @endif
            </div>
        </div>
        @endif

        <!-- Administratorski Dashboard - Administrator konkursa -->
        @if ($isCompetitionAdmin)
            <!-- Statistike -->
            <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-top: 16px;">
                <div class="info-card">
                    <h3 style="color: #6b7280; font-size: 12px; font-weight: 600; margin: 0 0 8px; text-transform: uppercase; letter-spacing: 0.5px;">Konkursi</h3>
                    <p style="font-size: 28px; font-weight: 800; color: var(--primary); margin: 0; line-height: 1;">{{ $stats['total_competitions'] ?? 0 }}</p>
                </div>
                <div class="info-card">
                    <h3 style="color: #6b7280; font-size: 12px; font-weight: 600; margin: 0 0 8px; text-transform: uppercase; letter-spacing: 0.5px;">Prijave</h3>
                    <p style="font-size: 28px; font-weight: 800; color: var(--primary); margin: 0; line-height: 1;">{{ $stats['total_applications'] ?? 0 }}</p>
                </div>
                <div class="info-card">
                    <h3 style="color: #6b7280; font-size: 12px; font-weight: 600; margin: 0 0 8px; text-transform: uppercase; letter-spacing: 0.5px;">Komisije</h3>
                    <p style="font-size: 28px; font-weight: 800; color: var(--primary); margin: 0; line-height: 1;">{{ $stats['total_commissions'] ?? 0 }}</p>
                    <p style="color: #6b7280; font-size: 11px; margin-top: 4px;">Aktivnih: {{ $stats['active_commissions'] ?? 0 }}</p>
                </div>
            </div>

            <!-- Brzi linkovi -->
            <div class="info-card" style="margin-top: 16px;">
                <div class="info-card-header">
                    <h2>Brzi linkovi</h2>
                </div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; padding: 10px;">
                    <a href="{{ route('admin.competitions.index') }}" style="padding: 10px; background: #f9fafb; border-radius: 8px; text-align: center; color: var(--primary); text-decoration: none; font-weight: 600; font-size: 13px; transition: background 0.2s;">
                        📋 Konkursi
                    </a>
                    <a href="{{ route('admin.commissions.index') }}" style="padding: 10px; background: #f9fafb; border-radius: 8px; text-align: center; color: var(--primary); text-decoration: none; font-weight: 600; font-size: 13px; transition: background 0.2s;">
                        👥 Komisija
                    </a>
                </div>
            </div>

            <!-- Najnovije prijave -->
            @if(isset($recent_applications))
            <div class="info-card" style="margin-top: 16px;">
                <div class="info-card-header">
                    <h2>Najnovije prijave na konkurse</h2>
                </div>
                <div style="padding: 10px;">
                    @forelse($recent_applications as $application)
                        <div style="padding: 10px 0; border-bottom: 1px solid #e5e7eb;">
                            <p style="font-weight: 600; color: #111827; margin: 0 0 2px; font-size: 13px;">{{ $application->user->name ?? 'N/A' }}</p>
                            <p style="color: #6b7280; font-size: 12px; margin: 0 0 2px;">{{ $application->competition->title ?? 'N/A' }}</p>
                            <p style="color: #9ca3af; font-size: 11px; margin: 0;">{{ $application->created_at->format('d.m.Y H:i') }}</p>
                        </div>
                    @empty
                        <p style="color: #6b7280; text-align: center; padding: 16px; font-size: 12px;">Nema prijava</p>
                    @endforelse
                </div>
            </div>
            @endif
        @endif
    </div>
</div>
@endsection
