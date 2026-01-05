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
    .form-label .description {
        font-size: 12px;
        font-weight: 400;
        color: #6b7280;
        margin-top: 4px;
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
    select.form-control {
        max-width: 200px;
    }
    .criterion-grid {
        display: grid;
        grid-template-columns: 1fr 150px;
        gap: 16px;
        align-items: center;
        padding: 16px;
        background: #f9fafb;
        border-radius: 8px;
        margin-bottom: 12px;
    }
    .criterion-info {
        display: flex;
        flex-direction: column;
    }
    .criterion-name {
        font-weight: 600;
        color: #111827;
        margin-bottom: 4px;
    }
    .criterion-number {
        font-size: 12px;
        color: #6b7280;
    }
    .criterion-score {
        display: flex;
        align-items: center;
        gap: 8px;
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
    .error-message {
        color: #ef4444;
        font-size: 12px;
        margin-top: 4px;
    }
    .total-score {
        background: var(--primary);
        color: #fff;
        padding: 16px;
        border-radius: 8px;
        text-align: center;
        font-size: 24px;
        font-weight: 700;
        margin-top: 24px;
    }
</style>

<div class="evaluation-page">
    <div class="container mx-auto px-4">
        <div class="page-header">
            <h1>Ocjenjivanje prijave</h1>
        </div>

        <!-- Informacije o prijavi -->
        <div class="info-card">
            <h2>Informacije o prijavi</h2>
            <p><strong>Naziv biznis plana:</strong> {{ $application->business_plan_name }}</p>
            <p><strong>Podnosilac:</strong> {{ $application->user->name ?? 'N/A' }}</p>
            <p><strong>Konkurs:</strong> {{ $application->competition->title ?? 'N/A' }}</p>
            <p><strong>Tip:</strong> {{ $application->applicant_type === 'preduzetnica' ? 'Preduzetnica' : 'DOO' }} - {{ $application->business_stage === 'započinjanje' ? 'Započinjanje' : 'Razvoj' }}</p>
        </div>

        @if($existingScore)
        <div class="info-card" style="background: #dbeafe; border-left: 4px solid #3b82f6;">
            <p><strong>Ocjena je već unesena.</strong> Možete je izmijeniti ispod.</p>
        </div>
        @endif

        <!-- Forma za ocjenjivanje -->
        <div class="form-card">
            <form method="POST" action="{{ route('evaluation.store', $application) }}" id="evaluationForm">
                @csrf

                <h2 style="font-size: 20px; font-weight: 700; color: var(--primary); margin-bottom: 24px;">
                    LISTA ZA OCJENJIVANJE BIZNIS PLANOVA
                </h2>

                <!-- Provjera dokumentacije -->
                <div class="form-group" style="background: #f0f9ff; padding: 20px; border-radius: 8px; border-left: 4px solid var(--primary); margin-bottom: 24px;">
                    <label class="form-label" style="font-size: 16px; font-weight: 700; color: var(--primary); margin-bottom: 12px;">
                        2. Dostavljena su sva potrebna dokumenta? *
                    </label>
                    <div style="display: flex; gap: 24px; margin-top: 12px;">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="radio" name="documents_complete" value="1" {{ old('documents_complete', $existingScore?->documents_complete ?? true) ? 'checked' : '' }} required>
                            <span>Da</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="radio" name="documents_complete" value="0" {{ old('documents_complete') === '0' || ($existingScore && !$existingScore->documents_complete) ? 'checked' : '' }} required>
                            <span>Ne *</span>
                        </label>
                    </div>
                    <div style="font-size: 12px; color: #6b7280; margin-top: 8px;">
                        * Ukoliko je odgovor "Ne", prijava će biti automatski odbijena
                    </div>
                    @error('documents_complete')
                        <div class="error-message">{{ $message }}</div>
                    @enderror
                </div>

                <h3 style="font-size: 18px; font-weight: 700; color: var(--primary); margin-bottom: 16px; margin-top: 32px;">
                    3. Ocjena biznis plana u brojkama:
                </h3>
                <div style="background: #f9fafb; padding: 16px; border-radius: 8px; margin-bottom: 24px; font-size: 14px; color: #374151;">
                    <strong>KRITERIJUMI ZA OCJENU (Član 18 stav 2 Odluke)</strong><br>
                    Komisija dodijeljuje ocjenu za biznis plan na skali od 1 do 5, pri čemu je:<br>
                    1 = uopšte ne odgovara navedenom,<br>
                    5 = u potpunosti odgovara navedenom.
                </div>

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

                @foreach($criteria as $num => $name)
                    <div class="criterion-grid">
                        <div class="criterion-info">
                            <div class="criterion-name">{{ $name }}</div>
                            <div class="criterion-number">Kriterijum {{ $num }}</div>
                        </div>
                        <div class="criterion-score">
                            <select name="criterion_{{ $num }}" class="form-control" required>
                                <option value="">Izaberi</option>
                                @for($i = 1; $i <= 5; $i++)
                                    <option value="{{ $i }}" {{ old("criterion_{$num}", $existingScore?->{"criterion_{$num}"}) == $i ? 'selected' : '' }}>
                                        {{ $i }} poen{{ $i > 1 ? 'a' : '' }}
                                    </option>
                                @endfor
                            </select>
                        </div>
                    </div>
                    @error("criterion_{$num}")
                        <div class="error-message">{{ $message }}</div>
                    @enderror
                @endforeach

                <div class="form-group" style="margin-top: 32px;">
                    <label class="form-label" style="font-size: 16px; font-weight: 700; color: var(--primary);">
                        5. Obrazloženje:
                    </label>
                    <textarea name="justification" class="form-control" rows="4" placeholder="Unesite obrazloženje ocjene...">{{ old('justification', $existingScore?->justification) }}</textarea>
                </div>

                <div class="form-group">
                    <label class="form-label" style="font-size: 16px; font-weight: 700; color: var(--primary);">
                        6. Ostale napomene:
                    </label>
                    <textarea name="notes" class="form-control" rows="4" placeholder="Unesite dodatne napomene...">{{ old('notes', $existingScore?->notes) }}</textarea>
                </div>

                <div style="background: #f9fafb; padding: 16px; border-radius: 8px; margin: 24px 0; font-size: 14px; color: #374151;">
                    <strong>Napomena:</strong> Biznis planovi sa ukupnim brojem bodova ispod 30 se neće podržati.
                </div>

                <div class="total-score" id="totalScore">
                    KONAČNA OCJENA: 0 / 50 poena
                </div>

                <div style="margin-top: 24px; text-align: center;">
                    <button type="submit" class="btn-primary">Sačuvaj ocjenu</button>
                    <a href="{{ route('evaluation.index') }}" style="margin-left: 12px; color: #6b7280;">Otkaži</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const selects = document.querySelectorAll('select[name^="criterion_"]');
        const totalScoreDiv = document.getElementById('totalScore');

        function updateTotalScore() {
            let total = 0;
            selects.forEach(select => {
                const value = parseInt(select.value) || 0;
                total += value;
            });
            totalScoreDiv.textContent = `Ukupno: ${total} / 50 poena`;
            if (total >= 30) {
                totalScoreDiv.style.background = '#10b981';
            } else {
                totalScoreDiv.style.background = 'var(--primary)';
            }
        }

        selects.forEach(select => {
            select.addEventListener('change', updateTotalScore);
        });

        updateTotalScore();
    });
</script>
@endsection

