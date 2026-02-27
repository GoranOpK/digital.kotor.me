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
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .page-header h1 {
        color: #fff;
        font-size: 28px;
        font-weight: 700;
        margin: 0;
    }
    .btn {
        padding: 10px 20px;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        margin-left: 8px;
    }
    .btn-primary {
        background: #fff;
        color: var(--primary);
    }
    .btn-success {
        background: #10b981;
        color: #fff;
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
    .budget-info {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
        margin-bottom: 24px;
    }
    .budget-item {
        padding: 16px;
        background: #f9fafb;
        border-radius: 8px;
        text-align: center;
    }
    .budget-label {
        font-size: 12px;
        color: #6b7280;
        margin-bottom: 8px;
    }
    .budget-value {
        font-size: 24px;
        font-weight: 700;
        color: var(--primary);
    }
    table {
        width: 100%;
        border-collapse: collapse;
    }
    th, td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid #e5e7eb;
    }
    th {
        font-weight: 600;
        color: #374151;
        font-size: 12px;
        text-transform: uppercase;
        background: #f9fafb;
    }
    .ranking-badge {
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
    .score-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 9999px;
        font-size: 14px;
        font-weight: 600;
    }
    .score-high {
        background: #d1fae5;
        color: #065f46;
    }
    .score-medium {
        background: #fef3c7;
        color: #92400e;
    }
    .score-low {
        background: #fee2e2;
        color: #991b1b;
    }
    .checkbox-cell {
        text-align: center;
    }
    .form-control {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 14px;
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
    .status-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 9999px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
    }
    .status-submitted {
        background: #dbeafe;
        color: #1e40af;
    }
    .status-evaluated {
        background: #e0e7ff;
        color: #3730a3;
    }
    .status-approved {
        background: #d1fae5;
        color: #065f46;
    }
    .status-rejected {
        background: #fee2e2;
        color: #991b1b;
    }
    @media print {
        nav,
        header,
        .no-print { display: none !important; }
        .admin-page button,
        .admin-page input[type="submit"],
        .admin-page input[type="button"],
        .admin-page input[type="checkbox"],
        .admin-page input[type="number"],
        .admin-page input[type="radio"],
        .admin-page textarea,
        .admin-page select { display: none !important; }
        .admin-page { background: #fff; padding: 0; }
        .info-card { box-shadow: none; border: 1px solid #e5e7eb; }
        /* Tabela - zaglavlje se ponavlja na svakoj stranici */
        .admin-page table thead {
            display: table-header-group;
        }
        /* Kompaktni stilovi za prvu stranicu - sve mora stati: Budžet + Rang lista + Zaključak */
        .ranking-main-content .info-card {
            padding: 12px !important;
            margin-bottom: 12px !important;
        }
        .ranking-main-content .info-card h2 {
            font-size: 16px !important;
            margin-bottom: 10px !important;
            padding-bottom: 8px !important;
        }
        .ranking-main-content .budget-info {
            gap: 8px !important;
            margin-bottom: 12px !important;
        }
        .ranking-main-content .budget-item {
            padding: 8px !important;
        }
        .ranking-main-content .budget-value {
            font-size: 18px !important;
        }
        .ranking-main-content table th,
        .ranking-main-content table td {
            padding: 6px 8px !important;
            font-size: 11px !important;
        }
        .ranking-main-content .commission-decision-block {
            padding: 12px !important;
            margin-bottom: 12px !important;
        }
        .ranking-main-content .commission-decision-block h3 {
            font-size: 14px !important;
        }
        .ranking-main-content .commission-decision-block p,
        .ranking-main-content .commission-decision-block div {
            font-size: 11px !important;
        }
        .ranking-main-content .commission-decision-section > p {
            margin-bottom: 12px !important;
            font-size: 11px !important;
        }
        /* Svaki blok kandidata ostaje cijeli - ne dijeli se na pola između stranica */
        .commission-decision-block {
            page-break-inside: avoid;
        }
        /* Stranica 1: Budget + Rang lista + Zaključak, zatim page break, zatim Potpis */
        .ranking-main-content {
            page-break-after: always;
        }
        /* Potpis uvijek na novoj stranici */
        #signature-block {
            page-break-before: always;
        }
        @page {
            size: A4;
            margin: 15mm;
        }
    }
</style>

<div class="admin-page">
    <div class="container mx-auto px-4">
        <div class="page-header">
            <h1>Rang lista - {{ $competition->title }}</h1>
            <div class="no-print">
                @if((isset($isSuperAdmin) && $isSuperAdmin) || (isset($isChairman) && $isChairman))
                <button type="button" onclick="window.print();" class="btn" style="background: #6b7280; color: #fff; border: none; cursor: pointer; padding: 10px 20px; border-radius: 8px; font-weight: 600;">Štampaj</button>
                @endif
                <a href="{{ route('admin.competitions.show', $competition) }}" class="btn btn-primary">Nazad</a>
            </div>
        </div>

        @php
            $daysRemaining = $competition->getDaysUntilEvaluationDeadline();
            $isDeadlinePassed = $competition->isEvaluationDeadlinePassed();
        @endphp
                
        @if($isDeadlinePassed)
            <div class="alert alert-danger" style="background: #fee2e2; border: 1px solid #ef4444; color: #991b1b; padding: 16px 20px; border-radius: 12px; margin-bottom: 24px;">
                <strong>❌ Rok istekao:</strong> Rok za ocjenjivanje i donošenje odluke je istekao. Komisija je dužna donijeti odluku u roku od 30 dana od dana zatvaranja prijava na konkurs.
            </div>
        @endif

        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        <div class="ranking-main-content">
        <!-- Informacije o budžetu -->
        <div class="info-card">
            <h2>Informacije o budžetu</h2>
            <div class="budget-info">
                <div class="budget-item">
                    <div class="budget-label">Ukupan budžet</div>
                    <div class="budget-value">{{ number_format($totalBudget, 2, ',', '.') }} €</div>
                </div>
                <div class="budget-item">
                    <div class="budget-label">Iskorišćen budžet</div>
                    <div class="budget-value">{{ number_format($usedBudget, 2, ',', '.') }} €</div>
                </div>
                <div class="budget-item">
                    <div class="budget-label">Preostali budžet</div>
                    <div class="budget-value">{{ number_format($remainingBudget, 2, ',', '.') }} €</div>
                </div>
            </div>
        </div>

        <!-- Rang lista -->
        <div class="info-card">
            <h2>Rang lista prijava</h2>

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
                                        <span class="ranking-badge">{{ $application->ranking_position ?? $loop->iteration }}</span>
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
                                            {{ number_format($score, 2) }} / 50
                                        </span>
                                    </td>
                                    <td style="text-align: right;">
                                        {{ number_format($application->requested_amount, 2, ',', '.') }} €
                                    </td>
                                    <td style="text-align: right;">
                                        @if($application->approved_amount)
                                            <strong style="color: #10b981;">
                                                {{ number_format($application->approved_amount, 2, ',', '.') }} €
                                            </strong>
                                        @else
                                            <span style="color: #6b7280;">-</span>
                                        @endif
                                    </td>
                                    <td class="checkbox-cell">
                                        @if($application->status === 'approved')
                                            <span style="color: #10b981; font-weight: 600;">✓ Dobitnik sredstava</span>
                                        @else
                                            <span style="color: #6b7280;">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div style="margin-top: 24px; text-align: center;">
                        <form method="GET" action="{{ route('admin.competitions.decision', $competition) }}" style="display: inline;">
                            <button type="submit" class="btn btn-primary" style="background: var(--primary); color: #fff; border: none; cursor: pointer; padding: 12px 24px;">Generiši odluku</button>
                        </form>
                    </div>
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
                                        <span class="ranking-badge">{{ $application->ranking_position ?? $loop->iteration }}</span>
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
                                            {{ number_format($score, 2) }} / 50
                                        </span>
                                    </td>
                                    <td style="text-align: right;">
                                        {{ number_format($application->requested_amount, 2, ',', '.') }} €
                                    </td>
                                    <td style="text-align: right;">
                                        @if($application->approved_amount)
                                            <strong style="color: #10b981;">
                                                {{ number_format($application->approved_amount, 2, ',', '.') }} €
                                            </strong>
                                        @else
                                            <span style="color: #6b7280;">-</span>
                                        @endif
                                    </td>
                                    <td class="checkbox-cell">
                                        @if($application->status === 'approved')
                                            <span style="color: #10b981; font-weight: 600;">✓ Dobitnik sredstava</span>
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
                                    <span class="ranking-badge">{{ $application->ranking_position ?? $loop->iteration }}</span>
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
                                        {{ number_format($score, 2) }} / 50
                                    </span>
                                </td>
                                <td style="text-align: right;">
                                    {{ number_format($application->requested_amount, 2, ',', '.') }} €
                                </td>
                                <td style="text-align: right;">
                                    @if($application->approved_amount)
                                        <strong style="color: #10b981;">
                                            {{ number_format($application->approved_amount, 2, ',', '.') }} €
                                        </strong>
                                    @else
                                        <span style="color: #6b7280;">-</span>
                                    @endif
                                </td>
                                <td class="checkbox-cell">
                                    @if($application->status === 'approved')
                                        <span style="color: #10b981; font-weight: 600;">✓ Dobitnik sredstava</span>
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
                <div style="margin-top: 48px; padding-top: 32px; border-top: 4px solid #374151; box-shadow: 0 -2px 8px rgba(0,0,0,0.08);">
                    <div style="background: #374151; color: #fff; padding: 14px 24px; margin-top: -32px; margin-bottom: 24px; border-radius: 8px; font-weight: 700; font-size: 16px; text-align: center; display: inline-block; width: 100%; box-sizing: border-box;">
                        ——— Prijave ispod minimuma (30 bodova) ———
                    </div>
                    <h3 style="font-size: 16px; font-weight: 600; color: #6b7280; margin-bottom: 16px;">Prijave sa manje od 30 bodova</h3>
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
                                            {{ number_format($application->getDisplayScore(), 2) }} / 50
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
                    <p style="margin-bottom: 16px; font-size: 16px; font-weight: 600;">Nema ocjenjenih prijava za ovaj konkurs.</p>
                    <p style="margin-bottom: 8px; font-size: 14px;">Prijave odbijene zbog nedostatka dokumenata ne prikazuju se u rang listi.</p>
                </div>
            @endif
        </div>

        <!-- Sekcije 4 i 5: Zaključak komisije i Obrazloženje -->
        @if((isset($isSuperAdmin) && $isSuperAdmin) || (isset($isChairman) && $isChairman))
            @if(!in_array($competition->status, ['closed', 'completed']))
                @if($applications->count() > 0)
                    <div class="info-card commission-decision-section">
                        <h2>Zaključak komisije i obrazloženje</h2>
                        <p style="color: #6b7280; margin-bottom: 24px; font-size: 14px;">
                            Za svaku prijavu u rang listi unesite zaključak komisije i obrazloženje.
                        </p>

                        @foreach($applications as $application)
                            <div class="commission-decision-block" style="background: #f9fafb; padding: 24px; border-radius: 12px; margin-bottom: 24px; border: 1px solid #e5e7eb;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 16px; border-bottom: 2px solid #e5e7eb;">
                                    <div>
                                        <h3 style="font-size: 18px; font-weight: 700; color: var(--primary); margin: 0 0 4px 0;">
                                            {{ $application->business_plan_name }}
                                        </h3>
                                        <p style="font-size: 14px; color: #6b7280; margin: 0;">
                                            Podnosilac: {{ $application->user->name ?? 'N/A' }} | 
                                            Pozicija: <strong>#{{ $application->ranking_position ?? $loop->iteration }}</strong> | 
                                            Ocjena: <strong>{{ number_format($application->getDisplayScore(), 2) }} / 50</strong>
                                        </p>
                                    </div>
                                    @if($application->commission_decision)
                                        <span style="display: inline-block; padding: 6px 12px; background: #d1fae5; color: #065f46; border-radius: 6px; font-size: 12px; font-weight: 600;">
                                            ✓ Zaključeno
                                        </span>
                                    @endif
                                </div>
                                
                                @if(!$application->signed_by_chairman)
                                    <form method="POST" action="{{ route('evaluation.store-decision', $application) }}">
                                        @csrf
                                        
                                        <div style="margin-bottom: 24px;">
                                            <label style="display: block; font-size: 15px; font-weight: 600; color: #374151; margin-bottom: 12px;">
                                                Zaključak komisije: *
                                            </label>
                                            <div style="display: flex; gap: 32px; flex-wrap: wrap;">
                                                <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; padding: 12px; background: #fff; border-radius: 8px; border: 2px solid #e5e7eb; transition: all 0.2s;">
                                                    <input type="radio" name="commission_decision" value="podrzava_potpuno" {{ old('commission_decision', $application->commission_decision) === 'podrzava_potpuno' ? 'checked' : '' }} required onchange="this.parentElement.style.borderColor = this.checked ? 'var(--primary)' : '#e5e7eb'; this.parentElement.style.background = this.checked ? '#eff6ff' : '#fff';">
                                                    <span style="font-size: 14px; color: #374151; font-weight: 500;">a. Podržava u potpunosti</span>
                                                </label>
                                                <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; padding: 12px; background: #fff; border-radius: 8px; border: 2px solid #e5e7eb; transition: all 0.2s;">
                                                    <input type="radio" name="commission_decision" value="podrzava_djelimicno" {{ old('commission_decision', $application->commission_decision) === 'podrzava_djelimicno' ? 'checked' : '' }} required onchange="this.parentElement.style.borderColor = this.checked ? 'var(--primary)' : '#e5e7eb'; this.parentElement.style.background = this.checked ? '#eff6ff' : '#fff';">
                                                    <span style="font-size: 14px; color: #374151; font-weight: 500;">b. Podržava djelimično</span>
                                                </label>
                                                <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; padding: 12px; background: #fff; border-radius: 8px; border: 2px solid #e5e7eb; transition: all 0.2s;">
                                                    <input type="radio" name="commission_decision" value="odbija" {{ old('commission_decision', $application->commission_decision) === 'odbija' ? 'checked' : '' }} required onchange="this.parentElement.style.borderColor = this.checked ? 'var(--primary)' : '#e5e7eb'; this.parentElement.style.background = this.checked ? '#eff6ff' : '#fff';">
                                                    <span style="font-size: 14px; color: #374151; font-weight: 500;">c. Odbija</span>
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
                                                Obrazloženje: *
                                            </label>
                                            <textarea 
                                                name="commission_justification" 
                                                class="form-control" 
                                                rows="6" 
                                                required 
                                                placeholder="Unesite obrazloženje zaključka komisije...">{{ old('commission_justification', $application->commission_justification) }}</textarea>
                                            @error('commission_justification')
                                                <div style="color: #ef4444; font-size: 12px; margin-top: 8px;">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div style="text-align: right;">
                                            <button type="submit" class="btn btn-success" style="padding: 10px 24px; font-size: 14px; min-width: 160px;" @if($isDeadlinePassed) disabled style="opacity: 0.5; cursor: not-allowed; padding: 10px 24px; font-size: 14px; min-width: 160px;" @endif>Sačuvaj zaključak</button>
                                        </div>
                                    </form>
                                @else
                                    {{-- Read-only prikaz ako je već zaključeno --}}
                                    <div style="background: #fff; padding: 20px; border-radius: 8px; border: 1px solid #e5e7eb;">
                                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 16px;">
                                            <div>
                                                <p style="margin-bottom: 8px; font-size: 13px; color: #6b7280; font-weight: 600; text-transform: uppercase;">Zaključak:</p>
                                                <p style="font-size: 15px; color: #374151; font-weight: 600;">
                                                    @if($application->commission_decision === 'podrzava_potpuno')
                                                        Podržava u potpunosti
                                                    @elseif($application->commission_decision === 'podrzava_djelimicno')
                                                        Podržava djelimično
                                                    @elseif($application->commission_decision === 'odbija')
                                                        Odbija
                                                    @else
                                                        Nije donesen
                                                    @endif
                                                </p>
                                            </div>
                                            <div>
                                                <p style="margin-bottom: 8px; font-size: 13px; color: #6b7280; font-weight: 600; text-transform: uppercase;">Iznos odobrenih sredstava:</p>
                                                <p style="font-size: 15px; color: #10b981; font-weight: 700;">
                                                    {{ $application->approved_amount ? number_format($application->approved_amount, 2, ',', '.') . ' €' : '-' }}
                                                </p>
                                            </div>
                                        </div>
                                        @if($application->commission_justification)
                                            <div>
                                                <p style="margin-bottom: 8px; font-size: 13px; color: #6b7280; font-weight: 600; text-transform: uppercase;">Obrazloženje:</p>
                                                <div style="background: #f9fafb; padding: 16px; border-radius: 6px; white-space: pre-wrap; font-size: 14px; color: #374151; line-height: 1.6;">
                                                    {{ $application->commission_justification }}
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        @endforeach

                        @if($competition->hasChairmanCompletedDecisions())
                        <div style="margin-top: 32px; padding-top: 24px; border-top: 2px solid #e5e7eb; text-align: center;">
                            <form method="GET" action="{{ route('admin.competitions.decision', $competition) }}" style="display: inline;">
                                <button type="submit" class="btn btn-primary" style="padding: 12px 32px; font-size: 16px; min-width: 200px; background: var(--primary); color: #fff; border: none; cursor: pointer; border-radius: 8px;">
                                    Generiši Odluku o dodjeli sredstava
                                </button>
                            </form>
                            <p style="margin-top: 12px; font-size: 14px; color: #6b7280;">Nakon generisanja odluke možete zatvoriti konkurs na stranici odluke.</p>
                        </div>
                        @endif
                    </div>
                @elseif($competition->hasChairmanCompletedDecisions() && $applications->count() == 0 && (isset($belowLineApplications) && $belowLineApplications->count() > 0))
                    <div class="info-card commission-decision-section">
                        <h2>Zaključak komisije</h2>
                        <p style="margin-bottom: 12px; font-size: 14px; color: #6b7280;">Sve prijave su ispod minimuma od 30 bodova. Možete generisati odluku i zatvoriti konkurs.</p>
                        <form method="GET" action="{{ route('admin.competitions.decision', $competition) }}" style="display: inline;">
                            <button type="submit" class="btn btn-primary" style="padding: 12px 32px; font-size: 16px; min-width: 200px; background: var(--primary); color: #fff; border: none; cursor: pointer; border-radius: 8px;">
                                Generiši Odluku o dodjeli sredstava
                            </button>
                        </form>
                    </div>
                @endif
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
    </div>
</div>

@endsection

