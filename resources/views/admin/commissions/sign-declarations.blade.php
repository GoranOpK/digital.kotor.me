@extends('layouts.app')

@section('content')
<style>
    :root {
        --primary: #0B3D91;
        --primary-dark: #0A347B;
    }
    .admin-page {
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
    .form-card h2 {
        font-size: 20px;
        font-weight: 700;
        color: var(--primary);
        margin: 0 0 20px;
        padding-bottom: 12px;
        border-bottom: 2px solid #e5e7eb;
    }
    .form-group {
        margin-bottom: 24px;
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
        min-height: 150px;
        resize: vertical;
    }
    .form-control:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(11, 61, 145, 0.1);
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
    .error-message {
        color: #ef4444;
        font-size: 12px;
        margin-top: 4px;
    }
    .info-box {
        background: #dbeafe;
        border-left: 4px solid #3b82f6;
        padding: 16px;
        border-radius: 8px;
        margin-bottom: 24px;
    }
</style>

<div class="admin-page">
    <div class="container mx-auto px-4">
        <div class="page-header">
            <h1>Potpisivanje izjava</h1>
        </div>

        <div class="info-box">
            <p><strong>Član komisije:</strong> {{ $member->name }}</p>
            <p><strong>Komisija:</strong> {{ $member->commission->name }}</p>
        </div>

        <form method="POST" action="{{ route('admin.commissions.members.store-declarations', $member) }}">
            @csrf

            <div class="form-card">
                <h2>Izjava o tajnosti</h2>
                <div class="form-group">
                    <label class="form-label">
                        Tekst izjave o tajnosti <span class="required">*</span>
                    </label>
                    <textarea 
                        name="confidentiality_declaration" 
                        class="form-control @error('confidentiality_declaration') error @enderror"
                        required
                        placeholder="Unesite tekst izjave o tajnosti..."
                    >{{ old('confidentiality_declaration', $member->confidentiality_declaration) }}</textarea>
                    @error('confidentiality_declaration')
                        <div class="error-message">{{ $message }}</div>
                    @enderror
                    <div style="font-size: 12px; color: #6b7280; margin-top: 4px;">
                        Primer: "Izjavljujem da ću sve informacije koje dobijem tokom ocjenjivanja prijava čuvati u tajnosti i neću ih koristiti u lične svrhe."
                    </div>
                </div>
            </div>

            <div class="form-card">
                <h2>Izjava o sukobu interesa</h2>
                <div class="form-group">
                    <label class="form-label">
                        Tekst izjave o sukobu interesa <span class="required">*</span>
                    </label>
                    <textarea 
                        name="conflict_of_interest_declaration" 
                        class="form-control @error('conflict_of_interest_declaration') error @enderror"
                        required
                        placeholder="Unesite tekst izjave o sukobu interesa..."
                    >{{ old('conflict_of_interest_declaration', $member->conflict_of_interest_declaration) }}</textarea>
                    @error('conflict_of_interest_declaration')
                        <div class="error-message">{{ $message }}</div>
                    @enderror
                    <div style="font-size: 12px; color: #6b7280; margin-top: 4px;">
                        Primer: "Izjavljujem da nemam lični ili finansijski interes u prijavama koje ocjenjujem i da ću se suzdržati od ocjenjivanja prijava gdje postoji sukob interesa."
                    </div>
                </div>
            </div>

            <div style="text-align: center; margin-top: 24px;">
                <button type="submit" class="btn-primary">Potpiši izjave</button>
                <a href="{{ route('admin.commissions.show', $member->commission) }}" style="margin-left: 12px; color: #6b7280;">Otkaži</a>
            </div>
        </form>
    </div>
</div>
@endsection

