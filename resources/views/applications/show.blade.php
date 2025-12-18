@extends('layouts.app')

@section('content')
<style>
    :root {
        --primary: #0B3D91;
        --primary-dark: #0A347B;
        --secondary: #B8860B;
    }
    .application-detail-page {
        background: #f9fafb;
        min-height: 100vh;
        padding: 16px 0;
    }
    @media (min-width: 768px) {
        .application-detail-page { padding: 24px 0; }
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
    .info-card {
        background: #fff;
        border-radius: 12px;
        padding: 16px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        margin-bottom: 16px;
    }
    @media (min-width: 768px) {
        .info-card { padding: 24px; margin-bottom: 24px; border-radius: 16px; }
    }
    .info-card h2 {
        font-size: 16px; font-weight: 700; color: var(--primary);
        margin: 0 0 16px; padding-bottom: 10px; border-bottom: 2px solid #e5e7eb;
    }
    .status-badge {
        display: inline-block; padding: 4px 12px; border-radius: 9999px;
        font-size: 12px; font-weight: 600;
    }
    .status-draft { background: #fef3c7; color: #92400e; }
    .status-submitted { background: #dbeafe; color: #1e40af; }
    .status-approved { background: #d1fae5; color: #065f46; }
    .status-rejected { background: #fee2e2; color: #991b1b; }

    .summary-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 16px;
        margin-bottom: 16px;
    }
    @media (min-width: 992px) {
        .summary-grid { grid-template-columns: repeat(3, 1fr); gap: 24px; margin-bottom: 24px; }
    }
    .info-item { display: flex; flex-direction: column; margin-bottom: 10px; }
    .info-label { font-size: 10px; font-weight: 600; color: #6b7280; text-transform: uppercase; margin-bottom: 2px; }
    .info-value { font-size: 13px; color: #111827; font-weight: 500; }

    .document-item {
        padding: 12px; border: 1px solid #e5e7eb; border-radius: 10px;
        margin-bottom: 10px; display: flex; flex-direction: column; gap: 12px;
    }
    @media (min-width: 640px) {
        .document-item { flex-direction: row; justify-content: space-between; align-items: center; }
    }
    .document-info { flex: 1; }
    .document-name { font-weight: 600; color: #111827; font-size: 13px; }
    .document-meta { font-size: 11px; color: #6b7280; margin-top: 2px; }
    
    .btn {
        padding: 10px 16px; border-radius: 8px; font-size: 13px; font-weight: 600;
        text-align: center; text-decoration: none; display: inline-block; border: none; cursor: pointer;
    }
    .btn-primary { background: var(--primary); color: #fff; width: 100%; }
    @media (min-width: 640px) { .btn-primary { width: auto; } }
    .btn-danger { background: #ef4444; color: #fff; }
    .btn-secondary { background: #6b7280; color: #fff; }

    .progress-bar { width: 100%; height: 6px; background: #e5e7eb; border-radius: 3px; overflow: hidden; margin: 8px 0; }
    .progress-fill { height: 100%; background: var(--primary); transition: width 0.3s; }
</style>

<div class="application-detail-page">
    <div class="container mx-auto px-4">
        <div class="page-header">
            <h1>Pregled prijave</h1>
            <p style="color: rgba(255,255,255,0.9); margin: 0; font-size: 12px;">{{ $application->business_plan_name }}</p>
        </div>

        @if(session('success'))
            <div style="background:#d1fae5; color:#065f46; padding:12px; border-radius:10px; margin-bottom:20px; font-size:13px;">
                {{ session('success') }}
            </div>
        @endif

        <div class="summary-grid">
            <!-- 1. Osnovni podaci -->
            <div class="info-card">
                <h2>Osnovni podaci</h2>
                <div class="info-item">
                    <span class="info-label">Tip / Faza</span>
                    <span class="info-value">{{ ucfirst($application->applicant_type) }} / {{ ucfirst($application->business_stage) }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Iznos podrške</span>
                    <span class="info-value" style="color:var(--primary); font-weight:700;">{{ number_format($application->requested_amount, 2, ',', '.') }} €</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Oblast</span>
                    <span class="info-value">{{ $application->business_area }}</span>
                </div>
            </div>

            <!-- 2. Akcije/Upload (Samo u draftu) -->
            @if($application->status === 'draft')
            <div class="info-card" style="background:#f0f9ff; border:1px solid #bae6fd;">
                <h2>Dodaj dokument</h2>
                <form method="POST" action="{{ route('applications.upload', $application) }}" enctype="multipart/form-data">
                    @csrf
                    <select name="document_type" class="btn" style="width:100%; background:#fff; border:1px solid #d1d5db; margin-bottom:10px; text-align:left;" required>
                        <option value="">Izaberi tip...</option>
                        @php
                            $requiredDocs = $application->getRequiredDocuments();
                            $uploadedTypes = $application->documents->pluck('document_type')->toArray();
                            $labels = [
                                'licna_karta' => 'Lična karta', 'crps_resenje' => 'CRPS', 'pib_resenje' => 'PIB',
                                'pdv_resenje' => 'PDV', 'statut' => 'Statut', 'biznis_plan_usb' => 'Biznis plan (USB)',
                                'ostalo' => 'Ostalo'
                            ];
                        @endphp
                        @foreach($requiredDocs as $docType)
                            @if(!in_array($docType, $uploadedTypes))
                                <option value="{{ $docType }}">{{ $labels[$docType] ?? $docType }}</option>
                            @endif
                        @endforeach
                        <option value="ostalo">Ostalo</option>
                    </select>
                    <input type="file" name="file" class="btn" style="width:100%; background:#fff; border:1px solid #d1d5db; margin-bottom:10px;">
                    <button type="submit" class="btn btn-primary">Priloži fajl</button>
                </form>
            </div>
            @endif

            <!-- 3. Status i BP -->
            <div class="info-card">
                <h2>Status i Biznis plan</h2>
                <div class="info-item">
                    <span class="info-label">Status</span>
                    <span class="status-badge status-{{ $application->status }}">{{ ucfirst($application->status) }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Biznis plan</span>
                    <span class="info-value">
                        @if($application->businessPlan)
                            <span style="color:#10b981;">✅ Popunjen</span>
                        @else
                            <span style="color:#ef4444;">❌ Nedostaje</span>
                        @endif
                        <a href="{{ route('applications.business-plan.create', $application) }}" style="margin-left:8px; color:#3b82f6;">{{ $application->businessPlan ? 'Uredi' : 'Popuni' }}</a>
                    </span>
                </div>
                @if($application->status === 'draft' && $isReadyToSubmit)
                    <form method="POST" action="{{ route('applications.final-submit', $application) }}" style="margin-top:10px;">
                        @csrf
                        <button type="submit" class="btn" style="background:#10b981; color:#fff; width:100%;">🚀 PODNESI PRIJAVU</button>
                    </form>
                @endif
            </div>
        </div>

        <!-- Dokumentacija -->
        <div class="info-card">
            <h2>Priložena dokumentacija</h2>
            @php
                $uploadedDocs = $application->documents;
                $totalReq = count($application->getRequiredDocuments());
                $uploadedReq = $uploadedDocs->whereIn('document_type', $application->getRequiredDocuments())->count();
                $perc = $totalReq > 0 ? round(($uploadedReq/$totalReq)*100) : 100;
            @endphp
            
            <div style="margin-bottom:20px;">
                <div style="display:flex; justify-content:space-between; font-size:12px; font-weight:600;">
                    <span>Obavezni dokumenti: {{ $uploadedReq }} / {{ $totalReq }}</span>
                    <span>{{ $perc }}%</span>
                </div>
                <div class="progress-bar"><div class="progress-fill" style="width:{{ $perc }}%"></div></div>
            </div>

            @foreach($uploadedDocs as $doc)
                <div class="document-item" style="border-left: 4px solid #10b981;">
                    <div class="document-info">
                        <div class="document-name">{{ $labels[$doc->document_type] ?? $doc->document_type }}</div>
                        <div class="document-meta">{{ $doc->name }} • {{ $doc->created_at->format('d.m.Y') }}</div>
                    </div>
                    <div style="display:flex; gap:8px;">
                        <a href="{{ route('applications.document.download', [$application, $doc]) }}" class="btn btn-secondary" style="padding:6px 12px; font-size:11px;">Preuzmi</a>
                        @if($application->status === 'draft')
                            <form action="{{ route('applications.document.destroy', [$application, $doc]) }}" method="POST">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger" style="padding:6px 12px; font-size:11px;">Ukloni</button>
                            </form>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Akcije -->
        <div style="display:flex; flex-direction:column; gap:10px; margin-bottom:40px;">
            <a href="{{ route('dashboard') }}" class="btn btn-secondary">Nazad na Dashboard</a>
            <form action="{{ route('applications.destroy', $application) }}" method="POST" onsubmit="return confirm('Sigurno želite obrisati CIJELU prijavu?');">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-danger" style="width:100%;">Obriši kompletnu prijavu</button>
            </form>
        </div>
    </div>
</div>
@endsection
