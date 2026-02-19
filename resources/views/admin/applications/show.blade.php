@extends('layouts.app')

@section('content')
<style>
    :root {
        --primary: #0B3D91;
        --primary-dark: #0A347B;
    }
    .admin-page {
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
        margin: 0;
    }
    .info-card {
        background: #fff;
        border-radius: 12px;
        padding: 16px;
        margin-bottom: 24px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    .info-card h2 {
        font-size: 16px;
        font-weight: 700;
        color: var(--primary);
        margin: 0 0 12px;
        padding-bottom: 8px;
        border-bottom: 2px solid #e5e7eb;
    }
    .info-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
    }
    .info-item {
        display: flex;
        flex-direction: column;
        margin-bottom: 0;
    }
    .info-label {
        font-size: 11px;
        font-weight: 600;
        color: #6b7280;
        text-transform: uppercase;
        margin-bottom: 2px;
        letter-spacing: 0.3px;
    }
    .info-value {
        font-size: 13px;
        color: #111827;
        font-weight: 500;
        line-height: 1.4;
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
    .documents-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    .document-item {
        padding: 8px 10px;
        border: 1px solid #e5e7eb;
        border-radius: 6px;
        margin-bottom: 6px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 13px;
    }
    .btn {
        padding: 6px 12px;
        border-radius: 6px;
        text-decoration: none;
        font-size: 12px;
        font-weight: 600;
    }
    .btn-primary {
        background: var(--primary);
        color: #fff;
    }
</style>

<div class="admin-page">
    <div class="container mx-auto px-4">
        <div class="page-header">
            <h1>Pregled prijave</h1>
        </div>

        <!-- Status prijave, Osnovni podaci i Priložena dokumentacija u istom redu -->
        <style>
            @media (min-width: 768px) {
                .status-and-basic-info {
                    grid-template-columns: repeat(3, 1fr) !important;
                }
            }
        </style>
        <div class="status-and-basic-info" style="display: grid; grid-template-columns: 1fr; gap: 16px; margin-bottom: 24px;">
                <!-- Status prijave -->
                <div class="info-card">
                    <h2>Status prijave</h2>
                    @php
                        $userRole = auth()->user()->role ? auth()->user()->role->name : null;
                        $isCommissionMember = $userRole === 'komisija';
                    @endphp
                    @if($isCommissionMember || $userRole === 'superadmin')
                        {{-- Detaljni prikaz statusa za članove komisije --}}
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
                                    <span class="status-badge {{ $statusClass }}" style="font-size: 12px; padding: 4px 12px;">
                                        {{ $statusLabels[$application->status] ?? $application->status }}
                                    </span>
                                </span>
                            </div>
                            <div class="info-item" style="margin-bottom: 16px;">
                                <span class="info-label">Obrazac 1a/1b</span>
                                <span class="info-value">
                                    @php
                                        $isObrazacComplete = $application->isObrazacComplete();
                                        $obrazacLabel = null;
                                        $obrazacClass = 'status-draft';
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
                                        } else {
                                            // Obrazac nije kompletan - prikaži nacrt prema tipu
                                            if ($application->applicant_type === 'preduzetnica') {
                                                $obrazacLabel = 'Obrazac 1a - Nacrt';
                                            } elseif (in_array($application->applicant_type, ['doo', 'ostalo'])) {
                                                $obrazacLabel = 'Obrazac 1b - Nacrt';
                                            } elseif ($application->applicant_type === 'fizicko_lice') {
                                                $obrazacLabel = 'Obrazac 1a/1b - Nacrt';
                                            }
                                        }
                                    @endphp
                                    @if($obrazacLabel)
                                        <a href="{{ route('applications.create', $application->competition_id) }}?application_id={{ $application->id }}" class="status-badge {{ $obrazacClass }}" style="font-size: 12px; padding: 4px 12px; text-decoration: none; cursor: pointer;">
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
                            @if($application->evaluationScores->count() > 0 || $application->status === 'rejected')
                            <div class="info-item">
                                <span class="info-label">Konačna ocjena</span>
                                <span class="info-value">{{ number_format($application->getDisplayScore(), 2) }} / 50</span>
                            </div>
                            @endif
                            @if($application->ranking_position)
                            <div class="info-item">
                                <span class="info-label">Pozicija na rang listi</span>
                                <span class="info-value">#{{ $application->ranking_position }}</span>
                            </div>
                            @endif
                        </div>
                    @else
                        {{-- Osnovni prikaz za ostale korisnike --}}
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-label">Status</span>
                                <span class="info-value">
                                    <span class="status-badge status-{{ $application->status }}">
                                        @if($application->status === 'draft') Nacrt
                                        @elseif($application->status === 'submitted') Podnesena
                                        @elseif($application->status === 'evaluated') Ocjenjena
                                        @elseif($application->status === 'approved') Odobrena
                                        @elseif($application->status === 'rejected') Odbijena
                                        @else {{ $application->status }}
                                        @endif
                                    </span>
                                </span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Datum podnošenja</span>
                                <span class="info-value">
                                    {{ $application->submitted_at ? $application->submitted_at->format('d.m.Y H:i') : 'N/A' }}
                                </span>
                            </div>
                            @if($application->evaluationScores->count() > 0 || $application->status === 'rejected')
                            <div class="info-item">
                                <span class="info-label">Konačna ocjena</span>
                                <span class="info-value">{{ number_format($application->getDisplayScore(), 2) }} / 50</span>
                            </div>
                            @endif
                            @if($application->ranking_position)
                            <div class="info-item">
                                <span class="info-label">Pozicija na rang listi</span>
                                <span class="info-value">#{{ $application->ranking_position }}</span>
                            </div>
                            @endif
                        </div>
                    @endif
                </div>

                <!-- Osnovni podaci -->
                <div class="info-card">
                    <h2>Osnovni podaci</h2>
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">Naziv biznis plana</span>
                            <span class="info-value">{{ $application->business_plan_name }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Podnosilac</span>
                            <span class="info-value">{{ $application->user->name ?? 'N/A' }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Email</span>
                            <span class="info-value">{{ $application->user->email ?? 'N/A' }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Konkurs</span>
                            <span class="info-value">{{ $application->competition->title ?? 'N/A' }}</span>
                        </div>
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

                <!-- Priložena dokumentacija -->
                @php
                    $userRole = auth()->user()->role ? auth()->user()->role->name : null;
                    // Mapiranje tipova dokumenata na nazive traženih dokumenata (prema Odluci)
                    $documentLabels = [
                        'licna_karta' => ($application->applicant_type === 'doo' || $application->applicant_type === 'ostalo') && $application->business_stage === 'razvoj'
                            ? 'Ovjerena kopija lične karte (osnivačica ili jedna od osnivača i izvršna direktorica)'
                            : 'Ovjerena kopija lične karte',
                        'crps_resenje' => 'Rješenje o upisu u CRPS' . (($application->applicant_type === 'preduzetnica' || $application->applicant_type === 'fizicko_lice' || $application->applicant_type === 'doo' || $application->applicant_type === 'ostalo') && $application->business_stage === 'započinjanje' ? ' (ukoliko ima registrovanu djelatnost)' : ''),
                        'pib_resenje' => ($application->applicant_type === 'preduzetnica' && $application->business_stage === 'započinjanje') ? 'Rješenje o PIB-u PJ Poreske uprave (ukoliko ima registrovanu djelatnost)' : ((($application->applicant_type === 'doo' || $application->applicant_type === 'ostalo') && $application->business_stage === 'započinjanje') ? 'Rješenje o registraciji PJ Poreske uprave (ukoliko ima registrovanu djelatnost)' : 'Rješenje o registraciji PJ Uprave prihoda i carina'),
                        'pdv_resenje' => ($application->applicant_type === 'preduzetnica' && $application->business_stage === 'započinjanje') ? 'Rješenje o registraciji za PDV (ukoliko ima registrovanu djelatnost i ako je obveznik PDV-a) ili potvrdu da nije PDV obveznik (ukoliko nije PDV obveznik)' : ((($application->applicant_type === 'doo' || $application->applicant_type === 'ostalo') && $application->business_stage === 'započinjanje') ? 'Rješenje o registraciji za PDV (ukoliko ima registrovanu djelatnost i ako je obveznik PDV-a) ili potvrdu da nije PDV obveznik (ukoliko nije PDV obveznik)' : 'Rješenje o registraciji za PDV' . ($application->business_stage === 'razvoj' ? ' (ako je obveznik PDV-a)' : '')),
                        'statut' => ($application->applicant_type === 'doo' || $application->applicant_type === 'ostalo') && $application->business_stage === 'započinjanje' ? 'Važeći Statut društva (ukoliko ima registrovanu djelatnost)' : 'Važeći Statut društva',
                        'karton_potpisa' => ($application->applicant_type === 'doo' || $application->applicant_type === 'ostalo') && $application->business_stage === 'započinjanje' ? 'Važeći karton deponovanih potpisa (ukoliko ima registrovanu djelatnost)' : 'Važeći karton deponovanih potpisa',
                        'potvrda_neosudjivanost' => ($application->applicant_type === 'preduzetnica' && $application->business_stage === 'započinjanje') ? 'Potvrda da se ne vodi krivični postupak na ime preduzetnika izdatu od strane Osnovnog suda' : (($application->applicant_type === 'doo' || $application->applicant_type === 'ostalo') && $application->business_stage === 'razvoj' ? 'Potvrda o neosuđivanosti za krivična djela na ime nosioca biznisa (osnivačice ili jedne od osnivača i izvršne direktorice) izdatu od strane Osnovnog suda' : ((($application->applicant_type === 'doo' || $application->applicant_type === 'ostalo') && $application->business_stage === 'započinjanje') ? 'Potvrda da se ne vodi krivični postupak na ime društva i na ime nosioca biznisa (osnivačice ili jedne od osnivača i izvršne direktorice) izdatu od strane Osnovnog suda' : 'Potvrda o neosuđivanosti za krivična djela na ime preduzetnice izdatu od strane Osnovnog suda')),
                        'uvjerenje_opstina_porezi' => ($application->applicant_type === 'preduzetnica' && $application->business_stage === 'započinjanje') ? 'Uvjerenje od organa lokalne uprave o izmirenim obavezama po osnovu lokalnih javnih prihoda na ime preduzetnika ne starije od mjesec dana' : (($application->applicant_type === 'doo' || $application->applicant_type === 'ostalo') && $application->business_stage === 'razvoj' ? 'Uvjerenje od organa lokalne uprave o urednom izmirivanju poreza na ime nosioca biznisa i na ime društva po osnovu prireza porezu, članskog doprinosa, lokalnih komunalnih taksi i naknada' : ((($application->applicant_type === 'doo' || $application->applicant_type === 'ostalo') && $application->business_stage === 'započinjanje') ? 'Uvjerenje od organa lokalne uprave, ne starije od mjesec dana, o urednom izmirivanju poreza na ime preduzetnice po osnovu prireza porezu, članskog doprinosa, lokalnih komunalnih taksi i naknada' : 'Uvjerenje od organa lokalne uprave o urednom izmirivanju poreza na ime preduzetnice po osnovu prireza porezu, članskog doprinosa, lokalnih komunalnih taksi i naknada')),
                        'uvjerenje_opstina_nepokretnost' => ($application->applicant_type === 'doo' || $application->applicant_type === 'ostalo') && $application->business_stage === 'razvoj' ? 'Uvjerenje od organa lokalne uprave o urednom izmirivanju poreza na nepokretnost na ime nosioca biznisa i na ime društva' : ((($application->applicant_type === 'doo' || $application->applicant_type === 'ostalo') && $application->business_stage === 'započinjanje') ? 'Uvjerenje od organa lokalne uprave, ne starije od mjesec dana, o urednom izmirivanju poreza na nepokretnost na ime preduzetnice' : 'Uvjerenje od organa lokalne uprave o urednom izmirivanju poreza na nepokretnost na ime preduzetnice'),
                        'potvrda_upc_porezi' => ($application->applicant_type === 'doo' || $application->applicant_type === 'ostalo') && $application->business_stage === 'razvoj' ? 'Potvrda Uprave prihoda i carina o urednom izmirivanju poreza i doprinosa ne stariju od 30 dana, na ime nosioca biznisa i na ime društva' : 'Potvrda Uprave prihoda i carina o urednom izmirivanju poreza i doprinosa ne stariju od 30 dana',
                        'ioppd_obrazac' => 'Odgovarajući obrazac za posljednji mjesec uplate poreza i doprinosa za zaposlene ovjeren od Uprave prihoda i carina, kao dokaz o broju zaposlenih (IOPPD Obrazac)',
                        'godisnji_racuni' => ($application->applicant_type === 'doo' || $application->applicant_type === 'ostalo') ? 'Komplet obrazaca za godišnje račune (Bilans stanja, Bilans uspjeha, Analitika kupaca i dobavljača) za prethodnu godinu' : 'Godišnji računi',
                        'biznis_plan_usb' => 'Jedna štampana i jedna elektronska verzija biznis plana na USB-u',
                        'izvjestaj_realizacija' => 'Izvještaj o realizaciji',
                        'finansijski_izvjestaj' => 'Finansijski izvještaj',
                        'dokaz_ziro_racun' => ($application->applicant_type === 'preduzetnica' && $application->business_stage === 'započinjanje') ? 'Dokaz o broju poslovnog žiro računa preduzetnika (ukoliko ima registrovanu djelatnost)' : 'Dokaz o broju poslovnog žiro računa',
                        'predracuni_nabavka' => 'Predračuni za planiranu nabavku',
                        'ostalo' => 'Ostalo',
                    ];
                    
                    // Za članove komisije prikaži tabelu sa svim potrebnim dokumentima
                    if ($userRole === 'komisija') {
                        $requiredDocs = $application->getRequiredDocuments();
                        $uploadedDocs = $application->documents->pluck('document_type')->toArray();
                    }
                @endphp
                @if($userRole === 'komisija')
                <div class="info-card">
                    <h2>Priložena dokumentacija</h2>
                    <table style="width: 100%; border-collapse: collapse; font-size: 13px; background: #fff; margin-top: 10px;">
                        <thead>
                            <tr style="background: #f9fafb; border-bottom: 2px solid #e5e7eb;">
                                <th style="padding: 12px; text-align: left; font-weight: 600; color: #374151;">Dokument</th>
                                <th style="padding: 12px; text-align: center; font-weight: 600; color: #374151; width: 120px;">Status</th>
                                <th style="padding: 12px; text-align: center; font-weight: 600; color: #374151; width: 100px;">Akcija</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($requiredDocs as $docType)
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
                </div>
                @endif
            </div>
        </div>

        <!-- Ocjene -->
        @if($application->evaluationScores->count() > 0)
        <div class="info-card">
            <h2>Ocjene komisije</h2>
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 2px solid #e5e7eb;">
                        <th style="padding: 12px; text-align: left;">Član komisije</th>
                        <th style="padding: 12px; text-align: center;">Ocjena</th>
                        <th style="padding: 12px; text-align: left;">Napomene</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($application->evaluationScores as $score)
                        @php
                            $memberScore = ($score->documents_complete === false) ? 0 : ($score->final_score ?? $score->calculateTotalScore());
                        @endphp
                        <tr style="border-bottom: 1px solid #e5e7eb;">
                            <td style="padding: 12px;">{{ $score->commissionMember->name ?? 'N/A' }}</td>
                            <td style="padding: 12px; text-align: center;">{{ number_format($memberScore, 2) }} / 50</td>
                            <td style="padding: 12px;">{{ $score->notes ? \Illuminate\Support\Str::limit($score->notes, 100) : '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        <div style="text-align: center; margin-top: 24px;">
            @php
                $userRole = auth()->user()->role ? auth()->user()->role->name : null;
            @endphp
            
            @if($userRole === 'konkurs_admin' || $userRole === 'komisija')
                <a href="{{ route('admin.competitions.show', $application->competition) }}" class="btn btn-primary">Nazad na konkurs</a>
            @else
                <a href="{{ route('admin.applications.index') }}" class="btn btn-primary">Nazad na listu</a>
            @endif
        </div>
    </div>
</div>
@endsection

