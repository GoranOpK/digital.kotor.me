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
        margin-bottom: 16px;
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
        padding: 8px 12px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 14px;
        transition: border-color 0.2s;
        font-family: inherit;
    }
    textarea.form-control {
        min-height: 80px;
        resize: vertical;
    }
    textarea.form-control[rows="2"] {
        min-height: 60px;
    }
    textarea.form-control[rows="1"] {
        min-height: 40px;
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
    .btn-secondary {
        background: #6b7280;
        color: #fff;
        padding: 8px 16px;
        border: none;
        border-radius: 6px;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
    }
    .btn-secondary:hover {
        background: #4b5563;
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
    .form-row {
        display: grid;
        grid-template-columns: 1fr;
        gap: 16px;
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
    .dynamic-table {
        width: 100%;
        border-collapse: collapse;
        margin: 16px 0;
    }
    .dynamic-table th,
    .dynamic-table td {
        padding: 12px;
        border: 1px solid #e5e7eb;
        text-align: left;
    }
    .dynamic-table th {
        background: #f9fafb;
        font-weight: 600;
        color: #374151;
    }
    .dynamic-table input,
    .dynamic-table textarea {
        width: 100%;
        padding: 8px;
        border: 1px solid #d1d5db;
        border-radius: 4px;
        font-size: 14px;
    }
    .conditional-field {
        display: none;
    }
    .conditional-field.show {
        display: block;
    }
    .info-box {
        background: #f0f9ff;
        border-left: 4px solid var(--primary);
        padding: 16px;
        border-radius: 8px;
        margin: 16px 0;
        font-size: 14px;
        color: #374151;
    }
    /* Memorandum (zaglavlje) kao u Obrascima 1a/1b */
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
        justify-content: flex-end;
        flex-wrap: wrap;
        gap: 12px;
        margin-top: 12px;
        padding-top: 12px;
        border-top: 1px solid #e5e7eb;
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
    @media print {
        .obrazac-zaglavlje { box-shadow: none; border: 1px solid #ccc; }
        .obrazac-grb img { height: 2cm; }
    }
</style>

<div class="business-plan-page">
    <div class="container mx-auto px-4">
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
                <div class="obrazac-1a-1b">Obrazac 2</div>
            </div>
            <h1 class="obrazac-naslov-prijava">FORMA ZA BIZNIS PLAN</h1>
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
                <strong>Pregled prijave:</strong> Ovo je pregled Biznis plana, ne možete mijenjati podatke.
            </div>
        @endif

        <form method="POST" action="{{ $readOnly ? '#' : route('applications.business-plan.store', $application) }}" id="businessPlanForm" @if($readOnly) onsubmit="event.preventDefault(); return false;" @endif>
            @csrf

            <!-- I. OSNOVNI PODACI -->
            <div class="form-card">
                <div class="form-section">
                    <h2>
                        <span class="section-number">I</span>
                        OSNOVNI PODACI
                    </h2>
                    
                    <div class="form-group">
                        <label class="form-label">
                            1. Naziv biznis ideje: <span class="required">*</span>
                        </label>
                        <input 
                            type="text" 
                            name="business_idea_name" 
                            class="form-control @error('business_idea_name') error @enderror"
                            value="{{ old('business_idea_name', $businessPlan->business_idea_name ?? $application->business_plan_name ?? '') }}"
                            required
                            maxlength="255"
                        >
                        @error('business_idea_name')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            2. Podaci o podnosiocu biznis plana: <span class="required">*</span>
                        </label>
                        <div class="form-row" style="grid-template-columns: repeat(3, 1fr);">
                            <div class="form-group">
                                <label class="form-label">Ime i prezime:</label>
                                <input type="text" name="applicant_name" class="form-control" value="{{ old('applicant_name', $businessPlan->applicant_name ?? ($defaultData['applicant_name'] ?? '')) }}" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">JMBG:</label>
                                <input type="text" name="applicant_jmbg" class="form-control" value="{{ old('applicant_jmbg', $businessPlan->applicant_jmbg ?? ($defaultData['applicant_jmbg'] ?? '')) }}" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Adresa:</label>
                                <input type="text" name="applicant_address" class="form-control" value="{{ old('applicant_address', $businessPlan->applicant_address ?? ($defaultData['applicant_address'] ?? '')) }}" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Kontakt telefon:</label>
                                <input type="text" name="applicant_phone" class="form-control" value="{{ old('applicant_phone', $businessPlan->applicant_phone ?? ($defaultData['applicant_phone'] ?? '')) }}" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">E-mail:</label>
                                <input type="email" name="applicant_email" class="form-control" value="{{ old('applicant_email', $businessPlan->applicant_email ?? ($defaultData['applicant_email'] ?? '')) }}" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            3. Da li imate registrovan biznis? <span class="required">*</span>
                        </label>
                        <div class="radio-group">
                            <div class="radio-option">
                                <input type="radio" name="has_registered_business" value="1" id="has_business_yes" {{ old('has_registered_business', $businessPlan->has_registered_business ?? ($defaultData['has_registered_business'] ?? false)) ? 'checked' : '' }} onchange="toggleRegisteredBusinessFields()">
                                <label for="has_business_yes">Da</label>
                            </div>
                            <div class="radio-option">
                                <input type="radio" name="has_registered_business" value="0" id="has_business_no" {{ old('has_registered_business') === '0' || (($businessPlan && !$businessPlan->has_registered_business) || (!isset($defaultData['has_registered_business']) || !$defaultData['has_registered_business'])) ? 'checked' : '' }} onchange="toggleRegisteredBusinessFields()">
                                <label for="has_business_no">Ne</label>
                            </div>
                        </div>
                        <div id="napomenaNemaRegistraciju" class="info-box conditional-field {{ !old('has_registered_business', $businessPlan->has_registered_business ?? ($defaultData['has_registered_business'] ?? false)) ? 'show' : '' }}">
                            <strong>Napomena:</strong> Ukoliko podnosilac biznis plana nema registrovanu djelatnost, u slučaju da joj sredstva budu odobrena, mora svoju djelatnost registrovati u neki od oblika registracije koji predviđa Zakon o privrednim društvima, najkasnije do dana potpisivanja ugovora.
                        </div>
                    </div>

                    <div id="registeredBusinessFields" class="conditional-field {{ old('has_registered_business', $businessPlan->has_registered_business ?? ($defaultData['has_registered_business'] ?? false)) ? 'show' : '' }}">
                        <div class="form-group">
                            <label class="form-label">
                                4. Podaci o registrovanoj djelatnosti:
                            </label>
                            <div class="form-group">
                                <label class="form-label">Oblik registracije:</label>
                                <input type="text" name="registration_form" id="registration_form" class="form-control" value="{{ old('registration_form', $businessPlan->registration_form ?? ($defaultData['registration_form'] ?? '')) }}" placeholder="Preduzetnik / DOO / itd.">
                            </div>
                            <div class="form-group">
                                <label class="form-label" id="company_name_label">Ime i prezime preduzetnice i trgovački naziv za oblik registracije "Preduzetnik", odnosno ime i prezime nosioca biznisa* i naziv društva za oblik registracije "DOO":</label>
                                <input type="text" name="company_name" class="form-control" value="{{ old('company_name', $businessPlan->company_name ?? '') }}">
                                <div class="form-text">*Nosioc biznisa je vlasnica ili jedna od vlasnika i izvršna direktorica društva</div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">PIB:</label>
                                    <input type="text" name="pib" class="form-control" value="{{ old('pib', $businessPlan->pib ?? ($defaultData['pib'] ?? '')) }}">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Broj PDV registracije (ukoliko ste PDV obveznik):</label>
                                    <input type="text" name="vat_number" class="form-control" value="{{ old('vat_number', $businessPlan->vat_number ?? ($defaultData['vat_number'] ?? '')) }}">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Adresa/sjedište:</label>
                                <textarea name="company_address" class="form-control" rows="1" style="min-height: 40px;">{{ old('company_address', $businessPlan->company_address ?? '') }}</textarea>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">Kontakt telefon:</label>
                                    <input type="text" name="company_phone" class="form-control" value="{{ old('company_phone', $businessPlan->company_phone ?? '') }}">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">E-mail:</label>
                                    <input type="email" name="company_email" class="form-control" value="{{ old('company_email', $businessPlan->company_email ?? '') }}">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">Website:</label>
                                    <input type="text" name="company_website" class="form-control" value="{{ old('company_website', $businessPlan->company_website ?? ($defaultData['company_website'] ?? '')) }}">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Broj žiro računa i naziv banke:</label>
                                    <input type="text" name="bank_account" class="form-control" value="{{ old('bank_account', $businessPlan->bank_account ?? ($defaultData['bank_account'] ?? '')) }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            5. Rezime (ukratko opišite ideju, proširite tabelu koliko je potrebno): <span class="required">*</span>
                        </label>
                        <textarea 
                            name="summary" 
                            class="form-control @error('summary') error @enderror"
                            required
                            rows="6"
                        >{{ old('summary', $businessPlan->summary ?? '') }}</textarea>
                        @error('summary')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- II. MARKETING -->
            <div class="form-card">
                <div class="form-section">
                    <h2>
                        <span class="section-number">II</span>
                        MARKETING
                    </h2>
                    
                    <div class="form-group">
                        <label class="form-label">
                            PROIZVOD/USLUGA
                        </label>
                        <div class="form-group">
                            <label class="form-label">
                                6. Navedite sve postojeće/planirane proizvode/usluge. (Proširite tabelu koliko je potrebno.)
                            </label>
                            <table class="dynamic-table" id="productsServicesTable">
                                <thead>
                                    <tr>
                                        <th>Proizvod/usluga</th>
                                        <th>Opis i karakteristike</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody id="productsServicesTableBody">
                                    @php
                                        // Osiguraj da se učitaju svi podaci iz baze
                                        $productsServices = old('products_services_table');
                                        if ($productsServices === null && isset($businessPlan) && $businessPlan->products_services_table) {
                                            $productsServices = $businessPlan->products_services_table;
                                        }
                                        if (empty($productsServices)) {
                                            $productsServices = [['product' => '', 'description' => '']];
                                        }
                                        // Osiguraj da je array
                                        if (!is_array($productsServices)) {
                                            $productsServices = [['product' => '', 'description' => '']];
                                        }
                                    @endphp
                                    @foreach($productsServices as $index => $item)
                                        <tr>
                                            <td><input type="text" name="products_services_table[{{ $index }}][product]" class="form-control" value="{{ $item['product'] ?? '' }}"></td>
                                            <td><textarea name="products_services_table[{{ $index }}][description]" class="form-control" rows="2">{{ $item['description'] ?? '' }}</textarea></td>
                                            <td><button type="button" class="btn-secondary" onclick="removeTableRow(this)">Ukloni</button></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <button type="button" class="btn-secondary" onclick="addTableRow('productsServicesTableBody', ['product', 'description'])">+ Dodaj red</button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            7. Realizacijom moje biznis ideje (označite odgovarajuću kolonu): <span class="required">*</span>
                        </label>
                        <div class="radio-group">
                            <div class="radio-option">
                                <input type="radio" name="realization_type" value="stvori_novi" id="realization_new" {{ old('realization_type', $businessPlan->realization_type ?? '') === 'stvori_novi' ? 'checked' : '' }}>
                                <label for="realization_new">Stvoriću novi proizvod/uslugu</label>
                            </div>
                            <div class="radio-option">
                                <input type="radio" name="realization_type" value="unaprijedi" id="realization_improve" {{ old('realization_type', $businessPlan->realization_type ?? '') === 'unaprijedi' ? 'checked' : '' }}>
                                <label for="realization_improve">Unaprijediću postojeći proizvod/postojeću uslugu</label>
                            </div>
                            <div class="radio-option">
                                <input type="radio" name="realization_type" value="uveca_obim" id="realization_volume" {{ old('realization_type', $businessPlan->realization_type ?? '') === 'uveca_obim' ? 'checked' : '' }}>
                                <label for="realization_volume">Neće nastati novi proizvod/usluga, ali će se uvećati obim poslovanja</label>
                            </div>
                            <div class="radio-option">
                                <input type="radio" name="realization_type" value="nista_se_nece_promijeniti" id="realization_nothing" {{ old('realization_type', $businessPlan->realization_type ?? '') === 'nista_se_nece_promijeniti' ? 'checked' : '' }}>
                                <label for="realization_nothing">Ništa se neće promijeniti u odnosu na sadašnje stanje</label>
                            </div>
                            <div class="radio-option">
                                <input type="radio" name="realization_type" value="nista_od_navedenog" id="realization_none" {{ old('realization_type', $businessPlan->realization_type ?? '') === 'nista_od_navedenog' ? 'checked' : '' }}>
                                <label for="realization_none">Ništa od navedenog</label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            8. Navedite glavne kupce Vaših proizvoda/usluga tj. ciljnu grupu. (Proširite tabelu koliko je potrebno.)
                        </label>
                        <table class="dynamic-table" id="targetCustomersTable">
                            <thead>
                                <tr>
                                    <th>Ciljna grupa</th>
                                    <th>Opis</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="targetCustomersTableBody">
                                @php
                                    $targetCustomers = old('target_customers', $businessPlan->target_customers ?? [['group' => '', 'description' => '']]);
                                @endphp
                                @foreach($targetCustomers as $index => $item)
                                    <tr>
                                        <td><input type="text" name="target_customers[{{ $index }}][group]" class="form-control" value="{{ $item['group'] ?? '' }}"></td>
                                        <td><textarea name="target_customers[{{ $index }}][description]" class="form-control" rows="2">{{ $item['description'] ?? '' }}</textarea></td>
                                        <td><button type="button" class="btn-secondary" onclick="removeTableRow(this)">Ukloni</button></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <button type="button" class="btn-secondary" onclick="addTableRow('targetCustomersTableBody', ['group', 'description'])">+ Dodaj red</button>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            LOKACIJA
                        </label>
                        <div class="form-group">
                            <label class="form-label">
                                9. Gdje ćete prodavati Vaše proizvode/usluge? (fizička lokacija, online,…, proširite tabelu koliko je potrebno.)
                            </label>
                            <table class="dynamic-table" id="salesLocationsTable">
                                <thead>
                                    <tr>
                                        <th>Lokacija prodaje</th>
                                        <th>Opis</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody id="salesLocationsTableBody">
                                    @php
                                        $salesLocations = old('sales_locations', $businessPlan->sales_locations ?? [['location' => '', 'description' => '']]);
                                    @endphp
                                    @foreach($salesLocations as $index => $item)
                                        <tr>
                                            <td><input type="text" name="sales_locations[{{ $index }}][location]" class="form-control" value="{{ $item['location'] ?? '' }}"></td>
                                            <td><textarea name="sales_locations[{{ $index }}][description]" class="form-control" rows="2">{{ $item['description'] ?? '' }}</textarea></td>
                                            <td><button type="button" class="btn-secondary" onclick="removeTableRow(this)">Ukloni</button></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <button type="button" class="btn-secondary" onclick="addTableRow('salesLocationsTableBody', ['location', 'description'])">+ Dodaj red</button>
                        </div>
                        <div class="form-group">
                            <label class="form-label">
                                10. Ukoliko Vam je za realizaciju biznis ideje potreban poslovni prostor, da li imate lokaciju sa svom potrebnom infrastrukturom: struja, voda, put i dr. (označiti odgovarajući red sa x)? <span class="required">*</span>
                            </label>
                            <div class="radio-group">
                                <div class="radio-option">
                                    <input type="radio" name="has_business_space" value="sopstveni" id="space_own" {{ old('has_business_space', $businessPlan->has_business_space ?? '') === 'sopstveni' ? 'checked' : '' }}>
                                    <label for="space_own">Da, sopstveni prostor</label>
                                </div>
                                <div class="radio-option">
                                    <input type="radio" name="has_business_space" value="iznajmljeni" id="space_rented" {{ old('has_business_space', $businessPlan->has_business_space ?? '') === 'iznajmljeni' ? 'checked' : '' }}>
                                    <label for="space_rented">Da, iznajmljeni prostor</label>
                                </div>
                                <div class="radio-option">
                                    <input type="radio" name="has_business_space" value="trazim" id="space_searching" {{ old('has_business_space', $businessPlan->has_business_space ?? '') === 'trazim' ? 'checked' : '' }}>
                                    <label for="space_searching">Radim na pronalaženju lokacije</label>
                                </div>
                                <div class="radio-option">
                                    <input type="radio" name="has_business_space" value="nemam" id="space_none" {{ old('has_business_space', $businessPlan->has_business_space ?? '') === 'nemam' ? 'checked' : '' }}>
                                    <label for="space_none">Nemam</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            CIJENA
                        </label>
                        <div class="form-group">
                            <label class="form-label">
                                11. Koje su trenutne/planirane cijene Vaših proizvoda/usluga? (Proširite tabelu koliko je potrebno)
                            </label>
                            <table class="dynamic-table" id="pricingTable">
                                <thead>
                                    <tr>
                                        <th>Proizvod/usluga</th>
                                        <th>Cijena</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody id="pricingTableBody">
                                    @php
                                        // Osiguraj da se učitaju svi podaci iz baze
                                        $pricing = old('pricing_table');
                                        if ($pricing === null && isset($businessPlan) && $businessPlan->pricing_table) {
                                            $pricing = $businessPlan->pricing_table;
                                        }
                                        if (empty($pricing)) {
                                            $pricing = [['product' => '', 'price' => '']];
                                        }
                                        // Osiguraj da je array
                                        if (!is_array($pricing)) {
                                            $pricing = [['product' => '', 'price' => '']];
                                        }
                                    @endphp
                                    @foreach($pricing as $index => $item)
                                        <tr>
                                            <td><input type="text" name="pricing_table[{{ $index }}][product]" class="form-control" value="{{ $item['product'] ?? '' }}"></td>
                                            <td><input type="text" name="pricing_table[{{ $index }}][price]" class="form-control" value="{{ $item['price'] ?? '' }}"></td>
                                            <td><button type="button" class="btn-secondary" onclick="removeTableRow(this)">Ukloni</button></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <button type="button" class="btn-secondary" onclick="addTableRow('pricingTableBody', ['product', 'price'])">+ Dodaj red</button>
                        </div>
                        <div class="form-group">
                            <label class="form-label">
                                12. Koliki obim godišnje prodaje očekujete (u EUR)?
                            </label>
                            <input type="number" name="annual_sales_volume" class="form-control" value="{{ old('annual_sales_volume', $businessPlan->annual_sales_volume ?? '') }}" step="0.01" min="0" placeholder="0.00">
                        </div>
                        <div class="form-group">
                            <label class="form-label">
                                13. Učešće proizvoda/usluga u ukupnim prihodima (u %):
                            </label>
                            <table class="dynamic-table" id="revenueShareTable">
                                <thead>
                                    <tr>
                                        <th>Proizvod/usluga</th>
                                        <th>Učešće u ukupnim prihodima (%)</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody id="revenueShareTableBody">
                                    @php
                                        // Osiguraj da se učitaju svi podaci iz baze
                                        $revenueShare = old('revenue_share_table');
                                        if ($revenueShare === null && isset($businessPlan) && $businessPlan->revenue_share_table) {
                                            $revenueShare = $businessPlan->revenue_share_table;
                                        }
                                        if (empty($revenueShare)) {
                                            $revenueShare = [['product' => '', 'share' => '']];
                                        }
                                        // Osiguraj da je array
                                        if (!is_array($revenueShare)) {
                                            $revenueShare = [['product' => '', 'share' => '']];
                                        }
                                    @endphp
                                    @foreach($revenueShare as $index => $item)
                                        <tr>
                                            <td><input type="text" name="revenue_share_table[{{ $index }}][product]" class="form-control" value="{{ $item['product'] ?? '' }}"></td>
                                            <td><input type="number" name="revenue_share_table[{{ $index }}][share]" class="form-control" value="{{ $item['share'] ?? '' }}" step="0.01" min="0" max="100"></td>
                                            <td><button type="button" class="btn-secondary" onclick="removeTableRow(this)">Ukloni</button></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <button type="button" class="btn-secondary" onclick="addTableRow('revenueShareTableBody', ['product', 'share'])">+ Dodaj red</button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            PROMOCIJA
                        </label>
                        <div class="form-group">
                            <label class="form-label">
                                14. Definišite Vašu marketing strategiju (prodor na tržište, distribucija proizvoda/usluga, komunikacija sa tržištem, promocija/reklama…proširite tabelu koliko je potrebno):
                            </label>
                            <textarea 
                                name="promotion" 
                                class="form-control @error('promotion') error @enderror"
                                rows="6"
                            >{{ old('promotion', $businessPlan->promotion ?? '') }}</textarea>
                            @error('promotion')
                                <div class="error-message">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            LJUDI
                        </label>
                        <div class="form-group">
                            <label class="form-label">
                                15. Zaposlenost i kvalifikaciona struktura
                            </label>
                            <table class="dynamic-table" id="employmentStructureTable">
                                <thead>
                                    <tr>
                                        <th>Godina</th>
                                        <th>Broj stalno zaposlenih</th>
                                        <th>Kvalifikaciona struktura zaposlenih</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody id="employmentStructureTableBody">
                                    @php
                                        $employmentStructure = old('employment_structure', $businessPlan->employment_structure ?? [['year' => '', 'employees' => '', 'qualifications' => '']]);
                                    @endphp
                                    @foreach($employmentStructure as $index => $item)
                                        <tr>
                                            <td><input type="text" name="employment_structure[{{ $index }}][year]" class="form-control" value="{{ $item['year'] ?? '' }}"></td>
                                            <td><input type="number" name="employment_structure[{{ $index }}][employees]" class="form-control" value="{{ $item['employees'] ?? '' }}" min="0"></td>
                                            <td><textarea name="employment_structure[{{ $index }}][qualifications]" class="form-control" rows="2">{{ $item['qualifications'] ?? '' }}</textarea></td>
                                            <td><button type="button" class="btn-secondary" onclick="removeTableRow(this)">Ukloni</button></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <button type="button" class="btn-secondary" onclick="addTableRow('employmentStructureTableBody', ['year', 'employees', 'qualifications'])">+ Dodaj red</button>
                        </div>
                        <div class="form-group">
                            <label class="form-label">
                                16. Da li ćete angažovati sezonske radnike (označiti odgovarajuću kolonu sa x)? <span class="required">*</span>
                            </label>
                            <div class="radio-group">
                                <div class="radio-option">
                                    <input type="radio" name="has_seasonal_workers" value="1" id="seasonal_yes" {{ old('has_seasonal_workers', $businessPlan->has_seasonal_workers ?? false) ? 'checked' : '' }}>
                                    <label for="seasonal_yes">Da</label>
                                </div>
                                <div class="radio-option">
                                    <input type="radio" name="has_seasonal_workers" value="0" id="seasonal_no" {{ old('has_seasonal_workers') === '0' || ($businessPlan && !$businessPlan->has_seasonal_workers) ? 'checked' : '' }}>
                                    <label for="seasonal_no">Ne</label>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">
                                17. Analiza konkurencije
                            </label>
                            <div class="form-group">
                                <label class="form-label">Da li isti ili slični proizvodi već postoje na lokalnom nivou? (Izbjegavajte odgovore kojima se negira postojanje konkurencije.)</label>
                                <textarea name="competition_analysis" class="form-control" rows="6">{{ old('competition_analysis', $businessPlan->competition_analysis ?? '') }}</textarea>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Navedite jake i slabe strane konkurencije. (Proširite tabelu koliko je potrebno)</label>
                                <textarea name="competition_analysis" class="form-control" rows="6">{{ old('competition_analysis', $businessPlan->competition_analysis ?? '') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- III. POSLOVANJE -->
            <div class="form-card">
                <div class="form-section">
                    <h2>
                        <span class="section-number">III</span>
                        POSLOVANJE
                    </h2>
                    
                    <div class="form-group">
                        <label class="form-label">
                            18. Analiza dosadašnjeg poslovanja (Ne popunjavate ukoliko još niste registrovali biznis.)
                        </label>
                        <div class="form-group">
                            <label class="form-label">
                                a. Kratak opis poslovanja (istorija, proizvodi/usluge, klijenti, obim i potencijal za razvoj):
                            </label>
                            <textarea name="business_analysis" class="form-control" rows="6">{{ old('business_analysis', $businessPlan->business_analysis ?? '') }}</textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label">
                                b. Kratka istorija poslovanja:
                            </label>
                            <table class="dynamic-table" id="businessHistoryTable">
                                <thead>
                                    <tr>
                                        <th>Godina</th>
                                        <th>Broj zaposlenih</th>
                                        <th>Godišnji promet</th>
                                        <th>Važne prekretnice u razvoju biznisa</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody id="businessHistoryTableBody">
                                    @php
                                        $businessHistory = old('business_history', $businessPlan->business_history ?? [['year' => '', 'employees' => '', 'revenue' => '', 'milestones' => '']]);
                                    @endphp
                                    @foreach($businessHistory as $index => $item)
                                        <tr>
                                            <td><input type="text" name="business_history[{{ $index }}][year]" class="form-control" value="{{ $item['year'] ?? '' }}"></td>
                                            <td><input type="number" name="business_history[{{ $index }}][employees]" class="form-control" value="{{ $item['employees'] ?? '' }}" min="0"></td>
                                            <td><input type="number" name="business_history[{{ $index }}][revenue]" class="form-control" value="{{ $item['revenue'] ?? '' }}" step="0.01" min="0"></td>
                                            <td><textarea name="business_history[{{ $index }}][milestones]" class="form-control" rows="2">{{ $item['milestones'] ?? '' }}</textarea></td>
                                            <td><button type="button" class="btn-secondary" onclick="removeTableRow(this)">Ukloni</button></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <button type="button" class="btn-secondary" onclick="addTableRow('businessHistoryTableBody', ['year', 'employees', 'revenue', 'milestones'])">+ Dodaj red</button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            19. Navedite što Vam je sve potrebno kako biste proizveli proizvod/uslugu. (Proširite tabelu koliko je potrebno)
                        </label>
                        <textarea name="required_resources" class="form-control" rows="6">{{ old('required_resources', $businessPlan->required_resources ?? '') }}</textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            20. Nabavno tržište
                        </label>
                        <div class="form-group">
                            <label class="form-label">
                                a. Gdje ćete nabavljati sirovine, alat, mašine, opremu, programe tj. sve što Vam je potrebno kako biste kreirali proizvod /pružili uslugu? (Proširite tabelu koliko je potrebno.)
                            </label>
                            <table class="dynamic-table" id="suppliersTable">
                                <thead>
                                    <tr>
                                        <th>Potrebno mi je</th>
                                        <th>Dobavljač</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody id="suppliersTableBody">
                                    @php
                                        // Osiguraj da se učitaju svi podaci iz baze
                                        $suppliers = old('suppliers_table');
                                        if ($suppliers === null && isset($businessPlan) && $businessPlan->suppliers_table) {
                                            $suppliers = $businessPlan->suppliers_table;
                                        }
                                        if (empty($suppliers)) {
                                            $suppliers = [['item' => '', 'supplier' => '']];
                                        }
                                        // Osiguraj da je array
                                        if (!is_array($suppliers)) {
                                            $suppliers = [['item' => '', 'supplier' => '']];
                                        }
                                    @endphp
                                    @foreach($suppliers as $index => $item)
                                        <tr>
                                            <td><input type="text" name="suppliers_table[{{ $index }}][item]" class="form-control" value="{{ $item['item'] ?? '' }}"></td>
                                            <td><input type="text" name="suppliers_table[{{ $index }}][supplier]" class="form-control" value="{{ $item['supplier'] ?? '' }}"></td>
                                            <td><button type="button" class="btn-secondary" onclick="removeTableRow(this)">Ukloni</button></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <button type="button" class="btn-secondary" onclick="addTableRow('suppliersTableBody', ['item', 'supplier'])">+ Dodaj red</button>
                        </div>
                        <div class="form-group">
                            <label class="form-label">
                                b. Koliki je obim godišnjih nabavki (u EUR) za sve što ste naveli u prethodnoj tabeli?
                            </label>
                            <input type="number" name="annual_purchases_volume" class="form-control" value="{{ old('annual_purchases_volume', $businessPlan->annual_purchases_volume ?? '') }}" step="0.01" min="0" placeholder="0.00">
                        </div>
                    </div>
                </div>
            </div>

            <!-- IV. FINANSIJE -->
            <div class="form-card">
                <div class="form-section">
                    <h2>
                        <span class="section-number">IV</span>
                        FINANSIJE
                    </h2>
                    
                    <div class="form-group">
                        <label class="form-label">
                            21. Koliki iznos sredstava Vam je potreban za realizaciju biznis ideje?
                        </label>
                        <input type="number" name="required_amount" class="form-control" value="{{ old('required_amount', $businessPlan->required_amount ?? $defaultData['required_amount'] ?? '') }}" step="0.01" min="0" placeholder="0.00">
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            22. Koliki iznos podrške tražite od Opštine Kotor i navedite na što biste tačno utrošiti tražena sredstva? (Proširite tabelu koliko je potrebno.)
                        </label>
                        <div class="form-group">
                            <label class="form-label">Iznos podrške:</label>
                            <input type="number" name="requested_amount" class="form-control" value="{{ old('requested_amount', $businessPlan->requested_amount ?? $defaultData['requested_amount'] ?? '') }}" step="0.01" min="0" placeholder="0.00">
                        </div>
                        <table class="dynamic-table" id="fundingSourcesTable">
                            <thead>
                                <tr>
                                    <th>Vrsta nabavke</th>
                                    <th>Cijena po predračunu (u EUR)</th>
                                    <th>Ukupno</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="fundingSourcesTableBody">
                                @php
                                    // Osiguraj da se učitaju svi podaci iz baze
                                    $fundingSources = old('funding_sources_table');
                                    if ($fundingSources === null && isset($businessPlan) && $businessPlan->funding_sources_table) {
                                        $fundingSources = $businessPlan->funding_sources_table;
                                    }
                                    if (empty($fundingSources)) {
                                        $fundingSources = [['type' => '', 'price' => '']];
                                    }
                                    // Osiguraj da je array
                                    if (!is_array($fundingSources)) {
                                        $fundingSources = [['type' => '', 'price' => '']];
                                    }
                                @endphp
                                @foreach($fundingSources as $index => $item)
                                    <tr>
                                        <td><input type="text" name="funding_sources_table[{{ $index }}][type]" class="form-control" value="{{ $item['type'] ?? '' }}"></td>
                                        <td><input type="number" name="funding_sources_table[{{ $index }}][price]" class="form-control funding-price" value="{{ $item['price'] ?? '' }}" step="0.01" min="0" oninput="calculateFundingTotal()"></td>
                                        <td><input type="text" class="form-control funding-total" value="{{ $item['price'] ?? '' }}" readonly style="background: #f9fafb;"></td>
                                        <td><button type="button" class="btn-secondary" onclick="removeTableRow(this)">Ukloni</button></td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr style="background: #f9fafb; font-weight: 600;">
                                    <td colspan="2" style="text-align: right;">UKUPNO:</td>
                                    <td><input type="text" id="fundingGrandTotal" class="form-control" value="0.00" readonly style="background: #fff; font-weight: 600;"></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                        <button type="button" class="btn-secondary" onclick="addTableRow('fundingSourcesTableBody', ['type', 'price'])">+ Dodaj red</button>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            23. Ukoliko Vam iznos podrške ne bude dovoljan za realizaciju biznis ideje, kako planirate pokriti ostatak? (označiti odgovarajući red sa x)
                        </label>
                        <div class="radio-group">
                            <div class="radio-option">
                                <input type="radio" name="funding_alternative" value="vlastita" id="funding_own" {{ old('funding_alternative', $businessPlan->funding_alternative ?? '') === 'vlastita' ? 'checked' : '' }}>
                                <label for="funding_own">Vlastita sredstva</label>
                            </div>
                            <div class="radio-option">
                                <input type="radio" name="funding_alternative" value="pozajmljena" id="funding_borrowed" {{ old('funding_alternative', $businessPlan->funding_alternative ?? '') === 'pozajmljena' ? 'checked' : '' }}>
                                <label for="funding_borrowed">Pozajmljena sredstva (porodica/prijatelji)</label>
                            </div>
                            <div class="radio-option">
                                <input type="radio" name="funding_alternative" value="kredit" id="funding_credit" {{ old('funding_alternative', $businessPlan->funding_alternative ?? '') === 'kredit' ? 'checked' : '' }}>
                                <label for="funding_credit">Kredit</label>
                            </div>
                            <div class="radio-option">
                                <input type="radio" name="funding_alternative" value="ostali" id="funding_other" {{ old('funding_alternative', $businessPlan->funding_alternative ?? '') === 'ostali' ? 'checked' : '' }}>
                                <label for="funding_other">Ostali izvori (ulagači, državne subvencije, fondovi, donacije, sponzorstva i dr.)</label>
                            </div>
                            <div class="radio-option">
                                <input type="radio" name="funding_alternative" value="ne_znam" id="funding_dont_know" {{ old('funding_alternative', $businessPlan->funding_alternative ?? '') === 'ne_znam' ? 'checked' : '' }}>
                                <label for="funding_dont_know">Ne znam</label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            24. Projekcija prihoda u naredne 3 godine (Proširite tabelu koliko je potrebno):
                        </label>
                        <table class="dynamic-table" id="revenueProjectionTable">
                            <thead>
                                <tr>
                                    <th>Proizvod/usluga</th>
                                    <th>I godina (tekuća)</th>
                                    <th>II godina</th>
                                    <th>III godina</th>
                                    <th>Ukupno</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="revenueProjectionTableBody">
                                @php
                                    // Osiguraj da se učitaju svi podaci iz baze
                                    $revenueProjection = old('revenue_projection');
                                    if ($revenueProjection === null && isset($businessPlan) && $businessPlan->revenue_projection) {
                                        $revenueProjection = $businessPlan->revenue_projection;
                                    }
                                    if (empty($revenueProjection)) {
                                        $revenueProjection = [['product' => '', 'year1' => '', 'year2' => '', 'year3' => '']];
                                    }
                                    // Osiguraj da je array
                                    if (!is_array($revenueProjection)) {
                                        $revenueProjection = [['product' => '', 'year1' => '', 'year2' => '', 'year3' => '']];
                                    }
                                @endphp
                                @foreach($revenueProjection as $index => $item)
                                    @php
                                        $rowTotal = (float)($item['year1'] ?? 0) + (float)($item['year2'] ?? 0) + (float)($item['year3'] ?? 0);
                                    @endphp
                                    <tr>
                                        <td><input type="text" name="revenue_projection[{{ $index }}][product]" class="form-control" value="{{ $item['product'] ?? '' }}"></td>
                                        <td><input type="number" name="revenue_projection[{{ $index }}][year1]" class="form-control revenue-year" value="{{ $item['year1'] ?? '' }}" step="0.01" min="0" oninput="calculateRevenueRowTotal(this)"></td>
                                        <td><input type="number" name="revenue_projection[{{ $index }}][year2]" class="form-control revenue-year" value="{{ $item['year2'] ?? '' }}" step="0.01" min="0" oninput="calculateRevenueRowTotal(this)"></td>
                                        <td><input type="number" name="revenue_projection[{{ $index }}][year3]" class="form-control revenue-year" value="{{ $item['year3'] ?? '' }}" step="0.01" min="0" oninput="calculateRevenueRowTotal(this)"></td>
                                        <td><input type="text" class="form-control revenue-total" value="{{ number_format($rowTotal, 2, ',', '.') }}" readonly style="background: #f9fafb;"></td>
                                        <td><button type="button" class="btn-secondary" onclick="removeTableRow(this)">Ukloni</button></td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr style="background: #f9fafb; font-weight: 600;">
                                    <td style="text-align: right;">UKUPNO:</td>
                                    <td><input type="text" id="revenueYear1Total" class="form-control" value="0.00" readonly style="background: #fff; font-weight: 600;"></td>
                                    <td><input type="text" id="revenueYear2Total" class="form-control" value="0.00" readonly style="background: #fff; font-weight: 600;"></td>
                                    <td><input type="text" id="revenueYear3Total" class="form-control" value="0.00" readonly style="background: #fff; font-weight: 600;"></td>
                                    <td><input type="text" id="revenueGrandTotal" class="form-control" value="0.00" readonly style="background: #fff; font-weight: 600;"></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                        <button type="button" class="btn-secondary" onclick="addTableRow('revenueProjectionTableBody', ['product', 'year1', 'year2', 'year3'])">+ Dodaj red</button>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            25. Projekcija rashoda u naredne 3 godine (Proširite tabelu koliko je potrebno):
                        </label>
                        <table class="dynamic-table" id="expenseProjectionTable">
                            <thead>
                                <tr>
                                    <th>Vrsta troška</th>
                                    <th>I godina (tekuća)</th>
                                    <th>II godina</th>
                                    <th>III godina</th>
                                    <th>Ukupno</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="expenseProjectionTableBody">
                                <!-- Investicioni troškovi kategorija -->
                                <tr class="category-header" style="background: #f3f4f6; font-weight: 600;">
                                    <td colspan="6">Investicioni troškovi (alati, oprema, mašine, software, licence, osiguranja, amortizacija i sl.)</td>
                                </tr>
                                @php
                                    $investmentExpenses = old('investment_expenses', $businessPlan->investment_expenses ?? [['type' => '', 'year1' => '', 'year2' => '', 'year3' => '']]);
                                @endphp
                                @if(empty($investmentExpenses) || (count($investmentExpenses) === 1 && empty($investmentExpenses[0]['type'])))
                                    @php $investmentExpenses = [['type' => '', 'year1' => '', 'year2' => '', 'year3' => '']]; @endphp
                                @endif
                                @foreach($investmentExpenses as $index => $item)
                                    @php
                                        $rowTotal = (float)($item['year1'] ?? 0) + (float)($item['year2'] ?? 0) + (float)($item['year3'] ?? 0);
                                    @endphp
                                    <tr data-category="investment">
                                        <td><input type="text" name="investment_expenses[{{ $index }}][type]" class="form-control" value="{{ $item['type'] ?? '' }}" placeholder="Npr. Alati, oprema..."></td>
                                        <td><input type="number" name="investment_expenses[{{ $index }}][year1]" class="form-control expense-year" value="{{ $item['year1'] ?? '' }}" step="0.01" min="0" oninput="calculateExpenseRowTotal(this)"></td>
                                        <td><input type="number" name="investment_expenses[{{ $index }}][year2]" class="form-control expense-year" value="{{ $item['year2'] ?? '' }}" step="0.01" min="0" oninput="calculateExpenseRowTotal(this)"></td>
                                        <td><input type="number" name="investment_expenses[{{ $index }}][year3]" class="form-control expense-year" value="{{ $item['year3'] ?? '' }}" step="0.01" min="0" oninput="calculateExpenseRowTotal(this)"></td>
                                        <td><input type="text" class="form-control expense-total" value="{{ number_format($rowTotal, 2, ',', '.') }}" readonly style="background: #f9fafb;"></td>
                                        <td><button type="button" class="btn-secondary" onclick="removeTableRow(this)">Ukloni</button></td>
                                    </tr>
                                @endforeach
                                
                                <!-- Tekući troškovi kategorija -->
                                <tr class="category-header" style="background: #f3f4f6; font-weight: 600;">
                                    <td colspan="6">Tekući troškovi (sirovine, bruto zarade, renta, struja, voda, telefon, internet, marketing itd.)</td>
                                </tr>
                                @php
                                    $operatingExpenses = old('operating_expenses', $businessPlan->operating_expenses ?? [['type' => '', 'year1' => '', 'year2' => '', 'year3' => '']]);
                                @endphp
                                @if(empty($operatingExpenses) || (count($operatingExpenses) === 1 && empty($operatingExpenses[0]['type'])))
                                    @php $operatingExpenses = [['type' => '', 'year1' => '', 'year2' => '', 'year3' => '']]; @endphp
                                @endif
                                @foreach($operatingExpenses as $index => $item)
                                    @php
                                        $rowTotal = (float)($item['year1'] ?? 0) + (float)($item['year2'] ?? 0) + (float)($item['year3'] ?? 0);
                                    @endphp
                                    <tr data-category="operating">
                                        <td><input type="text" name="operating_expenses[{{ $index }}][type]" class="form-control" value="{{ $item['type'] ?? '' }}" placeholder="Npr. Sirovine, renta..."></td>
                                        <td><input type="number" name="operating_expenses[{{ $index }}][year1]" class="form-control expense-year" value="{{ $item['year1'] ?? '' }}" step="0.01" min="0" oninput="calculateExpenseRowTotal(this)"></td>
                                        <td><input type="number" name="operating_expenses[{{ $index }}][year2]" class="form-control expense-year" value="{{ $item['year2'] ?? '' }}" step="0.01" min="0" oninput="calculateExpenseRowTotal(this)"></td>
                                        <td><input type="number" name="operating_expenses[{{ $index }}][year3]" class="form-control expense-year" value="{{ $item['year3'] ?? '' }}" step="0.01" min="0" oninput="calculateExpenseRowTotal(this)"></td>
                                        <td><input type="text" class="form-control expense-total" value="{{ number_format($rowTotal, 2, ',', '.') }}" readonly style="background: #f9fafb;"></td>
                                        <td><button type="button" class="btn-secondary" onclick="removeTableRow(this)">Ukloni</button></td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr style="background: #f9fafb; font-weight: 600;">
                                    <td style="text-align: right;">UKUPNO:</td>
                                    <td><input type="text" id="expenseYear1Total" class="form-control" value="0.00" readonly style="background: #fff; font-weight: 600;"></td>
                                    <td><input type="text" id="expenseYear2Total" class="form-control" value="0.00" readonly style="background: #fff; font-weight: 600;"></td>
                                    <td><input type="text" id="expenseYear3Total" class="form-control" value="0.00" readonly style="background: #fff; font-weight: 600;"></td>
                                    <td><input type="text" id="expenseGrandTotal" class="form-control" value="0.00" readonly style="background: #fff; font-weight: 600;"></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                        <div style="margin-top: 12px;">
                            <button type="button" class="btn-secondary" onclick="addExpenseRow('investment')">+ Dodaj red (Investicioni troškovi)</button>
                            <button type="button" class="btn-secondary" onclick="addExpenseRow('operating')">+ Dodaj red (Tekući troškovi)</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- V. LJUDI -->
            <div class="form-card">
                <div class="form-section">
                    <h2>
                        <span class="section-number">V</span>
                        LJUDI
                    </h2>
                    
                    <div class="form-group">
                        <label class="form-label">
                            26. Predstavite Vaše radno iskustvo, te opišite znanja i vještine koje posjedujete, a za koje smatrate da su od važnosti za realizaciju biznis plana. (Proširite tabelu koliko je potrebno.)
                        </label>
                        <textarea name="work_experience" class="form-control" rows="6">{{ old('work_experience', $businessPlan->work_experience ?? '') }}</textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            27. Za koje Vaše osobine smatrate da su prednosti, a koje osobine smatrate da biste trebali unaprijediti? (Proširite tabelu koliko je potrebno.)
                        </label>
                        <textarea name="personal_strengths_weaknesses" class="form-control" rows="6">{{ old('personal_strengths_weaknesses', $businessPlan->personal_strengths_weaknesses ?? '') }}</textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            28. Ko Vam je najveća podrška na Vašem preduzetničkom putovanju?
                        </label>
                        <input type="text" name="biggest_support" class="form-control" value="{{ old('biggest_support', $businessPlan->biggest_support ?? '') }}">
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            29. Kako ćete rasporediti poslove?
                        </label>
                        <table class="dynamic-table" id="jobScheduleTable">
                            <thead>
                                <tr>
                                    <th>Dio biznis plana</th>
                                    <th>Ko bi mogao ovo raditi u početku?</th>
                                    <th>Ko bi ovo mogao raditi kasnije?</th>
                                </tr>
                            </thead>
                            <tbody id="jobScheduleTableBody">
                                @php
                                    $defaultJobSchedule = [
                                        ['part' => 'Marketing', 'initially' => '', 'later' => ''],
                                        ['part' => 'Poslovanje', 'initially' => '', 'later' => ''],
                                        ['part' => 'Finansije', 'initially' => '', 'later' => '']
                                    ];
                                    $jobSchedule = old('job_schedule', $businessPlan->job_schedule ?? $defaultJobSchedule);
                                    
                                    // Osiguraj da imamo sve tri kategorije
                                    $parts = array_column($jobSchedule, 'part');
                                    if (!in_array('Marketing', $parts)) {
                                        $jobSchedule[] = ['part' => 'Marketing', 'initially' => '', 'later' => ''];
                                    }
                                    if (!in_array('Poslovanje', $parts)) {
                                        $jobSchedule[] = ['part' => 'Poslovanje', 'initially' => '', 'later' => ''];
                                    }
                                    if (!in_array('Finansije', $parts)) {
                                        $jobSchedule[] = ['part' => 'Finansije', 'initially' => '', 'later' => ''];
                                    }
                                    
                                    // Sortiraj da budu u redosledu: Marketing, Poslovanje, Finansije
                                    usort($jobSchedule, function($a, $b) {
                                        $order = ['Marketing' => 1, 'Poslovanje' => 2, 'Finansije' => 3];
                                        $aOrder = $order[$a['part']] ?? 999;
                                        $bOrder = $order[$b['part']] ?? 999;
                                        return $aOrder <=> $bOrder;
                                    });
                                @endphp
                                @foreach($jobSchedule as $index => $item)
                                    @php
                                        $isFixed = in_array($item['part'], ['Marketing', 'Poslovanje', 'Finansije']);
                                    @endphp
                                    <tr data-part="{{ $item['part'] }}" data-fixed="{{ $isFixed ? 'true' : 'false' }}">
                                        <td>
                                            @if($isFixed)
                                                <input type="text" name="job_schedule[{{ $index }}][part]" class="form-control" value="{{ $item['part'] }}" readonly style="background: #f9fafb; font-weight: 600;">
                                            @else
                                                <input type="text" name="job_schedule[{{ $index }}][part]" class="form-control" value="{{ $item['part'] ?? '' }}">
                                            @endif
                                        </td>
                                        <td><textarea name="job_schedule[{{ $index }}][initially]" class="form-control" rows="2" placeholder="-">{{ $item['initially'] ?? '' }}</textarea></td>
                                        <td><textarea name="job_schedule[{{ $index }}][later]" class="form-control" rows="2" placeholder="-">{{ $item['later'] ?? '' }}</textarea></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <button type="button" class="btn-secondary" onclick="addJobScheduleRow()">+ Dodaj red</button>
                    </div>
                </div>
            </div>

            <!-- VI. RIZICI -->
            <div class="form-card">
                <div class="form-section">
                    <h2>
                        <span class="section-number">VI</span>
                        RIZICI
                    </h2>
                    
                    <div class="form-group">
                        <label class="form-label">
                            30. Matrica upravljanja rizicima (Proširite tabelu koliko je potrebno):
                        </label>
                        <table class="dynamic-table" id="riskMatrixTable">
                            <thead>
                                <tr>
                                    <th>Rizik</th>
                                    <th>Vjerovatnoća da će se dogoditi</th>
                                    <th>Uticaj na Vaše poslovanje</th>
                                    <th>Mjere koje ćete preduzeti</th>
                                    <th>Odgovorna osoba (Ko će to uraditi?)</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="riskMatrixTableBody">
                                @php
                                    $riskMatrix = old('risk_matrix', $businessPlan->risk_matrix ?? [['risk' => '', 'probability' => '', 'impact' => '', 'measures' => '', 'responsible' => '']]);
                                @endphp
                                @foreach($riskMatrix as $index => $item)
                                    <tr>
                                        <td><input type="text" name="risk_matrix[{{ $index }}][risk]" class="form-control" value="{{ $item['risk'] ?? '' }}"></td>
                                        <td><textarea name="risk_matrix[{{ $index }}][probability]" class="form-control" rows="2">{{ $item['probability'] ?? '' }}</textarea></td>
                                        <td><textarea name="risk_matrix[{{ $index }}][impact]" class="form-control" rows="2">{{ $item['impact'] ?? '' }}</textarea></td>
                                        <td><textarea name="risk_matrix[{{ $index }}][measures]" class="form-control" rows="2">{{ $item['measures'] ?? '' }}</textarea></td>
                                        <td><input type="text" name="risk_matrix[{{ $index }}][responsible]" class="form-control" value="{{ $item['responsible'] ?? '' }}"></td>
                                        <td><button type="button" class="btn-secondary" onclick="removeTableRow(this)">Ukloni</button></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <button type="button" class="btn-secondary" onclick="addTableRow('riskMatrixTableBody', ['risk', 'probability', 'impact', 'measures', 'responsible'])">+ Dodaj red</button>
                    </div>
                </div>
            </div>

            <!-- Dugme za slanje -->
            @if(!$readOnly)
                <div class="form-card" style="text-align: center;">
                    <div style="display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;">
                        <button type="button" id="bpSubmitBtn" class="btn-primary" style="padding: 12px 24px; border: none; border-radius: 8px; font-weight: 600; font-size: 14px; cursor: pointer;">
                            <span id="bpSubmitBtnText">Sačuvaj Nacrt plana</span>
                        </button>
                    </div>
                </div>
            @endif
        </form>
    </div>
</div>

<script>
function toggleRegisteredBusinessFields() {
    const hasBusiness = document.querySelector('input[name="has_registered_business"]:checked')?.value === '1';
    const fields = document.getElementById('registeredBusinessFields');
    const napomena = document.getElementById('napomenaNemaRegistraciju');
    if (hasBusiness) {
        fields.classList.add('show');
        if (napomena) napomena.classList.remove('show');
    } else {
        fields.classList.remove('show');
        if (napomena) napomena.classList.add('show');
    }
}

function addTableRow(tableBodyId, fieldNames) {
    const tbody = document.getElementById(tableBodyId);
    const row = document.createElement('tr');
    const rowIndex = tbody.children.length;
    
    // Ispravljeno: Generiši pravilno ime tabele
    let tableName = tableBodyId.replace('TableBody', '');
    // Konvertuj camelCase u snake_case
    tableName = tableName.replace(/([A-Z])/g, '_$1').toLowerCase();
    // Ukloni vodeći underscore ako postoji
    if (tableName.startsWith('_')) {
        tableName = tableName.substring(1);
    }
    // Dodaj _table na kraj (osim za tabele koje već imaju drugačiji format)
    // Mapa za konverziju imena tabela
    const tableNameMap = {
        'products_services': 'products_services_table',
        'pricing': 'pricing_table',
        'revenue_share': 'revenue_share_table',
        'suppliers': 'suppliers_table',
        'target_customers': 'target_customers',
        'sales_locations': 'sales_locations',
        'employment_structure': 'employment_structure',
        'business_history': 'business_history',
        'job_schedule': 'job_schedule',
        'funding_sources': 'funding_sources_table',
        'revenue_projection': 'revenue_projection',
        'expense_projection': 'expense_projection'
    };
    // Ako postoji u mapi, koristi mapirano ime, inače dodaj _table
    if (tableNameMap[tableName]) {
        tableName = tableNameMap[tableName];
    } else if (!tableName.endsWith('_table') && !tableName.includes('_')) {
        tableName = tableName + '_table';
    }
    
    // Debug log
    console.log('Adding row to table:', tableBodyId, 'Table name:', tableName, 'Row index:', rowIndex);
    
    fieldNames.forEach(fieldName => {
        const cell = document.createElement('td');
        if (fieldName === 'description' || fieldName === 'qualifications' || fieldName === 'initially' || fieldName === 'later' || fieldName === 'probability' || fieldName === 'impact' || fieldName === 'measures' || fieldName === 'milestones') {
            const textarea = document.createElement('textarea');
            textarea.name = `${tableName}[${rowIndex}][${fieldName}]`;
            textarea.className = 'form-control';
            textarea.rows = 2;
            cell.appendChild(textarea);
        } else if (fieldName === 'share' || fieldName === 'employees' || fieldName === 'price' || fieldName === 'revenue' || fieldName === 'year1' || fieldName === 'year2' || fieldName === 'year3') {
            const input = document.createElement('input');
            input.type = 'number';
            input.name = `${tableName}[${rowIndex}][${fieldName}]`;
            input.className = 'form-control';
            input.step = '0.01';
            input.min = '0';
            if (fieldName === 'share') {
                input.max = '100';
            }
            // Dodaj event listener za automatsko računanje
            if (fieldName === 'price' && tableBodyId === 'fundingSourcesTableBody') {
                input.className += ' funding-price';
                input.oninput = function() { calculateFundingTotal(); };
            } else if (fieldName === 'year1' || fieldName === 'year2' || fieldName === 'year3') {
                if (tableBodyId === 'revenueProjectionTableBody') {
                    input.className += ' revenue-year';
                    input.oninput = function() { calculateRevenueRowTotal(this); };
                } else if (tableBodyId === 'expenseProjectionTableBody') {
                    input.className += ' expense-year';
                    input.oninput = function() { calculateExpenseRowTotal(this); };
                }
            }
            cell.appendChild(input);
        } else {
            const input = document.createElement('input');
            input.type = 'text';
            input.name = `${tableName}[${rowIndex}][${fieldName}]`;
            input.className = 'form-control';
            cell.appendChild(input);
        }
        row.appendChild(cell);
    });
    
    // Dodaj kolonu "Ukupno" ako je potrebno
    if (tableBodyId === 'fundingSourcesTableBody') {
        const totalCell = document.createElement('td');
        const totalInput = document.createElement('input');
        totalInput.type = 'text';
        totalInput.className = 'form-control funding-total';
        totalInput.readOnly = true;
        totalInput.style.background = '#f9fafb';
        totalInput.value = '0.00';
        totalCell.appendChild(totalInput);
        row.appendChild(totalCell);
    } else if (tableBodyId === 'revenueProjectionTableBody') {
        const totalCell = document.createElement('td');
        const totalInput = document.createElement('input');
        totalInput.type = 'text';
        totalInput.className = 'form-control revenue-total';
        totalInput.readOnly = true;
        totalInput.style.background = '#f9fafb';
        totalInput.value = '0.00';
        totalCell.appendChild(totalInput);
        row.appendChild(totalCell);
    } else if (tableBodyId === 'expenseProjectionTableBody') {
        const totalCell = document.createElement('td');
        const totalInput = document.createElement('input');
        totalInput.type = 'text';
        totalInput.className = 'form-control expense-total';
        totalInput.readOnly = true;
        totalInput.style.background = '#f9fafb';
        totalInput.value = '0.00';
        totalCell.appendChild(totalInput);
        row.appendChild(totalCell);
    }
    
    const actionCell = document.createElement('td');
    const removeBtn = document.createElement('button');
    removeBtn.type = 'button';
    removeBtn.className = 'btn-secondary';
    removeBtn.textContent = 'Ukloni';
    removeBtn.onclick = function() { removeTableRow(this); };
    actionCell.appendChild(removeBtn);
    row.appendChild(actionCell);
    
    tbody.appendChild(row);
}

function removeTableRow(button) {
    const row = button.closest('tr');
    const isFixed = row.getAttribute('data-fixed') === 'true';
    
    if (isFixed) {
        alert('Ne možete obrisati ovaj red. Ovo je obavezna kategorija.');
        return;
    }
    
    // Ne briši category-header redove
    if (row.classList.contains('category-header')) {
        return;
    }
    
    const tbody = row.parentElement;
    const category = row.getAttribute('data-category');
    row.remove();
    
    // Ažuriraj indekse svih preostalih redova nakon brisanja
    // Za expense_projection tabele, ažuriraj samo redove u istoj kategoriji
    if (category) {
        // Ažuriraj indekse samo za redove u istoj kategoriji
        const categoryRows = tbody.querySelectorAll(`tr[data-category="${category}"]`);
        categoryRows.forEach((remainingRow, index) => {
            const inputs = remainingRow.querySelectorAll('input[name], textarea[name]');
            inputs.forEach(input => {
                const name = input.name;
                // Pronađi ime tabele i polje iz name atributa
                const match = name.match(/^([^\[]+)\[\d+\]\[([^\]]+)\]$/);
                if (match) {
                    const tableName = match[1];
                    const fieldName = match[2];
                    // Ažuriraj indeks
                    input.name = `${tableName}[${index}][${fieldName}]`;
                }
            });
        });
    } else {
        // Za obične tabele, ažuriraj sve redove
        const rows = tbody.querySelectorAll('tr:not(.category-header)');
        rows.forEach((remainingRow, index) => {
            const inputs = remainingRow.querySelectorAll('input[name], textarea[name]');
            inputs.forEach(input => {
                const name = input.name;
                // Pronađi ime tabele i polje iz name atributa
                const match = name.match(/^([^\[]+)\[\d+\]\[([^\]]+)\]$/);
                if (match) {
                    const tableName = match[1];
                    const fieldName = match[2];
                    // Ažuriraj indeks
                    input.name = `${tableName}[${index}][${fieldName}]`;
                }
            });
        });
    }
    
    // Ponovo izračunaj ukupno nakon brisanja reda
    calculateFundingTotal();
    calculateRevenueGrandTotal();
    calculateExpenseGrandTotal();
}

function addJobScheduleRow() {
    const tbody = document.getElementById('jobScheduleTableBody');
    const rows = tbody.querySelectorAll('tr');
    const rowIndex = rows.length;
    
    const newRow = document.createElement('tr');
    newRow.setAttribute('data-fixed', 'false');
    newRow.innerHTML = `
        <td><input type="text" name="job_schedule[${rowIndex}][part]" class="form-control" placeholder="Dio biznis plana"></td>
        <td><textarea name="job_schedule[${rowIndex}][initially]" class="form-control" rows="2" placeholder="-"></textarea></td>
        <td><textarea name="job_schedule[${rowIndex}][later]" class="form-control" rows="2" placeholder="-"></textarea></td>
    `;
    
    tbody.appendChild(newRow);
}

// Funkcije za računanje ukupnih iznosa
function calculateFundingTotal() {
    const rows = document.querySelectorAll('#fundingSourcesTableBody tr');
    let grandTotal = 0;
    
    rows.forEach(row => {
        const priceInput = row.querySelector('.funding-price');
        const totalInput = row.querySelector('.funding-total');
        const price = parseFloat(priceInput?.value || 0);
        totalInput.value = price.toFixed(2).replace('.', ',');
        grandTotal += price;
    });
    
    const grandTotalInput = document.getElementById('fundingGrandTotal');
    if (grandTotalInput) {
        grandTotalInput.value = grandTotal.toFixed(2).replace('.', ',');
    }
}

function calculateRevenueRowTotal(input) {
    // Pozovi funkciju koja računa sve ukupne iznose
    calculateRevenueGrandTotal();
}

function calculateRevenueGrandTotal() {
    const rows = document.querySelectorAll('#revenueProjectionTableBody tr');
    let year1Total = 0;
    let year2Total = 0;
    let year3Total = 0;
    let grandTotal = 0;
    
    rows.forEach(row => {
        const year1 = parseFloat(row.querySelector('input[name*="[year1]"]')?.value || 0);
        const year2 = parseFloat(row.querySelector('input[name*="[year2]"]')?.value || 0);
        const year3 = parseFloat(row.querySelector('input[name*="[year3]"]')?.value || 0);
        const totalInput = row.querySelector('.revenue-total');
        const total = year1 + year2 + year3;
        
        year1Total += year1;
        year2Total += year2;
        year3Total += year3;
        grandTotal += total;
        
        if (totalInput) {
            totalInput.value = total.toFixed(2).replace('.', ',');
        }
    });
    
    const year1TotalInput = document.getElementById('revenueYear1Total');
    const year2TotalInput = document.getElementById('revenueYear2Total');
    const year3TotalInput = document.getElementById('revenueYear3Total');
    const grandTotalInput = document.getElementById('revenueGrandTotal');
    
    if (year1TotalInput) {
        year1TotalInput.value = year1Total.toFixed(2).replace('.', ',');
    }
    if (year2TotalInput) {
        year2TotalInput.value = year2Total.toFixed(2).replace('.', ',');
    }
    if (year3TotalInput) {
        year3TotalInput.value = year3Total.toFixed(2).replace('.', ',');
    }
    if (grandTotalInput) {
        grandTotalInput.value = grandTotal.toFixed(2).replace('.', ',');
    }
}

function calculateExpenseRowTotal(input) {
    // Pozovi funkciju koja računa sve ukupne iznose
    calculateExpenseGrandTotal();
}

function calculateExpenseGrandTotal() {
    const tbody = document.getElementById('expenseProjectionTableBody');
    const rows = tbody.querySelectorAll('tr:not(.category-header)');
    
    let year1Total = 0;
    let year2Total = 0;
    let year3Total = 0;
    let grandTotal = 0;
    
    rows.forEach(row => {
        const year1 = parseFloat(row.querySelector('input[name*="[year1]"]')?.value || 0);
        const year2 = parseFloat(row.querySelector('input[name*="[year2]"]')?.value || 0);
        const year3 = parseFloat(row.querySelector('input[name*="[year3]"]')?.value || 0);
        const totalInput = row.querySelector('.expense-total');
        const total = year1 + year2 + year3;
        
        year1Total += year1;
        year2Total += year2;
        year3Total += year3;
        grandTotal += total;
        
        if (totalInput) {
            totalInput.value = total.toFixed(2).replace('.', ',');
        }
    });
    
    const year1TotalInput = document.getElementById('expenseYear1Total');
    const year2TotalInput = document.getElementById('expenseYear2Total');
    const year3TotalInput = document.getElementById('expenseYear3Total');
    const grandTotalInput = document.getElementById('expenseGrandTotal');
    
    if (year1TotalInput) {
        year1TotalInput.value = year1Total.toFixed(2).replace('.', ',');
    }
    if (year2TotalInput) {
        year2TotalInput.value = year2Total.toFixed(2).replace('.', ',');
    }
    if (year3TotalInput) {
        year3TotalInput.value = year3Total.toFixed(2).replace('.', ',');
    }
    if (grandTotalInput) {
        grandTotalInput.value = grandTotal.toFixed(2).replace('.', ',');
    }
}

function addExpenseRow(category) {
    const tbody = document.getElementById('expenseProjectionTableBody');
    const categoryRows = Array.from(tbody.querySelectorAll('tr.category-header'));
    
    let targetRow = null;
    if (category === 'investment') {
        targetRow = categoryRows.find(row => row.textContent.includes('Investicioni troškovi'));
    } else {
        targetRow = categoryRows.find(row => row.textContent.includes('Tekući troškovi'));
    }
    
    if (!targetRow) return;
    
    // Pronađi poslednji red u toj kategoriji
    let currentRow = targetRow.nextElementSibling;
    let lastRowInCategory = targetRow;
    
    while (currentRow && !currentRow.classList.contains('category-header')) {
        lastRowInCategory = currentRow;
        currentRow = currentRow.nextElementSibling;
    }
    
    // Broj redova u kategoriji
    const categoryRowsList = Array.from(tbody.querySelectorAll(`tr[data-category="${category}"]`));
    const rowIndex = categoryRowsList.length;
    
    const newRow = document.createElement('tr');
    newRow.setAttribute('data-category', category);
    
    const fieldName = category === 'investment' ? 'investment_expenses' : 'operating_expenses';
    
    newRow.innerHTML = `
        <td><input type="text" name="${fieldName}[${rowIndex}][type]" class="form-control" placeholder="Npr. ${category === 'investment' ? 'Alati, oprema...' : 'Sirovine, renta...'}"></td>
        <td><input type="number" name="${fieldName}[${rowIndex}][year1]" class="form-control expense-year" step="0.01" min="0" oninput="calculateExpenseRowTotal(this)"></td>
        <td><input type="number" name="${fieldName}[${rowIndex}][year2]" class="form-control expense-year" step="0.01" min="0" oninput="calculateExpenseRowTotal(this)"></td>
        <td><input type="number" name="${fieldName}[${rowIndex}][year3]" class="form-control expense-year" step="0.01" min="0" oninput="calculateExpenseRowTotal(this)"></td>
        <td><input type="text" class="form-control expense-total" value="0.00" readonly style="background: #f9fafb;"></td>
        <td><button type="button" class="btn-secondary" onclick="removeTableRow(this)">Ukloni</button></td>
    `;
    
    lastRowInCategory.insertAdjacentElement('afterend', newRow);
    calculateExpenseGrandTotal();
}

// Pozovi funkcije za računanje pri učitavanju stranice
document.addEventListener('DOMContentLoaded', function() {
    // Read-only mod - onemogući sva polja ako je readOnly = true
    @if($readOnly ?? false)
        const form = document.getElementById('businessPlanForm');
        if (form) {
            // Onemogući sva polja u formi (readonly za input/textarea, disabled za select i button)
            const allFields = form.querySelectorAll('input, select, textarea');
            allFields.forEach(field => {
                if (field.type !== 'hidden' && field.type !== 'submit' && field.type !== 'button') {
                    if (field.tagName === 'SELECT' || field.type === 'checkbox' || field.type === 'radio') {
                        field.setAttribute('disabled', 'disabled');
                    } else {
                        field.setAttribute('readonly', 'readonly');
                    }
                    field.style.cursor = 'not-allowed';
                    field.style.backgroundColor = '#f9fafb';
                }
            });
            
            // Sakrij sva dugmad za dodavanje redova
            const addRowButtons = form.querySelectorAll('button[type="button"]');
            addRowButtons.forEach(button => {
                button.style.display = 'none';
            });
        }
    @endif
    
    calculateFundingTotal();
    calculateRevenueGrandTotal();
    calculateExpenseGrandTotal();

    // Funkcionalnost za dinamičko dugme na formi biznis plana
    const bpForm = document.getElementById('businessPlanForm');
    const bpSubmitBtn = document.getElementById('bpSubmitBtn');
    const bpSubmitBtnText = document.getElementById('bpSubmitBtnText');

    // Obavezna polja koja treba provjeriti
    const requiredFields = [
        'business_idea_name',
        'applicant_name',
        'applicant_jmbg',
        'applicant_address',
        'applicant_phone',
        'applicant_email',
        'summary'
    ];

    // Funkcija za provjeru da li su sva obavezna polja popunjena
    function checkRequiredFields() {
        let allFilled = true;
        
        requiredFields.forEach(fieldName => {
            const field = bpForm.querySelector(`[name="${fieldName}"]`);
            if (field) {
                const value = field.value ? field.value.trim() : '';
                if (!value) {
                    allFilled = false;
                }
            } else {
                allFilled = false;
            }
        });
        
        return allFilled;
    }

    // Funkcija za ažuriranje teksta dugmeta i informacije
    function updateSubmitButton() {
        if (!bpForm || !bpSubmitBtn) return;
        
        const allFilled = checkRequiredFields();
        
        if (allFilled) {
            // Ako su sva polja popunjena, prikaži "Sačuvaj Biznis plan"
            bpSubmitBtnText.textContent = 'Sačuvaj Biznis plan';
            bpSubmitBtn.className = 'btn-primary';
            bpSubmitBtn.style.padding = '12px 24px';
            bpSubmitBtn.style.fontSize = '14px';
        } else {
            // Ako nisu sva polja popunjena, prikaži "Sačuvaj Nacrt plana"
            bpSubmitBtnText.textContent = 'Sačuvaj Nacrt plana';
            bpSubmitBtn.className = 'btn-secondary';
            bpSubmitBtn.style.padding = '12px 24px';
            bpSubmitBtn.style.fontSize = '14px';
        }
    }

    // Funkcija za rukovanje klikom na dugme (samo ako nije readOnly)
    // Funkcija za re-indeksiranje svih name atributa u dinamičkim tabelama
    function reindexTableRows() {
        // Lista svih tabela koje treba re-indeksirati
        const tableIds = [
            'productsServicesTableBody',
            'pricingTableBody',
            'revenueShareTableBody',
            'suppliersTableBody',
            'targetCustomersTableBody',
            'salesLocationsTableBody',
            'employmentStructureTableBody',
            'businessHistoryTableBody',
            'jobScheduleTableBody',
            'fundingSourcesTableBody',
            'revenueProjectionTableBody',
            'expenseProjectionTableBody'
        ];
        
        tableIds.forEach(tableId => {
            const tbody = document.getElementById(tableId);
            if (!tbody) return;
            
            const rows = tbody.querySelectorAll('tr:not(.category-header)');
            rows.forEach((row, index) => {
                const inputs = row.querySelectorAll('input[name], textarea[name]');
                inputs.forEach(input => {
                    const name = input.name;
                    // Pronađi ime tabele i polje iz name atributa
                    const match = name.match(/^([^\[]+)\[\d+\]\[([^\]]+)\]$/);
                    if (match) {
                        const tableName = match[1];
                        const fieldName = match[2];
                        // Ažuriraj indeks
                        input.name = `${tableName}[${index}][${fieldName}]`;
                    }
                });
            });
        });
    }
    
    function handleSubmit(e) {
        @if($readOnly ?? false)
            e.preventDefault();
            return false;
        @endif
        
        e.preventDefault();
        
        // Re-indeksiraj sve name atribute prije slanja forme
        reindexTableRows();
        
        const allFilled = checkRequiredFields();
        
        if (allFilled) {
            // Ako su sva polja popunjena, ukloni save_as_draft i pošalji formu
            const draftInput = bpForm.querySelector('input[name="save_as_draft"]');
            if (draftInput) {
                draftInput.remove();
            }
            bpForm.submit();
        } else {
            // Ako nisu sva polja popunjena, dodaj save_as_draft i ukloni required atribute
            let draftInput = bpForm.querySelector('input[name="save_as_draft"]');
            if (!draftInput) {
                draftInput = document.createElement('input');
                draftInput.type = 'hidden';
                draftInput.name = 'save_as_draft';
                draftInput.value = '1';
                bpForm.appendChild(draftInput);
            }

            // Ukloni sve required atribute da bi se omogućilo delimično čuvanje
            const requiredFieldsElements = bpForm.querySelectorAll('[required]');
            requiredFieldsElements.forEach(field => {
                field.removeAttribute('required');
            });

            bpForm.submit();
        }
    }

    if (bpForm && bpSubmitBtn) {
        @if(!($readOnly ?? false))
        // Postavi event listener za klik (samo ako nije readOnly)
        bpSubmitBtn.addEventListener('click', handleSubmit);
        
        // Postavi event listenere na sva obavezna polja za dinamičko ažuriranje
        requiredFields.forEach(fieldName => {
            const field = bpForm.querySelector(`[name="${fieldName}"]`);
            if (field) {
                field.addEventListener('input', updateSubmitButton);
                field.addEventListener('change', updateSubmitButton);
            }
        });
        
        // Ažuriraj dugme pri učitavanju stranice
        updateSubmitButton();
        @endif
        
        // Dinamičko mijenjanje teksta labela za "company_name" u zavisnosti od "Oblika registracije"
        const registrationFormInput = document.getElementById('registration_form');
        const companyNameLabel = document.getElementById('company_name_label');
        
        function updateCompanyNameLabel() {
            if (!registrationFormInput || !companyNameLabel) return;
            
            const registrationForm = registrationFormInput.value.trim().toLowerCase();
            
            if (registrationForm === 'preduzetnik') {
                companyNameLabel.textContent = 'Ime i prezime preduzetnice i trgovački naziv za oblik registracije "Preduzetnik":';
            } else if (registrationForm !== '') {
                // Za DOO ili ostale oblike registracije
                companyNameLabel.textContent = 'Ime i prezime nosioca biznisa* i naziv društva za oblik registracije "' + registrationFormInput.value + '" ili ostali:';
            } else {
                // Default tekst ako nije ništa uneseno
                companyNameLabel.textContent = 'Ime i prezime preduzetnice i trgovački naziv za oblik registracije "Preduzetnik", odnosno ime i prezime nosioca biznisa* i naziv društva za oblik registracije "DOO":';
            }
        }
        
        // Ažuriraj label pri učitavanju stranice
        if (registrationFormInput && companyNameLabel) {
            updateCompanyNameLabel();
            // Pratiti promjene u polju
            registrationFormInput.addEventListener('input', updateCompanyNameLabel);
            registrationFormInput.addEventListener('change', updateCompanyNameLabel);
        }
    }
});
</script>
@endsection
