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
</style>

<div class="application-form-page">
    <div class="container mx-auto px-4">
        <div class="page-header">
            <h1>Prijava na konkurs - Obrazac 1a/1b</h1>
            <p style="color: rgba(255,255,255,0.9); margin: 0;">{{ $competition->title }}</p>
        </div>

        @if(session('success'))
            <div class="alert alert-info">
                {{ session('success') }}
            </div>
        @endif


        @php
            $readOnly = $readOnly ?? false;
        @endphp
        
        @if($readOnly)
            <div class="alert alert-info" style="margin-bottom: 24px; padding: 16px; background: #fef3c7; border: 1px solid #fbbf24; border-radius: 8px; color: #92400e;">
                <strong>Pregled prijave:</strong> Ovo je pregled prijave, ne možete mijenjati podatke.
            </div>
        @endif

        <form method="POST" action="{{ $readOnly ? '#' : route('applications.store', $competition) }}" id="applicationForm" @if($readOnly) onsubmit="event.preventDefault(); return false;" @endif>
            @csrf
            
            @php
                // Helper funkcija za dobijanje vrednosti polja (old > existingApplication > default)
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
            @endphp
            
            @if(isset($existingApplication) && $existingApplication && !$readOnly)
                <div class="alert alert-info" style="margin-bottom: 24px; padding: 16px; background: #dbeafe; border: 1px solid #93c5fd; border-radius: 8px; color: #1e40af;">
                    <strong>Nastavak popunjavanja:</strong> Već imate započetu prijavu. Možete je nastaviti popunjavati.
                </div>
            @endif

            <!-- Tip podnosioca prijave (prikazuje se prije obrazaca) -->
            <div class="form-card">
                <div class="form-section">
                    <div class="form-group">
                        <label class="form-label">
                            Tip podnosioca prijave <span class="required">*</span>
                        </label>
                        <div class="form-text" style="margin-bottom: 12px; color: #6b7280; font-size: 13px;">
                            <strong>Napomena:</strong> "Fizičko lice (nema registrovanu djelatnost)" se odnosi na osobe koje nemaju registrovanu djelatnost u skladu sa Zakonom o privrednim društvima. 
                            "Preduzetnica" se odnosi na fizička lica koja imaju registrovanu djelatnost (preduzetnici).
                        </div>
                        @php
                            $userType = auth()->user()->user_type ?? '';
                            // Podrazumevano uvijek "Preduzetnica"
                            $defaultType = 'preduzetnica';
                            
                            if (str_contains($userType, 'Društvo sa ograničenom odgovornošću') || str_contains($userType, 'DOO')) {
                                // Registrovani privredni subjekt tipa DOO
                                $defaultType = 'doo';
                            } elseif ($userType && $userType !== 'Fizičko lice' && $userType !== 'Preduzetnik') {
                                // Svi ostali registrovani privredni subjekti (Akcionarsko društvo, NVO, Udruženje, itd.)
                                $defaultType = 'ostalo';
                            }
                        @endphp
                        <div class="radio-group">
                            <div class="radio-option">
                                <input 
                                    type="radio" 
                                    id="applicant_type_fizicko_lice" 
                                    name="applicant_type" 
                                    value="fizicko_lice"
                                    {{ old('applicant_type', (isset($existingApplication) && $existingApplication ? $existingApplication->applicant_type : null) ?? $defaultType) === 'fizicko_lice' ? 'checked' : '' }}
                                    required
                                >
                                <label for="applicant_type_fizicko_lice">Fizičko lice (nema registrovanu djelatnost)</label>
                            </div>
                            <div class="radio-option">
                                <input 
                                    type="radio" 
                                    id="applicant_type_preduzetnica" 
                                    name="applicant_type" 
                                    value="preduzetnica"
                                    {{ old('applicant_type', (isset($existingApplication) && $existingApplication ? $existingApplication->applicant_type : null) ?? $defaultType) === 'preduzetnica' ? 'checked' : '' }}
                                >
                                <label for="applicant_type_preduzetnica">Preduzetnica</label>
                            </div>
                            <div class="radio-option">
                                <input 
                                    type="radio" 
                                    id="applicant_type_doo" 
                                    name="applicant_type" 
                                    value="doo"
                                    {{ old('applicant_type', (isset($existingApplication) && $existingApplication ? $existingApplication->applicant_type : null) ?? $defaultType) === 'doo' ? 'checked' : '' }}
                                >
                                <label for="applicant_type_doo">DOO (Društvo sa ograničenom odgovornošću)</label>
                            </div>
                            <div class="radio-option">
                                <input 
                                    type="radio" 
                                    id="applicant_type_ostalo" 
                                    name="applicant_type" 
                                    value="ostalo"
                                    {{ old('applicant_type', (isset($existingApplication) && $existingApplication ? $existingApplication->applicant_type : null) ?? $defaultType) === 'ostalo' ? 'checked' : '' }}
                                >
                                <label for="applicant_type_ostalo">Ostalo</label>
                            </div>
                        </div>
                        @error('applicant_type')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Napomena za Fizičko lice (nema registrovanu djelatnost) - prikazuje se kada je izabrano -->
            <div class="alert alert-info conditional-field" id="fizickoLiceNotice" style="display: none; margin-bottom: 24px;">
                <strong>Važno:</strong> Ukoliko podnosioc biznis plana nema registrovanu djelatnost, u slučaju da joj sredstva budu odobrena u obavezi je da svoju djelatnost registruje u neki od oblika registracije koji predviđa Zakon o privrednim društvima i priloži dokaz (rješenje o registraciji u CRPS i rješenje o registraciji PJ Uprave prihoda i carina), najkasnije do dana potpisivanja ugovora.
            </div>

            <!-- Izbor tipa prijave za Fizičko lice (Rezident) -->
            @php
                $userType = auth()->user()->user_type ?? '';
                $isFizickoLiceRezident = ($userType === 'Fizičko lice' || $userType === 'Rezident');
            @endphp
            <div class="form-card conditional-field" id="fizickoLiceBusinessStage" style="display: none;">
                <div class="form-section">
                    <div class="form-group">
                        <label class="form-label">
                            Tip prijave <span class="required">*</span>
                        </label>
                        <div class="form-text" style="margin-bottom: 12px; color: #6b7280; font-size: 13px;">
                            <strong>Napomena:</strong> Molimo vas da izaberete da li se prijavljujete kao Preduzetnica koja započinje biznis ili Preduzetnica koja planira razvoj poslovanja. Na osnovu vašeg izbora, biće određen spisak obaveznih dokumenata.
                        </div>
                        <div class="radio-group">
                            <div class="radio-option">
                                <input 
                                    type="radio" 
                                    id="business_stage_zapocinjanje_fizicko" 
                                    name="business_stage" 
                                    value="započinjanje"
                                    {{ old('business_stage', isset($existingApplication) && $existingApplication ? $existingApplication->business_stage : '') === 'započinjanje' ? 'checked' : '' }}
                                >
                                <label for="business_stage_zapocinjanje_fizicko">Preduzetnica koja započinje biznis</label>
                            </div>
                            <div class="radio-option">
                                <input 
                                    type="radio" 
                                    id="business_stage_razvoj_fizicko" 
                                    name="business_stage" 
                                    value="razvoj"
                                    {{ old('business_stage', isset($existingApplication) && $existingApplication ? $existingApplication->business_stage : '') === 'razvoj' ? 'checked' : '' }}
                                >
                                <label for="business_stage_razvoj_fizicko">Preduzetnica koja planira razvoj poslovanja</label>
                            </div>
                        </div>
                        @error('business_stage')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Obrazac 1a: Za Preduzetnice (PREDUZETNIK) -->
            <div class="form-card conditional-field" id="obrazac1a">
                <div class="form-section">
                    <div style="text-align: center; margin-bottom: 24px;">
                        <h1 style="font-size: 20px; font-weight: 700; margin-bottom: 8px;">Obrazac 1a</h1>
                        <h2 style="font-size: 18px; font-weight: 600; margin-bottom: 8px;">PRIJAVA</h2>
                        <p style="margin: 4px 0; font-size: 14px;">na javni konkurs za raspodjelu bespovratnih sredstava</p>
                        <p style="margin: 4px 0; font-size: 14px;">namjenjenih za podršku ženskom preduzetništvu</p>
                        <p style="margin: 4px 0; font-size: 14px;">(za oblik registracije PREDUZETNIK)</p>
                    </div>

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
                            <label class="form-label">JMBG:</label>
                            <input 
                                type="text" 
                                name="preduzetnik_jmbg" 
                                class="form-control @error('preduzetnik_jmbg') error @enderror"
                                value="{{ old('preduzetnik_jmbg', isset($existingApplication) && $existingApplication && $existingApplication->user ? $existingApplication->user->jmb : auth()->user()->jmb) }}"
                                maxlength="13"
                                pattern="[0-9]{13}"
                                placeholder="13 cifara"
                            >
                            @error('preduzetnik_jmbg')
                                <div class="error-message">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label">Kontakt telefon:</label>
                            <input 
                                type="tel" 
                                name="preduzetnik_phone" 
                                class="form-control @error('preduzetnik_phone') error @enderror"
                                value="{{ old('preduzetnik_phone', isset($existingApplication) && $existingApplication && $existingApplication->user ? $existingApplication->user->phone : auth()->user()->phone) }}"
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
                                name="preduzetnik_address" 
                                class="form-control @error('preduzetnik_address') error @enderror"
                                value="{{ old('preduzetnik_address', isset($existingApplication) && $existingApplication && $existingApplication->user ? $existingApplication->user->address : auth()->user()->address) }}"
                                maxlength="255"
                            >
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
                            Oblik registracije <span class="required">*</span>
                        </label>
                        <select 
                            name="registration_form" 
                            id="registration_form_1a"
                            class="form-control @error('registration_form') error @enderror"
                            required
                        >
                            <option value="">Izaberite oblik registracije</option>
                            @php
                                // Automatski postavi na osnovu tipa prijave ili user_type iz registracije
                                $defaultRegistrationForm = old('registration_form', '');
                                if (empty($defaultRegistrationForm) && isset($existingApplication) && $existingApplication && $existingApplication->registration_form) {
                                    $defaultRegistrationForm = $existingApplication->registration_form;
                                }
                                $userType = auth()->user()->user_type ?? '';
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
                            <option value="Druge organizacije (Političke partije, Verske zajednice, Komore, Sindikati)" {{ $defaultRegistrationForm === 'Druge organizacije (Političke partije, Verske zajednice, Komore, Sindikati)' ? 'selected' : '' }}>Druge organizacije (Političke partije, Verske zajednice, Komore, Sindikati)</option>
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
                                value="{{ old('pib', isset($existingApplication) && $existingApplication ? $existingApplication->pib : auth()->user()->pib) }}"
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
                                Kao podnosilac prijave pod punom materijalnom i krivičnom odgovornošću izjavljujem da su gore navedeni podaci istiniti.
                                <span class="required">*</span>
                            </label>
                        </div>
                        @error('accuracy_declaration')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            Faza biznisa <span class="required">*</span>
                        </label>
                        <div class="radio-group">
                            <div class="radio-option">
                                <input 
                                    type="radio" 
                                    id="business_stage_zapocinjanje_1a" 
                                    name="business_stage" 
                                    value="započinjanje"
                                    {{ old('business_stage', isset($existingApplication) && $existingApplication ? $existingApplication->business_stage : 'započinjanje') === 'započinjanje' ? 'checked' : '' }}
                                    required
                                >
                                <label for="business_stage_zapocinjanje_1a">Započinjanje poslovne djelatnosti</label>
                            </div>
                            <div class="radio-option">
                                <input 
                                    type="radio" 
                                    id="business_stage_razvoj_1a" 
                                    name="business_stage" 
                                    value="razvoj"
                                    {{ old('business_stage', isset($existingApplication) && $existingApplication ? $existingApplication->business_stage : '') === 'razvoj' ? 'checked' : '' }}
                                    data-required="true"
                                >
                                <label for="business_stage_razvoj_1a">Razvoj postojeće poslovne djelatnosti</label>
                            </div>
                        </div>
                        @error('business_stage')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Obrazac 1b: Za DOO i Ostalo -->
            <div class="form-card conditional-field" id="obrazac1b">
                <div class="form-section">
                    <div style="text-align: center; margin-bottom: 24px;">
                        <h1 style="font-size: 20px; font-weight: 700; margin-bottom: 8px;">Obrazac 1b</h1>
                        <h2 style="font-size: 18px; font-weight: 600; margin-bottom: 8px;">PRIJAVA</h2>
                        <p style="margin: 4px 0; font-size: 14px;">na javni konkurs za raspodjelu bespovratnih sredstava</p>
                        <p style="margin: 4px 0; font-size: 14px;">namjenjenih za podršku ženskom preduzetništvu</p>
                        <p style="margin: 4px 0; font-size: 14px;" id="obrazac1b-subtitle">(za oblik registracije DOO)</p>
                    </div>

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
                            <label class="form-label">JMBG:</label>
                            <input 
                                type="text" 
                                name="doo_jmbg" 
                                class="form-control @error('doo_jmbg') error @enderror"
                                value="{{ old('doo_jmbg', isset($existingApplication) && $existingApplication && $existingApplication->user ? $existingApplication->user->jmb : auth()->user()->jmb) }}"
                                maxlength="13"
                                pattern="[0-9]{13}"
                                placeholder="13 cifara"
                            >
                            @error('doo_jmbg')
                                <div class="error-message">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label">Kontakt telefon:</label>
                            <input 
                                type="tel" 
                                name="doo_phone" 
                                class="form-control @error('doo_phone') error @enderror"
                                value="{{ old('doo_phone', isset($existingApplication) && $existingApplication && $existingApplication->user ? $existingApplication->user->phone : auth()->user()->phone) }}"
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
                                name="doo_address" 
                                class="form-control @error('doo_address') error @enderror"
                                value="{{ old('doo_address', isset($existingApplication) && $existingApplication && $existingApplication->user ? $existingApplication->user->address : auth()->user()->address) }}"
                                maxlength="255"
                            >
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
                            Oblik registracije <span class="required">*</span>
                        </label>
                        <select 
                            name="registration_form" 
                            id="registration_form_1b"
                            class="form-control @error('registration_form') error @enderror"
                            required
                        >
                            <option value="">Izaberite oblik registracije</option>
                            @php
                                // Automatski postavi na osnovu tipa prijave ili user_type iz registracije
                                $defaultRegistrationForm1b = old('registration_form', '');
                                $userType = auth()->user()->user_type ?? '';
                                $defaultApplicantType = old('applicant_type', $defaultType ?? '');
                                
                                // Ako nema old value, koristi user_type ako postoji i nije "Fizičko lice"
                                if (empty($defaultRegistrationForm1b) && $userType && $userType !== 'Fizičko lice') {
                                    $defaultRegistrationForm1b = $userType;
                                }
                                
                                // Ako i dalje nema vrednost, postavi na osnovu tipa prijave
                                if (empty($defaultRegistrationForm1b)) {
                                    if ($defaultApplicantType === 'doo') {
                                        $defaultRegistrationForm1b = 'Društvo sa ograničenom odgovornošću';
                                    } elseif ($defaultApplicantType === 'ostalo') {
                                        // Za "Ostalo" ne postavljamo automatski, korisnik bira
                                        $defaultRegistrationForm1b = '';
                                    } else {
                                        // Podrazumevano za obrazac 1b
                                        $defaultRegistrationForm1b = 'Društvo sa ograničenom odgovornošću';
                                    }
                                }
                            @endphp
                            <option value="Preduzetnik" {{ $defaultRegistrationForm1b === 'Preduzetnik' ? 'selected' : '' }}>Preduzetnik</option>
                            <option value="Ortačko društvo" {{ $defaultRegistrationForm === 'Ortačko društvo' ? 'selected' : '' }}>Ortačko društvo</option>
                            <option value="Komanditno društvo" {{ $defaultRegistrationForm === 'Komanditno društvo' ? 'selected' : '' }}>Komanditno društvo</option>
                            <option value="Društvo sa ograničenom odgovornošću" {{ $defaultRegistrationForm === 'Društvo sa ograničenom odgovornošću' ? 'selected' : '' }}>Društvo sa ograničenom odgovornošću</option>
                            <option value="Akcionarsko društvo" {{ $defaultRegistrationForm === 'Akcionarsko društvo' ? 'selected' : '' }}>Akcionarsko društvo</option>
                            <option value="Dio stranog društva (predstavništvo ili poslovna jedinica)" {{ $defaultRegistrationForm === 'Dio stranog društva (predstavništvo ili poslovna jedinica)' ? 'selected' : '' }}>Dio stranog društva (predstavništvo ili poslovna jedinica)</option>
                            <option value="Udruženje (nvo, fondacije, sportske organizacije)" {{ $defaultRegistrationForm === 'Udruženje (nvo, fondacije, sportske organizacije)' ? 'selected' : '' }}>Udruženje (nvo, fondacije, sportske organizacije)</option>
                            <option value="Ustanova (državne i privatne)" {{ $defaultRegistrationForm === 'Ustanova (državne i privatne)' ? 'selected' : '' }}>Ustanova (državne i privatne)</option>
                            <option value="Druge organizacije (Političke partije, Verske zajednice, Komore, Sindikati)" {{ $defaultRegistrationForm === 'Druge organizacije (Političke partije, Verske zajednice, Komore, Sindikati)' ? 'selected' : '' }}>Druge organizacije (Političke partije, Verske zajednice, Komore, Sindikati)</option>
                        </select>
                        @error('registration_form')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                    </div>

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
                            name="company_seat" 
                            class="form-control @error('company_seat') error @enderror"
                            value="{{ old('company_seat', isset($existingApplication) && $existingApplication ? $existingApplication->company_seat : auth()->user()->address) }}"
                            maxlength="255"
                            placeholder="Npr. Kotor, Njegoševa 1"
                            required
                        >
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
                            value="{{ old('pib', isset($existingApplication) && $existingApplication ? $existingApplication->pib : auth()->user()->pib) }}"
                            maxlength="50"
                        >
                        @error('pib')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
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
                                id="accuracy_declaration_1b" 
                                name="accuracy_declaration" 
                                value="1"
                                {{ old('accuracy_declaration', isset($existingApplication) && $existingApplication ? $existingApplication->accuracy_declaration : false) ? 'checked' : '' }}
                                required
                            >
                            <label for="accuracy_declaration_1b">
                                Kao podnosilac prijave pod punom materijalnom i krivičnom odgovornošću izjavljujem da su gore navedeni podaci istiniti.
                                <span class="required">*</span>
                            </label>
                        </div>
                        @error('accuracy_declaration')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            Faza biznisa <span class="required">*</span>
                        </label>
                        <div class="radio-group">
                            <div class="radio-option">
                                <input 
                                    type="radio" 
                                    id="business_stage_zapocinjanje_1b" 
                                    name="business_stage" 
                                    value="započinjanje"
                                    {{ old('business_stage', isset($existingApplication) && $existingApplication ? $existingApplication->business_stage : 'započinjanje') === 'započinjanje' ? 'checked' : '' }}
                                    required
                                >
                                <label for="business_stage_zapocinjanje_1b">Započinjanje poslovne djelatnosti</label>
                            </div>
                            <div class="radio-option">
                                <input 
                                    type="radio" 
                                    id="business_stage_razvoj_1b" 
                                    name="business_stage" 
                                    value="razvoj"
                                    {{ old('business_stage', isset($existingApplication) && $existingApplication ? $existingApplication->business_stage : '') === 'razvoj' ? 'checked' : '' }}
                                    data-required="true"
                                >
                                <label for="business_stage_razvoj_1b">Razvoj postojeće poslovne djelatnosti</label>
                            </div>
                        </div>
                        @error('business_stage')
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
                            value="{{ old('physical_person_jmbg', isset($existingApplication) && $existingApplication ? $existingApplication->physical_person_jmbg : '') }}"
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
                                value="{{ old('physical_person_phone', isset($existingApplication) && $existingApplication ? $existingApplication->physical_person_phone : auth()->user()->phone) }}"
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
                        <label class="form-label">
                            Faza biznisa <span class="required">*</span>
                        </label>
                        <div class="radio-group">
                            <div class="radio-option">
                                <input 
                                    type="radio" 
                                    id="business_stage_zapocinjanje_fizicko" 
                                    name="business_stage" 
                                    value="započinjanje"
                                    {{ old('business_stage', isset($existingApplication) && $existingApplication ? $existingApplication->business_stage : 'započinjanje') === 'započinjanje' ? 'checked' : '' }}
                                    required
                                >
                                <label for="business_stage_zapocinjanje_fizicko">Započinjanje poslovne djelatnosti</label>
                            </div>
                            <div class="radio-option">
                                <input 
                                    type="radio" 
                                    id="business_stage_razvoj_fizicko" 
                                    name="business_stage" 
                                    value="razvoj"
                                    {{ old('business_stage', isset($existingApplication) && $existingApplication ? $existingApplication->business_stage : '') === 'razvoj' ? 'checked' : '' }}
                                    data-required="true"
                                >
                                <label for="business_stage_razvoj_fizicko">Razvoj postojeće poslovne djelatnosti</label>
                            </div>
                        </div>
                        @error('business_stage')
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

            <!-- Sekcija 3: Finansijski podaci -->
            <div class="form-card">
                <div class="form-section">
                    <h2>3. Finansijski podaci</h2>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">
                                Traženi iznos podrške (€) <span class="required">*</span>
                            </label>
                            <input 
                                type="number" 
                                name="requested_amount" 
                                class="form-control @error('requested_amount') error @enderror"
                                value="{{ old('requested_amount', isset($existingApplication) && $existingApplication ? $existingApplication->requested_amount : '') }}"
                                required
                                min="0"
                                step="0.01"
                                placeholder="0.00"
                            >
                            @error('requested_amount')
                                <div class="error-message">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                Maksimalno: {{ number_format(($competition->budget ?? 0) * (($competition->max_support_percentage ?? 30) / 100), 2, ',', '.') }} € 
                                ({{ $competition->max_support_percentage ?? 30 }}% budžeta)
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                Ukupan budžet potreban za realizaciju (€) <span class="required">*</span>
                            </label>
                            <input 
                                type="number" 
                                name="total_budget_needed" 
                                class="form-control @error('total_budget_needed') error @enderror"
                                value="{{ old('total_budget_needed', isset($existingApplication) && $existingApplication ? $existingApplication->total_budget_needed : '') }}"
                                required
                                min="0"
                                step="0.01"
                                placeholder="0.00"
                            >
                            @error('total_budget_needed')
                                <div class="error-message">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                Mora biti veći ili jednak traženom iznosu
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sekcija 4: Dodatni podaci -->
            <div class="form-card">
                <div class="form-section">
                    <h2>4. Dodatni podaci</h2>
                    
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

            <!-- Sekcija 5: Izjave -->
            <div class="form-card">
                <div class="form-section">
                    <h2>5. Izjave</h2>
                    
                    <div class="form-group">
                        <div class="checkbox-group">
                            <input 
                                type="checkbox" 
                                id="de_minimis_declaration" 
                                name="de_minimis_declaration" 
                                value="1"
                                {{ old('de_minimis_declaration', isset($existingApplication) && $existingApplication ? $existingApplication->de_minimis_declaration : false) ? 'checked' : '' }}
                                required
                            >
                            <label for="de_minimis_declaration">
                                Izjavljujem da će ukupna de minimis podrška koju sam dobio/la u posljednje tri godine 
                                biti u skladu sa propisima Evropske unije o de minimis podršci 
                                <span class="required">*</span>
                            </label>
                        </div>
                        @error('de_minimis_declaration')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <div class="checkbox-group">
                            <input 
                                type="checkbox" 
                                id="previous_support_declaration" 
                                name="previous_support_declaration" 
                                value="1"
                                {{ old('previous_support_declaration', isset($existingApplication) && $existingApplication ? $existingApplication->previous_support_declaration : false) ? 'checked' : '' }}
                            >
                            <label for="previous_support_declaration">
                                Prethodno sam dobio/la podršku iz budžeta Opštine Kotor za žensko preduzetništvo po javnom konkursu u prethodnoj godini. 
                                (Ukoliko je ova izjava tačna, priložiću Izvještaj o realizaciji biznis plana (obrazac 4) sa Finansijskim izvještajem (obrazac 4a) o utrošenim sredstvima za prethodnu godinu, sa kopijama računa, ugovora i izvoda banke po kojima su isti plaćeni.)
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dugme za slanje -->
            @if(!$readOnly)
                <div class="form-card" style="text-align: center;">
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
    document.addEventListener('DOMContentLoaded', function() {
        const applicantTypeInputs = document.querySelectorAll('input[name="applicant_type"]');
        const obrazac1a = document.getElementById('obrazac1a');
        const obrazac1b = document.getElementById('obrazac1b');
        const fizickoLiceFields = document.getElementById('fizickoLiceFields');
        const fizickoLiceRequiredFields = fizickoLiceFields ? fizickoLiceFields.querySelectorAll('input[required], input[name="physical_person_name"], input[name="physical_person_jmbg"], input[name="physical_person_phone"], input[name="physical_person_email"]') : [];
        const fizickoLiceNotice = document.getElementById('fizickoLiceNotice');

        function toggleFieldsByApplicantType() {
            const selectedType = document.querySelector('input[name="applicant_type"]:checked')?.value;
            
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
                fizickoLiceNotice.style.display = 'none';
            }

            // Resetuj sekciju za izbor tipa prijave za Fizičko lice (Rezident)
            const fizickoLiceBusinessStage = document.getElementById('fizickoLiceBusinessStage');
            if (fizickoLiceBusinessStage) {
                fizickoLiceBusinessStage.style.display = 'none';
                // Ukloni required sa radio button-a
                const businessStageRadios = fizickoLiceBusinessStage.querySelectorAll('input[name="business_stage"]');
                businessStageRadios.forEach(radio => {
                    radio.removeAttribute('required');
                });
            }

            // Prikaži/sakrij obrazce na osnovu tipa
            if (selectedType === 'preduzetnica') {
                // Preduzetnica - prikaži Obrazac 1a
                if (obrazac1a) {
                    obrazac1a.classList.add('show');
                    // Enable sva polja u obrazac1a
                    const obrazac1aFields = obrazac1a.querySelectorAll('input, select, textarea');
                    obrazac1aFields.forEach(field => {
                        field.removeAttribute('disabled');
                    });
                    // Dodaj required na obavezna polja u obrazac1a
                    const businessPlanName1a = obrazac1a.querySelector('input[name="business_plan_name"]');
                    const businessArea1a = obrazac1a.querySelector('input[name="business_area"]');
                    if (businessPlanName1a) businessPlanName1a.setAttribute('required', 'required');
                    if (businessArea1a) businessArea1a.setAttribute('required', 'required');
                }
            } else if (selectedType === 'doo' || selectedType === 'ostalo') {
                // DOO ili Ostalo - prikaži Obrazac 1b
                if (obrazac1b) {
                    obrazac1b.classList.add('show');
                    // Enable sva polja u obrazac1b
                    const obrazac1bFields = obrazac1b.querySelectorAll('input, select, textarea');
                    obrazac1bFields.forEach(field => {
                        field.removeAttribute('disabled');
                    });
                    // Ažuriraj zaglavlje na osnovu tipa
                    const obrazac1bSubtitle = document.getElementById('obrazac1b-subtitle');
                    if (obrazac1bSubtitle) {
                        if (selectedType === 'doo') {
                            obrazac1bSubtitle.textContent = '(za oblik registracije DOO)';
                        } else if (selectedType === 'ostalo') {
                            obrazac1bSubtitle.textContent = '(za ostale pravne subjekte)';
                        }
                    }
                    // Dodaj required na obavezna polja u obrazac1b
                    const businessPlanName1b = obrazac1b.querySelector('input[name="business_plan_name"]');
                    const businessArea1b = obrazac1b.querySelector('input[name="business_area"]');
                    const founderName = obrazac1b.querySelector('input[name="founder_name"]');
                    const directorName = obrazac1b.querySelector('input[name="director_name"]');
                    const companySeat = obrazac1b.querySelector('input[name="company_seat"]');
                    if (businessPlanName1b && businessPlanName1b.hasAttribute('data-required')) businessPlanName1b.setAttribute('required', 'required');
                    if (businessArea1b && businessArea1b.hasAttribute('data-required')) businessArea1b.setAttribute('required', 'required');
                    if (founderName && founderName.hasAttribute('data-required')) founderName.setAttribute('required', 'required');
                    if (directorName && directorName.hasAttribute('data-required')) directorName.setAttribute('required', 'required');
                    if (companySeat && companySeat.hasAttribute('data-required')) companySeat.setAttribute('required', 'required');
                }
            } else if (selectedType === 'fizicko_lice') {
                // Fizičko lice BEZ registrovane djelatnosti
                // Prikaži napomenu o obavezi registracije
                if (fizickoLiceNotice) {
                    fizickoLiceNotice.style.display = 'block';
                }
                // Prikaži polja za fizičko lice
                if (fizickoLiceFields) {
                    fizickoLiceFields.classList.add('show');
                    // Enable sva polja u fizickoLiceFields
                    const fizickoLiceAllFields = fizickoLiceFields.querySelectorAll('input, select, textarea');
                    fizickoLiceAllFields.forEach(field => {
                        field.removeAttribute('disabled');
                    });
                    // Dodaj required na obavezna polja
                    fizickoLiceRequiredFields.forEach(field => {
                        field.setAttribute('required', 'required');
                    });
                }
            }
        }

        applicantTypeInputs.forEach(input => {
            input.addEventListener('change', function() {
                toggleFieldsByApplicantType();
                setTimeout(setRegistrationForm, 100);
            });
        });

        // Inicijalno disable sva polja u sakrivenim sekcijama
        const allConditionalFields = document.querySelectorAll('.conditional-field');
        allConditionalFields.forEach(section => {
            if (!section.classList.contains('show')) {
                const fields = section.querySelectorAll('input, select, textarea');
                fields.forEach(field => {
                    field.setAttribute('disabled', 'disabled');
                });
            }
        });

        // Pozovi na učitavanju stranice - ali samo nakon što se DOM učita
        // Ovo će prikazati pravilnu sekciju na osnovu applicant_type iz existingApplication
        // VAŽNO: Čekaj da se vrednosti iz existingApplication učitaju u HTML pre pozivanja
        setTimeout(function() {
            toggleFieldsByApplicantType();
            
            // Nakon što se prikaže pravilna sekcija, osiguraj da se vrednosti iz existingApplication očuvaju
            // Proveri da li postoji existingApplication i osiguraj da su vrednosti vidljive
            const hasExistingApplication = {{ isset($existingApplication) && $existingApplication ? 'true' : 'false' }};
            if (hasExistingApplication) {
                // Sačekaj još malo da se sekcija prikaže
                setTimeout(function() {
                    // Osiguraj da su sva polja u aktivnoj sekciji enabled
                    const activeSection = document.querySelector('.conditional-field.show');
                    if (activeSection) {
                        const activeFields = activeSection.querySelectorAll('input, select, textarea');
                        activeFields.forEach(field => {
                            field.removeAttribute('disabled');
                        });
                        
                        // Osiguraj da se business_stage radio button pravilno prikaže
                        const existingBusinessStage = '{{ isset($existingApplication) && $existingApplication && $existingApplication->business_stage ? addslashes($existingApplication->business_stage) : '' }}';
                        if (existingBusinessStage) {
                            const businessStageRadio = activeSection.querySelector(`input[name="business_stage"][value="${existingBusinessStage}"]`);
                            if (businessStageRadio) {
                                businessStageRadio.checked = true;
                                businessStageRadio.removeAttribute('disabled');
                                console.log('Set business_stage to:', existingBusinessStage);
                            }
                        }
                        
                        // Osiguraj da se PIB prikaže - proveri u aktivnoj sekciji
                        const existingPib = '{{ isset($existingApplication) && $existingApplication && $existingApplication->pib ? addslashes($existingApplication->pib) : '' }}';
                        console.log('Existing PIB from server:', existingPib);
                        if (existingPib) {
                            const pibField = activeSection.querySelector('input[name="pib"]');
                            console.log('PIB field found:', pibField ? 'yes' : 'no');
                            if (pibField) {
                                console.log('PIB field current value:', pibField.value);
                                pibField.value = existingPib;
                                pibField.removeAttribute('disabled');
                                console.log('PIB field set to:', pibField.value);
                            } else {
                                // Ako nije nađen u aktivnoj sekciji, proveri u svim sekcijama
                                const allPibFields = form.querySelectorAll('input[name="pib"]');
                                allPibFields.forEach(field => {
                                    if (field.closest('.conditional-field.show')) {
                                        field.value = existingPib;
                                        field.removeAttribute('disabled');
                                        console.log('PIB field set in all sections');
                                    }
                                });
                            }
                        }
                    }
                }, 300);
            }
        }, 200);
        
        // Funkcija za automatsko postavljanje obrasca registracije
        // VAŽNO: Ne prepisuj vrednosti ako postoje iz existingApplication
        function setRegistrationForm() {
            const selectedType = document.querySelector('input[name="applicant_type"]:checked')?.value;
            const userRegistrationForm = '{{ auth()->user()->user_type ?? "" }}';
            
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

        // Funkcija za proveru da li su sva obavezna polja popunjena
        function checkIfObrazacComplete() {
            const form = document.getElementById('applicationForm');
            if (!form) return false;

            // Proveri tip podnosioca
            const applicantType = form.querySelector('input[name="applicant_type"]:checked');
            if (!applicantType) return false;

            const applicantTypeValue = applicantType.value;

            // Osnovna obavezna polja - traži samo u aktivnoj sekciji
            const activeSection = document.querySelector('.conditional-field.show');
            const businessPlanName = activeSection ? activeSection.querySelector('input[name="business_plan_name"]') : form.querySelector('input[name="business_plan_name"]:not([disabled])');
            // Proveri business_stage u svim sekcijama (može biti u obrazac1a, obrazac1b ili fizickoLiceFields)
            const businessStage = form.querySelector('input[name="business_stage"]:checked');
            const businessArea = activeSection ? activeSection.querySelector('input[name="business_area"]') : form.querySelector('input[name="business_area"]:not([disabled])');
            const requestedAmount = form.querySelector('input[name="requested_amount"]:not([disabled])');
            const totalBudgetNeeded = form.querySelector('input[name="total_budget_needed"]:not([disabled])');
            const deMinimisDeclaration = form.querySelector('input[name="de_minimis_declaration"]:not([disabled])');

            // Proveri osnovna polja
            if (!businessPlanName || !businessPlanName.value.trim()) {
                console.log('Business plan name missing or empty');
                return false;
            }
            if (!businessStage || !businessStage.value) {
                console.log('Business stage not selected');
                return false;
            }
            if (!businessArea || !businessArea.value.trim()) return false;
            if (!requestedAmount || !requestedAmount.value || parseFloat(requestedAmount.value) <= 0) return false;
            if (!totalBudgetNeeded || !totalBudgetNeeded.value || parseFloat(totalBudgetNeeded.value) <= 0) return false;
            if (!deMinimisDeclaration || !deMinimisDeclaration.checked) return false;

            // Proveri polja specifična za tip podnosioca
            if (applicantTypeValue === 'fizicko_lice') {
                // Traži polja samo u aktivnoj sekciji (fizickoLiceFields)
                const physicalPersonName = activeSection ? activeSection.querySelector('input[name="physical_person_name"]') : form.querySelector('input[name="physical_person_name"]:not([disabled])');
                const physicalPersonJmbg = activeSection ? activeSection.querySelector('input[name="physical_person_jmbg"]') : form.querySelector('input[name="physical_person_jmbg"]:not([disabled])');
                const physicalPersonPhone = activeSection ? activeSection.querySelector('input[name="physical_person_phone"]') : form.querySelector('input[name="physical_person_phone"]:not([disabled])');
                const physicalPersonEmail = activeSection ? activeSection.querySelector('input[name="physical_person_email"]') : form.querySelector('input[name="physical_person_email"]:not([disabled])');
                const accuracyDeclaration = activeSection ? activeSection.querySelector('input[name="accuracy_declaration"]') : form.querySelector('input[name="accuracy_declaration"]:not([disabled])');

                if (!physicalPersonName || !physicalPersonName.value.trim()) {
                    console.log('Physical person name missing or empty');
                    return false;
                }
                if (!physicalPersonJmbg || !physicalPersonJmbg.value.trim()) {
                    console.log('Physical person JMBG missing or empty');
                    return false;
                }
                if (!physicalPersonPhone || !physicalPersonPhone.value.trim()) {
                    console.log('Physical person phone missing or empty');
                    return false;
                }
                if (!physicalPersonEmail || !physicalPersonEmail.value.trim()) {
                    console.log('Physical person email missing or empty');
                    return false;
                }
                if (!accuracyDeclaration || !accuracyDeclaration.checked) {
                    console.log('Accuracy declaration not checked');
                    return false;
                }
            } else if (applicantTypeValue === 'doo' || applicantTypeValue === 'ostalo') {
                // Traži polja samo u aktivnoj sekciji (Obrazac 1b)
                const founderName = activeSection ? activeSection.querySelector('input[name="founder_name"]') : form.querySelector('input[name="founder_name"]:not([disabled])');
                const directorName = activeSection ? activeSection.querySelector('input[name="director_name"]') : form.querySelector('input[name="director_name"]:not([disabled])');
                const companySeat = activeSection ? activeSection.querySelector('input[name="company_seat"]') : form.querySelector('input[name="company_seat"]:not([disabled])');
                const registrationForm = activeSection ? activeSection.querySelector('select[name="registration_form"]') : form.querySelector('select[name="registration_form"]:not([disabled])');

                if (!founderName || !founderName.value.trim()) {
                    console.log('Founder name missing or empty');
                    return false;
                }
                if (!directorName || !directorName.value.trim()) {
                    console.log('Director name missing or empty');
                    return false;
                }
                if (!companySeat || !companySeat.value.trim()) {
                    console.log('Company seat missing or empty');
                    return false;
                }
                if (!registrationForm || !registrationForm.value) {
                    console.log('Registration form missing or empty');
                    return false;
                }
            } else if (applicantTypeValue === 'preduzetnica') {
                // Traži registration_form samo u aktivnoj sekciji (Obrazac 1a)
                const registrationForm = activeSection ? activeSection.querySelector('select[name="registration_form"]') : form.querySelector('select[name="registration_form"]:not([disabled])');
                if (!registrationForm || !registrationForm.value) {
                    console.log('Registration form missing or empty for preduzetnica');
                    return false;
                }
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
                submitBtn.textContent = 'Sačuvaj prijavu';
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
        const form = document.getElementById('applicationForm');
        if (form) {
            // Dugme "Sačuvaj kao nacrt" - ručno submit-uj formu bez validacije
            const saveAsDraftBtn = document.getElementById('saveAsDraftBtn');
            if (saveAsDraftBtn) {
                saveAsDraftBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // Dodaj hidden input za save_as_draft
                    let draftInput = form.querySelector('input[name="save_as_draft"]');
                    if (!draftInput) {
                        draftInput = document.createElement('input');
                        draftInput.type = 'hidden';
                        draftInput.name = 'save_as_draft';
                        draftInput.value = '1';
                        form.appendChild(draftInput);
                    }
                    
                    // Ukloni sve required atribute
                    const allRequiredFields = form.querySelectorAll('[required]');
                    allRequiredFields.forEach(field => {
                        field.removeAttribute('required');
                    });
                    
                    // VAŽNO: Jednostavniji pristup - ukloni disabled samo iz aktivne sekcije i postavi disabled u sakrivenim
                    // Ovo je sigurniji način jer ne kopiramo vrednosti, već samo kontrolišemo koja se šalju
                    
                    // 1. Osiguraj da se applicant_type šalje - ukloni disabled sa svih
                    const allApplicantTypeRadios = form.querySelectorAll('input[name="applicant_type"]');
                    allApplicantTypeRadios.forEach(radio => {
                        radio.removeAttribute('disabled');
                    });
                    const checkedApplicantTypeRadio = form.querySelector('input[name="applicant_type"][type="radio"]:checked');
                    if (!checkedApplicantTypeRadio) {
                        const defaultApplicantTypeRadio = form.querySelector('input[name="applicant_type"][type="radio"][value="preduzetnica"]') 
                            || form.querySelector('input[name="applicant_type"][type="radio"]');
                        if (defaultApplicantTypeRadio) {
                            defaultApplicantTypeRadio.checked = true;
                        }
                    }
                    
                    // 2. PRVO proveri checked status za business_stage u aktivnoj sekciji PRE uklanjanja disabled
                    const activeSection = document.querySelector('.conditional-field.show');
                    let checkedBusinessStageValue = null;
                    if (activeSection) {
                        const checkedBusinessStageInActive = activeSection.querySelector('input[name="business_stage"][type="radio"]:checked');
                        if (checkedBusinessStageInActive) {
                            checkedBusinessStageValue = checkedBusinessStageInActive.value;
                            console.log('Found checked business_stage in active section:', checkedBusinessStageValue);
                        }
                    }
                    
                    // 3. Ukloni disabled sa svih polja u aktivnoj sekciji
                    if (activeSection) {
                        const activeFields = activeSection.querySelectorAll('input, select, textarea');
                        activeFields.forEach(field => {
                            field.removeAttribute('disabled');
                        });
                    }
                    
                    // 4. Postavi disabled na sva polja u sakrivenim sekcijama (osim applicant_type)
                    // VAŽNO: Osiguraj da se business_plan_name, business_area, registration_form i polja specifična za Obrazac 1b šalju samo iz aktivne sekcije
                    const allBusinessPlanNames = form.querySelectorAll('input[name="business_plan_name"]');
                    const allBusinessAreas = form.querySelectorAll('input[name="business_area"]');
                    const allRegistrationForms = form.querySelectorAll('select[name="registration_form"]');
                    const allFounderNames = form.querySelectorAll('input[name="founder_name"]');
                    const allDirectorNames = form.querySelectorAll('input[name="director_name"]');
                    const allCompanySeats = form.querySelectorAll('input[name="company_seat"]');
                    
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
                    
                    // 5. Osiguraj da se business_stage šalje - ukloni disabled sa svih radio button-a u aktivnoj sekciji
                    // Browser će poslati samo one koji nisu disabled
                    if (activeSection) {
                        const businessStageRadiosInActive = activeSection.querySelectorAll('input[name="business_stage"][type="radio"]');
                        businessStageRadiosInActive.forEach(radio => {
                            radio.removeAttribute('disabled');
                        });
                    }
                    
                    // 6. Postavi checked status na prvi business_stage radio button u formi sa sačuvanom vrednošću
                    if (checkedBusinessStageValue) {
                        console.log('Setting business_stage to:', checkedBusinessStageValue);
                        const allBusinessStageRadios = form.querySelectorAll('input[name="business_stage"][type="radio"]');
                        allBusinessStageRadios.forEach(radio => {
                            if (radio.value === checkedBusinessStageValue) {
                                radio.checked = true;
                                radio.removeAttribute('disabled');
                            } else {
                                radio.checked = false;
                            }
                        });
                    } else {
                        // Ako nije bilo checked u aktivnoj sekciji, proveri da li postoji checked negde
                        const checkedBusinessStageRadio = form.querySelector('input[name="business_stage"][type="radio"]:checked');
                        if (!checkedBusinessStageRadio) {
                            // Ako nijedan nije checked, postavi prvi (default "započinjanje")
                            const firstBusinessStageRadio = form.querySelector('input[name="business_stage"][type="radio"]');
                            if (firstBusinessStageRadio) {
                                firstBusinessStageRadio.checked = true;
                                firstBusinessStageRadio.removeAttribute('disabled');
                                console.log('No checked business_stage found, setting default to započinjanje');
                            }
                        } else {
                            // Ako postoji checked, osiguraj da nije disabled
                            checkedBusinessStageRadio.removeAttribute('disabled');
                        }
                    }
                    
                    // Submit-uj formu
                    form.submit();
                });
            }
            
            // Normalan submit (za "Sačuvaj prijavu" dugme) - samo ako nije readOnly
            form.addEventListener('submit', function(e) {
                // VAŽNO: Ukloni save_as_draft hidden input ako postoji
                // (ovo osigurava da se forma ne šalje kao draft kada je obrazac kompletan)
                const saveAsDraftInput = form.querySelector('input[name="save_as_draft"]');
                if (saveAsDraftInput) {
                    saveAsDraftInput.remove();
                    console.log('Removed save_as_draft input before submit');
                }
                
                // VAŽNO: Ukloni disabled sa radio button-a za applicant_type
                const allApplicantTypeRadios = form.querySelectorAll('input[name="applicant_type"]');
                allApplicantTypeRadios.forEach(radio => {
                    radio.removeAttribute('disabled');
                });
                
                // VAŽNO: Ukloni disabled sa radio button-a za business_stage u svim sekcijama PRVO
                const allBusinessStageRadios = form.querySelectorAll('input[name="business_stage"]');
                allBusinessStageRadios.forEach(radio => {
                    radio.removeAttribute('disabled');
                });
                
                // VAŽNO: Osiguraj da se registration_form i business_plan_name šalju iz aktivne sekcije
                const activeSection = document.querySelector('.conditional-field.show');
                const selectedType = document.querySelector('input[name="applicant_type"]:checked')?.value;
                
                // Pronađi SVE registration_form select-e, business_plan_name input-e, business_area input-e i polja specifična za Obrazac 1b
                const allRegistrationForms = form.querySelectorAll('select[name="registration_form"]');
                const allBusinessPlanNames = form.querySelectorAll('input[name="business_plan_name"]');
                const allBusinessAreas = form.querySelectorAll('input[name="business_area"]');
                const allFounderNames = form.querySelectorAll('input[name="founder_name"]');
                const allDirectorNames = form.querySelectorAll('input[name="director_name"]');
                const allCompanySeats = form.querySelectorAll('input[name="company_seat"]');
                
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
                        const fieldsToKeepDisabled = ['registration_form', 'business_plan_name', 'business_area', 'founder_name', 'director_name', 'company_seat'];
                        if (fieldsToKeepDisabled.includes(field.name)) {
                            return; // Već postavljen na disabled
                        }
                        field.removeAttribute('required');
                        field.removeAttribute('disabled');
                    });
                });
            });
        }
        @endif
    });
</script>
@endsection

