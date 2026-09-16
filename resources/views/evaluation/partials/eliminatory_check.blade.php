{{-- Omladinski Obrazac 3. Ženski markup ostaje izvorni u create/show. --}}
{{-- Polaritet: true = prolaz / razlog nije aktiviran; false = pad / razlog je aktiviran. --}}
@php
    $eliminatoryProfile = $eliminatoryProfile ?? \App\Support\EliminatoryProfileConfig::for($application->competition?->type);
    $eliminatoryCheck = $eliminatoryCheck ?? $application->eliminatoryCheck;
    $youthDraftNotes = \App\Support\EliminatoryProfileConfig::youthDraftExplanations($eliminatoryCheck?->note);
    $mode = $mode ?? 'readonly';
@endphp

@if($mode === 'edit')
    @foreach($eliminatoryProfile->criteria as $number => $criterion)
        <label class="form-label form-label-large" @if($number === 1) @else style="margin-top: 16px;" @endif>
            @if($number === 1) 3. @endif{{ $criterion['statement'] }}
        </label>
        <div class="radio-group">
            <label class="radio-option">
                <input type="radio" name="criterion_{{ $number }}" value="1" {{ $criterionYes('criterion_'.$number, $eliminatoryCheck?->{'criterion_'.$number}) ? 'checked' : '' }} required>
                <span>{{ $criterion['pass_label'] }}</span>
            </label>
            <label class="radio-option">
                <input type="radio" name="criterion_{{ $number }}" value="0" {{ ! $criterionYes('criterion_'.$number, $eliminatoryCheck?->{'criterion_'.$number}) ? 'checked' : '' }} required>
                <span>{{ $criterion['fail_label'] }}</span>
            </label>
        </div>
        <p style="margin: 6px 0 0; font-size: 12px; color: #6b7280;">
            {{ $criterion['pass_label'] }} = prolaz (razlog nije aktiviran).
            {{ $criterion['fail_label'] }} = eliminatorni razlog je aktiviran.
        </p>
        @error('criterion_'.$number)
            <div class="error-message">{{ $message }}</div>
        @enderror

        <label class="form-label" style="margin-top: 8px;">Obrazloženje kriterijuma {{ $number }}</label>
        <textarea name="criterion_notes[{{ $number }}]" class="form-control" rows="3" placeholder="Obrazloženje ako je razlog aktiviran">{{ old('criterion_notes.'.$number, $youthDraftNotes[$number] ?? '') }}</textarea>
        @error('criterion_notes.'.$number)
            <div class="error-message">{{ $message }}</div>
        @enderror
    @endforeach
    @error('confirmation_acknowledged')
        <div class="error-message">{{ $message }}</div>
    @enderror
@else
    @foreach($eliminatoryProfile->criteria as $number => $criterion)
        <div style="margin-bottom: 8px; font-weight: 600;">@if($number === 1) 3. @endif{{ $criterion['statement'] }}</div>
        <div style="margin-bottom: 12px;">
            <strong>{{ $eliminatoryCheck
                ? $eliminatoryProfile->displayValue(
                    $number,
                    $eliminatoryCheck->{'criterion_'.$number},
                    $eliminatoryCheck->criterionIsTrue($eliminatoryCheck->{'criterion_'.$number}),
                    $eliminatoryCheck->criterionIsFalse($eliminatoryCheck->{'criterion_'.$number})
                )
                : 'Nije označeno' }}</strong>
        </div>
        @php
            $shownExplanation = \App\Support\EliminatoryProfileConfig::youthExplanation($eliminatoryCheck?->note, $number);
            $reasonActivated = $eliminatoryCheck && $eliminatoryCheck->criterionIsFalse($eliminatoryCheck->{'criterion_'.$number});
        @endphp
        @if($reasonActivated || $shownExplanation !== '')
            <div style="margin-bottom: 8px; font-weight: 600;">Obrazloženje kriterijuma {{ $number }}</div>
            <div style="white-space: pre-wrap; margin-bottom: 12px;">{{ $shownExplanation !== '' ? $shownExplanation : '—' }}</div>
        @endif
    @endforeach
@endif
