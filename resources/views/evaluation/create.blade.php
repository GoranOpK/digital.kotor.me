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
</style>

<div class="evaluation-page">
    <div class="container mx-auto px-4">
        <div class="page-header">
            <h1>Ocjenjivanje prijave</h1>
            <p class="subtitle">{{ $application->business_plan_name }}</p>
        </div>

        @if($existingScore)
        <div class="warning-box">
            <strong>Ocjena je već unesena.</strong> Možete je izmijeniti ispod.
        </div>
        @endif

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
                    <div style="font-size: 12px; color: #6b7280; margin-top: 8px; margin-left: 24px;">
                        *ukoliko je odgovor „Ne", odbiti aplikaciju
                    </div>
                    @error('documents_complete')
                        <div class="error-message">{{ $message }}</div>
                    @enderror
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
                                                $isCurrentMember = $member->id === $commissionMember->id;
                                            @endphp
                                            @if($isCurrentMember)
                                                <input 
                                                    type="number" 
                                                    name="criterion_{{ $num }}" 
                                                    class="score-input" 
                                                    min="1" 
                                                    max="5" 
                                                    value="{{ old("criterion_{$num}", $currentValue) }}"
                                                    required
                                                    onchange="updateAverages()">
                                            @else
                                                <span class="score-display">
                                                    {{ $currentValue ? $currentValue : '—' }}
                                                </span>
                                            @endif
                                        </td>
                                    @endforeach
                                    <td class="average-col" id="avg_{{ $num }}">
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
                                <td class="average-col" id="final_score">
                                    <strong>{{ $finalScore > 0 ? number_format($finalScore, 2) : '—' }}</strong>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="info-box" style="margin-top: 16px; font-size: 12px;">
                        * Prosječna ocjena za svaki kriterijum se dobija odnosom zbira ocjena svih članova Komisije i broja članova Komisije.<br>
                        Konačna ocjena je zbir svih prosječnih ocjena po kriterijumima.
                    </div>

                    <div class="warning-box" style="margin-top: 16px;">
                        <strong>Napomena:</strong> Biznis planovi sa ukupnim brojem bodova ispod 30 se neće podržati.
                    </div>

                    @for($i = 1; $i <= 10; $i++)
                        @error("criterion_{$i}")
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                    @endfor
                </div>

                <!-- 4. Zaključak komisije -->
                <div class="form-section">
                    <label class="form-label form-label-large">4. Na bazi konačne ocjene Komisija donosi zaključak da se biznis plan:</label>
                    @if($commissionMember->position === 'predsjednik')
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
                    @else
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
                                <strong>{{ $currentDecision ? $decisionLabels[$currentDecision] ?? 'Nije doneseno' : 'Nije doneseno' }}</strong>
                            </div>
                            @if($application->approved_amount)
                                <div>
                                    <strong>Iznos odobrenih sredstava:</strong> {{ number_format($application->approved_amount, 2) }} €
                                </div>
                            @endif
                            <div style="font-size: 12px; color: #6b7280; margin-top: 8px;">
                                (Samo predsjednik komisije može donijeti zaključak)
                            </div>
                        </div>
                    @endif
                </div>

                <!-- 5. Obrazloženje -->
                <div class="form-section">
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
                    <textarea 
                        name="notes" 
                        class="form-control" 
                        rows="6" 
                        placeholder="Unesite dodatne napomene...">{{ old('notes', $existingScore?->notes) }}</textarea>
                </div>

                <!-- Potpisi -->
                <div class="signature-section">
                    <div style="text-align: right; margin-bottom: 24px; font-size: 14px;">
                        Kotor, <input type="date" name="decision_date" class="form-control" style="display: inline-block; width: 150px; margin: 0 8px;" value="{{ old('decision_date', $application->commission_decision_date ? $application->commission_decision_date->format('Y-m-d') : '') }}">
                    </div>
                    <div class="signature-row">
                        @foreach($allMembers as $member)
                            <div class="signature-item">
                                <div style="font-weight: 600; margin-bottom: 8px;">
                                    {{ $member->position === 'predsjednik' ? 'Predsjednik Komisije' : 'Član ' . ($loop->index) }}:
                                </div>
                                <div style="margin-top: 40px; border-top: 1px solid #d1d5db; padding-top: 8px;">
                                    {{ $member->name }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div style="margin-top: 32px; text-align: center;">
                    <button type="submit" class="btn-primary">Sačuvaj ocjenu</button>
                    <a href="{{ route('evaluation.index') }}" style="margin-left: 12px; color: #6b7280; text-decoration: none;">Otkaži</a>
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
