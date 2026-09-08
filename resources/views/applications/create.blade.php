@extends('layouts.app')

@section('content')
<style>
    :root {
        --primary: #0B3D91;
        --primary-dark: #0A347B;
        --secondary: #B8860B;
    }
    .application-form-page {
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
    .form-card {
        background: #fff;
        border-radius: 16px;
        padding: 32px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        margin-bottom: 24px;
    }
    .form-section {
        margin-bottom: 32px;
    }
    .form-section:last-child {
        margin-bottom: 0;
    }
    .form-section h2 {
        font-size: 20px;
        font-weight: 700;
        color: var(--primary);
        margin: 0 0 20px;
        padding-bottom: 12px;
        border-bottom: 2px solid #e5e7eb;
    }
    .form-group {
        margin-bottom: 20px;
    }
    .form-label {
        display: block;
        font-size: 14px;
        font-weight: 600;
        color: #374151;
        margin-bottom: 8px;
    }
    .form-label .required {
        color: #ef4444;
        margin-left: 4px;
    }
    .form-control {
        width: 100%;
        padding: 10px 14px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        font-size: 14px;
        transition: border-color 0.2s;
    }
    .form-control:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(11, 61, 145, 0.1);
    }
    .form-control.error {
        border-color: #ef4444;
    }
    .form-control[readonly] {
        background: #f3f4f6;
        color: #374151;
        cursor: not-allowed;
    }
    .address-from-profile-note {
        font-size: 12px;
        color: #6b7280;
        margin-top: 6px;
    }
    .address-from-profile-note a {
        color: var(--primary);
        font-weight: 600;
    }
    .form-text {
        font-size: 12px;
        color: #6b7280;
        margin-top: 4px;
    }
    .form-row {
        display: grid;
        grid-template-columns: 1fr;
        gap: 20px;
    }
    @media (min-width: 768px) {
        .form-row {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    .radio-group {
        display: flex;
        gap: 24px;
        flex-wrap: wrap;
    }
    .radio-option {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .radio-option input[type="radio"] {
        width: 18px;
        height: 18px;
        cursor: pointer;
    }
    .radio-option label {
        font-size: 14px;
        color: #374151;
        cursor: pointer;
        margin: 0;
    }
    .checkbox-group {
        display: flex;
        align-items: flex-start;
        gap: 12px;
    }
    .checkbox-group input[type="checkbox"] {
        width: 18px;
        height: 18px;
        min-width: 18px;
        min-height: 18px;
        max-width: 18px;
        max-height: 18px;
        margin-top: 2px;
        cursor: pointer;
        flex-shrink: 0;
        box-sizing: border-box;
    }
    .checkbox-group label {
        font-size: 14px;
        color: #374151;
        cursor: pointer;
        margin: 0;
        line-height: 1.5;
    }
    .btn-primary {
        background: var(--primary);
        color: #fff;
        padding: 12px 32px;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        font-size: 16px;
        cursor: pointer;
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
    .error-message {
        color: #ef4444;
        font-size: 12px;
        margin-top: 4px;
    }
    .conditional-field {
        display: none;
    }
    .conditional-field.show {
        display: block;
    }
    /* Zaglavlje obrasca 1a/1b – grb, ustanova, broj prijave */
    .obrazac-zaglavlje {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 20px 24px;
        margin-bottom: 24px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.06);
    }
    .obrazac-zaglavlje-top {
        display: flex;
        align-items: flex-start;
        gap: 20px;
        margin-bottom: 16px;
    }
    .obrazac-grb {
        flex-shrink: 0;
        line-height: 0;
    }
    .obrazac-grb img {
        height: 2cm;
        width: auto;
        display: block;
    }
    .obrazac-org {
        flex: 1;
        font-size: 13px;
        line-height: 1.5;
        color: #111;
    }
    .obrazac-org p { margin: 0 0 2px 0; }
    .obrazac-contact {
        text-align: right;
        font-size: 13px;
        line-height: 1.5;
        color: #111;
    }
    .obrazac-contact p { margin: 0 0 2px 0; }
    .obrazac-broj-i-naslov {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
        margin-top: 12px;
        padding-top: 12px;
        border-top: 1px solid #e5e7eb;
    }
    .obrazac-broj-prijave {
        font-size: 14px;
        font-weight: 600;
        color: #111;
    }
    .obrazac-1a-1b {
        font-size: 14px;
        font-weight: 700;
        color: #374151;
    }
    .obrazac-naslov-prijava {
        width: 100%;
        text-align: center;
        font-size: 22px;
        font-weight: 700;
        letter-spacing: 0.02em;
        color: #111;
        margin: 8px 0 0 0;
    }
    .obrazac-podnaslov {
        width: 100%;
        text-align: center;
        font-size: 14px;
        line-height: 1.5;
        color: #374151;
        margin: 12px 0 0 0;
    }
    @media print {
        @page { margin: 12mm 10mm; }

        .no-print { display: none !important; }

        nav { display: none !important; }

        .application-form-page {
            background: #fff;
            padding: 0;
            min-height: 0;
        }
        .application-form-page .container {
            padding: 0;
            max-width: 100%;
        }

        .obrazac-zaglavlje {
            box-shadow: none;
            border: 1px solid #ccc;
            margin-bottom: 12px;
            padding: 12px 16px;
        }
        .obrazac-grb img { height: 2cm; }
        .obrazac-org,
        .obrazac-contact {
            font-size: 11.7px;
        }
        .obrazac-broj-prijave,
        .obrazac-1a-1b {
            font-size: 12.6px;
        }
        .obrazac-naslov-prijava {
            font-size: 19.8px;
        }
        .obrazac-podnaslov {
            font-size: 12.6px;
        }

        .form-card {
            padding: 0;
            box-shadow: none;
            margin-bottom: 8px;
            border-radius: 0;
        }

        .form-group {
            margin-bottom: 6px;
        }
        .form-label {
            font-size: 9.9pt;
            margin-bottom: 2px;
        }
        .form-control {
            font-size: 9pt;
            padding: 3px 5px;
            border-radius: 0;
        }
        .checkbox-group label,
        .radio-option label {
            font-size: 9pt;
        }
        .error-message,
        .required {
            display: none !important;
        }
        .form-control::placeholder {
            color: transparent;
        }

        #obrazac1a.show .form-section,
        #obrazac1b.show .form-section,
        #fizickoLiceFields.show .form-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 6px 16px;
            margin-bottom: 0;
        }

        #obrazac1a .form-row,
        #obrazac1b .form-row,
        #fizickoLiceFields .form-row {
            display: contents;
        }

        #obrazac1a .form-section > .form-group:has(select),
        #obrazac1b .form-section > .form-group:has(select),
        #fizickoLiceFields .form-section > .form-group:has(select),
        #obrazac1a .form-section > .form-group:has(.checkbox-group),
        #obrazac1b .form-section > .form-group:has(.checkbox-group),
        #fizickoLiceFields .form-section > .form-group:has(.checkbox-group),
        #obrazac1a .form-section > .form-group:has(.radio-group),
        #obrazac1b .form-section > .form-group:has(.radio-group),
        #fizickoLiceFields .form-section > .form-group:has(.radio-group),
        #obrazac1a .form-section > div[style*="background"],
        #obrazac1b .form-section > div[style*="background"] {
            grid-column: 1 / -1;
            font-size: 11.7px;
        }
    }
</style>

