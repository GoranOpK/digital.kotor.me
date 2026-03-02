@extends('layouts.app')

@section('content')
<style>
    :root {
        --primary: #0B3D91;
        --primary-dark: #0A347B;
        --secondary: #B8860B;
    }
    .application-detail-page {
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
        margin: 0 0 8px;
    }
    .info-card {
        background: #fff;
        border-radius: 16px;
        padding: 32px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        margin-bottom: 24px;
    }
    .info-card h2 {
        font-size: 20px;
        font-weight: 700;
        color: var(--primary);
        margin: 0 0 20px;
        padding-bottom: 12px;
        border-bottom: 2px solid #e5e7eb;
    }
    .status-badge {
        display: inline-block;
        padding: 6px 16px;
        border-radius: 9999px;
        font-size: 14px;
        font-weight: 600;
    }
    .status-draft {
        background: #fef3c7;
        color: #92400e;
    }
    .status-submitted {
        background: #dbeafe;
        color: #1e40af;
    }
    .status-evaluated {
        background: #d1fae5;
        color: #065f46;
    }
    .status-approved {
        background: #d1fae5;
        color: #065f46;
    }
    .status-rejected {
        background: #fee2e2;
        color: #991b1b;
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
    .documents-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    .document-item {
        padding: 16px;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        margin-bottom: 12px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .document-item.required {
        border-left: 4px solid #ef4444;
    }
    .document-item.uploaded {
        border-left: 4px solid #10b981;
    }
    .document-info {
        flex: 1;
    }
    .document-name {
        font-weight: 600;
        color: #111827;
        margin-bottom: 4px;
    }
    .document-type {
        font-size: 12px;
        color: #6b7280;
    }
    .document-actions {
        display: flex;
        gap: 8px;
    }
    .btn {
        padding: 8px 16px;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 600;
        text-decoration: none;
        cursor: pointer;
        border: none;
        transition: all 0.2s;
    }
    .btn-primary {
        background: var(--primary);
        color: #fff;
    }
    .btn-primary:hover {
        background: var(--primary-dark);
    }
    .btn-danger {
        background: #ef4444;
        color: #fff;
    }
    .btn-danger:hover {
        background: #dc2626;
    }
    .btn-secondary {
        background: #6b7280;
        color: #fff;
    }
    .btn-secondary:hover {
        background: #4b5563;
    }
    .upload-section {
        background: #f3f4f6;
        padding: 20px;
        border-radius: 8px;
        margin-top: 16px;
    }
    .form-group {
        margin-bottom: 16px;
    }
    .form-label {
        display: block;
        font-size: 14px;
        font-weight: 600;
        color: #374151;
        margin-bottom: 8px;
    }
    .form-control {
        width: 100%;
        padding: 10px 14px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        font-size: 14px;
    }
    .form-control:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(11, 61, 145, 0.1);
    }
    .file-input-wrapper {
        position: relative;
        display: inline-block;
        width: 100%;
        min-height: 40px;
    }
    input[type="file"] {
        position: absolute;
        opacity: 0;
        width: 100%;
        height: 40px;
        cursor: pointer;
        z-index: 2;
        top: 0;
        left: 0;
    }
    .file-input-label-custom {
        display: inline-block;
        background: var(--primary);
        color: #fff;
        padding: 8px 16px;
        border-radius: 6px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 600;
        transition: background 0.2s;
    }
    .file-input-label-custom:hover {
        background: var(--primary-dark);
    }
    .file-input-wrapper:hover .file-input-label-custom {
        background: var(--primary-dark);
    }
    .file-name-display {
        margin-top: 8px;
        font-size: 12px;
        color: var(--primary);
        font-weight: 600;
    }
    .error-message {
        color: #ef4444;
        font-size: 12px;
        margin-top: 4px;
    }
    .form-control.error {
        border-color: #ef4444;
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
    .alert-info {
        background: #dbeafe;
        border-color: #3b82f6;
        color: #1e40af;
    }
    .alert-warning {
        background: #fef3c7;
        border-color: #f59e0b;
        color: #92400e;
    }
    .progress-bar {
        width: 100%;
        height: 8px;
        background: #e5e7eb;
        border-radius: 9999px;
        overflow: hidden;
        margin-top: 8px;
    }
    .progress-fill {
        height: 100%;
        background: var(--primary);
        transition: width 0.3s;
    }
    .btn-sm {
        padding: 6px 12px;
        font-size: 12px;
        border-radius: 6px;
        text-decoration: none;
        font-weight: 600;
        display: inline-block;
    }
    .summary-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 24px;
        margin-bottom: 24px;
        align-items: stretch;
    }
    @media (min-width: 992px) {
        .summary-grid {
            grid-template-columns: repeat(3, 1fr);
        }
    }
</style>

<div class="application-detail-page">
    <div class="container mx-auto px-4">
        <div class="page-header">
            <h1>Status prijave</h1>
            <p style="color: rgba(255,255,255,0.9); margin: 0;">{{ $application->business_plan_name }}</p>
        </div>

        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if(session('info'))
            <div class="alert alert-info">
                {{ session('info') }}
            </div>
        @endif

        @if(!$application->is_registered && $application->status !== 'rejected')
            <div class="alert alert-warning">
                <strong>Važno:</strong> Nemate registrovanu djelatnost. U slučaju da vam sredstva budu odobrena, 
                u obavezi ste da svoju djelatnost registrujete u neki od oblika registracije koji predviđa 
                Zakon o privrednim društvima i priložite dokaz (rješenje o registraciji u CRPS i rješenje o 
                registraciji PJ Uprave prihoda i carina), najkasnije do dana potpisivanja ugovora.
            </div>
        @endif

        @php
            $canManage = $canManage ?? false;
            // Upload i izmjene su dozvoljeni samo vlasniku prijave
            $showUpload = $canManage && ($application->status === 'draft' || $application->status === 'submitted');

            // Izračun roka za prijavu – kandidat može obrisati prijavu samo do isteka roka od 15 dana
            // Članovi komisije nikada ne mogu brisati prijave
            $competition = $application->competition;
            $deadline = $competition?->deadline;
            $canDelete = $canManage && $deadline && !$competition->isApplicationDeadlinePassed() && $competition->status !== 'closed';

            // Prvi red: Osnovni podaci + Status prijave + Dodaj dokument
            $gridColumnsStyle = 'repeat(3, 1fr)';
        @endphp

        <div class="summary-grid" style="grid-template-columns: 1fr;">
            <style>
                @media (min-width: 992px) {
                    .summary-grid {
                        grid-template-columns: {{ $gridColumnsStyle }} !important;
                    }
                }
            </style>

            <!-- 1. Osnovni podaci -->
            <div class="info-card">
                <h2>Osnovni podaci</h2>
                <div class="info-grid" style="grid-template-columns: 1fr;">
                    <div class="info-item">
                        <span class="info-label">Tip podnosioca</span>
                        <span class="info-value">
                            @if($application->applicant_type === 'preduzetnica')
                                Preduzetnica
                            @elseif($application->applicant_type === 'fizicko_lice')
                                Fizičko lice (rezident) / nema registrovanu djelatnost
                            @elseif($application->applicant_type === 'doo')
                                DOO
                            @elseif($application->applicant_type === 'ostalo')
                                Ostalo
                            @else
                                {{ $application->applicant_type ?? '—' }}
                            @endif
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Faza biznisa</span>
                        <span class="info-value">
                            {{ $application->business_stage === 'započinjanje' ? 'Započinjanje' : 'Razvoj' }}
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Oblast biznisa</span>
                        <span class="info-value">{{ $application->business_area }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Registrovana djelatnost</span>
                        <span class="info-value">
                            @if($application->is_registered)
                                <span style="color: #10b981;">✓ Da</span>
                            @else
                                <span style="color: #f59e0b;">✗ Ne</span>
                            @endif
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Traženi iznos</span>
                        <span class="info-value">{{ number_format($application->requested_amount, 2, ',', '.') }} €</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Ukupan budžet</span>
                        <span class="info-value">{{ number_format($application->total_budget_needed, 2, ',', '.') }} €</span>
                    </div>
                    @if($application->approved_amount)
                    <div class="info-item">
                        <span class="info-label">Odobreni iznos</span>
                        <span class="info-value">{{ number_format($application->approved_amount, 2, ',', '.') }} €</span>
                    </div>
                    @endif
                </div>
            </div>

            <!-- 2. Status prijave i Biznis plan (u prvom redu grida) -->
            <div class="info-card">
                <div style="border-bottom: 2px solid #e5e7eb; padding-bottom: 12px; margin-bottom: 20px;">
                    <h2 style="margin: 0; border: none; padding: 0;">Status prijave</h2>
                </div>
                <div class="info-grid" style="grid-template-columns: 1fr;">
                    <div class="info-item" style="margin-bottom: 16px;">
                        <span class="info-label">Trenutni Status</span>
                        <span class="info-value">
                            @php
                                $statusLabels = [
                                    'draft' => 'Nacrt',
                                    'submitted' => 'U obradi',
                                    'evaluated' => 'Ocjenjena',
                                    'approved' => 'Odobrena',
                                    'rejected' => 'Odbijena',
                                ];
                                $statusClass = 'status-' . $application->status;
                            @endphp
                            @if($application->status === 'rejected')
                                <a href="{{ route('evaluation.create', $application) }}" class="status-badge {{ $statusClass }}" style="font-size: 12px; padding: 4px 12px; text-decoration: none; cursor: pointer; display: inline-block;">
                                    {{ $statusLabels[$application->status] ?? $application->status }}
                                </a>
                            @else
                                <span class="status-badge {{ $statusClass }}" style="font-size: 12px; padding: 4px 12px;">
                                    {{ $statusLabels[$application->status] ?? $application->status }}
                                </span>
                            @endif
                        </span>
                    </div>
                    @if($application->status === 'rejected' && $application->rejection_reason)
                        <div class="info-item" style="margin-bottom: 16px; padding: 12px; background: #fee2e2; border-radius: 8px; border-left: 4px solid #dc2626;">
                            <span class="info-label" style="color: #991b1b; font-weight: 600; margin-bottom: 8px;">Razlog odbijanja</span>
                            <span class="info-value" style="color: #7f1d1d; font-size: 13px;">
                                {{ rtrim($application->rejection_reason, '.') }}
                            </span>
                        </div>
                    @endif
                    <div class="info-item" style="margin-bottom: 16px;">
                        <span class="info-label">Obrazac 1a/1b</span>
                        <span class="info-value">
                            @php
                                $isObrazacComplete = $application->isObrazacComplete();
                                $obrazacLabel = null;
                                $obrazacClass = 'status-draft';
                                $obrazacUrl = null;
                                if ($isObrazacComplete) {
                                    if ($application->applicant_type === 'preduzetnica') {
                                        $obrazacLabel = 'Obrazac 1a popunjen';
                                        $obrazacClass = 'status-evaluated';
                                    } elseif (in_array($application->applicant_type, ['doo', 'ostalo'])) {
                                        $obrazacLabel = 'Obrazac 1b popunjen';
                                        $obrazacClass = 'status-evaluated';
                                    } elseif ($application->applicant_type === 'fizicko_lice') {
                                        $obrazacLabel = 'Obrazac 1a/1b popunjen';
                                        $obrazacClass = 'status-evaluated';
                                    }
                                    // Klik na badge vodi direktno na popunjen obrazac (formu prijave sa application_id kao query parametar)
                                    $obrazacUrl = route('applications.create', $application->competition_id) . '?application_id=' . $application->id;
                                } else {
                                    // Obrazac nije kompletan - prikaži nacrt prema tipu
                                    if ($application->applicant_type === 'preduzetnica') {
                                        $obrazacLabel = 'Obrazac 1a - Nacrt';
                                    } elseif (in_array($application->applicant_type, ['doo', 'ostalo'])) {
                                        $obrazacLabel = 'Obrazac 1b - Nacrt';
                                    } elseif ($application->applicant_type === 'fizicko_lice') {
                                        $obrazacLabel = 'Obrazac 1a/1b - Nacrt';
                                    }
                                    // Klik na badge vodi na nastavak popunjavanja Obrasca 1a/1b
                                    $obrazacUrl = route('applications.create', $application->competition_id) . '?application_id=' . $application->id;
                                }
                            @endphp
                            @if($obrazacLabel)
                                <a href="{{ $obrazacUrl }}" class="status-badge {{ $obrazacClass }}" style="font-size: 12px; padding: 4px 12px; text-decoration: none; cursor: pointer;">
                                    {{ $obrazacLabel }}
                                </a>
                            @else
                                <span class="status-badge status-draft" style="font-size: 12px; padding: 4px 12px;">Nije popunjen</span>
                            @endif
                        </span>
                    </div>
                    <div class="info-item" style="margin-bottom: 16px;">
                        <span class="info-label">Biznis Plan</span>
                        <span class="info-value">
                            @php
                                // Proveri da li je Obrazac kompletno popunjen koristeći metodu iz modela
                                $isObrazacComplete = $application->isObrazacComplete();
                                $bizPlanLabel = null;
                                $bizPlanClass = 'status-draft';
                                
                                if ($application->businessPlan) {
                                    if ($application->businessPlan->isComplete()) {
                                        $bizPlanLabel = 'Biznis Plan - popunjen';
                                        $bizPlanClass = 'status-evaluated';
                                    } else {
                                        $bizPlanLabel = 'Biznis Plan - nacrt';
                                        $bizPlanClass = 'status-draft';
                                    }
                                } elseif ($isObrazacComplete) {
                                    // Obrazac je kompletan, ali biznis plan ne postoji - može da krene da popunjava
                                    $bizPlanLabel = 'Biznis Plan - nacrt';
                                    $bizPlanClass = 'status-draft';
                                }
                            @endphp
                            @if($bizPlanLabel)
                                <a href="{{ route('applications.business-plan.create', $application) }}" class="status-badge {{ $bizPlanClass }}" style="font-size: 12px; padding: 4px 12px; text-decoration: none; cursor: pointer;">
                                    {{ $bizPlanLabel }}
                                </a>
                                @if($application->businessPlan && $application->businessPlan->isComplete())
                                    {{-- UX napomena: objašnjenje šta znači status "Biznis plan - popunjen" (lako uklonjivo ako ne odgovara) --}}
                                    <div style="font-size: 11px; color: #6b7280; margin-top: 4px; max-width: 460px;">
                                        Status „Biznis plan - popunjen“ znači da su ispunjeni minimalni uslovi za predaju. Sva polja biznis plana utiču na ocjenjivanje – što detaljnije popunite sve sekcije, to je veća šansa za bolju ocjenu.
                                    </div>
                                @endif
                            @else
                                <span class="status-badge status-draft" style="font-size: 12px; padding: 4px 12px;">
                                    Nije dostupan
                                </span>
                                <span style="color: #6b7280; font-size: 11px; margin-left: 8px;">Popunite Obrazac 1a/1b prvo</span>
                            @endif
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Datum podnošenja</span>
                        <span class="info-value" style="font-size: 13px;">
                            {{ $application->submitted_at ? $application->submitted_at->format('d.m.Y H:i') : 'Nije podnesena' }}
                        </span>
                    </div>
                    @if($canDelete)
                        <div class="info-item" style="margin-top: 12px; padding-top: 10px; border-top: 1px solid #e5e7eb;">
                            <span class="info-label">Akcije nad prijavom</span>
                            <div class="info-value" style="display: block; margin-top: 4px;">
                                <p style="font-size: 13px; color: #4b5563; margin-bottom: 8px;">
                                    Prijavu možete obrisati najkasnije do isteka roka za prijavu na konkurs.
                                </p>
                                <form method="POST" action="{{ route('applications.destroy', $application) }}" onsubmit="return confirm('Da li ste sigurni da želite obrisati ovu prijavu? Ova akcija se ne može poništiti.');" style="display: inline-block; margin-right: 12px;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-danger" style="background: #dc2626; color: #fff; padding: 8px 16px; border-radius: 8px; border: none; cursor: pointer; font-size: 13px; font-weight: 600;">
                                        Obriši prijavu
                                    </button>
                                </form>
                                @if($deadline)
                                    <span style="display: inline-block; font-size: 12px; color: #6b7280; margin-top: 4px;">
                                        Rok za prijave: {{ $deadline->format('d.m.Y. H:i') }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endif
                    @if($application->status === 'draft' && $canManage)
                        <div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #e5e7eb;">
                            @if($isReadyToSubmit)
                                <form method="POST" action="{{ route('applications.final-submit', $application) }}" onsubmit="return confirm('Podnijeti prijavu?');">
                                    @csrf
                                    <button type="submit" class="btn btn-primary" style="background: #10b981; width: 100%; font-size: 13px;">
                                        🚀 Podnesi prijavu
                                    </button>
                                </form>
                                @if(!empty($missingDocs))
                                    @php
                                        $documentLabels = [
                                            'licna_karta' => 'Lična karta',
                                            'crps_resenje' => 'CRPS rješenje',
                                            'pib_resenje' => 'PIB rješenje',
                                            'pdv_resenje' => 'PDV rješenje',
                                            'statut' => 'Statut',
                                            'karton_potpisa' => 'Karton potpisa',
                                            'potvrda_neosudjivanost' => 'Neosuđivanost',
                                            'uvjerenje_opstina_porezi' => 'Porezi Opština',
                                            'uvjerenje_opstina_nepokretnost' => 'Nepokretnost Opština',
                                            'potvrda_upc_porezi' => 'Porezi UPC',
                                            'ioppd_obrazac' => 'IOPPD',
                                            'godisnji_racuni' => 'Godišnji računi',
                                            'biznis_plan_usb' => 'USB verzija',
                                            'ostalo' => 'Ostalo',
                                        ];
                                        $missingDocLabels = array_map(function($docType) use ($documentLabels) {
                                            return $documentLabels[$docType] ?? $docType;
                                        }, $missingDocs);
                                    @endphp
                                    <div style="margin-top: 10px; padding: 10px; background: #fef3c7; border-left: 4px solid #f59e0b; border-radius: 4px;">
                                        <p style="font-size: 12px; color: #92400e; margin: 0 0 6px 0; font-weight: 600;">
                                            ⚠️ Napomena: Niste priložili sva neophodna dokumenta
                                        </p>
                                        <p style="font-size: 11px; color: #78350f; margin: 0;">
                                            Nedostaju: {{ implode(', ', $missingDocLabels) }}
                                        </p>
                                        <p style="font-size: 11px; color: #78350f; margin: 6px 0 0 0;">
                                            Možete podnijeti prijavu, ali Predsjednik komisije može odbiti prijavu zbog nepotpune dokumentacije.
                                        </p>
                                    </div>
                                @endif
                            @else
                                <button class="btn btn-secondary" disabled style="width: 100%; font-size: 13px; background: #9ca3af;">
                                    Podnesi prijavu
                                </button>
                                <p style="font-size: 10px; color: #ef4444; margin-top: 6px; text-align: center;">Morate popuniti biznis plan prije podnošenja prijave</p>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            <!-- 3. Dodaj dokument (treća sekcija u prvom redu) -->
            @if($showUpload)
            <div class="info-card">
                <h2>Dodaj dokument</h2>
                <div class="upload-section" style="margin-top: 0; background: #f9fafb; padding: 15px;">
                    <form method="POST" action="{{ route('applications.upload', $application) }}" enctype="multipart/form-data" id="upload-doc-form-{{ $application->id }}" data-app-id="{{ $application->id }}" onsubmit="return prepareUploadFormSubmit(event, {{ $application->id }})">
                        @csrf
                        <div class="form-group" style="margin-bottom: 12px;">
                            <label class="form-label" style="font-size: 13px;">Tip dokumenta</label>
                            <select name="document_type" class="form-control" style="font-size: 13px; padding: 8px;" required>
                                <option value="">Izaberite tip</option>
                                @php
                                    $requiredDocs = $application->getRequiredDocuments();
                                    $uploadedDocs = $application->documents->pluck('document_type')->toArray();
                                    
                                    // Definiši redoslijed dokumenata za dropdown
                                    $order = [];
                                    if ($application->applicant_type === 'preduzetnica' && $application->business_stage === 'započinjanje') {
                                        $order = ['licna_karta', 'crps_resenje', 'pib_resenje', 'pdv_resenje', 'potvrda_neosudjivanost', 'uvjerenje_opstina_porezi', 'uvjerenje_opstina_nepokretnost', 'dokaz_ziro_racun', 'predracuni_nabavka'];
                                    } elseif ($application->applicant_type === 'preduzetnica' && $application->business_stage === 'razvoj') {
                                        $order = ['licna_karta', 'crps_resenje', 'pib_resenje', 'pdv_resenje', 'potvrda_neosudjivanost', 'uvjerenje_opstina_porezi', 'uvjerenje_opstina_nepokretnost', 'potvrda_upc_porezi', 'ioppd_obrazac', 'dokaz_ziro_racun', 'predracuni_nabavka'];
                                    } elseif ($application->applicant_type === 'fizicko_lice' && $application->business_stage === 'započinjanje') {
                                        $order = ['licna_karta', 'crps_resenje', 'pib_resenje', 'pdv_resenje', 'potvrda_neosudjivanost', 'uvjerenje_opstina_porezi', 'uvjerenje_opstina_nepokretnost', 'dokaz_ziro_racun', 'predracuni_nabavka'];
                                    } elseif ($application->applicant_type === 'fizicko_lice' && $application->business_stage === 'razvoj') {
                                        $order = ['licna_karta', 'crps_resenje', 'pib_resenje', 'pdv_resenje', 'potvrda_neosudjivanost', 'uvjerenje_opstina_porezi', 'potvrda_upc_porezi', 'ioppd_obrazac', 'predracuni_nabavka'];
                                    } elseif (($application->applicant_type === 'doo' || $application->applicant_type === 'ostalo') && $application->business_stage === 'započinjanje') {
                                        $order = ['licna_karta', 'crps_resenje', 'pib_resenje', 'pdv_resenje', 'statut', 'karton_potpisa', 'potvrda_neosudjivanost', 'uvjerenje_opstina_porezi', 'uvjerenje_opstina_nepokretnost', 'dokaz_ziro_racun', 'predracuni_nabavka'];
                                    } elseif (($application->applicant_type === 'doo' || $application->applicant_type === 'ostalo') && $application->business_stage === 'razvoj') {
                                        $order = ['licna_karta', 'crps_resenje', 'pib_resenje', 'pdv_resenje', 'statut', 'karton_potpisa', 'godisnji_racuni', 'izvjestaj_registar_kase', 'potvrda_neosudjivanost', 'uvjerenje_opstina_porezi', 'uvjerenje_opstina_nepokretnost', 'potvrda_upc_porezi', 'ioppd_obrazac', 'dokaz_ziro_racun', 'predracuni_nabavka'];
                                    }
                                    
                                    // Sortiraj dokumente prema redoslijedu
                                    $orderedDocsForDropdown = [];
                                    if (!empty($order)) {
                                        foreach ($order as $docType) {
                                            if (in_array($docType, $requiredDocs)) {
                                                $orderedDocsForDropdown[] = $docType;
                                            }
                                        }
                                        // Dodaj ostale dokumente koje nisu u listi
                                        foreach ($requiredDocs as $docType) {
                                            if (!in_array($docType, $orderedDocsForDropdown)) {
                                                $orderedDocsForDropdown[] = $docType;
                                            }
                                        }
                                    } else {
                                        $orderedDocsForDropdown = $requiredDocs;
                                    }
                                    // Ukloni "Ostalo" iz padajućeg menija – prikazuju se samo dokumenta potrebna za prijavu
                                    $orderedDocsForDropdown = array_values(array_filter($orderedDocsForDropdown, fn($d) => $d !== 'ostalo'));
                                    
                                    $documentLabels = [
                                        'licna_karta' => (($application->applicant_type === 'doo' || $application->applicant_type === 'ostalo') && $application->business_stage === 'razvoj') ? 'Ovjerenu kopiju lične karte nosioca biznisa (osnivačica ili jedna od osnivača i izvršna direktorica)' : 'Ovjerena kopija lične karte',
                                        'crps_resenje' => 'Rješenje o upisu u CRPS' . (($application->applicant_type === 'preduzetnica' || $application->applicant_type === 'fizicko_lice') && $application->business_stage === 'započinjanje' ? ' (ukoliko ima registrovanu djelatnost)' : ''),
                                        'pib_resenje' => (($application->applicant_type === 'preduzetnica' || $application->applicant_type === 'fizicko_lice') && $application->business_stage === 'započinjanje' ? 'Rješenje o PIB-u PJ Poreske uprave (ukoliko ima registrovanu djelatnost)' : 'Rješenje o registraciji PJ Uprave prihoda i carina'),
                                        'pdv_resenje' => (($application->applicant_type === 'preduzetnica' || $application->applicant_type === 'fizicko_lice') && $application->business_stage === 'započinjanje' ? 'Rješenje o registraciji za PDV (ukoliko ima registrovanu djelatnost i ako je obveznik PDV-a) ili potvrdu da nije PDV obveznik' : (($application->applicant_type === 'preduzetnica' || $application->applicant_type === 'fizicko_lice') && $application->business_stage === 'razvoj' ? 'Rješenje o registraciji za PDV (ako je obveznik PDV-a) ili potvrdu da nije PDV obveznik (ukoliko nije PDV obveznik)' : 'Rješenje o registraciji za PDV' . ($application->business_stage === 'razvoj' ? ' (ako je obveznik PDV-a)' : ''))),
                                        'statut' => 'Statut društva',
                                        'karton_potpisa' => 'Karton potpisa',
                                        'potvrda_neosudjivanost' => ($application->applicant_type === 'preduzetnica' && $application->business_stage === 'razvoj') ? 'Potvrda da se ne vodi krivični postupak na ime preduzetnice izdatu od Osnovnog suda' : (($application->applicant_type === 'preduzetnica' || $application->applicant_type === 'fizicko_lice') ? 'Potvrda da se ne vodi krivični postupak na ime podnositeljke prijave odnosno preduzetnice izdatu od Osnovnog suda' : ((($application->applicant_type === 'doo' || $application->applicant_type === 'ostalo') && $application->business_stage === 'započinjanje') ? 'Potvrda da se ne vodi krivični postupak na ime podnositeljke prijave odnosno na ime nosioca biznisa (osnivačice ili jedne od osnivača i izvršne direktorice) izdatu od strane Osnovnog suda' : (($application->applicant_type === 'doo' || $application->applicant_type === 'ostalo') ? 'Potvrda da se ne vodi krivični postupak na ime društva i na ime nosioca biznisa (osnivačice ili jedne od osnivača i izvršne direktorice) izdatu od strane Osnovnog suda' : 'Potvrda o neosuđivanosti')))),
                                        'uvjerenje_opstina_porezi' => ($application->applicant_type === 'preduzetnica' && $application->business_stage === 'razvoj') ? 'Uvjerenje od organa lokalne uprave, ne starije od 30 dana, o urednom izmirivanju poreza na ime preduzetnice po osnovu prireza porezu, članskog doprinosa, lokalnih komunalnih taksi i naknada' : ((($application->applicant_type === 'doo' || $application->applicant_type === 'ostalo') && $application->business_stage === 'započinjanje') ? 'Uvjerenje od organa lokalne uprave, ne starije od 30 dana, o urednom izmirivanju poreza na ime podnositeljke prijave odnosno nosioca biznisa (osnivačice ili jedne od osnivača i izvršne direktorice) po osnovu prireza porezu, članskog doprinosa, lokalnih komunalnih taksi i naknada' : ((($application->applicant_type === 'doo' || $application->applicant_type === 'ostalo') && $application->business_stage === 'razvoj') ? 'Uvjerenje od organa lokalne uprave, ne starije od 30 dana, o urednom izmirivanju poreza na ime nosioca biznisa (osnivačice ili jedne od osnivača i izvršne direktorice) i na ime društva po osnovu prireza porezu, članskog doprinosa, lokalnih komunalnih taksi i naknada' : ((($application->applicant_type === 'preduzetnica' || $application->applicant_type === 'fizicko_lice') && ($application->business_stage === 'započinjanje' || $application->business_stage === 'razvoj')) ? 'Uvjerenje od organa lokalne uprave, ne starije od 30 dana, o urednom izmirivanju poreza na ime podnositeljke prijave odnosno preduzetnice po osnovu prireza porezu, članskog doprinosa, lokalnih komunalnih taksi i naknada' : 'Uvjerenje Opštine o urednom izmirivanju poreza'))),
                                        'uvjerenje_opstina_nepokretnost' => ($application->applicant_type === 'preduzetnica' && $application->business_stage === 'razvoj') ? 'Uvjerenje od organa lokalne uprave, ne starije od 30 dana, o urednom izmirivanju poreza na nepokretnost na ime preduzetnice' : ((($application->applicant_type === 'doo' || $application->applicant_type === 'ostalo') && $application->business_stage === 'započinjanje') ? 'Uvjerenje od organa lokalne uprave, ne starije od 30 dana, o urednom izmirivanju poreza na nepokretnost na ime podnositeljke prijave odnosno nosioca biznisa (osnivačice ili jedne od osnivača i izvršne direktorice)' : ((($application->applicant_type === 'doo' || $application->applicant_type === 'ostalo') && $application->business_stage === 'razvoj') ? 'Uvjerenje od organa lokalne uprave, ne starije od 30 dana, o urednom izmirivanju poreza na nepokretnost na ime nosioca biznisa (osnivačice ili jedne od osnivača i izvršne direktorice) i na ime društva' : ((($application->applicant_type === 'preduzetnica' || $application->applicant_type === 'fizicko_lice') && $application->business_stage !== 'razvoj') ? 'Uvjerenje od organa lokalne uprave, ne starije od 30 dana, o urednom izmirivanju poreza na nepokretnost na ime podnositeljke prijave odnosno preduzetnice' : 'Uvjerenje Opštine o nepostojanju nepokretnosti')))),
                                        'potvrda_upc_porezi' => ($application->applicant_type === 'preduzetnica' && $application->business_stage === 'razvoj') ? 'Potvrda Poreske uprave o urednom izmirivanju poreza i doprinosa ne stariju od 30 dana, na ime preduzetnice' : (($application->applicant_type === 'preduzetnica' || $application->applicant_type === 'fizicko_lice') && $application->business_stage === 'razvoj' ? 'Potvrda Poreske uprave o urednom izmirivanju poreza i doprinosa ne stariju od 30 dana na ime preduzetnika' : 'Potvrda Uprave za javne prihode o urednom izmirivanju poreza'),
                                        'ioppd_obrazac' => ($application->applicant_type === 'preduzetnica' && $application->business_stage === 'razvoj') ? 'Odgovarajući obrazac ovjeren od strane Poreske uprave za poslijednji mjesec uplate poreza i doprinosa za zaposlene, kao dokaz o broju zaposlenih (IOPPD Obrazac) ili potvrdu ovjerenu od strane Poreske uprave da preduzetnica nema zaposlenih' : ((($application->applicant_type === 'doo' || $application->applicant_type === 'ostalo') && $application->business_stage === 'razvoj') ? 'Odgovarajući obrazac za poslijednji mjesec uplate poreza i doprinosa za zaposlene ovjeren od strane Poreske uprave, kao dokaz o broju zaposlenih (IOPPD Obrazac)' : (($application->applicant_type === 'preduzetnica' || $application->applicant_type === 'fizicko_lice') && $application->business_stage === 'razvoj' ? 'Odgovarajući obrazac za posljednji mjesec uplate poreza i doprinosa za zaposlene, kao dokaz o broju zaposlenih (IOPPD Obrazac) ili potvrdu ovjerenu od Poreske uprave' : 'Obrazac IOPPD')),
                                        'godisnji_racuni' => (($application->applicant_type === 'doo' || $application->applicant_type === 'ostalo') && $application->business_stage === 'razvoj') ? 'Komplet obrazaca za godišnje račune (Bilans stanja, Bilans uspjeha, Analitika kupaca i Analitika dobavljača) za prethodnu godinu. Napomena: U slučaju da preduzetnica/društvo ne vodi analitiku kupaca tj. posluje isključivo sa fizičkim licima i naplata se vrši odmah putem registar kase, preduzetnica/društvo ima obavezu dostaviti periodični izvještaj sa registar kase' : 'Godišnji računi',
                                        'biznis_plan_usb' => 'Jedna štampana i jedna elektronska verzija biznis plana na USB-u',
                                        'dokaz_ziro_racun' => ($application->applicant_type === 'preduzetnica' && $application->business_stage === 'razvoj') ? 'Dokaz o broju poslovnog žiro računa preduzetnice' : (($application->applicant_type === 'preduzetnica' && $application->business_stage === 'započinjanje') ? 'Dokaz o broju poslovnog žiro računa preduzetnice (ukoliko ima registrovanu djelatnost)' : ((($application->applicant_type === 'doo' || $application->applicant_type === 'ostalo') && $application->business_stage === 'započinjanje') ? 'Dokaz o broju poslovnog žiro računa društva (ukoliko ima registrovanu djelatnost)' : ((($application->applicant_type === 'doo' || $application->applicant_type === 'ostalo') && $application->business_stage === 'razvoj') ? 'Dokaz o broju poslovnog žiro računa društva' : 'Dokaz o broju poslovnog žiro računa')))),
                                        'predracuni_nabavka' => 'Predračuni za planiranu nabavku',
                                        'ostalo' => 'Ostalo',
                                    ];
                                @endphp
                                @foreach($orderedDocsForDropdown as $docType)
                                    @if(!in_array($docType, $uploadedDocs))
                                        <option value="{{ $docType }}">{{ $documentLabels[$docType] ?? $docType }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom: 12px;">
                            <label class="form-label" style="font-size: 13px;">Fajl</label>
                            <div class="file-input-wrapper" style="min-height: 32px;">
                                <input type="file" name="files[]" id="file-input-{{ $application->id }}" multiple accept="image/jpeg,image/png,image/jpg,application/pdf" onchange="updateFileDisplayApp(this)" style="height: 32px;">
                                <label for="file-input-{{ $application->id }}" class="file-input-label-custom" id="file-label-{{ $application->id }}" style="padding: 6px 12px; font-size: 12px;">Izaberi fajlove (možete izabrati više)</label>
                                <div id="file-names-app-{{ $application->id }}" class="file-names-app" style="display: none; margin-top: 8px;"></div>
                            </div>
                            <div style="background: #eff6ff; border-left: 4px solid #3b82f6; padding: 12px; margin-top: 8px; border-radius: 4px;">
                                <strong style="color: #1e40af; display: block; margin-bottom: 4px;">ℹ️ Važno:</strong>
                                <span style="color: #1e3a8a; font-size: 12px;">
                                    Ako izaberete više fajlova, oni će biti spojeni u <strong>jedan PDF dokument</strong> tim redosledom kako su navedeni.
                                    Možete promeniti redosled fajlova pomoću dugmadi "Gore" i "Dole" pre upload-a.
                                </span>
                            </div>
                        </div>
                        <div class="form-group" style="margin-bottom: 15px;">
                            <label class="form-label" style="font-weight: 400; font-size: 12px;">Ili iz biblioteke</label>
                            <select name="user_document_id" class="form-control" style="font-size: 12px; padding: 6px;">
                                <option value="">Izaberi...</option>
                                @foreach(auth()->user()->documents()->where('status', 'active')->latest()->get() as $userDoc)
                                    <option value="{{ $userDoc->id }}">{{ Str::limit($userDoc->name, 20) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom: 15px; display: flex; align-items: center; gap: 8px;">
                            <input type="checkbox" name="save_to_library" id="save-to-library-{{ $application->id }}" value="1" style="width: 18px; height: 18px; cursor: pointer;">
                            <label for="save-to-library-{{ $application->id }}" class="form-label" style="font-weight: 400; font-size: 12px; margin: 0; cursor: pointer;">Sačuvaj dokument i u moju biblioteku</label>
                        </div>
                        <button type="submit" class="btn btn-primary" style="width: 100%; font-size: 13px; padding: 10px;">Priloži dokument</button>
                    </form>
                </div>
            </div>
            @endif
        </div>

        @if($errors->any())
            <div class="alert" style="background: #fee2e2; border-color: #ef4444; color: #991b1b; margin-bottom: 24px;">
                <strong>Greška:</strong>
                <ul style="margin-top: 8px; padding-left: 20px;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Dokumenti -->
        <div class="info-card">
            <h2>Priložena dokumentacija</h2>
            
            @php
                $requiredDocs = $application->getRequiredDocuments();
                // Za broj i prikaz računamo samo dokumenta potrebna za prijavu (isključujemo tip 'ostalo')
                $allUploadedTypes = $application->documents->pluck('document_type')->toArray();
                $uploadedDocs = array_values(array_filter($allUploadedTypes, fn($t) => $t !== 'ostalo'));
                
                // Definiši redoslijed dokumenata na osnovu tipa prijave i faze biznisa
                $orderedDocs = [];
                if ($application->applicant_type === 'preduzetnica' && $application->business_stage === 'započinjanje') {
                    // Redoslijed za Preduzetnica koja započinje biznis
                    $order = ['licna_karta', 'crps_resenje', 'pib_resenje', 'pdv_resenje', 'potvrda_neosudjivanost', 'uvjerenje_opstina_porezi', 'uvjerenje_opstina_nepokretnost', 'dokaz_ziro_racun', 'predracuni_nabavka'];
                    // Dodaj ostale dokumente koje možda postoje (npr. izvještaji)
                    foreach ($requiredDocs as $docType) {
                        if (!in_array($docType, $order)) {
                            $order[] = $docType;
                        }
                    }
                    // Sortiraj prema definisanom redoslijedu
                    $orderedDocs = array_intersect($order, $requiredDocs);
                    $orderedDocs = array_merge($orderedDocs, array_diff($requiredDocs, $orderedDocs));
                } elseif ($application->applicant_type === 'preduzetnica' && $application->business_stage === 'razvoj') {
                    // Redoslijed za Preduzetnica koja planira razvoj poslovanja (prema novoj Odluci)
                    $order = ['licna_karta', 'crps_resenje', 'pib_resenje', 'pdv_resenje', 'potvrda_neosudjivanost', 'uvjerenje_opstina_porezi', 'uvjerenje_opstina_nepokretnost', 'potvrda_upc_porezi', 'ioppd_obrazac', 'dokaz_ziro_racun', 'predracuni_nabavka'];
                    foreach ($requiredDocs as $docType) {
                        if (!in_array($docType, $order)) {
                            $order[] = $docType;
                        }
                    }
                    $orderedDocs = array_intersect($order, $requiredDocs);
                    $orderedDocs = array_merge($orderedDocs, array_diff($requiredDocs, $orderedDocs));
                } elseif ($application->applicant_type === 'fizicko_lice' && $application->business_stage === 'započinjanje') {
                    // Redoslijed za Fizičko lice koje započinje biznis (ista lista kao preduzetnica)
                    $order = ['licna_karta', 'crps_resenje', 'pib_resenje', 'pdv_resenje', 'potvrda_neosudjivanost', 'uvjerenje_opstina_porezi', 'uvjerenje_opstina_nepokretnost', 'dokaz_ziro_racun', 'predracuni_nabavka'];
                    foreach ($requiredDocs as $docType) {
                        if (!in_array($docType, $order)) {
                            $order[] = $docType;
                        }
                    }
                    $orderedDocs = array_intersect($order, $requiredDocs);
                    $orderedDocs = array_merge($orderedDocs, array_diff($requiredDocs, $orderedDocs));
                } elseif ($application->applicant_type === 'fizicko_lice' && $application->business_stage === 'razvoj') {
                    // Redoslijed za Fizičko lice koje planira razvoj poslovanja (prema novoj Odluci)
                    $order = ['licna_karta', 'crps_resenje', 'pib_resenje', 'pdv_resenje', 'potvrda_neosudjivanost', 'uvjerenje_opstina_porezi', 'potvrda_upc_porezi', 'ioppd_obrazac', 'predracuni_nabavka'];
                    foreach ($requiredDocs as $docType) {
                        if (!in_array($docType, $order)) {
                            $order[] = $docType;
                        }
                    }
                    $orderedDocs = array_intersect($order, $requiredDocs);
                    $orderedDocs = array_merge($orderedDocs, array_diff($requiredDocs, $orderedDocs));
                } elseif (($application->applicant_type === 'doo' || $application->applicant_type === 'ostalo') && $application->business_stage === 'započinjanje') {
                    // Redoslijed za DOO/Ostalo koja započinju biznis
                    $order = ['licna_karta', 'crps_resenje', 'pib_resenje', 'pdv_resenje', 'statut', 'karton_potpisa', 'potvrda_neosudjivanost', 'uvjerenje_opstina_porezi', 'uvjerenje_opstina_nepokretnost', 'dokaz_ziro_racun', 'predracuni_nabavka'];
                    foreach ($requiredDocs as $docType) {
                        if (!in_array($docType, $order)) {
                            $order[] = $docType;
                        }
                    }
                    $orderedDocs = array_intersect($order, $requiredDocs);
                    $orderedDocs = array_merge($orderedDocs, array_diff($requiredDocs, $orderedDocs));
                } elseif (($application->applicant_type === 'doo' || $application->applicant_type === 'ostalo') && $application->business_stage === 'razvoj') {
                    // Redoslijed za DOO/Ostalo koja planiraju razvoj poslovanja
                    $order = ['licna_karta', 'crps_resenje', 'pib_resenje', 'pdv_resenje', 'statut', 'karton_potpisa', 'godisnji_racuni', 'izvjestaj_registar_kase', 'potvrda_neosudjivanost', 'uvjerenje_opstina_porezi', 'uvjerenje_opstina_nepokretnost', 'potvrda_upc_porezi', 'ioppd_obrazac', 'dokaz_ziro_racun', 'predracuni_nabavka'];
                    foreach ($requiredDocs as $docType) {
                        if (!in_array($docType, $order)) {
                            $order[] = $docType;
                        }
                    }
                    $orderedDocs = array_intersect($order, $requiredDocs);
                    $orderedDocs = array_merge($orderedDocs, array_diff($requiredDocs, $orderedDocs));
                } else {
                    // Za ostale tipove, koristi originalni redoslijed
                    $orderedDocs = $requiredDocs;
                }
                
                $isPreduzetnica = in_array($application->applicant_type, ['preduzetnica', 'fizicko_lice']);
                    $isDooOstalo = in_array($application->applicant_type, ['doo', 'ostalo']);
                    $isZapocinjanje = $application->business_stage === 'započinjanje';
                    $isRazvoj = $application->business_stage === 'razvoj';

                    $documentLabels = [];
                    $documentLabels['licna_karta'] = ($isDooOstalo && $isRazvoj) ? 'Ovjerenu kopiju lične karte nosioca biznisa (osnivačica ili jedna od osnivača i izvršna direktorica)' : (($isDooOstalo && $isZapocinjanje) ? 'Ovjerenu kopiju lične karte' : 'Ovjerena kopija lične karte');
                    $documentLabels['crps_resenje'] = 'Rješenje o upisu u CRPS' . (($isPreduzetnica && $isZapocinjanje) ? ' (ukoliko ima registrovanu djelatnost)' : (($isDooOstalo && $isZapocinjanje) ? ' (ukoliko ima registrovanu djelatnost)' : ''));
                    if ($isPreduzetnica && $isZapocinjanje) {
                        $documentLabels['pib_resenje'] = 'Rješenje o PIB-u PJ Poreske uprave (ukoliko ima registrovanu djelatnost)';
                    } elseif ($isDooOstalo && ($isZapocinjanje || $isRazvoj)) {
                        $documentLabels['pib_resenje'] = 'Rješenje o registraciji PJ Poreske uprave' . ($isZapocinjanje ? ' (ukoliko ima registrovanu djelatnost)' : '');
                    } else {
                        $documentLabels['pib_resenje'] = 'Rješenje o registraciji PJ Uprave prihoda i carina';
                    }
                    if ($isPreduzetnica && $isZapocinjanje) {
                        $documentLabels['pdv_resenje'] = 'Rješenje o registraciji za PDV (ukoliko ima registrovanu djelatnost i ako je obveznik PDV-a) ili potvrdu da nije PDV obveznik (ukoliko nije PDV obveznik)';
                    } elseif ($isPreduzetnica && $isRazvoj) {
                        $documentLabels['pdv_resenje'] = 'Rješenje o registraciji za PDV (ako je obveznik PDV-a) ili potvrdu da nije PDV obveznik (ukoliko nije PDV obveznik)';
                    } elseif ($isDooOstalo && $isZapocinjanje) {
                        $documentLabels['pdv_resenje'] = 'Rješenje o registraciji za PDV (ukoliko ima registrovanu djelatnost i ako je obveznik PDV-a) ili potvrdu da nije PDV obveznik (ukoliko nije PDV obveznik)';
                    } elseif ($isDooOstalo && $isRazvoj) {
                        $documentLabels['pdv_resenje'] = 'Rješenje o registraciji za PDV (ako je obveznik PDV-a) ili potvrdu da nije PDV obveznik (ukoliko nije PDV obveznik)';
                    } else {
                        $documentLabels['pdv_resenje'] = 'Rješenje o registraciji za PDV' . ($isRazvoj ? ' (ako je obveznik PDV-a)' : '');
                    }
                    $documentLabels['statut'] = ($isDooOstalo && $isZapocinjanje) ? 'Važeći Statut društva (ukoliko ima registrovanu djelatnost)' : 'Važeći Statut društva';
                    $documentLabels['karton_potpisa'] = ($isDooOstalo && $isZapocinjanje) ? 'Važeći karton deponovanih potpisa (ukoliko ima registrovanu djelatnost)' : 'Važeći karton deponovanih potpisa';
                    $documentLabels['potvrda_neosudjivanost'] = ($application->applicant_type === 'preduzetnica' && $isRazvoj) ? 'Potvrda da se ne vodi krivični postupak na ime preduzetnice izdatu od Osnovnog suda' : ($isPreduzetnica ? 'Potvrda da se ne vodi krivični postupak na ime podnositeljke prijave odnosno preduzetnice izdatu od Osnovnog suda' : (($isDooOstalo && $isZapocinjanje) ? 'Potvrda da se ne vodi krivični postupak na ime podnositeljke prijave odnosno na ime nosioca biznisa (osnivačice ili jedne od osnivača i izvršne direktorice) izdatu od strane Osnovnog suda' : (($isDooOstalo && $isRazvoj) ? 'Potvrda da se ne vodi krivični postupak na ime društva i na ime nosioca biznisa (osnivačice ili jedne od osnivača i izvršne direktorice) izdatu od strane Osnovnog suda' : 'Potvrda o neosuđivanosti')));
                    if ($application->applicant_type === 'preduzetnica' && $isRazvoj) {
                        $documentLabels['uvjerenje_opstina_porezi'] = 'Uvjerenje od organa lokalne uprave, ne starije od 30 dana, o urednom izmirivanju poreza na ime preduzetnice po osnovu prireza porezu, članskog doprinosa, lokalnih komunalnih taksi i naknada';
                    } elseif ($isPreduzetnica && ($isZapocinjanje || $isRazvoj)) {
                        $documentLabels['uvjerenje_opstina_porezi'] = 'Uvjerenje od organa lokalne uprave, ne starije od 30 dana, o urednom izmirivanju poreza na ime podnositeljke prijave odnosno preduzetnice po osnovu prireza porezu, članskog doprinosa, lokalnih komunalnih taksi i naknada';
                    } elseif ($isDooOstalo && $isRazvoj) {
                        $documentLabels['uvjerenje_opstina_porezi'] = 'Uvjerenje od organa lokalne uprave, ne starije od 30 dana, o urednom izmirivanju poreza na ime nosioca biznisa (osnivačice ili jedne od osnivača i izvršne direktorice) i na ime društva po osnovu prireza porezu, članskog doprinosa, lokalnih komunalnih taksi i naknada';
                    } elseif ($isDooOstalo && $isZapocinjanje) {
                        $documentLabels['uvjerenje_opstina_porezi'] = 'Uvjerenje od organa lokalne uprave, ne starije od 30 dana, o urednom izmirivanju poreza na ime podnositeljke prijave odnosno nosioca biznisa (osnivačice ili jedne od osnivača i izvršne direktorice) po osnovu prireza porezu, članskog doprinosa, lokalnih komunalnih taksi i naknada';
                    } else {
                        $documentLabels['uvjerenje_opstina_porezi'] = 'Uvjerenje od organa lokalne uprave o urednom izmirivanju poreza na ime preduzetnice po osnovu prireza porezu, članskog doprinosa, lokalnih komunalnih taksi i naknada';
                    }
                    if ($application->applicant_type === 'preduzetnica' && $isRazvoj) {
                        $documentLabels['uvjerenje_opstina_nepokretnost'] = 'Uvjerenje od organa lokalne uprave, ne starije od 30 dana, o urednom izmirivanju poreza na nepokretnost na ime preduzetnice';
                    } elseif ($isPreduzetnica) {
                        $documentLabels['uvjerenje_opstina_nepokretnost'] = 'Uvjerenje od organa lokalne uprave, ne starije od 30 dana, o urednom izmirivanju poreza na nepokretnost na ime podnositeljke prijave odnosno preduzetnice';
                    } elseif ($isDooOstalo && $isRazvoj) {
                        $documentLabels['uvjerenje_opstina_nepokretnost'] = 'Uvjerenje od organa lokalne uprave, ne starije od 30 dana, o urednom izmirivanju poreza na nepokretnost na ime nosioca biznisa (osnivačice ili jedne od osnivača i izvršne direktorice) i na ime društva';
                    } elseif ($isDooOstalo && $isZapocinjanje) {
                        $documentLabels['uvjerenje_opstina_nepokretnost'] = 'Uvjerenje od organa lokalne uprave, ne starije od 30 dana, o urednom izmirivanju poreza na nepokretnost na ime podnositeljke prijave odnosno nosioca biznisa (osnivačice ili jedne od osnivača i izvršne direktorice)';
                    } else {
                        $documentLabels['uvjerenje_opstina_nepokretnost'] = 'Uvjerenje od organa lokalne uprave o urednom izmirivanju poreza na nepokretnost na ime preduzetnice';
                    }
                    $documentLabels['potvrda_upc_porezi'] = ($application->applicant_type === 'preduzetnica' && $isRazvoj) ? 'Potvrda Poreske uprave o urednom izmirivanju poreza i doprinosa ne stariju od 30 dana, na ime preduzetnice' : (($isPreduzetnica && $isRazvoj) ? 'Potvrda Poreske uprave o urednom izmirivanju poreza i doprinosa ne stariju od 30 dana na ime preduzetnika' : (($isDooOstalo && $isRazvoj) ? 'Potvrdu Poreske uprave o urednom izmirivanju poreza i doprinosa ne stariju od 30 dana, na ime nosioca biznisa (osnivačice ili jedne od osnivača i izvršne direktorice) i na ime društva' : 'Potvrda Uprave za javne prihode o urednom izmirivanju poreza'));
                    $documentLabels['ioppd_obrazac'] = ($application->applicant_type === 'preduzetnica' && $isRazvoj) ? 'Odgovarajući obrazac ovjeren od strane Poreske uprave za poslijednji mjesec uplate poreza i doprinosa za zaposlene, kao dokaz o broju zaposlenih (IOPPD Obrazac) ili potvrdu ovjerenu od strane Poreske uprave da preduzetnica nema zaposlenih' : (($isPreduzetnica && $isRazvoj) ? 'Odgovarajući obrazac za posljednji mjesec uplate poreza i doprinosa za zaposlene, kao dokaz o broju zaposlenih (IOPPD Obrazac) ili potvrdu ovjerenu od Poreske uprave' : (($isDooOstalo && $isRazvoj) ? 'Odgovarajući obrazac za poslijednji mjesec uplate poreza i doprinosa za zaposlene ovjeren od strane Poreske uprave, kao dokaz o broju zaposlenih (IOPPD Obrazac)' : 'Obrazac IOPPD'));
                    $documentLabels['godisnji_racuni'] = $isDooOstalo ? 'Komplet obrazaca za godišnje račune (Bilans stanja, Bilans uspjeha, Analitika kupaca i Analitika dobavljača) za prethodnu godinu. Napomena: U slučaju da preduzetnica/društvo ne vodi analitiku kupaca tj. posluje isključivo sa fizičkim licima i naplata se vrši odmah putem registar kase, preduzetnica/društvo ima obavezu dostaviti periodični izvještaj sa registar kase' : 'Godišnji računi';
                    $documentLabels['izvjestaj_registar_kase'] = 'Izvještaj sa registra kase';
                    $documentLabels['biznis_plan_usb'] = 'Jedna štampana i jedna elektronska verzija biznis plana na USB-u';
                    $documentLabels['izvjestaj_realizacija'] = 'Izvještaj o realizaciji';
                    $documentLabels['finansijski_izvjestaj'] = 'Finansijski izvještaj';
                    $documentLabels['dokaz_ziro_racun'] = ($application->applicant_type === 'preduzetnica' && $isRazvoj) ? 'Dokaz o broju poslovnog žiro računa preduzetnice' : (($application->applicant_type === 'preduzetnica' && $isZapocinjanje) ? 'Dokaz o broju poslovnog žiro računa preduzetnice (ukoliko ima registrovanu djelatnost)' : (($isDooOstalo && $isZapocinjanje) ? 'Dokaz o broju poslovnog žiro računa društva (ukoliko ima registrovanu djelatnost)' : (($isDooOstalo && $isRazvoj) ? 'Dokaz o broju poslovnog žiro računa društva' : 'Dokaz o broju poslovnog žiro računa')));
                    $documentLabels['predracuni_nabavka'] = $isDooOstalo ? 'Predračune za planiranu nabavku' : 'Predračuni za planiranu nabavku';
                    $documentLabels['ostalo'] = 'Ostalo';
                    // Broj priloženih obaveznih dokumenata (samo tipovi iz $orderedDocs)
                    $uploadedRequiredCount = count(array_intersect($orderedDocs, $allUploadedTypes));
            @endphp

            <!-- Progress bar -->
            <div style="margin-bottom: 20px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                    <span style="font-size: 14px; color: #374151; font-weight: 600;">
                        Dokumenti: {{ $uploadedRequiredCount }} / {{ count($orderedDocs) }}
                    </span>
                    <span style="font-size: 14px; color: #6b7280;">
                        {{ round(($uploadedRequiredCount / max(count($orderedDocs), 1)) * 100) }}%
                    </span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: {{ ($uploadedRequiredCount / max(count($orderedDocs), 1)) * 100 }}%"></div>
                </div>
            </div>

            @php
                // Provjeri da li je korisnik član komisije
                $isCommissionMember = $isCommissionMemberForThisCompetition ?? false;
                
                // Fallback provjera - ako varijabla nije postavljena, provjeri direktno
                if (!$isCommissionMember && auth()->check()) {
                    $user = auth()->user();
                    $roleName = $user->role ? $user->role->name : null;
                    if ($roleName === 'komisija') {
                        $competition = $application->competition;
                        if ($competition && $competition->commission_id) {
                            $commissionMember = \App\Models\CommissionMember::where('user_id', $user->id)
                                ->where('status', 'active')
                                ->first();
                            if ($commissionMember && $commissionMember->commission_id === $competition->commission_id) {
                                $isCommissionMember = true;
                            }
                        }
                    }
                }
            @endphp
            
            @if($isCommissionMember)
                <!-- Tabela svih potrebnih dokumenata za članove komisije -->
                <table style="width: 100%; border-collapse: collapse; font-size: 13px; background: #fff; margin-top: 20px;">
                    <thead>
                        <tr style="background: #f9fafb; border-bottom: 2px solid #e5e7eb;">
                            <th style="padding: 12px; text-align: left; font-weight: 600; color: #374151;">Dokument</th>
                            <th style="padding: 12px; text-align: center; font-weight: 600; color: #374151; width: 120px;">Status</th>
                            <th style="padding: 12px; text-align: center; font-weight: 600; color: #374151; width: 100px;">Akcija</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($orderedDocs as $docType)
                            @php
                                $isUploaded = in_array($docType, $uploadedDocs);
                                $document = $isUploaded ? $application->documents->firstWhere('document_type', $docType) : null;
                            @endphp
                            <tr style="border-bottom: 1px solid #e5e7eb;">
                                <td style="padding: 12px; color: #111827;">
                                    {{ $documentLabels[$docType] ?? $docType }}
                                </td>
                                <td style="padding: 12px; text-align: center;">
                                    @if($isUploaded)
                                        <span style="display: inline-block; padding: 4px 12px; background: #d1fae5; color: #065f46; border-radius: 9999px; font-size: 12px; font-weight: 600;">
                                            ✓ Priloženo
                                        </span>
                                    @else
                                        <span style="display: inline-block; padding: 4px 12px; background: #fee2e2; color: #991b1b; border-radius: 9999px; font-size: 12px; font-weight: 600;">
                                            ✗ Nedostaje
                                        </span>
                                    @endif
                                </td>
                                <td style="padding: 12px; text-align: center;">
                                    @if($isUploaded && $document)
                                        <a href="{{ route('applications.document.view', ['application' => $application, 'document' => $document]) }}" 
                                           target="_blank" 
                                           style="color: var(--primary); text-decoration: none; font-size: 12px; font-weight: 600;">
                                            Pogledaj
                                        </a>
                                    @else
                                        <span style="color: #9ca3af; font-size: 12px;">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <!-- Lista obaveznih dokumenata za vlasnika prijave -->
                <ul class="documents-list">
                    @foreach($orderedDocs as $docType)
                        @php
                            $uploaded = in_array($docType, $uploadedDocs);
                            $doc = $application->documents->where('document_type', $docType)->first();
                        @endphp
                        <li class="document-item {{ $uploaded ? 'uploaded' : 'required' }}">
                            <div class="document-info">
                                <div class="document-name">
                                    {{ $documentLabels[$docType] ?? $docType }}
                                    @if(!$uploaded)
                                        <span style="color: #ef4444; font-size: 12px; margin-left: 8px;">(Obavezno)</span>
                                    @endif
                                </div>
                                @if($uploaded && $doc)
                                    <div class="document-type">
                                        Upload-ovano: {{ $doc->created_at->format('d.m.Y H:i') }}
                                    </div>
                                @endif
                            </div>
                            <div class="document-actions">
                                @if($uploaded && $doc)
                                    <a href="{{ route('applications.document.view', ['application' => $application, 'document' => $doc]) }}" 
                                       class="btn btn-secondary" target="_blank" style="margin-right: 4px;">
                                        Pogledaj
                                    </a>
                                    @if(auth()->id() === $application->user_id)
                                        <a href="{{ route('applications.document.download', ['application' => $application, 'document' => $doc]) }}" 
                                           class="btn btn-secondary" style="margin-right: 4px;">
                                            Preuzmi
                                        </a>
                                        <form action="{{ route('applications.document.destroy', ['application' => $application, 'document' => $doc]) }}" 
                                              method="POST" 
                                              style="display: inline;" 
                                              onsubmit="return confirm('Da li ste sigurni da želite da uklonite ovaj dokument iz prijave?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger" style="padding: 8px 12px;">
                                                Ukloni
                                            </button>
                                        </form>
                                    @endif
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <!-- Ugovor -->
        @if($application->status === 'approved')
        <div class="info-card">
            <h2>Ugovor</h2>
            @if($application->contract)
                <p><strong>Status:</strong> 
                    @if($application->contract->status === 'draft') Nacrt
                    @elseif($application->contract->status === 'signed') Potpisan
                    @elseif($application->contract->status === 'approved') Potvrđen
                    @endif
                </p>
                <a href="{{ route('contracts.show', $application->contract) }}" class="btn btn-primary" style="margin-top: 12px;">
                    Pregled ugovora
                </a>
            @else
                <p style="color: #6b7280;">Ugovor još nije kreiran.</p>
                @if(auth()->user()->role && (auth()->user()->role->name === 'admin' || auth()->user()->role->name === 'superadmin'))
                    <a href="{{ route('contracts.generate', $application) }}" class="btn btn-primary" style="margin-top: 12px;">
                        Generiši ugovor
                    </a>
                @endif
            @endif
        </div>
        @endif

        <!-- Izvještaji -->
        @if($application->status === 'approved' && $application->contract && $application->contract->status === 'approved')
        <div class="info-card">
            <h2>Izvještaji o realizaciji</h2>
            @php
                $realizationReport = $application->reports()->where('type', 'realization')->first();
                $financialReport = $application->reports()->where('type', 'financial')->first();
            @endphp
            
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; margin-top: 16px;">
                <div style="padding: 16px; background: #f9fafb; border-radius: 8px;">
                    <h3 style="font-size: 16px; margin-bottom: 8px;">Obrazac 4 - Izvještaj o realizaciji</h3>
                    @if($realizationReport)
                        <p style="font-size: 12px; color: #6b7280; margin-bottom: 8px;">
                            Status: {{ $realizationReport->status === 'submitted' ? 'Podnesen' : ($realizationReport->status === 'approved' ? 'Odobren' : 'Odbijen') }}
                        </p>
                        <a href="{{ route('reports.download', $realizationReport) }}" class="btn-sm" style="background: #3b82f6; color: #fff;">Preuzmi</a>
                    @else
                        <a href="{{ route('reports.create', $application) }}" class="btn-sm" style="background: var(--primary); color: #fff;">Kreiraj izvještaj</a>
                    @endif
                </div>
                
                <div style="padding: 16px; background: #f9fafb; border-radius: 8px;">
                    <h3 style="font-size: 16px; margin-bottom: 8px;">Obrazac 4a - Finansijski izvještaj</h3>
                    @if($financialReport)
                        <p style="font-size: 12px; color: #6b7280; margin-bottom: 8px;">
                            Status: {{ $financialReport->status === 'submitted' ? 'Podnesen' : ($financialReport->status === 'approved' ? 'Odobren' : 'Odbijen') }}
                        </p>
                        <a href="{{ route('reports.download', $financialReport) }}" class="btn-sm" style="background: #3b82f6; color: #fff;">Preuzmi</a>
                    @else
                        <a href="{{ route('reports.create-financial', $application) }}" class="btn-sm" style="background: var(--primary); color: #fff;">Kreiraj izvještaj</a>
                    @endif
                </div>
            </div>
        </div>
        @endif

        <!-- Akcije -->
        <div class="info-card" style="text-align: center;">
            <a href="{{ route('dashboard') }}" class="btn btn-secondary">
                Nazad na Moj panel
            </a>
            @if($application->status === 'draft' && $canManage)
                @php
                    // Proveri da li je Obrazac kompletno popunjen koristeći metodu iz modela
                    $isObrazacComplete = $application->isObrazacComplete();
                @endphp
                @if($application->businessPlan)
                    <a href="{{ route('applications.business-plan.create', $application) }}" class="btn btn-primary" style="margin-left: 8px;">
                        Uredi biznis plan
                    </a>
                @elseif($isObrazacComplete)
                    <a href="{{ route('applications.business-plan.create', $application) }}" class="btn btn-primary" style="margin-left: 8px;">
                        Popuni biznis plan
                    </a>
                @else
                    <span style="color: #6b7280; font-size: 12px; margin-left: 8px; display: inline-block;">Popunite Obrazac 1a/1b prvo</span>
                @endif
            @endif
            
    </div>
</div>

<script>
(function() {
    let selectedFilesApp = [];

    function getAppIdFromInput(input) {
        const form = input.closest('form');
        return form ? form.dataset.appId : null;
    }

    window.updateFileDisplayApp = function(input) {
        const appId = getAppIdFromInput(input);
        if (!appId) return;
        const fileNamesDiv = document.getElementById('file-names-app-' + appId);
        const fileLabel = document.getElementById('file-label-' + appId);
        if (!fileNamesDiv || !fileLabel) return;

        if (input.files && input.files.length > 0) {
            const newFiles = Array.from(input.files);
            newFiles.forEach(function(newFile) {
                const exists = selectedFilesApp.some(function(f) { return f.name === newFile.name && f.size === newFile.size; });
                if (!exists) selectedFilesApp.push(newFile);
            });
            const dataTransfer = new DataTransfer();
            selectedFilesApp.forEach(function(f) { dataTransfer.items.add(f); });
            input.files = dataTransfer.files;
        }

        if (selectedFilesApp.length > 0) {
            let html = '<div style="font-size: 12px; color: var(--primary); font-weight: 600; margin-bottom: 4px;">Izabrano fajlova: ' + selectedFilesApp.length + (selectedFilesApp.length > 1 ? ' (biće spojeni u jedan PDF)' : '') + '</div>';
            html += '<ul style="margin: 0; padding-left: 20px; font-size: 12px; color: #6b7280; list-style: none;">';
            selectedFilesApp.forEach(function(file, index) {
                const size = (file.size / 1024 / 1024).toFixed(2);
                html += '<li style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; padding: 6px; background: #f9fafb; border-radius: 4px;">';
                html += '<span style="flex: 1;"><strong style="color: var(--primary);">' + (index + 1) + '.</strong> ' + file.name + ' (' + size + ' MB)</span>';
                html += '<div style="display: flex; gap: 4px; margin-left: 8px;">';
                if (index > 0) {
                    html += '<button type="button" class="file-action-btn-app" data-action="move-up" data-index="' + index + '" title="Pomeri gore" style="background: #3b82f6; color: white; border: none; border-radius: 4px; padding: 4px 8px; font-size: 11px; cursor: pointer;">⬆️</button>';
                } else {
                    html += '<button type="button" disabled style="background: #d1d5db; color: #9ca3af; border: none; border-radius: 4px; padding: 4px 8px; font-size: 11px; cursor: not-allowed;">⬆️</button>';
                }
                if (index < selectedFilesApp.length - 1) {
                    html += '<button type="button" class="file-action-btn-app" data-action="move-down" data-index="' + index + '" title="Pomeri dole" style="background: #3b82f6; color: white; border: none; border-radius: 4px; padding: 4px 8px; font-size: 11px; cursor: pointer;">⬇️</button>';
                } else {
                    html += '<button type="button" disabled style="background: #d1d5db; color: #9ca3af; border: none; border-radius: 4px; padding: 4px 8px; font-size: 11px; cursor: not-allowed;">⬇️</button>';
                }
                html += '<button type="button" class="file-action-btn-app" data-action="remove" data-index="' + index + '" title="Ukloni" style="background: #ef4444; color: white; border: none; border-radius: 4px; padding: 4px 8px; font-size: 11px; cursor: pointer;">✕</button>';
                html += '</div></li>';
            });
            html += '</ul>';
            fileNamesDiv.innerHTML = html;
            fileNamesDiv.style.display = 'block';
            fileLabel.textContent = selectedFilesApp.length === 1 ? selectedFilesApp[0].name : 'Izabrano ' + selectedFilesApp.length + ' fajlova (biće spojeni u jedan PDF)';
        } else {
            fileNamesDiv.style.display = 'none';
            fileLabel.textContent = 'Izaberi fajlove (možete izabrati više)';
        }
    };

    function removeFileApp(index, input) {
        selectedFilesApp.splice(index, 1);
        const dataTransfer = new DataTransfer();
        selectedFilesApp.forEach(function(f) { dataTransfer.items.add(f); });
        input.files = dataTransfer.files;
        updateFileDisplayApp(input);
    }

    function moveFileUpApp(index, input) {
        if (index > 0) {
            const t = selectedFilesApp[index];
            selectedFilesApp[index] = selectedFilesApp[index - 1];
            selectedFilesApp[index - 1] = t;
            const dataTransfer = new DataTransfer();
            selectedFilesApp.forEach(function(f) { dataTransfer.items.add(f); });
            input.files = dataTransfer.files;
            updateFileDisplayApp(input);
        }
    }

    function moveFileDownApp(index, input) {
        if (index < selectedFilesApp.length - 1) {
            const t = selectedFilesApp[index];
            selectedFilesApp[index] = selectedFilesApp[index + 1];
            selectedFilesApp[index + 1] = t;
            const dataTransfer = new DataTransfer();
            selectedFilesApp.forEach(function(f) { dataTransfer.items.add(f); });
            input.files = dataTransfer.files;
            updateFileDisplayApp(input);
        }
    }

    window.prepareUploadFormSubmit = function(event, appId) {
        const userDocSelect = document.querySelector('select[name="user_document_id"]');
        if (userDocSelect && userDocSelect.value) return true;

        const input = document.getElementById('file-input-' + appId);
        if (!input) return true;

        if (selectedFilesApp.length === 0) {
            event.preventDefault();
            alert('Morate priložiti fajl ili izabrati dokument iz biblioteke.');
            return false;
        }

        const maxFileSize = 20 * 1024 * 1024;
        for (let i = 0; i < selectedFilesApp.length; i++) {
            if (selectedFilesApp[i].size > maxFileSize) {
                event.preventDefault();
                alert('Fajl "' + selectedFilesApp[i].name + '" je prevelik. Maksimalno dozvoljeno je 20 MB po fajlu.');
                return false;
            }
        }

        const dataTransfer = new DataTransfer();
        selectedFilesApp.forEach(function(f) { dataTransfer.items.add(f); });
        input.files = dataTransfer.files;
        return true;
    };

    document.addEventListener('DOMContentLoaded', function() {
        document.addEventListener('mousedown', function(e) {
            const btn = e.target.closest('.file-action-btn-app');
            if (!btn) return;
            e.preventDefault();
            e.stopPropagation();
            const action = btn.getAttribute('data-action');
            const index = parseInt(btn.getAttribute('data-index'));
            const form = btn.closest('form');
            const input = form ? form.querySelector('input[type="file"]') : null;
            if (!input) return;
            if (action === 'move-up') moveFileUpApp(index, input);
            else if (action === 'move-down') moveFileDownApp(index, input);
            else if (action === 'remove') removeFileApp(index, input);
        });
        document.addEventListener('click', function(e) {
            if (e.target.closest('.file-action-btn-app')) {
                e.preventDefault();
                e.stopPropagation();
            }
        }, true);
    });
})();
</script>
@endsection
