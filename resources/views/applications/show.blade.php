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
    .info-card {
        background: #fff;
        border-radius: 16px;
        padding: 32px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        margin-bottom: 24px;
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
    .status-submitted {
        background: #dbeafe;
        color: #1e40af;
    }
    .status-evaluated {
        background: #d1fae5;
        color: #065f46;
    }
    .status-approved {
        background: #d1fae5;
        color: #065f46;
    }
    .status-rejected {
        background: #fee2e2;
        color: #991b1b;
    }
    .info-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 16px;
    }
    @media (min-width: 768px) {
        .info-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    .info-item {
        display: flex;
        flex-direction: column;
    }
    .info-label {
        font-size: 12px;
        font-weight: 600;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 4px;
    }
    .info-value {
        font-size: 14px;
        color: #111827;
        font-weight: 500;
    }
    .documents-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    .document-item {
        padding: 16px;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        margin-bottom: 12px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .document-item.required {
        border-left: 4px solid #ef4444;
    }
    .document-item.uploaded {
        border-left: 4px solid #10b981;
    }
    .document-info {
        flex: 1;
    }
    .document-name {
        font-weight: 600;
        color: #111827;
        margin-bottom: 4px;
    }
    .document-type {
        font-size: 12px;
        color: #6b7280;
    }
    .document-actions {
        display: flex;
        gap: 8px;
    }
    .btn {
        padding: 8px 16px;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 600;
        text-decoration: none;
        cursor: pointer;
        border: none;
        transition: all 0.2s;
    }
    .btn-primary {
        background: var(--primary);
        color: #fff;
    }
    .btn-primary:hover {
        background: var(--primary-dark);
    }
    .btn-danger {
        background: #ef4444;
        color: #fff;
    }
    .btn-danger:hover {
        background: #dc2626;
    }
    .btn-secondary {
        background: #6b7280;
        color: #fff;
    }
    .btn-secondary:hover {
        background: #4b5563;
    }
    .upload-section {
        background: #f3f4f6;
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
    .form-control:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(11, 61, 145, 0.1);
    }
    .file-input-wrapper {
        position: relative;
        display: inline-block;
        width: 100%;
        min-height: 40px;
    }
    input[type="file"] {
        position: absolute;
        opacity: 0;
        width: 100%;
        height: 40px;
        cursor: pointer;
        z-index: 2;
        top: 0;
        left: 0;
    }
    .file-input-label-custom {
        display: inline-block;
        background: var(--primary);
        color: #fff;
        padding: 8px 16px;
        border-radius: 6px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 600;
        transition: background 0.2s;
    }
    .file-input-label-custom:hover {
        background: var(--primary-dark);
    }
    .file-input-wrapper:hover .file-input-label-custom {
        background: var(--primary-dark);
    }
    .file-name-display {
        margin-top: 8px;
        font-size: 12px;
        color: var(--primary);
        font-weight: 600;
    }
    .error-message {
        color: #ef4444;
        font-size: 12px;
        margin-top: 4px;
    }
    .form-control.error {
        border-color: #ef4444;
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
    .alert-warning {
        background: #fef3c7;
        border-color: #f59e0b;
        color: #92400e;
    }
    .progress-bar {
        width: 100%;
        height: 8px;
        background: #e5e7eb;
        border-radius: 9999px;
        overflow: hidden;
        margin-top: 8px;
    }
    .progress-fill {
        height: 100%;
        background: var(--primary);
        transition: width 0.3s;
    }
    .btn-sm {
        padding: 6px 12px;
        font-size: 12px;
        border-radius: 6px;
        text-decoration: none;
        font-weight: 600;
        display: inline-block;
    }
</style>

<div class="application-detail-page">
    <div class="container mx-auto px-4">
        <div class="page-header">
            <h1>Status prijave</h1>
            <p style="color: rgba(255,255,255,0.9); margin: 0;">{{ $application->business_plan_name }}</p>
        </div>

        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if(session('info'))
            <div class="alert alert-info">
                {{ session('info') }}
            </div>
        @endif

        <!-- Status prijave i Biznis plan -->
        <div class="info-card">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #e5e7eb; padding-bottom: 12px; margin-bottom: 20px;">
                <h2 style="margin: 0; border: none; padding: 0;">Status prijave</h2>
                @if($application->status === 'draft')
                    <div style="text-align: right;">
                        @if($isReadyToSubmit)
                            <form method="POST" action="{{ route('applications.final-submit', $application) }}" onsubmit="return confirm('Da li ste sigurni da želite da podnesete prijavu? Nakon podnošenja više nećete moći da mijenjate podatke.');">
                                @csrf
                                <button type="submit" class="btn btn-primary" style="background: #10b981;">
                                    🚀 Podnesi konačnu prijavu
                                </button>
                            </form>
                        @else
                            <button class="btn btn-secondary" disabled title="Niste popunili biznis plan ili priložili sve dokumente">
                                Podnesi konačnu prijavu
                            </button>
                            <p style="font-size: 11px; color: #ef4444; margin-top: 4px;">Fale dokumenti ili biznis plan</p>
                        @endif
                    </div>
                @endif
            </div>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Trenutni Status</span>
                    <span class="info-value">
                        @php
                            $statusLabels = [
                                'draft' => 'Nacrt (U pripremi)',
                                'submitted' => 'Podnesena (U obradi)',
                                'evaluated' => 'Ocjenjena',
                                'approved' => 'Odobrena',
                                'rejected' => 'Odbijena',
                            ];
                            $statusClass = 'status-' . $application->status;
                        @endphp
                        <span class="status-badge {{ $statusClass }}">
                            {{ $statusLabels[$application->status] ?? $application->status }}
                        </span>
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label">Biznis Plan (Obrazac 2)</span>
                    <span class="info-value">
                        @if($application->businessPlan)
                            <span style="color: #10b981; font-weight: 600;">✅ Popunjen</span>
                            <a href="{{ route('applications.business-plan.create', $application) }}" style="color: #3b82f6; font-size: 12px; margin-left: 8px;">Uredi</a>
                        @else
                            <span style="color: #ef4444; font-weight: 600;">❌ Nije popunjen</span>
                            <a href="{{ route('applications.business-plan.create', $application) }}" style="color: #3b82f6; font-size: 12px; margin-left: 8px;">Popuni odmah</a>
                        @endif
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label">Datum podnošenja</span>
                    <span class="info-value">
                        {{ $application->submitted_at ? $application->submitted_at->format('d.m.Y H:i') : 'Nije još podnesena' }}
                    </span>
                </div>
            </div>
        </div>

        @if($errors->any())
            <div class="alert" style="background: #fee2e2; border-color: #ef4444; color: #991b1b; margin-bottom: 24px;">
                <strong>Greška pri podnošenju:</strong>
                <ul style="margin-top: 8px; padding-left: 20px;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Osnovni podaci -->
        <div class="info-card">
            <h2>Osnovni podaci</h2>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Tip podnosioca</span>
                    <span class="info-value">
                        {{ $application->applicant_type === 'preduzetnica' ? 'Preduzetnica' : 'DOO' }}
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label">Faza biznisa</span>
                    <span class="info-value">
                        {{ $application->business_stage === 'započinjanje' ? 'Započinjanje' : 'Razvoj' }}
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label">Oblast biznisa</span>
                    <span class="info-value">{{ $application->business_area }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Traženi iznos</span>
                    <span class="info-value">{{ number_format($application->requested_amount, 2, ',', '.') }} €</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Ukupan budžet</span>
                    <span class="info-value">{{ number_format($application->total_budget_needed, 2, ',', '.') }} €</span>
                </div>
                @if($application->approved_amount)
                <div class="info-item">
                    <span class="info-label">Odobreni iznos</span>
                    <span class="info-value">{{ number_format($application->approved_amount, 2, ',', '.') }} €</span>
                </div>
                @endif
            </div>
        </div>

        <!-- Dokumenti -->
        <div class="info-card">
            <h2>Priložena dokumentacija</h2>
            
            @php
                $requiredDocs = $application->getRequiredDocuments();
                $documentLabels = [
                    'licna_karta' => 'Ovjerena kopija lične karte',
                    'crps_resenje' => 'Rješenje o upisu u CRPS',
                    'pib_resenje' => 'Rješenje o registraciji PJ Uprave prihoda i carina (PIB)',
                    'pdv_resenje' => 'Rješenje o registraciji za PDV',
                    'statut' => 'Statut društva',
                    'karton_potpisa' => 'Karton potpisa',
                    'potvrda_neosudjivanost' => 'Potvrda o neosuđivanosti',
                    'uvjerenje_opstina_porezi' => 'Uvjerenje Opštine o urednom izmirivanju poreza',
                    'uvjerenje_opstina_nepokretnost' => 'Uvjerenje Opštine o nepostojanju nepokretnosti',
                    'potvrda_upc_porezi' => 'Potvrda Uprave za javne prihode o urednom izmirivanju poreza',
                    'ioppd_obrazac' => 'Obrazac IOPPD',
                    'godisnji_racuni' => 'Godišnji računi',
                    'biznis_plan_usb' => 'Štampana i elektronska verzija biznis plana na USB-u',
                    'izvjestaj_realizacija' => 'Izvještaj o realizaciji',
                    'finansijski_izvjestaj' => 'Finansijski izvještaj',
                    'ostalo' => 'Ostalo',
                ];
                $uploadedDocs = $application->documents->pluck('document_type')->toArray();
            @endphp

            <!-- Progress bar -->
            <div style="margin-bottom: 20px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                    <span style="font-size: 14px; color: #374151; font-weight: 600;">
                        Dokumenti: {{ count($uploadedDocs) }} / {{ count($requiredDocs) }}
                    </span>
                    <span style="font-size: 14px; color: #6b7280;">
                        {{ round((count($uploadedDocs) / max(count($requiredDocs), 1)) * 100) }}%
                    </span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: {{ (count($uploadedDocs) / max(count($requiredDocs), 1)) * 100 }}%"></div>
                </div>
            </div>

            <!-- Lista obaveznih dokumenata -->
            <ul class="documents-list">
                @foreach($requiredDocs as $docType)
                    @php
                        $uploaded = in_array($docType, $uploadedDocs);
                        $doc = $application->documents->where('document_type', $docType)->first();
                    @endphp
                    <li class="document-item {{ $uploaded ? 'uploaded' : 'required' }}">
                        <div class="document-info">
                            <div class="document-name">
                                {{ $documentLabels[$docType] ?? $docType }}
                                @if(!$uploaded)
                                    <span style="color: #ef4444; font-size: 12px; margin-left: 8px;">(Obavezno)</span>
                                @endif
                            </div>
                            @if($uploaded && $doc)
                                <div class="document-type">
                                    Upload-ovano: {{ $doc->created_at->format('d.m.Y H:i') }}
                                </div>
                            @endif
                        </div>
                        <div class="document-actions">
                            @if($uploaded && $doc)
                                <a href="{{ route('applications.document.download', ['application' => $application, 'document' => $doc]) }}" 
                                   class="btn btn-secondary">
                                    Preuzmi
                                </a>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>

            <!-- Forma za upload dokumenata -->
            @if($application->status === 'draft' || $application->status === 'submitted')
            <div class="upload-section">
                <h3 style="font-size: 16px; font-weight: 600; color: #111827; margin-bottom: 16px;">
                    Dodaj dokument
                </h3>
                <form method="POST" action="{{ route('applications.upload', $application) }}" enctype="multipart/form-data">
                    @csrf
                    @if($errors->any())
                        <div class="alert" style="background: #fee2e2; border-color: #ef4444; color: #991b1b; margin-bottom: 16px;">
                            <strong>Greška:</strong>
                            <ul style="margin: 8px 0 0 0; padding-left: 20px;">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    @if(session('success'))
                        <div class="alert" style="background: #d1fae5; border-color: #10b981; color: #065f46; margin-bottom: 16px;">
                            {{ session('success') }}
                        </div>
                    @endif
                    <div class="form-group">
                        <label class="form-label">Tip dokumenta</label>
                        <select name="document_type" class="form-control @error('document_type') error @enderror" required>
                            <option value="">Izaberite tip dokumenta</option>
                            @foreach($requiredDocs as $docType)
                                @if(!in_array($docType, $uploadedDocs))
                                    <option value="{{ $docType }}" {{ old('document_type') === $docType ? 'selected' : '' }}>{{ $documentLabels[$docType] ?? $docType }}</option>
                                @endif
                            @endforeach
                        </select>
                        @error('document_type')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Fajl</label>
                        <div class="file-input-wrapper">
                            <input type="file" name="file" id="file-input-{{ $application->id }}" accept=".pdf,.jpg,.jpeg,.png" onchange="updateFileName(this, 'file-name-{{ $application->id }}')">
                            <label for="file-input-{{ $application->id }}" class="file-input-label-custom">Izaberi fajl</label>
                            <span id="file-name-{{ $application->id }}" class="file-name-display" style="display: none;"></span>
                        </div>
                        <div style="font-size: 12px; color: #6b7280; margin-top: 4px;">
                            Dozvoljeni formati: PDF, JPEG, PNG (max 20MB)
                        </div>
                        @error('file')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label" style="font-weight: 400;">Ili izaberite iz biblioteke dokumenata</label>
                        <select name="user_document_id" class="form-control @error('user_document_id') error @enderror">
                            <option value="">Izaberite dokument iz biblioteke</option>
                            @foreach(auth()->user()->documents()->where('status', 'active')->get() as $userDoc)
                                <option value="{{ $userDoc->id }}" {{ old('user_document_id') == $userDoc->id ? 'selected' : '' }}>{{ $userDoc->name }} ({{ $userDoc->category }})</option>
                            @endforeach
                        </select>
                        @error('user_document_id')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                    </div>
                    <script>
                        function updateFileName(input, displayId) {
                            const display = document.getElementById(displayId);
                            if (input.files && input.files[0]) {
                                display.textContent = input.files[0].name;
                                display.style.display = 'block';
                            } else {
                                display.style.display = 'none';
                            }
                        }
                    </script>
                    <button type="submit" class="btn btn-primary">Priloži dokument</button>
                </form>
            </div>
            @endif
        </div>

        <!-- Ugovor -->
        @if($application->status === 'approved')
        <div class="info-card">
            <h2>Ugovor</h2>
            @if($application->contract)
                <p><strong>Status:</strong> 
                    @if($application->contract->status === 'draft') Nacrt
                    @elseif($application->contract->status === 'signed') Potpisan
                    @elseif($application->contract->status === 'approved') Potvrđen
                    @endif
                </p>
                <a href="{{ route('contracts.show', $application->contract) }}" class="btn btn-primary" style="margin-top: 12px;">
                    Pregled ugovora
                </a>
            @else
                <p style="color: #6b7280;">Ugovor još nije kreiran.</p>
                @if(auth()->user()->role && (auth()->user()->role->name === 'admin' || auth()->user()->role->name === 'superadmin'))
                    <a href="{{ route('contracts.generate', $application) }}" class="btn btn-primary" style="margin-top: 12px;">
                        Generiši ugovor
                    </a>
                @endif
            @endif
        </div>
        @endif

        <!-- Izvještaji -->
        @if($application->status === 'approved' && $application->contract && $application->contract->status === 'approved')
        <div class="info-card">
            <h2>Izvještaji o realizaciji</h2>
            @php
                $realizationReport = $application->reports()->where('type', 'realization')->first();
                $financialReport = $application->reports()->where('type', 'financial')->first();
            @endphp
            
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; margin-top: 16px;">
                <div style="padding: 16px; background: #f9fafb; border-radius: 8px;">
                    <h3 style="font-size: 16px; margin-bottom: 8px;">Obrazac 4 - Izvještaj o realizaciji</h3>
                    @if($realizationReport)
                        <p style="font-size: 12px; color: #6b7280; margin-bottom: 8px;">
                            Status: {{ $realizationReport->status === 'submitted' ? 'Podnesen' : ($realizationReport->status === 'approved' ? 'Odobren' : 'Odbijen') }}
                        </p>
                        <a href="{{ route('reports.download', $realizationReport) }}" class="btn-sm" style="background: #3b82f6; color: #fff;">Preuzmi</a>
                    @else
                        <a href="{{ route('reports.create', $application) }}" class="btn-sm" style="background: var(--primary); color: #fff;">Kreiraj izvještaj</a>
                    @endif
                </div>
                
                <div style="padding: 16px; background: #f9fafb; border-radius: 8px;">
                    <h3 style="font-size: 16px; margin-bottom: 8px;">Obrazac 4a - Finansijski izvještaj</h3>
                    @if($financialReport)
                        <p style="font-size: 12px; color: #6b7280; margin-bottom: 8px;">
                            Status: {{ $financialReport->status === 'submitted' ? 'Podnesen' : ($financialReport->status === 'approved' ? 'Odobren' : 'Odbijen') }}
                        </p>
                        <a href="{{ route('reports.download', $financialReport) }}" class="btn-sm" style="background: #3b82f6; color: #fff;">Preuzmi</a>
                    @else
                        <a href="{{ route('reports.create-financial', $application) }}" class="btn-sm" style="background: var(--primary); color: #fff;">Kreiraj izvještaj</a>
                    @endif
                </div>
            </div>
        </div>
        @endif

        <!-- Akcije -->
        <div class="info-card" style="text-align: center;">
            <a href="{{ route('competitions.show', $application->competition) }}" class="btn btn-secondary">
                Nazad na konkurs
            </a>
            @if($application->status === 'draft')
                @if($application->businessPlan)
                    <a href="{{ route('applications.business-plan.create', $application) }}" class="btn btn-primary" style="margin-left: 8px;">
                        Uredi biznis plan
                    </a>
                @else
                    <a href="{{ route('applications.business-plan.create', $application) }}" class="btn btn-primary" style="margin-left: 8px;">
                        Popuni biznis plan
                    </a>
                @endif
                
                <form action="{{ route('applications.destroy', $application) }}" method="POST" style="display: inline;" onsubmit="return confirm('Da li ste sigurni da želite da obrišete ovu prijavu? Svi podaci o biznis planu i priloženi dokumenti će biti uklonjeni.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger" style="margin-left: 8px;">
                        Obriši prijavu
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>
@endsection

