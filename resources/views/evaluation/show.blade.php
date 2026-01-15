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
    .form-control-readonly {
        background: #f9fafb;
        border: 1px solid #e5e7eb;
        color: #6b7280;
        padding: 10px 14px;
        border-radius: 8px;
        font-size: 14px;
        width: 100%;
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
        border: 1px solid #e5e7eb;
        padding: 12px 8px;
        text-align: center;
    }
    .evaluation-table th {
        background: #f9fafb;
        font-weight: 600;
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
        padding: 12px;
    }
    .evaluation-table .score-display {
        color: #111827;
        font-weight: 500;
    }
    .evaluation-table .average-col {
        background: #f0f9ff;
        font-weight: 600;
        color: var(--primary);
    }
    .evaluation-table .final-score-row {
        background: var(--primary);
        color: #fff;
        font-weight: 700;
    }
    .evaluation-table .final-score-row td {
        background: var(--primary);
        color: #fff;
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
        text-decoration: none;
        display: inline-block;
    }
    .btn-primary:hover {
        background: var(--primary-dark);
    }
    .readonly-value {
        padding: 12px;
        background: #f9fafb;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        color: #111827;
        font-size: 14px;
        min-height: 100px;
        white-space: pre-wrap;
    }
    @media print {
        * {
            margin: 0;
            padding: 0;
        }
        body {
            background: #fff;
            font-family: Arial, sans-serif;
            font-size: 12pt;
            line-height: 1.5;
        }
        .container {
            padding: 0;
            max-width: 100%;
        }
        .evaluation-page {
            background: #fff;
            padding: 0;
        }
        .page-header {
            display: none !important;
        }
        .form-card {
            box-shadow: none;
            padding: 0;
            background: #fff;
            border: none;
        }
        .form-title {
            text-align: center;
            font-size: 14pt;
            font-weight: bold;
            margin-bottom: 4pt;
            text-transform: uppercase;
        }
        .form-subtitle {
            text-align: center;
            font-size: 10pt;
            margin-bottom: 16pt;
            font-style: italic;
        }
        .form-section {
            margin-bottom: 16pt;
        }
        .form-label,
        .form-label-large {
            font-size: 11pt;
            font-weight: bold;
            margin-bottom: 4pt;
        }
        .form-control-readonly {
            border: none;
            border-bottom: 1px solid #000;
            background: transparent;
            padding: 2pt 0;
            font-size: 11pt;
            border-radius: 0;
        }
        .readonly-value {
            border: 1px solid #000;
            background: transparent;
            padding: 4pt;
            font-size: 11pt;
            min-height: 60pt;
            white-space: pre-wrap;
        }
        .info-box {
            background: transparent;
            border: none;
            border-left: none;
            padding: 8pt 0;
            margin-bottom: 12pt;
            font-size: 10pt;
        }
        .warning-box {
            background: transparent;
            border: none;
            border-left: none;
            padding: 8pt 0;
            margin-bottom: 12pt;
            font-size: 10pt;
        }
        .evaluation-table {
            width: 100%;
            border-collapse: collapse;
            margin: 12pt 0;
            font-size: 10pt;
            page-break-inside: avoid;
        }
        .evaluation-table th,
        .evaluation-table td {
            border: 1px solid #000;
            padding: 6pt 4pt;
            text-align: center;
        }
        .evaluation-table th {
            background: #f0f0f0;
            font-weight: bold;
        }
        .evaluation-table .criterion-col {
            text-align: left;
            padding: 6pt;
        }
        .evaluation-table .average-col {
            background: #f0f0f0;
            font-weight: bold;
        }
        .evaluation-table .final-score-row {
            background: #000;
            color: #fff;
            font-weight: bold;
        }
        .evaluation-table .final-score-row td {
            background: #000;
            color: #fff;
        }
        .signature-section {
            margin-top: 32pt;
            padding-top: 16pt;
            border-top: 1px solid #000;
            page-break-inside: avoid;
        }
        .signature-row {
            display: flex;
            justify-content: space-between;
            margin-top: 24pt;
        }
        .signature-item {
            width: 140pt;
            text-align: center;
        }
        .signature-item > div:first-child {
            font-weight: bold;
            margin-bottom: 4pt;
        }
        .signature-item > div:last-child {
            margin-top: 32pt;
            border-top: 1px solid #000;
            padding-top: 4pt;
        }
        .btn-primary,
        button[type="button"],
        a[href] {
            display: none !important;
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
            <h1>Pregled ocjene</h1>
            <p class="subtitle">{{ $application->business_plan_name }}</p>
        </div>

        <!-- Forma za prikaz ocjene -->
        <div class="form-card">
            <div class="form-title">
                LISTA ZA OCJENJIVANJE BIZNIS PLANOVA
            </div>
            <div class="form-subtitle">
                (Popunjava Komisija za raspodjelu sredstava za podršku ženskom preduzetništvu)
            </div>

            <!-- 1. Naziv biznis plana -->
            <div class="form-section">
                <label class="form-label form-label-large">1. Naziv biznis plana:</label>
                <input type="text" class="form-control-readonly" value="{{ $application->business_plan_name }}" readonly>
            </div>

            <!-- 2. Dostavljena su sva potrebna dokumenta? -->
            <div class="form-section">
                <label class="form-label form-label-large">2. Dostavljena su sva potrebna dokumenta?</label>
                <div style="padding: 12px; background: #f9fafb; border-radius: 8px; margin-top: 12px;">
                    <strong>{{ $evaluationScore->documents_complete ? 'a. Da' : 'b. Ne*' }}</strong>
                    @if(!$evaluationScore->documents_complete)
                        <div style="font-size: 12px; color: #6b7280; margin-top: 4px;">
                            *ukoliko je odgovor „Ne", odbiti aplikaciju
                        </div>
                    @endif
                </div>
            </div>

            <!-- 3. Ocjena biznis plana u brojkama -->
            <div class="form-section">
                <label class="form-label form-label-large">3. Ocjena biznis plana u brojkama:</label>
                
                <div class="info-box">
                    <strong>KRITERIJUMI ZA OCJENU</strong><br>
                    (Član 18 stav 2 Odluke)<br><br>
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
                                        @endphp
                                        <span class="score-display">
                                            {{ $currentValue ? $currentValue : '—' }}
                                        </span>
                                    </td>
                                @endforeach
                                <td class="average-col">
                                    {{ isset($averageScores[$num]) ? number_format($averageScores[$num], 2) : '—' }}
                                </td>
                            </tr>
                        @endforeach
                        <tr class="final-score-row">
                            <td class="criterion-col" style="text-align: center;">
                                <strong>KONAČNA OCJENA:</strong>
                            </td>
                            @foreach($allMembers as $member)
                                <td>
                                    @php
                                        $memberScore = $allScores->get($member->id);
                                        $memberTotal = $memberScore ? $memberScore->calculateTotalScore() : 0;
                                    @endphp
                                    <strong>{{ $memberTotal > 0 ? $memberTotal : '—' }}</strong>
                                </td>
                            @endforeach
                            <td class="average-col">
                                <strong>{{ $finalScore > 0 ? number_format($finalScore, 2) : '—' }}</strong>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div class="info-box" style="margin-top: 16px; font-size: 12px;">
                    * Prosječna ocjena za svaki kriterijum se dobija odnosom zbira ocjena svih članova Komisije i broja članova Komisije.<br>
                    Konačna ocjena je zbir svih prosječnih ocjena po kriterijumima.
                </div>

                <div class="info-box" style="margin-top: 16px; background: #fef3c7; border-left-color: #f59e0b;">
                    <strong>Napomena:</strong> Biznis planovi sa ukupnim brojem bodova ispod 30 se neće podržati.
                </div>
            </div>

            <!-- 4. Zaključak komisije -->
            <div class="form-section">
                <label class="form-label form-label-large">4. Na bazi konačne ocjene Komisija donosi zaključak da se biznis plan:</label>
                <div style="padding: 16px; background: #f9fafb; border-radius: 8px; margin-top: 12px;">
                    @php
                        $decisionLabels = [
                            'podrzava_potpuno' => 'a. Podržava u potpunosti',
                            'podrzava_djelimicno' => 'b. Podržava djelimično',
                            'odbija' => 'c. Odbija'
                        ];
                        $currentDecision = $application->commission_decision;
                    @endphp
                    <div style="margin-bottom: 12px;">
                        <strong>{{ $currentDecision ? ($decisionLabels[$currentDecision] ?? 'Nije doneseno') : 'Nije doneseno' }}</strong>
                    </div>
                    @if($application->approved_amount)
                        <div>
                            <strong>Iznos odobrenih sredstava:</strong> {{ number_format($application->approved_amount, 2) }} €
                        </div>
                    @endif
                </div>
            </div>

            <!-- 5. Obrazloženje -->
            <div class="form-section">
                <label class="form-label form-label-large">5. Obrazloženje:</label>
                <div class="readonly-value">
                    {{ $evaluationScore->justification ?? $application->commission_justification ?? 'Nema obrazloženja' }}
                </div>
            </div>

            <!-- 6. Ostale napomene -->
            <div class="form-section">
                <label class="form-label form-label-large">6. Ostale napomene:</label>
                <div class="readonly-value">
                    {{ $evaluationScore->notes ?? 'Nema napomena' }}
                </div>
            </div>

            <!-- Potpisi -->
            <div class="signature-section">
                <div style="text-align: right; margin-bottom: 24px; font-size: 14px;">
                    Kotor, {{ $application->commission_decision_date ? $application->commission_decision_date->format('d.m.Y') : '_______________' }} god.
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
                <a href="{{ route('evaluation.create', $application) }}" class="btn-primary">Izmijeni</a>
                <button type="button" onclick="window.print()" class="btn-primary" style="margin-left: 12px;">Štampaj</button>
                <a href="{{ route('evaluation.index') }}" style="margin-left: 12px; color: #6b7280; text-decoration: none;">Nazad na listu</a>
            </div>
        </div>
    </div>
</div>
@endsection
