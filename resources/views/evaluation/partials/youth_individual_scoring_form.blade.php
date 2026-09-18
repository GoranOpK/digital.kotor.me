@php
    $scoringProfile = $scoringProfile ?? \App\Support\ScoringProfileConfig::for($application->competition?->type);
    $youthOral = $youthOralPresentation ?? $application->oralPresentation;
    $criteria = $scoringProfile->criteria;
    $scoreInputsLocked = ! $scoringIsAllowed || ($isRejected ?? false) || ($isApplicant ?? false) || ($hasCompletedEvaluation ?? false);
@endphp

@if(! $scoringIsAllowed)
    <div class="alert no-print" style="background: #fff7ed; border: 1px solid #fdba74; color: #9a3412; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px;">
        @if($eliminatoryIsConfirmedFail ?? false)
            {{ \App\Services\ApplicationEliminatoryCheckService::CONFIRMED_FAIL_SCORING_MESSAGE }}
        @else
            {{ $scoringLockedMessage ?? \App\Support\ScoringProfileConfig::YOUTH_SCORING_LOCKED_MESSAGE }}
        @endif
    </div>
@endif

<form method="POST" action="{{ route('evaluation.store', $application) }}" id="evaluationForm" data-needs-final-confirm="{{ ($scoringIsAllowed && !($hasCompletedEvaluation ?? false) && !($isApplicant ?? false) && !($isRejected ?? false)) ? '1' : '0' }}" @if(($isRejected ?? false) || ($isApplicant ?? false) || ($isDeadlinePassed ?? false) || ! $scoringIsAllowed) onsubmit="event.preventDefault(); return false;" @endif>
    @csrf
    <input type="hidden" name="scoring_confirmed" value="0">

    <div class="form-section form-section-scores">
        <label class="form-label form-label-large">4. Ocjena biznis plana u brojkama:</label>
        <div class="no-print" style="margin-bottom: 12px; color: #374151; font-size: 13px;">
            Skala: 1 — {{ \App\Support\ScoringProfileConfig::SCALE_MIN_LABEL }}; 5 — {{ \App\Support\ScoringProfileConfig::SCALE_MAX_LABEL }}. Vrijednosti 2, 3 i 4 su međuvrijednosti.
        </div>
        @if($youthOral && $youthOral->isCompleted())
            <div class="no-print" style="background: #eff6ff; border: 1px solid #93c5fd; color: #1e3a8a; padding: 10px 14px; border-radius: 8px; margin-bottom: 16px; font-size: 13px;">
                @if($youthOral->applicantAttended())
                    Usmeno predstavljanje je završeno. Podnosilac je prisustvovao. Prisustvo ne postavlja automatski ocjenu kriterijuma 10.
                @else
                    Usmeno predstavljanje je završeno. Evidentiran je nedolazak. Nedolazak nije eliminacija i ne postavlja automatski ocjenu kriterijuma 10.
                @endif
            </div>
        @else
            <div class="no-print" style="background: #fff7ed; border: 1px solid #fdba74; color: #9a3412; padding: 10px 14px; border-radius: 8px; margin-bottom: 16px; font-size: 13px;">
                Nacrt ocjene je dozvoljen. Završetak ocjenjivanja je moguć tek nakon završene evidencije usmenog predstavljanja ove prijave.
            </div>
        @endif

        <table class="evaluation-table evaluation-table-criteria">
            <thead>
                <tr>
                    <th class="criterion-col">KRITERIJUMI ZA OCJENU</th>
                    <th style="font-size: 11px;">Vaša ocjena</th>
                </tr>
            </thead>
            <tbody>
                @foreach($criteria as $num => $name)
                    <tr>
                        <td class="criterion-col">
                            <strong>{{ $num }}.</strong> {{ $name }}
                        </td>
                        <td>
                            @php
                                $currentValue = $existingScore ? $existingScore->{"criterion_{$num}"} : null;
                            @endphp
                            @if($hasCompletedEvaluation ?? false)
                                <span class="score-display">{{ $currentValue ? $currentValue : '—' }}</span>
                            @else
                                <input
                                    type="number"
                                    name="criterion_{{ $num }}"
                                    class="score-input"
                                    min="1"
                                    max="5"
                                    value="{{ old("criterion_{$num}", $currentValue) }}"
                                    @if($scoreInputsLocked) disabled @endif
                                >
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        @for($i = 1; $i <= 10; $i++)
            @error("criterion_{$i}")
                <div class="error-message">{{ $message }}</div>
            @enderror
        @endfor
    </div>

    <div class="form-section form-section-notes">
        <label class="form-label form-label-large">5. Ostale napomene:</label>
        @if($hasCompletedEvaluation ?? false)
            <div style="padding: 12px; background: #f9fafb; border-radius: 8px; border: 1px solid #e5e7eb; white-space: pre-wrap;">{{ $existingScore?->notes ?: '—' }}</div>
        @else
            <textarea
                name="notes"
                class="form-control"
                rows="6"
                placeholder="Unesite dodatne napomene..."
                @if($scoreInputsLocked) disabled @endif
            >{{ old('notes', $existingScore?->notes) }}</textarea>
        @endif
    </div>

    <div style="margin-top: 32px; text-align: center;" class="no-print">
        @if($hasCompletedEvaluation ?? false)
            <div style="padding: 16px; background: #ecfdf5; border-radius: 8px; margin-bottom: 16px; border: 1px solid #6ee7b7; color: #065f46; font-weight: 600;">
                Individualna ocjena je zaključana i ne može se mijenjati.
            </div>
            <a href="{{ route('evaluation.index') }}" class="btn-primary" style="text-decoration: none; display: inline-block;">Nazad na listu</a>
        @else
            <button type="submit" name="save_as_draft" value="1" class="btn-primary" @if($isDeadlinePassed || ! $scoringIsAllowed) disabled style="opacity: 0.5; cursor: not-allowed;" @endif>Sačuvaj nacrt</button>
            <button type="submit" class="btn-primary" @if($isDeadlinePassed || ! $scoringIsAllowed || ! ($youthLockEvidenceReady ?? false)) disabled style="opacity: 0.5; cursor: not-allowed; margin-left: 12px;" @else style="margin-left: 12px;" @endif>Završi ocjenjivanje</button>
            @if(! ($youthLockEvidenceReady ?? false))
                <div style="margin-top: 12px; color: #9a3412; font-size: 13px;">
                    Završetak ocjenjivanja je onemogućen dok usmeno predstavljanje ove prijave nije evidentirano kao završeno.
                </div>
            @endif
            <a href="{{ route('evaluation.index') }}" style="margin-left: 12px; color: #6b7280; text-decoration: none;">Otkaži</a>
        @endif
    </div>
</form>
