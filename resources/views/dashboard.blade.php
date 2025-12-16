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
        margin: 0;
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

<div class="user-dashboard">
    <div class="container mx-auto px-4">
        <!-- Header -->
        <div class="dashboard-header">
            <h1>Moj Panel</h1>
            <p class="welcome-text">Dobrodošli, {{ auth()->user()->name ?? 'Korisnik' }}</p>
        </div>

        <!-- Alerts -->
        @if (session('verified'))
            <div class="alert alert-success">
                <strong>Uspešno!</strong> Vaša email adresa je verifikovana.
            </div>
        @endif

        @if (!auth()->user()->hasVerifiedEmail())
            <div class="alert alert-warning">
                <strong>Važno:</strong> Molimo vas da <a href="{{ route('verification.notice') }}">verifikujete svoju email adresu</a>.
            </div>
        @endif

        <!-- Services -->
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
    </div>
</div>
@endsection
