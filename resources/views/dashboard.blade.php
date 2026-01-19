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
        padding: 24px;
        border-radius: 16px;
        margin-bottom: 24px;
    }
    .dashboard-header h1 {
        color: #fff;
        font-size: 32px;
        font-weight: 700;
        margin: 0 0 8px;
    }
    .dashboard-header .welcome-text {
        color: rgba(255, 255, 255, 0.9);
        font-size: 16px;
        margin: 0 0 4px;
    }
    .dashboard-header .user-type-badge {
        display: inline-block;
        background: rgba(255, 255, 255, 0.2);
        padding: 4px 12px;
        border-radius: 9999px;
        font-size: 12px;
        font-weight: 600;
        margin-top: 8px;
    }
    .alert {
        border-radius: 12px;
        padding: 16px 20px;
        margin-bottom: 20px;
        border: 1px solid;
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
    .alert strong {
        display: block;
        margin-bottom: 4px;
    }
    .alert a {
        color: inherit;
        text-decoration: underline;
        font-weight: 600;
    }
    .top-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 16px;
        margin-bottom: 24px;
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
        margin-top: 24px;
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
        border-radius: 16px;
        padding: 24px;
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
        width: 48px;
        height: 48px;
        border-radius: 12px;
        border: 2px solid var(--primary);
        color: var(--primary);
        background: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 24px;
        margin-bottom: 16px;
    }
    .service-card h3 {
        font-size: 18px;
        font-weight: 700;
        color: #111827;
        margin: 0 0 8px;
    }
    .service-card p {
        color: #6b7280;
        font-size: 14px;
        margin: 0;
        line-height: 1.5;
    }
    .info-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 1px 2px rgba(0,0,0,.06);
        margin-bottom: 24px;
        height: 100%;
        display: flex;
        flex-direction: column;
    }
    .info-card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 16px;
        border-bottom: 1px solid #e5e7eb;
    }
    .info-card-header h2 {
        font-size: 20px;
        font-weight: 700;
        color: #111827;
        margin: 0;
    }
    .info-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 16px;
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
        font-size: 12px;
        font-weight: 600;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 4px;
    }
    .info-value {
        font-size: 14px;
        color: #111827;
        font-weight: 500;
    }
    .btn-edit {
        background: var(--primary);
        color: #fff;
        padding: 8px 16px;
        border-radius: 8px;
        text-decoration: none;
        font-size: 14px;
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
    $isKomisija = isset($isKomisija) ? $isKomisija : ($user->role && $user->role->name === 'komisija');
    $isPhysicalPerson = $user->user_type === 'Fizičko lice';
    $isResident = $user->residential_status === 'resident';
    $isNonResident = $user->residential_status === 'non-resident';
    $isLegalEntity = $user->user_type !== 'Fizičko lice';
    
    // Određivanje tipa korisnika za prikaz
    if ($isSuperAdmin) {
        $userTypeLabel = 'Super Administrator';
    } elseif ($isCompetitionAdmin) {
        $userTypeLabel = 'Administrator konkursa';
    } elseif ($isKomisija && isset($commissionMember)) {
        $positionLabel = $commissionMember->position === 'predsjednik' ? 'Predsjednik' : 'Član';
        $userTypeLabel = $positionLabel . ' komisije';
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
            <p class="welcome-text">Dobrodošli, @if($isCompetitionAdmin) Administrator konkursa @else {{ $user->name ?? 'Korisnik' }} @endif</p>
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
                <div class="info-grid" style="grid-template-columns: 1fr; gap: 12px;">
                    <div class="info-item">
                        <span class="info-label">Ime i prezime</span>
                        <span class="info-value">{{ $user->name ?? 'N/A' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Email adresa</span>
                        <span class="info-value">{{ $user->email ?? 'N/A' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Broj telefona</span>
                        <span class="info-value">{{ $user->phone ?? 'N/A' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Adresa</span>
                        <span class="info-value">{{ $user->address ?? 'N/A' }}</span>
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
                <p style="margin: 0 0 16px; color: #6b7280; font-size: 13px;">
                    Čuvajte lična, finansijska i poslovna dokumenta za prijave na konkurse i tendere.
                </p>
                <div style="margin-top: auto; padding-top: 16px; border-top: 1px solid #e5e7eb;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <span style="font-size: 11px; font-weight: 600; color: #6b7280; text-transform: uppercase;">Iskorišćeno</span>
                        <span style="font-size: 13px; font-weight: 600; color: var(--primary);">{{ $usedStorageMB ?? 0 }} MB / 20 MB</span>
                    </div>
                    <div style="width: 100%; height: 8px; background: #e5e7eb; border-radius: 4px; overflow: hidden;">
                        <div style="height: 100%; background: linear-gradient(90deg, var(--primary), var(--primary-dark)); width: {{ min($storagePercentage ?? 0, 100) }}%; transition: width 0.3s ease;"></div>
                    </div>
                    <div style="font-size: 11px; color: #6b7280; margin-top: 4px;">
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
                @php
                    // Za članove komisije koristi $myApplications, za ostale $applications
                    $myApplicationsList = isset($myApplications) ? $myApplications : (isset($applications) ? $applications : collect());
                @endphp
                @if($myApplicationsList->count() > 0)
                    <div style="overflow-y: auto; max-height: 350px;">
                        @foreach($myApplicationsList->take(5) as $app)
                            <div style="padding: 12px 0; border-bottom: 1px solid #f3f4f6;">
                                <div style="font-weight: 600; color: #111827; font-size: 13px;">{{ Str::limit($app->business_plan_name, 25) }}</div>
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 4px;">
                                    @php
                                        $statusLabels = ['draft' => 'Nacrt', 'submitted' => 'U obradi', 'evaluated' => 'Ocjenjena', 'approved' => 'Odobrena', 'rejected' => 'Odbijena'];
                                        $statusColors = ['draft' => 'background: #fef3c7; color: #92400e;', 'submitted' => 'background: #dbeafe; color: #1e40af;', 'evaluated' => 'background: #d1fae5; color: #065f46;', 'approved' => 'background: #d1fae5; color: #065f46;', 'rejected' => 'background: #fee2e2; color: #991b1b;'];
                                    @endphp
                                    <span style="display: inline-block; padding: 2px 8px; border-radius: 9999px; font-size: 10px; font-weight: 600; {{ $statusColors[$app->status] ?? '' }}">
                                        {{ $statusLabels[$app->status] ?? $app->status }}
                                    </span>
                                    <div style="display: flex; gap: 8px;">
                                        <a href="{{ route('applications.show', $app) }}" style="color: var(--primary); font-weight: 600; text-decoration: none; font-size: 12px;">Pregled</a>
                                        <form action="{{ route('applications.destroy', $app) }}" method="POST" onsubmit="return confirm('Obrisati prijavu?');" style="display: inline;">
                                            @csrf @method('DELETE')
                                            <button type="submit" style="background: none; border: none; color: #ef4444; font-weight: 600; cursor: pointer; font-size: 12px; padding: 0;">Obriši</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        @if($myApplicationsList->count() > 5)
                            <p style="text-align: center; margin-top: 12px; font-size: 12px; color: #6b7280;">+ još {{ $myApplicationsList->count() - 5 }} prijave</p>
                        @endif
                    </div>
                @else
                    <div style="text-align: center; padding: 24px 0;">
                        <p style="color: #6b7280; font-size: 13px; margin-bottom: 12px;">Nemate aktivnih prijava.</p>
                        <a href="{{ route('competitions.index') }}" class="btn-edit" style="font-size: 12px;">Prijavi se</a>
                    </div>
                @endif
            </div>
        </div>

        <!-- Brzi servisi (Plati, Konkursi, Tenderi) -->
        <div style="margin-bottom: 24px;">
            <div class="services-grid" style="margin-top: 0;">
                <a href="{{ route('payments.index') }}" class="service-card">
                    <div class="service-icon" style="width: 40px; height: 40px; font-size: 20px;">₿</div>
                    <h3 style="font-size: 16px;">Online plaćanja</h3>
                    <p style="font-size: 12px;">Uplate komunalija, taksi i naknada.</p>
                </a>
                <a href="{{ route('competitions.index') }}" class="service-card">
                    <div class="service-icon" style="width: 40px; height: 40px; font-size: 20px;">🏆</div>
                    <h3 style="font-size: 16px;">Konkursi</h3>
                    <p style="font-size: 12px;">Prijave na konkurse za podršku preduzetništvu.</p>
                </a>
                <a href="{{ route('tenders.index') }}" class="service-card">
                    <div class="service-icon" style="width: 40px; height: 40px; font-size: 20px;">§</div>
                    <h3 style="font-size: 16px;">Tenderi</h3>
                    <p style="font-size: 12px;">Otkup i pregled tenderske dokumentacije.</p>
                </a>
                @if($isLegalEntity)
                <div class="service-card" style="border-color: var(--primary); background: linear-gradient(135deg, rgba(11,61,145,0.05), rgba(11,61,145,0.1));">
                    <div class="service-icon" style="width: 40px; height: 40px; font-size: 20px;">🏢</div>
                    <h3 style="font-size: 16px;">Servisi za pravna lica</h3>
                    <p style="font-size: 12px;">Izvještaji i administrativne procedure.</p>
                </div>
                @endif
            </div>
        </div>
        @endif

        <!-- Services - Super Admin (samo Administracija) -->
        @if ($isSuperAdmin)
            <div class="services-grid">
                <a href="{{ route('admin.dashboard') }}" class="service-card" style="border-color: var(--primary); background: linear-gradient(135deg, rgba(11,61,145,0.05), rgba(11,61,145,0.1));">
                    <div class="service-icon">⚙️</div>
                    <h3>Administracija</h3>
                    <p>Upravljanje korisnicima, konkursima, tenderima i svim aspektima sistema.</p>
                </a>
            </div>
        @endif

        <!-- Dashboard za članove komisije -->
        @if ($isKomisija && isset($commissionMember) && isset($commission))
            <!-- Informacije o komisiji i konkursu -->
            <div class="info-grid" style="margin-top: 24px;">
                <div class="info-card">
                    <div class="info-card-header">
                        <h2>Informacije o komisiji</h2>
                    </div>
                    <div class="info-grid" style="grid-template-columns: 1fr; gap: 12px;">
                        <div class="info-item">
                            <span class="info-label">Naziv komisije</span>
                            <span class="info-value">{{ $commission->name }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Godina</span>
                            <span class="info-value">{{ $commission->year }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Mandat</span>
                            <span class="info-value">{{ $commission->start_date->format('d.m.Y') }} - {{ $commission->end_date->format('d.m.Y') }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Pozicija</span>
                            <span class="info-value">{{ $commissionMember->position === 'predsjednik' ? 'Predsjednik' : 'Član' }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Status</span>
                            <span class="info-value" style="color: {{ $commission->status === 'active' ? '#10b981' : '#6b7280' }};">
                                {{ $commission->status === 'active' ? 'Aktivna' : 'Neaktivna' }}
                            </span>
                        </div>
                    </div>
                </div>

                @if($commission->competitions->count() > 0)
                <div class="info-card">
                    <div class="info-card-header">
                        <h2>Dodijeljeni konkursi</h2>
                    </div>
                    <div style="padding: 20px;">
                        @foreach($commission->competitions as $competition)
                            <div style="padding: 16px 0; border-bottom: 1px solid #e5e7eb;">
                                <h3 style="font-size: 18px; font-weight: 700; color: #111827; margin: 0 0 8px;">
                                    <a href="{{ route('admin.competitions.show', $competition) }}" style="color: var(--primary); text-decoration: none;">
                                        {{ $competition->title }}
                                    </a>
                                </h3>
                                <div class="info-grid" style="grid-template-columns: 1fr; gap: 8px; margin-top: 12px;">
                                    <div class="info-item">
                                        <span class="info-label">Godina</span>
                                        <span class="info-value">{{ $competition->year }}</span>
                                    </div>
                                    @if($competition->published_at)
                                    <div class="info-item">
                                        <span class="info-label">Datum objave</span>
                                        <span class="info-value">{{ $competition->published_at->format('d.m.Y') }}</span>
                                    </div>
                                    @endif
                                    @if($competition->deadline)
                                    <div class="info-item">
                                        <span class="info-label">Rok za prijave</span>
                                        <span class="info-value">{{ $competition->deadline->format('d.m.Y') }}</span>
                                    </div>
                                    @endif
                                    <div class="info-item">
                                        <span class="info-label">Status</span>
                                        <span class="info-value" style="color: {{ $competition->status === 'published' ? '#10b981' : ($competition->status === 'closed' ? '#ef4444' : '#6b7280') }};">
                                            @if($competition->status === 'published') Objavljen
                                            @elseif($competition->status === 'closed') Zatvoren
                                            @elseif($competition->status === 'draft') Nacrt
                                            @else Završen
                                            @endif
                                        </span>
                                    </div>
                                </div>
                                <div style="margin-top: 12px;">
                                    <a href="{{ route('admin.competitions.show', $competition) }}" class="btn-edit" style="font-size: 12px; padding: 6px 12px; display: inline-block;">
                                        Pregled konkursa
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                @else
                <div class="info-card">
                    <div class="info-card-header">
                        <h2>Dodijeljeni konkursi</h2>
                    </div>
                    <div style="padding: 20px; text-align: center;">
                        <p style="color: #6b7280; font-size: 14px;">Komisiji još nije dodijeljen nijedan konkurs.</p>
                    </div>
                </div>
                @endif
            </div>

            <!-- Prijave za ocjenjivanje -->
            @if(isset($applications) && $applications->count() > 0)
            <div class="info-card" style="margin-top: 24px;">
                <div class="info-card-header">
                    <h2>Prijave za ocjenjivanje</h2>
                </div>
                <div style="padding: 20px;">
                    @foreach($applications as $application)
                        <div style="padding: 16px 0; border-bottom: 1px solid #e5e7eb;">
                            <h3 style="font-size: 16px; font-weight: 600; color: #111827; margin: 0 0 8px;">
                                <a href="{{ route('evaluation.show', $application) }}" style="color: var(--primary); text-decoration: none;">
                                    {{ $application->business_plan_name ?? 'Naziv biznis plana' }}
                                </a>
                            </h3>
                            <p style="color: #6b7280; font-size: 14px; margin: 0 0 4px;">
                                Konkurs: {{ $application->competition->title ?? 'N/A' }}
                            </p>
                            <p style="color: #9ca3af; font-size: 12px; margin: 0 0 8px;">
                                Podnosilac: {{ $application->user->name ?? 'N/A' }} | {{ $application->created_at->format('d.m.Y H:i') }}
                            </p>
                            @if(isset($application->is_evaluated_by_member) && $application->is_evaluated_by_member)
                                <p style="color: #10b981; font-size: 12px; font-weight: 600; margin: 0 0 8px;">
                                    ✓ Ocjenjeno
                                </p>
                            @endif
                            <div style="display: flex; gap: 8px; margin-top: 8px;">
                                <a href="{{ route('evaluation.show', $application) }}" class="btn-edit" style="font-size: 12px; padding: 6px 12px;">
                                    {{ isset($application->is_evaluated_by_member) && $application->is_evaluated_by_member ? 'Pregledaj ocjenu' : 'Pregledaj prijavu' }}
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            @else
            <div class="info-card" style="margin-top: 24px;">
                <div class="info-card-header">
                    <h2>Prijave za ocjenjivanje</h2>
                </div>
                <div style="padding: 20px; text-align: center;">
                    <p style="color: #6b7280; font-size: 14px;">Trenutno nema prijava za ocjenjivanje.</p>
                </div>
            </div>
            @endif
        @endif

        <!-- Administratorski Dashboard - Administrator konkursa -->
        @if ($isCompetitionAdmin)
            <!-- Statistike -->
            <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 24px;">
                <div class="info-card">
                    <h3 style="color: #6b7280; font-size: 14px; font-weight: 600; margin: 0 0 12px; text-transform: uppercase; letter-spacing: 0.5px;">Konkursi</h3>
                    <p style="font-size: 36px; font-weight: 800; color: var(--primary); margin: 0; line-height: 1;">{{ $stats['total_competitions'] ?? 0 }}</p>
                </div>
                <div class="info-card">
                    <h3 style="color: #6b7280; font-size: 14px; font-weight: 600; margin: 0 0 12px; text-transform: uppercase; letter-spacing: 0.5px;">Komisije</h3>
                    <p style="font-size: 36px; font-weight: 800; color: var(--primary); margin: 0; line-height: 1;">{{ $stats['total_commissions'] ?? 0 }}</p>
                    <p style="color: #6b7280; font-size: 13px; margin-top: 8px;">Aktivnih: {{ $stats['active_commissions'] ?? 0 }}</p>
                </div>
            </div>

            <!-- Brzi linkovi -->
            <div class="info-card" style="margin-top: 24px;">
                <div class="info-card-header">
                    <h2>Brzi linkovi</h2>
                </div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; padding: 20px;">
                    <a href="{{ route('admin.competitions.index') }}" style="padding: 16px; background: linear-gradient(135deg, var(--primary), var(--primary-dark)); border-radius: 8px; text-align: center; color: #fff; text-decoration: none; font-weight: 600; transition: all 0.2s; box-shadow: 0 2px 4px rgba(0,0,0,0.1);" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 8px rgba(0,0,0,0.15)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 4px rgba(0,0,0,0.1)';">
                        📋 Upravljanje konkursima
                    </a>
                    <a href="{{ route('competitions.archive') }}" style="padding: 16px; background: linear-gradient(135deg, var(--primary), var(--primary-dark)); border-radius: 8px; text-align: center; color: #fff; text-decoration: none; font-weight: 600; transition: all 0.2s; box-shadow: 0 2px 4px rgba(0,0,0,0.1);" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 8px rgba(0,0,0,0.15)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 4px rgba(0,0,0,0.1)';">
                        📁 Arhiva konkursa
                    </a>
                    <a href="{{ route('admin.commissions.index') }}" style="padding: 16px; background: linear-gradient(135deg, var(--primary), var(--primary-dark)); border-radius: 8px; text-align: center; color: #fff; text-decoration: none; font-weight: 600; transition: all 0.2s; box-shadow: 0 2px 4px rgba(0,0,0,0.1);" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 8px rgba(0,0,0,0.15)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 4px rgba(0,0,0,0.1)';">
                        👥 Komisija
                    </a>
                </div>
            </div>
        @endif

        <!-- Najnovije prijave (samo za članove komisije) -->
        @if(isset($recent_applications) && $isKomisija)
        <div class="info-card" style="margin-top: 24px;">
            <div class="info-card-header">
                <h2>Najnovije prijave na konkurse</h2>
            </div>
            <div style="padding: 20px;">
                @forelse($recent_applications as $application)
                    <div style="padding: 16px 0; border-bottom: 1px solid #e5e7eb;">
                        <p style="font-weight: 600; color: #111827; margin: 0 0 4px;">{{ $application->user->name ?? 'N/A' }}</p>
                        <p style="color: #6b7280; font-size: 14px; margin: 0 0 4px;">{{ $application->competition->title ?? 'N/A' }}</p>
                        <p style="color: #9ca3af; font-size: 12px; margin: 0;">{{ $application->created_at->format('d.m.Y H:i') }}</p>
                    </div>
                @empty
                    <p style="color: #6b7280; text-align: center; padding: 24px;">Nema prijava</p>
                @endforelse
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
