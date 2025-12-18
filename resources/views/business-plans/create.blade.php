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
        padding: 16px 0;
    }
    @media (min-width: 768px) {
        .business-plan-page { padding: 24px 0; }
    }
    .page-header {
        background: linear-gradient(90deg, var(--primary), var(--primary-dark));
        color: #fff;
        padding: 16px 20px;
        border-radius: 12px;
        margin-bottom: 20px;
    }
    @media (min-width: 768px) {
        .page-header { padding: 24px; border-radius: 16px; }
    }
    .page-header h1 {
        color: #fff; font-size: 18px; font-weight: 700; margin: 0 0 4px;
    }
    @media (min-width: 768px) {
        .page-header h1 { font-size: 24px; }
    }
    .form-card {
        background: #fff; border-radius: 12px; padding: 20px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px;
    }
    @media (min-width: 768px) {
        .form-card { border-radius: 16px; padding: 32px; margin-bottom: 24px; }
    }
    .form-section h2 {
        font-size: 16px; font-weight: 700; color: var(--primary);
        display: flex; align-items: center; gap: 10px;
        margin: 0 0 16px; padding-bottom: 10px; border-bottom: 2px solid #e5e7eb;
    }
    .section-num {
        width: 24px; height: 24px; background: var(--primary); color: #fff;
        border-radius: 50%; display: flex; align-items: center; justify-content: center;
        font-size: 12px; flex-shrink: 0;
    }
    .form-group { margin-bottom: 16px; }
    .form-label {
        display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px;
    }
    .form-control {
        width: 100%; padding: 10px 12px; border: 1px solid #d1d5db;
        border-radius: 8px; font-size: 14px; font-family: inherit;
    }
    textarea.form-control { min-height: 100px; resize: vertical; }
    .form-text { font-size: 11px; color: #6b7280; margin-top: 4px; line-height: 1.4; }
    
    .btn-primary {
        background: var(--primary); color: #fff; padding: 12px 24px;
        border-radius: 8px; font-weight: 600; font-size: 15px;
        width: 100%; text-align: center; border: none; cursor: pointer;
    }
    @media (min-width: 640px) { .btn-primary { width: auto; padding: 12px 40px; } }
</style>

<div class="business-plan-page">
    <div class="container mx-auto px-4">
        <div class="page-header">
            <h1>Obrazac 2 - Biznis plan</h1>
            <p style="color:rgba(255,255,255,0.8); font-size:12px; margin:0;">Popunite detalje vaše poslovne ideje</p>
        </div>

        <form method="POST" action="{{ route('applications.business-plan.store', $application) }}">
            @csrf

            <!-- I. Osnovni podaci -->
            <div class="form-card">
                <div class="form-section">
                    <h2><span class="section-num">I</span> Osnovni podaci</h2>
                    <div class="form-group">
                        <label class="form-label">Naziv biznis ideje <span style="color:#ef4444">*</span></label>
                        <input type="text" name="business_idea_name" class="form-control" value="{{ old('business_idea_name', $businessPlan->business_idea_name ?? '') }}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Podaci o podnosiocu <span style="color:#ef4444">*</span></label>
                        <textarea name="applicant_data" class="form-control" required>{{ old('applicant_data', $businessPlan->applicant_data ?? '') }}</textarea>
                        <p class="form-text">Ime, prezime, JMBG, adresa i kontakt podaci.</p>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Rezime <span style="color:#ef4444">*</span></label>
                        <textarea name="summary" class="form-control" required>{{ old('summary', $businessPlan->summary ?? '') }}</textarea>
                        <p class="form-text">Kratak opis vaše vizije i ciljeva.</p>
                    </div>
                </div>
            </div>

            <!-- II. Marketing -->
            <div class="form-card">
                <div class="form-section">
                    <h2><span class="section-num">II</span> Marketing</h2>
                    <div class="form-group">
                        <label class="form-label">Proizvod / Usluga <span style="color:#ef4444">*</span></label>
                        <textarea name="product_service" class="form-control" required>{{ old('product_service', $businessPlan->product_service ?? '') }}</textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Ciljno tržište i lokacija <span style="color:#ef4444">*</span></label>
                        <textarea name="location" class="form-control" required>{{ old('location', $businessPlan->location ?? '') }}</textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Cijene i promocija <span style="color:#ef4444">*</span></label>
                        <textarea name="pricing" class="form-control" required>{{ old('pricing', $businessPlan->pricing ?? '') }}</textarea>
                    </div>
                </div>
            </div>

            <!-- III. Poslovanje -->
            <div class="form-card">
                <div class="form-section">
                    <h2><span class="section-num">III</span> Poslovanje</h2>
                    <div class="form-group">
                        <label class="form-label">Analiza procesa rada <span style="color:#ef4444">*</span></label>
                        <textarea name="business_analysis" class="form-control" required>{{ old('business_analysis', $businessPlan->business_analysis ?? '') }}</textarea>
                    </div>
                </div>
            </div>

            <!-- IV. Finansije -->
            <div class="form-card">
                <div class="form-section">
                    <h2><span class="section-num">IV</span> Finansije</h2>
                    <div class="form-group">
                        <label class="form-label">Plan ulaganja i izvori <span style="color:#ef4444">*</span></label>
                        <textarea name="required_funds" class="form-control" required>{{ old('required_funds', $businessPlan->required_funds ?? '') }}</textarea>
                    </div>
                </div>
            </div>

            <!-- V. Ljudi i Rizici -->
            <div class="form-card">
                <div class="form-section">
                    <h2><span class="section-num">V</span> Ljudi i Rizici</h2>
                    <div class="form-group">
                        <label class="form-label">Kvalifikacije i iskustvo <span style="color:#ef4444">*</span></label>
                        <textarea name="entrepreneur_data" class="form-control" required>{{ old('entrepreneur_data', $businessPlan->entrepreneur_data ?? '') }}</textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Glavni rizici <span style="color:#ef4444">*</span></label>
                        <textarea name="risk_matrix" class="form-control" required>{{ old('risk_matrix', $businessPlan->risk_matrix ?? '') }}</textarea>
                    </div>
                </div>
            </div>

            <div style="text-align:center; margin-bottom:40px;">
                <button type="submit" class="btn-primary">SAČUVAJ BIZNIS PLAN</button>
                <p style="margin-top:15px; font-size:12px; color:#6b7280;">Nakon čuvanja, moći ćete da priložite ostala dokumenta.</p>
            </div>
        </form>
    </div>
</div>
@endsection
