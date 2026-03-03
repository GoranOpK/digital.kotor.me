@extends('layouts.app')

@section('content')
<style>
    :root {
        --primary: #0B3D91;
        --primary-dark: #0A347B;
        --secondary: #B8860B;
    }
    .competition-detail-page {
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
        font-size: 32px;
        font-weight: 700;
        margin: 0 0 8px;
    }
    .info-card {
        background: #fff;
        border-radius: 12px;
        padding: 24px;
        margin-bottom: 24px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    .main-content-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 24px;
        margin-bottom: 24px;
    }
    @media (min-width: 992px) {
        .main-content-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        .main-content-grid .info-card {
            margin-bottom: 0;
            height: 100%;
        }
    }
    .info-card h2 {
        font-size: 20px;
        font-weight: 700;
        color: var(--primary);
        margin: 0 0 16px;
        padding-bottom: 12px;
        border-bottom: 2px solid #e5e7eb;
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
    .status-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 9999px;
        font-size: 12px;
        font-weight: 600;
    }
    .status-open {
        background: #d1fae5;
        color: #065f46;
    }
    .status-closed {
        background: #fee2e2;
        color: #991b1b;
    }
    .deadline-alert {
        background: #fef3c7;
        border-left: 4px solid #f59e0b;
        padding: 16px 20px;
        border-radius: 8px;
        margin-bottom: 24px;
    }
    .deadline-alert strong {
        color: #92400e;
        font-size: 16px;
    }
    .documents-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    .documents-list li {
        padding: 12px 16px;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .documents-list li:last-child {
        border-bottom: none;
    }
    .documents-list li::before {
        content: "📄";
        font-size: 20px;
    }
    .btn-primary {
        background: var(--primary);
        color: #fff;
        padding: 12px 24px;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        font-size: 16px;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
        transition: background 0.2s;
    }
    .btn-primary:hover {
        background: var(--primary-dark);
    }
    .btn-primary:disabled {
        background: #9ca3af;
        cursor: not-allowed;
    }
    .alert {
        border-radius: 12px;
        padding: 16px 20px;
        margin-bottom: 20px;
        border: 1px solid;
    }
    .alert-info {
        background: #dbeafe;
        border-color: #3b82f6;
        color: #1e40af;
    }
</style>

<div class="competition-detail-page">
    <div class="container mx-auto px-4">
        <div class="page-header">
            <h1>{{ $competition->title }}</h1>
            <p style="color: rgba(255,255,255,0.9); margin: 0;">Detalji konkursa za podršku ženskom preduzetništvu</p>
        </div>

        @if($isUpcoming)
            <div class="alert alert-info" style="background: #eff6ff; border-color: #3b82f6; color: #1e40af;">
                <strong>🔜 Konkurs uskoro počinje!</strong><br>
                Prijave će biti moguće od: <strong>{{ $competition->start_date->format('d.m.Y') }}</strong>
            </div>
        @elseif($isOpen && ($daysRemaining > 0 || $hoursRemaining > 0 || $minutesRemaining > 0))
            <div class="deadline-alert" id="deadlineAlert">
                <strong>⚠️ Preostalo vreme za prijavu: <span id="countdown">Učitavanje...</span></strong>
                <div style="font-size: 14px; color: #92400e; margin-top: 4px;">
                    Rok za podnošenje prijava: {{ $deadline->format('d.m.Y H:i') }}
                </div>
            </div>
            
            <script>
                (function() {
                    const deadline = new Date('{{ $deadline->format('Y-m-d H:i:s') }}').getTime();
                    const countdownEl = document.getElementById('countdown');
                    
                    function updateCountdown() {
                        const now = new Date().getTime();
                        const distance = deadline - now;
                        
                        if (distance < 0) {
                            countdownEl.textContent = 'Rok je istekao';
                            return;
                        }
                        
                        const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                        const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                        const seconds = Math.floor((distance % (1000 * 60)) / 1000);
                        
                        let text = '';
                        if (days > 0) {
                            text += days + ' ' + (days === 1 ? 'dan' : 'dana');
                            if (hours > 0 || minutes > 0) text += ', ';
                        }
                        if (hours > 0) {
                            text += hours + ' ' + (hours === 1 ? 'sat' : (hours < 5 ? 'sata' : 'sati'));
                            if (minutes > 0) text += ', ';
                        }
                        if (minutes > 0) {
                            text += minutes + ' ' + (minutes === 1 ? 'minut' : (minutes < 5 ? 'minuta' : 'minuta'));
                        }
                        if (days === 0 && hours === 0 && minutes === 0) {
                            text = seconds + ' ' + (seconds === 1 ? 'sekund' : (seconds < 5 ? 'sekunda' : 'sekundi'));
                        }
                        
                        countdownEl.textContent = text;
                    }
                    
                    updateCountdown();
                    setInterval(updateCountdown, 1000);
                })();
            </script>
        @elseif(!$isOpen)
            <div class="alert" style="background: #fee2e2; border-color: #ef4444; color: #991b1b;">
                <strong>Konkurs je zatvoren za prijave</strong> - rok je istekao.
            </div>
        @endif

        @if($userApplication)
            <div class="alert alert-info">
                <strong>Već ste podneli prijavu na ovaj konkurs.</strong>
                <a href="{{ route('applications.show', $userApplication) }}" style="color: #1e40af; text-decoration: underline; margin-left: 8px;">
                    Pregledajte status prijave
                </a>
            </div>
        @endif

        <div class="main-content-grid">
            <!-- Osnovne informacije -->
            <div class="info-card">
                <h2>Osnovne informacije</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">Status konkursa</span>
                        <span class="info-value">
                            @php
                                $isApplicationDeadlinePassed = $competition->isApplicationDeadlinePassed();
                                $statusLabel = $isOpen ? 'Otvoren' : ($isApplicationDeadlinePassed ? 'Zatvoren za prijave' : 'Zatvoren');
                                $statusClass = $isOpen ? 'status-open' : 'status-closed';
                            @endphp
                            <span class="status-badge {{ $statusClass }}">
                                {{ $statusLabel }}
                            </span>
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Broj konkursa</span>
                        <span class="info-value">{{ $competition->competition_number ?? 'N/A' }}. konkurs {{ $competition->year ?? date('Y') }}. godine</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Ukupan budžet</span>
                        <span class="info-value">{{ number_format($competition->budget ?? 0, 2, ',', '.') }} €</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Maksimalna podrška po biznis planu</span>
                        <span class="info-value">{{ $competition->max_support_percentage ?? 30 }}% ({{ number_format(($competition->budget ?? 0) * (($competition->max_support_percentage ?? 30) / 100), 2, ',', '.') }} €)</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Datum objavljivanja</span>
                        <span class="info-value">{{ $competition->published_at ? $competition->published_at->format('d.m.Y H:i') : 'N/A' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Datum početka</span>
                        <span class="info-value">{{ $competition->start_date ? $competition->start_date->format('d.m.Y') : 'N/A' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Rok za prijave</span>
                        <span class="info-value">{{ $deadline ? $deadline->format('d.m.Y H:i') : 'N/A' }}</span>
                    </div>
                </div>
            </div>

            @php
                $isCommissionMemberForThisCompetition = false;
                if (auth()->check()) {
                    $userRole = auth()->user()->role ? auth()->user()->role->name : null;
                    if ($userRole === 'komisija' && $competition->commission_id) {
                        $commissionMember = \App\Models\CommissionMember::where('user_id', auth()->id())
                            ->where('status', 'active')
                            ->first();
                        if ($commissionMember && $commissionMember->commission_id === $competition->commission_id) {
                            $isCommissionMemberForThisCompetition = true;
                        }
                    }
                }
            @endphp
            @if(!$isCommissionMemberForThisCompetition)
                <!-- Obavezna dokumentacija -->
                <div class="info-card">
                    <h2>Obavezna dokumentacija</h2>
                    <p style="color: #6b7280; margin-bottom: 16px;">
                        Prilikom prijave na konkurs, potrebno je priložiti sledeće dokumente:
                    </p>
                    
                    @if(auth()->check() && $applicantType && ($applicantType === 'preduzetnica' || $applicantType === 'doo' || $applicantType === 'ostalo' || ($applicantType === 'fizicko_lice' && ($userType === 'Fizičko lice' || $userType === 'Rezident'))))
                    <!-- Izbor faze biznisa -->
                    <div style="margin-bottom: 20px; padding: 16px; background: #f3f4f6; border-radius: 8px;">
                        <label style="display: block; font-weight: 600; margin-bottom: 12px; color: #374151;">
                            Faza biznisa <span style="color: #dc2626;">*</span>
                        </label>
                        <div style="display: flex; gap: 20px; flex-wrap: wrap;">
                            <label style="display: flex; align-items: center; cursor: pointer;">
                                <input 
                                    type="radio" 
                                    name="business_stage_preview" 
                                    value="započinjanje" 
                                    id="business_stage_zapocinjanje_preview"
                                    checked
                                    style="margin-right: 8px; cursor: pointer;"
                                >
                                <span>{{ ($applicantType === 'doo' || $applicantType === 'ostalo') ? 'Društvo koje započinje biznis' : 'Preduzetnica koja započinje biznis' }}</span>
                            </label>
                            <label style="display: flex; align-items: center; cursor: pointer;">
                                <input 
                                    type="radio" 
                                    name="business_stage_preview" 
                                    value="razvoj" 
                                    id="business_stage_razvoj_preview"
                                    style="margin-right: 8px; cursor: pointer;"
                                >
                                <span>{{ ($applicantType === 'doo' || $applicantType === 'ostalo') ? 'Društvo koje planira razvoj poslovanja' : 'Preduzetnica koja planira razvoj poslovanja' }}</span>
                            </label>
                        </div>
                    </div>
                    @endif
                    
                    <ul class="documents-list" id="documents-list">
                        @foreach($requiredDocuments as $document)
                            <li>{{ $document }}</li>
                        @endforeach
                    </ul>
                </div>
            @else
                <!-- Za članove komisije: Opis konkursa u istom redu kao Osnovne informacije -->
                @if($competition->description)
                <div class="info-card">
                    <h2>Opis konkursa</h2>
                    <div style="color: #374151; line-height: 1.8; white-space: pre-wrap;">
                        {{ $competition->description }}
                    </div>
                </div>
                @endif
            @endif
        </div>

        <!-- Opis konkursa (ispod grida samo kada nisu članovi komisije tog konkursa) -->
        @if(!$isCommissionMemberForThisCompetition && $competition->description)
        <div class="info-card">
            <h2>Opis konkursa</h2>
            <div style="color: #374151; line-height: 1.8; white-space: pre-wrap;">
                {{ $competition->description }}
            </div>
        </div>
        @endif


        <!-- Akcije -->
        @php
            $userRole = auth()->check() ? (auth()->user()->role ? auth()->user()->role->name : null) : null;
            $isCompetitionAdmin = $userRole === 'konkurs_admin';
            $isCommissionMemberForThisCompetition = false;
            if (auth()->check() && $userRole === 'komisija' && $competition->commission_id) {
                $commissionMember = \App\Models\CommissionMember::where('user_id', auth()->id())
                    ->where('status', 'active')
                    ->first();
                if ($commissionMember && $commissionMember->commission_id === $competition->commission_id) {
                    $isCommissionMemberForThisCompetition = true;
                }
            }
        @endphp
        @if(!$isCompetitionAdmin)
        <div class="info-card" style="text-align: center;">
            @if($isOpen && !$userApplication && auth()->check() && !$isCommissionMemberForThisCompetition)
                <a href="{{ route('applications.create', $competition) }}" class="btn-primary" id="applyBtn" data-base-url="{{ route('applications.create', $competition) }}" data-applicant-type="{{ $applicantType ?? '' }}">
                    Prijavi se na konkurs
                </a>
            @elseif($isOpen && auth()->check() && $isCommissionMemberForThisCompetition)
                <p style="color: #6b7280; margin-bottom: 0;">
                    Članovi komisije ne mogu se prijaviti na konkurse za koje su imenovani kao članovi komisije.
                </p>
            @elseif(!auth()->check())
                <p style="color: #6b7280; margin-bottom: 16px;">
                    Za prijavu na konkurs potrebno je da budete prijavljeni.
                </p>
                <a href="{{ route('login') }}" class="btn-primary">Prijavite se</a>
            @elseif($userApplication)
                <a href="{{ route('applications.show', $userApplication) }}" class="btn-primary">
                    Pregledajte vašu prijavu
                </a>
            @else
                <button class="btn-primary" disabled>Konkurs je zatvoren</button>
            @endif
        </div>
        @endif
    </div>
</div>

@if(auth()->check() && $applicantType && ($applicantType === 'preduzetnica' || $applicantType === 'doo' || $applicantType === 'ostalo' || ($applicantType === 'fizicko_lice' && ($userType === 'Fizičko lice' || $userType === 'Rezident'))))
<script>
document.addEventListener('DOMContentLoaded', function() {
    const applicantType = @json($applicantType);
    const documentLabels = @json($documentLabels);
    const businessStageRadios = document.querySelectorAll('input[name="business_stage_preview"]');
    const documentsList = document.getElementById('documents-list');
    
    // Mapa dokumenata po tipu prijave i fazi biznisa
    // Za započinjanje, uvijek prikazujemo sve dokumente sa napomenama za opcione
    const documentsMap = {
        'preduzetnica': {
            'započinjanje': {
                all: ['licna_karta', 'crps_resenje', 'pib_resenje', 'pdv_resenje', 'potvrda_neosudjivanost', 'uvjerenje_opstina_porezi', 'uvjerenje_opstina_nepokretnost', 'dokaz_ziro_racun', 'predracuni_nabavka'],
                optional: ['crps_resenje', 'pib_resenje', 'pdv_resenje', 'dokaz_ziro_racun']
            },
            'razvoj': {
                all: ['licna_karta', 'crps_resenje', 'pib_resenje', 'pdv_resenje', 'potvrda_neosudjivanost', 'uvjerenje_opstina_porezi', 'uvjerenje_opstina_nepokretnost', 'potvrda_upc_porezi', 'ioppd_obrazac', 'dokaz_ziro_racun', 'predracuni_nabavka'],
                optional: ['pdv_resenje']
            }
        },
        'doo': {
            'započinjanje': {
                all: ['licna_karta', 'crps_resenje', 'pib_resenje', 'pdv_resenje', 'statut', 'karton_potpisa', 'potvrda_neosudjivanost', 'uvjerenje_opstina_porezi', 'uvjerenje_opstina_nepokretnost', 'predracuni_nabavka'],
                optional: ['crps_resenje', 'pib_resenje', 'pdv_resenje', 'statut', 'karton_potpisa']
            },
            'razvoj': {
                all: ['licna_karta', 'crps_resenje', 'pib_resenje', 'pdv_resenje', 'statut', 'karton_potpisa', 'godisnji_racuni', 'izvjestaj_registar_kase', 'potvrda_neosudjivanost', 'uvjerenje_opstina_porezi', 'uvjerenje_opstina_nepokretnost', 'potvrda_upc_porezi', 'ioppd_obrazac', 'predracuni_nabavka'],
                optional: ['pdv_resenje']
            }
        },
        'ostalo': {
            'započinjanje': {
                all: ['licna_karta', 'crps_resenje', 'pib_resenje', 'pdv_resenje', 'statut', 'karton_potpisa', 'potvrda_neosudjivanost', 'uvjerenje_opstina_porezi', 'uvjerenje_opstina_nepokretnost', 'predracuni_nabavka'],
                optional: ['crps_resenje', 'pib_resenje', 'pdv_resenje', 'statut', 'karton_potpisa']
            },
            'razvoj': {
                all: ['licna_karta', 'crps_resenje', 'pib_resenje', 'pdv_resenje', 'statut', 'karton_potpisa', 'godisnji_racuni', 'izvjestaj_registar_kase', 'potvrda_neosudjivanost', 'uvjerenje_opstina_porezi', 'uvjerenje_opstina_nepokretnost', 'potvrda_upc_porezi', 'ioppd_obrazac', 'predracuni_nabavka'],
                optional: ['pdv_resenje']
            }
        },
        'fizicko_lice': {
            'započinjanje': {
                all: ['licna_karta', 'crps_resenje', 'pib_resenje', 'pdv_resenje', 'potvrda_neosudjivanost', 'uvjerenje_opstina_porezi', 'uvjerenje_opstina_nepokretnost', 'dokaz_ziro_racun', 'predracuni_nabavka'],
                optional: ['crps_resenje', 'pib_resenje', 'pdv_resenje', 'dokaz_ziro_racun']
            },
            'razvoj': {
                // ista lista kao za preduzetnicu - razvoj
                all: ['licna_karta', 'crps_resenje', 'pib_resenje', 'pdv_resenje', 'potvrda_neosudjivanost', 'uvjerenje_opstina_porezi', 'uvjerenje_opstina_nepokretnost', 'potvrda_upc_porezi', 'ioppd_obrazac', 'dokaz_ziro_racun', 'predracuni_nabavka'],
                optional: ['pdv_resenje']
            }
        }
    };
    
    function updateDocumentsList() {
        const selectedStage = document.querySelector('input[name="business_stage_preview"]:checked')?.value;
        if (!selectedStage) return;
        
        // Uzmi listu dokumenata
        const docTypes = documentsMap[applicantType]?.[selectedStage]?.all || 
                        documentsMap[applicantType]?.[selectedStage]?.withoutRegistration || 
                        documentsMap[applicantType]?.[selectedStage]?.withRegistration || [];
        
        // Uzmi listu opcionih dokumenata
        const optionalDocs = documentsMap[applicantType]?.[selectedStage]?.optional || [];
        
        // Dodaj obavezne dokumente koje svi moraju imati
        let allDocuments = [];
        
        if ((selectedStage === 'započinjanje' || selectedStage === 'razvoj') && (applicantType === 'preduzetnica' || applicantType === 'fizicko_lice')) {
            allDocuments = [
                'Prijava na konkurs za podsticaj ženskog preduzetništva (obrazac 1a)',
                'Popunjena forma za biznis plan (obrazac 2 — Forma za biznis plan)',
            ];
        } else if ((selectedStage === 'započinjanje' || selectedStage === 'razvoj') && (applicantType === 'doo' || applicantType === 'ostalo')) {
            allDocuments = [
                'Prijavu na konkurs za podsticaj ženskog preduzetništva (obrazac 1b)',
                'Popunjenu formu za biznis plan (obrazac 2)',
            ];
        } else {
            allDocuments = [
                'Prijava na konkurs (Obrazac 1a ili 1b)',
                'Popunjena forma za biznis plan (Obrazac 2)',
            ];
        }
        
        // Dodaj dokumente sa napomenama za opcione
        docTypes.forEach(docType => {
            let docLabel = documentLabels[docType] || docType;
            
            // Ako je dokument opcioni, dodaj napomenu
            if (optionalDocs.includes(docType)) {
                if (docType === 'crps_resenje') {
                    docLabel = 'Rješenje o upisu u CRPS (ukoliko ima registrovanu djelatnost)';
                } else if (docType === 'pib_resenje') {
                    docLabel = 'Rješenje o PIB-u PJ Poreske uprave (ukoliko ima registrovanu djelatnost)';
                } else if (docType === 'pdv_resenje') {
                    if (selectedStage === 'započinjanje') {
                        docLabel = 'Rješenje o registraciji za PDV (ukoliko ima registrovanu djelatnost i ako je obveznik PDV-a) ili potvrdu da nije PDV obveznik (ukoliko nije PDV obveznik)';
                    } else if (selectedStage === 'razvoj') {
                        docLabel = 'Rješenje o registraciji za PDV (ako je obveznik PDV-a) ili potvrdu da nije PDV obveznik (ukoliko nije PDV obveznik)';
                    }
                } else if (docType === 'potvrda_neosudjivanost' && applicantType === 'preduzetnica' && selectedStage === 'razvoj') {
                    docLabel = 'Potvrda da se ne vodi krivični postupak na ime preduzetnice izdatu od Osnovnog suda';
                } else if (docType === 'potvrda_neosudjivanost' && (applicantType === 'preduzetnica' || applicantType === 'fizicko_lice')) {
                    docLabel = 'Potvrda da se ne vodi krivični postupak na ime podnositeljke prijave odnosno preduzetnice izdatu od Osnovnog suda';
                } else if (docType === 'uvjerenje_opstina_porezi' && selectedStage === 'započinjanje' && (applicantType === 'preduzetnica' || applicantType === 'fizicko_lice')) {
                    docLabel = 'Uvjerenje od organa lokalne uprave, ne starije od 30 dana, o urednom izmirivanju poreza na ime podnositeljke prijave odnosno preduzetnice po osnovu prireza porezu, članskog doprinosa, lokalnih komunalnih taksi i naknada';
                } else if (docType === 'dokaz_ziro_racun') {
                    docLabel = 'Dokaz o broju poslovnog žiro računa preduzetnice (ukoliko ima registrovanu djelatnost)';
                } else if (docType === 'predracuni_nabavka') {
                    docLabel = 'Predračuni za planiranu nabavku';
                } else if (docType === 'statut') {
                    docLabel = 'Važeći Statut društva (ukoliko ima registrovanu djelatnost)';
                } else if (docType === 'karton_potpisa') {
                    docLabel = 'Važeći karton deponovanih potpisa (ukoliko ima registrovanu djelatnost)';
                }
            }
            
            // Ažuriraj label za potvrdu neosuđivanosti – preduzetnica/fizičko lice razvoj
            if (docType === 'potvrda_neosudjivanost' && (applicantType === 'preduzetnica' || applicantType === 'fizicko_lice') && selectedStage === 'razvoj') {
                docLabel = 'Potvrda da se ne vodi krivični postupak na ime preduzetnice izdatu od Osnovnog suda';
            }
            
            // Ažuriraj label za uvjerenje o nepokretnosti – preduzetnica/fizičko lice razvoj
            if (docType === 'uvjerenje_opstina_nepokretnost' && (applicantType === 'preduzetnica' || applicantType === 'fizicko_lice') && selectedStage === 'razvoj') {
                docLabel = 'Uvjerenje od organa lokalne uprave, ne starije od 30 dana, o urednom izmirivanju poreza na nepokretnost na ime preduzetnice';
            } else if (docType === 'uvjerenje_opstina_nepokretnost' && selectedStage === 'započinjanje' && (applicantType === 'preduzetnica' || applicantType === 'fizicko_lice')) {
                docLabel = 'Uvjerenje od organa lokalne uprave, ne starije od 30 dana, o urednom izmirivanju poreza na nepokretnost na ime podnositeljke prijave odnosno preduzetnice';
            }
            
            // Ažuriraj label za uvjerenje o porezima – razvoj (preduzetnica/fizičko lice)
            if (docType === 'uvjerenje_opstina_porezi' && selectedStage === 'razvoj' && (applicantType === 'preduzetnica' || applicantType === 'fizicko_lice')) {
                docLabel = 'Uvjerenje od organa lokalne uprave, ne starije od 30 dana, o urednom izmirivanju poreza na ime preduzetnice po osnovu prireza porezu, članskog doprinosa, lokalnih komunalnih taksi i naknada';
            }
            
            // Ažuriraj label za potvrdu Poreske uprave – razvoj (preduzetnica/fizičko lice)
            if (docType === 'potvrda_upc_porezi' && selectedStage === 'razvoj' && (applicantType === 'preduzetnica' || applicantType === 'fizicko_lice')) {
                docLabel = 'Potvrda Poreske uprave o urednom izmirivanju poreza i doprinosa ne stariju od 30 dana, na ime preduzetnice';
            }
            
            // Ažuriraj label za IOPPD obrazac – razvoj (preduzetnica/fizičko lice)
            if (docType === 'ioppd_obrazac' && selectedStage === 'razvoj' && (applicantType === 'preduzetnica' || applicantType === 'fizicko_lice')) {
                docLabel = 'Odgovarajući obrazac ovjeren od strane Poreske uprave za poslijednji mjesec uplate poreza i doprinosa za zaposlene, kao dokaz o broju zaposlenih (IOPPD Obrazac) ili potvrdu ovjerenu od strane Poreske uprave da preduzetnica nema zaposlenih';
            }
            
            // Dokaz žiro račun – preduzetnica/fizičko lice razvoj
            if (docType === 'dokaz_ziro_racun' && (applicantType === 'preduzetnica' || applicantType === 'fizicko_lice') && selectedStage === 'razvoj') {
                docLabel = 'Dokaz o broju poslovnog žiro računa preduzetnice';
            }
            
            // DOO/Ostalo - razvoj – tačni tekstovi prema Odluci
            if ((applicantType === 'doo' || applicantType === 'ostalo') && selectedStage === 'razvoj') {
                if (docType === 'licna_karta') {
                    docLabel = 'Ovjerenu kopiju lične karte nosioca biznisa (osnivačica ili jedna od osnivača i izvršna direktorica)';
                } else if (docType === 'crps_resenje') {
                    docLabel = 'Rješenje o upisu u CRPS';
                } else if (docType === 'pib_resenje') {
                    docLabel = 'Rješenje o registraciji PJ Poreske uprave';
                } else if (docType === 'pdv_resenje') {
                    docLabel = 'Rješenje o registraciji za PDV (ako je obveznik PDV-a) ili potvrdu da nije PDV obveznik (ukoliko nije PDV obveznik)';
                } else if (docType === 'statut') {
                    docLabel = 'Važeći Statut društva';
                } else if (docType === 'karton_potpisa') {
                    docLabel = 'Važeći karton deponovanih potpisa';
                } else if (docType === 'godisnji_racuni') {
                    docLabel = 'Komplet obrazaca za godišnje račune (Bilans stanja, Bilans uspjeha, Analitika kupaca i Analitika dobavljača) za prethodnu godinu. Napomena: U slučaju da preduzetnica/društvo ne vodi analitiku kupaca tj. posluje isključivo sa fizičkim licima i naplata se vrši odmah putem registar kase, preduzetnica/društvo ima obavezu dostaviti periodični izvještaj sa registar kase';
                } else if (docType === 'izvjestaj_registar_kase') {
                    docLabel = 'Izvještaj sa registra kase';
                } else if (docType === 'potvrda_neosudjivanost') {
                    docLabel = 'Potvrda da se ne vodi krivični postupak na ime društva i na ime nosioca biznisa (osnivačice ili jedne od osnivača i izvršne direktorice) izdatu od strane Osnovnog suda';
                } else if (docType === 'uvjerenje_opstina_porezi') {
                    docLabel = 'Uvjerenje od organa lokalne uprave, ne starije od 30 dana, o urednom izmirivanju poreza na ime nosioca biznisa (osnivačice ili jedne od osnivača i izvršne direktorice) i na ime društva po osnovu prireza porezu, članskog doprinosa, lokalnih komunalnih taksi i naknada';
                } else if (docType === 'uvjerenje_opstina_nepokretnost') {
                    docLabel = 'Uvjerenje od organa lokalne uprave, ne starije od 30 dana, o urednom izmirivanju poreza na nepokretnost na ime nosioca biznisa (osnivačice ili jedne od osnivača i izvršne direktorice) i na ime društva';
                } else if (docType === 'potvrda_upc_porezi') {
                    docLabel = 'Potvrdu Poreske uprave o urednom izmirivanju poreza i doprinosa ne stariju od 30 dana, na ime nosioca biznisa (osnivačice ili jedne od osnivača i izvršne direktorice) i na ime društva';
                } else if (docType === 'ioppd_obrazac') {
                    docLabel = 'Odgovarajući obrazac za poslijednji mjesec uplate poreza i doprinosa za zaposlene ovjeren od strane Poreske uprave, kao dokaz o broju zaposlenih (IOPPD Obrazac)';
                } else if (docType === 'dokaz_ziro_racun') {
                    docLabel = 'Dokaz o broju poslovnog žiro računa društva';
                } else if (docType === 'predracuni_nabavka') {
                    docLabel = 'Predračune za planiranu nabavku';
                }
            }
            
            // DOO/Ostalo - započinjanje – tačni tekstovi prema Odluci
            if ((applicantType === 'doo' || applicantType === 'ostalo') && selectedStage === 'započinjanje') {
                if (docType === 'licna_karta') {
                    docLabel = 'Ovjerenu kopiju lične karte';
                } else if (docType === 'crps_resenje') {
                    docLabel = 'Rješenje o upisu u CRPS (ukoliko ima registrovanu djelatnost)';
                } else if (docType === 'pib_resenje') {
                    docLabel = 'Rješenje o registraciji PJ Poreske uprave (ukoliko ima registrovanu djelatnost)';
                } else if (docType === 'pdv_resenje') {
                    docLabel = 'Rješenje o registraciji za PDV (ukoliko ima registrovanu djelatnost i ako je obveznik PDV-a) ili potvrdu da nije PDV obveznik (ukoliko nije PDV obveznik)';
                } else if (docType === 'statut') {
                    docLabel = 'Važeći Statut društva (ukoliko ima registrovanu djelatnost)';
                } else if (docType === 'karton_potpisa') {
                    docLabel = 'Važeći karton deponovanih potpisa (ukoliko ima registrovanu djelatnost)';
                } else if (docType === 'potvrda_neosudjivanost') {
                    docLabel = 'Potvrda da se ne vodi krivični postupak na ime podnositeljke prijave odnosno na ime nosioca biznisa (osnivačice ili jedne od osnivača i izvršne direktorice) izdatu od strane Osnovnog suda';
                } else if (docType === 'uvjerenje_opstina_porezi') {
                    docLabel = 'Uvjerenje od organa lokalne uprave, ne starije od 30 dana, o urednom izmirivanju poreza na ime podnositeljke prijave odnosno nosioca biznisa (osnivačice ili jedne od osnivača i izvršne direktorice) po osnovu prireza porezu, članskog doprinosa, lokalnih komunalnih taksi i naknada';
                } else if (docType === 'uvjerenje_opstina_nepokretnost') {
                    docLabel = 'Uvjerenje od organa lokalne uprave, ne starije od 30 dana, o urednom izmirivanju poreza na nepokretnost na ime podnositeljke prijave odnosno nosioca biznisa (osnivačice ili jedne od osnivača i izvršne direktorice)';
                } else if (docType === 'dokaz_ziro_racun') {
                    docLabel = 'Dokaz o broju poslovnog žiro računa društva (ukoliko ima registrovanu djelatnost)';
                } else if (docType === 'predracuni_nabavka') {
                    docLabel = 'Predračune za planiranu nabavku';
                }
            }
            
            allDocuments.push(docLabel);
        });
        
        // Ažuriraj listu
        if (documentsList) {
            documentsList.innerHTML = allDocuments.map(doc => `<li>${doc}</li>`).join('');
        }
    }
    
    // Dodaj event listener-e na radio button-e
    businessStageRadios.forEach(radio => {
        radio.addEventListener('change', updateDocumentsList);
        radio.addEventListener('change', updateApplyLink);
    });
    
    // Ažuriraj link "Prijavi se" sa izabranim business_stage i applicant_type (prosljeđuje se kroz URL parametar)
    function updateApplyLink() {
        const applyBtn = document.getElementById('applyBtn');
        if (!applyBtn) return;
        const baseUrl = applyBtn.getAttribute('data-base-url');
        if (!baseUrl) return;
        const applicantTypeFromBtn = applyBtn.getAttribute('data-applicant-type');
        const selectedStage = document.querySelector('input[name="business_stage_preview"]:checked')?.value;
        const url = new URL(baseUrl, window.location.origin);
        if (applicantTypeFromBtn && ['preduzetnica', 'doo', 'fizicko_lice', 'ostalo'].includes(applicantTypeFromBtn)) {
            url.searchParams.set('applicant_type', applicantTypeFromBtn);
        }
        if (selectedStage) {
            url.searchParams.set('business_stage', selectedStage);
        }
        applyBtn.href = url.toString();
    }
    
    // Inicijalno ažuriraj listu i link
    if (documentsList) {
        setTimeout(function() {
            updateDocumentsList();
        }, 100);
    }
    updateApplyLink();
});
</script>
@endif
@endsection
