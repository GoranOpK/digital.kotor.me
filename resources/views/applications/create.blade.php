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
        padding: 16px 0;
    }
    @media (min-width: 768px) {
        .application-form-page {
            padding: 24px 0;
        }
    }
    .page-header {
        background: linear-gradient(90deg, var(--primary), var(--primary-dark));
        color: #fff;
        padding: 16px 20px;
        border-radius: 12px;
        margin-bottom: 20px;
    }
    @media (min-width: 768px) {
        .page-header {
            padding: 24px;
            border-radius: 16px;
        }
    }
    .page-header h1 {
        color: #fff;
        font-size: 18px;
        font-weight: 700;
        margin: 0 0 4px;
    }
    @media (min-width: 768px) {
        .page-header h1 {
            font-size: 24px;
        }
    }
    .form-card {
        background: #fff;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        margin-bottom: 20px;
    }
    @media (min-width: 768px) {
        .form-card {
            border-radius: 16px;
            padding: 32px;
            margin-bottom: 24px;
        }
    }
    .form-section h2 {
        font-size: 16px;
        font-weight: 700;
        color: var(--primary);
        margin: 0 0 16px;
        padding-bottom: 10px;
        border-bottom: 2px solid #e5e7eb;
    }
    @media (min-width: 768px) {
        .form-section h2 {
            font-size: 18px;
        }
    }
    .form-group { margin-bottom: 16px; }
    .form-label {
        display: block;
        font-size: 13px;
        font-weight: 600;
        color: #374151;
        margin-bottom: 6px;
    }
    .form-control {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        font-size: 14px;
    }
    .form-row {
        display: grid;
        grid-template-columns: 1fr;
        gap: 12px;
    }
    @media (min-width: 640px) {
        .form-row {
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
    }
    .radio-group, .checkbox-group {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    @media (min-width: 640px) {
        .radio-group {
            flex-direction: row;
            gap: 24px;
        }
    }
    .radio-option, .checkbox-option {
        display: flex;
        align-items: flex-start;
        gap: 10px;
    }
    .radio-option input, .checkbox-option input {
        margin-top: 3px;
        flex-shrink: 0;
    }
    .radio-option label, .checkbox-option label {
        font-size: 13px;
        color: #374151;
        line-height: 1.4;
    }
    .btn-primary {
        background: var(--primary);
        color: #fff;
        padding: 12px 24px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 15px;
        width: 100%;
        text-align: center;
        border: none;
        cursor: pointer;
    }
    @media (min-width: 640px) {
        .btn-primary { width: auto; padding: 12px 40px; }
    }
    .conditional-field { display: none; }
    .conditional-field.show { display: block; }
</style>

<div class="application-form-page">
    <div class="container mx-auto px-4">
        <div class="page-header">
            <h1>Obrazac 1a/1b - Nova prijava</h1>
            <p style="color: rgba(255,255,255,0.9); margin: 0; font-size: 12px;">{{ $competition->title }}</p>
        </div>

        <form method="POST" action="{{ route('applications.store', $competition) }}" id="applicationForm">
            @csrf

            <!-- Sekcija 1 -->
            <div class="form-card">
                <div class="form-section">
                    <h2>1. Osnovni podaci</h2>
                    <div class="form-group">
                        <label class="form-label">Naziv biznis plana <span style="color:#ef4444">*</span></label>
                        <input type="text" name="business_plan_name" class="form-control" value="{{ old('business_plan_name') }}" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Tip podnosioca <span style="color:#ef4444">*</span></label>
                            <div class="radio-group">
                                <div class="radio-option">
                                    <input type="radio" id="type_p" name="applicant_type" value="preduzetnica" {{ old('applicant_type', 'preduzetnica') === 'preduzetnica' ? 'checked' : '' }} required>
                                    <label for="type_p">Preduzetnica</label>
                                </div>
                                <div class="radio-option">
                                    <input type="radio" id="type_d" name="applicant_type" value="doo" {{ old('applicant_type') === 'doo' ? 'checked' : '' }} required>
                                    <label for="type_d">DOO</label>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Faza biznisa <span style="color:#ef4444">*</span></label>
                            <div class="radio-group">
                                <div class="radio-option">
                                    <input type="radio" id="stage_z" name="business_stage" value="započinjanje" {{ old('business_stage', 'započinjanje') === 'započinjanje' ? 'checked' : '' }} required>
                                    <label for="stage_z">Započinjanje</label>
                                </div>
                                <div class="radio-option">
                                    <input type="radio" id="stage_r" name="business_stage" value="razvoj" {{ old('business_stage') === 'razvoj' ? 'checked' : '' }} required>
                                    <label for="stage_r">Razvoj</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Registrovana djelatnost? <span style="color:#ef4444">*</span></label>
                        <div class="radio-group">
                            <div class="radio-option">
                                <input type="radio" id="reg_y" name="is_registered" value="1" {{ old('is_registered', '1') === '1' ? 'checked' : '' }} required>
                                <label for="reg_y">Da</label>
                            </div>
                            <div class="radio-option">
                                <input type="radio" id="reg_n" name="is_registered" value="0" {{ old('is_registered') === '0' ? 'checked' : '' }} required>
                                <label for="reg_n">Ne (Fizičko lice)</label>
                            </div>
                        </div>
                        <div id="registration_notice" style="display:none; font-size: 11px; color: #0B3D91; margin-top: 8px; background: #eff6ff; padding: 10px; border-radius: 6px;">
                            ℹ️ Ukoliko dobijete sredstva, obavezni ste registrovati djelatnost prije potpisivanja ugovora.
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Oblast biznisa <span style="color:#ef4444">*</span></label>
                        <input type="text" name="business_area" class="form-control" placeholder="Npr. IT, turizam, trgovina..." value="{{ old('business_area') }}" required>
                    </div>
                </div>
            </div>

            <!-- Sekcija 2 (DOO) -->
            <div class="form-card conditional-field" id="dooFields">
                <div class="form-section">
                    <h2>2. Podaci o firmi (DOO)</h2>
                    <div class="form-group">
                        <label class="form-label">Osnivač/ica <span style="color:#ef4444">*</span></label>
                        <input type="text" name="founder_name" class="form-control" value="{{ old('founder_name', auth()->user()->name) }}">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Izvršni direktor <span style="color:#ef4444">*</span></label>
                            <input type="text" name="director_name" class="form-control" value="{{ old('director_name', auth()->user()->name) }}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Sjedište <span style="color:#ef4444">*</span></label>
                            <input type="text" name="company_seat" class="form-control" value="{{ old('company_seat', auth()->user()->address) }}">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sekcija 3 -->
            <div class="form-card">
                <div class="form-section">
                    <h2>3. Finansije</h2>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Traženi iznos (€) <span style="color:#ef4444">*</span></label>
                            <input type="number" name="requested_amount" step="0.01" class="form-control" value="{{ old('requested_amount') }}" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Ukupan budžet (€) <span style="color:#ef4444">*</span></label>
                            <input type="number" name="total_budget_needed" step="0.01" class="form-control" value="{{ old('total_budget_needed') }}" required>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sekcija 4 -->
            <div class="form-card">
                <div class="form-section">
                    <h2>4. Izjave</h2>
                    <div class="checkbox-option" style="margin-bottom: 15px;">
                        <input type="checkbox" id="de_minimis" name="de_minimis_declaration" value="1" required>
                        <label for="de_minimis">Prihvatam uslove o <strong>de minimis</strong> pomoći. <span style="color:#ef4444">*</span></label>
                    </div>
                    <div class="checkbox-option">
                        <input type="checkbox" id="accuracy" name="accuracy_declaration" value="1" required>
                        <label for="accuracy">Garantujem za tačnost svih unesenih podataka. <span style="color:#ef4444">*</span></label>
                    </div>
                </div>
            </div>

            <div style="text-align: center; margin-bottom: 40px;">
                <button type="submit" class="btn-primary">Sačuvaj i nastavi na biznis plan →</button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const typeInputs = document.querySelectorAll('input[name="applicant_type"]');
        const regInputs = document.querySelectorAll('input[name="is_registered"]');
        const dooFields = document.getElementById('dooFields');
        const notice = document.getElementById('registration_notice');

        function toggle() {
            const isDoo = document.querySelector('input[name="applicant_type"]:checked')?.value === 'doo';
            const isNotReg = document.querySelector('input[name="is_registered"]:checked')?.value === '0';
            
            dooFields.style.display = isDoo ? 'block' : 'none';
            notice.style.display = isNotReg ? 'block' : 'none';
            
            const dooInputs = dooFields.querySelectorAll('input');
            dooInputs.forEach(input => {
                if (isDoo) input.setAttribute('required', 'required');
                else input.removeAttribute('required');
            });
        }

        typeInputs.forEach(i => i.addEventListener('change', toggle));
        regInputs.forEach(i => i.addEventListener('change', toggle));
        toggle();
    });
</script>
@endsection
