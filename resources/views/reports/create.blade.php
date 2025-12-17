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
        min-height: 200px;
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

<div class="report-page">
    <div class="container mx-auto px-4">
        <div class="page-header">
            <h1>Izvještaj o realizaciji - Obrazac 4</h1>
        </div>

        <div class="info-box">
            <p><strong>Naziv biznis plana:</strong> {{ $application->business_plan_name }}</p>
            <p><strong>Odobreni iznos:</strong> {{ number_format($application->approved_amount ?? 0, 2, ',', '.') }} €</p>
        </div>

        <div class="form-card">
            <form method="POST" action="{{ route('reports.store', $application) }}" enctype="multipart/form-data">
                @csrf

                <div class="form-group">
                    <label class="form-label">
                        Opis realizacije <span class="required">*</span>
                    </label>
                    <textarea 
                        name="description" 
                        class="form-control @error('description') error @enderror"
                        required
                        placeholder="Detaljno opišite kako je realizovan biznis plan, koje su aktivnosti sprovedene, koje su prepreke premošćene, i kakvi su rezultati..."
                    >{{ old('description', $report->description ?? '') }}</textarea>
                    @error('description')
                        <div class="error-message">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Dokument (opciono)</label>
                    <input 
                        type="file" 
                        name="document_file" 
                        class="form-control"
                        accept=".pdf,.doc,.docx"
                    >
                    <div style="font-size: 12px; color: #6b7280; margin-top: 4px;">
                        Dozvoljeni formati: PDF, DOC, DOCX (max 10MB)
                    </div>
                </div>

                <div style="margin-top: 24px; text-align: center;">
                    <button type="submit" class="btn-primary">Sačuvaj izvještaj</button>
                    <a href="{{ route('applications.show', $application) }}" style="margin-left: 12px; color: #6b7280;">Otkaži</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

