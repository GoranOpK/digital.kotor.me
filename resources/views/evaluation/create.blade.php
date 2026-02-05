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
        /* Sekcija "Ostale napomene" uvijek počinje na novoj stranici (nakon tabele) - dodaj gornju marginu */
        .form-section-notes {
            padding-top: 20mm !important;
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
            margin: 20mm;
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
            @php
                $isRejected = $application->status === 'rejected';
                $isApplicant = $isApplicant ?? false;
                $competition = $application->competition;
                $daysRemaining = $competition ? $competition->getDaysUntilEvaluationDeadline() : null;
                $isDeadlinePassed = $competition ? $competition->isEvaluationDeadlinePassed() : false;
            @endphp
            
            @if($competition && $competition->closed_at && !$isDeadlinePassed && $daysRemaining !== null)
                <div class="alert alert-warning" style="background: #fef3c7; border: 1px solid #f59e0b; color: #92400e; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px;">
                    <strong>⚠️ Upozorenje:</strong> Preostalo vremena za ocjenjivanje i donošenje odluke: <strong>{{ $daysRemaining }} {{ $daysRemaining == 1 ? 'dan' : ($daysRemaining < 5 ? 'dana' : 'dana') }}</strong>. Komisija je dužna donijeti odluku u roku od 30 dana od dana zatvaranja prijava na konkurs.
                </div>
            @endif
            
            @if($isDeadlinePassed)
                <div class="alert alert-danger" style="background: #fee2e2; border: 1px solid #ef4444; color: #991b1b; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px;">
                    <strong>❌ Rok istekao:</strong> Rok za ocjenjivanje i donošenje odluke je istekao. Komisija je dužna donijeti odluku u roku od 30 dana od dana zatvaranja prijava na konkurs.
                </div>
            @endif
            
            <form method="POST" action="{{ route('evaluation.store', $application) }}" id="evaluationForm" @if($isRejected || $isApplicant || $isDeadlinePassed) onsubmit="event.preventDefault(); return false;" @endif>
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
                    
                    @if($commissionMember && $commissionMember->position === 'predsjednik')
                        {{-- Predsjednik može označiti --}}
                        <div class="radio-group">
                            <label class="radio-option">
                                <input type="radio" name="documents_complete" value="1" {{ old('documents_complete', $existingScore?->documents_complete ?? true) ? 'checked' : '' }} @if($isRejected || $isApplicant) disabled @else required @endif>
                                <span>a. Da</span>
                            </label>
                            <label class="radio-option">
                                <input type="radio" name="documents_complete" value="0" {{ old('documents_complete') === '0' || ($existingScore && !$existingScore->documents_complete) ? 'checked' : '' }} @if($isRejected || $isApplicant) disabled @else required @endif>
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
                                                $isCurrentMember = $commissionMember && $member->id === $commissionMember->id;
                                                // Provjeri da li je trenutni član završio ocjenjivanje
                                                $hasCompletedEvaluation = isset($hasCompletedEvaluation) ? $hasCompletedEvaluation : ($existingScore && $existingScore->criterion_1 !== null);
                                                // Provjeri da li je drugi član završio ocjenjivanje
                                                $otherMemberCompleted = $memberScore && $memberScore->criterion_1 !== null;
                                                // Prikaz ocjena ostalih članova dozvoljen je tek kada svi članovi završe ocjenjivanje
                                                $canViewAllScores = isset($allMembersEvaluated) && $allMembersEvaluated;
                                            @endphp
                                            @if($isCurrentMember)
                                                {{-- Trenutni član UVIJEK vidi svoje ocjene; ostale tek kada svi ocijene --}}
                                                @if(isset($hasCompletedEvaluation) && $hasCompletedEvaluation)
                                                    {{-- Član je ocjenio - prikaži svoju ocjenu (read-only) --}}
                                                    <span class="score-display">
                                                        {{ $currentValue ? $currentValue : '—' }}
                                                    </span>
                                                @else
                                                    {{-- Može unijeti ili mijenjati --}}
                                                    {{-- Ako je predsjednik i documents_complete je "Ne", ne treba required --}}
                                                    @php
                                                        $isDocumentsCompleteNo = false;
                                                        if (isset($isChairman) && $isChairman) {
                                                            $chairmanScore = $commissionMember ? $allScores->get($commissionMember->id) : null;
                                                            $isDocumentsCompleteNo = $chairmanScore && $chairmanScore->documents_complete === false;
                                                        }
                                                    @endphp
                                                    <input 
                                                        type="number" 
                                                        name="criterion_{{ $num }}" 
                                                        class="score-input" 
                                                        min="1" 
                                                        max="5" 
                                                        value="{{ old("criterion_{$num}", $currentValue) }}"
                                                        @if(!$isDocumentsCompleteNo && !$isRejected && !($isApplicant ?? false)) required @endif
                                                        @if($isRejected || ($isApplicant ?? false)) disabled @endif
                                                        onchange="updateAverages()">
                                                @endif
                                            @else
                                                {{-- Ostali članovi - prikaži ocjenu tek kada svi članovi završe ocjenjivanje, ili ako je predsjednik i već je ocjenio --}}
                                                @php
                                                    $allMembersEvaluatedFlag = isset($allMembersEvaluated) ? $allMembersEvaluated : false;
                                                @endphp
                                                @if($allMembersEvaluatedFlag || $canViewAllScores)
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
                                            // Prikaži prosječnu ocjenu tek kada svi članovi završe ocjenjivanje
                                            $allMembersEvaluatedFlag = isset($allMembersEvaluated) ? $allMembersEvaluated : false;
                                        @endphp
                                        @if($allMembersEvaluatedFlag && isset($averageScores[$num]))
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
                                                $isCurrentMember = $commissionMember && $member->id === $commissionMember->id;
                                                $allMembersEvaluatedFlag = isset($allMembersEvaluated) ? $allMembersEvaluated : false;
                                            @endphp
                                        @if($isCurrentMember)
                                            {{-- Trenutni član UVIJEK vidi svoju konačnu ocjenu --}}
                                            <strong>{{ $memberTotal > 0 ? $memberTotal : '—' }}</strong>
                                        @else
                                            {{-- Ostali članovi - prikaži konačne ocjene tek kada svi članovi završe ocjenjivanje --}}
                                            @if($allMembersEvaluatedFlag)
                                                <strong>{{ $memberTotal > 0 ? $memberTotal : '—' }}</strong>
                                            @else
                                                <strong style="color: #d1d5db;">—</strong>
                                            @endif
                                        @endif
                                    </td>
                                @endforeach
                                <td class="average-col" id="final_score" style="font-weight: bold !important;">
                                    @php
                                        // Prikaži konačnu prosječnu ocjenu tek kada svi članovi završe ocjenjivanje
                                        $allMembersEvaluatedFlag = isset($allMembersEvaluated) ? $allMembersEvaluated : false;
                                    @endphp
                                    @if($allMembersEvaluatedFlag && $finalScore > 0)
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

                <!-- 4. Ostale napomene -->
                <div class="form-section form-section-notes">
                    <label class="form-label form-label-large">4. Ostale napomene:</label>
                    
                    @php
                        // Prikupi napomene svih članova koji su ih dali, zadržavajući redoslijed iz $allMembers
                        // $allMembers je već sortiran tako da predsjednik bude prvi, zatim ostali članovi po redoslijedu
                        $membersWithNotes = [];
                        
                        // Prvo dodaj predsjednika ako ima napomenu
                        // Eksplicitno pronađi predsjednika iz sortirane kolekcije
                        $chairman = null;
                        foreach($allMembers as $member) {
                            if ($member->position === 'predsjednik') {
                                $chairman = $member;
                                break;
                            }
                        }
                        
                        if ($chairman) {
                            $chairmanScore = $allScores->get($chairman->id);
                            if ($chairmanScore && $chairmanScore->notes && trim($chairmanScore->notes) !== '') {
                                $membersWithNotes[] = [
                                    'member' => $chairman,
                                    'notes' => $chairmanScore->notes,
                                    'isChairman' => true
                                ];
                            }
                        }
                        
                        // Zatim dodaj ostale članove po redoslijedu iz $allMembers
                        foreach($allMembers as $member) {
                            if ($member->position !== 'predsjednik') {
                                $memberScore = $allScores->get($member->id);
                                if ($memberScore && $memberScore->notes && trim($memberScore->notes) !== '') {
                                    $membersWithNotes[] = [
                                        'member' => $member,
                                        'notes' => $memberScore->notes,
                                        'isChairman' => false
                                    ];
                                }
                            }
                        }
                        
                        // Eksplicitno sortiraj da osiguramo da predsjednik bude prvi
                        usort($membersWithNotes, function($a, $b) {
                            if ($a['isChairman'] && !$b['isChairman']) {
                                return -1; // a je predsjednik, ide prvi
                            } elseif (!$a['isChairman'] && $b['isChairman']) {
                                return 1; // b je predsjednik, ide prvi
                            }
                            return 0; // zadrži redoslijed za ostale
                        });
                        
                        // Provjeri da li je predsjednik zaključio prijavu
                        $isDecisionMade = $application->commission_decision !== null;
                        
                        // Trenutni član može unijeti napomene dok predsjednik ne zaključi prijavu
                        $canEditNotes = !$isDecisionMade;
                    @endphp
                    
                    @php
                        // Provjeri da li trenutni član već ima napomenu u listi
                        $currentMemberHasNote = false;
                        foreach($membersWithNotes as $note) {
                            if ($commissionMember && $note['member']->id === $commissionMember->id) {
                                $currentMemberHasNote = true;
                                break;
                            }
                        }

                        // Tuđe napomene vidljive tek kada svi članovi završe ocjenjivanje
                        // Trenutni član UVIJEK vidi svoju napomenu
                        if (!($allMembersEvaluated ?? false)) {
                            $membersWithNotes = array_filter($membersWithNotes, function($note) use ($commissionMember) {
                                return $commissionMember && $note['member']->id === $commissionMember->id;
                            });
                        }
                    @endphp
                    
                    @if(count($membersWithNotes) > 0)
                        {{-- Prikaži napomene članova koji su ih dali --}}
                        @foreach($membersWithNotes as $memberNote)
                            <div style="margin-bottom: 20px;">
                                <label class="form-label" style="font-weight: 600; color: #374151; margin-bottom: 8px;">
                                    @if($memberNote['member']->position === 'predsjednik')
                                        Napomena Predsjednik komisije ({{ $memberNote['member']->name }})
                                    @else
                                        Napomene - {{ $memberNote['member']->name }}
                                    @endif
                                </label>
                                @if($canEditNotes && $commissionMember && $memberNote['member']->id === $commissionMember->id)
                                    {{-- Trenutni član može editovati svoju napomenu --}}
                                    <textarea 
                                        name="notes" 
                                        class="form-control" 
                                        rows="6" 
                                        placeholder="Unesite dodatne napomene..."
                                        @if($isRejected || $isApplicant) disabled @endif>{{ old('notes', $memberNote['notes']) }}</textarea>
                                @else
                                    {{-- Read-only prikaz za ostale članove --}}
                                    <div style="padding: 12px; background: #f9fafb; border-radius: 8px; border: 1px solid #e5e7eb; white-space: pre-wrap;">
                                        {{ $memberNote['notes'] }}
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    @endif
                    
                    @if($canEditNotes && !$currentMemberHasNote && $commissionMember)
                        {{-- Polje za unos napomena ako trenutni član još nema napomenu --}}
                        <div style="margin-top: {{ count($membersWithNotes) > 0 ? '20px' : '0' }};">
                            <label class="form-label" style="font-weight: 600; color: #374151; margin-bottom: 8px;">
                                @if(isset($isChairman) && $isChairman)
                                    Napomena Predsjednik komisije ({{ $commissionMember->name }})
                                @else
                                    Napomena Član komisije ({{ $commissionMember->name }})
                                @endif
                            </label>
                            <textarea 
                                name="notes" 
                                class="form-control" 
                                rows="6" 
                                placeholder="Unesite dodatne napomene..."
                                @if($isRejected || $isApplicant) disabled @endif>{{ old('notes', $existingScore?->notes) }}</textarea>
                        </div>
                    @endif
                </div>

                <div style="margin-top: 32px; text-align: center;">
                    @php
                        // Provjeri da li trenutni član može editovati napomene
                        $isDecisionMade = $application->commission_decision !== null;
                        $canEditNotesValue = !$isDecisionMade;
                        $isRejected = $application->status === 'rejected';
                    @endphp
                    
                    @if($isRejected)
                        {{-- Ako je prijava odbijena, prikaži samo read-only poruku --}}
                        <div style="padding: 16px; background: #fee2e2; border-radius: 8px; margin-bottom: 16px; border: 1px solid #ef4444;">
                            <div style="color: #991b1b; font-weight: 600; margin-bottom: 8px;">
                                ⚠️ Ova prijava je odbijena i ne može se editovati.
                            </div>
                            @if($application->rejection_reason)
                                <div style="color: #7f1d1d; font-size: 13px; margin-top: 4px;">
                                    Razlog: {{ rtrim($application->rejection_reason, '.') }}
                                </div>
                            @endif
                        </div>
                        @if($isApplicant)
                            <a href="{{ route('applications.show', $application) }}" class="btn-primary" style="text-decoration: none; display: inline-block;">
                                Nazad na Status prijave
                            </a>
                        @else
                            <a href="{{ route('evaluation.index', ['filter' => 'rejected']) }}" class="btn-primary" style="text-decoration: none; display: inline-block;">
                                Nazad na listu
                            </a>
                        @endif
                    @elseif($isChairman && $hasCompletedEvaluation)
                        {{-- Predsjednik kada je već ocjenio - može mijenjati sekciju 2 --}}
                        @if($allMembersEvaluated ?? false)
                            <button type="button" onclick="window.print();" class="btn-primary" style="margin-right: 12px; background: #6b7280;">Štampaj</button>
                        @endif
                        <button type="submit" class="btn-primary" @if($isDeadlinePassed) disabled style="opacity: 0.5; cursor: not-allowed;" @endif>Ocjene</button>
                        <a href="{{ route('evaluation.index') }}" style="margin-left: 12px; color: #6b7280; text-decoration: none;">Otkaži</a>
                    @elseif($hasCompletedEvaluation && $canEditNotesValue && !$isChairman)
                        {{-- Član koji je već ocjenio ali može editovati napomene --}}
                        {{-- Ova provjera mora biti PRIJE provjere za sve ocjenjene --}}
                        @if($allMembersEvaluated ?? false)
                            <button type="button" onclick="window.print();" class="btn-primary" style="margin-right: 12px; background: #6b7280;">Štampaj</button>
                        @endif
                        <button type="submit" class="btn-primary" @if($isDeadlinePassed) disabled style="opacity: 0.5; cursor: not-allowed;" @endif>Ocjene</button>
                        <a href="{{ route('evaluation.index') }}" style="margin-left: 12px; color: #6b7280; text-decoration: none;">Otkaži</a>
                    @elseif($hasCompletedEvaluation && $allMembersEvaluated)
                        {{-- Kada su svi članovi ocjenili, ostali članovi vide formu u read-only modu --}}
                        <div style="padding: 16px; background: #f0f9ff; border-radius: 8px; margin-bottom: 16px; border: 1px solid #0ea5e9;">
                            <div style="color: #0c4a6e; font-weight: 600; margin-bottom: 8px;">
                                ℹ️ Svi članovi komisije su ocjenili ovu prijavu. Forma je dostupna samo za pregled.
                            </div>
                        </div>
                        <button type="button" onclick="window.print();" class="btn-primary" style="margin-right: 12px; background: #6b7280;">Štampaj</button>
                        <a href="{{ route('evaluation.index') }}" class="btn-primary" style="text-decoration: none; display: inline-block;">
                            Nazad na listu
                        </a>
                    @elseif($hasCompletedEvaluation)
                        {{-- Član je završio ocjenjivanje (prije ostalih) - vidi svoje ocjene, dugme Ocjene --}}
                        @if($allMembersEvaluated ?? false)
                            <button type="button" onclick="window.print();" class="btn-primary" style="margin-right: 12px; background: #6b7280;">Štampaj</button>
                        @endif
                        <button type="submit" class="btn-primary" @if($isDeadlinePassed) disabled style="opacity: 0.5; cursor: not-allowed;" @endif>Ocjene</button>
                        <a href="{{ route('evaluation.index') }}" style="margin-left: 12px; color: #6b7280; text-decoration: none;">Otkaži</a>
                    @else
                        {{-- Član još nije završio ocjenjivanje --}}
                        <button type="submit" class="btn-primary" @if($isDeadlinePassed) disabled style="opacity: 0.5; cursor: not-allowed;" @endif>Ocijeni</button>
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
    
    // Debug - provjeri da li se forma submit-uje
    const form = document.getElementById('evaluationForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            console.log('=== FORM SUBMIT TRIGGERED ===');
            console.log('Form action:', this.action);
            console.log('Form method:', this.method);
            
            // Provjeri documents_complete
            const documentsComplete = document.querySelector('input[name="documents_complete"]:checked');
            if (documentsComplete) {
                console.log('documents_complete value:', documentsComplete.value);
                
                // Ako je documents_complete "Ne" (value="0"), ukloni required sa svih kriterijuma
                if (documentsComplete.value === '0') {
                    console.log('Removing required from all criterion inputs');
                    const criterionInputs = document.querySelectorAll('input[name^="criterion_"]');
                    criterionInputs.forEach(function(input) {
                        input.removeAttribute('required');
                    });
                }
            } else {
                console.log('documents_complete: NOT SELECTED');
            }
            
            // Ne blokiramo submit, samo logujemo i uklanjamo required ako je potrebno
        });
        
        // Takođe, dodaj event listener na radio button-e za documents_complete
        const documentsCompleteRadios = document.querySelectorAll('input[name="documents_complete"]');
        documentsCompleteRadios.forEach(function(radio) {
            radio.addEventListener('change', function() {
                if (this.value === '0') {
                    // Ako je označeno "Ne", ukloni required sa svih kriterijuma
                    const criterionInputs = document.querySelectorAll('input[name^="criterion_"]');
                    criterionInputs.forEach(function(input) {
                        input.removeAttribute('required');
                    });
                } else {
                    // Ako je označeno "Da", dodaj required na sve kriterijume
                    const criterionInputs = document.querySelectorAll('input[name^="criterion_"]');
                    criterionInputs.forEach(function(input) {
                        input.setAttribute('required', 'required');
                    });
                }
            });
        });
    }
</script>
@endsection
