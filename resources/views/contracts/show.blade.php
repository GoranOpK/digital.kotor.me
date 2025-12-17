@extends('layouts.app')

@section('content')
<style>
    :root {
        --primary: #0B3D91;
        --primary-dark: #0A347B;
    }
    .contract-page {
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
    .info-card {
        background: #fff;
        border-radius: 16px;
        padding: 24px;
        margin-bottom: 24px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    .info-card h2 {
        font-size: 20px;
        font-weight: 700;
        color: var(--primary);
        margin: 0 0 20px;
        padding-bottom: 12px;
        border-bottom: 2px solid #e5e7eb;
    }
    .status-badge {
        display: inline-block;
        padding: 6px 16px;
        border-radius: 9999px;
        font-size: 14px;
        font-weight: 600;
    }
    .status-draft {
        background: #fef3c7;
        color: #92400e;
    }
    .status-signed {
        background: #dbeafe;
        color: #1e40af;
    }
    .status-approved {
        background: #d1fae5;
        color: #065f46;
    }
    .upload-section {
        background: #f9fafb;
        padding: 20px;
        border-radius: 8px;
        margin-top: 16px;
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
    .form-control {
        width: 100%;
        padding: 10px 14px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        font-size: 14px;
    }
    .btn {
        padding: 12px 24px;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        display: inline-block;
        margin-right: 8px;
    }
    .btn-primary {
        background: var(--primary);
        color: #fff;
    }
    .btn-secondary {
        background: #6b7280;
        color: #fff;
    }
    .alert {
        border-radius: 12px;
        padding: 16px 20px;
        margin-bottom: 20px;
        border: 1px solid;
    }
    .alert-success {
        background: #d1fae5;
        border-color: #10b981;
        color: #065f46;
    }
    .alert-info {
        background: #dbeafe;
        border-color: #3b82f6;
        color: #1e40af;
    }
</style>

<div class="contract-page">
    <div class="container mx-auto px-4">
        <div class="page-header">
            <h1>Ugovor</h1>
        </div>

        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        <div class="info-card">
            <h2>Status ugovora</h2>
            <p><strong>Status:</strong> 
                <span class="status-badge status-{{ $contract->status }}">
                    @if($contract->status === 'draft') Nacrt
                    @elseif($contract->status === 'signed') Potpisan
                    @elseif($contract->status === 'approved') Potvrđen
                    @else {{ $contract->status }}
                    @endif
                </span>
            </p>
            @if($contract->signed_at)
                <p><strong>Datum potpisivanja:</strong> {{ $contract->signed_at->format('d.m.Y H:i') }}</p>
            @endif
            <p><strong>Naziv biznis plana:</strong> {{ $contract->application->business_plan_name }}</p>
            <p><strong>Podnosilac:</strong> {{ $contract->application->user->name ?? 'N/A' }}</p>
            <p><strong>Odobreni iznos:</strong> {{ number_format($contract->application->approved_amount ?? 0, 2, ',', '.') }} €</p>
        </div>

        @if($contract->contract_file)
            <div class="info-card">
                <h2>Ugovor</h2>
                <p style="margin-bottom: 16px;">Ugovor je dostupan za preuzimanje.</p>
                <a href="{{ route('contracts.download', $contract) }}" class="btn btn-primary">Preuzmi ugovor</a>
            </div>
        @endif

        @if($contract->application->user_id === Auth::id() && $contract->status === 'draft')
            <div class="info-card">
                <h2>Upload potpisanog ugovora</h2>
                <div class="upload-section">
                    <form method="POST" action="{{ route('contracts.upload', $contract) }}" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group">
                            <label class="form-label">Potpisani ugovor (PDF) *</label>
                            <input type="file" name="signed_contract" class="form-control" accept=".pdf" required>
                            <div style="font-size: 12px; color: #6b7280; margin-top: 4px;">
                                Upload-ujte potpisani ugovor u PDF formatu (max 10MB)
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Upload-uj potpisani ugovor</button>
                    </form>
                </div>
            </div>
        @endif

        @if(Auth::user()->role && (Auth::user()->role->name === 'admin' || Auth::user()->role->name === 'superadmin'))
            @if($contract->status === 'signed')
                <div class="info-card">
                    <h2>Potvrda ugovora</h2>
                    <form method="POST" action="{{ route('contracts.approve', $contract) }}">
                        @csrf
                        <button type="submit" class="btn btn-primary">Potvrdi ugovor</button>
                    </form>
                </div>
            @endif
        @endif

        <div style="text-align: center; margin-top: 24px;">
            <a href="{{ route('applications.show', $contract->application) }}" class="btn btn-secondary">Nazad na prijavu</a>
        </div>
    </div>
</div>
@endsection

