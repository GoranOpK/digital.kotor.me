@extends('layouts.app')

@section('content')
@php
    $competition->loadMissing('upNumber');
@endphp
<style>
    .mmsp-ranking-print-root {
        --mmsp-ranking-primary: #0B3D91;
        margin-bottom: 24px;
    }
    .mmsp-ranking-print-root.is-blocked,
    .mmsp-ranking-list-card {
        background: #fff;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    .mmsp-ranking-print-root.is-blocked {
        border-left: 4px solid #8b5cf6;
    }
    .mmsp-ranking-print-root .obrazac-zaglavlje {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 20px 24px;
        margin-bottom: 24px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.06);
    }
    .mmsp-ranking-print-root .obrazac-zaglavlje-top {
        display: flex;
        align-items: flex-start;
        gap: 20px;
    }
    .mmsp-ranking-print-root .obrazac-grb {
        flex-shrink: 0;
        line-height: 0;
    }
    .mmsp-ranking-print-root .obrazac-grb img {
        height: 2cm;
        width: auto;
        display: block;
    }
    .mmsp-ranking-print-root .obrazac-org {
        flex: 1;
        font-size: 13px;
        line-height: 1.5;
        color: #111;
    }
    .mmsp-ranking-print-root .obrazac-org p { margin: 0 0 2px 0; }
    .mmsp-ranking-print-root .obrazac-contact {
        text-align: right;
        font-size: 13px;
        line-height: 1.5;
        color: #111;
    }
    .mmsp-ranking-print-root .obrazac-contact p { margin: 0 0 2px 0; }
    .mmsp-ranking-print-root .obrazac-broj-i-naslov {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
        margin-top: 12px;
        padding-top: 12px;
        border-top: 1px solid #e5e7eb;
    }
    .mmsp-ranking-print-root .obrazac-broj-prijave {
        font-size: 13px;
        color: #374151;
        font-weight: 500;
    }
    .mmsp-ranking-print-root .obrazac-naslov-prijava {
        width: 100%;
        text-align: center;
        font-size: 22px;
        font-weight: 700;
        letter-spacing: 0.02em;
        color: #111;
        margin: 8px 0 0 0;
        text-transform: uppercase;
    }
    .mmsp-ranking-print-root .obrazac-podnaslov {
        text-align: center;
        font-size: 13px;
        line-height: 1.5;
        color: #374151;
        margin: 4px 0 0 0;
    }
    .mmsp-ranking-list-card > h2 {
        font-size: 20px;
        font-weight: 700;
        color: var(--mmsp-ranking-primary);
        margin: 0 0 20px;
        padding-bottom: 12px;
        border-bottom: 2px solid #e5e7eb;
    }
    .mmsp-ranking-print-root .btn-primary {
        background: var(--mmsp-ranking-primary);
        color: #fff;
        padding: 10px 16px;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
    }
    .mmsp-ranking-list-card,
    .mmsp-ranking-decision-section {
        max-width: 100%;
        min-width: 0;
    }
    .mmsp-ranking-table-wrap {
        display: block;
        width: 100%;
        max-width: 100%;
        min-width: 0;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        box-sizing: border-box;
    }
    .mmsp-ranking-print-root table.mmsp-ranking-table {
        width: 100%;
        min-width: 640px;
        table-layout: fixed;
        border-collapse: separate;
        border-spacing: 0;
        margin: 0;
    }
    .mmsp-ranking-print-root table.mmsp-ranking-table col.mmsp-col-pos { width: 8%; }
    .mmsp-ranking-print-root table.mmsp-ranking-table col.mmsp-col-subsidy { width: 20%; }
    .mmsp-ranking-print-root table.mmsp-ranking-table col.mmsp-col-applicant { width: 18%; }
    .mmsp-ranking-print-root table.mmsp-ranking-table col.mmsp-col-score { width: 12%; }
    .mmsp-ranking-print-root table.mmsp-ranking-table col.mmsp-col-needed { width: 14%; }
    .mmsp-ranking-print-root table.mmsp-ranking-table col.mmsp-col-approved { width: 14%; }
    .mmsp-ranking-print-root table.mmsp-ranking-table col.mmsp-col-decision { width: 14%; }
    .mmsp-ranking-print-root table.mmsp-ranking-table th,
    .mmsp-ranking-print-root table.mmsp-ranking-table td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid #e5e7eb;
        vertical-align: top;
        background-clip: padding-box;
        overflow-wrap: anywhere;
    }
    .mmsp-ranking-print-root .mmsp-ranking-reason {
        display: block;
        margin-top: 6px;
        font-size: 12px;
        font-weight: 600;
        line-height: 1.35;
        text-align: left;
        color: #374151;
    }
    .mmsp-ranking-print-root table.mmsp-ranking-table th:first-child,
    .mmsp-ranking-print-root table.mmsp-ranking-table td:first-child {
        width: 4.5rem;
        min-width: 4.5rem;
        position: sticky;
        left: 0;
        z-index: 1;
        background: #fff;
    }
    .mmsp-ranking-print-root table.mmsp-ranking-table th:first-child {
        background: #f9fafb;
    }
    .mmsp-ranking-table-wrap.is-below table.mmsp-ranking-table td:first-child {
        background: #f9fafb;
    }
    .mmsp-ranking-print-root table.mmsp-ranking-table th {
        font-weight: 600;
        color: #374151;
        font-size: 12px;
        text-transform: uppercase;
        background: #f9fafb;
    }
    .mmsp-ranking-print-root .ranking-badge {
        display: inline-block;
        width: 32px;
        height: 32px;
        background: var(--mmsp-ranking-primary);
        color: #fff;
        border-radius: 50%;
        text-align: center;
        line-height: 32px;
        font-weight: 700;
        font-size: 14px;
    }
    .mmsp-ranking-print-root .score-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 9999px;
        font-size: 14px;
        font-weight: 600;
        white-space: nowrap;
    }
    .mmsp-ranking-print-root .score-high {
        background: #d1fae5;
        color: #065f46;
    }
    .mmsp-ranking-print-root .score-medium {
        background: #fef3c7;
        color: #92400e;
    }
    .mmsp-ranking-print-root .score-low {
        background: #fee2e2;
        color: #991b1b;
    }
    .mmsp-ranking-print-root .mmsp-ranking-print-btn {
        background: #6b7280;
        color: #fff;
        border: none;
        cursor: pointer;
        padding: 10px 20px;
        border-radius: 8px;
        font-weight: 600;
    }
    .mmsp-ranking-print-root .mmsp-ranking-decision-block {
        background: #f9fafb;
        padding: 24px;
        border-radius: 12px;
        margin-bottom: 24px;
        border: 1px solid #e5e7eb;
    }
    .mmsp-ranking-print-root .mmsp-ranking-decision-form input[type="text"],
    .mmsp-ranking-print-root .mmsp-ranking-decision-form select,
    .mmsp-ranking-print-root .mmsp-ranking-decision-form textarea {
        background: #ffffff;
        color: #111827;
        border: 1px solid #9ca3af;
        border-radius: 6px;
        padding: 8px 10px;
        font-size: 14px;
        line-height: 1.4;
        box-sizing: border-box;
        width: 100%;
    }
    .mmsp-ranking-print-root .mmsp-ranking-decision-form input[type="text"]:focus,
    .mmsp-ranking-print-root .mmsp-ranking-decision-form select:focus,
    .mmsp-ranking-print-root .mmsp-ranking-decision-form textarea:focus {
        outline: 2px solid #2563eb;
        outline-offset: 1px;
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.25);
    }
    .mmsp-ranking-print-root .mmsp-decision-choices {
        display: flex;
        gap: 32px;
        flex-wrap: wrap;
    }
    .mmsp-ranking-print-root .mmsp-decision-choice {
        display: flex;
        align-items: center;
        gap: 10px;
        cursor: pointer;
        padding: 12px;
        background: #fff;
        border-radius: 8px;
        border: 2px solid #e5e7eb;
        margin: 0;
    }
    .mmsp-ranking-print-root .mmsp-decision-choice input[type="radio"] {
        width: auto;
        margin: 0;
    }
    .mmsp-ranking-print-root .mmsp-decision-amount-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 24px;
        margin-bottom: 24px;
        min-width: 0;
    }
    .mmsp-ranking-print-root .mmsp-decision-amount-grid > *,
    .mmsp-ranking-print-root .mmsp-decision-budget-grid > * {
        min-width: 0;
    }
    .mmsp-ranking-print-root .mmsp-decision-budget-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
        margin-bottom: 24px;
    }
    .mmsp-ranking-print-root .mmsp-decision-fact {
        padding: 10px 12px;
        background: #fff;
        border-radius: 6px;
        color: #374151;
        font-size: 14px;
    }
    .mmsp-ranking-print-root .mmsp-decision-field[hidden] {
        display: none;
    }
    .mmsp-ranking-note { margin-top: 6px; font-size: 12px; color: #6b7280; }
    .mmsp-ranking-conclusions { margin-top: 0; padding-top: 24px; }
    .mmsp-ranking-conclusions > h2,
    .mmsp-ranking-signatures > h2 {
        font-size: 18px;
        font-weight: 700;
        color: var(--mmsp-ranking-primary);
        margin: 0 0 12px;
    }
    .mmsp-ranking-conclusion {
        background: #f9fafb;
        padding: 24px;
        border-radius: 12px;
        margin-bottom: 24px;
        border: 1px solid #e5e7eb;
    }
    .mmsp-ranking-print-root table.mmsp-conclusion-table {
        width: 100%;
        border-collapse: collapse;
        margin: 0;
    }
    .mmsp-ranking-print-root table.mmsp-conclusion-table th,
    .mmsp-ranking-print-root table.mmsp-conclusion-table td {
        padding: 10px 12px;
        text-align: left;
        border: 1px solid #e5e7eb;
        vertical-align: top;
        font-size: 14px;
    }
    .mmsp-ranking-print-root table.mmsp-conclusion-table th {
        width: 28%;
        font-weight: 600;
        font-size: 13px;
        color: #374151;
        background: #f9fafb;
    }
    .mmsp-ranking-print-root table.mmsp-conclusion-table .mmsp-conclusion-name th {
        width: auto;
        font-size: 16px;
        font-weight: 700;
        color: #0B3D91;
        background: #fff;
    }
    .mmsp-ranking-error { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; border-radius: 8px; padding: 12px; margin-bottom: 16px; }
    @media (max-width: 768px) {
        .mmsp-ranking-print-root .obrazac-zaglavlje-top { flex-wrap: wrap; }
        .mmsp-ranking-print-root .obrazac-contact { text-align: left; }
        .mmsp-ranking-print-root .obrazac-naslov-prijava { font-size: 18px; }
        .mmsp-ranking-print-root table.mmsp-ranking-table th,
        .mmsp-ranking-print-root table.mmsp-ranking-table td {
            padding: 8px;
            font-size: 12px;
        }
        .mmsp-ranking-print-root .ranking-badge {
            width: 28px;
            height: 28px;
            line-height: 28px;
            font-size: 12px;
        }
        .mmsp-ranking-print-root .mmsp-decision-amount-grid,
        .mmsp-ranking-print-root .mmsp-decision-budget-grid {
            grid-template-columns: 1fr;
        }
    }
    @media print {
        @page { size: A4 portrait; margin: 8mm 10mm; }
        nav, header, footer,
        .mmsp-ranking-no-print,
        .no-print,
        .mmsp-ranking-decision-section,
        .mmsp-ranking-decision-form,
        form, button, select, textarea, input {
            display: none !important;
        }
        .admin-page {
            min-height: 0 !important;
            padding: 0 !important;
            background: #fff !important;
        }
        .container { max-width: none !important; padding: 0 !important; }
        body.mmsp-ranking-print .container > *:not(.mmsp-ranking-print-root) {
            display: none !important;
        }
        body.mmsp-ranking-print .mmsp-ranking-print-root {
            position: static;
            box-shadow: none;
            border: none;
            border-radius: 0;
            padding: 0;
            margin: 0;
            background: #fff;
        }
        .mmsp-ranking-print-root .obrazac-zaglavlje,
        .mmsp-ranking-list-card {
            box-shadow: none;
            border: none !important;
            border-radius: 0;
            padding: 0 0 4px 0;
            margin-bottom: 4px;
        }
        .mmsp-ranking-print-root .obrazac-zaglavlje { padding: 0 0 2px 0; }
        .mmsp-ranking-print-root .obrazac-zaglavlje-top { gap: 8px; }
        .mmsp-ranking-print-root .obrazac-grb img { height: 1.3cm; }
        .mmsp-ranking-print-root .obrazac-org p,
        .mmsp-ranking-print-root .obrazac-contact p { margin: 0; line-height: 1.2; }
        .mmsp-ranking-print-root .obrazac-broj-i-naslov { margin-top: 2px; padding-top: 2px; gap: 4px; }
        .mmsp-ranking-print-root .obrazac-naslov-prijava { font-size: 13pt; margin-top: 2px; }
        .mmsp-ranking-print-root .obrazac-org,
        .mmsp-ranking-print-root .obrazac-contact,
        .mmsp-ranking-print-root .obrazac-podnaslov,
        .mmsp-ranking-print-root .obrazac-broj-prijave { font-size: 8pt; }
        .mmsp-ranking-list-card > h2 { font-size: 12pt; margin: 0 0 6px; padding-bottom: 4px; }
        .mmsp-ranking-intro { margin-bottom: 4px !important; font-size: 8pt !important; }
        .mmsp-ranking-print-root .below-minimum-section {
            margin-top: 8px !important;
            padding-top: 6px !important;
            border-top-width: 1px !important;
            box-shadow: none !important;
        }
        .mmsp-ranking-print-root .below-minimum-heading {
            margin: 0 0 4px 0 !important;
        }
        .mmsp-ranking-print-root .below-minimum-section h3 { font-size: 9pt !important; margin: 0 0 4px !important; }
        .mmsp-ranking-print-root table.mmsp-ranking-table thead { display: table-header-group; }
        .mmsp-ranking-print-root table.mmsp-ranking-table tr,
        .mmsp-ranking-conclusion,
        .mmsp-ranking-signatures {
            break-inside: avoid;
            page-break-inside: avoid;
        }
        .mmsp-ranking-conclusions { margin-top: 6px; }
        .mmsp-ranking-conclusions > h2,
        .mmsp-ranking-signatures > h2 { font-size: 11pt; margin: 0 0 4px; }
        .mmsp-ranking-conclusion {
            padding: 0;
            margin: 0 0 4px;
            border: none;
            background: transparent;
            box-shadow: none;
        }
        .mmsp-ranking-print-root table.mmsp-conclusion-table th,
        .mmsp-ranking-print-root table.mmsp-conclusion-table td {
            border: 1px solid #000 !important;
            padding: 2px 4px !important;
            font-size: 8pt !important;
            background: transparent !important;
            color: #000 !important;
        }
        .mmsp-ranking-signatures { padding: 2px 0 0 !important; margin: 2px 0 0 !important; font-size: 8pt !important; line-height: 1.2 !important; }
        .mmsp-ranking-signatures > div { margin-top: 2px !important; }
        .mmsp-ranking-signatures p { margin: 0 0 2px !important; }
        .mmsp-ranking-signatures div { margin-top: 0 !important; margin-bottom: 2px !important; }
        .mmsp-ranking-print-root table.mmsp-ranking-table {
            min-width: 0;
            font-size: 8pt;
            border-collapse: collapse;
        }
        .mmsp-ranking-print-root .mmsp-ranking-reason {
            margin-top: 2px;
            font-size: 7pt;
            font-weight: 600;
        }
        .mmsp-ranking-print-root table.mmsp-ranking-table th,
        .mmsp-ranking-print-root table.mmsp-ranking-table td {
            border: 1px solid #000 !important;
            padding: 3px 4px !important;
            font-size: 8pt !important;
            background: transparent !important;
            color: #000 !important;
        }
        .mmsp-ranking-print-root .ranking-badge {
            background: transparent !important;
            color: #000 !important;
            border: 1px solid #000;
            width: 22px;
            height: 22px;
            line-height: 22px;
            font-size: 8pt;
        }
        .mmsp-ranking-print-root .score-badge {
            background: transparent !important;
            color: #000 !important;
            border: none;
            padding: 0;
            font-size: 8pt;
        }
        .mmsp-ranking-print-root .below-minimum-heading {
            background: transparent !important;
            color: #000 !important;
            border: none !important;
            box-shadow: none !important;
            padding: 0 0 6px 0 !important;
            font-size: 9pt !important;
        }
        .mmsp-ranking-table-wrap { overflow: visible; }
        .mmsp-ranking-print-root table.mmsp-ranking-table th:first-child,
        .mmsp-ranking-print-root table.mmsp-ranking-table td:first-child {
            position: static;
            width: auto;
            min-width: 0;
            background: transparent;
        }
    }
</style>
<script>
    window.printMmspRanking = function (button) {
        var root = button && typeof button.closest === 'function'
            ? button.closest('.mmsp-ranking-print-root')
            : null;
        if (!root) {
            return;
        }
        var cleaned = false;
        var done = function () {
            if (cleaned) {
                return;
            }
            cleaned = true;
            document.body.classList.remove('mmsp-ranking-print');
            root.classList.remove('mmsp-ranking-print-target');
            window.removeEventListener('afterprint', done);
        };
        window.addEventListener('afterprint', done);
        document.body.classList.add('mmsp-ranking-print');
        root.classList.add('mmsp-ranking-print-target');
        window.print();
    };
</script>

<div class="admin-page" style="background: #f9fafb; min-height: 100vh; padding: 24px 0;">
<div class="container mx-auto px-4">
    @if(session('success'))
        <div class="alert no-print" role="status" style="background: #ecfdf5; border: 1px solid #10b981; color: #065f46; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px;">
            {{ session('success') }}
        </div>
    @endif
    @if($errors->any())
        <div class="mmsp-ranking-error no-print" data-testid="mmsp-ranking-error">{{ $errors->first() }}</div>
    @endif

    @if(! ($rankingView['visible'] ?? false))
        <div class="mmsp-ranking-print-root is-blocked" data-testid="mmsp-ranking-page">
            <div data-testid="mmsp-ranking-blocked">
                <strong>{{ $competition->title }}</strong>
                <p style="margin: 8px 0 0; color: #92400e; font-weight: 600;">{{ $rankingView['block_reason'] ?? \App\Services\CanonicalIndividualScoringService::CANONICAL_CYCLE_INCOMPLETE_MESSAGE }}</p>
            </div>
        </div>
    @else
        @php
            $supportThreshold = \App\Services\CanonicalIndividualScoringService::CANONICAL_SUPPORT_THRESHOLD;
            $above = [];
            $below = [];
            foreach ($outcomeRows as $row) {
                if (bccomp((string) $row->display, $supportThreshold, 2) >= 0) {
                    $above[] = $row;
                } else {
                    $below[] = $row;
                }
            }
            $mmspApplicantName = static function ($row): string {
                $applicantForm = $row->application->canonicalApplicantForm;
                $applicantName = $applicantForm?->legal_form === \App\Support\CanonicalLegalForm::DRUSTVO
                    ? trim((string) $applicantForm->company_name)
                    : trim((string) ($applicantForm->person_name ?? ''));
                if ($applicantName === '') {
                    $applicantName = $row->application->founder_name ?? '—';
                }

                return $applicantName;
            };
            $mmspScoreText = static fn ($row): string => number_format((float) $row->display, 2).' / 50';
            $mmspMoneyPlain = static fn ($amount): string => $amount === null || $amount === ''
                ? '—'
                : number_format((float) $amount, 2, ',', '.').' €';
            $mmspReasonText = static function ($outcome): ?string {
                if ($outcome === null || $outcome->isSupport() || ! filled($outcome->rejection_reason)) {
                    return null;
                }

                return \App\Models\CanonicalOutcome::REASON_LABELS[$outcome->rejection_reason] ?? null;
            };
            $mmspScoreClass = static function ($row): string {
                $score = (float) $row->display;
                if ($score >= 40) {
                    return 'score-high';
                }

                return $score >= 30 ? 'score-medium' : 'score-low';
            };
        @endphp

        <div class="mmsp-ranking-print-root" data-testid="mmsp-ranking-page">
            <div class="obrazac-zaglavlje">
                <div class="obrazac-zaglavlje-top">
                    <div class="obrazac-grb">
                        <img src="{{ asset('images/srednji_grb.png') }}" alt="Grb Opštine Kotor" onerror="this.onerror=null; this.src='{{ asset('images/srednji_grb.svg') }}';">
                    </div>
                    <div class="obrazac-org">
                        <p><strong>Crna Gora</strong></p>
                        <p>Opština Kotor</p>
                        <p>Sekretarijat za razvoj preduzetništva</p>
                        <p>komunalne poslove i saobraćaj</p>
                    </div>
                    <div class="obrazac-contact">
                        <p>Stari grad 317</p>
                        <p>85330 Kotor, Crna Gora</p>
                        <p>tel. +382(0)32 325 865</p>
                        <p>privreda@kotor.me</p>
                        <p>www.kotor.me</p>
                    </div>
                </div>
                <div class="obrazac-broj-i-naslov">
                    <div class="obrazac-broj-prijave">
                        Konkurs: {{ $competition->upNumber?->number ?? '—' }} / {{ $competition->year ?? '' }}
                    </div>
                </div>
                <h1 class="obrazac-naslov-prijava">RANG LISTA PLANOVA ULAGANJA</h1>
                <p class="obrazac-podnaslov">{{ $competition->title }}</p>
            </div>

            <div class="mmsp-ranking-list-card">
                <h2 data-testid="mmsp-ranking-title">{{ $canonicalRankingIsFinal ? 'Konačna rang-lista' : 'Preliminarna rang-lista' }}</h2>
                <p class="mmsp-ranking-intro" style="color: #4b5563; margin-bottom: 16px;">
                    Planovi ispod 30 bodova ostaju na listi i ne mogu biti podržani.
                    Isti rang znači stvarno jednak konačni rezultat; redoslijed unutar istog ranga je tehnički i ne daje prednost.
                </p>

                @if(count($above) > 0)
                <div class="mmsp-ranking-table-wrap">
                    <table class="mmsp-ranking-table">
                        <colgroup>
                            <col class="mmsp-col-pos">
                            <col class="mmsp-col-subsidy">
                            <col class="mmsp-col-applicant">
                            <col class="mmsp-col-score">
                            <col class="mmsp-col-needed">
                            <col class="mmsp-col-approved">
                            <col class="mmsp-col-decision">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Poz.</th>
                                <th>Vrsta subvencije</th>
                                <th>Podnosilac</th>
                                <th style="text-align: center;">Ocjena</th>
                                <th style="text-align: right;">Potrebna sredstva</th>
                                <th style="text-align: right;">Odobrena sredstva</th>
                                <th style="text-align: center;">Odluka</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($above as $row)
                                @php $outcome = $outcomeRecords->get($row->id()); @endphp
                                <tr data-testid="mmsp-ranking-row">
                                    <td><span class="ranking-badge">{{ $row->rank }}</span></td>
                                    <td>{{ $row->subsidyType && \App\Support\CanonicalSubsidyType::isValid($row->subsidyType) ? \App\Support\CanonicalSubsidyType::label($row->subsidyType) : '—' }}</td>
                                    <td>{{ $mmspApplicantName($row) }}</td>
                                    <td style="text-align: center;">
                                        <span class="score-badge {{ $mmspScoreClass($row) }}">{{ $mmspScoreText($row) }}</span>
                                    </td>
                                    <td style="text-align: right;">{{ $mmspMoneyPlain($row->totalInvestment) }}</td>
                                    <td style="text-align: right;">
                                        @if($outcome?->isSupport())
                                            <strong>{{ $mmspMoneyPlain($outcome->approved_amount) }}</strong>
                                        @else
                                            <span style="color: #6b7280;">—</span>
                                        @endif
                                    </td>
                                    <td style="text-align: center;">
                                        @if($outcome)
                                            <span class="score-badge {{ $outcome->isSupport() ? 'score-high' : 'score-low' }}">{{ $outcome->isSupport() ? 'Podržava' : 'Odbija' }}</span>
                                            @if($mmspReasonText($outcome))<span class="mmsp-ranking-reason">{{ $mmspReasonText($outcome) }}</span>@endif
                                        @else
                                            <span style="color: #6b7280;">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif

                @if(count($below) > 0)
                <div class="below-minimum-section" style="margin-top: 48px; padding-top: 32px; border-top: 4px solid #374151; box-shadow: 0 -2px 8px rgba(0,0,0,0.08);">
                    <div class="below-minimum-heading" style="background: #374151; color: #fff; padding: 14px 24px; margin-top: -32px; margin-bottom: 24px; border-radius: 8px; font-weight: 700; font-size: 16px; text-align: center; display: inline-block; width: 100%; box-sizing: border-box;">
                        ——— Ispod praga od 30 bodova ———
                    </div>
                    <h3 style="font-size: 16px; font-weight: 600; color: #6b7280; margin-bottom: 16px;">Planovi sa manje od 30 bodova</h3>
                    <div class="mmsp-ranking-table-wrap is-below">
                        <table class="mmsp-ranking-table">
                            <colgroup>
                                <col class="mmsp-col-pos">
                                <col class="mmsp-col-subsidy">
                                <col class="mmsp-col-applicant">
                                <col class="mmsp-col-score">
                                <col class="mmsp-col-needed">
                                <col class="mmsp-col-approved">
                                <col class="mmsp-col-decision">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>Poz.</th>
                                    <th>Vrsta subvencije</th>
                                    <th>Podnosilac</th>
                                    <th style="text-align: center;">Ocjena</th>
                                    <th style="text-align: right;">Potrebna sredstva</th>
                                    <th style="text-align: right;">Odobrena sredstva</th>
                                    <th style="text-align: center;">Odluka</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($below as $row)
                                    @php $outcome = $outcomeRecords->get($row->id()); @endphp
                                    <tr data-testid="mmsp-ranking-row" style="background: #f9fafb;">
                                        <td><span class="ranking-badge">{{ $row->rank }}</span></td>
                                        <td>{{ $row->subsidyType && \App\Support\CanonicalSubsidyType::isValid($row->subsidyType) ? \App\Support\CanonicalSubsidyType::label($row->subsidyType) : '—' }}</td>
                                        <td>{{ $mmspApplicantName($row) }}</td>
                                        <td style="text-align: center;">
                                            <span class="score-badge score-low">{{ $mmspScoreText($row) }}</span>
                                        </td>
                                        <td style="text-align: right;">{{ $mmspMoneyPlain($row->totalInvestment) }}</td>
                                        <td style="text-align: right;">
                                            @if($outcome?->isSupport())
                                                <strong>{{ $mmspMoneyPlain($outcome->approved_amount) }}</strong>
                                            @else
                                                <span style="color: #6b7280;">—</span>
                                            @endif
                                        </td>
                                        <td style="text-align: center;">
                                            @if($outcome)
                                                <span class="score-badge score-low">{{ $outcome->isSupport() ? 'Podržava' : 'Odbija' }}</span>
                                            @else
                                                <span class="score-badge score-low">Ispod praga</span>
                                            @endif
                                            @if($mmspReasonText($outcome))
                                                <span class="mmsp-ranking-reason">{{ $mmspReasonText($outcome) }}</span>
                                            @else
                                                <div class="mmsp-ranking-note">Ne može se podržati.</div>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif

                @if($canonicalChair && ! $canonicalRankingIsFinal && count($outcomeRows) > 0)
                @php
                    $mmspMoney = static fn ($amount): string => $amount === null || $amount === ''
                        ? '—'
                        : number_format((float) $amount, 2, ',', '.').' €';
                    $mmspBudget = $fundingPosition['budget'] ?? '0.00';
                    $mmspSupported = $fundingPosition['supported'] ?? '0.00';
                    $mmspRemaining = $fundingPosition['remaining'] ?? '0.00';
                    $mmspAvailable = $fundingPosition['available'] ?? [];
                @endphp
                <div class="mmsp-ranking-decision-section no-print" data-testid="mmsp-ranking-conclusions" style="background: transparent; box-shadow: none; padding: 24px 0 0; margin-bottom: 0;">
                    <h2>Zaključak komisije i obrazloženje</h2>
                    <p class="no-print" style="color: #6b7280; margin-bottom: 24px; font-size: 14px;">
                        Za svaku prijavu u rang listi unesite zaključak komisije i obrazloženje.
                    </p>
                    <div class="mmsp-decision-budget-grid" data-testid="mmsp-ranking-budget">
                        <div>
                            <span style="display: block; font-size: 15px; font-weight: 600; color: #374151; margin-bottom: 8px;">Ukupan budžet</span>
                            <div class="mmsp-decision-fact">{{ $mmspMoney($mmspBudget) }}</div>
                        </div>
                        <div>
                            <span style="display: block; font-size: 15px; font-weight: 600; color: #374151; margin-bottom: 8px;">Zbir evidentirane podrške</span>
                            <div class="mmsp-decision-fact">{{ $mmspMoney($mmspSupported) }}</div>
                        </div>
                        <div>
                            <span style="display: block; font-size: 15px; font-weight: 600; color: #374151; margin-bottom: 8px;">Preostala sredstva</span>
                            <div class="mmsp-decision-fact">{{ $mmspMoney($mmspRemaining) }}</div>
                        </div>
                    </div>
                    @foreach($outcomeRows as $row)
                        @php
                            $outcome = $outcomeRecords->get($row->id());
                            $belowThreshold = $row->belowThreshold();
                            $plan = $row->application->canonicalInvestmentPlan;
                            $requestedSubsidy = ($plan !== null && $plan->isFrozen()) ? $plan->expected_subsidy : null;
                            $availableForRow = $mmspAvailable[$row->id()] ?? $mmspBudget;
                            $entryCap = $row->legalMaximum;
                            if ($entryCap !== null && bccomp((string) $availableForRow, (string) $entryCap, 2) < 0) {
                                $entryCap = $availableForRow;
                            }
                            $siblingSupported = false;
                            if (! $belowThreshold && $row->pib !== null) {
                                foreach ($outcomeRows as $otherRow) {
                                    if ($otherRow->id() === $row->id() || $otherRow->pib !== $row->pib) {
                                        continue;
                                    }
                                    if ($outcomeRecords->get($otherRow->id())?->isSupport()) {
                                        $siblingSupported = true;
                                        break;
                                    }
                                }
                            }
                            $decision = $belowThreshold ? 'reject' : $outcome?->decision;
                            $showAmount = $decision === 'support';
                            $showReason = $decision === 'reject';
                            $reasonValue = $belowThreshold
                                ? \App\Models\CanonicalOutcome::REASON_BELOW_30
                                : ($outcome?->rejection_reason ?: ($siblingSupported ? \App\Models\CanonicalOutcome::REASON_DUPLICATE_BENEFICIARY_SUPPORT : ''));
                        @endphp
                        <div class="mmsp-ranking-decision-block" data-testid="mmsp-decision-card">
                            <div class="no-print" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 16px; border-bottom: 2px solid #e5e7eb; gap: 12px; flex-wrap: wrap;">
                                <div>
                                    <h3 style="font-size: 18px; font-weight: 700; color: #0B3D91; margin: 0 0 4px 0;">{{ $mmspApplicantName($row) }}</h3>
                                    <p style="font-size: 14px; color: #6b7280; margin: 0;">
                                        Vrsta subvencije: {{ $row->subsidyType && \App\Support\CanonicalSubsidyType::isValid($row->subsidyType) ? \App\Support\CanonicalSubsidyType::label($row->subsidyType) : '—' }} |
                                        Pozicija: <strong>#{{ $row->rank }}</strong> |
                                        Ocjena: <strong>{{ $mmspScoreText($row) }}</strong>
                                    </p>
                                </div>
                                @if($outcome)
                                    <span style="display: inline-block; padding: 6px 12px; background: #d1fae5; color: #065f46; border-radius: 6px; font-size: 12px; font-weight: 600;">✓ Zaključeno</span>
                                @endif
                            </div>
                            @if($belowThreshold)
                                <p class="mmsp-ranking-note" style="margin: 0 0 12px;">Ispod praga od 30 bodova. Ne može se podržati.</p>
                            @endif
                            @if($siblingSupported)
                                <p data-testid="mmsp-duplicate-pib" style="margin: 0 0 12px; color: #92400e; font-size: 14px; font-weight: 600;">{{ \App\Services\Competitions\Outcome\CanonicalAllocationPolicy::DUPLICATE_REASON_REQUIRED_MESSAGE }}</p>
                            @endif
                            <form method="POST" action="{{ route('evaluation.canonical-outcome.draft', [$competition, $row->application]) }}" class="mmsp-ranking-decision-form no-print" data-mmsp-decision-form data-below-threshold="{{ $belowThreshold ? '1' : '0' }}" data-sibling-supported="{{ $siblingSupported ? '1' : '0' }}" data-cap-message="{{ \App\Services\Competitions\Outcome\CanonicalAmountCapPolicy::AMOUNT_ABOVE_CAP_MESSAGE }}" data-budget-message="{{ \App\Services\Competitions\Outcome\CanonicalAllocationPolicy::BUDGET_EXCEEDED_MESSAGE }}">
                                @csrf
                                <div style="margin-bottom: 24px;">
                                    <span style="display: block; font-size: 15px; font-weight: 600; color: #374151; margin-bottom: 12px;">Zaključak komisije: *</span>
                                    <div class="mmsp-decision-choices">
                                        <label class="mmsp-decision-choice">
                                            <input type="radio" name="decision" value="support" data-mmsp-decision{{ $decision === 'support' ? ' checked' : '' }}{{ $belowThreshold ? ' disabled' : ' required' }}>
                                            <span style="font-size: 14px; color: #374151; font-weight: 500;">Podržava</span>
                                        </label>
                                        <label class="mmsp-decision-choice">
                                            <input type="radio" name="decision" value="reject" data-mmsp-decision{{ $decision === 'reject' ? ' checked' : '' }}{{ $belowThreshold ? '' : ' required' }}>
                                            <span style="font-size: 14px; color: #374151; font-weight: 500;">Odbija</span>
                                        </label>
                                    </div>
                                </div>
                                <div class="mmsp-decision-amount-grid" data-testid="mmsp-ranking-financials">
                                    <div>
                                        <span style="display: block; font-size: 15px; font-weight: 600; color: #374151; margin-bottom: 8px;">Ukupna vrijednost ulaganja</span>
                                        <div class="mmsp-decision-fact">{{ $mmspMoney($row->totalInvestment) }}</div>
                                    </div>
                                    <div>
                                        <span style="display: block; font-size: 15px; font-weight: 600; color: #374151; margin-bottom: 8px;">Tražena subvencija</span>
                                        <div class="mmsp-decision-fact">{{ $mmspMoney($requestedSubsidy) }}</div>
                                    </div>
                                    <div>
                                        <span style="display: block; font-size: 15px; font-weight: 600; color: #374151; margin-bottom: 8px;">Maksimalni dozvoljeni iznos</span>
                                        <div class="mmsp-decision-fact">{{ $mmspMoney($row->legalMaximum) }}</div>
                                    </div>
                                    <div>
                                        <span style="display: block; font-size: 15px; font-weight: 600; color: #374151; margin-bottom: 8px;">Raspoloživa sredstva za ovu prijavu</span>
                                        <div class="mmsp-decision-fact" data-testid="mmsp-available-for-row">{{ $mmspMoney($availableForRow) }}</div>
                                    </div>
                                    <label class="mmsp-decision-field" data-mmsp-amount {{ $showAmount ? '' : 'hidden' }}>
                                        <span style="display: block; font-size: 15px; font-weight: 600; color: #374151; margin-bottom: 8px;">Iznos odobrenih sredstava</span>
                                        <input type="text" name="amount" inputmode="decimal" placeholder="Iznos €" value="{{ $showAmount ? $outcome?->approved_amount : '' }}" data-mmsp-amount-input data-legal-maximum="{{ $row->legalMaximum }}" data-available="{{ $availableForRow }}" @disabled(! $showAmount)>
                                    </label>
                                    <label class="mmsp-decision-field" data-mmsp-reason {{ $showReason ? '' : 'hidden' }}>
                                        <span style="display: block; font-size: 15px; font-weight: 600; color: #374151; margin-bottom: 8px;">Razlog odbijanja</span>
                                        @if($belowThreshold)
                                            <input type="hidden" name="reason" value="{{ \App\Models\CanonicalOutcome::REASON_BELOW_30 }}">
                                        @endif
                                        <select @if(! $belowThreshold) name="reason" @endif data-mmsp-reason-select @disabled($belowThreshold || ! $showReason)>
                                            <option value="">— razlog odbijanja —</option>
                                            @foreach($reasonLabels as $code => $label)
                                                <option value="{{ $code }}" @selected($reasonValue === $code)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </label>
                                </div>
                                <label style="display: block; margin-bottom: 24px;">
                                    <span style="display: block; font-size: 15px; font-weight: 600; color: #374151; margin-bottom: 8px;">Obrazloženje</span>
                                    <textarea name="justification" rows="6" placeholder="Obrazloženje">{{ $outcome?->justification }}</textarea>
                                </label>
                                <label style="display: block; margin-bottom: 24px;">
                                    <span style="display: block; font-size: 15px; font-weight: 600; color: #374151; margin-bottom: 8px;">Napomene i mišljenja</span>
                                    <textarea name="remarks" rows="3" placeholder="Napomene i mišljenja">{{ $outcome?->remarks }}</textarea>
                                </label>
                                <div style="text-align: right;">
                                    <button type="submit" class="btn-primary" style="padding: 10px 24px; font-size: 14px; min-width: 160px;">Sačuvaj zaključak</button>
                                </div>
                            </form>
                        </div>
                    @endforeach
                </div>
                <script>
                    document.querySelectorAll('[data-mmsp-decision-form]').forEach(function (form) {
                        var sync = function () {
                            var selected = form.querySelector('[data-mmsp-decision]:checked');
                            var decision = selected ? selected.value : '';
                            var below = form.dataset.belowThreshold === '1';
                            var amountWrap = form.querySelector('[data-mmsp-amount]');
                            var reasonWrap = form.querySelector('[data-mmsp-reason]');
                            var amount = form.querySelector('[data-mmsp-amount-input]');
                            var reason = form.querySelector('[data-mmsp-reason-select]');
                            var showAmount = !below && decision === 'support';
                            var showReason = below || decision === 'reject';
                            if (amountWrap) amountWrap.hidden = !showAmount;
                            if (amount) {
                                amount.disabled = !showAmount;
                                if (!showAmount) amount.value = '';
                            }
                            if (reasonWrap) reasonWrap.hidden = !showReason;
                            if (reason && !below) {
                                reason.disabled = !showReason;
                                if (showReason && reason.value === '' && form.dataset.siblingSupported === '1') {
                                    reason.value = 'duplicate_beneficiary_support';
                                }
                            }
                        };
                        form.querySelectorAll('[data-mmsp-decision]').forEach(function (input) {
                            input.addEventListener('change', sync);
                        });
                        var amount = form.querySelector('[data-mmsp-amount-input]');
                        if (amount) {
                            amount.addEventListener('input', function () {
                                var raw = amount.value.trim().replace(',', '.');
                                var legal = amount.dataset.legalMaximum;
                                var available = amount.dataset.available;
                                if (raw === '' || Number.isNaN(Number(raw))) {
                                    amount.setCustomValidity('');
                                    return;
                                }
                                if (legal !== '' && Number(raw) > Number(legal)) {
                                    amount.setCustomValidity(form.dataset.capMessage);
                                    return;
                                }
                                if (available !== '' && Number(raw) > Number(available)) {
                                    amount.setCustomValidity(form.dataset.budgetMessage);
                                    return;
                                }
                                amount.setCustomValidity('');
                            });
                        }
                        form.addEventListener('submit', function (event) {
                            if (amount && !amount.disabled && !amount.checkValidity()) {
                                event.preventDefault();
                                amount.reportValidity();
                            }
                        });
                        sync();
                    });
                </script>
                @endif

                @if($canonicalRankingIsFinal)
                <div class="mmsp-ranking-conclusions" data-testid="mmsp-ranking-recorded-conclusions">
                    <h2>Zaključak komisije i obrazloženje</h2>
                    @foreach($outcomeRows as $row)
                        @php $outcome = $outcomeRecords->get($row->id()); @endphp
                        @continue($outcome === null)
                        <div class="mmsp-ranking-conclusion">
                            <table class="mmsp-conclusion-table">
                                <tbody>
                                    <tr class="mmsp-conclusion-name">
                                        <th colspan="2">{{ $mmspApplicantName($row) }}</th>
                                    </tr>
                                    <tr>
                                        <th>Vrsta subvencije</th>
                                        <td>{{ $row->subsidyType && \App\Support\CanonicalSubsidyType::isValid($row->subsidyType) ? \App\Support\CanonicalSubsidyType::label($row->subsidyType) : '—' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Pozicija</th>
                                        <td>#{{ $row->rank }}</td>
                                    </tr>
                                    <tr>
                                        <th>Ocjena</th>
                                        <td>{{ $mmspScoreText($row) }}</td>
                                    </tr>
                                    <tr>
                                        <th>Zaključak</th>
                                        <td>{{ $outcome->isSupport() ? 'Podržava' : 'Odbija' }}</td>
                                    </tr>
                                    @if($mmspReasonText($outcome))
                                        <tr>
                                            <th>Razlog odbijanja</th>
                                            <td>{{ $mmspReasonText($outcome) }}</td>
                                        </tr>
                                    @endif
                                    <tr>
                                        <th>Iznos odobrenih sredstava</th>
                                        <td>{{ $outcome->isSupport() ? $mmspMoneyPlain($outcome->approved_amount) : '—' }}</td>
                                    </tr>
                                    @if(filled($outcome->justification))
                                        <tr>
                                            <th>Obrazloženje</th>
                                            <td style="white-space: pre-wrap;">{{ $outcome->justification }}</td>
                                        </tr>
                                    @endif
                                    @if(filled($outcome->remarks))
                                        <tr>
                                            <th>Napomene i mišljenja</th>
                                            <td style="white-space: pre-wrap;">{{ $outcome->remarks }}</td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    @endforeach
                </div>
                @endif
            </div>

            @if($canonicalRankingIsFinal)
                <div class="info-card mmsp-ranking-signatures" id="signature-block" data-testid="mmsp-ranking-signatures" style="padding: 16px 24px;">
                    <h2>Potpis komisije</h2>
                    <div style="margin-top: 16px; font-size: 13px; line-height: 1.5;">
                        <p style="margin-bottom: 14px;">Kotor, _______________ god.</p>
                        @php $clanNum = 0; @endphp
                        @foreach($signatories as $member)
                            <div style="margin-bottom: 6px;">
                                <div style="font-weight: 600; color: #374151;">{{ $member->position === 'predsjednik' ? 'Predsjednik Komisije' : 'Član '.(++$clanNum) }}</div>
                                <div style="margin: 2px 0 0 0;">{{ $member->name }}</div>
                                <div style="width: 200px; border-bottom: 1px solid #111; min-height: 1px; margin-top: 8px;"></div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="mmsp-ranking-no-print no-print" style="margin: 24px 0 8px 0; text-align: center;">
                    <button type="button" onclick="printMmspRanking(this);" class="mmsp-ranking-print-btn" data-testid="mmsp-ranking-print">Štampaj rang-listu</button>
                </div>
                @if($canonicalChair)
                    <div class="no-print" style="margin: 24px 0 8px 0; text-align: center;">
                        <a href="{{ route('evaluation.mmsp-decision', $competition) }}" data-testid="mmsp-decision-open" class="btn btn-primary" style="display: inline-block; background: #0B3D91; color: #fff; border: 0; border-radius: 8px; padding: 10px 20px; font-weight: 600; text-decoration: none;">Generiši Word odluku</a>
                    </div>
                @endif
            @endif
        </div>
    @endif

    <div class="no-print" style="margin: 24px 0 8px 0; text-align: center;">
        <a href="{{ route('evaluation.index') }}" class="btn btn-primary" style="display: inline-block; margin: 0 8px; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 600; background: #0B3D91; color: #fff;">Nazad</a>
    </div>
</div>
</div>
@endsection
