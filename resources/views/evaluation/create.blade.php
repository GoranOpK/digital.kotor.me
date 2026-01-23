@extends('layouts.app')

@section('content')
<style>
    :root {
        --primary: #0B3D91;
        --primary-dark: #0A347B;
    }
    .evaluation-page {
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
    .page-header .subtitle {
        color: rgba(255, 255, 255, 0.9);
        font-size: 14px;
        margin: 0;
    }
    .form-card {
        background: #fff;
        border-radius: 16px;
        padding: 40px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        max-width: 1200px;
        margin: 0 auto;
    }
    .form-title {
        text-align: center;
        font-size: 20px;
        font-weight: 700;
        color: #111827;
        margin-bottom: 8px;
        text-transform: uppercase;
    }
    .form-subtitle {
        text-align: center;
        font-size: 14px;
        color: #6b7280;
        margin-bottom: 32px;
        font-style: italic;
    }
    .form-section {
        margin-bottom: 32px;
    }
    .form-label {
        display: block;
        font-size: 14px;
        font-weight: 600;
        color: #374151;
        margin-bottom: 8px;
    }
    .form-label-large {
        font-size: 16px;
        font-weight: 700;
        color: #111827;
        margin-bottom: 12px;
    }
    .form-control {
        width: 100%;
        padding: 10px 14px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        font-size: 14px;
        background: #fff;
    }
    .form-control:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(11, 61, 145, 0.1);
    }
    .form-control-readonly {
        background: #f9fafb;
        border: 1px solid #e5e7eb;
        color: #6b7280;
    }
    .radio-group {
        display: flex;
        gap: 24px;
        margin-top: 12px;
    }
    .radio-option {
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
    }
    .radio-option input[type="radio"] {
        cursor: pointer;
    }
    .info-box {
        background: #f9fafb;
        padding: 16px;
        border-radius: 8px;
        margin-bottom: 24px;
        font-size: 14px;
        color: #374151;
        border-left: 4px solid var(--primary);
    }
    .info-box strong {
        color: #111827;
    }
    .evaluation-table {
        width: 100%;
        border-collapse: collapse;
        margin: 24px 0;
        font-size: 13px;
    }
    .evaluation-table th,
    .evaluation-table td {
        border: 1px solid #e5e7eb !important;
        padding: 6px 4px;
        text-align: center;
    }
    .evaluation-table th {
        background: #f9fafb;
        font-weight: bold !important;
        color: #111827;
        font-size: 12px;
    }
    .evaluation-table td {
        background: #fff;
    }
    .evaluation-table .criterion-col {
        text-align: left;
        font-size: 12px;
        width: 40%;
        padding: 6px;
    }
    .evaluation-table .score-input {
        width: 60px;
        padding: 6px;
        border: 1px solid #d1d5db;
        border-radius: 4px;
        text-align: center;
        font-size: 13px;
    }
    .evaluation-table .score-input:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 2px rgba(11, 61, 145, 0.1);
    }
    .evaluation-table .score-display {
        color: #6b7280;
        font-weight: 500;
    }
    .evaluation-table .average-col {
        background: #f0f9ff;
        font-weight: 600;
        color: var(--primary);
    }
    .evaluation-table .final-score-row {
        background: var(--primary);
        color: #fff !important;
        font-weight: bold !important;
    }
    .evaluation-table .final-score-row .average-col {
        font-weight: bold !important;
        color: #fff !important;
    }
    .evaluation-table .final-score-row td {
        background: var(--primary);
        color: #fff !important;
        font-weight: bold !important;
    }
    .justification-section textarea.form-control {
        padding-left: 2em !important;
        padding-right: 14px !important;
        margin-left: 0 !important;
    }
    .justification-section .readonly-value {
        padding-left: 2em !important;
        margin-left: 0 !important;
    }
    .signature-section {
        margin-top: 48px;
        padding-top: 32px;
        border-top: 2px solid #e5e7eb;
    }
    .signature-row {
        display: flex;
        justify-content: space-between;
        margin-top: 24px;
        font-size: 14px;
    }
    .signature-item {
        width: 180px;
        padding-bottom: 40px;
    }
    .btn-primary {
        background: var(--primary);
        color: #fff;
        padding: 12px 24px;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        font-size: 14px;
    }
    .btn-primary:hover {
        background: var(--primary-dark);
    }
    .error-message {
        color: #ef4444;
        font-size: 12px;
        margin-top: 4px;
    }
    .warning-box {
        background: #fef3c7;
        border-left: 4px solid #f59e0b;
        padding: 16px;
        border-radius: 8px;
        margin-bottom: 24px;
        font-size: 13px;
        color: #92400e;
    }
    .amount-input {
        max-width: 200px;
        margin-top: 12px;
    }
    @media print {
        nav,
        .page-header,
        .btn-primary,
        button,
        a[href] {
            display: none !important;
        }
        .evaluation-page {
            padding: 0;
        }
        .container {
            padding: 0;
        }
        .form-card {
            padding: 20px;
            box-shadow: none;
        }
        .form-section {
            margin-bottom: 4px;
            padding-left: 0 !important;
            padding-right: 0 !important;
        }
        .form-section textarea,
        .form-section .readonly-value {
            text-align: left !important;
            padding-left: 0 !important;
            padding-right: 0 !important;
            margin-left: 0 !important;
            margin-right: 0 !important;
            width: 100% !important;
        }
        .form-section .readonly-value {
            text-align: left !important;
        }
        .justification-section textarea.form-control {
            padding-left: 2em !important;
            padding-right: 0 !important;
            margin-left: 0 !important;
        }
        .justification-section .readonly-value {
            padding-left: 2em !important;
            margin-left: 0 !important;
        }
        .commission-decision-section {
            margin-top: 20mm !important;
        }
        .form-label-large {
            page-break-after: avoid;
        }
        .form-title {
            margin-top: 15mm !important;
        }
        .evaluation-table {
            page-break-inside: auto;
            page-break-after: always;
        }
        .evaluation-table thead {
            display: table-header-group;
        }
        .evaluation-table tbody {
            display: table-row-group;
        }
        .evaluation-table tr {
            page-break-inside: avoid;
        }
        .evaluation-table th,
        .evaluation-table td {
            border: 1px solid #000 !important;
            padding: 6px 4px;
        }
        .evaluation-table th {
            font-weight: bold !important;
        }
        .evaluation-table .average-col {
            font-weight: bold !important;
            color: #000 !important;
        }
        .evaluation-table .final-score-row td {
            font-weight: bold !important;
            color: #000 !important;
        }
        .evaluation-table .final-score-row .average-col {
            font-weight: bold !important;
            color: #000 !important;
        }
        .info-box,
        .warning-box {
            page-break-inside: avoid;
        }
        .criteria-info-box {
            display: none !important;
        }
        .notes-info-box {
            display: none !important;
        }
        .signature-section {
            page-break-inside: avoid;
            page-break-before: auto;
            border-top: none !important;
        }
        .form-control-readonly,
        textarea.form-control {
            border: none !important;
            background: transparent !important;
        }
        @page {
            size: A4;
            margin: 25mm;
        }
    }