<div class="application-form-page">
    <div class="container mx-auto px-4">
        {{-- Zaglavlje obrasca 1a/1b: grb, ustanova, broj prijave, PRIJAVA --}}
        @php
            $upBroj = $competition->upNumber?->number ?? '—';
            $redniBroj = isset($existingApplication) && $existingApplication ? ($existingApplication->redni_broj ?? '—') : '—';
            $brojPrijave = $upBroj . '/' . $redniBroj;
            $applicantType = $lockedApplicantType ?? old('applicant_type', isset($existingApplication) && $existingApplication ? $existingApplication->applicant_type : null);
            $obrazacLabel = 'Obrazac 1a/1b';
            if ($applicantType === 'preduzetnica' || $applicantType === 'fizicko_lice') {
                $obrazacLabel = 'Obrazac 1a';
            } elseif ($applicantType === 'doo' || $applicantType === 'ostalo') {
                $obrazacLabel = 'Obrazac 1b';
            }
            $obrazacRegistracijaHeading = \App\Support\KnCommercialCompanyForm::obrazacRegistracijaHeading(
                $lockedCommercialForm ?? null,
                is_string($applicantType) ? $applicantType : null,
                $lockedRegistrationForm ?? ((isset($existingApplication) && $existingApplication) ? $existingApplication->registration_form : null)
            );
            $userProfileAddress = \App\Support\KotorAddress::formatStreetAndCity($subjectIdentity->address, $subjectIdentity->city);
        @endphp
        <div class="obrazac-zaglavlje">
            <div class="obrazac-zaglavlje-top">
                <div class="obrazac-grb">
                    <img src="{{ asset('images/srednji_grb.png') }}" alt="Grb Opštine Kotor" class="obrazac-grb-img" onerror="this.onerror=null; this.src='{{ asset('images/srednji_grb.svg') }}';" style="height: 2cm; width: auto; display: block;">
                </div>
                <div class="obrazac-org">
                    <p><strong>Crna Gora</strong></p>
                    <p>Opština Kotor</p>
                    <p>Sekretarijat za razvoj preduzetništva</p>
                    <p>komunalne poslove i saobraćaj</p>
                </div>
                <div class="obrazac-contact">
                    <p>Stari grad 317</p>
                    <p>85330 Kotor, Crna Gora</p>
                    <p>tel. +382(0)32 325 865</p>
                    <p>privreda@kotor.me</p>
                    <p>www.kotor.me</p>
                </div>
            </div>
            <div class="obrazac-broj-i-naslov">
                <div class="obrazac-broj-prijave">
                    Broj prijave: {{ $brojPrijave }}
                </div>
                <div class="obrazac-1a-1b" id="obrazacLabelHeader">{{ $obrazacLabel }}</div>
            </div>
            <h1 class="obrazac-naslov-prijava">PRIJAVA</h1>
            <p class="obrazac-podnaslov" id="obrazacPodnaslovHeader">
                na javni konkurs za raspodjelu bespovratnih sredstava<br>
                namijenjenih za podršku ženskom preduzetništvu<br>
                <span id="obrazacRegistracijaHeader">{{ $obrazacRegistracijaHeading }}</span>
            </p>
        </div>

        @if(session('success'))
            <div class="alert alert-info no-print">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger no-print" style="margin-bottom: 24px; padding: 16px; background: #fee2e2; border: 1px solid #f87171; border-radius: 8px; color: #991b1b;">
                {{ session('error') }}
            </div>
        @endif

        @if(session('warning'))
            <div class="alert alert-warning no-print" style="margin-bottom: 24px; padding: 16px; background: #fef3c7; border: 1px solid #fbbf24; border-radius: 8px; color: #92400e;">
                {{ session('warning') }}
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger no-print" style="margin-bottom: 24px; padding: 16px; background: #fee2e2; border: 1px solid #f87171; border-radius: 8px; color: #991b1b;">
                <strong>Ispravite sljedeće greške:</strong>
                <ul style="margin: 8px 0 0; padding-left: 20px;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif


        @php
            $readOnly = $readOnly ?? false;
        @endphp
        
        @if($readOnly)
            <div class="alert alert-info no-print" style="margin-bottom: 24px; padding: 16px; background: #fef3c7; border: 1px solid #fbbf24; border-radius: 8px; color: #92400e;">
                <strong>Pregled prijave:</strong> Ovo je pregled prijave, ne možete mijenjati podatke.
            </div>
        @endif

        <form method="POST" action="{{ $readOnly ? '#' : route('applications.store', $competition) }}" id="applicationForm" @if($readOnly) onsubmit="event.preventDefault(); return false;" @endif>
            @csrf
            @php
                $lockedBusinessStage = (isset($existingApplication) && $existingApplication && is_string($existingApplication->business_stage) && $existingApplication->business_stage !== '')
                    ? $existingApplication->business_stage
                    : ((isset($startContext) && $startContext)
                        ? $startContext->businessStage
                        : ($preselectedBusinessStage ?? null));
            @endphp
            @if(!empty($startContextToken))
                <input type="hidden" name="start_context_token" value="{{ $startContextToken }}">
            @endif
            @if(!empty($lockedApplicantType))
                <input type="hidden" name="applicant_type" id="kn_locked_applicant_type" value="{{ $lockedApplicantType }}">
            @endif
            @if(!empty($lockedRegistrationForm))
                <input type="hidden" name="registration_form" id="kn_locked_registration_form" value="{{ $lockedRegistrationForm }}">
            @endif
            @if(!$readOnly && !empty($lockedBusinessStage))
                <input type="hidden" name="business_stage" id="kn_locked_business_stage" value="{{ $lockedBusinessStage }}" data-kn-locked="1">
            @endif
            @if(isset($existingApplication) && $existingApplication && !$readOnly)
                <input type="hidden" name="application_id" value="{{ $existingApplication->id }}">
            @endif
            
            @php
                // Helper funkcija za dobijanje vrednosti polja (old > existingApplication > default)
                if (! function_exists('getFieldValue')) {
                    function getFieldValue($field, $default = '') {
                        $oldValue = old($field);
                        if ($oldValue !== null) {
                            return $oldValue;
                        }
                        if (isset($existingApplication) && $existingApplication && $existingApplication->$field) {
                            return $existingApplication->$field;
                        }
                        return $default;
                    }
                }
            @endphp
            
            @if(isset($existingApplication) && $existingApplication && !$readOnly)
                <div class="alert alert-info no-print" style="margin-bottom: 24px; padding: 16px; background: #dbeafe; border: 1px solid #93c5fd; border-radius: 8px; color: #1e40af;">
                    <strong>Nastavak popunjavanja:</strong> Već imate započetu prijavu. Možete je nastaviti popunjavati.
                </div>
            @endif

            <!-- Tip podnosioca prijave (prikazuje se prije obrazaca) -->
            <div class="form-card no-print">
                <div class="form-section">
                    <div class="form-group">
                        <label class="form-label">
                            Tip podnosioca prijave <span class="required">*</span>
                        </label>
                        @php
                            $userType = $subjectIdentity->userType ?? '';
                            $residentialStatus = $subjectIdentity->residentialStatus ?? '';
                            $isFizickoLiceRezident = \App\Support\ApplicationCreateApplicantTypeDefault::isFizickoLiceRezident($userType, $residentialStatus);
                            $preferredApplicantType = $lockedApplicantType ?? $preferredApplicantType ?? null;
                            $knClassification = \App\Support\KnApplicationClassification::fromUserType($userType);
                            $knIsRegistered = $knClassification->isRegisteredBusiness;
                            $lockedIsRegistered = (isset($existingApplication) && $existingApplication)
                                ? (bool) $existingApplication->is_registered
                                : (isset($startContext) && $startContext
                                    ? (bool) $startContext->isRegistered
                                    : (bool) $knIsRegistered);
                            $knAllowsRazvoj = $knClassification->allowsStage('razvoj');
                            $knAllowsFizickoLice = $knClassification->allowsApplicantType('fizicko_lice');
                            $defaultType = \App\Support\ApplicationCreateApplicantTypeDefault::forUser(
                                $userType,
                                $residentialStatus,
                                $preferredApplicantType
                            );
                            $selectedApplicantType = $lockedApplicantType ?? old('applicant_type', (isset($existingApplication) && $existingApplication ? $existingApplication->applicant_type : null) ?? $defaultType);
                            $lockedCommercialForm = $lockedCommercialForm ?? null;
                            $lockedRegistrationForm = $lockedRegistrationForm ?? null;
                            $isCommercialCompanyPath = \App\Support\KnCommercialCompanyForm::isValid($lockedCommercialForm)
                                || $selectedApplicantType === 'doo';
                            $showOstaloRadio = false;
                        @endphp
                        <div class="form-text" style="margin-bottom: 12px; color: #6b7280; font-size: 13px;">
                            <strong>Napomena:</strong> Tip prijave i planirani pravni oblik biraju se na stranici konkursa i zaključani su za ovu prijavu. Kanonski identitet se ne mijenja. Postojeća Preduzetnica i postojeće privredno društvo (DOO / AD / OD / KD) imaju zaključan oblik.
                        </div>
                        <div class="radio-group">
                            <div class="radio-option">
                                <input 
                                    type="radio" 
                                    id="applicant_type_fizicko_lice" 
                                    name="applicant_type_display"
                                    value="fizicko_lice"
                                    {{ $selectedApplicantType === 'fizicko_lice' ? 'checked' : '' }}
                                    disabled
                                    data-kn-locked="1"
                                >
                                <label for="applicant_type_fizicko_lice">{{ $knAllowsFizickoLice ? 'Planiram registraciju kao preduzetnik' : 'Fizičko lice (nema registrovanu djelatnost)' }}</label>
                            </div>
                            <div class="radio-option">
                                <input 
                                    type="radio" 
                                    id="applicant_type_preduzetnica" 
                                    name="applicant_type_display"
                                    value="preduzetnica"
                                    {{ $selectedApplicantType === 'preduzetnica' ? 'checked' : '' }}
                                    disabled
                                    data-kn-locked="1"
                                >
                                <label for="applicant_type_preduzetnica">Preduzetnica</label>
                            </div>
                            <div class="radio-option">
                                <input 
                                    type="radio" 
                                    id="applicant_type_doo" 
                                    name="applicant_type_display"
                                    value="doo"
                                    {{ $isCommercialCompanyPath ? 'checked' : '' }}
                                    disabled
                                    data-kn-locked="1"
                                >
                                <label for="applicant_type_doo">{{ !empty($knIsRegistered) ? 'Privredno društvo' : 'Planiram osnivanje privrednog društva' }}</label>
                            </div>
                        </div>
                        @error('applicant_type')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Napomena za Fizičko lice (nema registrovanu djelatnost) - prikazuje se kada je izabrano -->
            <div class="alert alert-info conditional-field no-print" id="fizickoLiceNotice" style="display: {{ !empty($knIsRegistered) ? 'none' : 'block' }}; margin-bottom: 24px;">
                <strong>Važno:</strong> Ukoliko podnositeljka biznis plana nema registrovanu djelatnost, u slučaju da joj sredstva budu odobrena u obavezi je da svoju djelatnost registruje u neki od oblika registracije koji predviđa Zakon o privrednim društvima i priloži dokaz (rješenje o registraciji u CRPS i rješenje o registraciji PJ Uprave prihoda i carina), najkasnije do dana potpisivanja ugovora.
            </div>

            <!-- Izbor tipa prijave za Fizičko lice (Rezident) -->
            @php
                $userType = $subjectIdentity->userType ?? '';
                $isFizickoLiceRezident = ($userType === 'Fizičko lice' || $userType === 'Rezident');
            @endphp

            <!-- Obrazac 1a: Za Preduzetnice (PREDUZETNIK) -->
            <div class="form-card conditional-field" id="obrazac1a">
                <div class="form-section">
                    <div class="form-group">
                        <label class="form-label">
                            Naziv biznis plana <span class="required">*</span>
                        </label>
                        <input 
                            type="text" 
                            name="business_plan_name" 
                            class="form-control @error('business_plan_name') error @enderror"
                            value="{{ old('business_plan_name', isset($existingApplication) && $existingApplication ? $existingApplication->business_plan_name : '') }}"
                            required
                            maxlength="255"
                        >
                        @error('business_plan_name')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">Ime i prezime:</label>
                        <input 
                            type="text" 
                            name="preduzetnik_name" 
                            class="form-control @error('preduzetnik_name') error @enderror"
                            value="{{ old('preduzetnik_name', isset($existingApplication) && $existingApplication && $existingApplication->user ? $existingApplication->user->name : auth()->user()->name) }}"
                            maxlength="255"
                        >
                        @error('preduzetnik_name')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">JMBG: <span class="required">*</span></label>
                            <input 
                                type="text" 
                                name="preduzetnik_jmbg" 
                                class="form-control @error('preduzetnik_jmbg') error @enderror @error('applicant_jmbg') error @enderror"
                                value="{{ old('preduzetnik_jmbg', (isset($existingApplication) && $existingApplication ? $existingApplication->applicant_jmbg : null) ?? $subjectIdentity->jmb) }}"
                                maxlength="13"
                                pattern="[0-9]{13}"
                                placeholder="13 cifara"
                                inputmode="numeric"
                            >
                            @error('preduzetnik_jmbg')
                                <div class="error-message">{{ $message }}</div>
                            @enderror
                            @error('applicant_jmbg')
                                <div class="error-message">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label">Kontakt telefon:</label>
                            <input 
                                type="tel" 
                                name="preduzetnik_phone" 
                                class="form-control @error('preduzetnik_phone') error @enderror"
                                value="{{ old('preduzetnik_phone', $subjectIdentity->phone) }}"
                                maxlength="50"
                                placeholder="Npr. +382 67 123 456"
                            >
                            @error('preduzetnik_phone')
                                <div class="error-message">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Adresa:</label>
                            <input
                                type="text"
                                class="form-control @error('preduzetnik_address') error @enderror"
                                value="{{ old('preduzetnik_address', $userProfileAddress) }}"
                                maxlength="500"
                                readonly
                                tabindex="-1"
                            >
                            <input type="hidden" name="preduzetnik_address" value="{{ old('preduzetnik_address', $userProfileAddress) }}">
                            <p class="address-from-profile-note">
                                Adresa se povlači iz vašeg profila (ulica i grad iz registracije).
                                <a href="{{ route('profile.edit') }}">Izmijeni u profilu</a>
                            </p>
                            @error('preduzetnik_address')
                                <div class="error-message">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label">E-mail:</label>
                            <input 
                                type="email" 
                                name="preduzetnik_email" 
                                class="form-control @error('preduzetnik_email') error @enderror"
                                value="{{ old('preduzetnik_email', isset($existingApplication) && $existingApplication && $existingApplication->user ? $existingApplication->user->email : auth()->user()->email) }}"
                                maxlength="255"
                            >
                            @error('preduzetnik_email')
                                <div class="error-message">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            Oblik registracije @if(!empty($knIsRegistered))<span class="required">*</span>@endif
                        </label>
                        <select 
                            name="registration_form_display"
                            id="registration_form_1a"
                            class="form-control @error('registration_form') error @enderror"
                            disabled
                            data-kn-locked="1"
                        >
                            <option value="">Izaberite oblik registracije</option>
                            @php
                                // Automatski postavi na osnovu tipa prijave ili user_type iz registracije
                                $defaultRegistrationForm = old('registration_form', '');
                                if (empty($defaultRegistrationForm) && !empty($lockedRegistrationForm)) {
                                    $defaultRegistrationForm = $lockedRegistrationForm;
                                }
                                if (empty($defaultRegistrationForm) && isset($existingApplication) && $existingApplication && $existingApplication->registration_form) {
                                    $defaultRegistrationForm = $existingApplication->registration_form;
                                }
                                $userType = $subjectIdentity->userType ?? '';
                                $defaultApplicantType = old('applicant_type', (isset($existingApplication) && $existingApplication ? $existingApplication->applicant_type : null) ?? $defaultType ?? '');
                                
                                // Ako nema old value, koristi user_type ako postoji i nije "Fizičko lice"
                                if (empty($defaultRegistrationForm) && $userType && $userType !== 'Fizičko lice') {
                                    $defaultRegistrationForm = $userType;
                                }
                                
                                // Ako i dalje nema vrednost, postavi na osnovu tipa prijave
                                if (empty($defaultRegistrationForm)) {
                                    if ($defaultApplicantType === 'preduzetnica') {
                                        $defaultRegistrationForm = 'Preduzetnik';
                                    } elseif ($defaultApplicantType === 'doo') {
                                        $defaultRegistrationForm = 'Društvo sa ograničenom odgovornošću';
                                    } else {
                                        // Podrazumevano za obrazac 1a
                                        $defaultRegistrationForm = 'Preduzetnik';
                                    }
                                }
                            @endphp
                            <option value="Preduzetnik" {{ $defaultRegistrationForm === 'Preduzetnik' ? 'selected' : '' }}>Preduzetnik</option>
                            <option value="Ortačko društvo" {{ $defaultRegistrationForm === 'Ortačko društvo' ? 'selected' : '' }}>Ortačko društvo</option>
                            <option value="Komanditno društvo" {{ $defaultRegistrationForm === 'Komanditno društvo' ? 'selected' : '' }}>Komanditno društvo</option>
                            <option value="Društvo sa ograničenom odgovornošću" {{ $defaultRegistrationForm === 'Društvo sa ograničenom odgovornošću' ? 'selected' : '' }}>Društvo sa ograničenom odgovornošću</option>
                            <option value="Akcionarsko društvo" {{ $defaultRegistrationForm === 'Akcionarsko društvo' ? 'selected' : '' }}>Akcionarsko društvo</option>
                            <option value="Dio stranog društva (predstavništvo ili poslovna jedinica)" {{ $defaultRegistrationForm === 'Dio stranog društva (predstavništvo ili poslovna jedinica)' ? 'selected' : '' }}>Dio stranog društva (predstavništvo ili poslovna jedinica)</option>
                            <option value="Udruženje (nvo, fondacije, sportske organizacije)" {{ $defaultRegistrationForm === 'Udruženje (nvo, fondacije, sportske organizacije)' ? 'selected' : '' }}>Udruženje (nvo, fondacije, sportske organizacije)</option>
                            <option value="Ustanova (državne i privatne)" {{ $defaultRegistrationForm === 'Ustanova (državne i privatne)' ? 'selected' : '' }}>Ustanova (državne i privatne)</option>
                            <option value="Druge organizacije (Političke partije, Vjerske zajednice, Komore, Sindikati)" {{ $defaultRegistrationForm === 'Druge organizacije (Političke partije, Vjerske zajednice, Komore, Sindikati)' ? 'selected' : '' }}>Druge organizacije (Političke partije, Vjerske zajednice, Komore, Sindikati)</option>
                        </select>
                        @error('registration_form')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">*Broj registracije u CRPS:</label>
                            <input 
                                type="text" 
                                name="crps_number" 
                                class="form-control @error('crps_number') error @enderror"
                                value="{{ old('crps_number', isset($existingApplication) && $existingApplication ? $existingApplication->crps_number : '') }}"
                                maxlength="50"
                            >
                            @error('crps_number')
                                <div class="error-message">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label">*PIB:</label>
                            <input 
                                type="text" 
                                name="pib" 
                                class="form-control @error('pib') error @enderror"
                                value="{{ old('pib', isset($existingApplication) && $existingApplication ? $existingApplication->pib : $subjectIdentity->pib) }}"
                                maxlength="8"
                                pattern="[0-9]{8}"
                                placeholder="8 cifara"
                            >
                            @error('pib')
                                <div class="error-message">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div style="margin: 16px 0; padding: 12px; background: #f3f4f6; border-radius: 8px; font-size: 13px;">
                        <p style="margin: 4px 0;"><strong>*</strong> Popunjavate samo ako imate registrovan biznis.</p>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            Oblast u kojoj planirate realizaciju biznis plana: <span class="required">*</span>
                        </label>
                        <input 
                            type="text" 
                            name="business_area" 
                            class="form-control @error('business_area') error @enderror"
                            value="{{ old('business_area', isset($existingApplication) && $existingApplication ? $existingApplication->business_area : '') }}"
                            required
                            maxlength="255"
                            placeholder="Npr. IT usluge, turizam, poljoprivreda..."
                        >
                        @error('business_area')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group" style="margin-top: 24px;">
                        <div class="checkbox-group">
                            <input 
                                type="checkbox" 
                                id="accuracy_declaration_1a" 
                                name="accuracy_declaration" 
                                value="1"
                                {{ old('accuracy_declaration', isset($existingApplication) && $existingApplication ? $existingApplication->accuracy_declaration : false) ? 'checked' : '' }}
                                required
                            >
                            <label for="accuracy_declaration_1a">
                                Kao podnositeljka prijave pod punom materijalnom i krivičnom odgovornošću izjavljujem da su gore navedeni podaci istiniti.
                                <span class="required">*</span>
                            </label>
                        </div>
                        @error('accuracy_declaration')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Obrazac 1b: Za DOO i Ostalo -->
            <div class="form-card conditional-field" id="obrazac1b">
                <div class="form-section">
                    <div class="form-group">
                        <label class="form-label">
                            Naziv biznis plana <span class="required">*</span>
                        </label>
                        <input 
                            type="text" 
                            name="business_plan_name" 
                            class="form-control @error('business_plan_name') error @enderror"
                            value="{{ old('business_plan_name', isset($existingApplication) && $existingApplication ? $existingApplication->business_plan_name : '') }}"
                            required
                            maxlength="255"
                        >
                        @error('business_plan_name')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">Ime i prezime nositeljke biznisa:</label>
                        <input 
                            type="text" 
                            name="doo_name" 
                            class="form-control @error('doo_name') error @enderror"
                            value="{{ old('doo_name', isset($existingApplication) && $existingApplication && $existingApplication->user ? $existingApplication->user->name : auth()->user()->name) }}"
                            maxlength="255"
                        >
                        @error('doo_name')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">JMBG: <span class="required">*</span></label>
                            <input 
                                type="text" 
                                name="doo_jmbg" 
                                class="form-control @error('doo_jmbg') error @enderror @error('applicant_jmbg') error @enderror"
                                value="{{ old('doo_jmbg', (isset($existingApplication) && $existingApplication ? $existingApplication->applicant_jmbg : null) ?? $subjectIdentity->jmb) }}"
                                maxlength="13"
                                pattern="[0-9]{13}"
                                placeholder="13 cifara"
                                inputmode="numeric"
                            >
                            @error('doo_jmbg')
                                <div class="error-message">{{ $message }}</div>
                            @enderror
                            @error('applicant_jmbg')
                                <div class="error-message">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label">Kontakt telefon:</label>
                            <input 
                                type="tel" 
                                name="doo_phone" 
                                class="form-control @error('doo_phone') error @enderror"
                                value="{{ old('doo_phone', $subjectIdentity->phone) }}"
                                maxlength="50"
                                placeholder="Npr. +382 67 123 456"
                            >
                            @error('doo_phone')
                                <div class="error-message">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Adresa:</label>
                            <input
                                type="text"
                                class="form-control @error('doo_address') error @enderror"
                                value="{{ old('doo_address', $userProfileAddress) }}"
                                maxlength="500"
                                readonly
                                tabindex="-1"
                            >
                            <input type="hidden" name="doo_address" value="{{ old('doo_address', $userProfileAddress) }}">
                            <p class="address-from-profile-note">
                                Adresa se povlači iz vašeg profila (ulica i grad iz registracije).
                                <a href="{{ route('profile.edit') }}">Izmijeni u profilu</a>
                            </p>
                            @error('doo_address')
                                <div class="error-message">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label">E-mail:</label>
                            <input 
                                type="email" 
                                name="doo_email" 
                                class="form-control @error('doo_email') error @enderror"
                                value="{{ old('doo_email', isset($existingApplication) && $existingApplication && $existingApplication->user ? $existingApplication->user->email : auth()->user()->email) }}"
                                maxlength="255"
                            >
                            @error('doo_email')
                                <div class="error-message">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            Oblik registracije @if(!empty($knIsRegistered))<span class="required">*</span>@endif
                        </label>
                        <select 
                            name="registration_form_display"
                            id="registration_form_1b"
                            class="form-control @error('registration_form') error @enderror"
                            disabled
                            data-kn-locked="1"
                        >
                            <option value="">Izaberite oblik registracije</option>
                            @php
                                $defaultRegistrationForm1b = old('registration_form', '');
                                if (empty($defaultRegistrationForm1b) && !empty($lockedRegistrationForm)) {
                                    $defaultRegistrationForm1b = $lockedRegistrationForm;
                                }
                                if (empty($defaultRegistrationForm1b) && isset($existingApplication) && $existingApplication && $existingApplication->registration_form) {
                                    $defaultRegistrationForm1b = $existingApplication->registration_form;
                                }
                                $userType = $subjectIdentity->userType ?? '';
                                $defaultApplicantType = $lockedApplicantType ?? old('applicant_type', $defaultType ?? '');
                                
                                if (empty($defaultRegistrationForm1b) && $userType && $userType !== 'Fizičko lice') {
                                    $mappedCommercial = \App\Support\KnCommercialCompanyForm::fromUserType($userType);
                                    $defaultRegistrationForm1b = $mappedCommercial
                                        ? \App\Support\KnCommercialCompanyForm::registrationFormLabel($mappedCommercial)
                                        : $userType;
                                }
                                
                                if (empty($defaultRegistrationForm1b)) {
                                    if ($defaultApplicantType === 'doo') {
                                        $defaultRegistrationForm1b = 'Društvo sa ograničenom odgovornošću';
                                    } else {
                                        $defaultRegistrationForm1b = '';
                                    }
                                }
                                $defaultRegistrationForm = $defaultRegistrationForm1b;
                            @endphp
                            <option value="Preduzetnik" {{ $defaultRegistrationForm1b === 'Preduzetnik' ? 'selected' : '' }}>Preduzetnik</option>
                            <option value="Ortačko društvo" {{ $defaultRegistrationForm === 'Ortačko društvo' ? 'selected' : '' }}>Ortačko društvo</option>
                            <option value="Komanditno društvo" {{ $defaultRegistrationForm === 'Komanditno društvo' ? 'selected' : '' }}>Komanditno društvo</option>
                            <option value="Društvo sa ograničenom odgovornošću" {{ $defaultRegistrationForm === 'Društvo sa ograničenom odgovornošću' ? 'selected' : '' }}>Društvo sa ograničenom odgovornošću</option>
                            <option value="Akcionarsko društvo" {{ $defaultRegistrationForm === 'Akcionarsko društvo' ? 'selected' : '' }}>Akcionarsko društvo</option>
                            <option value="Dio stranog društva (predstavništvo ili poslovna jedinica)" {{ $defaultRegistrationForm === 'Dio stranog društva (predstavništvo ili poslovna jedinica)' ? 'selected' : '' }}>Dio stranog društva (predstavništvo ili poslovna jedinica)</option>
                            <option value="Udruženje (nvo, fondacije, sportske organizacije)" {{ $defaultRegistrationForm === 'Udruženje (nvo, fondacije, sportske organizacije)' ? 'selected' : '' }}>Udruženje (nvo, fondacije, sportske organizacije)</option>
                            <option value="Ustanova (državne i privatne)" {{ $defaultRegistrationForm === 'Ustanova (državne i privatne)' ? 'selected' : '' }}>Ustanova (državne i privatne)</option>
                            <option value="Druge organizacije (Političke partije, Vjerske zajednice, Komore, Sindikati)" {{ $defaultRegistrationForm === 'Druge organizacije (Političke partije, Vjerske zajednice, Komore, Sindikati)' ? 'selected' : '' }}>Druge organizacije (Političke partije, Vjerske zajednice, Komore, Sindikati)</option>
                        </select>
                        @error('registration_form')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                    </div>

                    @if(!empty($lockedIsRegistered))
                    <div class="form-group">
                        <label class="form-label">*Broj registracije u CRPS:</label>
                        <input 
                            type="text" 
                            name="crps_number" 
                            class="form-control @error('crps_number') error @enderror"
                            value="{{ old('crps_number', isset($existingApplication) && $existingApplication ? $existingApplication->crps_number : '') }}"
                            maxlength="50"
                        >
                        @error('crps_number')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">*Osnivač/ica:</label>
                            <input 
                                type="text" 
                                name="founder_name" 
                                class="form-control @error('founder_name') error @enderror"
                                value="{{ old('founder_name', isset($existingApplication) && $existingApplication ? $existingApplication->founder_name : auth()->user()->name) }}"
                                maxlength="255"
                                required
                            >
                            @error('founder_name')
                                <div class="error-message">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label">*Izvršni direktor/ica:</label>
                            <input 
                                type="text" 
                                name="director_name" 
                                class="form-control @error('director_name') error @enderror"
                                value="{{ old('director_name', isset($existingApplication) && $existingApplication ? $existingApplication->director_name : auth()->user()->name) }}"
                                maxlength="255"
                                required
                            >
                            @error('director_name')
                                <div class="error-message">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">*Sjedište društva:</label>
                        <input
                            type="text"
                            class="form-control @error('company_seat') error @enderror"
                            value="{{ old('company_seat', $userProfileAddress) }}"
                            maxlength="500"
                            readonly
                            tabindex="-1"
                        >
                        <input type="hidden" name="company_seat" value="{{ old('company_seat', $userProfileAddress) }}">
                        <p class="address-from-profile-note">
                            Sjedište se povlači iz vašeg profila (ulica i grad iz registracije).
                            <a href="{{ route('profile.edit') }}">Izmijeni u profilu</a>
                        </p>
                        @error('company_seat')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">*PIB:</label>
                        <input 
                            type="text" 
                            name="pib" 
                            class="form-control @error('pib') error @enderror"
                            value="{{ old('pib', isset($existingApplication) && $existingApplication ? $existingApplication->pib : $subjectIdentity->pib) }}"
                            maxlength="8"
                            pattern="[0-9]{8}"
                            placeholder="8 cifara"
                        >
                        @error('pib')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                    </div>

                    <div style="margin: 16px 0; padding: 12px; background: #f3f4f6; border-radius: 8px; font-size: 13px;">
                        <p style="margin: 4px 0;"><strong>*</strong> Popunjavate samo ako imate registrovan biznis.</p>
                    </div>
                    @endif

                    <div class="form-group">
                        <label class="form-label">
                            Oblast u kojoj planirate realizaciju biznis plana: <span class="required">*</span>
                        </label>
                        <input 
                            type="text" 
                            name="business_area" 
                            class="form-control @error('business_area') error @enderror"
                            value="{{ old('business_area', isset($existingApplication) && $existingApplication ? $existingApplication->business_area : '') }}"
                            required
                            maxlength="255"
                            placeholder="Npr. IT usluge, turizam, poljoprivreda..."
                        >
                        @error('business_area')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group" style="margin-top: 24px;">
                        <div class="checkbox-group">
                            <input 
                                type="checkbox" 
                                id="accuracy_declaration_1b" 
                                name="accuracy_declaration" 
                                value="1"
                                {{ old('accuracy_declaration', isset($existingApplication) && $existingApplication ? $existingApplication->accuracy_declaration : false) ? 'checked' : '' }}
                                required
                            >
                            <label for="accuracy_declaration_1b">
                                Kao podnositeljka prijave pod punom materijalnom i krivičnom odgovornošću izjavljujem da su gore navedeni podaci istiniti.
                                <span class="required">*</span>
                            </label>
                        </div>
                        @error('accuracy_declaration')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Sekcija za Fizičko lice (nema registrovanu djelatnost) -->
            <div class="form-card conditional-field" id="fizickoLiceFields">
                <div class="form-section">
                    <div class="form-group">
                        <label class="form-label">
                            Naziv biznis plana <span class="required">*</span>
                        </label>
                        <input 
                            type="text" 
                            name="business_plan_name" 
                            class="form-control @error('business_plan_name') error @enderror"
                            value="{{ old('business_plan_name', isset($existingApplication) && $existingApplication ? $existingApplication->business_plan_name : '') }}"
                            required
                            maxlength="255"
                        >
                        @error('business_plan_name')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            Ime i prezime <span class="required">*</span>
                        </label>
                        <input 
                            type="text" 
                            name="physical_person_name" 
                            class="form-control @error('physical_person_name') error @enderror"
                            value="{{ old('physical_person_name', isset($existingApplication) && $existingApplication ? $existingApplication->physical_person_name : auth()->user()->name) }}"
                            maxlength="255"
                        >
                        @error('physical_person_name')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            JMBG <span class="required">*</span>
                        </label>
                        <input 
                            type="text" 
                            name="physical_person_jmbg" 
                            class="form-control @error('physical_person_jmbg') error @enderror"
                                value="{{ old('physical_person_jmbg', (isset($existingApplication) && $existingApplication && filled($existingApplication->physical_person_jmbg) ? $existingApplication->physical_person_jmbg : null) ?? $subjectIdentity->jmb) }}"
                            maxlength="13"
                            pattern="[0-9]{13}"
                            placeholder="13 cifara"
                        >
                        @error('physical_person_jmbg')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">
                                Kontakt telefon <span class="required">*</span>
                            </label>
                            <input 
                                type="tel" 
                                name="physical_person_phone" 
                                class="form-control @error('physical_person_phone') error @enderror"
                                value="{{ old('physical_person_phone', isset($existingApplication) && $existingApplication ? $existingApplication->physical_person_phone : $subjectIdentity->phone) }}"
                                maxlength="50"
                                placeholder="Npr. +382 67 123 456"
                            >
                            @error('physical_person_phone')
                                <div class="error-message">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                E-mail <span class="required">*</span>
                            </label>
                            <input 
                                type="email" 
                                name="physical_person_email" 
                                class="form-control @error('physical_person_email') error @enderror"
                                value="{{ old('physical_person_email', isset($existingApplication) && $existingApplication ? $existingApplication->physical_person_email : auth()->user()->email) }}"
                                maxlength="255"
                            >
                            @error('physical_person_email')
                                <div class="error-message">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="checkbox-group">
                            <input 
                                type="checkbox" 
                                id="accuracy_declaration_fizicko" 
                                name="accuracy_declaration" 
                                value="1"
                                {{ old('accuracy_declaration', isset($existingApplication) && $existingApplication ? $existingApplication->accuracy_declaration : false) ? 'checked' : '' }}
                                required
                            >
                            <label for="accuracy_declaration_fizicko">
                                Kao podnositeljka prijave pod punom materijalnom i krivičnom odgovornošću izjavljujem da su gore navedeni podaci istiniti.
                                <span class="required">*</span>
                            </label>
                        </div>
                        @error('accuracy_declaration')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            Oblast u kojoj planirate realizaciju biznis plana: <span class="required">*</span>
                        </label>
                        <input 
                            type="text" 
                            name="business_area" 
                            class="form-control @error('business_area') error @enderror"
                            value="{{ old('business_area', isset($existingApplication) && $existingApplication ? $existingApplication->business_area : '') }}"
                            required
                            maxlength="255"
                            placeholder="Npr. IT usluge, turizam, poljoprivreda..."
                        >
                        @error('business_area')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Sekcija 3: Dodatni podaci — samo za registrovanu prijavu (is_registered = DA) -->
            @if(!empty($lockedIsRegistered))
            <div class="form-card no-print" id="additional-data-section">
                <div class="form-section">
                    <h2>Dodatni podaci</h2>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Broj računa (opciono)</label>
                            <input 
                                type="text" 
                                name="bank_account" 
                                class="form-control @error('bank_account') error @enderror"
                                value="{{ old('bank_account', isset($existingApplication) && $existingApplication ? $existingApplication->bank_account : '') }}"
                                maxlength="50"
                                placeholder="Npr. 510-0000000000123-45"
                            >
                            @error('bank_account')
                                <div class="error-message">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label">PDV broj (opciono)</label>
                            <input 
                                type="text" 
                                name="vat_number" 
                                class="form-control @error('vat_number') error @enderror"
                                value="{{ old('vat_number', isset($existingApplication) && $existingApplication ? $existingApplication->vat_number : '') }}"
                                maxlength="50"
                                placeholder="Npr. ME123456789"
                            >
                            @error('vat_number')
                                <div class="error-message">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Website (opciono)</label>
                        <input 
                            type="url" 
                            name="website" 
                            class="form-control @error('website') error @enderror"
                            value="{{ old('website', isset($existingApplication) && $existingApplication ? $existingApplication->website : '') }}"
                            maxlength="255"
                            placeholder="https://example.com"
                        >
                        @error('website')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
            @endif

            <!-- Dugme za slanje -->
            @if(!$readOnly)
                <div class="form-card no-print" style="text-align: center;">
                    <div style="display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;">
                        <button type="button" id="saveAsDraftBtn" class="btn-secondary" style="background: #6b7280; color: #fff; padding: 12px 24px; border: none; border-radius: 8px; font-weight: 600; font-size: 14px; cursor: pointer;">
                            Sačuvaj kao nacrt
                        </button>
                        <button type="submit" class="btn-primary" id="submitBtn" style="display: none;">
                            Sačuvaj i nastavi na biznis plan
                        </button>
                    </div>
                </div>
            @endif
        </form>

        @php
            // Dugme "Štampa" za Obrazac 1a/1b
            // Vidljivo je:
            // - kandidatu koji popunjava obrazac (readOnly = false)
            // - predsjedniku komisije koji gleda obrazac u read-only modu za odgovarajući konkurs
            $canPrintObrazac = false;
            $currentUser = $user ?? auth()->user();

            if (!($readOnly ?? false)) {
                // Kandidat koji popunjava obrazac
                $canPrintObrazac = true;
            } else {
                // Read-only prikaz – provjeri da li je korisnik predsjednik komisije za ovaj konkurs
                if ($currentUser && isset($existingApplication) && $existingApplication && $existingApplication->competition) {
                    $competitionId = $existingApplication->competition->commission_id;
                    $commissionMember = $competitionId
                        ? \App\Models\CommissionMember::activeForCommission($currentUser->id, $competitionId)
                        : null;

                    if ($commissionMember && $commissionMember->position === 'predsjednik') {
                        $canPrintObrazac = true;
                    }
                }
            }
        @endphp

        @if($canPrintObrazac)
            <div class="form-card no-print" style="text-align: center; margin-top: 8px; margin-bottom: 24px;">
                <button type="button" class="btn-primary" onclick="window.print();" style="padding: 10px 24px; font-size: 14px; min-width: 180px;">
                    Štampaj
                </button>
            </div>
        @endif
    </div>
