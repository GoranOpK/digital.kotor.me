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


        <form method="POST" action="{{ route('applications.store', $competition) }}" id="applicationForm">
            @csrf

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
                            $userType = auth()->user()->user_type;
                            $defaultType = 'preduzetnica';
                            
                            if (str_contains($userType, 'Društvo sa ograničenom odgovornošću') || str_contains($userType, 'DOO')) {
                                $defaultType = 'doo';
                            } elseif ($userType === 'Fizičko lice' || $userType === 'Preduzetnik') {
                                $defaultType = 'preduzetnica';
                            } else {
                                // Za ostale pravne subjekte (NVO, AD, itd.) koristimo DOO obrazac jer su pravna lica
                                $defaultType = 'doo';
                            }
                        @endphp
                        <div class="radio-group">
                            <div class="radio-option">
                                <input 
                                    type="radio" 
                                    id="applicant_type_fizicko_lice" 
                                    name="applicant_type" 
                                    value="fizicko_lice"
                                    {{ old('applicant_type', $defaultType) === 'fizicko_lice' ? 'checked' : '' }}
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
                                    {{ old('applicant_type', $defaultType) === 'preduzetnica' ? 'checked' : '' }}
                                    required
                                >
                                <label for="applicant_type_preduzetnica">Preduzetnica</label>
                            </div>
                            <div class="radio-option">
                                <input 
                                    type="radio" 
                                    id="applicant_type_doo" 
                                    name="applicant_type" 
                                    value="doo"
                                    {{ old('applicant_type', $defaultType) === 'doo' ? 'checked' : '' }}
                                    required
                                >
                                <label for="applicant_type_doo">DOO (Društvo sa ograničenom odgovornošću)</label>
                            </div>
                            <div class="radio-option">
                                <input 
                                    type="radio" 
                                    id="applicant_type_ostalo" 
                                    name="applicant_type" 
                                    value="ostalo"
                                    {{ old('applicant_type', $defaultType) === 'ostalo' ? 'checked' : '' }}
                                    required
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
                            value="{{ old('business_plan_name') }}"
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
                            value="{{ old('preduzetnik_name', auth()->user()->name) }}"
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
                                value="{{ old('preduzetnik_jmbg', auth()->user()->jmb) }}"
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
                                value="{{ old('preduzetnik_phone', auth()->user()->phone) }}"
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
                                value="{{ old('preduzetnik_address', auth()->user()->address) }}"
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
                                value="{{ old('preduzetnik_email', auth()->user()->email) }}"
                                maxlength="255"
                            >
                            @error('preduzetnik_email')
                                <div class="error-message">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">*Broj registracije u CRPS:</label>
                            <input 
                                type="text" 
                                name="crps_number" 
                                class="form-control @error('crps_number') error @enderror"
                                value="{{ old('crps_number') }}"
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
                                value="{{ old('pib', auth()->user()->pib) }}"
                                maxlength="50"
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
                            value="{{ old('business_area') }}"
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
                                {{ old('accuracy_declaration') ? 'checked' : '' }}
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
                                    {{ old('business_stage', 'započinjanje') === 'započinjanje' ? 'checked' : '' }}
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
                                    {{ old('business_stage') === 'razvoj' ? 'checked' : '' }}
                                    required
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
                            value="{{ old('business_plan_name') }}"
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
                            value="{{ old('doo_name', auth()->user()->name) }}"
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
                                value="{{ old('doo_jmbg', auth()->user()->jmb) }}"
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
                                value="{{ old('doo_phone', auth()->user()->phone) }}"
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
                                value="{{ old('doo_address', auth()->user()->address) }}"
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
                                value="{{ old('doo_email', auth()->user()->email) }}"
                                maxlength="255"
                            >
                            @error('doo_email')
                                <div class="error-message">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">*Broj registracije u CRPS:</label>
                        <input 
                            type="text" 
                            name="crps_number" 
                            class="form-control @error('crps_number') error @enderror"
                            value="{{ old('crps_number') }}"
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
                                value="{{ old('founder_name', auth()->user()->name) }}"
                                maxlength="255"
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
                                value="{{ old('director_name', auth()->user()->name) }}"
                                maxlength="255"
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
                            value="{{ old('company_seat', auth()->user()->address) }}"
                            maxlength="255"
                            placeholder="Npr. Kotor, Njegoševa 1"
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
                            value="{{ old('pib', auth()->user()->pib) }}"
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
                            value="{{ old('business_area') }}"
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
                                {{ old('accuracy_declaration') ? 'checked' : '' }}
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
                                    {{ old('business_stage', 'započinjanje') === 'započinjanje' ? 'checked' : '' }}
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
                                    {{ old('business_stage') === 'razvoj' ? 'checked' : '' }}
                                    required
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
                            value="{{ old('business_plan_name') }}"
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
                            value="{{ old('physical_person_name', auth()->user()->name) }}"
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
                            value="{{ old('physical_person_jmbg') }}"
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
                                value="{{ old('physical_person_phone', auth()->user()->phone) }}"
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
                                value="{{ old('physical_person_email', auth()->user()->email) }}"
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
                                    {{ old('business_stage', 'započinjanje') === 'započinjanje' ? 'checked' : '' }}
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
                                    {{ old('business_stage') === 'razvoj' ? 'checked' : '' }}
                                    required
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
                            value="{{ old('business_area') }}"
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
                                value="{{ old('requested_amount') }}"
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
                                value="{{ old('total_budget_needed') }}"
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
                                value="{{ old('bank_account') }}"
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
                                value="{{ old('vat_number') }}"
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
                            value="{{ old('website') }}"
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
                                {{ old('de_minimis_declaration') ? 'checked' : '' }}
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
                                {{ old('previous_support_declaration') ? 'checked' : '' }}
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
            <div class="form-card" style="text-align: center;">
                <button type="submit" class="btn-primary">
                    Nastavi na biznis plan
                </button>
                <p style="color: #6b7280; font-size: 14px; margin-top: 16px;">
                    Nakon čuvanja osnovnih podataka, bićete preusmereni na formu za popunjavanje biznis plana.
                </p>
            </div>
        </form>
    </div>
