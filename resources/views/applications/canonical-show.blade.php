@extends('layouts.app')

@section('content')
@php
    $formCode = $form ? $form->formCode() : 'P1a/P1b';
    $statusLabels = [
        'draft' => 'Nacrt',
        'submitted' => 'U obradi',
        'evaluated' => 'Ocijenjena',
        'approved' => 'Odobrena',
        'rejected' => 'Odbijena',
    ];
    $statusClass = 'status-'.$application->status;
    $uploadRows = collect($checklist)->where('source', \App\Support\CanonicalDocumentCatalog::SOURCE_UPLOAD);
    $requiredUploads = $uploadRows->where('required_now', true);
    $uploadedRequired = $requiredUploads->whereNotNull('document')->count();
    $missingRequired = $requiredUploads->whereNull('document')->count();
    $tipPrijave = $form
        ? ($form->legal_form === \App\Support\CanonicalLegalForm::PREDUZETNIK ? 'Preduzetnik' : 'Privredno društvo')
        : '—';
    $p1Complete = $form && app(\App\Services\Competitions\CanonicalApplicantFormService::class)->finalBlockReason($application) === null;
    $p2Complete = $plan && app(\App\Services\Competitions\CanonicalInvestmentPlanService::class)->finalBlockReason($application) === null;