</div>

<script>
    // Read-only mod - onemogući sva polja ako je readOnly = true
    @if($readOnly ?? false)
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('applicationForm');
            if (form) {
                // Onemogući sva polja u formi (readonly za input/textarea, disabled za select i button)
                const allFields = form.querySelectorAll('input, select, textarea');
                allFields.forEach(field => {
                    if (field.type !== 'hidden' && field.type !== 'submit') {
                        if (field.tagName === 'SELECT' || field.type === 'checkbox' || field.type === 'radio') {
                            field.setAttribute('disabled', 'disabled');
                        } else {
                            field.setAttribute('readonly', 'readonly');
                        }
                        field.style.cursor = 'not-allowed';
                        field.style.backgroundColor = '#f9fafb';
                    }
                });
                
                // Sakrij sva dugmad
                const buttons = form.querySelectorAll('button');
                buttons.forEach(button => {
                    button.style.display = 'none';
                });
            }
        });
    @endif

    // Dinamičko prikazivanje/sakrivanje polja na osnovu tipa podnosioca
    // VAŽNO: 'fizicko_lice' = Fizičko lice BEZ registrovane djelatnosti (automatski is_registered = false)
    //        'preduzetnica' = Fizičko lice SA registrovanom djelatnošću (automatski is_registered = true)
    //        'doo' = Društvo sa ograničenom odgovornošću (automatski is_registered = true)
    //        'ostalo' = Ostali pravni subjekti (automatski is_registered = true)
    @php
        $userType = $subjectIdentity->userType ?? '';
        $isFizickoLiceRezident = ($userType === 'Fizičko lice' || $userType === 'Rezident');
    @endphp