</style>

<div class="evaluation-page">
    <div class="container mx-auto px-4">
        <div class="page-header">
            <h1>Ocjenjivanje prijave</h1>
            <p class="subtitle">{{ $application->business_plan_name }}</p>
        </div>


        <!-- Forma za ocjenjivanje -->
        <div class="form-card">
            <form method="POST" action="{{ route('evaluation.store', $application) }}" id="evaluationForm">
                @csrf

                <div class="form-title">
                    LISTA ZA OCJENJIVANJE BIZNIS PLANOVA
                </div>
                <div class="form-subtitle">
                    (Popunjava Komisija za raspodjelu sredstava za podršku ženskom preduzetništvu)
                </div>

                <!-- 1. Naziv biznis plana -->
                <div class="form-section">
                    <label class="form-label form-label-large">1. Naziv biznis plana:</label>
                    <input type="text" class="form-control form-control-readonly" value="{{ $application->business_plan_name }}" readonly>
                </div>

                <!-- 2. Dostavljena su sva potrebna dokumenta? -->
                <div class="form-section">
                    <label class="form-label form-label-large">2. Dostavljena su sva potrebna dokumenta?</label>
                    
                    @if($commissionMember->position === 'predsjednik')
                        {{-- Predsjednik može označiti --}}
                        <div class="radio-group">
                            <label class="radio-option">
                                <input type="radio" name="documents_complete" value="1" {{ old('documents_complete', $existingScore?->documents_complete ?? true) ? 'checked' : '' }} required>
                                <span>a. Da</span>
                            </label>
                            <label class="radio-option">
                                <input type="radio" name="documents_complete" value="0" {{ old('documents_complete') === '0' || ($existingScore && !$existingScore->documents_complete) ? 'checked' : '' }} required>
                                <span>b. Ne*</span>
                            </label>
                        </div>
                        @error('documents_complete')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                    @else
                        {{-- Ostali članovi vide samo read-only prikaz --}}
                        @php
                            // Pronađi predsjednika komisije i njegovu ocjenu
                            $chairmanMember = $allMembers->firstWhere('position', 'predsjednik');
                            $chairmanScore = $chairmanMember ? $allScores->get($chairmanMember->id) : null;
                            $documentsComplete = $chairmanScore ? $chairmanScore->documents_complete : null;
                        @endphp
                        <div style="padding: 16px; background: #f9fafb; border-radius: 8px; margin-top: 12px; border: 1px solid #e5e7eb;">
                            @if($documentsComplete !== null)
                                <div style="margin-bottom: 8px;">
                                    <strong style="color: #111827;">
                                        {{ $documentsComplete ? 'a. Da' : 'b. Ne*' }}
                                    </strong>
                                </div>
                            @else
                                <div style="color: #6b7280;">
                                    <em>Nije označeno</em>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>

                <!-- 4. Ocjena biznis plana u brojkama -->
                <div class="form-section">
                    <label class="form-label form-label-large">3. Ocjena biznis plana u brojkama:</label>
                    
                    <div class="info-box criteria-info-box">
                        <strong>KRITERIJUMI ZA OCJENU</strong><br>
                        (Član 18 stav 2 Odluke)<br>
                        Komisija dodijeljuje ocjenu za biznis plan na skali od 1 do 5, pri čemu je:<br>
                        1 = uopšte ne odgovara navedenom,<br>
                        5 = u potpunosti odgovara navedenom.
                    </div>

                    @php
                        $criteria = [
                            1 => 'Obrazac biznis plana je detaljno popunjen sa svim neophodnim informacijama i jasno su precizirani proizvodi/usluge koje će se ponuditi na tržištu',
                            2 => 'Biznis ideja je inovativna (stvaranje novog proizvoda/usluge, unaprijeđenje proizvoda/usluga, uvećan obim proizvodnje)',
                            3 => 'Jasno su identifikovani potencijalni kupci i njihove karakteristike',
                            4 => 'Biznis plan će omogućiti samozapošljavanje i/ili zapošljavanje (stalno ili sezonsko) lica sa teritorije opštine Kotor',
                            5 => 'Prepoznata je i navedena konkurencija, kao i slabosti i snage iste',
                            6 => 'Jasno su navedeni potrebni resursi i identifikovani dobavljači',
                            7 => 'Biznis ideja je finansijski održiva (jasno su prikazani očekivani prihodi i rashodi poslovanja)',
                            8 => 'Podaci o preduzetnici (preduzetnica posjeduje iskustvo, potrebna znanja i vještine, te svijest o preduzetničkim osobinama koje mora unaprijediti, preduzetnica planira raspored poslova uz identifikaciju osoba za njihovo obavljanje)',
                            9 => 'Razvijena matrica rizika je jasna i logična',
                            10 => 'Usmeno obrazloženje biznis plana (preduzetnica je uvjerljiva i sigurna u svoju biznis ideju, pokazuje visoku motivisanost za realizaciju iste i spremno odgovara na sva pitanja)',
                        ];
                    @endphp

                    <table class="evaluation-table">
                        <thead>
                            <tr>
                                <th class="criterion-col">KRITERIJUMI ZA OCJENU</th>
                                @foreach($allMembers as $member)
                                    <th style="font-size: 11px;">
                                        {{ $member->position === 'predsjednik' ? 'Predsjednik' : 'Član ' . ($loop->index) }}
                                    </th>
                                @endforeach
                                <th class="average-col">Prosječna ocjena*</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($criteria as $num => $name)
                                <tr>
                                    <td class="criterion-col">
                                        <strong>{{ $num }}.</strong> {{ $name }}
                                    </td>
                                    @foreach($allMembers as $member)
                                        <td>
                                            @php
                                                $memberScore = $allScores->get($member->id);
                                                $currentValue = $memberScore ? $memberScore->{"criterion_{$num}"} : null;
                                                $isCurrentMember = $member->id === $commissionMember->id;
                                                // Provjeri da li je trenutni član završio ocjenjivanje
                                                $hasCompletedEvaluation = isset($hasCompletedEvaluation) ? $hasCompletedEvaluation : ($existingScore && $existingScore->criterion_1 !== null);
                                                // Provjeri da li je drugi član završio ocjenjivanje
                                                $otherMemberCompleted = $memberScore && $memberScore->criterion_1 !== null;
                                                // Ako je predsjednik i već je ocjenio, može vidjeti sve ocjene
                                                // ILI ako su svi članovi ocjenili, svi članovi mogu vidjeti sve ocjene
                                                $canViewAllScores = (isset($isChairman) && $isChairman && isset($hasCompletedEvaluation) && $hasCompletedEvaluation) 
                                                    || (isset($allMembersEvaluated) && $allMembersEvaluated);
                                            @endphp
                                            @if($isCurrentMember)
                                                {{-- Trenutni član vidi svoje input polje --}}
                                                @if(isset($hasCompletedEvaluation) && $hasCompletedEvaluation && isset($isChairman) && $isChairman)
                                                    {{-- Predsjednik kada je već ocjenio - kriterijumi su read-only (može mijenjati samo sekciju 2) --}}
                                                    <span class="score-display">
                                                        {{ $currentValue ? $currentValue : '—' }}
                                                    </span>
                                                @elseif(isset($hasCompletedEvaluation) && $hasCompletedEvaluation && isset($allMembersEvaluated) && $allMembersEvaluated)
                                                    {{-- Kada su svi članovi ocjenili, svi članovi vide svoje ocjene u read-only modu --}}
                                                    <span class="score-display">
                                                        {{ $currentValue ? $currentValue : '—' }}
                                                    </span>
                                                @elseif(isset($hasCompletedEvaluation) && $hasCompletedEvaluation && !(isset($isChairman) && $isChairman))
                                                    {{-- Ako je već ocjenio i nije predsjednik i nisu svi ocjenili, read-only --}}
                                                    <span class="score-display">
                                                        {{ $currentValue ? $currentValue : '—' }}
                                                    </span>
                                                @else
                                                    {{-- Može unijeti ili mijenjati --}}
                                                    <input 
                                                        type="number" 
                                                        name="criterion_{{ $num }}" 
                                                        class="score-input" 
                                                        min="1" 
                                                        max="5" 
                                                        value="{{ old("criterion_{$num}", $currentValue) }}"
                                                        required
                                                        onchange="updateAverages()">
                                                @endif
                                            @else
                                                {{-- Ostali članovi - prikaži ocjenu samo ako je trenutni član završio ocjenjivanje I drugi član je završio ocjenjivanje, ILI ako je predsjednik i svi su ocjenili --}}
                                                @if(($hasCompletedEvaluation && $otherMemberCompleted) || $canViewAllScores)
                                                    <span class="score-display">
                                                        {{ $currentValue ? $currentValue : '—' }}
                                                    </span>
                                                @else
                                                    <span class="score-display" style="color: #d1d5db;">
                                                        —
                                                    </span>
                                                @endif
                                            @endif
                                        </td>
                                    @endforeach
                                    <td class="average-col" id="avg_{{ $num }}">
                                        @php
                                            // Prikaži prosječnu ocjenu samo ako je trenutni član završio ocjenjivanje ILI ako je predsjednik i već je ocjenio
                                            $hasCompletedEvaluation = isset($hasCompletedEvaluation) ? $hasCompletedEvaluation : ($existingScore && $existingScore->criterion_1 !== null);
                                            $canViewAllScores = isset($isChairman) && $isChairman && isset($hasCompletedEvaluation) && $hasCompletedEvaluation;
                                        @endphp
                                        @if(($hasCompletedEvaluation && isset($averageScores[$num])) || ($canViewAllScores && isset($averageScores[$num])))
                                            {{ number_format($averageScores[$num], 2) }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            <tr class="final-score-row">
                                <td class="criterion-col" style="text-align: center; font-weight: bold !important;">
                                    <strong>KONAČNA OCJENA:</strong>
                                </td>
                                @foreach($allMembers as $member)
                                    <td style="font-weight: bold !important;">
                                        @php
                                            $memberScore = $allScores->get($member->id);
                                            $memberTotal = $memberScore ? $memberScore->calculateTotalScore() : 0;
                                            $isCurrentMember = $member->id === $commissionMember->id;
                                            $hasCompletedEvaluation = isset($hasCompletedEvaluation) ? $hasCompletedEvaluation : ($existingScore && $existingScore->criterion_1 !== null);
                                            $otherMemberCompleted = $memberScore && $memberScore->criterion_1 !== null;
                                            $canViewAllScores = isset($isChairman) && $isChairman && isset($hasCompletedEvaluation) && $hasCompletedEvaluation;
                                        @endphp
                                        @if($isCurrentMember)
                                            {{-- Trenutni član vidi svoju konačnu ocjenu --}}
                                            <strong>{{ $memberTotal > 0 ? $memberTotal : '—' }}</strong>
                                        @else
                                            {{-- Ostali članovi - prikaži ocjenu samo ako je trenutni član završio ocjenjivanje I drugi član je završio ocjenjivanje, ILI ako je predsjednik i svi su ocjenili --}}
                                            @if(($hasCompletedEvaluation && $otherMemberCompleted) || $canViewAllScores)
                                                <strong>{{ $memberTotal > 0 ? $memberTotal : '—' }}</strong>
                                            @else
                                                <strong style="color: #d1d5db;">—</strong>
                                            @endif
                                        @endif
                                    </td>
                                @endforeach
                                <td class="average-col" id="final_score" style="font-weight: bold !important;">
                                    @php
                                        // Prikaži konačnu ocjenu samo ako je trenutni član završio ocjenjivanje ILI ako je predsjednik i već je ocjenio
                                        $hasCompletedEvaluation = isset($hasCompletedEvaluation) ? $hasCompletedEvaluation : ($existingScore && $existingScore->criterion_1 !== null);
                                        $canViewAllScores = isset($isChairman) && $isChairman && isset($hasCompletedEvaluation) && $hasCompletedEvaluation;
                                    @endphp
                                    @if(($hasCompletedEvaluation && $finalScore > 0) || ($canViewAllScores && $finalScore > 0))
                                        <strong>{{ number_format($finalScore, 2) }}</strong>
                                    @else
                                        <strong>—</strong>
                                    @endif
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="info-box notes-info-box" style="margin-top: 16px; font-size: 12px;">
                        * Prosječna ocjena za svaki kriterijum se dobija odnosom zbira ocjena svih članova Komisije i broja članova Komisije.<br>
                        Konačna ocjena je zbir svih prosječnih ocjena po kriterijumima.
                    </div>

                    <div class="warning-box notes-info-box" style="margin-top: 16px;">
                        <strong>Napomena:</strong> Biznis planovi sa ukupnim brojem bodova ispod 30 se neće podržati.
                    </div>

                    @for($i = 1; $i <= 10; $i++)
                        @error("criterion_{$i}")
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                    @endfor
                </div>

                <!-- 5. Zaključak komisije -->
                <div class="form-section commission-decision-section">
                    <label class="form-label form-label-large">4. Na bazi konačne ocjene Komisija donosi zaključak da se biznis plan:</label>
                    
                    @if($commissionMember->position === 'predsjednik' && isset($allMembersEvaluated) && $allMembersEvaluated)
                        {{-- Predsjednik može unijeti zaključak kada su svi članovi ocjenili --}}
                        <div class="radio-group">
                            <label class="radio-option">
                                <input type="radio" name="commission_decision" value="podrzava_potpuno" {{ old('commission_decision', $application->commission_decision) === 'podrzava_potpuno' ? 'checked' : '' }}>
                                <span>a. Podržava u potpunosti</span>
                            </label>
                            <label class="radio-option">
                                <input type="radio" name="commission_decision" value="podrzava_djelimicno" {{ old('commission_decision', $application->commission_decision) === 'podrzava_djelimicno' ? 'checked' : '' }}>
                                <span>b. Podržava djelimično</span>
                            </label>
                            <label class="radio-option">
                                <input type="radio" name="commission_decision" value="odbija" {{ old('commission_decision', $application->commission_decision) === 'odbija' ? 'checked' : '' }}>
                                <span>c. Odbija</span>
                            </label>
                        </div>
                        <div style="margin-top: 16px;">
                            <label class="form-label">Iznos odobrenih sredstava:</label>
                            <input 
                                type="number" 
                                name="approved_amount" 
                                class="form-control amount-input" 
                                step="0.01" 
                                min="0"
                                value="{{ old('approved_amount', $application->approved_amount) }}"
                                placeholder="0.00">
                        </div>
                    @elseif($commissionMember->position === 'predsjednik' && (!isset($allMembersEvaluated) || !$allMembersEvaluated))
                        {{-- Predsjednik vidi poruku da mora sačekati da svi članovi ocjene --}}
                        <div style="padding: 16px; background: #fef3c7; border-radius: 8px; margin-top: 12px; border: 1px solid #fbbf24;">
                            <div style="color: #92400e; font-weight: 600; margin-bottom: 8px;">
                                ⚠️ Zaključak komisije može se donijeti tek kada svi članovi komisije ocjene prijavu.
                            </div>
                            <div style="color: #78350f; font-size: 13px;">
                                Trenutno: {{ $evaluatedMemberIds ?? 0 }} / {{ $totalMembers ?? 0 }} članova je ocjenilo prijavu.
                            </div>
                        </div>
                    @else
                        {{-- Ostali članovi komisije vide read-only prikaz zaključka --}}
                        <div style="padding: 16px; background: #f9fafb; border-radius: 8px; margin-top: 12px;">
                            @php
                                $decisionLabels = [
                                    'podrzava_potpuno' => 'a. Podržava u potpunosti',
                                    'podrzava_djelimicno' => 'b. Podržava djelimično',
                                    'odbija' => 'c. Odbija'
                                ];
                                $currentDecision = $application->commission_decision;
                            @endphp
                            @if($currentDecision)
                                <div style="margin-bottom: 12px;">
                                    <strong>{{ $decisionLabels[$currentDecision] ?? 'Nije doneseno' }}</strong>
                                </div>
                                @if($application->approved_amount)
                                    <div style="margin-bottom: 12px;">
                                        <strong>Iznos odobrenih sredstava:</strong> {{ number_format($application->approved_amount, 2) }} €
                                    </div>
                                @endif
                            @else
                                <div style="margin-bottom: 12px; color: #6b7280;">
                                    <em>Zaključak još nije donesen. Predsjednik komisije će donijeti zaključak nakon što svi članovi ocjene prijavu.</em>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>

                <!-- 5. Obrazloženje -->
                <div class="form-section justification-section">
                    <label class="form-label form-label-large">5. Obrazloženje:</label>
                    <textarea 
                        name="justification" 
                        class="form-control" 
                        rows="6" 
                        placeholder="Unesite obrazloženje ocjene...">{{ old('justification', $existingScore?->justification ?? $application->commission_justification) }}</textarea>
                </div>

                <!-- 6. Ostale napomene -->
                <div class="form-section">
                    <label class="form-label form-label-large">6. Ostale napomene:</label>
                    
                    @php
                        // Prikupi napomene svih članova koji su ih dali, zadržavajući redoslijed iz $allMembers
                        // $allMembers je već sortiran tako da predsjednik bude prvi, zatim ostali članovi po redoslijedu
                        $membersWithNotes = [];
                        
                        foreach($allMembers as $member) {
                            $memberScore = $allScores->get($member->id);
                            if ($memberScore && $memberScore->notes && trim($memberScore->notes) !== '') {
                                $membersWithNotes[] = [
                                    'member' => $member,
                                    'notes' => $memberScore->notes
                                ];
                            }
                        }
                        
                        // Provjeri da li je predsjednik zaključio prijavu
                        $isDecisionMade = $application->commission_decision !== null;
                        
                        // Trenutni član može unijeti napomene dok predsjednik ne zaključi prijavu
                        $canEditNotes = !$isDecisionMade;
                    @endphp
                    
                    @if(count($membersWithNotes) > 0)
                        {{-- Prikaži napomene članova koji su ih dali --}}
                        @foreach($membersWithNotes as $memberNote)
                            <div style="margin-bottom: 20px;">
                                <label class="form-label" style="font-weight: 600; color: #374151; margin-bottom: 8px;">
                                    Napomene - {{ $memberNote['member']->name }}
                                    @if($memberNote['member']->position === 'predsjednik')
                                        <span style="color: #6b7280; font-size: 12px;">(Predsjednik komisije)</span>
                                    @endif
                                </label>
                                <div style="padding: 12px; background: #f9fafb; border-radius: 8px; border: 1px solid #e5e7eb; white-space: pre-wrap;">
                                    {{ $memberNote['notes'] }}
                                </div>
                            </div>
                        @endforeach
                    @endif
                    
                    @if($canEditNotes)
                        {{-- Trenutni član može unijeti svoje napomene dok predsjednik ne zaključi prijavu --}}
                        <div style="margin-top: {{ count($membersWithNotes) > 0 ? '20px' : '0' }};">
                            <label class="form-label" style="font-weight: 600; color: #374151; margin-bottom: 8px;">
                                Moje napomene
                                @if(isset($isChairman) && $isChairman)
                                    <span style="color: #6b7280; font-size: 12px;">(Predsjednik komisije)</span>
                                @endif
                            </label>
                            <textarea 
                                name="notes" 
                                class="form-control" 
                                rows="6" 
                                placeholder="Unesite dodatne napomene...">{{ old('notes', $existingScore?->notes) }}</textarea>
                        </div>
                    @endif
                </div>

                <!-- Potpisi -->
                <div class="signature-section">
                    <div style="text-align: right; margin-bottom: 24px; font-size: 14px;">
                        Kotor, <input type="date" name="decision_date" class="form-control" style="display: inline-block; width: 150px; margin: 0 8px;" value="{{ old('decision_date', $application->commission_decision_date ? $application->commission_decision_date->format('Y-m-d') : '') }}">
                    </div>
                    <div class="signature-row">
                        @foreach($allMembers as $member)
                            <div class="signature-item">
                                <div style="font-weight: 600; margin-bottom: 8px; text-align: center;">
                                    {{ $member->position === 'predsjednik' ? 'Predsjednik Komisije' : 'Član ' . ($loop->index) }}:
                                </div>
                                <div style="margin-top: 40px; border-top: 1px solid #d1d5db; padding-top: 8px; text-align: center;">
                                    {{ $member->name }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div style="margin-top: 32px; text-align: center;">
                    @if(isset($isChairman) && $isChairman && isset($hasCompletedEvaluation) && $hasCompletedEvaluation)
                        {{-- Predsjednik kada je već ocjenio - može mijenjati sekciju 2 --}}
                        <button type="submit" class="btn-primary">Sačuvaj izmjene</button>
                        @if(isset($allMembersEvaluated) && $allMembersEvaluated)
                            {{-- Dugme za zaključak se prikazuje samo kada su svi članovi ocjenili --}}
                            <a href="{{ route('evaluation.chairman-review', $application) }}" class="btn-primary" style="margin-left: 12px; text-decoration: none; display: inline-block;">
                                Zaključak komisije
                            </a>
                        @endif
                        <a href="{{ route('evaluation.index') }}" style="margin-left: 12px; color: #6b7280; text-decoration: none;">Otkaži</a>
                    @elseif(isset($hasCompletedEvaluation) && $hasCompletedEvaluation && isset($allMembersEvaluated) && $allMembersEvaluated)
                        {{-- Kada su svi članovi ocjenili, ostali članovi vide formu u read-only modu --}}
                        <div style="padding: 16px; background: #f0f9ff; border-radius: 8px; margin-bottom: 16px; border: 1px solid #0ea5e9;">
                            <div style="color: #0c4a6e; font-weight: 600; margin-bottom: 8px;">
                                ℹ️ Svi članovi komisije su ocjenili ovu prijavu. Forma je dostupna samo za pregled.
                            </div>
                        </div>
                        <a href="{{ route('evaluation.index') }}" class="btn-primary" style="text-decoration: none; display: inline-block;">
                            Nazad na listu
                        </a>
                    @else
                        <button type="submit" class="btn-primary">Ocijeni</button>
                        <a href="{{ route('evaluation.index') }}" style="margin-left: 12px; color: #6b7280; text-decoration: none;">Otkaži</a>
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function updateAverages() {
        // Ova funkcija će se pozivati kada se promijene ocjene
        // Za sada, prosječne ocjene se računaju na serveru
        // Možemo dodati JavaScript za real-time izračun ako je potrebno
    }
</script>
@endsection