@endphp

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
        box-shadow: 0 1px 3px rgba(0,0,0,.1);
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
    .summary-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 24px;
        margin-bottom: 24px;
        align-items: stretch;
    }
    @media (min-width: 992px) {
        .summary-grid { grid-template-columns: repeat(3, 1fr); }
    }
    .summary-grid .info-card {
        margin-bottom: 0;
        height: 100%;
        box-sizing: border-box;
    }
    .info-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 16px;
    }
    .info-item { display: flex; flex-direction: column; }
    .info-label {
        font-size: 12px;
        font-weight: 600;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: .5px;
        margin-bottom: 4px;
    }
    .info-value {
        font-size: 14px;
        color: #111827;
        font-weight: 500;
    }
    .status-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 9999px;
        font-size: 12px;
        font-weight: 600;
        text-decoration: none;
    }
    .status-draft { background:#fef3c7; color:#92400e; }
    .status-submitted { background:#dbeafe; color:#1e40af; }
    .status-evaluated, .status-approved { background:#d1fae5; color:#065f46; }
    .status-rejected { background:#fee2e2; color:#991b1b; }
    .btn {
        padding: 8px 16px;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 600;
        text-decoration: none;
        cursor: pointer;
        border: none;
        transition: all .2s;
        display: inline-block;
    }
    .btn-primary { background: var(--primary); color:#fff; }
    .btn-primary:hover { background: var(--primary-dark); }
    .btn-secondary { background:#6b7280; color:#fff; }
    .btn-secondary:hover { background:#4b5563; }
    .alert {
        border-radius: 12px;
        padding: 16px 20px;
        margin-bottom: 20px;
        border: 1px solid;
    }
    .alert-success { background:#d1fae5; border-color:#10b981; color:#065f46; }
    .alert-info { background:#dbeafe; border-color:#3b82f6; color:#1e40af; }
    .alert-warning { background:#fef3c7; border-color:#f59e0b; color:#92400e; }
    .alert-error { background:#fee2e2; border-color:#ef4444; color:#991b1b; }
    .progress-bar {
        width:100%;
        height:8px;
        background:#e5e7eb;
        border-radius:9999px;
        overflow:hidden;
        margin-top:8px;
    }
    .progress-fill {
        height:100%;
        background:var(--primary);
        transition:width .3s;
    }
    .documents-list { list-style:none; padding:0; margin:0; }
    .document-item {
        padding:16px;
        border:1px solid #e5e7eb;
        border-radius:8px;
        margin-bottom:12px;
        display:flex;
        justify-content:space-between;
        align-items:center;
        gap:16px;
    }
    .document-item.required { border-left:4px solid #ef4444; }
    .document-item.uploaded { border-left:4px solid #10b981; }
    .document-info { flex:1; min-width:0; }
    .document-name { font-weight:600; color:#111827; margin-bottom:4px; }
    .document-type { font-size:12px; color:#6b7280; }
    .document-actions { display:flex; gap:8px; flex-wrap:wrap; }
    @media (max-width: 700px) {
        .document-item { align-items:flex-start; flex-direction:column; }
    }
</style>

<div class="application-detail-page">
    <div class="container mx-auto px-4">
        <div class="page-header">
            <h1>Status prijave</h1>
            <p style="color:rgba(255,255,255,.9); margin:0;">
                Podrška mikro, malim i srednjim preduzećima — {{ $competition->title }}
            </p>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('info'))
            <div class="alert alert-info">{{ session('info') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-error">
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <div class="summary-grid">
            <div class="info-card">
                <h2>Osnovni podaci</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">Tip podnosioca</span>
                        <span class="info-value">{{ $tipPrijave }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Obrazac prijave</span>
                        <span class="info-value">Obrazac {{ $formCode }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Vrsta subvencije</span>
                        <span class="info-value">
                            {{ $form?->subsidy_type ? ($subsidyLabels[$form->subsidy_type] ?? $form->subsidy_type) : 'Nije izabrana' }}
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Broj konkursa</span>
                        <span class="info-value">{{ $competition->upNumber?->number ?? '—' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Redni broj prijave</span>
                        <span class="info-value">{{ $application->redni_broj ?? 'Biće dodijeljen nakon konačne predaje' }}</span>
                    </div>
                </div>
            </div>

            <div class="info-card">
                <h2>Status prijave</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">Trenutni status</span>
                        <span class="info-value">
                            <span class="status-badge {{ $statusClass }}">{{ $statusLabels[$application->status] ?? $application->status }}</span>
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Obrazac {{ $formCode }}</span>
                        <span class="info-value">
                            <a href="{{ route('applications.canonical-p1.edit', $application) }}"
                               class="status-badge {{ $p1Complete ? 'status-evaluated' : 'status-draft' }}">
                                Obrazac {{ $formCode }} - {{ $p1Complete ? 'popunjen' : 'Nacrt' }}
                            </a>
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Plan ulaganja</span>
                        <span class="info-value">
                            <a href="{{ route('applications.canonical-p2.edit', $application) }}"
                               class="status-badge {{ $p2Complete ? 'status-evaluated' : 'status-draft' }}">
                                Obrazac P2 - {{ $p2Complete ? 'popunjen' : 'Nacrt' }}
                            </a>
                        </span>
                    </div>
                    @if($application->finalClaim)
                        <div class="info-item">
                            <span class="info-label">Provjera potpunosti</span>
                            <span class="info-value">
                                <a href="{{ route('applications.canonical-review.show', $application) }}" class="status-badge status-submitted">
                                    Pregled statusa
                                </a>
                            </span>
                        </div>
                    @endif

                    @if($application->status === 'draft')
                        <div class="info-item" style="margin-top:4px;">
                            @if($canSubmit)
                                <form method="POST" action="{{ route('applications.final-submit', $application) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-primary" style="width:100%;">Podnesi prijavu</button>
                                </form>
                            @else
                                <button class="btn btn-secondary" disabled style="width:100%; background:#9ca3af;">Podnesi prijavu</button>
                                <span style="font-size:10px; color:#ef4444; margin-top:6px; text-align:center;">
                                    Dovršite obrazac, plan ulaganja i obaveznu dokumentaciju.
                                </span>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            <div class="info-card" id="application-documents">
                <h2>Dodaj dokument</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">Obavezni prilozi</span>
                        <span class="info-value">{{ $uploadedRequired }} od {{ $requiredUploads->count() }} priloženo</span>
                        @php
                            $docPercent = $requiredUploads->count() > 0
                                ? (int) round(($uploadedRequired / $requiredUploads->count()) * 100)
                                : 100;
                        @endphp
                        <div class="progress-bar"><div class="progress-fill" style="width:{{ $docPercent }}%;"></div></div>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Nedostaje</span>
                        <span class="info-value">{{ $missingRequired }} obaveznih priloga</span>
                    </div>
                    <div class="info-item">
                        <a href="{{ route('applications.canonical-documents.index', $application) }}" class="btn btn-primary" style="text-align:center;">
                            {{ $canManage ? 'Dodaj / uredi dokumente' : 'Pregled dokumentacije' }}
                        </a>
                    </div>
                </div>
            </div>
        </div>

        @if($application->status === 'draft' && $blockers !== [])
            <div class="alert alert-warning">
                <strong>Prije konačne predaje potrebno je:</strong>
                <ul style="margin:8px 0 0; padding-left:20px;">
                    @foreach($blockers as $blocker)
                        <li>{{ $blocker }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="info-card">
            <h2>Priložena dokumentacija</h2>
            <ul class="documents-list">
                @foreach($uploadRows as $row)
                    @php $uploaded = $row['document'] !== null; @endphp
                    <li class="document-item {{ $uploaded ? 'uploaded' : ($row['required_now'] ? 'required' : '') }}">
                        <div class="document-info">
                            <div class="document-name">{{ $row['label'] }}</div>
                            <div class="document-type">
                                @if($uploaded)
                                    Priloženo
                                    @if($row['issued_on']) · izdano {{ \Carbon\Carbon::parse($row['issued_on'])->format('d.m.Y.') }} @endif
                                @elseif($row['required_now'])
                                    Nedostaje — obavezno
                                @else
                                    Nije obavezno u vašem slučaju
                                @endif
                            </div>
                        </div>
                        <div class="document-actions">
                            @if($uploaded)
                                <a class="btn btn-primary" target="_blank" href="{{ route('applications.document.view', [$application, $row['document']]) }}">Pregled</a>
                            @elseif($canManage)
                                <a class="btn btn-primary" href="{{ route('applications.canonical-documents.index', $application) }}">Priloži</a>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>

        @if($application->status === 'draft' && $canManage)
            <div style="margin-bottom:24px;">
                <form method="POST" action="{{ route('applications.destroy', $application) }}" style="display:inline-block;" onsubmit="return confirm('Obrisati nacrt prijave?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-secondary">Obriši nacrt prijave</button>
                </form>
            </div>
        @endif

        @if($canStartAnother)
            <div class="alert alert-info">
                Na isti javni poziv možete podnijeti još jednu prijavu za drugu vrstu subvencije.
                <a href="{{ route('applications.canonical.legal-form', $competition) }}">Započni drugu prijavu</a>
            </div>
        @endif

        <div style="margin-bottom:24px;">
            <a href="{{ route('dashboard') }}" class="btn btn-secondary">Nazad na Moj panel</a>
            <a href="{{ route('competitions.show', $competition) }}" class="btn btn-primary" style="margin-left:8px;">Nazad na konkurs</a>
        </div>
    </div>
</div>
@endsection
