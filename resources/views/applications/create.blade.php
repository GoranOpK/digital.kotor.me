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
        margin-top: 2px;
        cursor: pointer;
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

        <!-- Prioriteti za raspodjelu sredstava -->
        <div class="form-card" style="background: #f0f9ff; border-left: 4px solid var(--primary); margin-bottom: 24px;">
            <h2 style="color: var(--primary); font-size: 18px; margin-bottom: 12px;">Prioriteti za raspodjelu sredstava</h2>
            <p style="color: #374151; line-height: 1.6; margin-bottom: 12px; font-size: 14px;">
                Sredstva opredijeljena Budžetom Opštine Kotor raspodjeljuju se za biznis planove koji:
            </p>
            <ul style="color: #374151; line-height: 1.6; margin: 0; padding-left: 20px; font-size: 14px;">
                <li>Podstiču ekonomski razvoj opštine (započinjanje biznisa, povećanje zaposlenosti i kreiranje novih radnih mjesta, smanjenje sive ekonomije, povećanje životnog standarda, razvoj lokalne zajednice, osvajanje novih tržišta i povećanje konkurentnosti, kreiranje nove ponude, osnaživanje žena u biznisu itd);</li>
                <li>Podstiču razvoj turizma (naročito razvoj ruralnog turizma - pružanje usluga u seoskom domaćinstvu, etno sela, turistička valorizacija kulturnog potencijala, tradicije i kulturne posebnosti, bogatija i raznovrsnija turistička ponuda);</li>
                <li>Podstiču razvoj trgovine;</li>
                <li>Podstiču razvoj kreativnih industrija (aktivnosti koje su bazirane na individualnoj kreativnosti, vještini i talentu: zanati, arhitektura, umjetnost, dizajn, produkcija, mediji, izdavaštvo, razvoj software-a);</li>
                <li>Podstiču razvoj start-up-ova (inovativnih tehnoloških biznisa koji imaju potencijal brzog rasta i velikih dometa);</li>
                <li>Doprinose razvoju fizičke kulture i sporta i zdravih stilova života;</li>
                <li>Doprinose očuvanju životne sredine i održivog razvoja.</li>
            </ul>
        </div>

        <!-- Biznis planovi koji se neće podržati -->
        <div class="form-card" style="background: #fef2f2; border-left: 4px solid #ef4444; margin-bottom: 24px;">
            <h2 style="color: #991b1b; font-size: 18px; margin-bottom: 12px;">Biznis planovi koji se neće podržati</h2>
            <ul style="color: #374151; line-height: 1.6; margin: 0; padding-left: 20px; font-size: 14px;">
                <li>Aktivnosti koje su u nadležnosti ili odgovornosti Vlade, kao što je formalno obrazovanje, formalna zdravstvena zaštita i sl.;</li>
                <li>Biznis planovi kojim se traže finansijska sredstva za kupovinu i raspodjelu humanitarne pomoći;</li>
                <li>Biznis planovi koji se isključivo temelje na jednokratnoj izradi, pripremi i štampanju knjiga, brošura, biltena, časopisa i slično, ukoliko objava takvih publikacija nije dio nekog šireg programa ili sveobuhvatnijih i kontinuiranih aktivnosti;</li>
                <li>Aktivnost koja se smatra nezakonitom ili štetnom po okolinu i opasnom za ljudsko zdravlje: igre na sreću, duvan, alkoholna pića (izuzev proizvodnje vina i voćnih rakija).</li>
            </ul>
        </div>

        <form method="POST" action="{{ route('applications.store', $competition) }}" id="applicationForm">
            @csrf

            <!-- Sekcija 1: Osnovni podaci o biznis planu -->
            <div class="form-card">
                <div class="form-section">
                    <h2>1. Osnovni podaci o biznis planu</h2>
                    
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
                                <label for="applicant_type_preduzetnica">Preduzetnica (fizičko lice sa registrovanom djelatnošću)</label>
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

                    <!-- Sekcija za Fizičko lice (nema registrovanu djelatnost) -->
                    <div class="conditional-field" id="fizickoLiceFields">
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

                        <!-- Napomena o obavezi registracije -->
                        <div class="alert alert-info" style="margin-top: 20px;">
                            <strong>Važno:</strong> Ukoliko podnosioc biznis plana nema registrovanu djelatnost, u slučaju da joj sredstva budu odobrena u obavezi je da svoju djelatnost registruje u neki od oblika registracije koji predviđa Zakon o privrednim društvima i priloži dokaz (rješenje o registraciji u CRPS i rješenje o registraciji PJ Uprave prihoda i carina), najkasnije do dana potpisivanja ugovora.
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
                                    id="business_stage_zapocinjanje" 
                                    name="business_stage" 
                                    value="započinjanje"
                                    {{ old('business_stage', 'započinjanje') === 'započinjanje' ? 'checked' : '' }}
                                    required
                                >
                                <label for="business_stage_zapocinjanje">Započinjanje poslovne djelatnosti</label>
                            </div>
                            <div class="radio-option">
                                <input 
                                    type="radio" 
                                    id="business_stage_razvoj" 
                                    name="business_stage" 
                                    value="razvoj"
                                    {{ old('business_stage') === 'razvoj' ? 'checked' : '' }}
                                    required
                                >
                                <label for="business_stage_razvoj">Razvoj postojeće poslovne djelatnosti</label>
                            </div>
                        </div>
                        @error('business_stage')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            Oblast biznisa <span class="required">*</span>
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

                    <div class="form-group">
                        <label class="form-label">
                            Imate li registrovanu djelatnost u skladu sa Zakonom o privrednim društvima? <span class="required">*</span>
                        </label>
                        <div class="radio-group">
                            <div class="radio-option">
                                <input 
                                    type="radio" 
                                    id="is_registered_yes" 
                                    name="is_registered" 
                                    value="1"
                                    {{ old('is_registered', '1') === '1' ? 'checked' : '' }}
                                    required
                                >
                                <label for="is_registered_yes">Da</label>
                            </div>
                            <div class="radio-option">
                                <input 
                                    type="radio" 
                                    id="is_registered_no" 
                                    name="is_registered" 
                                    value="0"
                                    {{ old('is_registered') === '0' ? 'checked' : '' }}
                                    required
                                >
                                <label for="is_registered_no">Ne</label>
                            </div>
                        </div>
                        @error('is_registered')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Upozorenje za neregistrovanu djelatnost -->
                    <div class="alert alert-info conditional-field" id="unregisteredInfo" style="display: none;">
                        <strong>Važno:</strong> Ukoliko nemate registrovanu djelatnost, u slučaju da vam sredstva budu odobrena, 
                        u obavezi ste da svoju djelatnost registrujete u neki od oblika registracije koji predviđa 
                        Zakon o privrednim društvima i priložite dokaz (rješenje o registraciji u CRPS i rješenje o 
                        registraciji PJ Uprave prihoda i carina), najkasnije do dana potpisivanja ugovora.
                    </div>
                </div>
            </div>

            <!-- Sekcija 2: Podaci o DOO (samo ako je izabran DOO) -->
            <div class="form-card conditional-field" id="dooFields">
                <div class="form-section">
                    <h2>2. Podaci o DOO</h2>
                    
                    <div class="form-group">
                        <label class="form-label">
                            Ime i prezime osnivača/ice <span class="required">*</span>
                        </label>
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
                        <label class="form-label">
                            Ime i prezime izvršnog direktora/ice <span class="required">*</span>
                        </label>
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

                    <div class="form-group">
                        <label class="form-label">
                            Sjedište društva <span class="required">*</span>
                        </label>
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
                                Prethodno sam dobio/la podršku iz budžeta Opštine Kotor za žensko preduzetništvo
                            </label>
                        </div>
                    </div>

                    <!-- Izjava o tačnosti podataka (samo ako nema registrovanu djelatnost) -->
                    <div class="form-group conditional-field" id="accuracyDeclarationGroup" style="display: none;">
                        <div class="checkbox-group">
                            <input 
                                type="checkbox" 
                                id="accuracy_declaration" 
                                name="accuracy_declaration" 
                                value="1"
                                {{ old('accuracy_declaration') ? 'checked' : '' }}
                            >
                            <label for="accuracy_declaration">
                                Izjavljujem da za tačnost datih podataka odgovaram kao podnosioc prijave 
                                <span class="required">*</span>
                            </label>
                        </div>
                        @error('accuracy_declaration')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
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
    // VAŽNO: 'fizicko_lice' = Fizičko lice BEZ registrovane djelatnosti
    //        'preduzetnica' = Fizičko lice SA registrovanom djelatnošću (preduzetnik)
    document.addEventListener('DOMContentLoaded', function() {
        const applicantTypeInputs = document.querySelectorAll('input[name="applicant_type"]');
        const dooFields = document.getElementById('dooFields');
        const dooRequiredFields = dooFields ? dooFields.querySelectorAll('input[required]') : [];
        const fizickoLiceFields = document.getElementById('fizickoLiceFields');
        const fizickoLiceRequiredFields = fizickoLiceFields ? fizickoLiceFields.querySelectorAll('input[required], input[name="physical_person_name"], input[name="physical_person_jmbg"], input[name="physical_person_phone"], input[name="physical_person_email"]') : [];
        // Pronađi form-group koji sadrži polje za registrovanu djelatnost
        const isRegisteredInputs = document.querySelectorAll('input[name="is_registered"]');
        const isRegisteredGroup = isRegisteredInputs.length > 0 ? isRegisteredInputs[0].closest('.form-group') : null;
        const unregisteredInfo = document.getElementById('unregisteredInfo');
        const accuracyDeclarationGroup = document.getElementById('accuracyDeclarationGroup');
        const accuracyDeclarationCheckbox = document.getElementById('accuracy_declaration');

        function toggleFieldsByApplicantType() {
            const selectedType = document.querySelector('input[name="applicant_type"]:checked')?.value;
            
            // Resetuj sve polja
            if (dooFields) {
                dooFields.classList.remove('show');
                dooRequiredFields.forEach(field => {
                    field.removeAttribute('required');
                });
            }
            
            if (fizickoLiceFields) {
                fizickoLiceFields.classList.remove('show');
                fizickoLiceRequiredFields.forEach(field => {
                    field.removeAttribute('required');
                });
            }

            // Prikaži/sakrij polja na osnovu tipa
            if (selectedType === 'doo') {
                if (dooFields) {
                    dooFields.classList.add('show');
                    dooRequiredFields.forEach(field => {
                        field.setAttribute('required', 'required');
                    });
                }
                // Prikaži polje za registrovanu djelatnost (DOO može imati registrovanu djelatnost)
                if (isRegisteredGroup) {
                    isRegisteredGroup.style.display = 'block';
                }
            } else if (selectedType === 'fizicko_lice') {
                // Fizičko lice BEZ registrovane djelatnosti
                if (fizickoLiceFields) {
                    fizickoLiceFields.classList.add('show');
                    fizickoLiceRequiredFields.forEach(field => {
                        field.setAttribute('required', 'required');
                    });
                }
                // Sakrij polje za registrovanu djelatnost jer fizičko lice automatski nema registrovanu djelatnost
                if (isRegisteredGroup) {
                    isRegisteredGroup.style.display = 'none';
                }
                // Automatski postavi na "Ne" (nema registrovanu djelatnost)
                const noRegisteredInput = document.getElementById('is_registered_no');
                if (noRegisteredInput) {
                    noRegisteredInput.checked = true;
                }
                // Prikaži izjavu o tačnosti (obavezna za fizička lica bez registrovane djelatnosti)
                if (accuracyDeclarationGroup) {
                    accuracyDeclarationGroup.style.display = 'block';
                }
                if (accuracyDeclarationCheckbox) {
                    accuracyDeclarationCheckbox.setAttribute('required', 'required');
                }
            } else {
                // Preduzetnica (fizičko lice SA registrovanom djelatnošću) ili Ostalo - prikaži polje za registrovanu djelatnost
                if (isRegisteredGroup) {
                    isRegisteredGroup.style.display = 'block';
                }
            }
        }

        applicantTypeInputs.forEach(input => {
            input.addEventListener('change', toggleFieldsByApplicantType);
        });

        // Pozovi na učitavanju stranice
        toggleFieldsByApplicantType();

        // Dinamičko prikazivanje/sakrivanje polja za neregistrovanu djelatnost
        function toggleRegistrationFields() {
            const selectedType = document.querySelector('input[name="applicant_type"]:checked')?.value;
            
            // Ako je fizičko lice BEZ registrovane djelatnosti, automatski je neregistrovano
            // (polje za registrovanu djelatnost je već sakriveno u toggleFieldsByApplicantType)
            if (selectedType === 'fizicko_lice') {
                if (unregisteredInfo) {
                    unregisteredInfo.style.display = 'none'; // Već je prikazana napomena u sekciji za fizičko lice
                }
                return;
            }

            const selectedRegistration = document.querySelector('input[name="is_registered"]:checked')?.value;
            
            if (selectedRegistration === '0') {
                // Nema registrovanu djelatnost
                if (unregisteredInfo) {
                    unregisteredInfo.style.display = 'block';
                }
                if (accuracyDeclarationGroup) {
                    accuracyDeclarationGroup.style.display = 'block';
                }
                if (accuracyDeclarationCheckbox) {
                    accuracyDeclarationCheckbox.setAttribute('required', 'required');
                }
            } else {
                // Ima registrovanu djelatnost
                if (unregisteredInfo) {
                    unregisteredInfo.style.display = 'none';
                }
                if (accuracyDeclarationGroup) {
                    accuracyDeclarationGroup.style.display = 'none';
                }
                if (accuracyDeclarationCheckbox) {
                    accuracyDeclarationCheckbox.removeAttribute('required');
                }
            }
        }

        isRegisteredInputs.forEach(input => {
            input.addEventListener('change', toggleRegistrationFields);
        });

        // Pozovi na učitavanju stranice
        toggleRegistrationFields();
    });
</script>
@endsection

