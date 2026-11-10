@extends('layouts.app')

@section('content')
@include('partials.obrazac-form-styles')
<style>
    /*
     * Rang lista — page-specific chrome on top of shared M1b / obrazac-form-page styles.
     * Ranking data structure stays; visual tokens match Obrazac M1b.
     */
    .ranking-page.obrazac-form-page > .container {
        width: 100%;
        max-width: 80rem;
    }
    @media (max-width: 767px) {
        .ranking-page.obrazac-form-page > .container {
            max-width: 100%;
        }
    }
    .ranking-page .info-card {
        background: var(--obrazac-surface, #fff);
        border-radius: var(--obrazac-radius-card, 16px);
        padding: 24px;
        margin-bottom: 24px;
        box-shadow: var(--obrazac-shadow-card, 0 1px 3px rgba(0,0,0,0.1));
    }
    .ranking-page .info-card h2 {
        font-size: 20px;
        font-weight: 700;
        color: var(--primary);
        margin: 0 0 20px;
        padding-bottom: 12px;
        border-bottom: 2px solid var(--obrazac-border, #e5e7eb);
    }
    .ranking-page .budget-info {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
        margin-bottom: 24px;
    }
    .ranking-page .budget-item {
        padding: 16px;
        background: var(--obrazac-page-bg, #f9fafb);
        border-radius: var(--obrazac-radius-control, 8px);
        text-align: center;
    }
    .ranking-page .budget-label {
        font-size: 12px;
        color: var(--obrazac-muted, #6b7280);
        margin-bottom: 8px;
    }
    .ranking-page .budget-value {
        font-size: 24px;
        font-weight: 700;
        color: var(--primary);
    }
    .ranking-page .ranking-table,
    .ranking-page .info-card > table,
    .ranking-page .below-minimum-section table {
        width: 100%;
        border-collapse: collapse;
        margin: 16px 0;
    }
    .ranking-page .ranking-table th,
    .ranking-page .ranking-table td,
    .ranking-page .info-card > table th,
    .ranking-page .info-card > table td,
    .ranking-page .below-minimum-section table th,
    .ranking-page .below-minimum-section table td {
        padding: 12px;
        text-align: left;
        border: 1px solid var(--obrazac-border, #e5e7eb);
        vertical-align: top;
        color: var(--obrazac-text, #111);
    }
    .ranking-page .ranking-table th,
    .ranking-page .info-card > table th,
    .ranking-page .below-minimum-section table th {
        font-weight: 600;
        color: var(--obrazac-label, #374151);
        font-size: 12px;
        text-transform: uppercase;
        background: var(--obrazac-page-bg, #f9fafb);
    }
    .ranking-page .ranking-badge {
        display: inline-block;
        width: 32px;
        height: 32px;
        background: var(--primary);
        color: #fff;
        border-radius: 50%;
        text-align: center;
        line-height: 32px;
        font-weight: 700;
        font-size: 14px;
    }
    .ranking-page .score-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
    }
    .ranking-page .score-high { background: #d1fae5; color: #065f46; }
    .ranking-page .score-medium { background: #fef3c7; color: #92400e; }
    .ranking-page .score-low { background: #fee2e2; color: #991b1b; }
    .ranking-page .checkbox-cell { text-align: center; }
    .ranking-page .btn {
        padding: 10px 20px;
        border-radius: var(--obrazac-radius-control, 8px);
        text-decoration: none;
        font-weight: 600;
        margin-left: 8px;
        border: none;
        cursor: pointer;
        display: inline-block;
    }
    .ranking-page .btn-success {
        background: #10b981;
        color: #fff;
    }
    .ranking-page .status-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
    }
    .ranking-page .status-submitted { background: #dbeafe; color: #1e40af; }
    .ranking-page .status-evaluated { background: #e0e7ff; color: #3730a3; }
    .ranking-page .status-approved { background: #d1fae5; color: #065f46; }
    .ranking-page .status-rejected { background: #fee2e2; color: #991b1b; }
    .ranking-page .commission-decision-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
    }
    .ranking-page .commission-decision-table th,
    .ranking-page .commission-decision-table td {
        padding: 10px 12px;
        border: 1px solid var(--obrazac-border, #e5e7eb);
        text-align: left;
        vertical-align: top;
    }
    .ranking-page .commission-decision-table th {
        width: 28%;
        font-weight: 600;
        background: var(--obrazac-page-bg, #f9fafb);
        color: var(--obrazac-label, #374151);
        font-size: 13px;
    }
    .ranking-page .commission-decision-table td {
        color: var(--obrazac-label, #374151);
        font-size: 14px;
    }
    .ranking-page .commission-decision-table .commission-decision-name th {
        width: auto;
        background: #fff;
        font-size: 16px;
        font-weight: 700;
        color: var(--primary);
    }
    .ranking-page .commission-decision-block-saved {
        background: transparent !important;
        border: none !important;
        border-radius: 0 !important;
        padding: 0 !important;
    }
    .ranking-page .print-only { display: none; }
    .ranking-page .below-minimum-section {
        margin-top: 32px;
        padding-top: 20px;
        border-top: 2px solid var(--obrazac-border, #e5e7eb);
    }
    .ranking-page .below-minimum-heading {
        margin: 0 0 12px;
        padding: 0;
        font-size: 16px;
        font-weight: 700;
        color: var(--primary);
        text-align: left;
        background: transparent;
        border: none;
        border-radius: 0;
        box-shadow: none;
    }
    .ranking-page .below-minimum-section > h3 {
        font-size: 14px;
        font-weight: 600;
        color: var(--obrazac-muted, #6b7280);
        margin: 0 0 16px;
    }
    @media print {
        @page {
            size: A4;
            margin: 12mm 10mm;
        }
        nav,
        header,
        .ranking-page button,
        .ranking-page a[href],
        .ranking-page .no-print {
            display: none !important;
        }
        .ranking-page.obrazac-form-page {
            background: #fff;
            padding: 0;
            min-height: 0;
        }
        .ranking-page.obrazac-form-page > .container {
            max-width: 100% !important;
            width: 100% !important;
            padding: 0 !important;
        }
        .ranking-page .info-card {
            box-shadow: none;
            border: none;
            border-radius: 0;
            padding: 0;
            margin-bottom: 10px;
        }
        .ranking-page .info-card h2 {
            font-size: 11pt;
            border-bottom: 1px solid var(--obrazac-border, #e5e7eb);
            padding-bottom: 4px;
            margin-bottom: 8px;
            color: var(--primary);
        }
        .ranking-page .ranking-table,
        .ranking-page .info-card > table,
        .ranking-page .below-minimum-section table,
        .ranking-page .commission-decision-table {
            width: 100%;
            border-collapse: collapse;
            page-break-inside: auto;
            break-inside: auto;
            font-size: 8.5pt;
            margin: 4px 0;
        }
        .ranking-page table thead { display: table-header-group; }
        .ranking-page table tr {
            page-break-inside: avoid;
            break-inside: avoid;
        }
        .ranking-page th,
        .ranking-page td {
            border: 1px solid var(--obrazac-border, #e5e7eb) !important;
            padding: 3px 5px !important;
            font-size: 8.5pt !important;
            background: transparent !important;
            color: var(--obrazac-text, #111) !important;
        }
        .ranking-page th {
            font-weight: 600 !important;
            color: var(--obrazac-label, #374151) !important;
            background: var(--obrazac-page-bg, #f9fafb) !important;
        }
        .ranking-page tbody tr { background: transparent !important; }
        .ranking-page .print-only { display: table !important; }
        .ranking-page .commission-decision-table {
            page-break-inside: avoid;
            break-inside: avoid;
            margin-bottom: 10px !important;
        }
        .ranking-page .commission-decision-table th { width: 28%; }
        .ranking-page .commission-decision-table .commission-decision-name th {
            font-size: 9pt !important;
            color: var(--primary) !important;
        }
        .ranking-page .ranking-badge {
            background: transparent !important;
            color: var(--primary) !important;
            border: 1px solid var(--primary);
            border-radius: 0;
            width: auto;
            height: auto;
            line-height: 1.2;
            padding: 0 5px;
            font-size: 8.5pt;
        }
        .ranking-page .score-badge {
            background: transparent !important;
            color: var(--obrazac-text, #111) !important;
            border: none;
            padding: 0;
            font-size: 8.5pt;
        }
        .ranking-page .below-minimum-section {
            margin-top: 12px !important;
            padding-top: 0 !important;
            border-top: none !important;
            box-shadow: none !important;
        }
        .ranking-page .below-minimum-section > div:first-child,
        .ranking-page .below-minimum-heading {
            background: transparent !important;
            color: var(--obrazac-text, #111) !important;
            border: none !important;
            padding: 0 0 6px 0 !important;
            margin: 0 0 6px 0 !important;
            font-size: 9pt !important;
            border-radius: 0 !important;
            box-shadow: none !important;
        }
        .ranking-page .below-minimum-section h3 {
            font-size: 9pt !important;
            color: var(--obrazac-label, #374151) !important;
            margin-bottom: 6px !important;
        }
        .ranking-page .commission-decision-section > p { display: none !important; }
        .ranking-page .commission-decision-block,
        .ranking-page .commission-decision-block-saved {
            background: transparent !important;
            border: none !important;
            padding: 0 !important;
            margin-bottom: 10px !important;
        }
        .ranking-page .commission-decision-block h3 {
            font-size: 10pt !important;
            color: var(--primary) !important;
        }
        .ranking-page .commission-decision-block p,
        .ranking-page .commission-decision-block div {
            font-size: 9pt !important;
            color: var(--obrazac-text, #111) !important;
        }
        .ranking-page .commission-decision-block > .commission-decision-header,
        .ranking-page .commission-decision-block > div:first-child {
            border-bottom: none !important;
            padding-bottom: 4px !important;
            margin-bottom: 6px !important;
        }
        .ranking-page .commission-decision-block span[style*="background"],
        .ranking-page .commission-decision-content,
        .ranking-page .commission-justification-box {
            background: transparent !important;
            color: var(--obrazac-text, #111) !important;
            border: none !important;
            border-radius: 0 !important;
            padding: 0 !important;
            box-shadow: none !important;
        }
        .ranking-page .ranking-main-content {
            page-break-after: always;
            break-after: page;
        }
        .ranking-page #signature-block {
            page-break-before: always;
            break-before: page;
            padding: 0 !important;
        }
        .ranking-page #signature-block h2 {
            font-size: 11pt;
            margin-bottom: 8px;
            color: var(--primary);
        }
        .ranking-page #signature-block p,
        .ranking-page #signature-block div {
            font-size: 9pt;
            color: var(--obrazac-text, #111);
        }
    }
</style>

<div class="admin-page ranking-page obrazac-form-page">
    <div class="container mx-auto px-4">

        @php
            $daysRemaining = $competition->getDaysUntilEvaluationDeadline();
            $isDeadlinePassed = \App\Support\ScoringProfileConfig::for($competition->type)->appliesEvaluationDeadline
                && $competition->isEvaluationDeadlinePassed();
        @endphp
                
        @if($isDeadlinePassed)
            <div class="alert alert-danger no-print" style="background: #fee2e2; border: 1px solid #ef4444; color: #991b1b; padding: 16px 20px; border-radius: 12px; margin-bottom: 24px;">
                <strong>❌ Rok istekao:</strong> Rok za ocjenjivanje i donošenje odluke je istekao. Komisija je dužna donijeti odluku u roku od 45 dana od dana zatvaranja prijava na konkurs.
            </div>
        @endif

        @if(session('success'))
            <div class="alert alert-success no-print">
                {{ session('success') }}
            </div>
        @endif

        @include('partials.obrazac-zaglavlje', [
            'leftMeta' => 'Konkurs: '.($competition->upNumber?->number ?? '—').' / '.($competition->year ?? ''),
            'obrazacLabel' => '',
            'obrazacTitle' => 'RANG LISTA BIZNIS PLANOVA',
            'podnaslov' => e($competition->title),
        ])

        <div class="ranking-main-content">
        <!-- Rang lista -->
        <div class="info-card">
            @if($applications->count() > 0 || (isset($belowLineApplications) && $belowLineApplications->count() > 0))
            @if($applications->count() > 0)
            @if((isset($isSuperAdmin) && $isSuperAdmin) || (isset($isChairman) && $isChairman))
                @if(in_array($competition->status, ['closed', 'completed']))
                    {{-- Read-only prikaz za završene konkurse --}}
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 60px;">Poz.</th>
                                <th>Naziv biznis plana</th>
                                <th>Podnosilac</th>
                                <th>Tip</th>
                                <th style="text-align: center;">Ocjena</th>
                                <th style="text-align: right;">Traženi iznos</th>
                                <th style="text-align: right;">Odobreni iznos</th>
                                <th style="text-align: center;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($applications as $application)
                                <tr style="{{ $application->status === 'approved' ? 'background: #d1fae5;' : '' }}">
                                    <td>
                                        <span class="ranking-badge">{{ $application->ranking_position }}</span>
                                    </td>
                                    <td>{{ $application->business_plan_name }}</td>
                                    <td>{{ $application->user->name ?? 'N/A' }}</td>
                                    <td>
                                        {{ $application->applicant_type === 'preduzetnica' ? 'Preduzetnica' : ($application->applicant_type === 'doo' ? 'DOO' : ($application->applicant_type === 'fizicko_lice' ? 'Fizičko lice' : 'Ostalo')) }} - 
                                        {{ $application->business_stage === 'započinjanje' ? 'Započinjanje' : 'Razvoj' }}
                                    </td>
                                    <td style="text-align: center;">
                                        @php
                                            $score = $application->getDisplayScore();
                                            $scoreClass = $score >= 40 ? 'score-high' : ($score >= 30 ? 'score-medium' : 'score-low');
                                        @endphp
                                        <span class="score-badge {{ $scoreClass }}">
                                            {{ number_format($score, 2) }} / 58
                                        </span>
                                    </td>
                                    <td style="text-align: right;">
                                        {{ number_format($application->requested_amount, 2, ',', '.') }} €
                                    </td>
                                    <td style="text-align: right;">
                                        @if($application->approved_amount)
                                            <strong>
                                                {{ number_format($application->approved_amount, 2, ',', '.') }} €
                                            </strong>
                                        @else
                                            <span style="color: #6b7280;">-</span>
                                        @endif
                                    </td>
                                    <td class="checkbox-cell">
                                        @if($application->status === 'approved')
                                            <span style="font-weight: 600;">✓ Dobitnik sredstava</span>
                                        @else
                                            <span style="color: #6b7280;">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    {{-- Read-only prikaz za aktivne konkurse - odabir dobitnika se vrši u sekciji Zaključak komisije --}}
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 60px;">Poz.</th>
                                <th>Naziv biznis plana</th>
                                <th>Podnosilac</th>
                                <th>Tip</th>
                                <th style="text-align: center;">Ocjena</th>
                                <th style="text-align: right;">Traženi iznos</th>
                                <th style="text-align: right;">Odobreni iznos</th>
                                <th style="text-align: center;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($applications as $application)
                                <tr style="{{ $application->status === 'approved' ? 'background: #d1fae5;' : '' }}">
                                    <td>
                                        <span class="ranking-badge">{{ $application->ranking_position }}</span>
                                    </td>
                                    <td>{{ $application->business_plan_name }}</td>
                                    <td>{{ $application->user->name ?? 'N/A' }}</td>
                                    <td>
                                        {{ $application->applicant_type === 'preduzetnica' ? 'Preduzetnica' : ($application->applicant_type === 'doo' ? 'DOO' : ($application->applicant_type === 'fizicko_lice' ? 'Fizičko lice' : 'Ostalo')) }} - 
                                        {{ $application->business_stage === 'započinjanje' ? 'Započinjanje' : 'Razvoj' }}
                                    </td>
                                    <td style="text-align: center;">
                                        @php
                                            $score = $application->getDisplayScore();
                                            $scoreClass = $score >= 40 ? 'score-high' : ($score >= 30 ? 'score-medium' : 'score-low');
                                        @endphp
                                        <span class="score-badge {{ $scoreClass }}">
                                            {{ number_format($score, 2) }} / 58
                                        </span>
                                    </td>
                                    <td style="text-align: right;">
                                        {{ number_format($application->requested_amount, 2, ',', '.') }} €
                                    </td>
                                    <td style="text-align: right;">
                                        @if($application->approved_amount)
                                            <strong>
                                                {{ number_format($application->approved_amount, 2, ',', '.') }} €
                                            </strong>
                                        @else
                                            <span style="color: #6b7280;">-</span>
                                        @endif
                                    </td>
                                    <td class="checkbox-cell">
                                        @if($application->status === 'approved')
                                            <span style="font-weight: 600;">✓ Dobitnik sredstava</span>
                                        @else
                                            <span style="color: #6b7280;">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            @else
                {{-- Read-only prikaz za članove komisije --}}
                <table>
                    <thead>
                        <tr>
                            <th style="width: 60px;">Poz.</th>
                            <th>Naziv biznis plana</th>
                            <th>Podnosilac</th>
                            <th>Tip</th>
                            <th style="text-align: center;">Ocjena</th>
                            <th style="text-align: right;">Traženi iznos</th>
                            <th style="text-align: right;">Odobreni iznos</th>
                            <th style="text-align: center;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($applications as $application)
                            <tr style="{{ $application->status === 'approved' ? 'background: #d1fae5;' : '' }}">
                                <td>
                                    <span class="ranking-badge">{{ $application->ranking_position }}</span>
                                </td>
                                <td>{{ $application->business_plan_name }}</td>
                                <td>{{ $application->user->name ?? 'N/A' }}</td>
                                <td>
                                    {{ $application->applicant_type === 'preduzetnica' ? 'Preduzetnica' : ($application->applicant_type === 'doo' ? 'DOO' : ($application->applicant_type === 'fizicko_lice' ? 'Fizičko lice' : 'Ostalo')) }} - 
                                    {{ $application->business_stage === 'započinjanje' ? 'Započinjanje' : 'Razvoj' }}
                                </td>
                                <td style="text-align: center;">
                                    @php
                                        $score = $application->getDisplayScore();
                                        $scoreClass = $score >= 40 ? 'score-high' : ($score >= 30 ? 'score-medium' : 'score-low');
                                    @endphp
                                    <span class="score-badge {{ $scoreClass }}">
                                        {{ number_format($score, 2) }} / 58
                                    </span>
                                </td>
                                <td style="text-align: right;">
                                    {{ number_format($application->requested_amount, 2, ',', '.') }} €
                                </td>
                                <td style="text-align: right;">
                                    @if($application->approved_amount)
                                        <strong>
                                            {{ number_format($application->approved_amount, 2, ',', '.') }} €
                                        </strong>
                                    @else
                                        <span style="color: #6b7280;">-</span>
                                    @endif
                                </td>
                                <td class="checkbox-cell">
                                    @if($application->status === 'approved')
                                        <span style="font-weight: 600;">✓ Dobitnik sredstava</span>
                                    @else
                                        <span style="color: #6b7280;">-</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
            @endif
            @if(isset($belowLineApplications) && $belowLineApplications->count() > 0)
                <div class="below-minimum-section">
                    <div class="below-minimum-heading">
                        Prijave ispod minimuma (30 bodova)
                    </div>
                    <h3>Prijave sa manje od 30 bodova</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Naziv biznis plana</th>
                                <th>Podnosilac</th>
                                <th>Tip</th>
                                <th style="text-align: center;">Ocjena</th>
                                <th style="text-align: right;">Traženi iznos</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($belowLineApplications as $application)
                                <tr style="background: #f9fafb;">
                                    <td>{{ $application->business_plan_name }}</td>
                                    <td>{{ $application->user->name ?? 'N/A' }}</td>
                                    <td>
                                        {{ $application->applicant_type === 'preduzetnica' ? 'Preduzetnica' : ($application->applicant_type === 'doo' ? 'DOO' : ($application->applicant_type === 'fizicko_lice' ? 'Fizičko lice' : 'Ostalo')) }} -
                                        {{ $application->business_stage === 'započinjanje' ? 'Započinjanje' : 'Razvoj' }}
                                    </td>
                                    <td style="text-align: center;">
                                        <span class="score-badge score-low">
                                            {{ number_format($application->getDisplayScore(), 2) }} / 58
                                        </span>
                                    </td>
                                    <td style="text-align: right;">
                                        {{ number_format($application->requested_amount, 2, ',', '.') }} €
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
            @else
                <div style="text-align: center; padding: 40px; color: #6b7280;">
                    <p style="margin-bottom: 16px; font-size: 16px; font-weight: 600;">Nema ocijenjenih prijava za ovaj konkurs.</p>
                    <p style="margin-bottom: 8px; font-size: 14px;">Prijave odbijene zbog nedostatka dokumenata ne prikazuju se u rang listi.</p>
                </div>
            @endif
        </div>

        <!-- Sekcije 4 i 5: Zaključak komisije i Obrazloženje -->
        @if(
            (isset($isSuperAdmin) && $isSuperAdmin)
            || (isset($isChairman) && $isChairman)
            || (
                in_array($competition->status, ['closed', 'completed'])
                && (
                    (isset($isCompetitionAdmin) && $isCompetitionAdmin)
                    || (isset($isCommissionMember) && $isCommissionMember)
                )
            )
        )
                @if($applications->count() > 0)
                    <div class="info-card commission-decision-section">
                        <h2>Zaključak komisije i obrazloženje</h2>
                        @if(!in_array($competition->status, ['closed', 'completed']))
                            <p class="no-print" style="color: #6b7280; margin-bottom: 24px; font-size: 14px;">
                                Za svaku prijavu u rang listi unesite zaključak komisije i obrazloženje.
                            </p>
                        @endif

                        @foreach($applications as $application)
                            @php
                                $zenskoDecisionLocked = $competition->type === 'zensko'
                                    && $application->commission_decision_locked_at !== null;
                            @endphp
                            <div class="commission-decision-block{{ $application->signed_by_chairman ? ' commission-decision-block-saved' : '' }}" style="background: #f9fafb; padding: 24px; border-radius: 12px; margin-bottom: 24px; border: 1px solid #e5e7eb;">
                                @if(!in_array($competition->status, ['closed', 'completed']))
                                <div class="commission-decision-header no-print" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 16px; border-bottom: 2px solid #e5e7eb;">
                                    <div>
                                        <h3 style="font-size: 18px; font-weight: 700; color: var(--primary); margin: 0 0 4px 0;">
                                            {{ $application->business_plan_name }}
                                        </h3>
                                        <p style="font-size: 14px; color: #6b7280; margin: 0;">
                                            Podnosilac: {{ $application->user->name ?? 'N/A' }} |
                                            Pozicija: <strong>#{{ $application->ranking_position }}</strong> |
                                            Ocjena: <strong>{{ number_format($application->getDisplayScore(), 2) }} / 58</strong>
                                        </p>
                                    </div>
                                    @if($application->commission_decision)
                                        <span style="display: inline-block; padding: 6px 12px; background: #d1fae5; color: #065f46; border-radius: 6px; font-size: 12px; font-weight: 600;">
                                            ✓ Zaključeno
                                        </span>
                                    @endif
                                </div>

                                    @if($zenskoDecisionLocked)
                                        <p class="no-print" style="margin: 0 0 16px 0; color: #92400e; font-size: 14px;">
                                            {{ \App\Services\Competitions\ZenskoCommissionDecisionWriter::DECISION_LOCKED_MESSAGE }}
                                        </p>
                                    @else
                                    <form class="no-print" method="POST" action="{{ route('evaluation.store-decision', $application) }}">
                                        @csrf
                                        
                                        <div style="margin-bottom: 24px;">
                                            <label style="display: block; font-size: 15px; font-weight: 600; color: #374151; margin-bottom: 12px;">
                                                Zaključak komisije: *
                                            </label>
                                            <div style="display: flex; gap: 32px; flex-wrap: wrap;">
                                                <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; padding: 12px; background: #fff; border-radius: 8px; border: 2px solid #e5e7eb; transition: all 0.2s;">
                                                    <input type="radio" name="commission_decision" value="podrzava_potpuno" {{ old('commission_decision', $application->commission_decision) === 'podrzava_potpuno' ? 'checked' : '' }} required onchange="this.parentElement.style.borderColor = this.checked ? 'var(--primary)' : '#e5e7eb'; this.parentElement.style.background = this.checked ? '#eff6ff' : '#fff';">
                                                    <span style="font-size: 14px; color: #374151; font-weight: 500;">a. Podržava se</span>
                                                </label>
                                                <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; padding: 12px; background: #fff; border-radius: 8px; border: 2px solid #e5e7eb; transition: all 0.2s;">
                                                    <input type="radio" name="commission_decision" value="odbija" {{ old('commission_decision', $application->commission_decision) === 'odbija' ? 'checked' : '' }} required onchange="this.parentElement.style.borderColor = this.checked ? 'var(--primary)' : '#e5e7eb'; this.parentElement.style.background = this.checked ? '#eff6ff' : '#fff';">
                                                    <span style="font-size: 14px; color: #374151; font-weight: 500;">b. Odbija</span>
                                                </label>
                                            </div>
                                            @error('commission_decision')
                                                <div style="color: #ef4444; font-size: 12px; margin-top: 8px;">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px;">
                                            <div>
                                                <label style="display: block; font-size: 15px; font-weight: 600; color: #374151; margin-bottom: 8px;">
                                                    Iznos odobrenih sredstava:
                                                </label>
                                                <input 
                                                    type="number" 
                                                    name="approved_amount" 
                                                    class="form-control" 
                                                    value="{{ old('approved_amount', $application->approved_amount) }}" 
                                                    step="0.01" 
                                                    min="0" 
                                                    max="{{ $application->requested_amount }}"
                                                    placeholder="0.00"
                                                    style="width: 100%;"
                                                >
                                            </div>
                                            <div>
                                                <label style="display: block; font-size: 15px; font-weight: 600; color: #374151; margin-bottom: 8px;">
                                                    Traženi iznos:
                                                </label>
                                                <div style="padding: 10px 12px; background: #f9fafb; border-radius: 6px; color: #6b7280; font-size: 14px;">
                                                    {{ number_format($application->requested_amount, 2, ',', '.') }} €
                                                </div>
                                            </div>
                                        </div>

                                        <div style="margin-bottom: 24px;">
                                            <label style="display: block; font-size: 15px; font-weight: 600; color: #374151; margin-bottom: 8px;">
                                                Obrazloženje (obavezno za Odbija; kod jednakih bodova i istog stadijuma kada sredstva nijesu dovoljna — i za Podržava):
                                            </label>
                                            <textarea 
                                                name="commission_justification" 
                                                class="form-control" 
                                                rows="6" 
                                                placeholder="Unesite obrazloženje zaključka komisije...">{{ old('commission_justification', $application->commission_justification) }}</textarea>
                                            @error('commission_justification')
                                                <div style="color: #ef4444; font-size: 12px; margin-top: 8px;">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div style="text-align: right;">
                                            <button type="submit" class="btn btn-success" style="padding: 10px 24px; font-size: 14px; min-width: 160px;" @if($isDeadlinePassed) disabled style="opacity: 0.5; cursor: not-allowed; padding: 10px 24px; font-size: 14px; min-width: 160px;" @endif>Sačuvaj zaključak</button>
                                        </div>
                                    </form>
                                    @endif
                                @endif

                                <table class="commission-decision-table{{ (!in_array($competition->status, ['closed', 'completed']) && ! $zenskoDecisionLocked) ? ' print-only' : '' }}">
                                    <tbody>
                                        <tr class="commission-decision-name">
                                            <th colspan="2">{{ $application->business_plan_name }}</th>
                                        </tr>
                                        <tr>
                                            <th>Podnosilac</th>
                                            <td>{{ $application->user->name ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Pozicija</th>
                                            <td>#{{ $application->ranking_position }}</td>
                                        </tr>
                                        <tr>
                                            <th>Ocjena</th>
                                            <td>{{ number_format($application->getDisplayScore(), 2) }} / 58</td>
                                        </tr>
                                        @if($application->signed_by_chairman || in_array($competition->status, ['closed', 'completed']))
                                        <tr>
                                            <th>Zaključak</th>
                                            <td>
                                                @if($application->commission_decision === 'podrzava_potpuno')
                                                    Podržava se
                                                @elseif($application->commission_decision === 'odbija')
                                                    Odbija
                                                @else
                                                    Nije donesen
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Iznos odobrenih sredstava</th>
                                            <td>{{ $application->approved_amount ? number_format($application->approved_amount, 2, ',', '.') . ' €' : '-' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Traženi iznos</th>
                                            <td>{{ number_format($application->requested_amount ?? 0, 2, ',', '.') }} €</td>
                                        </tr>
                                        <tr>
                                            <th>Unio / Datum</th>
                                            <td>
                                                {{ $decisionEnteredBy ?? 'Predsjednik komisije' }}
                                                @if($application->commission_decision_date)
                                                    | {{ \Carbon\Carbon::parse($application->commission_decision_date)->format('d.m.Y') }}
                                                @endif
                                            </td>
                                        </tr>
                                        @if($application->commission_justification)
                                        <tr>
                                            <th>Obrazloženje</th>
                                            <td style="white-space: pre-wrap;">{{ $application->commission_justification }}</td>
                                        </tr>
                                        @endif
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        @endforeach

                        @if($competition->hasChairmanCompletedDecisions())
                        <div class="no-print" style="margin-top: 32px; padding-top: 24px; border-top: 2px solid #e5e7eb;"></div>
                        @endif
                    </div>
                @elseif($competition->hasChairmanCompletedDecisions() && $applications->count() == 0 && (isset($belowLineApplications) && $belowLineApplications->count() > 0))
                    <div class="info-card commission-decision-section">
                        <h2>Zaključak komisije</h2>
                        <p style="margin-bottom: 12px; font-size: 14px; color: #6b7280;">Sve prijave su ispod minimuma od 30 bodova. Možete generisati odluku i zatvoriti konkurs.</p>
                    </div>
                @endif
        @endif
        </div>

        <!-- Potpis komisije (samo za predsjednika i superadmina) -->
        @if((isset($isSuperAdmin) && $isSuperAdmin) || (isset($isChairman) && $isChairman))
        <div class="info-card" id="signature-block" style="padding: 16px 24px;">
            <h2 style="margin-bottom: 12px; font-size: 18px;">Potpis komisije</h2>
            <div style="margin-top: 16px; font-size: 13px; line-height: 1.5;">
                <p style="margin-bottom: 14px;">Kotor, _______________ god.</p>
                @if(isset($commissionMembers) && $commissionMembers->isNotEmpty())
                    @php $clanNum = 0; @endphp
                    @foreach($commissionMembers as $member)
                        <div style="margin-bottom: 6px;">
                            <div style="font-weight: 600; color: #374151;">{{ $member->position === 'predsjednik' ? 'Predsjednik Komisije' : 'Član ' . (++$clanNum) }}</div>
                            <div style="margin: 2px 0 0 0;">{{ $member->name ?? '' }}</div>
                            <div style="width: 200px; border-bottom: 1px solid #111; min-height: 1px; margin-top: 8px;"></div>
                        </div>
                    @endforeach
                @else
                    <div style="margin-bottom: 6px;"><div style="font-weight: 600;">Predsjednik Komisije</div><div style="width: 200px; border-bottom: 1px solid #111; margin-top: 8px; min-height: 1px;"></div></div>
                    <div style="margin-bottom: 6px;"><div style="font-weight: 600;">Član 1</div><div style="width: 200px; border-bottom: 1px solid #111; margin-top: 8px; min-height: 1px;"></div></div>
                    <div style="margin-bottom: 6px;"><div style="font-weight: 600;">Član 2</div><div style="width: 200px; border-bottom: 1px solid #111; margin-top: 8px; min-height: 1px;"></div></div>
                    <div style="margin-bottom: 6px;"><div style="font-weight: 600;">Član 3</div><div style="width: 200px; border-bottom: 1px solid #111; margin-top: 8px; min-height: 1px;"></div></div>
                    <div style="margin-bottom: 6px;"><div style="font-weight: 600;">Član 4</div><div style="width: 200px; border-bottom: 1px solid #111; margin-top: 8px; min-height: 1px;"></div></div>
                @endif
            </div>
        </div>
        @endif

        <!-- Dugmad na dnu (ekran) -->
        <div class="no-print" style="margin: 24px 0 8px 0; text-align: center;">
            @php
                $canViewDecisionInArchive = in_array($competition->status, ['closed', 'completed'])
                    && (
                        (isset($isCompetitionAdmin) && $isCompetitionAdmin)
                        || (isset($isCommissionMember) && $isCommissionMember)
                    );
            @endphp
            @if((isset($isSuperAdmin) && $isSuperAdmin) || (isset($isChairman) && $isChairman))
                @if($competition->hasChairmanCompletedDecisions())
                    <form method="GET" action="{{ route('admin.competitions.decision', $competition) }}" style="display: inline-block; margin: 0 8px;">
                        <button type="submit" class="btn btn-primary" style="background: var(--primary); color: #fff; border: none; cursor: pointer; padding: 10px 20px; border-radius: 8px; font-weight: 600;">
                            Generiši odluku
                        </button>
                    </form>
                @endif
                <button type="button" onclick="window.print();" class="btn" style="background: #6b7280; color: #fff; border: none; cursor: pointer; padding: 10px 20px; border-radius: 8px; font-weight: 600; margin: 0 8px;">
                    Štampaj
                </button>
            @endif
            @if($canViewDecisionInArchive)
                <form method="GET" action="{{ route('admin.competitions.decision', $competition) }}" style="display: inline-block; margin: 0 8px;">
                    <button type="submit" class="btn btn-primary" style="background: var(--primary); color: #fff; border: none; cursor: pointer; padding: 10px 20px; border-radius: 8px; font-weight: 600;">
                        Pregled odluke
                    </button>
                </form>
            @endif
            <a href="{{ route('admin.competitions.show', $competition) }}" class="btn btn-primary" style="margin: 0 8px;">Nazad</a>
        </div>
    </div>
</div>

@endsection

