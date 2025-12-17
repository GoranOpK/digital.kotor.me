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
                        <div class="radio-group">
                            <div class="radio-option">
                                <input 
                                    type="radio" 
                                    id="applicant_type_preduzetnica" 
                                    name="applicant_type" 
                                    value="preduzetnica"
                                    {{ old('applicant_type', 'preduzetnica') === 'preduzetnica' ? 'checked' : '' }}
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
                                    {{ old('applicant_type') === 'doo' ? 'checked' : '' }}
                                    required
                                >
                                <label for="applicant_type_doo">DOO (Društvo sa ograničenom odgovornošću)</label>
                            </div>
                        </div>
                        @error('applicant_type')
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
                                    id="business_stage_zapocinjanje" 
                                    name="business_stage" 
                                    value="započinjanje"
                                    {{ old('business_stage', 'započinjanje') === 'započinjanje' ? 'checked' : '' }}
                                    required
                                >
                                <label for="business_stage_zapocinjanje">Započinjanje poslovne delatnosti</label>
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
                                <label for="business_stage_razvoj">Razvoj postojeće poslovne delatnosti</label>
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
                            value="{{ old('founder_name') }}"
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
                            value="{{ old('director_name') }}"
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
                            value="{{ old('company_seat') }}"
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
    // Dinamičko prikazivanje/sakrivanje polja za DOO
    document.addEventListener('DOMContentLoaded', function() {
        const applicantTypeInputs = document.querySelectorAll('input[name="applicant_type"]');
        const dooFields = document.getElementById('dooFields');
        const dooRequiredFields = dooFields.querySelectorAll('input[required]');

        function toggleDooFields() {
            const selectedType = document.querySelector('input[name="applicant_type"]:checked')?.value;
            
            if (selectedType === 'doo') {
                dooFields.classList.add('show');
                dooRequiredFields.forEach(field => {
                    field.setAttribute('required', 'required');
                });
            } else {
                dooFields.classList.remove('show');
                dooRequiredFields.forEach(field => {
                    field.removeAttribute('required');
                });
            }
        }

        applicantTypeInputs.forEach(input => {
            input.addEventListener('change', toggleDooFields);
        });

        // Pozovi na učitavanju stranice
        toggleDooFields();
    });
</script>
@endsection

