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
    $uploadRowMetadata = $uploadRows->whereNull('document')->values()->map(function ($row) {
        return [
            'key' => $row['key'],
            'variants' => $row['variants'],
            'needs_issue_date' => $row['needs_issue_date'],
        ];
    })->all();
    $reviewAt = fn ($at) => $at
        ? \Carbon\CarbonImmutable::parse($at)->timezone($canonicalReview['timezone'] ?? 'Europe/Belgrade')->format('d.m.Y. H:i')
        : '—';
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
    .upload-section {
        background:#f9fafb;
        padding:15px;
        border-radius:8px;
        margin-top:0;
    }
    .form-group { margin-bottom:12px; }
    .form-label {
        display:block;
        font-size:13px;
        font-weight:600;
        color:#374151;
        margin-bottom:8px;
    }
    .form-control {
        width:100%;
        padding:8px 12px;
        border:1px solid #d1d5db;
        border-radius:8px;
        font-size:13px;
        background:#fff;
        box-sizing:border-box;
    }
    .form-control:focus {
        outline:none;
        border-color:var(--primary);
        box-shadow:0 0 0 3px rgba(11,61,145,.1);
    }
    .file-input-wrapper {
        position:relative;
        display:block;
        width:100%;
        min-height:40px;
    }
    .file-input-wrapper input[type="file"] {
        position:absolute;
        opacity:0;
        width:100%;
        height:40px;
        cursor:pointer;
        z-index:2;
        top:0;
        left:0;
    }
    .file-input-label-custom {
        display:inline-block;
        background:var(--primary);
        color:#fff;
        padding:8px 16px;
        border-radius:6px;
        cursor:pointer;
        font-size:14px;
        font-weight:600;
    }
    .upload-info-box {
        background:#eff6ff;
        border-left:4px solid #3b82f6;
        padding:14px 16px;
        border-radius:6px;
        color:#1e40af;
        font-size:12px;
        line-height:1.6;
        margin:12px 0;
    }
    .canonical-evidence-box {
        display:none;
        margin:10px 0;
        padding:10px 12px;
        border:1px solid #dbeafe;
        border-radius:8px;
        background:#f8fafc;
        font-size:12px;
    }
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

                @if($canManage)
                    <div class="upload-section">
                        <form method="POST" action="{{ route('applications.upload', $application) }}" enctype="multipart/form-data" id="canonical-status-upload-form">
                            @csrf

                            <div class="form-group">
                                <label class="form-label" for="canonical_document_type">Tip dokumenta</label>
                                <select name="document_type" id="canonical_document_type" class="form-control" required>
                                    <option value="">Izaberite tip</option>
                                    @foreach($uploadRows->whereNull('document') as $row)
                                        <option value="{{ $row['key'] }}">{{ $row['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div id="canonical_evidence_box" class="canonical-evidence-box">
                                <div id="canonical_variant_wrap" style="display:none; margin-bottom:10px;">
                                    <div class="form-label">Vrsta priloga</div>
                                    <div id="canonical_variant_options"></div>
                                </div>
                                <div id="canonical_issued_on_wrap" style="display:none;">
                                    <label class="form-label" for="canonical_issued_on">Datum izdavanja</label>
                                    <input id="canonical_issued_on" type="date" name="issued_on" class="form-control">
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Fajl</label>
                                <div class="file-input-wrapper">
                                    <input id="canonical_status_files" type="file" name="files[]" accept="application/pdf" multiple>
                                    <label for="canonical_status_files" class="file-input-label-custom">Izaberi fajlove (možete izabrati više)</label>
                                </div>
                                <div id="canonical_file_names" style="font-size:12px; color:#6b7280; margin-top:6px;"></div>
                            </div>

                            <div class="upload-info-box">
                                <strong style="display:block; font-size:14px; margin-bottom:6px;">ℹ️ Važno:</strong>
                                Ako izaberete više fajlova, oni će biti spojeni u <strong>jedan PDF dokument</strong> redosledom kojim su izabrani.
                                MMSP prilozi se dostavljaju u PDF formatu.
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="canonical_library_document" style="font-weight:400;">Ili iz biblioteke</label>
                                <select name="user_document_id" id="canonical_library_document" class="form-control">
                                    <option value="">Izaberi...</option>
                                    @foreach($libraryDocuments as $libraryDocument)
                                        <option value="{{ $libraryDocument->id }}">{{ $libraryDocument->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <label style="display:flex; align-items:center; gap:8px; font-size:12px; color:#4b5563; margin:14px 0;">
                                <input type="checkbox" name="save_to_library" value="1">
                                <span>Sačuvaj dokument i u moju biblioteku</span>
                            </label>

                            <button type="submit" class="btn btn-primary" style="width:100%; text-align:center;">Priloži dokument</button>
                        </form>
                    </div>
                @else
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">Obavezni prilozi</span>
                            <span class="info-value">{{ $uploadedRequired }} od {{ $requiredUploads->count() }} priloženo</span>
                        </div>
                        <div class="info-item">
                            <a href="{{ route('applications.show', $application) }}#application-documents" class="btn btn-primary" style="text-align:center;">Pregled dokumentacije</a>
                        </div>
                    </div>
                @endif
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
                                <a class="btn btn-primary" href="{{ route('applications.show', $application) }}#application-documents">Priloži</a>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>

        @if($canonicalReview !== null)
            <div class="info-card" id="application-review">
                <h2>Obavještenje i Prigovor</h2>

                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">Provjera potpunosti</span>
                        <span class="info-value"><strong>{{ $canonicalReview['label'] }}</strong></span>
                    </div>

                    @if($canonicalReview['incompleteReason'] !== null)
                        <div class="info-item">
                            <span class="info-label">Nalaz Komisije</span>
                            <span class="info-value">{{ $canonicalReview['incompleteReason'] }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Obavještenje poslato</span>
                            <span class="info-value">{{ $reviewAt($canonicalReview['noticeSentAt']) }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Rok za Prigovor</span>
                            <span class="info-value">{{ $reviewAt($canonicalReview['complaintDeadline']) }}</span>
                        </div>
                        <p style="font-size:13px; color:#4b5563; line-height:1.5; margin:0;">
                            Prigovor ne otvara prijavu za izmjenu niti omogućava naknadno dostavljanje dokumenata.
                            Komisija odlučuje na osnovu podnesenog dosijea.
                        </p>
                    @endif

                    @if($canonicalReview['complaintSubmittedAt'])
                        <div class="info-item">
                            <span class="info-label">Vaš Prigovor</span>
                            <span class="info-value">Podnesen {{ $reviewAt($canonicalReview['complaintSubmittedAt']) }}</span>
                        </div>
                        @if($canonicalReview['complaintDecision'] === null)
                            <div class="alert alert-info" style="margin:0;">Prigovor čeka odluku Komisije.</div>
                        @else
                            <div class="alert {{ $canonicalReview['complaintDecision'] === \App\Models\ApplicationComplaint::ACCEPTED ? 'alert-success' : 'alert-warning' }}" style="margin:0;">
                                Odluka Komisije: prigovor je
                                <strong>{{ $canonicalReview['complaintDecision'] === \App\Models\ApplicationComplaint::ACCEPTED ? 'prihvaćen' : 'odbijen' }}</strong>
                                ({{ $reviewAt($canonicalReview['complaintDecidedAt']) }}).
                                @if(filled($canonicalReview['complaintDecisionNotes']))
                                    <div style="margin-top:8px;"><strong>Obrazloženje:</strong> {{ $canonicalReview['complaintDecisionNotes'] }}</div>
                                @endif
                            </div>
                        @endif
                    @elseif($canonicalReview['complaintOpen'])
                        <form method="POST" action="{{ route('applications.complaint', $application) }}">
                            @csrf
                            <div class="form-group">
                                <label class="form-label" for="canonical_complaint_body">Obrazloženje Prigovora</label>
                                <textarea id="canonical_complaint_body" name="body" rows="5" maxlength="5000" required class="form-control" style="min-height:140px;">{{ old('body') }}</textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Podnesi Prigovor</button>
                        </form>
                    @elseif($canonicalReview['incompleteReason'] !== null)
                        <div class="info-item">
                            <span class="info-value">Prigovor nije podnesen ili je rok istekao.</span>
                        </div>
                    @endif
                </div>
            </div>
        @endif

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

<script>
(function () {
    const rows = @json($uploadRowMetadata);

    const typeSelect = document.getElementById('canonical_document_type');
    const evidenceBox = document.getElementById('canonical_evidence_box');
    const variantWrap = document.getElementById('canonical_variant_wrap');
    const variantOptions = document.getElementById('canonical_variant_options');
    const issuedWrap = document.getElementById('canonical_issued_on_wrap');
    const issuedInput = document.getElementById('canonical_issued_on');
    const filesInput = document.getElementById('canonical_status_files');
    const fileNames = document.getElementById('canonical_file_names');

    function updateEvidence() {
        if (!typeSelect) return;
        const row = rows.find(r => r.key === typeSelect.value);
        if (!row) {
            evidenceBox.style.display = 'none';
            variantWrap.style.display = 'none';
            issuedWrap.style.display = 'none';
            variantOptions.innerHTML = '';
            if (issuedInput) issuedInput.required = false;
            return;
        }

        const variantEntries = Object.entries(row.variants || {});
        const hasVariants = variantEntries.length > 0;
        const needsDate = !!row.needs_issue_date;
        evidenceBox.style.display = (hasVariants || needsDate) ? 'block' : 'none';
        variantWrap.style.display = hasVariants ? 'block' : 'none';
        issuedWrap.style.display = needsDate ? 'block' : 'none';

        variantOptions.innerHTML = '';
        variantEntries.forEach(([value, label]) => {
            const el = document.createElement('label');
            el.style.cssText = 'display:flex;gap:8px;align-items:flex-start;margin-bottom:6px;';
            const input = document.createElement('input');
            input.type = 'radio';
            input.name = 'document_variant';
            input.value = value;
            input.required = true;
            const span = document.createElement('span');
            span.textContent = label;
            el.appendChild(input);
            el.appendChild(span);
            variantOptions.appendChild(el);
        });

        if (issuedInput) issuedInput.required = needsDate;
    }

    if (typeSelect) {
        typeSelect.addEventListener('change', updateEvidence);
        updateEvidence();
    }

    if (filesInput && fileNames) {
        filesInput.addEventListener('change', function () {
            const files = Array.from(filesInput.files || []);
            fileNames.textContent = files.length
                ? 'Izabrano: ' + files.map(f => f.name).join(', ')
                : '';
        });
    }
})();
</script>

</div>
@endsection
