@extends('layouts.app')

@section('content')
<style>
    :root {
        --primary: #0B3D91;
        --primary-dark: #0A347B;
        --secondary: #B8860B;
    }
    .business-plan-page {
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
        font-family: inherit;
    }
    textarea.form-control {
        min-height: 120px;
        resize: vertical;
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
    .error-message {
        color: #ef4444;
        font-size: 12px;
        margin-top: 4px;
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
    .section-number {
        display: inline-block;
        width: 28px;
        height: 28px;
        background: var(--primary);
        color: #fff;
        border-radius: 50%;
        text-align: center;
        line-height: 28px;
        font-weight: 700;
        font-size: 14px;
        margin-right: 12px;
    }
</style>

<div class="business-plan-page">
    <div class="container mx-auto px-4">
        <div class="page-header">
            <h1>Biznis plan - Obrazac 2</h1>
            <p style="color: rgba(255,255,255,0.9); margin: 0;">{{ $application->business_plan_name ?? 'Biznis plan' }}</p>
        </div>

        @if(session('success'))
            <div class="alert alert-info">
                {{ session('success') }}
            </div>
        @endif

        <form method="POST" action="{{ route('applications.business-plan.store', $application) }}" id="businessPlanForm">
            @csrf

            <!-- I. OSNOVNI PODACI -->
            <div class="form-card">
                <div class="form-section">
                    <h2>
                        <span class="section-number">I</span>
                        Osnovni podaci
                    </h2>
                    
                    <div class="form-group">
                        <label class="form-label">
                            Naziv biznis ideje <span class="required">*</span>
                        </label>
                        <input 
                            type="text" 
                            name="business_idea_name" 
                            class="form-control @error('business_idea_name') error @enderror"
                            value="{{ old('business_idea_name', $businessPlan->business_idea_name ?? '') }}"
                            required
                            maxlength="255"
                        >
                        @error('business_idea_name')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            Podaci o podnosiocu <span class="required">*</span>
                        </label>
                        <textarea 
                            name="applicant_data" 
                            class="form-control @error('applicant_data') error @enderror"
                            required
                        >{{ old('applicant_data', $businessPlan->applicant_data ?? '') }}</textarea>
                        @error('applicant_data')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Unesite detaljne podatke o podnosiocu prijave (ime, prezime, JMB, adresa, kontakt...)</div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            Podaci o registrovanoj djelatnosti <span class="required">*</span>
                        </label>
                        <textarea 
                            name="registered_activity_data" 
                            class="form-control @error('registered_activity_data') error @enderror"
                            required
                        >{{ old('registered_activity_data', $businessPlan->registered_activity_data ?? '') }}</textarea>
                        @error('registered_activity_data')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Unesite podatke o registrovanoj djelatnosti (šifra djelatnosti, datum registracije, status...)</div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            Rezime <span class="required">*</span>
                        </label>
                        <textarea 
                            name="summary" 
                            class="form-control @error('summary') error @enderror"
                            required
                        >{{ old('summary', $businessPlan->summary ?? '') }}</textarea>
                        @error('summary')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Kratak pregled biznis ideje (do 500 reči)</div>
                    </div>
                </div>
            </div>

            <!-- II. MARKETING -->
            <div class="form-card">
                <div class="form-section">
                    <h2>
                        <span class="section-number">II</span>
                        Marketing
                    </h2>
                    
                    <div class="form-group">
                        <label class="form-label">
                            Proizvod/Usluga <span class="required">*</span>
                        </label>
                        <textarea 
                            name="product_service" 
                            class="form-control @error('product_service') error @enderror"
                            required
                        >{{ old('product_service', $businessPlan->product_service ?? '') }}</textarea>
                        @error('product_service')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Opis proizvoda ili usluge koju planirate da nudite</div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            Lokacija <span class="required">*</span>
                        </label>
                        <textarea 
                            name="location" 
                            class="form-control @error('location') error @enderror"
                            required
                        >{{ old('location', $businessPlan->location ?? '') }}</textarea>
                        @error('location')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Opis lokacije poslovanja (adresa, karakteristike prostora, pristup...)</div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            Cijena <span class="required">*</span>
                        </label>
                        <textarea 
                            name="pricing" 
                            class="form-control @error('pricing') error @enderror"
                            required
                        >{{ old('pricing', $businessPlan->pricing ?? '') }}</textarea>
                        @error('pricing')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Strategija cijena, cenovna lista, konkurentnost cijena</div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            Promocija <span class="required">*</span>
                        </label>
                        <textarea 
                            name="promotion" 
                            class="form-control @error('promotion') error @enderror"
                            required
                        >{{ old('promotion', $businessPlan->promotion ?? '') }}</textarea>
                        @error('promotion')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Plan promocije i marketing aktivnosti</div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            Ljudi (marketing) <span class="required">*</span>
                        </label>
                        <textarea 
                            name="people_marketing" 
                            class="form-control @error('people_marketing') error @enderror"
                            required
                        >{{ old('people_marketing', $businessPlan->people_marketing ?? '') }}</textarea>
                        @error('people_marketing')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Osoblje zaduženo za marketing i prodaju</div>
                    </div>
                </div>
            </div>

            <!-- III. POSLOVANJE -->
            <div class="form-card">
                <div class="form-section">
                    <h2>
                        <span class="section-number">III</span>
                        Poslovanje
                    </h2>
                    
                    <div class="form-group">
                        <label class="form-label">
                            Analiza dosadašnjeg poslovanja <span class="required">*</span>
                        </label>
                        <textarea 
                            name="business_analysis" 
                            class="form-control @error('business_analysis') error @enderror"
                            required
                        >{{ old('business_analysis', $businessPlan->business_analysis ?? '') }}</textarea>
                        @error('business_analysis')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Analiza dosadašnjeg poslovanja (ako postoji) ili plan za započinjanje</div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            Nabavno tržište <span class="required">*</span>
                        </label>
                        <textarea 
                            name="supply_market" 
                            class="form-control @error('supply_market') error @enderror"
                            required
                        >{{ old('supply_market', $businessPlan->supply_market ?? '') }}</textarea>
                        @error('supply_market')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Analiza dobavljača, nabavnih kanala, uslova nabavke</div>
                    </div>
                </div>
            </div>

            <!-- IV. FINANSIJE -->
            <div class="form-card">
                <div class="form-section">
                    <h2>
                        <span class="section-number">IV</span>
                        Finansije
                    </h2>
                    
                    <div class="form-group">
                        <label class="form-label">
                            Potrebna sredstva i izvori finansiranja <span class="required">*</span>
                        </label>
                        <textarea 
                            name="required_funds" 
                            class="form-control @error('required_funds') error @enderror"
                            required
                        >{{ old('required_funds', $businessPlan->required_funds ?? '') }}</textarea>
                        @error('required_funds')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Detaljan pregled potrebnih sredstava i planiranih izvora finansiranja</div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            Projekcija prihoda i rashoda <span class="required">*</span>
                        </label>
                        <textarea 
                            name="revenue_expense_projection" 
                            class="form-control @error('revenue_expense_projection') error @enderror"
                            required
                        >{{ old('revenue_expense_projection', $businessPlan->revenue_expense_projection ?? '') }}</textarea>
                        @error('revenue_expense_projection')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Projekcija prihoda i rashoda za prve 12-24 mjeseca</div>
                    </div>
                </div>
            </div>

            <!-- V. LJUDI -->
            <div class="form-card">
                <div class="form-section">
                    <h2>
                        <span class="section-number">V</span>
                        Ljudi
                    </h2>
                    
                    <div class="form-group">
                        <label class="form-label">
                            Podaci o preduzetnici <span class="required">*</span>
                        </label>
                        <textarea 
                            name="entrepreneur_data" 
                            class="form-control @error('entrepreneur_data') error @enderror"
                            required
                        >{{ old('entrepreneur_data', $businessPlan->entrepreneur_data ?? '') }}</textarea>
                        @error('entrepreneur_data')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Obrazovanje, iskustvo, kvalifikacije preduzetnice</div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            Raspored poslova <span class="required">*</span>
                        </label>
                        <textarea 
                            name="job_schedule" 
                            class="form-control @error('job_schedule') error @enderror"
                            required
                        >{{ old('job_schedule', $businessPlan->job_schedule ?? '') }}</textarea>
                        @error('job_schedule')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Organizacija rada, raspored poslova, plan zapošljavanja</div>
                    </div>
                </div>
            </div>

            <!-- VI. RIZICI -->
            <div class="form-card">
                <div class="form-section">
                    <h2>
                        <span class="section-number">VI</span>
                        Rizici
                    </h2>
                    
                    <div class="form-group">
                        <label class="form-label">
                            Matrica upravljanja rizicima <span class="required">*</span>
                        </label>
                        <textarea 
                            name="risk_matrix" 
                            class="form-control @error('risk_matrix') error @enderror"
                            required
                            style="min-height: 200px;"
                        >{{ old('risk_matrix', $businessPlan->risk_matrix ?? '') }}</textarea>
                        @error('risk_matrix')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Identifikacija rizika, procjena verovatnoće i uticaja, plan ublažavanja rizika</div>
                    </div>
                </div>
            </div>

            <!-- Dugme za slanje -->
            <div class="form-card" style="text-align: center;">
                <button type="submit" class="btn-primary">
                    Sačuvaj biznis plan
                </button>
                <p style="color: #6b7280; font-size: 14px; margin-top: 16px;">
                    Nakon čuvanja biznis plana, možete priložiti potrebne dokumente.
                </p>
            </div>
        </form>
    </div>
</div>
@endsection

