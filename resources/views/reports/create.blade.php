@extends('layouts.app')

@section('content')
<style>
    :root {
        --primary: #0B3D91;
        --primary-dark: #0A347B;
    }
    .report-page {
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
    .form-card {
        background: #fff;
        border-radius: 16px;
        padding: 32px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        margin-bottom: 24px;
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
    .btn-primary {
        background: var(--primary);
        color: #fff;
        padding: 12px 24px;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
    }
    .btn-primary:hover {
        background: var(--primary-dark);
    }
    .error-message {
        color: #ef4444;
        font-size: 12px;
        margin-top: 4px;
    }
    .info-box {
        background: #f0f9ff;
        border-left: 4px solid var(--primary);
        padding: 16px;
        border-radius: 8px;
        margin-bottom: 24px;
        font-size: 14px;
        color: #374151;
    }
    .info-box ul {
        margin: 8px 0 0 20px;
        padding: 0;
    }
    .info-box li {
        margin: 4px 0;
    }
    .file-upload-group {
        background: #f9fafb;
        padding: 16px;
        border-radius: 8px;
        border: 1px solid #e5e7eb;
        margin-bottom: 16px;
    }
    .file-upload-group label {
        font-weight: 600;
        color: var(--primary);
        margin-bottom: 8px;
    }
    input[type="file"] {
        width: 100%;
        padding: 8px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        background: #fff;
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
</style>

<div class="report-page">
    <div class="container mx-auto px-4">
        <div class="page-header">
            <h1>IZVJEŠTAJ O REALIZACIJI BIZNIS PLANA</h1>
        </div>

        <div class="info-box">
            <p><strong>Napomene:</strong></p>
            <ul>
                <li>Izvještaj prilagoditi proširivanjem tabela po potrebi.</li>
                <li>Potpis i pečat su obavezni.</li>
                <li>Sastavni dio ovog izvještaja su prilozi: finansijski izvještaj (Obrazac 4a), fakture, izvodi sa banke ili nalozi za plaćanje (žute uplatnice).</li>
                <li>Odgovori na sva pitanja moraju se odnositi na izvještajni period.</li>
            </ul>
        </div>

        <form method="POST" action="{{ route('reports.store', $application) }}" enctype="multipart/form-data">
            @csrf

            <!-- Osnovni podaci -->
            <div class="form-card">
                <h2 style="font-size: 20px; font-weight: 700; color: var(--primary); margin-bottom: 20px; padding-bottom: 12px; border-bottom: 2px solid #e5e7eb;">
                    Osnovni podaci
                </h2>

                <div class="form-group">
                    <label class="form-label">Ime i prezime preduzetnice/nosioca biznisa: <span class="required">*</span></label>
                    <input type="text" name="entrepreneur_name" class="form-control @error('entrepreneur_name') error @enderror" value="{{ old('entrepreneur_name', $report->entrepreneur_name ?? $application->user->name ?? '') }}" required>
                    @error('entrepreneur_name')
                        <div class="error-message">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Pravni status i naziv biznisa: <span class="required">*</span></label>
                    <input type="text" name="legal_status" class="form-control @error('legal_status') error @enderror" value="{{ old('legal_status', $report->legal_status ?? '') }}" required placeholder="npr. Preduzetnik - Ime Prezime / DOO - Naziv društva">
                    @error('legal_status')
                        <div class="error-message">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Naziv biznis plana: <span class="required">*</span></label>
                    <input type="text" name="business_plan_name" class="form-control @error('business_plan_name') error @enderror" value="{{ old('business_plan_name', $report->business_plan_name ?? $application->business_plan_name ?? '') }}" required>
                    @error('business_plan_name')
                        <div class="error-message">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Iznos odobrenih sredstava: <span class="required">*</span></label>
                        <input type="number" name="approved_amount" class="form-control @error('approved_amount') error @enderror" value="{{ old('approved_amount', $report->approved_amount ?? $application->approved_amount ?? '') }}" step="0.01" min="0" required>
                        @error('approved_amount')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Broj ugovora:</label>
                        <input type="text" name="contract_number" class="form-control @error('contract_number') error @enderror" value="{{ old('contract_number', $report->contract_number ?? ($application->contract ? 'Ugovor #' . $application->contract->id : '')) }}">
                        @error('contract_number')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Izvještajni period - Od: <span class="required">*</span></label>
                        <input type="date" name="report_period_start" class="form-control @error('report_period_start') error @enderror" value="{{ old('report_period_start', $report->report_period_start ? $report->report_period_start->format('Y-m-d') : '') }}" required>
                        @error('report_period_start')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Izvještajni period - Do: <span class="required">*</span></label>
                        <input type="date" name="report_period_end" class="form-control @error('report_period_end') error @enderror" value="{{ old('report_period_end', $report->report_period_end ? $report->report_period_end->format('Y-m-d') : '') }}" required>
                        @error('report_period_end')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Pitanja -->
            <div class="form-card">
                <h2 style="font-size: 20px; font-weight: 700; color: var(--primary); margin-bottom: 20px; padding-bottom: 12px; border-bottom: 2px solid #e5e7eb;">
                    Pitanja
                </h2>

                <div class="form-group">
                    <label class="form-label">1. Ukratko opišite sve aktivnosti u izvještajnom periodu. <span class="required">*</span></label>
                    <textarea name="activities_description" class="form-control @error('activities_description') error @enderror" rows="6" required>{{ old('activities_description', $report->activities_description ?? '') }}</textarea>
                    @error('activities_description')
                        <div class="error-message">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">2. Kratak opis uočenih problema:</label>
                    <textarea name="problems_description" class="form-control @error('problems_description') error @enderror" rows="6">{{ old('problems_description', $report->problems_description ?? '') }}</textarea>
                    @error('problems_description')
                        <div class="error-message">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">3. Kratak opis uočenih uspjeha:</label>
                    <textarea name="successes_description" class="form-control @error('successes_description') error @enderror" rows="6">{{ old('successes_description', $report->successes_description ?? '') }}</textarea>
                    @error('successes_description')
                        <div class="error-message">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">4. Unesite broj novozaposlenih lica (uključujući samozapošljavanje), vrstu ugovora i period na koji je ugovor o radu zaključen.</label>
                    <textarea name="new_employees" class="form-control @error('new_employees') error @enderror" rows="6">{{ old('new_employees', $report->new_employees ?? '') }}</textarea>
                    @error('new_employees')
                        <div class="error-message">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">5. Da li ste plasirali novi proizvod/uslugu? Ako jeste, ukratko opišite proizvod/uslugu i način plasiranja.</label>
                    <textarea name="new_product_service" class="form-control @error('new_product_service') error @enderror" rows="6">{{ old('new_product_service', $report->new_product_service ?? '') }}</textarea>
                    @error('new_product_service')
                        <div class="error-message">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">6. Navedite što ste nabavili koristeći odobrena sredstva.</label>
                    <textarea name="purchases_description" class="form-control @error('purchases_description') error @enderror" rows="6">{{ old('purchases_description', $report->purchases_description ?? '') }}</textarea>
                    @error('purchases_description')
                        <div class="error-message">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">7. Navedite eventualna odstupanja od biznis plana i obrazložite.</label>
                    <textarea name="deviations_description" class="form-control @error('deviations_description') error @enderror" rows="6">{{ old('deviations_description', $report->deviations_description ?? '') }}</textarea>
                    @error('deviations_description')
                        <div class="error-message">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">8. Da li ste zadovoljni saradnjom sa Opštinom Kotor – Sekretarijatom za razvoj preduzetništva, komunalne poslove i saobraćaj? <span class="required">*</span></label>
                    <div class="radio-group">
                        <div class="radio-option">
                            <input type="radio" name="satisfaction_with_cooperation" value="da" id="satisfaction_yes" {{ old('satisfaction_with_cooperation', $report->satisfaction_with_cooperation ?? '') === 'da' ? 'checked' : '' }} required>
                            <label for="satisfaction_yes">Da</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" name="satisfaction_with_cooperation" value="ne" id="satisfaction_no" {{ old('satisfaction_with_cooperation', $report->satisfaction_with_cooperation ?? '') === 'ne' ? 'checked' : '' }} required>
                            <label for="satisfaction_no">Ne</label>
                        </div>
                    </div>
                    @error('satisfaction_with_cooperation')
                        <div class="error-message">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">9. Da li imate preporuke za unapređenje saradanje privatnog sektora i Opštine Kotor?</label>
                    <textarea name="recommendations" class="form-control @error('recommendations') error @enderror" rows="6">{{ old('recommendations', $report->recommendations ?? '') }}</textarea>
                    @error('recommendations')
                        <div class="error-message">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">10. Da li ćete i ubuduće aplicirati za sredstva za podršku ženskom preduzetništvu? <span class="required">*</span></label>
                    <div class="radio-group">
                        <div class="radio-option">
                            <input type="radio" name="will_apply_again" value="da" id="apply_yes" {{ old('will_apply_again', $report->will_apply_again ?? '') === 'da' ? 'checked' : '' }} required>
                            <label for="apply_yes">Da</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" name="will_apply_again" value="ne" id="apply_no" {{ old('will_apply_again', $report->will_apply_again ?? '') === 'ne' ? 'checked' : '' }} required>
                            <label for="apply_no">Ne</label>
                        </div>
                    </div>
                    @error('will_apply_again')
                        <div class="error-message">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <!-- Prilozi -->
            <div class="form-card">
                <h2 style="font-size: 20px; font-weight: 700; color: var(--primary); margin-bottom: 20px; padding-bottom: 12px; border-bottom: 2px solid #e5e7eb;">
                    Prilozi
                </h2>

                <div class="file-upload-group">
                    <label class="form-label">Finansijski izvještaj (Obrazac 4a) <span class="required">*</span></label>
                    <input type="file" name="financial_report_file" class="form-control @error('financial_report_file') error @enderror" accept=".pdf,.doc,.docx,.xls,.xlsx" {{ !$report || !$report->financial_report_file ? 'required' : '' }}>
                    <div style="font-size: 12px; color: #6b7280; margin-top: 4px;">
                        Dozvoljeni formati: PDF, DOC, DOCX, XLS, XLSX (max 10MB)
                    </div>
                    @if($report && $report->financial_report_file)
                        <div style="margin-top: 8px; color: #10b981; font-size: 12px;">
                            ✓ Fajl je već upload-ovan
                        </div>
                    @endif
                    @error('financial_report_file')
                        <div class="error-message">{{ $message }}</div>
                    @enderror
                </div>

                <div class="file-upload-group">
                    <label class="form-label">Fakture <span class="required">*</span></label>
                    <input type="file" name="invoices_file" class="form-control @error('invoices_file') error @enderror" accept=".pdf,.jpg,.jpeg,.png,.zip" {{ !$report || !$report->invoices_file ? 'required' : '' }}>
                    <div style="font-size: 12px; color: #6b7280; margin-top: 4px;">
                        Dozvoljeni formati: PDF, JPG, JPEG, PNG (max 10MB). Možete upload-ovati više faktura u jednom PDF-u ili ZIP arhivi.
                    </div>
                    @if($report && $report->invoices_file)
                        <div style="margin-top: 8px; color: #10b981; font-size: 12px;">
                            ✓ Fajl je već upload-ovan
                        </div>
                    @endif
                    @error('invoices_file')
                        <div class="error-message">{{ $message }}</div>
                    @enderror
                </div>

                <div class="file-upload-group">
                    <label class="form-label">Izvod sa žiro računa banke ili nalog za prenos sredstava (žuta uplatnica) <span class="required">*</span></label>
                    <input type="file" name="bank_statement_file" class="form-control @error('bank_statement_file') error @enderror" accept=".pdf,.jpg,.jpeg,.png" {{ !$report || !$report->bank_statement_file ? 'required' : '' }}>
                    <div style="font-size: 12px; color: #6b7280; margin-top: 4px;">
                        Dozvoljeni formati: PDF, JPG, JPEG, PNG (max 10MB)
                    </div>
                    @if($report && $report->bank_statement_file)
                        <div style="margin-top: 8px; color: #10b981; font-size: 12px;">
                            ✓ Fajl je već upload-ovan
                        </div>
                    @endif
                    @error('bank_statement_file')
                        <div class="error-message">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="form-card" style="text-align: center;">
                <button type="submit" class="btn-primary">
                    Sačuvaj izvještaj
                </button>
                <p style="color: #6b7280; font-size: 14px; margin-top: 16px;">
                    Kotor, {{ date('d.m.Y') }} god.
                </p>
                <p style="color: #6b7280; font-size: 14px; margin-top: 8px;">
                    Potpis: _______________________
                </p>
                <a href="{{ route('applications.show', $application) }}" style="margin-left: 12px; color: #6b7280; text-decoration: none;">Otkaži</a>
            </div>
        </form>
    </div>
</div>
@endsection
