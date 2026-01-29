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
        margin: 0;
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
    .form-card {
        background: #fff;
        border-radius: 16px;
        padding: 32px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    .scores-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 24px;
    }
    .scores-table th,
    .scores-table td {
        padding: 12px;
        text-align: center;
        border: 1px solid #e5e7eb;
    }
    .scores-table th {
        background: #f9fafb;
        font-weight: 600;
        color: #374151;
        font-size: 12px;
    }
    .scores-table td {
        font-size: 14px;
    }
    .scores-table .criterion-name {
        text-align: left;
        font-weight: 600;
        color: #111827;
        max-width: 300px;
    }
    .average-row {
        background: #f0f9ff;
        font-weight: 600;
    }
    .final-score {
        background: var(--primary);
        color: #fff;
        padding: 16px;
        border-radius: 8px;
        text-align: center;
        font-size: 24px;
        font-weight: 700;
        margin: 24px 0;
    }
    .form-group {
        margin-bottom: 24px;
    }
    .form-label {
        display: block;
        font-size: 14px;
        font-weight: 600;
        color: #374151;
        margin-bottom: 8px;
    }
    .form-control {
        width: 100%;
        padding: 10px 14px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        font-size: 14px;
    }
    .form-control:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(11, 61, 145, 0.1);
    }
    .radio-group {
        display: flex;
        gap: 24px;
        flex-wrap: wrap;
    }
    .radio-option {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .radio-option input[type="radio"] {
        width: 18px;
        height: 18px;
        cursor: pointer;
    }
    .radio-option label {
        font-size: 14px;
        color: #374151;
        cursor: pointer;
        margin: 0;
    }
    .btn-primary {
        background: var(--primary);
        color: #fff;
        padding: 12px 24px;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
    }
    .btn-primary:hover {
        background: var(--primary-dark);
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
    .alert-error {
        background: #fee2e2;
        border-color: #ef4444;
        color: #991b1b;
    }
    .signature-section {
        background: #f9fafb;
        padding: 20px;
        border-radius: 8px;
        margin-top: 24px;
    }
    .signature-item {
        padding: 8px 0;
        border-bottom: 1px solid #e5e7eb;
    }
    .signature-item:last-child {
        border-bottom: none;
    }
    .signed {
        color: #10b981;
        font-weight: 600;
    }
    .not-signed {
        color: #6b7280;
    }
</style>

<div class="evaluation-page">
    <div class="container mx-auto px-4">
        <div class="page-header">
            <h1>Pregled ocjena i zaključak komisije</h1>
        </div>

        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-error">
                {{ session('error') }}
            </div>
        @endif

        <!-- Informacije o prijavi -->
        <div class="info-card">
            <h2>1. Naziv biznis plana: {{ $application->business_plan_name }}</h2>
            <p><strong>Podnosilac:</strong> {{ $application->user->name ?? 'N/A' }}</p>
            <p><strong>Konkurs:</strong> {{ $application->competition->title ?? 'N/A' }}</p>
            <p><strong>Tip:</strong> {{ $application->applicant_type === 'preduzetnica' ? 'Preduzetnica' : 'DOO' }} - {{ $application->business_stage === 'započinjanje' ? 'Započinjanje' : 'Razvoj' }}</p>
        </div>

        <!-- Tabela sa svim ocjenama -->
        <div class="info-card">
            <h2>3. Ocjena biznis plana u brojkama</h2>
            
            <table class="scores-table">
                <thead>
                    <tr>
                        <th style="text-align: left;">KRITERIJUMI ZA OCJENU</th>
                        @foreach($allScores as $score)
                            <th>{{ $score->commissionMember->name }}<br>
                                @if($score->commissionMember->position === 'predsjednik')
                                    <span style="font-size: 10px; color: var(--primary);">(Predsjednik)</span>
                                @else
                                    <span style="font-size: 10px; color: #6b7280;">(Član)</span>
                                @endif
                            </th>
                        @endforeach
                        <th>Prosječna ocjena</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $criteria = [
                            1 => 'Obrazac biznis plana je detaljno popunjen sa svim neophodnim informacijama i jasno su precizirani proizvodi/usluge koje će se ponuditi na tržištu.',
                            2 => 'Biznis ideja je inovativna (stvaranje novog proizvoda/usluge, unaprijeđenje proizvoda/usluga, uvećan obim proizvodnje)',
                            3 => 'Jasno su identifikovani potencijalni kupci i njihove karakteristike.',
                            4 => 'Biznis plan će omogućiti samozapošljavanje i/ili zapošljavanje (stalno ili sezonsko) lica sa teritorije opštine Kotor.',
                            5 => 'Prepoznata je i navedena konkurencija, kao i slabosti i snage iste.',
                            6 => 'Jasno su navedeni potrebni resursi i identifikovani dobavljači.',
                            7 => 'Biznis ideja je finansijski održiva (jasno su prikazani očekivani prihodi i rashodi poslovanja).',
                            8 => 'Podaci o preduzetnici (preduzetnica posjeduje iskustvo, potrebna znanja i vještine, te svijest o preduzetničkim osobinama koje mora unaprijediti, preduzetnica planira raspored poslova uz identifikaciju osoba za njihovo obavljanje).',
                            9 => 'Razvijena matrica rizika je jasna i logična.',
                            10 => 'Usmeno obrazloženje biznis plana (preduzetnica je uvjerljiva i sigurna u svoju biznis ideju, pokazuje visoku motivisanost za realizaciju iste i spremno odgovara na sva pitanja).',
                        ];
                    @endphp
                    
                    @for($i = 1; $i <= 10; $i++)
                        <tr>
                            <td class="criterion-name">{{ $i }}. {{ $criteria[$i] }}</td>
                            @foreach($allScores as $score)
                                <td>{{ $score->{"criterion_{$i}"} ?? '-' }}</td>
                            @endforeach
                            <td class="average-row">{{ $criterionAverages[$i] ?? 0 }}</td>
                        </tr>
                    @endfor
                </tbody>
            </table>

            <div class="final-score">
                KONAČNA OCJENA: {{ $application->final_score ?? 0 }} / 50 poena
                @if(($application->final_score ?? 0) < 30)
                    <div style="font-size: 14px; margin-top: 8px; color: #fee2e2;">
                        ⚠ Ocjena ispod 30 - prijava se neće podržati
                    </div>
                @endif
            </div>
        </div>

        <!-- Zaključak komisije -->
        @if(!$application->signed_by_chairman)
        <div class="form-card">
            <h2 style="font-size: 20px; font-weight: 700; color: var(--primary); margin-bottom: 24px;">
                4. Na bazi konačne ocjene Komisija donosi zaključak da se biznis plan:
            </h2>

            <form method="POST" action="{{ route('evaluation.store-decision', $application) }}">
                @csrf

                <div class="form-group">
                    <div class="radio-group">
                        <div class="radio-option">
                            <input type="radio" name="commission_decision" value="podrzava_potpuno" id="decision_potpuno" {{ old('commission_decision', $application->commission_decision) === 'podrzava_potpuno' ? 'checked' : '' }} required>
                            <label for="decision_potpuno">a. Podržava u potpunosti</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" name="commission_decision" value="podrzava_djelimicno" id="decision_djelimicno" {{ old('commission_decision', $application->commission_decision) === 'podrzava_djelimicno' ? 'checked' : '' }} required>
                            <label for="decision_djelimicno">b. Podržava djelimično</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" name="commission_decision" value="odbija" id="decision_odbija" {{ old('commission_decision', $application->commission_decision) === 'odbija' ? 'checked' : '' }} required>
                            <label for="decision_odbija">c. Odbija</label>
                        </div>
                    </div>
                    @error('commission_decision')
                        <div style="color: #ef4444; font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Iznos odobrenih sredstava:</label>
                    <input type="number" name="approved_amount" class="form-control" value="{{ old('approved_amount', $application->approved_amount) }}" step="0.01" min="0" placeholder="0.00">
                </div>

                <div class="form-group">
                    <label class="form-label">5. Obrazloženje: *</label>
                    <textarea name="commission_justification" class="form-control" rows="6" required placeholder="Unesite obrazloženje zaključka komisije...">{{ old('commission_justification', $application->commission_justification) }}</textarea>
                    @error('commission_justification')
                        <div style="color: #ef4444; font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">4. Ostale napomene:</label>
                    <textarea name="commission_notes" class="form-control" rows="4" placeholder="Unesite ostale napomene...">{{ old('commission_notes', $application->commission_notes) }}</textarea>
                </div>

                <div style="margin-top: 24px; text-align: center;">
                    <button type="submit" class="btn-primary">Sačuvaj zaključak komisije</button>
                    <a href="{{ route('evaluation.index') }}" style="margin-left: 12px; color: #6b7280;">Nazad</a>
                </div>
            </form>
        </div>
        @else
        <!-- Prikaz zaključka ako je već donesen -->
        <div class="info-card">
            <h2>4. Zaključak komisije</h2>
            <p><strong>Zaključak:</strong> 
                @if($application->commission_decision === 'podrzava_potpuno')
                    Podržava u potpunosti
                @elseif($application->commission_decision === 'podrzava_djelimicno')
                    Podržava djelimično
                @else
                    Odbija
                @endif
            </p>
            @if($application->approved_amount)
                <p><strong>Iznos odobrenih sredstava:</strong> {{ number_format($application->approved_amount, 2, ',', '.') }} €</p>
            @endif
            <p><strong>Obrazloženje:</strong></p>
            <div style="background: #f9fafb; padding: 16px; border-radius: 8px; margin-top: 8px;">
                {{ $application->commission_justification }}
            </div>
            @if($application->commission_notes)
                <p style="margin-top: 16px;"><strong>Ostale napomene:</strong></p>
                <div style="background: #f9fafb; padding: 16px; border-radius: 8px; margin-top: 8px;">
                    {{ $application->commission_notes }}
                </div>
            @endif
            <p style="margin-top: 16px;"><strong>Datum donošenja zaključka:</strong> {{ $application->commission_decision_date ? $application->commission_decision_date->format('d.m.Y') : 'N/A' }}</p>
        </div>

        <!-- Potpisivanje -->
        <div class="info-card">
            <h2>Potpisivanje odluke</h2>
            
            <div class="signature-section">
                <div class="signature-item">
                    <strong>Predsjednik Komisije:</strong> 
                    <span class="{{ $application->signed_by_chairman ? 'signed' : 'not-signed' }}">
                        {{ $application->signed_by_chairman ? '✓ Potpisano' : 'Nije potpisano' }}
                    </span>
                </div>
                @php
                    $commission = $commissionMember->commission;
                    $members = $commission->members()->where('position', '!=', 'predsjednik')->get();
                    $signedMembers = $application->signed_by_members ?? [];
                @endphp
                @foreach($members as $index => $member)
                    <div class="signature-item">
                        <strong>Član {{ $index + 1 }}:</strong> {{ $member->name }}
                        <span class="{{ in_array($member->id, $signedMembers) ? 'signed' : 'not-signed' }}">
                            {{ in_array($member->id, $signedMembers) ? '✓ Potpisano' : 'Nije potpisano' }}
                        </span>
                        @if(!in_array($member->id, $signedMembers) && $commissionMember->id === $member->id)
                            <form method="POST" action="{{ route('evaluation.sign-decision', $application) }}" style="display: inline; margin-left: 12px;">
                                @csrf
                                <button type="submit" class="btn-primary" style="padding: 6px 12px; font-size: 12px;">Potpiši</button>
                            </form>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
        @endif

        <div style="text-align: center; margin-top: 24px;">
            <a href="{{ route('evaluation.index') }}" class="btn-primary" style="text-decoration: none; display: inline-block;">Nazad na listu</a>
        </div>
    </div>
</div>
@endsection

