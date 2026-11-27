@extends('layouts.app')

@section('content')
@php
    $formCode = $form ? $form->formCode() : 'P1a/P1b';
    $statusLabels = [
        'draft' => 'Nacrt',
        'submitted' => 'Podnesena',
        'evaluated' => 'Ocijenjena',
        'approved' => 'Odobrena',
        'rejected' => 'Odbijena',
    ];
    $statusClass = 'status-'.$application->status;
    $uploadRows = collect($checklist)->where('source', \App\Support\CanonicalDocumentCatalog::SOURCE_UPLOAD);
    $requiredUploads = $uploadRows->where('required_now', true);
    $uploadedRequired = $requiredUploads->whereNotNull('document')->count();
    $missingRequiredRows = $requiredUploads->whereNull('document')->values();
    $missingRequired = $missingRequiredRows->count();
    $documentBlockPrefix = 'Prateća dokumentacija (čl.14) nije kompletna: ';
    $noticeBlockers = [];
    foreach ($blockers as $blocker) {
        if (! str_starts_with($blocker, $documentBlockPrefix)) {
            $noticeBlockers[] = $blocker;
            continue;
        }
        $remaining = [];
        foreach (preg_split('/(?<=\.)\s+/u', substr($blocker, strlen($documentBlockPrefix))) ?: [] as $sentence) {
            if ($sentence !== '' && ! str_starts_with($sentence, 'Nedostaje prilog:')) {
                $remaining[] = $sentence;
            }
        }
        if ($remaining !== []) {
            $noticeBlockers[] = implode(' ', $remaining);
        }
    }
    $uploadedUploads = $uploadRows->whereNotNull('document')->count();
    $totalUploads = $uploadRows->count();
    $uploadProgress = $totalUploads > 0 ? (int) round(($uploadedUploads / $totalUploads) * 100) : 0;
    $tipPrijave = $form
        ? ($form->legal_form === \App\Support\CanonicalLegalForm::PREDUZETNIK ? 'Preduzetnik' : 'Privredno društvo')
        : '—';
    $isFinallySubmitted = $application->finalClaim !== null || $application->submitted_at !== null || $application->status !== 'draft';
    $p1Complete = $form && (
        $isFinallySubmitted
        || app(\App\Services\Competitions\CanonicalApplicantFormService::class)->finalBlockReason($application) === null
    );
    $p2Complete = $plan && (
        $isFinallySubmitted
        || app(\App\Services\Competitions\CanonicalInvestmentPlanService::class)->finalBlockReason($application) === null
    );
    $p1StatusLabel = $p1Complete ? 'Obrazac '.$formCode.' popunjen' : 'Obrazac '.$formCode.' - Nacrt';
    $p2StatusLabel = $p2Complete ? 'Popunjen' : ($plan ? 'Nacrt' : 'Nije kreiran');
    $p1StatusClass = $p1Complete ? 'status-evaluated' : 'status-draft';
    $p2StatusClass = $p2Complete ? 'status-evaluated' : 'status-draft';
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
        .summary-grid.summary-grid-submitted { grid-template-columns: repeat(2, 1fr); }
        .summary-grid.summary-grid-submitted.summary-grid-with-notice { grid-template-columns: repeat(3, 1fr); }
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
    .btn-danger { background:#ef4444; color:#fff; }
    .btn-danger:hover { background:#dc2626; }
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
    .mmsp-prigovor-card {
        background: #fff;
        border-radius: 16px;
        padding: 32px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        margin-bottom: 24px;
        box-sizing: border-box;
    }
    .mmsp-prigovor-card h2 {
        font-size: 20px;
        font-weight: 700;
        color: #0B3D91;
        margin: 0 0 20px;
        padding-bottom: 12px;
        border-bottom: 2px solid #e5e7eb;
    }
    .mmsp-prigovor-card p,
    .mmsp-prigovor-card li,
    .mmsp-prigovor-card label {
        font-size: 14px;
        color: #111827;
        line-height: 1.5;
    }
    .mmsp-prigovor-card textarea.mmsp-prigovor-textarea {
        width: 100%;
        min-height: 140px;
        padding: 10px 12px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        font-size: 14px;
        box-sizing: border-box;
        margin-top: 8px;
    }
    .mmsp-prigovor-submit {
        margin-top: 16px;
        background: #0B3D91;
        color: #fff;
        padding: 10px 18px;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        font-size: 14px;
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
        @if(session('document_warning'))
            <div class="alert" style="background: #fef3c7; border-left: 4px solid #f59e0b; color: #92400e;">
                {{ session('document_warning') }}
            </div>
        @endif

        @if(session('mmsp_confirm_missing_documents') && $application->status === 'draft')
            <div id="mmsp-missing-documents-warning" class="alert" style="background: #fef3c7; border-left: 4px solid #f59e0b; color: #92400e;">
                <p style="margin: 0 0 8px 0; font-weight: 600;">
                    Upozorenje prije podnošenja: nisu priloženi svi obavezni prilozi.
                </p>
                <p style="margin: 0 0 8px 0;">
                    @foreach(session('mmsp_missing_document_labels', []) as $label)
                        Nedostaje prilog: {{ $label }}.@if(! $loop->last)<br>@endif
                    @endforeach
                </p>
                <p style="margin: 0 0 12px 0;">
                    Prijava ostaje u pripremi. Možete se vratiti na uređivanje i dopuniti priloge, ili svjesno podnijeti prijavu bez dopune. Komisija utvrđuje administrativnu potpunost prijave u skladu sa članom 18 Odluke.
                </p>
                <p style="margin: 0 0 12px 0;">
                    <a href="#application-documents">Vrati se na uređivanje</a>
                </p>
                @if($competition?->is_open)
                    <form method="POST" action="{{ route('applications.final-submit', $application) }}">
                        @csrf
                        <input type="hidden" name="confirm_missing_documents" value="1">
                        <button type="submit" class="btn btn-primary" style="background: #10b981;">
                            Podnesi prijavu uprkos nedostajućim prilozima
                        </button>
                    </form>
                @endif
            </div>
        @endif

        @php
            $mmspReview = $canonicalReview;
            $mmspNoticeStatuses = [
                \App\Services\Competitions\ComplaintService::STATUS_INCOMPLETE,
                \App\Services\Competitions\ComplaintService::STATUS_COMPLAINT_AVAILABLE,
                \App\Services\Competitions\ComplaintService::STATUS_COMPLAINT_SUBMITTED,
                \App\Services\Competitions\ComplaintService::STATUS_COMPLAINT_ACCEPTED,
                \App\Services\Competitions\ComplaintService::STATUS_COMPLAINT_REJECTED,
            ];
            $mmspReviewNotice = $mmspReview !== null
                && $application->status !== 'draft'
                && in_array($mmspReview['status'], $mmspNoticeStatuses, true);
            $mmspReviewSummary = $mmspReview !== null
                && $application->status !== 'draft'
                && ! $mmspReviewNotice
                && ($mmspReview['status'] ?? null) !== \App\Services\Competitions\ComplaintService::STATUS_AWAITING_REVIEW;
            $mmspStatusGridClass = 'summary-grid';
            if ($application->status !== 'draft') {
                $mmspStatusGridClass .= ' summary-grid-submitted';
                if ($mmspReviewNotice) {
                    $mmspStatusGridClass .= ' summary-grid-with-notice';
                }
            }
        @endphp

        <div class="{{ $mmspStatusGridClass }}">
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
                <div style="border-bottom: 2px solid #e5e7eb; padding-bottom: 12px; margin-bottom: 20px;">
                    <h2 style="margin: 0; border: none; padding: 0;">Status prijave</h2>
                </div>
                <div class="info-grid" style="grid-template-columns: 1fr;">
                    <div class="info-item" style="margin-bottom: 16px;">
                        <span class="info-label">Trenutni Status</span>
                        <span class="info-value">
                            <span class="status-badge {{ $statusClass }}" style="font-size: 12px; padding: 4px 12px;">{{ $statusLabels[$application->status] ?? $application->status }}</span>
                        </span>
                    </div>
                    <div class="info-item" style="margin-bottom: 16px;">
                        <span class="info-label">Obrazac {{ $formCode }}</span>
                        <span class="info-value">
                            <a href="{{ route('applications.canonical-p1.edit', $application) }}"
                               class="status-badge {{ $p1StatusClass }}" style="font-size: 12px; padding: 4px 12px; text-decoration: none; cursor: pointer;">
                                {{ $p1StatusLabel }}
                            </a>
                        </span>
                    </div>
                    <div class="info-item" style="margin-bottom: 16px;">
                        <span class="info-label">Plan ulaganja</span>
                        <span class="info-value">
                            <a href="{{ route('applications.canonical-p2.edit', $application) }}"
                               class="status-badge {{ $p2StatusClass }}" style="font-size: 12px; padding: 4px 12px; text-decoration: none; cursor: pointer;">
                                {{ $p2StatusLabel }}
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

                    <div class="info-item">
                        <span class="info-label">Datum podnošenja</span>
                        <span class="info-value" style="font-size: 13px;">{{ $application->submitted_at ? $application->submitted_at->format('d.m.Y H:i') : 'Nije podnesena' }}</span>
                    </div>

                    @if($application->status === 'draft' && $canManage)
                        <div class="info-item" style="margin-top: 12px; padding-top: 10px; border-top: 1px solid #e5e7eb;">
                            <span class="info-label">Akcije nad prijavom</span>
                            <div class="info-value" style="display: block; margin-top: 4px;">
                                <p style="font-size: 13px; color: #4b5563; margin-bottom: 8px;">
                                    Prijavu možete obrisati najkasnije do isteka roka za prijavu na konkurs.
                                </p>
                                <form method="POST" action="{{ route('applications.destroy', $application) }}" onsubmit="return confirm('Da li ste sigurni da želite obrisati ovu prijavu? Ova akcija se ne može poništiti.');" style="display: inline-block; margin-right: 12px;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-danger" style="background: #dc2626; color: #fff; padding: 8px 16px; border-radius: 8px; border: none; cursor: pointer; font-size: 13px; font-weight: 600;">
                                        Obriši prijavu
                                    </button>
                                </form>
                                @if($competition?->deadline)
                                    <span style="display: inline-block; font-size: 12px; color: #6b7280; margin-top: 4px;">
                                        Rok za prijave: {{ $competition->deadline->format('d.m.Y. H:i') }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endif

                    @if($application->status === 'draft')
                        <div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #e5e7eb;">
                            @if($canSubmit && ! session('mmsp_confirm_missing_documents'))
                                <form method="POST" action="{{ route('applications.final-submit', $application) }}" onsubmit="return confirm('Podnijeti prijavu?');">
                                    @csrf
                                    <button type="submit" class="btn btn-primary" style="background: #10b981; width: 100%; font-size: 13px;">
                                        🚀 Podnesi prijavu
                                    </button>
                                </form>
                            @elseif(! $canSubmit)
                                <button class="btn btn-secondary" disabled style="width: 100%; font-size: 13px; background: #9ca3af;">
                                    Podnesi prijavu
                                </button>
                                @if($noticeBlockers !== [])
                                    @foreach($noticeBlockers as $blocker)
                                        <span style="font-size:10px; color:#ef4444; margin-top:6px; text-align:center; display:block;">
                                            {{ $blocker }}
                                        </span>
                                    @endforeach
                                @elseif($missingRequiredRows->isEmpty())
                                    <span style="font-size:10px; color:#ef4444; margin-top:6px; text-align:center;">
                                        Dovršite obrazac, plan ulaganja i obaveznu dokumentaciju.
                                    </span>
                                @endif
                            @endif
                            @if($missingRequiredRows->isNotEmpty())
                                <div style="margin-top: 10px; padding: 10px; background: #fef3c7; border-left: 4px solid #f59e0b; border-radius: 4px;">
                                    <p style="font-size: 12px; color: #92400e; margin: 0 0 6px 0; font-weight: 600;">
                                        ⚠️ Napomena: Niste priložili sva neophodna dokumenta
                                    </p>
                                    <p style="font-size: 11px; color: #78350f; margin: 0;">
                                        @foreach($missingRequiredRows as $row)
                                            Nedostaje prilog: {{ $row['label'] }}.@if(! $loop->last)<br>@endif
                                        @endforeach
                                    </p>
                                    @if($canSubmit)
                                        <p style="font-size: 11px; color: #78350f; margin: 6px 0 0 0;">
                                            Možete podnijeti prijavu uz izričitu potvrdu. Komisija utvrđuje administrativnu potpunost prijave u skladu sa članom 18 Odluke.
                                        </p>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            @php
                $mmspComplaintState = match (is_array($mmspReview) ? ($mmspReview['status'] ?? null) : null) {
                    \App\Services\Competitions\ComplaintService::STATUS_COMPLAINT_SUBMITTED => 'Podnesen',
                    \App\Services\Competitions\ComplaintService::STATUS_COMPLAINT_ACCEPTED => 'Prihvaćen',
                    \App\Services\Competitions\ComplaintService::STATUS_COMPLAINT_REJECTED => 'Odbijen',
                    default => null,
                };
                $mmspDecisionDuePassed = false;
                $mmspDecidedLate = false;
                if ($mmspReview && $mmspReview['complaintDecisionDueAt']) {
                    $mmspDue = \Carbon\CarbonImmutable::parse($mmspReview['complaintDecisionDueAt'])->timezone($mmspReview['timezone']);
                    if ($mmspReview['complaintDecision'] === null) {
                        $mmspDecisionDuePassed = \Carbon\CarbonImmutable::now($mmspReview['timezone'])->greaterThan($mmspDue);
                    }
                    if ($mmspReview['complaintDecidedAt']) {
                        $mmspDecidedLate = \Carbon\CarbonImmutable::parse($mmspReview['complaintDecidedAt'])->timezone($mmspReview['timezone'])->greaterThan($mmspDue);
                    }
                }
            @endphp

            @if($mmspReviewNotice)
                <div class="mmsp-prigovor-card" id="application-review" data-status="{{ $mmspReview['status'] }}">
                    <h2>Obavještenje i Prigovor</h2>

                    @if(filled($mmspReview['incompleteReason']) || $mmspReview['noticeSentAt'])
                        @if(filled($mmspReview['incompleteReason']))
                            <p><strong>Obrazloženje Komisije o nepotpunosti prijave</strong></p>
                            <p style="white-space: pre-wrap;">{{ $mmspReview['incompleteReason'] }}</p>
                        @endif
                        @if($mmspReview['noticeSentAt'])
                            <p>
                                Imate pravo na jedan Prigovor putem Platforme u roku od 3 dana od slanja obavještenja.
                                Rok ističe {{ $reviewAt($mmspReview['complaintDeadline']) }}.
                            </p>
                            <p>Vanjski e-mail nije važeći kanal podnošenja Prigovora. Prigovor nije dopuna prijave i njime se ne mogu dodavati, zamjenjivati ni brisati dokumenti, niti mijenjati obrasci ili podaci prijave.</p>
                        @endif
                    @endif

                    @if($mmspReview['complaintSubmittedAt'])
                        @if($mmspComplaintState)
                            <p><strong>Stanje Vašeg Prigovora:</strong> {{ $mmspComplaintState }}</p>
                        @endif
                        <p>Podnesen: {{ $reviewAt($mmspReview['complaintSubmittedAt']) }}</p>
                        @if(filled($mmspReview['complaintBody']))
                            <p><strong>Vaše obrazloženje prigovora</strong></p>
                            <p style="white-space: pre-wrap;">{{ $mmspReview['complaintBody'] }}</p>
                        @endif
                        @if($mmspReview['complaintDecision'] === null)
                            <p>Prigovor je podnesen Komisiji.</p>
                            @if($mmspReview['complaintDecisionDueAt'])
                                <p>Rok Komisije za odluku: {{ $reviewAt($mmspReview['complaintDecisionDueAt']) }} (7 kalendarskih dana od prijema).</p>
                            @endif
                            @if($mmspDecisionDuePassed)
                                <p>Rok od 7 dana je prekoračen. Čeka se odluka Komisije. Platforma ne donosi automatsku odluku.</p>
                            @else
                                <p>Čeka se odluka Komisije.</p>
                            @endif
                        @else
                            <p>
                                Konačna odluka Komisije: {{ $mmspComplaintState }}
                                @if($mmspReview['complaintDecidedAt'])
                                    ({{ $reviewAt($mmspReview['complaintDecidedAt']) }})
                                @endif
                            </p>
                            @if($mmspDecidedLate)
                                <p>Odluka je evidentirana nakon isteka roka od 7 dana.</p>
                            @endif
                            @if(filled($mmspReview['complaintDecisionNotes']))
                                <p><strong>Obrazloženje odluke:</strong></p>
                                <p style="white-space: pre-wrap;">{{ $mmspReview['complaintDecisionNotes'] }}</p>
                            @endif
                            @if($mmspReview['complaintDecision'] === \App\Models\ApplicationComplaint::ACCEPTED)
                                <p>Prijava se smatra potpunom i nastavlja postupak u fazi bodovanja.</p>
                            @else
                                <p>Nalaz o nepotpunosti ostaje na snazi; prijava ne učestvuje u daljem bodovanju.</p>
                            @endif
                        @endif
                    @elseif($mmspReview['complaintOpen'])
                        <form method="POST" action="{{ route('applications.complaint', $application) }}">
                            @csrf
                            <label for="canonical_complaint_body"><strong>Vaše obrazloženje prigovora</strong></label>
                            <textarea id="canonical_complaint_body"
                                name="body"
                                class="mmsp-prigovor-textarea"
                                maxlength="5000"
                                required>{{ old('body') }}</textarea>
                            @error('body')
                                <p style="color: #ef4444;">{{ $message }}</p>
                            @enderror
                            <button type="submit" class="mmsp-prigovor-submit">Podnesi Prigovor</button>
                        </form>
                    @elseif($mmspReview['closed'])
                        <p>Postupak po ovom Pozivu je zatvoren.</p>
                    @elseif($mmspReview['rankingFormed'])
                        <p>{{ \App\Services\Competitions\CompletenessAssessmentService::STAGE_LOCKED_MESSAGE }}</p>
                    @elseif($mmspReview['status'] === \App\Services\Competitions\ComplaintService::STATUS_INCOMPLETE)
                        <p>Rok za Prigovor je istekao.</p>
                    @endif

                    @if($mmspReview['closed'] && $mmspReview['complaintSubmittedAt'])
                        <p>Postupak po ovom Pozivu je zatvoren.</p>
                    @endif
                    @if($mmspReview['rankingFormed'] && $mmspReview['proceedsToScoring'])
                        <p>Bodovanje je završeno i formirana je preliminarna rang lista.</p>
                    @endif
                </div>
            @endif

            @if($application->status === 'draft')
            <div class="info-card" id="application-documents">
                <h2>Dodaj dokument</h2>

                @if($canManage)
                    <div class="upload-section" style="margin-top: 0; background: #f9fafb; padding: 15px;">
                        <form method="POST" action="{{ route('applications.upload', $application) }}" enctype="multipart/form-data" id="canonical-status-upload-form">
                            @csrf

                            <div class="form-group" style="margin-bottom: 12px;">
                                <label class="form-label" for="canonical_document_type" style="font-size: 13px;">Tip dokumenta</label>
                                <select name="document_type" id="canonical_document_type" class="form-control" style="font-size: 13px; padding: 8px;" required>
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

                            <div class="form-group" style="margin-bottom: 12px;">
                                <label class="form-label" style="font-size: 13px;">Fajl</label>
                                <div class="file-input-wrapper" style="min-height: 32px;">
                                    <input id="canonical_status_files" type="file" name="files[]" accept="application/pdf" multiple style="height: 32px;">
                                    <label for="canonical_status_files" class="file-input-label-custom" id="canonical_file_label" style="padding: 6px 12px; font-size: 12px;">Izaberi fajlove (možete izabrati više)</label>
                                    <div id="canonical_file_names" class="file-names-app" style="display: none; margin-top: 8px;"></div>
                                </div>
                                <div style="background: #eff6ff; border-left: 4px solid #3b82f6; padding: 12px; margin-top: 8px; border-radius: 4px;">
                                    <strong style="color: #1e40af; display: block; margin-bottom: 4px;">ℹ️ Važno:</strong>
                                    <span style="color: #1e3a8a; font-size: 12px;">
                                        Ako izaberete više fajlova, oni će biti spojeni u <strong>jedan PDF dokument</strong>, redoslijedom kojim su navedeni. Redoslijed fajlova možete promijeniti pomoću dugmadi ‘Gore’ i ‘Dolje’ prije upload-a.
                                        MMSP prilozi se dostavljaju u PDF formatu.
                                    </span>
                                </div>
                            </div>
                            <div class="form-group" id="canonical-merged-document-name-wrap" style="display: none; margin-bottom: 12px;">
                                <label class="form-label" style="font-size: 13px;" for="canonical_document_name">Naziv spojenog dokumenta <span style="color: #ef4444;">*</span></label>
                                <input
                                    type="text"
                                    name="document_name"
                                    id="canonical_document_name"
                                    class="form-control @error('document_name') error @enderror"
                                    value="{{ old('document_name') }}"
                                    maxlength="255"
                                    placeholder="Unesite naziv pod kojim će biti sačuvan spojeni PDF"
                                    style="font-size: 13px; padding: 8px;"
                                >
                                <div style="font-size: 12px; color: #6b7280; margin-top: 6px;">
                                    Ovaj naziv će biti prikazan u listi priložene dokumentacije. Ekstenzija .pdf se dodaje automatski.
                                </div>
                                @error('document_name')
                                    <div style="color: #ef4444; font-size: 12px; margin-top: 6px;">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group" style="margin-bottom: 15px;">
                                <label class="form-label" for="canonical_library_document" style="font-weight: 400; font-size: 12px;">Ili iz biblioteke</label>
                                <select name="user_document_id" id="canonical_library_document" class="form-control" style="font-size: 12px; padding: 6px;">
                                    <option value="">Izaberi...</option>
                                    @foreach($libraryDocuments as $libraryDocument)
                                        <option value="{{ $libraryDocument->id }}">{{ \Illuminate\Support\Str::limit($libraryDocument->name, 20) }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group" style="margin-bottom: 15px; display: flex; align-items: center; gap: 8px;">
                                <input type="checkbox" name="save_to_library" id="canonical-save-to-library" value="1" style="width: 18px; height: 18px; cursor: pointer;">
                                <label for="canonical-save-to-library" class="form-label" style="font-weight: 400; font-size: 12px; margin: 0; cursor: pointer;">Sačuvaj dokument i u moju biblioteku</label>
                            </div>

                            <button type="submit" class="btn btn-primary" style="width: 100%; font-size: 13px; padding: 10px;">Priloži dokument</button>
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
            @endif
        </div>

        @if($mmspReviewSummary)
            <div class="mmsp-prigovor-card" id="application-review" data-status="{{ $mmspReview['status'] }}">
                <h2>Provjera potpunosti</h2>
                <p><strong>{{ $mmspReview['label'] }}</strong></p>
                @if($mmspReview['closed'])
                    <p>Postupak po ovom Pozivu je zatvoren.</p>
                @elseif($mmspReview['rankingFormed'] && $mmspReview['proceedsToScoring'])
                    <p>Bodovanje je završeno i formirana je preliminarna rang lista.</p>
                @endif
            </div>
        @endif

        @if($errors->any())
            <div class="alert" style="background: #fee2e2; border-color: #ef4444; color: #991b1b; margin-bottom: 24px;">
                <strong>Greška:</strong>
                <ul style="margin-top: 8px; padding-left: 20px;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="info-card">
            <h2>Priložena dokumentacija</h2>
            <div style="margin-bottom: 20px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                    <span style="font-size: 14px; color: #374151; font-weight: 600;">
                        Dokumenti: {{ $uploadedUploads }} / {{ $totalUploads }}
                    </span>
                    <span style="font-size: 14px; color: #6b7280;">{{ $uploadProgress }}%</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: {{ $uploadProgress }}%;"></div>
                </div>
            </div>
            <ul class="documents-list">
                @foreach($uploadRows as $row)
                    @php $uploaded = $row['document'] !== null; @endphp
                    <li class="document-item {{ $uploaded ? 'uploaded' : ($row['required_now'] ? 'required' : '') }}">
                        <div class="document-info">
                            <div class="document-name">
                                {{ $row['label'] }}
                                @if(!$uploaded)
                                    @if($row['required_now'])
                                        <span style="color:#ef4444; font-size:12px; margin-left:8px;">(Obavezno)</span>
                                    @else
                                        <span style="color:#92400e; font-size:12px; margin-left:8px;">(Opciono)</span>
                                    @endif
                                @endif
                            </div>
                            @if($uploaded)
                                <div class="document-type">
                                    Upload-ovano: {{ $row['document']->created_at->format('d.m.Y H:i') }}
                                    @if($row['issued_on']) · izdano {{ \Carbon\Carbon::parse($row['issued_on'])->format('d.m.Y.') }} @endif
                                </div>
                            @endif
                        </div>
                        @if($uploaded)
                            <div class="document-actions">
                                <a href="{{ route('applications.document.view', [$application, $row['document']]) }}"
                                   class="btn btn-secondary" target="_blank" style="margin-right: 4px;">
                                    Pogledaj
                                </a>
                                @if($canManage)
                                    <form action="{{ route('applications.document.destroy', ['application' => $application, 'document' => $row['document']]) }}"
                                          method="POST"
                                          style="display: inline;"
                                          onsubmit="return confirm('Da li ste sigurni da želite da uklonite ovaj dokument iz prijave?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger" style="padding: 8px 12px;">
                                            Ukloni
                                        </button>
                                    </form>
                                @endif
                            </div>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>

        @if($canStartAnother)
            <div class="alert alert-info">
                Na isti javni poziv možete podnijeti još jednu prijavu za drugu vrstu subvencije.
                <a href="{{ route('applications.canonical.legal-form', $competition) }}">Započni drugu prijavu</a>
            </div>
        @endif

        <div class="info-card" style="text-align: center;">
            <a href="{{ route('dashboard') }}" class="btn btn-secondary">Nazad na Moj panel</a>
            @if($application->status === 'draft' && !$p1Complete)
                <span style="font-size:12px; color:#6b7280; margin-left:8px;">Popunite Obrazac {{ $formCode }} prvo</span>
            @endif
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

    const fileLabel = document.getElementById('canonical_file_label');
    const mergedNameWrap = document.getElementById('canonical-merged-document-name-wrap');
    const mergedNameInput = document.getElementById('canonical_document_name');
    let selectedFiles = [];

    function syncFileInput() {
        if (!filesInput) return;
        const dataTransfer = new DataTransfer();
        selectedFiles.forEach(function (file) { dataTransfer.items.add(file); });
        filesInput.files = dataTransfer.files;
    }

    function updateMergedName() {
        if (!mergedNameWrap || !mergedNameInput) return;
        const multiple = selectedFiles.length > 1;
        mergedNameWrap.style.display = multiple ? 'block' : 'none';
        mergedNameInput.required = multiple;
    }

    function renderSelectedFiles() {
        if (!fileNames || !fileLabel) return;
        if (selectedFiles.length === 0) {
            fileNames.style.display = 'none';
            fileNames.innerHTML = '';
            fileLabel.textContent = 'Izaberi fajlove (možete izabrati više)';
            updateMergedName();
            return;
        }

        let html = '<div style="font-size: 12px; color: var(--primary); font-weight: 600; margin-bottom: 4px;">Izabrano fajlova: ' + selectedFiles.length + (selectedFiles.length > 1 ? ' (biće spojeni u jedan PDF)' : '') + '</div>';
        html += '<ul style="margin: 0; padding-left: 20px; font-size: 12px; color: #6b7280; list-style: none;">';
        selectedFiles.forEach(function (file, index) {
            const size = (file.size / 1024 / 1024).toFixed(2);
            const safeName = String(file.name).replace(/[&<>"']/g, function (char) {
                return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[char];
            });
            html += '<li style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; padding: 6px; background: #f9fafb; border-radius: 4px;">';
            html += '<span style="flex: 1;"><strong style="color: var(--primary);">' + (index + 1) + '.</strong> ' + safeName + ' (' + size + ' MB)</span>';
            html += '<div style="display: flex; gap: 4px; margin-left: 8px;">';
            if (index > 0) {
                html += '<button type="button" class="canonical-file-action" data-action="move-up" data-index="' + index + '" title="Pomeri gore" style="background: #3b82f6; color: white; border: none; border-radius: 4px; padding: 4px 8px; font-size: 11px; cursor: pointer;">⬆️</button>';
            } else {
                html += '<button type="button" disabled style="background: #d1d5db; color: #9ca3af; border: none; border-radius: 4px; padding: 4px 8px; font-size: 11px; cursor: not-allowed;">⬆️</button>';
            }
            if (index < selectedFiles.length - 1) {
                html += '<button type="button" class="canonical-file-action" data-action="move-down" data-index="' + index + '" title="Pomeri dolje" style="background: #3b82f6; color: white; border: none; border-radius: 4px; padding: 4px 8px; font-size: 11px; cursor: pointer;">Dolje</button>';
            } else {
                html += '<button type="button" disabled style="background: #d1d5db; color: #9ca3af; border: none; border-radius: 4px; padding: 4px 8px; font-size: 11px; cursor: not-allowed;">Dolje</button>';
            }
            html += '<button type="button" class="canonical-file-action" data-action="remove" data-index="' + index + '" title="Ukloni" style="background: #ef4444; color: white; border: none; border-radius: 4px; padding: 4px 8px; font-size: 11px; cursor: pointer;">✕</button>';
            html += '</div></li>';
        });
        html += '</ul>';
        fileNames.innerHTML = html;
        fileNames.style.display = 'block';
        fileLabel.textContent = selectedFiles.length === 1
            ? selectedFiles[0].name
            : 'Izabrano ' + selectedFiles.length + ' fajlova (biće spojeni u jedan PDF)';
        updateMergedName();
    }

    function refreshFromInput() {
        if (!filesInput) return;
        Array.from(filesInput.files || []).forEach(function (file) {
            const exists = selectedFiles.some(function (current) {
                return current.name === file.name && current.size === file.size;
            });
            if (!exists) selectedFiles.push(file);
        });
        syncFileInput();
        renderSelectedFiles();
    }

    if (filesInput) {
        filesInput.addEventListener('change', refreshFromInput);
    }
    if (fileNames) {
        fileNames.addEventListener('click', function (event) {
            const button = event.target.closest('.canonical-file-action');
            if (!button) return;
            event.preventDefault();
            const index = Number(button.dataset.index);
            if (button.dataset.action === 'remove') {
                selectedFiles.splice(index, 1);
            } else if (button.dataset.action === 'move-up' && index > 0) {
                const current = selectedFiles[index];
                selectedFiles[index] = selectedFiles[index - 1];
                selectedFiles[index - 1] = current;
            } else if (button.dataset.action === 'move-down' && index < selectedFiles.length - 1) {
                const current = selectedFiles[index];
                selectedFiles[index] = selectedFiles[index + 1];
                selectedFiles[index + 1] = current;
            }
            syncFileInput();
            renderSelectedFiles();
        });
    }
})();
</script>

</div>
@endsection