</div>

<script>
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
            
            // Resetuj sve obrazce
            if (obrazac1a) {
                obrazac1a.classList.remove('show');
            }
            if (obrazac1b) {
                obrazac1b.classList.remove('show');
            }
            if (fizickoLiceFields) {
                fizickoLiceFields.classList.remove('show');
                fizickoLiceRequiredFields.forEach(field => {
                    field.removeAttribute('required');
                });
            }

            // Resetuj napomenu
            if (fizickoLiceNotice) {
                fizickoLiceNotice.style.display = 'none';
            }

            // Prikaži/sakrij obrazce na osnovu tipa
            if (selectedType === 'preduzetnica') {
                // Preduzetnica - prikaži Obrazac 1a
                if (obrazac1a) {
                    obrazac1a.classList.add('show');
                }
            } else if (selectedType === 'doo' || selectedType === 'ostalo') {
                // DOO ili Ostalo - prikaži Obrazac 1b
                if (obrazac1b) {
                    obrazac1b.classList.add('show');
                    // Ažuriraj zaglavlje na osnovu tipa
                    const obrazac1bSubtitle = document.getElementById('obrazac1b-subtitle');
                    if (obrazac1bSubtitle) {
                        if (selectedType === 'doo') {
                            obrazac1bSubtitle.textContent = '(za oblik registracije DOO)';
                        } else if (selectedType === 'ostalo') {
                            obrazac1bSubtitle.textContent = '(za ostale pravne subjekte)';
                        }
                    }
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
                    fizickoLiceRequiredFields.forEach(field => {
                        field.setAttribute('required', 'required');
                    });
                }
            }
        }

        applicantTypeInputs.forEach(input => {
            input.addEventListener('change', toggleFieldsByApplicantType);
        });

        // Pozovi na učitavanju stranice
        toggleFieldsByApplicantType();
    });
</script>
@endsection

