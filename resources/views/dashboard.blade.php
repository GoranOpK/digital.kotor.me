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
</style>

@php
    $user = auth()->user();
    $isPhysicalPerson = $user->user_type === 'Fizičko lice';
    $isResident = $user->residential_status === 'resident';
    $isNonResident = $user->residential_status === 'non-resident';
    $isLegalEntity = $user->user_type !== 'Fizičko lice';
    
    // Određivanje tipa korisnika za prikaz
    if ($isPhysicalPerson && $isResident) {
        $userTypeLabel = 'Fizičko lice (Rezident)';
    } elseif ($isPhysicalPerson && $isNonResident) {
        $userTypeLabel = 'Fizičko lice (Nerezident)';
    } else {
        $userTypeLabel = $user->user_type ?? 'Pravno lice';
    }
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

        <!-- Services - Fizičko lice (Rezident) -->
        @if ($isPhysicalPerson && $isResident)
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
        @if ($isPhysicalPerson && $isNonResident)
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
        @if ($isLegalEntity)
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