</script>
<script>
    const isFizickoLiceRezident = @json($isFizickoLiceRezident);
    const knIsRegistered = @json((bool) ($knIsRegistered ?? false));
    const knLockedIsRegistered = @json((bool) ($lockedIsRegistered ?? false));
    const knAllowsRazvoj = @json((bool) ($knAllowsRazvoj ?? false));
    const knLockedCommercialForm = @json($lockedCommercialForm ?? null);
    const knLockedBusinessStage = @json($lockedBusinessStage ?? null);
    const knLockedRegistrationForm = @json($lockedRegistrationForm ?? null);
</script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('applicationForm');
        const applicantTypeInputs = document.querySelectorAll('input[name="applicant_type"], input[name="applicant_type_display"]');
        const obrazac1a = document.getElementById('obrazac1a');
        const obrazac1b = document.getElementById('obrazac1b');
        const fizickoLiceFields = document.getElementById('fizickoLiceFields');
        const fizickoLiceRequiredFields = fizickoLiceFields ? fizickoLiceFields.querySelectorAll('input[required], input[name="physical_person_name"], input[name="physical_person_jmbg"], input[name="physical_person_phone"], input[name="physical_person_email"], input[name="accuracy_declaration"]') : [];
        const fizickoLiceNotice = document.getElementById('fizickoLiceNotice');
        const additionalDataSection = document.getElementById('additional-data-section');
        const savedBusinessStageValue = @json(old('business_stage', (isset($existingApplication) && $existingApplication ? $existingApplication->business_stage : null) ?? ($preselectedBusinessStage ?? null)));

        function currentApplicantType() {
            const locked = document.getElementById('kn_locked_applicant_type');
            if (locked && locked.value) {
                return locked.value;
            }
            return document.querySelector('input[name="applicant_type_display"]:checked')?.value
                || document.querySelector('input[name="applicant_type"]:checked')?.value;
        }

        function syncBusinessStageRadiosInSection(section, stage) {
            if (!section || !stage) {
                return;
            }

            section.querySelectorAll('input[name="business_stage"][type="radio"]').forEach((radio) => {
                radio.checked = radio.value === stage;
            });
        }

        function prepareBusinessStageForSubmit(form) {
            const value = (typeof knLockedBusinessStage === 'string' && knLockedBusinessStage !== '')
                ? knLockedBusinessStage
                : (document.getElementById('kn_locked_business_stage')?.value || '');

            form.querySelectorAll('input[name="business_stage"]').forEach((el) => {
                if (el.id === 'kn_locked_business_stage' || el.hasAttribute('data-kn-locked')) {
                    el.value = value;
                    el.removeAttribute('disabled');
                    return;
                }
                el.setAttribute('disabled', 'disabled');
            });

            const locked = document.getElementById('kn_locked_business_stage');
            if (locked) {
                return;
            }

            let hidden = form.querySelector('#business_stage_submitted');
            if (!hidden) {
                hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'business_stage';
                hidden.id = 'business_stage_submitted';
                form.appendChild(hidden);
            }

            hidden.value = value;
            hidden.removeAttribute('disabled');
        }

        function toggleFieldsByApplicantType() {
            const selectedType = currentApplicantType();
            const headerLabel = document.getElementById('obrazacLabelHeader');
            if (headerLabel) {
                if (selectedType === 'preduzetnica' || selectedType === 'fizicko_lice') headerLabel.textContent = 'Obrazac 1a';
                else if (selectedType === 'doo' || selectedType === 'ostalo') headerLabel.textContent = 'Obrazac 1b';
                else headerLabel.textContent = 'Obrazac 1a/1b';
            }
            const regHeader = document.getElementById('obrazacRegistracijaHeader');
            if (regHeader) {
                const commercialHeadings = {doo: 'DOO', ad: 'AD', od: 'OD', kd: 'KD'};
                if (knLockedCommercialForm && commercialHeadings[knLockedCommercialForm]) {
                    regHeader.textContent = '(za oblik registracije ' + commercialHeadings[knLockedCommercialForm] + ')';
                } else if (selectedType === 'preduzetnica' || selectedType === 'fizicko_lice') {
                    regHeader.textContent = '(za oblik registracije PREDUZETNIK)';
                } else if (selectedType === 'doo') {
                    regHeader.textContent = '(za oblik registracije DOO)';
                } else if (selectedType === 'ostalo') {
                    regHeader.textContent = '(za ostale pravne subjekte)';
                } else {
                    regHeader.textContent = '(za oblik registracije PREDUZETNIK)';
                }
            }
            // Resetuj sve obrazce - disable sva polja u sakrivenim sekcijama
            if (obrazac1a) {
                obrazac1a.classList.remove('show');
                // Disable sva polja u obrazac1a
                const obrazac1aFields = obrazac1a.querySelectorAll('input, select, textarea');
                obrazac1aFields.forEach(field => {
                    field.setAttribute('disabled', 'disabled');
                    field.removeAttribute('required');
                });
            }
            if (obrazac1b) {
                obrazac1b.classList.remove('show');
                // Disable sva polja u obrazac1b
                const obrazac1bFields = obrazac1b.querySelectorAll('input, select, textarea');
                obrazac1bFields.forEach(field => {
                    field.setAttribute('disabled', 'disabled');
                    field.removeAttribute('required');
                });
            }
            if (fizickoLiceFields) {
                fizickoLiceFields.classList.remove('show');
                // Disable sva polja u fizickoLiceFields
                const fizickoLiceAllFields = fizickoLiceFields.querySelectorAll('input, select, textarea');
                fizickoLiceAllFields.forEach(field => {
                    field.setAttribute('disabled', 'disabled');
                    field.removeAttribute('required');
                });
            }

            // Resetuj napomenu
            if (fizickoLiceNotice) {
                fizickoLiceNotice.style.display = (!knIsRegistered && selectedType) ? 'block' : 'none';
            }

            // Resetuj sekciju za izbor tipa prijave za Fizičko lice (Rezident)
            const fizickoLiceBusinessStage = document.getElementById('fizickoLiceBusinessStage');
            if (fizickoLiceBusinessStage) {
                fizickoLiceBusinessStage.style.display = 'none';
                // Ukloni required sa radio button-a
                const businessStageRadios = fizickoLiceBusinessStage.querySelectorAll('input[name="business_stage"]');
                businessStageRadios.forEach(radio => {
                    radio.removeAttribute('required');
                    radio.checked = false;
                });
            }
            
            // Disable business_stage u obrazac1a i obrazac1b (reset)
            if (obrazac1a) {
                const businessStage1a = obrazac1a.querySelectorAll('input[name="business_stage"]');
                businessStage1a.forEach(radio => {
                    radio.setAttribute('disabled', 'disabled');
                });
            }
            if (obrazac1b) {
                const businessStage1b = obrazac1b.querySelectorAll('input[name="business_stage"]');
                businessStage1b.forEach(radio => {
                    radio.setAttribute('disabled', 'disabled');
                });
            }

            form.querySelectorAll('input[name="business_stage"][type="radio"]').forEach((radio) => {
                radio.checked = false;
            });

            // Prikaži/sakrij obrazce na osnovu tipa
            if (selectedType === 'preduzetnica') {
                // Preduzetnica - prikaži Obrazac 1a
                if (obrazac1a) {
                    obrazac1a.classList.add('show');
                    // Enable sva polja u obrazac1a
                    const obrazac1aFields = obrazac1a.querySelectorAll('input, select, textarea');
                    obrazac1aFields.forEach(field => {
                        if (field.hasAttribute('data-kn-locked')) {
                            return;
                        }
                        field.removeAttribute('disabled');
                    });
                    const businessPlanName1a = obrazac1a.querySelector('input[name="business_plan_name"]');
                    const businessArea1a = obrazac1a.querySelector('input[name="business_area"]');
                    const accuracyDeclaration1a = obrazac1a.querySelector('input[name="accuracy_declaration"]');
                    if (businessPlanName1a) businessPlanName1a.setAttribute('required', 'required');
                    if (businessArea1a) businessArea1a.setAttribute('required', 'required');
                    if (accuracyDeclaration1a) accuracyDeclaration1a.setAttribute('required', 'required');
                    const businessStage1a = obrazac1a.querySelectorAll('input[name="business_stage"]');
                    businessStage1a.forEach(radio => {
                        radio.removeAttribute('disabled');
                        radio.setAttribute('required', 'required');
                        if (!knAllowsRazvoj && radio.value === 'razvoj') {
                            radio.setAttribute('disabled', 'disabled');
                            radio.checked = false;
                            radio.removeAttribute('required');
                        }
                    });
                    if (!knAllowsRazvoj) {
                        syncBusinessStageRadiosInSection(obrazac1a, 'započinjanje');
                    } else {
                        syncBusinessStageRadiosInSection(obrazac1a, savedBusinessStageValue);
                    }
                }
            } else if (selectedType === 'doo' || selectedType === 'ostalo') {
                // DOO ili Ostalo - prikaži Obrazac 1b
                if (obrazac1b) {
                    obrazac1b.classList.add('show');
                    // Enable sva polja u obrazac1b
                    const obrazac1bFields = obrazac1b.querySelectorAll('input, select, textarea');
                    obrazac1bFields.forEach(field => {
                        if (field.hasAttribute('data-kn-locked')) {
                            return;
                        }
                        field.removeAttribute('disabled');
                    });
                    const businessPlanName1b = obrazac1b.querySelector('input[name="business_plan_name"]');
                    const businessArea1b = obrazac1b.querySelector('input[name="business_area"]');
                    const founderName = obrazac1b.querySelector('input[name="founder_name"]');
                    const directorName = obrazac1b.querySelector('input[name="director_name"]');
                    const companySeat = obrazac1b.querySelector('input[name="company_seat"]');
                    const accuracyDeclaration1b = obrazac1b.querySelector('input[name="accuracy_declaration"]');
                    if (businessPlanName1b && businessPlanName1b.hasAttribute('data-required')) businessPlanName1b.setAttribute('required', 'required');
                    if (businessArea1b && businessArea1b.hasAttribute('data-required')) businessArea1b.setAttribute('required', 'required');
                    if (founderName && founderName.hasAttribute('data-required')) founderName.setAttribute('required', 'required');
                    if (directorName && directorName.hasAttribute('data-required')) directorName.setAttribute('required', 'required');
                    if (accuracyDeclaration1b) accuracyDeclaration1b.setAttribute('required', 'required');
                    const businessStage1b = obrazac1b.querySelectorAll('input[name="business_stage"]');
                    businessStage1b.forEach(radio => {
                        radio.removeAttribute('disabled');
                        radio.setAttribute('required', 'required');
                        if (!knAllowsRazvoj && radio.value === 'razvoj') {
                            radio.setAttribute('disabled', 'disabled');
                            radio.checked = false;
                            radio.removeAttribute('required');
                        }
                    });
                    if (!knAllowsRazvoj) {
                        syncBusinessStageRadiosInSection(obrazac1b, 'započinjanje');
                    } else {
                        syncBusinessStageRadiosInSection(obrazac1b, savedBusinessStageValue);
                    }
                }
            } else if (selectedType === 'fizicko_lice') {
                // Fizičko lice BEZ registrovane djelatnosti
                // Prikaži napomenu o obavezi registracije
                if (fizickoLiceNotice) {
                    fizickoLiceNotice.style.display = 'block';
                }
                
                // Za "Fizičko lice (nema registrovanu djelatnost)" ne prikazujemo izbor tipa prijave – podrazumijeva se započinjanje
                const fizickoLiceBusinessStage = document.getElementById('fizickoLiceBusinessStage');
                if (fizickoLiceBusinessStage) {
                    fizickoLiceBusinessStage.style.display = 'none';
                    const businessStageRadios = fizickoLiceBusinessStage.querySelectorAll('input[name="business_stage"]');
                    businessStageRadios.forEach(radio => {
                        radio.removeAttribute('required');
                        radio.setAttribute('disabled', 'disabled');
                    });
                }
                // Skriveni business_stage=započinjanje šalje se samo kada nema drugih business_stage polja (Rezident nema "Faza biznisa" u DOM-u)
                const fizickoLiceBusinessStageHidden = document.getElementById('fizickoLiceBusinessStageHidden');
                if (fizickoLiceBusinessStageHidden) {
                    if (typeof isFizickoLiceRezident !== 'undefined' && isFizickoLiceRezident) {
                        fizickoLiceBusinessStageHidden.removeAttribute('disabled');
                    } else {
                        fizickoLiceBusinessStageHidden.setAttribute('disabled', 'disabled');
                    }
                }
                
                // Prikaži polja za fizičko lice
                if (fizickoLiceFields) {
                    fizickoLiceFields.classList.add('show');
                    // Enable sva polja u fizickoLiceFields
                    const fizickoLiceAllFields = fizickoLiceFields.querySelectorAll('input, select, textarea');
                    fizickoLiceAllFields.forEach(field => {
                        field.removeAttribute('disabled');
                    });
                    fizickoLiceRequiredFields.forEach(field => {
                        field.setAttribute('required', 'required');
                    });
                    
                    // Ako je korisnik "Fizičko lice (Rezident)", sakrij business_stage polje u fizickoLiceFields
                    if (typeof isFizickoLiceRezident !== 'undefined' && isFizickoLiceRezident) {
                        const businessStageInFizickoLiceFields = fizickoLiceFields.querySelectorAll('input[name="business_stage"]');
                        businessStageInFizickoLiceFields.forEach(radio => {
                            radio.setAttribute('disabled', 'disabled');
                            radio.removeAttribute('required');
                            radio.checked = false;
                            // Sakrij parent div
                            const parentGroup = radio.closest('.form-group');
                            if (parentGroup) {
                                parentGroup.style.display = 'none';
                            }
                        });
                    }
                }
            }

            // Dodatni podaci: autoritet je zaključani is_registered, ne applicant_type.
            if (additionalDataSection) {
                const additionalInputs = additionalDataSection.querySelectorAll('input[name="bank_account"], input[name="vat_number"], input[name="website"]');
                if (!knLockedIsRegistered) {
                    additionalDataSection.style.display = 'none';
                    additionalInputs.forEach((field) => {
                        field.value = '';
                        field.setAttribute('disabled', 'disabled');
                    });
                } else {
                    additionalDataSection.style.display = '';
                    additionalInputs.forEach((field) => {
                        field.removeAttribute('disabled');
                    });
                }
            }

            // Ažuriraj dugme nakon promjene tipa (npr. za Fizičko lice - prikaži "Sačuvaj prijavu" ako je forma kompletna)
            if (typeof updateSubmitButton === 'function') {
                updateSubmitButton();
            }
        }

        applicantTypeInputs.forEach(input => {
            input.addEventListener('change', function() {
                toggleFieldsByApplicantType();
                setTimeout(setRegistrationForm, 100);
            });
        });

        // Odmah prikaži Obrazac 1a ili 1b (ili polja za fizičko lice) prema izabranom tipu podnosioca
        toggleFieldsByApplicantType();

        // Zatim onemogući polja samo u sekcijama koje su i dalje sakrivene
        const allConditionalFields = document.querySelectorAll('.conditional-field');
        allConditionalFields.forEach(section => {
            if (!section.classList.contains('show')) {
                const fields = section.querySelectorAll('input, select, textarea');
                fields.forEach(field => {
                    field.setAttribute('disabled', 'disabled');
                });
            }
        });

        // Za postojeću prijavu popuni business_stage i PIB u aktivnoj sekciji
        const hasExistingApplication = {{ isset($existingApplication) && $existingApplication ? 'true' : 'false' }};
        if (hasExistingApplication) {
            setTimeout(function() {
                const activeSection = document.querySelector('.conditional-field.show');
                if (activeSection) {
                    const activeFields = activeSection.querySelectorAll('input, select, textarea');
                    activeFields.forEach(field => {
                        field.removeAttribute('disabled');
                    });
                    const existingBusinessStage = savedBusinessStageValue;
                    if (existingBusinessStage) {
                        syncBusinessStageRadiosInSection(activeSection, existingBusinessStage);
                        activeSection.querySelectorAll('input[name="business_stage"][type="radio"]').forEach((radio) => {
                            radio.removeAttribute('disabled');
                        });
                    }
                    const existingPib = '{{ isset($existingApplication) && $existingApplication && $existingApplication->pib ? addslashes($existingApplication->pib) : '' }}';
                    if (existingPib) {
                        const pibField = activeSection.querySelector('input[name="pib"]');
                        if (pibField) {
                            pibField.value = existingPib;
                            pibField.removeAttribute('disabled');
                        } else {
                            const allPibFields = form.querySelectorAll('input[name="pib"]');
                            allPibFields.forEach(field => {
                                if (field.closest('.conditional-field.show')) {
                                    field.value = existingPib;
                                    field.removeAttribute('disabled');
                                }
                            });
                        }
                    }
                    const existingApplicantJmbg = '{{ isset($existingApplication) && $existingApplication && $existingApplication->applicant_jmbg ? addslashes($existingApplication->applicant_jmbg) : '' }}';
                    if (existingApplicantJmbg) {
                        const jmbgField = activeSection.querySelector('input[name="preduzetnik_jmbg"], input[name="doo_jmbg"]');
                        if (jmbgField) {
                            jmbgField.value = existingApplicantJmbg;
                            jmbgField.removeAttribute('disabled');
                        }
                    }
                }
            }, 100);
        }

        // Funkcija za automatsko postavljanje obrasca registracije
        // VAŽNO: Ne prepisuj vrednosti ako postoje iz existingApplication
        function setRegistrationForm() {
            if (!knIsRegistered) {
                return;
            }
            const selectedType = currentApplicantType();
            const userRegistrationForm = '{{ $subjectIdentity->userType ?? "" }}';
            
            // Proveri da li postoji existingApplication
            const hasExistingApplication = {{ isset($existingApplication) && $existingApplication ? 'true' : 'false' }};
            
            if (selectedType === 'preduzetnica') {
                const registrationForm1a = document.getElementById('registration_form_1a');
                if (registrationForm1a && registrationForm1a.offsetParent !== null) {
                    // Ako već postoji vrednost (iz existingApplication ili old), ne menjaj je
                    if (registrationForm1a.value && registrationForm1a.value !== '') {
                        console.log('Registration form 1a već ima vrednost:', registrationForm1a.value);
                        return;
                    }
                    
                    // Ako nema vrednost, postavi default
                    if (userRegistrationForm && userRegistrationForm !== 'Fizičko lice' && userRegistrationForm.trim() !== '') {
                        registrationForm1a.value = userRegistrationForm;
                    } else {
                        registrationForm1a.value = 'Preduzetnik';
                    }
                    console.log('Postavljeno registration_form_1a na default:', registrationForm1a.value);
                }
            } else if (selectedType === 'doo') {
                const registrationForm1b = document.getElementById('registration_form_1b');
                if (registrationForm1b && registrationForm1b.offsetParent !== null) {
                    // Ako već postoji vrednost (iz existingApplication ili old), ne menjaj je
                    if (registrationForm1b.value && registrationForm1b.value !== '') {
                        console.log('Registration form 1b već ima vrednost:', registrationForm1b.value);
                        return;
                    }
                    
                    // Ako nema vrednost, postavi default
                    if (userRegistrationForm && userRegistrationForm !== 'Fizičko lice' && userRegistrationForm.trim() !== '') {
                        registrationForm1b.value = userRegistrationForm;
                    } else {
                        registrationForm1b.value = 'Društvo sa ograničenom odgovornošću';
                    }
                    console.log('Postavljeno registration_form_1b na default:', registrationForm1b.value);
                }
            }
        }
        
        // Pozovi funkciju nakon kratkog vremena da se osiguramo da je DOM spreman
        // Prvo pozovi toggleFieldsByApplicantType da prikaže pravilnu sekciju
        toggleFieldsByApplicantType();
        // Zatim pozovi setRegistrationForm nakon kratke pauze, ali samo ako polja nemaju vrednost
        setTimeout(setRegistrationForm, 300);
        
        // Takođe pozovi kada se promeni tip prijave
        applicantTypeInputs.forEach(input => {
            input.addEventListener('change', function() {
                toggleFieldsByApplicantType();
                setTimeout(setRegistrationForm, 300);
            });
        });

        function hasLockedRegistrationForm() {
            return typeof knLockedRegistrationForm === 'string' && knLockedRegistrationForm.trim() !== '';
        }

        // Funkcija za proveru da li su sva obavezna polja popunjena
        function checkIfObrazacComplete() {
            const form = document.getElementById('applicationForm');
            if (!form) return false;

            // Proveri tip podnosioca
            const applicantTypeValue = currentApplicantType();
            if (!applicantTypeValue) return false;

            // Osnovna obavezna polja - traži samo u aktivnoj sekciji
            const activeSection = document.querySelector('.conditional-field.show');
            const businessPlanName = activeSection ? activeSection.querySelector('input[name="business_plan_name"]') : form.querySelector('input[name="business_plan_name"]:not([disabled])');
            const businessArea = activeSection ? activeSection.querySelector('input[name="business_area"]') : form.querySelector('input[name="business_area"]:not([disabled])');

            // Proveri osnovna polja
            if (!businessPlanName || !businessPlanName.value.trim()) return false;
            if (!knLockedBusinessStage) return false;
            if (!businessArea || !businessArea.value.trim()) return false;

            // Proveri polja specifična za tip podnosioca
            if (applicantTypeValue === 'fizicko_lice') {
                // Traži polja samo u aktivnoj sekciji (fizickoLiceFields)
                const physicalPersonName = activeSection ? activeSection.querySelector('input[name="physical_person_name"]') : form.querySelector('input[name="physical_person_name"]:not([disabled])');
                const physicalPersonJmbg = activeSection ? activeSection.querySelector('input[name="physical_person_jmbg"]') : form.querySelector('input[name="physical_person_jmbg"]:not([disabled])');
                const physicalPersonPhone = activeSection ? activeSection.querySelector('input[name="physical_person_phone"]') : form.querySelector('input[name="physical_person_phone"]:not([disabled])');
                const physicalPersonEmail = activeSection ? activeSection.querySelector('input[name="physical_person_email"]') : form.querySelector('input[name="physical_person_email"]:not([disabled])');
                const accuracyDeclaration = activeSection ? activeSection.querySelector('input[name="accuracy_declaration"]') : form.querySelector('input[name="accuracy_declaration"]:not([disabled])');

                if (!physicalPersonName || !physicalPersonName.value.trim()) return false;
                if (!physicalPersonJmbg || !physicalPersonJmbg.value.trim()) return false;
                if (!physicalPersonPhone || !physicalPersonPhone.value.trim()) return false;
                if (!physicalPersonEmail || !physicalPersonEmail.value.trim()) return false;
                if (!accuracyDeclaration || !accuracyDeclaration.checked) return false;
            } else if (applicantTypeValue === 'doo' || applicantTypeValue === 'ostalo') {
                // Traži polja samo u aktivnoj sekciji (Obrazac 1b)
                const founderName = activeSection ? activeSection.querySelector('input[name="founder_name"]') : form.querySelector('input[name="founder_name"]:not([disabled])');
                const directorName = activeSection ? activeSection.querySelector('input[name="director_name"]') : form.querySelector('input[name="director_name"]:not([disabled])');
                const companySeat = activeSection ? activeSection.querySelector('input[name="company_seat"]') : form.querySelector('input[name="company_seat"]:not([disabled])');
                const applicantJmbg = activeSection ? activeSection.querySelector('input[name="doo_jmbg"]') : form.querySelector('input[name="doo_jmbg"]:not([disabled])');

                if (knLockedIsRegistered) {
                    if (!founderName || !founderName.value.trim()) return false;
                    if (!directorName || !directorName.value.trim()) return false;
                    if (!companySeat || !companySeat.value.trim()) return false;
                    if (!hasLockedRegistrationForm()) return false;
                }
                if (!applicantJmbg || !/^[0-9]{13}$/.test(applicantJmbg.value.trim())) return false;
                const accuracyDeclaration = activeSection ? activeSection.querySelector('input[name="accuracy_declaration"]') : form.querySelector('input[name="accuracy_declaration"]:not([disabled])');
                if (!accuracyDeclaration || !accuracyDeclaration.checked) return false;
            } else if (applicantTypeValue === 'preduzetnica') {
                const accuracyDeclaration = activeSection ? activeSection.querySelector('input[name="accuracy_declaration"]') : form.querySelector('input[name="accuracy_declaration"]:not([disabled])');
                const applicantJmbg = activeSection ? activeSection.querySelector('input[name="preduzetnik_jmbg"]') : form.querySelector('input[name="preduzetnik_jmbg"]:not([disabled])');
                const profileAddress = @json($userProfileAddress);

                if (knLockedIsRegistered && !hasLockedRegistrationForm()) return false;
                if (!accuracyDeclaration || !accuracyDeclaration.checked) return false;
                if (!profileAddress || !profileAddress.trim()) return false;
                if (!applicantJmbg || !/^[0-9]{13}$/.test(applicantJmbg.value.trim())) return false;
            }

            return true;
        }

        // Funkcija za ažuriranje dugmeta na osnovu kompletnosti obrasca
        function updateSubmitButton() {
            const saveAsDraftBtn = document.getElementById('saveAsDraftBtn');
            const submitBtn = document.getElementById('submitBtn');

            if (!saveAsDraftBtn || !submitBtn) return;

            const isComplete = checkIfObrazacComplete();

            if (isComplete) {
                // Ako je obrazac kompletan, sakrij "Sačuvaj kao nacrt" i prikaži "Sačuvaj prijavu"
                saveAsDraftBtn.style.display = 'none';
                submitBtn.style.display = 'inline-block';
                submitBtn.textContent = 'Sačuvaj i nastavi na biznis plan';
                // Ukloni name="save_as_draft" sa submitBtn da se ne šalje kao draft
                submitBtn.removeAttribute('name');
                submitBtn.removeAttribute('value');
            } else {
                // Ako nije kompletan, prikaži "Sačuvaj kao nacrt" i sakrij "Sačuvaj prijavu"
                saveAsDraftBtn.style.display = 'inline-block';
                submitBtn.style.display = 'none';
            }
        }

        // Dodaj event listenere na sva polja za praćenje promena
        const formForTracking = document.getElementById('applicationForm');
        if (formForTracking) {
            // Dodaj event listenere na sva input polja, select-ove i checkbox-ove
            const allFields = formForTracking.querySelectorAll('input, select, textarea');
            allFields.forEach(field => {
                field.addEventListener('input', updateSubmitButton);
                field.addEventListener('change', updateSubmitButton);
            });

            // Proveri inicijalno stanje nakon kratkog vremena
            setTimeout(updateSubmitButton, 1000);
        }

        // Pripremi formu za submit - ukloni disabled sa svih polja (samo ako nije readOnly)
        @if(!($readOnly ?? false))
        const applicationForm = document.getElementById('applicationForm');
        if (applicationForm) {
            // Dugme "Sačuvaj kao nacrt" - ručno submit-uj formu bez validacije
            const saveAsDraftBtn = document.getElementById('saveAsDraftBtn');
            if (saveAsDraftBtn) {
                saveAsDraftBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // Dodaj hidden input za save_as_draft
                    let draftInput = applicationForm.querySelector('input[name="save_as_draft"]');
                    if (!draftInput) {
                        draftInput = document.createElement('input');
                        draftInput.type = 'hidden';
                        draftInput.name = 'save_as_draft';
                        draftInput.value = '1';
                        applicationForm.appendChild(draftInput);
                    }
                    
                    // Ukloni sve required atribute
                    const allRequiredFields = applicationForm.querySelectorAll('[required]');
                    allRequiredFields.forEach(field => {
                        field.removeAttribute('required');
                    });
                    
                    // VAŽNO: Jednostavniji pristup - ukloni disabled samo iz aktivne sekcije i postavi disabled u sakrivenim
                    // Ovo je sigurniji način jer ne kopiramo vrednosti, već samo kontrolišemo koja se šalju
                    
                    // 1. applicant_type šalje zaključani hidden; ne otključavaj display radio
                    const lockedApplicantType = applicationForm.querySelector('#kn_locked_applicant_type');
                    if (!lockedApplicantType || !lockedApplicantType.value) {
                        const allApplicantTypeRadios = applicationForm.querySelectorAll('input[name="applicant_type"]');
                        allApplicantTypeRadios.forEach(radio => {
                            radio.removeAttribute('disabled');
                        });
                        const checkedApplicantTypeRadio = applicationForm.querySelector('input[name="applicant_type"][type="radio"]:checked');
                        if (!checkedApplicantTypeRadio) {
                            const defaultApplicantTypeRadio = applicationForm.querySelector('input[name="applicant_type"][type="radio"][value="preduzetnica"]')
                                || applicationForm.querySelector('input[name="applicant_type"][type="radio"]');
                            if (defaultApplicantTypeRadio) {
                                defaultApplicantTypeRadio.checked = true;
                            }
                        }
                    }
                    
                    // 2. Ukloni disabled sa svih polja u aktivnoj sekciji (osim business_stage – šalje se posebno)
                    const activeSection = document.querySelector('.conditional-field.show');
                    if (activeSection) {
                        const activeFields = activeSection.querySelectorAll('input, select, textarea');
                        activeFields.forEach(field => {
                            if (field.name !== 'business_stage') {
                                field.removeAttribute('disabled');
                            }
                        });
                    }
                    
                    // 4. Postavi disabled na sva polja u sakrivenim sekcijama (osim applicant_type)
                    // VAŽNO: Osiguraj da se business_plan_name, business_area, registration_form i polja specifična za Obrazac 1b šalju samo iz aktivne sekcije
                    const allBusinessPlanNames = applicationForm.querySelectorAll('input[name="business_plan_name"]');
                    const allBusinessAreas = applicationForm.querySelectorAll('input[name="business_area"]');
                    const allRegistrationForms = applicationForm.querySelectorAll('select[name="registration_form"]');
                    const allFounderNames = applicationForm.querySelectorAll('input[name="founder_name"]');
                    const allDirectorNames = applicationForm.querySelectorAll('input[name="director_name"]');
                    const allCompanySeats = applicationForm.querySelectorAll('input[name="company_seat"]');
                    
                    // Postavi disabled na SVE business_plan_name input-e
                    allBusinessPlanNames.forEach(input => {
                        input.setAttribute('disabled', 'disabled');
                    });
                    
                    // Postavi disabled na SVE business_area input-e
                    allBusinessAreas.forEach(input => {
                        input.setAttribute('disabled', 'disabled');
                    });
                    
                    // Postavi disabled na SVE registration_form select-e
                    allRegistrationForms.forEach(select => {
                        select.setAttribute('disabled', 'disabled');
                    });
                    
                    // Postavi disabled na SVE founder_name input-e (za Obrazac 1b)
                    allFounderNames.forEach(input => {
                        input.setAttribute('disabled', 'disabled');
                    });
                    
                    // Postavi disabled na SVE director_name input-e (za Obrazac 1b)
                    allDirectorNames.forEach(input => {
                        input.setAttribute('disabled', 'disabled');
                    });
                    
                    // Postavi disabled na SVE company_seat input-e (za Obrazac 1b)
                    allCompanySeats.forEach(input => {
                        input.setAttribute('disabled', 'disabled');
                    });
                    
                    // Ukloni disabled samo iz aktivne sekcije
                    if (activeSection) {
                        const businessPlanNameInActive = activeSection.querySelector('input[name="business_plan_name"]');
                        const businessAreaInActive = activeSection.querySelector('input[name="business_area"]');
                        const registrationFormInActive = activeSection.querySelector('select[name="registration_form"]');
                        const founderNameInActive = activeSection.querySelector('input[name="founder_name"]');
                        const directorNameInActive = activeSection.querySelector('input[name="director_name"]');
                        const companySeatInActive = activeSection.querySelector('input[name="company_seat"]');
                        
                        if (businessPlanNameInActive) {
                            businessPlanNameInActive.removeAttribute('disabled');
                        }
                        if (businessAreaInActive) {
                            businessAreaInActive.removeAttribute('disabled');
                        }
                        if (registrationFormInActive) {
                            registrationFormInActive.removeAttribute('disabled');
                        }
                        if (founderNameInActive) {
                            founderNameInActive.removeAttribute('disabled');
                        }
                        if (directorNameInActive) {
                            directorNameInActive.removeAttribute('disabled');
                        }
                        if (companySeatInActive) {
                            companySeatInActive.removeAttribute('disabled');
                        }
                    }
                    
                    const hiddenSections = document.querySelectorAll('.conditional-field:not(.show)');
                    hiddenSections.forEach(section => {
                        const allFieldsInSection = section.querySelectorAll('input, select, textarea');
                        allFieldsInSection.forEach(field => {
                            // Ne postavljaj disabled na applicant_type i business_stage
                            // Ostala polja već imaju disabled postavljen gore (business_plan_name, registration_form, founder_name, itd.)
                            if (field.name !== 'applicant_type' && field.name !== 'business_stage') {
                                // Osiguraj da su polja koja se šalju samo iz aktivne sekcije disabled
                                const fieldsToKeepDisabled = ['registration_form', 'business_plan_name', 'business_area', 'founder_name', 'director_name', 'company_seat'];
                                if (fieldsToKeepDisabled.includes(field.name)) {
                                    field.setAttribute('disabled', 'disabled');
                                } else {
                                    // Za ostala polja, također postavi disabled
                                    field.setAttribute('disabled', 'disabled');
                                }
                            }
                        });
                    });
                    
                    prepareBusinessStageForSubmit(applicationForm);

                    // Submit-uj formu
                    applicationForm.submit();
                });
            }
            
            // Normalan submit (za "Sačuvaj prijavu" dugme) - samo ako nije readOnly
            applicationForm.addEventListener('submit', function(e) {
                // VAŽNO: Ukloni save_as_draft hidden input ako postoji
                // (ovo osigurava da se forma ne šalje kao draft kada je obrazac kompletan)
                const saveAsDraftInput = applicationForm.querySelector('input[name="save_as_draft"]');
                if (saveAsDraftInput) {
                    saveAsDraftInput.remove();
                    console.log('Removed save_as_draft input before submit');
                }
                
                const lockedApplicantType = applicationForm.querySelector('#kn_locked_applicant_type');
                if (!lockedApplicantType || !lockedApplicantType.value) {
                    const allApplicantTypeRadios = applicationForm.querySelectorAll('input[name="applicant_type"]');
                    allApplicantTypeRadios.forEach(radio => {
                        radio.removeAttribute('disabled');
                    });
                }
                
                // VAŽNO: Osiguraj da se registration_form i business_plan_name šalju iz aktivne sekcije
                const activeSection = document.querySelector('.conditional-field.show');
                const selectedType = currentApplicantType();
                
                // Pronađi SVE registration_form select-e, business_plan_name input-e, business_area input-e i polja specifična za Obrazac 1b
                const allRegistrationForms = applicationForm.querySelectorAll('select[name="registration_form"]');
                const allBusinessPlanNames = applicationForm.querySelectorAll('input[name="business_plan_name"]');
                const allBusinessAreas = applicationForm.querySelectorAll('input[name="business_area"]');
                const allFounderNames = applicationForm.querySelectorAll('input[name="founder_name"]');
                const allDirectorNames = applicationForm.querySelectorAll('input[name="director_name"]');
                const allCompanySeats = applicationForm.querySelectorAll('input[name="company_seat"]');
                
                if (activeSection && selectedType) {
                    const registrationFormInActive = activeSection.querySelector('select[name="registration_form"]');
                    const businessPlanNameInActive = activeSection.querySelector('input[name="business_plan_name"]');
                    const businessAreaInActive = activeSection.querySelector('input[name="business_area"]');
                    const founderNameInActive = activeSection.querySelector('input[name="founder_name"]');
                    const directorNameInActive = activeSection.querySelector('input[name="director_name"]');
                    const companySeatInActive = activeSection.querySelector('input[name="company_seat"]');
                    
                    // Postavi disabled na SVE registration_form select-e
                    allRegistrationForms.forEach(select => {
                        select.setAttribute('disabled', 'disabled');
                    });
                    
                    // Postavi disabled na SVE business_plan_name input-e
                    allBusinessPlanNames.forEach(input => {
                        input.setAttribute('disabled', 'disabled');
                    });
                    
                    // Postavi disabled na SVE business_area input-e
                    allBusinessAreas.forEach(input => {
                        input.setAttribute('disabled', 'disabled');
                    });
                    
                    // Postavi disabled na SVE founder_name input-e (za Obrazac 1b)
                    allFounderNames.forEach(input => {
                        input.setAttribute('disabled', 'disabled');
                    });
                    
                    // Postavi disabled na SVE director_name input-e (za Obrazac 1b)
                    allDirectorNames.forEach(input => {
                        input.setAttribute('disabled', 'disabled');
                    });
                    
                    // Postavi disabled na SVE company_seat input-e (za Obrazac 1b)
                    allCompanySeats.forEach(input => {
                        input.setAttribute('disabled', 'disabled');
                    });
                    
                    if (registrationFormInActive) {
                        // Ukloni disabled samo sa select-a u aktivnoj sekciji
                        registrationFormInActive.removeAttribute('disabled');
                        
                        // VAŽNO: Uvek postavi vrednost, čak i ako već ima
                        let registrationFormValue = registrationFormInActive.value;
                        
                        // Ako nema vrednost, postavi default na osnovu applicant_type
                        if (!registrationFormValue || registrationFormValue === '') {
                            if (selectedType === 'preduzetnica') {
                                registrationFormValue = 'Preduzetnik';
                            } else if (selectedType === 'doo') {
                                registrationFormValue = 'Društvo sa ograničenom odgovornošću';
                            }
                            registrationFormInActive.value = registrationFormValue;
                            console.log('Set registration_form to:', registrationFormValue, 'for type:', selectedType);
                        } else {
                            console.log('Registration_form already has value:', registrationFormValue);
                        }
                    } else {
                        console.error('registration_form select not found in active section!');
                    }
                    
                    if (businessPlanNameInActive) {
                        // Ukloni disabled samo sa input-a u aktivnoj sekciji
                        businessPlanNameInActive.removeAttribute('disabled');
                        console.log('Enabled business_plan_name in active section:', businessPlanNameInActive.value);
                    } else {
                        console.error('business_plan_name input not found in active section!');
                    }
                    
                    if (businessAreaInActive) {
                        // Ukloni disabled samo sa input-a u aktivnoj sekciji
                        businessAreaInActive.removeAttribute('disabled');
                        console.log('Enabled business_area in active section:', businessAreaInActive.value);
                    } else {
                        console.error('business_area input not found in active section!');
                    }
                    
                    // Ukloni disabled sa polja specifična za Obrazac 1b (ako postoje u aktivnoj sekciji)
                    if (founderNameInActive) {
                        founderNameInActive.removeAttribute('disabled');
                        console.log('Enabled founder_name in active section:', founderNameInActive.value);
                    }
                    if (directorNameInActive) {
                        directorNameInActive.removeAttribute('disabled');
                        console.log('Enabled director_name in active section:', directorNameInActive.value);
                    }
                    if (companySeatInActive) {
                        companySeatInActive.removeAttribute('disabled');
                        console.log('Enabled company_seat in active section:', companySeatInActive.value);
                    }
                } else {
                    console.error('Active section or selectedType not found!', { activeSection, selectedType });
                }
                
                // Ukloni disabled sa svih polja u sakrivenim sekcijama (osim polja koja se šalju samo iz aktivne sekcije)
                const hiddenSections = document.querySelectorAll('.conditional-field:not(.show)');
                hiddenSections.forEach(section => {
                    const allFields = section.querySelectorAll('input, select, textarea');
                    allFields.forEach(field => {
                        // Ne uklanjaj disabled sa polja koja se šalju samo iz aktivne sekcije
                        const fieldsToKeepDisabled = ['registration_form', 'business_plan_name', 'business_area', 'founder_name', 'director_name', 'company_seat', 'business_stage'];
                        if (fieldsToKeepDisabled.includes(field.name)) {
                            return; // Već postavljen na disabled
                        }
                        field.removeAttribute('required');
                        field.removeAttribute('disabled');
                    });
                });

                prepareBusinessStageForSubmit(applicationForm);
            });
        }
        @endif
    });
</script>
@endsection

