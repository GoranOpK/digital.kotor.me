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
            grid-template-columns: 2fr 1.5fr;
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
    $isPhysicalPerson = $user->user_type === 'Fizičko lice';
    $isResident = $user->residential_status === 'resident';
    $isNonResident = $user->residential_status === 'non-resident';
    $isLegalEntity = $user->user_type !== 'Fizičko lice';
    
    // Određivanje tipa korisnika za prikaz
    if ($isSuperAdmin) {
        $userTypeLabel = 'Super Administrator';
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
                <strong>Uspešno!</strong> Vaša email adresa je verifikovana.
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

        <div class="top-grid">
            <!-- Informacije o korisniku (ne prikazuje se za super admin i konkurs admin) -->
            @if (!$isSuperAdmin && !$isCompetitionAdmin)
            <div class="info-card">
                <div class="info-card-header">
                    <h2>Informacije o korisniku</h2>
                    <a href="{{ route('profile.edit') }}" class="btn-edit">Izmijeni podatke</a>
                </div>
                <div class="info-grid">
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
                    @if($user->passport_number)
                        <div class="info-item">
                            <span class="info-label">Broj pasoša</span>
                            <span class="info-value">{{ $user->passport_number }}</span>
                        </div>
                    @endif
                    <div class="info-item">
                        <span class="info-label">Status naloga</span>
                        <span class="info-value">
                            <span style="display: inline-block; padding: 4px 12px; border-radius: 9999px; font-size: 12px; font-weight: 600; {{ $user->activation_status === 'active' ? 'background: #d1fae5; color: #065f46;' : 'background: #fee2e2; color: #991b1b;' }}">
                                {{ $user->activation_status === 'active' ? 'Aktivan' : 'Deaktiviran' }}
                            </span>
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Email verifikovan</span>
                        <span class="info-value">
                            @if($user->email_verified_at)
                                <span style="color: #065f46; font-weight: 600;">Da ({{ $user->email_verified_at->format('d.m.Y H:i') }})</span>
                            @else
                                <span style="color: #991b1b; font-weight: 600;">Ne</span>
                            @endif
                        </span>
                    </div>
                </div>
            </div>
            @endif

            <!-- Kratka sekcija za biblioteku dokumenata (ne prikazuje se za super admin i konkurs admin) -->
            @if (!$isSuperAdmin && !$isCompetitionAdmin)
            <div class="info-card" style="min-height: 100%;">
                <div class="info-card-header">
                    <h2>Moja biblioteka dokumenata</h2>
                    <a href="{{ route('documents.index') }}" class="btn-edit">Otvori biblioteku</a>
                </div>
                <p style="margin: 0 0 12px; color: #6b7280; font-size: 14px;">
                    Centralno mjesto gdje možete čuvati lična, finansijska i poslovna dokumenta i koristiti ih pri prijavama na konkurse i tendere.
                </p>
                <div style="margin-top: 16px; padding-top: 16px; border-top: 1px solid #e5e7eb;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <span style="font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px;">Iskorišćen prostor</span>
                        <span style="font-size: 14px; font-weight: 600; color: var(--primary);">{{ $usedStorageMB }} MB / {{ $maxStorageMB }} MB</span>
                    </div>
                    <div style="width: 100%; height: 8px; background: #e5e7eb; border-radius: 4px; overflow: hidden;">
                        <div style="height: 100%; background: linear-gradient(90deg, var(--primary), var(--primary-dark)); width: {{ min($storagePercentage, 100) }}%; transition: width 0.3s ease;"></div>
                    </div>
                    <div style="font-size: 12px; color: #6b7280; margin-top: 4px;">
                        {{ $storagePercentage }}% iskorišćeno
                    </div>
                </div>
            </div>
            @endif
        </div>

        <!-- Services - Super Admin (vidi sve opcije) -->
        @if ($isSuperAdmin)
            <div class="services-grid">
                <a href="{{ route('payments.index') }}" class="service-card">
                    <div class="service-icon">₿</div>
                    <h3>Online plaćanja</h3>
                    <p>Uplate komunalija, taksi i drugih opštinskih naknada. Pregled istorije uplata i novih zahteva.</p>
                </a>

                <a href="{{ route('competitions.index') }}" class="service-card">
                    <div class="service-icon">★</div>
                    <h3>Moje prijave na konkurse</h3>
                    <p>Prijava i praćenje statusa na programe podrške. Pregled svih vaših prijava i njihovog stanja.</p>
                </a>

                <a href="{{ route('tenders.index') }}" class="service-card">
                    <div class="service-icon">§</div>
                    <h3>Moje tenderske kupovine</h3>
                    <p>Pregled, preuzimanje i otkup tenderske dokumentacije. Istorija svih vaših kupovina.</p>
                </a>

                <a href="{{ route('admin.dashboard') }}" class="service-card" style="border-color: var(--primary); background: linear-gradient(135deg, rgba(11,61,145,0.05), rgba(11,61,145,0.1));">
                    <div class="service-icon" style="border-color: var(--primary);">⚙️</div>
                    <h3>Administracija</h3>
                    <p>Upravljanje korisnicima, konkursima, tenderima i svim aspektima sistema.</p>
                </a>

            </div>
        @endif

        <!-- Services - Fizičko lice (Rezident) -->
        @if (!$isSuperAdmin && $isPhysicalPerson && $isResident)
            <div class="services-grid">
                <a href="{{ route('payments.index') }}" class="service-card">
                    <div class="service-icon">₿</div>
                    <h3>Online plaćanja</h3>
                    <p>Uplate komunalija, taksi i drugih opštinskih naknada. Pregled istorije uplata i novih zahteva.</p>
                </a>

                <a href="{{ route('competitions.index') }}" class="service-card">
                    <div class="service-icon">★</div>
                    <h3>Moje prijave na konkurse</h3>
                    <p>Prijava i praćenje statusa na programe podrške. Pregled svih vaših prijava i njihovog stanja.</p>
                </a>

                <a href="{{ route('tenders.index') }}" class="service-card">
                    <div class="service-icon">§</div>
                    <h3>Moje tenderske kupovine</h3>
                    <p>Pregled, preuzimanje i otkup tenderske dokumentacije. Istorija svih vaših kupovina.</p>
                </a>

            </div>
        @endif

        <!-- Services - Fizičko lice (Nerezident) -->
        @if (!$isSuperAdmin && $isPhysicalPerson && $isNonResident)
            <div class="services-grid">
                <a href="{{ route('payments.index') }}" class="service-card">
                    <div class="service-icon">₿</div>
                    <h3>Online plaćanja</h3>
                    <p>Uplate komunalija, taksi i drugih opštinskih naknada. Pregled istorije uplata i novih zahteva.</p>
                </a>

                <a href="{{ route('competitions.index') }}" class="service-card">
                    <div class="service-icon">★</div>
                    <h3>Moje prijave na konkurse</h3>
                    <p>Prijava i praćenje statusa na programe podrške. Pregled svih vaših prijava i njihovog stanja.</p>
                </a>

                <a href="{{ route('tenders.index') }}" class="service-card">
                    <div class="service-icon">§</div>
                    <h3>Moje tenderske kupovine</h3>
                    <p>Pregled, preuzimanje i otkup tenderske dokumentacije. Istorija svih vaših kupovina.</p>
                </a>

                <div class="service-card" style="border-color: var(--secondary); background: linear-gradient(135deg, rgba(184,134,11,0.05), rgba(184,134,11,0.1));">
                    <div class="service-icon" style="border-color: var(--secondary); color: var(--secondary);">🌍</div>
                    <h3>Nerezident servisi</h3>
                    <p>Dodatne informacije i usluge dostupne nerezidentima. Pregled posebnih procedura i zahteva.</p>
                </div>

            </div>
        @endif

        <!-- Services - Pravno lice -->
        @if (!$isSuperAdmin && $isLegalEntity)
            <div class="services-grid">
                <a href="{{ route('payments.index') }}" class="service-card">
                    <div class="service-icon">₿</div>
                    <h3>Online plaćanja</h3>
                    <p>Uplate komunalija, taksi i drugih opštinskih naknada. Pregled istorije uplata i novih zahteva.</p>
                </a>

                <a href="{{ route('competitions.index') }}" class="service-card">
                    <div class="service-icon">★</div>
                    <h3>Moje prijave na konkurse</h3>
                    <p>Prijava i praćenje statusa na programe podrške. Pregled svih vaših prijava i njihovog stanja.</p>
                </a>

                <a href="{{ route('tenders.index') }}" class="service-card">
                    <div class="service-icon">§</div>
                    <h3>Moje tenderske kupovine</h3>
                    <p>Pregled, preuzimanje i otkup tenderske dokumentacije. Istorija svih vaših kupovina.</p>
                </a>

                <div class="service-card" style="border-color: var(--primary); background: linear-gradient(135deg, rgba(11,61,145,0.05), rgba(11,61,145,0.1));">
                    <div class="service-icon" style="border-color: var(--primary);">🏢</div>
                    <h3>Servisi za pravna lica</h3>
                    <p>Specijalizovane usluge za privredne subjekte. Pregled dokumentacije, izvještaja i administrativnih procedura.</p>
                </div>

                <div class="service-card" style="border-color: var(--primary); background: linear-gradient(135deg, rgba(11,61,145,0.05), rgba(11,61,145,0.1));">
                    <div class="service-icon" style="border-color: var(--primary);">📋</div>
                    <h3>Upravljanje dokumentacijom</h3>
                    <p>Centralizovano upravljanje svim dokumentima vašeg privrednog subjekta. Pregled i ažuriranje podataka.</p>
                </div>

            </div>
        @endif
    </div>
</div>
@endsection
