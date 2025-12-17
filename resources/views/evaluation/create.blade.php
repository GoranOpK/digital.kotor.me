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
            <p><strong>Ocjena je već unesena.</strong> Možete je izmeniti ispod.</p>
        </div>
        @endif

        <!-- Forma za ocjenjivanje -->
        <div class="form-card">
            <form method="POST" action="{{ route('evaluation.store', $application) }}" id="evaluationForm">
                @csrf

                <h2 style="font-size: 20px; font-weight: 700; color: var(--primary); margin-bottom: 24px;">
                    Ocjene po kriterijumima (1-5 poena)
                </h2>

                @php
                    $criteria = [
                        1 => 'Obrazac biznis plana detaljno popunjen',
                        2 => 'Biznis ideja je inovativna',
                        3 => 'Jasno identifikovani potencijalni kupci',
                        4 => 'Omogućava samozapošljavanje/zapošljavanje',
                        5 => 'Prepoznata konkurencija',
                        6 => 'Jasno navedeni potrebni resursi',
                        7 => 'Finansijski održiva',
                        8 => 'Podaci o preduzetnici',
                        9 => 'Razvijena matrica rizika',
                        10 => 'Usmeno obrazloženje',
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

                <div class="form-group">
                    <label class="form-label">
                        Napomene
                        <span class="description">(Opciono)</span>
                    </label>
                    <textarea name="notes" class="form-control" rows="4" placeholder="Unesite dodatne napomene o prijavi...">{{ old('notes', $existingScore?->notes) }}</textarea>
                </div>

                <div class="total-score" id="totalScore">
                    Ukupno: 0 / 50 poena
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

