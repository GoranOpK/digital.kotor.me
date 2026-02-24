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
                                <div style="font-weight: 600; color: #111827; font-size: 13px;">{{ Str::limit($app->business_plan_name ?? 'Bez naziva', 25) }}</div>
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 4px;">
                                    @php
                                        $statusLabels = ['draft' => 'Nacrt', 'submitted' => 'U obradi', 'evaluated' => 'Ocjenjena', 'approved' => 'Odobrena', 'rejected' => 'Odbijena'];
                                        $statusColors = ['draft' => 'background: #fef3c7; color: #92400e;', 'submitted' => 'background: #dbeafe; color: #1e40af;', 'evaluated' => 'background: #d1fae5; color: #065f46;', 'approved' => 'background: #d1fae5; color: #065f46;', 'rejected' => 'background: #fee2e2; color: #991b1b;'];

                                        // Badge stil
                                        $badgeBaseStyle = 'display: inline-block; padding: 2px 8px; border-radius: 9999px; font-size: 10px; font-weight: 600; margin-right: 4px;';

                                        // 1) Status Obrasca 1a/1b
                                        $obrazacLabel = null;
                                        $obrazacStyle = '';
                                        $obrazacUrl = null;
                                        if ($app->applicant_type) {
                                            $isObrazacComplete = $app->isObrazacComplete();

                                            if ($isObrazacComplete) {
                                                // Obrazac je kompletan
                                                if ($app->applicant_type === 'preduzetnica') {
                                                    $obrazacLabel = 'Obrazac 1a popunjen';
                                                    $obrazacStyle = 'background: #d1fae5; color: #065f46;';
                                                } elseif (in_array($app->applicant_type, ['doo', 'ostalo'])) {
                                                    $obrazacLabel = 'Obrazac 1b popunjen';
                                                    $obrazacStyle = 'background: #d1fae5; color: #065f46;';
                                                } elseif ($app->applicant_type === 'fizicko_lice') {
                                                    $obrazacLabel = 'Obrazac 1a/1b popunjen';
                                                    $obrazacStyle = 'background: #d1fae5; color: #065f46;';
                                                }
                                                // Klik na badge vodi direktno na popunjen obrazac (formu prijave sa application_id kao query parametar)
                                                $obrazacUrl = route('applications.create', $app->competition_id) . '?application_id=' . $app->id;
                                            } else {
                                                // Obrazac nije kompletan (nacrt)
                                                if ($app->applicant_type === 'preduzetnica') {
                                                    $obrazacLabel = 'Obrazac 1a - Nacrt';
                                                    $obrazacStyle = 'background: #fef3c7; color: #92400e;';
                                                } elseif (in_array($app->applicant_type, ['doo', 'ostalo'])) {
                                                    $obrazacLabel = 'Obrazac 1b - Nacrt';
                                                    $obrazacStyle = 'background: #fef3c7; color: #92400e;';
                                                } elseif ($app->applicant_type === 'fizicko_lice') {
                                                    $obrazacLabel = 'Obrazac 1a/1b - Nacrt';
                                                    $obrazacStyle = 'background: #fef3c7; color: #92400e;';
                                                }
                                                // Klik na badge vodi na nastavak popunjavanja Obrasca 1a/1b
                                                $obrazacUrl = route('applications.create', $app->competition_id) . '?application_id=' . $app->id;
                                            }
                                        }

                                        // 2) Status biznis plana
                                        $bizPlanLabel = null;
                                        $bizPlanStyle = '';
                                        $bizPlanUrl = null;
                                        if ($app->businessPlan) {
                                            if ($app->businessPlan->isComplete()) {
                                                $bizPlanLabel = 'Biznis plan – popunjen';
                                                $bizPlanStyle = 'background: #d1fae5; color: #065f46;';
                                            } else {
                                                $bizPlanLabel = 'Biznis plan – nacrt';
                                                $bizPlanStyle = 'background: #fef3c7; color: #92400e;';
                                            }
                                            // Klik na badge uvijek vodi na formu biznis plana (nastavak ili pregled)
                                            $bizPlanUrl = route('applications.business-plan.create', $app);
                                        }
                                    @endphp

                                    <div style="display: flex; align-items: center; flex-wrap: wrap;">
                                        {{-- Badge za Obrazac 1a/1b (klikabilan) --}}
                                        @if($obrazacLabel && $obrazacUrl)
                                            <a href="{{ $obrazacUrl }}" style="{{ $badgeBaseStyle }} {{ $obrazacStyle }} text-decoration: none; cursor: pointer;">
                                                {{ $obrazacLabel }}
                                            </a>
                                        @endif

                                        {{-- Badge za Biznis plan, ako postoji biznis plan (klikabilan) --}}
                                        @if($bizPlanLabel && $bizPlanUrl)
                                            <a href="{{ $bizPlanUrl }}" style="{{ $badgeBaseStyle }} {{ $bizPlanStyle }} text-decoration: none; cursor: pointer;">
                                                {{ $bizPlanLabel }}
                                            </a>
                                        @endif

                                        {{-- Ako nema specifičnih bedžova, prikaži opšti status --}}
                                        @if(!$obrazacLabel && !$bizPlanLabel)
                                            <span style="{{ $badgeBaseStyle }} {{ $statusColors[$app->status] ?? '' }}">
                                                {{ $statusLabels[$app->status] ?? $app->status }}
                                            </span>
                                        @endif
                                    </div>
                                    <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 4px;">
                                        <div style="display: flex; gap: 8px; align-items: center;">
                                            {{-- Badge \"Status prijave\" kao link na prikaz prijave --}}
                                            <a href="{{ route('applications.show', $app) }}"
                                               style="display: inline-block; padding: 4px 10px; border-radius: 9999px; font-size: 11px; font-weight: 600; background: #e5e7eb; color: #111827; text-decoration: none;">
                                                Status prijave
                                            </a>
                                            @php $comp = $app->competition; $canDeleteApp = $comp && $comp->status !== 'closed' && !$comp->isApplicationDeadlinePassed(); @endphp
                                            @if($canDeleteApp)
                                            <form action="{{ route('applications.destroy', $app) }}" method="POST" onsubmit="return confirm('Obrisati prijavu?');" style="display: inline;">
                                                @csrf @method('DELETE')
                                                <button type="submit" style="background: none; border: none; color: #ef4444; font-weight: 600; cursor: pointer; font-size: 12px; padding: 0;">Obriši</button>
                                            </form>
                                            @endif
                                        </div>
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
                                    @if($competition->isApplicationDeadlinePassed() && $competition->getEvaluationDeadlineDate())
                                    <div class="info-item">
                                        <span class="info-label">Rok za odluku (30 dana)</span>
                                        <span class="info-value" style="color: {{ $competition->isEvaluationDeadlinePassed() ? '#991b1b' : '#065f46' }};">
                                            {{ $competition->getEvaluationDeadlineDate()->format('d.m.Y') }}
                                            @if(!$competition->isEvaluationDeadlinePassed())
                                                ({{ $competition->getDaysUntilEvaluationDeadline() }} {{ $competition->getDaysUntilEvaluationDeadline() == 1 ? 'dan' : 'dana' }})
                                            @else
                                                (isteklo)
                                            @endif
                                        </span>
                                    </div>
                                    @endif
                                    <div class="info-item">
                                        <span class="info-label">Status</span>
                                        <span class="info-value" style="color: {{ $competition->status === 'published' && !$competition->isApplicationDeadlinePassed() ? '#10b981' : ($competition->status === 'published' && $competition->isApplicationDeadlinePassed() ? '#92400e' : ($competition->status === 'closed' ? '#ef4444' : '#6b7280')) }};">
                                            @if($competition->status === 'published' && $competition->isApplicationDeadlinePassed()) Zatvoren za prijave
                                            @elseif($competition->status === 'published') Objavljen
                                            @elseif($competition->status === 'closed') Zatvoren
                                            @elseif($competition->status === 'draft') Nacrt
                                            @else Završen
                                            @endif
                                        </span>
                                    </div>
                                </div>
                                <div style="margin-top: 12px; display: flex; gap: 8px; flex-wrap: wrap;">
                                    <a href="{{ route('admin.competitions.show', $competition) }}" class="btn-edit" style="font-size: 12px; padding: 6px 12px; display: inline-block;">
                                        Pregled konkursa
                                    </a>
                                    @if($competition->isRankingFormed())
                                        <a href="{{ route('admin.competitions.ranking', $competition) }}" style="font-size: 12px; padding: 6px 12px; display: inline-block; background: #8b5cf6; color: #fff; border-radius: 6px; text-decoration: none;">
                                            Rang lista
                                        </a>
                                    @endif
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

            <!-- Prijave za ocjenjivanje – vidljivo tek nakon isteka roka za prijavljivanje -->
            @if(isset($showEvaluationSection) && $showEvaluationSection)
            @if(isset($applications) && $applications->count() > 0)
            <div class="info-card" style="margin-top: 24px;">
                <div class="info-card-header">
                    <h2>Prijave za ocjenjivanje</h2>
                </div>
                <div style="padding: 20px; overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; min-width: 800px;">
                        <thead>
                            <tr style="border-bottom: 2px solid #e5e7eb;">
                                <th style="padding: 12px; text-align: left;">Naziv biznis plana</th>
                                <th style="padding: 12px; text-align: left;">Podnosilac</th>
                                <th style="padding: 12px; text-align: left;">Status prijave</th>
                                <th style="padding: 12px; text-align: left;">Obrazac</th>
                                <th style="padding: 12px; text-align: left;">Biznis Plan</th>
                                <th style="padding: 12px; text-align: left;">Datum podnošenja</th>
                                <th style="padding: 12px; text-align: left;">Akcije</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($applications as $app)
                                @php
                                    $isObrazacComplete = $app->isObrazacComplete();
                                    $statusLabels = [
                                        'draft' => 'Nacrt',
                                        'submitted' => 'U obradi',
                                        'evaluated' => 'Ocjenjena',
                                        'approved' => 'Odobrena',
                                        'rejected' => 'Odbijena',
                                    ];
                                    $statusClass = 'status-' . $app->status;
                                    
                                    // Badge za Obrazac
                                    $obrazacLabel = null;
                                    $obrazacClass = 'status-draft';
                                    $obrazacUrl = null;
                                    if ($isObrazacComplete) {
                                        if ($app->applicant_type === 'preduzetnica') {
                                            $obrazacLabel = 'Obrazac 1a popunjen';
                                            $obrazacClass = 'status-evaluated';
                                        } elseif (in_array($app->applicant_type, ['doo', 'ostalo'])) {
                                            $obrazacLabel = 'Obrazac 1b popunjen';
                                            $obrazacClass = 'status-evaluated';
                                        } elseif ($app->applicant_type === 'fizicko_lice') {
                                            $obrazacLabel = 'Obrazac 1a/1b popunjen';
                                            $obrazacClass = 'status-evaluated';
                                        }
                                        $obrazacUrl = route('applications.create', $app->competition_id) . '?application_id=' . $app->id;
                                    } else {
                                        if ($app->applicant_type === 'preduzetnica') {
                                            $obrazacLabel = 'Obrazac 1a - Nacrt';
                                        } elseif (in_array($app->applicant_type, ['doo', 'ostalo'])) {
                                            $obrazacLabel = 'Obrazac 1b - Nacrt';
                                        } elseif ($app->applicant_type === 'fizicko_lice') {
                                            $obrazacLabel = 'Obrazac 1a/1b - Nacrt';
                                        }
                                        $obrazacUrl = route('applications.create', $app->competition_id) . '?application_id=' . $app->id;
                                    }
                                    
                                    // Badge za Biznis Plan
                                    $bizPlanLabel = null;
                                    $bizPlanClass = 'status-draft';
                                    $bizPlanUrl = null;
                                    if ($app->businessPlan) {
                                        if ($app->businessPlan->isComplete()) {
                                            $bizPlanLabel = 'Biznis Plan - popunjen';
                                            $bizPlanClass = 'status-evaluated';
                                        } else {
                                            $bizPlanLabel = 'Biznis Plan - nacrt';
                                            $bizPlanClass = 'status-draft';
                                        }
                                        $bizPlanUrl = route('applications.business-plan.create', $app);
                                    } elseif ($isObrazacComplete) {
                                        $bizPlanLabel = 'Biznis Plan - nacrt';
                                        $bizPlanClass = 'status-draft';
                                        $bizPlanUrl = route('applications.business-plan.create', $app);
                                    }
                                    
                                    // Provjeri da li je član komisije ocjenio prijavu
                                    $isEvaluated = isset($app->is_evaluated_by_member) && $app->is_evaluated_by_member;
                                @endphp
                                <tr style="border-bottom: 1px solid #e5e7eb;">
                                    <td style="padding: 12px; vertical-align: top;">{{ $app->business_plan_name }}</td>
                                    <td style="padding: 12px; vertical-align: top;">{{ $app->user->name ?? 'N/A' }}</td>
                                    <td style="padding: 12px; vertical-align: top;">
                                        @if($app->status === 'rejected')
                                            <a href="{{ route('applications.show', $app) }}" class="status-badge {{ $statusClass }}" style="font-size: 11px; padding: 3px 10px; text-decoration: none; cursor: pointer; display: inline-block;">
                                                {{ $statusLabels[$app->status] ?? $app->status }}
                                            </a>
                                        @else
                                            <span class="status-badge {{ $statusClass }}" style="font-size: 11px; padding: 3px 10px;">
                                                {{ $statusLabels[$app->status] ?? $app->status }}
                                            </span>
                                        @endif
                                    </td>
                                    <td style="padding: 12px; vertical-align: top;">
                                        @if($obrazacLabel && $obrazacUrl)
                                            <a href="{{ $obrazacUrl }}" class="status-badge {{ $obrazacClass }}" style="font-size: 11px; padding: 3px 10px; text-decoration: none; cursor: pointer; display: inline-block;">
                                                {{ $obrazacLabel }}
                                            </a>
                                        @else
                                            <span class="status-badge status-draft" style="font-size: 11px; padding: 3px 10px;">Nije popunjen</span>
                                        @endif
                                    </td>
                                    <td style="padding: 12px; vertical-align: top;">
                                        @if($bizPlanLabel && $bizPlanUrl)
                                            <a href="{{ $bizPlanUrl }}" class="status-badge {{ $bizPlanClass }}" style="font-size: 11px; padding: 3px 10px; text-decoration: none; cursor: pointer; display: inline-block;">
                                                {{ $bizPlanLabel }}
                                            </a>
                                        @else
                                            <span class="status-badge status-draft" style="font-size: 11px; padding: 3px 10px;">Nije dostupan</span>
                                        @endif
                                    </td>
                                    <td style="padding: 12px; vertical-align: top; font-size: 12px;">
                                        {{ $app->submitted_at ? $app->submitted_at->format('d.m.Y H:i') : '-' }}
                                    </td>
                                    <td style="padding: 12px; vertical-align: top;">
                                        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                            <a href="{{ route('admin.applications.show', $app) }}" class="btn-edit" style="font-size: 12px; padding: 6px 12px; text-decoration: none;">
                                                Pregled prijave
                                            </a>
                                            <a href="{{ route('evaluation.create', $app) }}" class="btn-edit" style="font-size: 12px; padding: 6px 12px; text-decoration: none; background: var(--primary); color: #fff;">
                                                {{ $isEvaluated ? 'Pregledaj ocjenu' : 'Ocjeni' }}
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
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

        <!-- Informacije o rokovima za konkurse (samo za članove komisije) -->
        @if(isset($commission) && $isKomisija && isset($competitions))
            @foreach($competitions as $comp)
                @php
                    $daysUntilApplicationDeadline = $comp->getDaysUntilApplicationDeadline();
                    $daysUntilEvaluationDeadline = $comp->getDaysUntilEvaluationDeadline();
                    $isApplicationDeadlinePassed = $comp->isApplicationDeadlinePassed();
                    $isEvaluationDeadlinePassed = $comp->isEvaluationDeadlinePassed();
                @endphp
                @if($comp->status === 'published' && $daysUntilApplicationDeadline !== null && !$isApplicationDeadlinePassed)
                    <div class="info-card" style="margin-top: 24px; border-left: 4px solid {{ $daysUntilApplicationDeadline <= 3 ? '#ef4444' : ($daysUntilApplicationDeadline <= 7 ? '#f59e0b' : '#10b981') }};">
                        <div class="info-card-header">
                            <h2 style="display: flex; align-items: center; gap: 8px;">
                                <span>📅</span>
                                <span>{{ $comp->title }} - Rok za prijave</span>
                            </h2>
                        </div>
                        <div style="padding: 20px;">
                            <p style="color: {{ $daysUntilApplicationDeadline <= 3 ? '#991b1b' : ($daysUntilApplicationDeadline <= 7 ? '#92400e' : '#065f46') }}; font-weight: 600; font-size: 18px; margin: 8px 0;">
                                Preostalo vremena: <strong>{{ $daysUntilApplicationDeadline }} {{ $daysUntilApplicationDeadline == 1 ? 'dan' : ($daysUntilApplicationDeadline < 5 ? 'dana' : 'dana') }}</strong>
                            </p>
                            <p style="color: #6b7280; font-size: 14px; margin: 4px 0;">
                                Rok za prijave: {{ $comp->deadline ? $comp->deadline->format('d.m.Y H:i') : 'N/A' }}
                            </p>
                        </div>
                    </div>
                @elseif((($comp->status === 'published' && $isApplicationDeadlinePassed) || $comp->status === 'closed') && $daysUntilEvaluationDeadline !== null)
                    <div class="info-card" style="margin-top: 24px; border-left: 4px solid {{ $daysUntilEvaluationDeadline <= 3 ? '#ef4444' : ($daysUntilEvaluationDeadline <= 7 ? '#f59e0b' : '#10b981') }};">
                        <div class="info-card-header">
                            <h2 style="display: flex; align-items: center; gap: 8px;">
                                <span>⏰</span>
                                <span>{{ $comp->title }} - Rok za donošenje odluke</span>
                            </h2>
                        </div>
                        <div style="padding: 20px;">
                            @if($isEvaluationDeadlinePassed)
                                <p style="color: #991b1b; font-weight: 600; font-size: 16px; margin: 8px 0;">
                                    ❌ Rok za donošenje odluke je istekao (0 dana)
                                </p>
                            @else
                                <p style="color: {{ $daysUntilEvaluationDeadline <= 3 ? '#991b1b' : ($daysUntilEvaluationDeadline <= 7 ? '#92400e' : '#065f46') }}; font-weight: 600; font-size: 18px; margin: 8px 0;">
                                    Preostalo vremena: <strong>{{ $daysUntilEvaluationDeadline }} {{ $daysUntilEvaluationDeadline == 1 ? 'dan' : ($daysUntilEvaluationDeadline < 5 ? 'dana' : 'dana') }}</strong>
                                </p>
                                <p style="color: #6b7280; font-size: 14px; margin: 4px 0;">
                                    Komisija je dužna donijeti odluku u roku od 30 dana od dana zatvaranja prijava. Rok: {{ $comp->getEvaluationDeadlineDate() ? $comp->getEvaluationDeadlineDate()->format('d.m.Y H:i') : 'N/A' }}
                                </p>
                            @endif
                        </div>
                    </div>
                @endif
            @endforeach
        @endif

    </div>
</div>
@endsection
