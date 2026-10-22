{{-- Omladinski Obrazac 3. Ženski markup ostaje izvorni u create/show. --}}
{{-- Polaritet: true = prolaz / razlog nije aktiviran; false = pad / razlog je aktiviran. --}}
@php
    $eliminatoryProfile = $eliminatoryProfile ?? \App\Support\EliminatoryProfileConfig::for($application->competition?->type);
    $eliminatoryCheck = $eliminatoryCheck ?? $application->eliminatoryCheck;
    $youthSharedNote = \App\Support\EliminatoryProfileConfig::youthSharedNote($eliminatoryCheck?->note);
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
        @error('criterion_'.$number)
            <div class="error-message">{{ $message }}</div>
        @enderror
    @endforeach

    <p style="margin-top: 12px; font-size: 13px; color: #4b5563; line-height: 1.5;">{{ \App\Models\ApplicationEliminatoryCheck::NE_STAR_FOOTNOTE }}</p>

    <label class="form-label" style="margin-top: 16px;">Napomena</label>
    <textarea name="note" class="form-control" rows="4" placeholder="Napomena Obrasca 3">{{ old('note', $youthSharedNote) }}</textarea>
    @error('note')
        <div class="error-message">{{ $message }}</div>
    @enderror
    @error('confirmation_acknowledged')
        <div class="error-message">{{ $message }}</div>
    @enderror
@else
    @foreach($eliminatoryProfile->criteria as $number => $criterion)
        @if($number === 1)
            <label class="form-label form-label-large">3. {{ $criterion['statement'] }}</label>
            <div style="margin-bottom: 12px; margin-top: 12px;">
                <strong>{{ $eliminatoryCheck
                    ? $eliminatoryProfile->displayValue(
                        $number,
                        $eliminatoryCheck->{'criterion_'.$number},
                        $eliminatoryCheck->criterionIsTrue($eliminatoryCheck->{'criterion_'.$number}),
                        $eliminatoryCheck->criterionIsFalse($eliminatoryCheck->{'criterion_'.$number})
                    )
                    : 'Nije označeno' }}</strong>
            </div>
        @else
            <div style="margin-bottom: 8px; font-weight: 600;">{{ $criterion['statement'] }}</div>
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
        @endif
    @endforeach
    <p style="margin-bottom: 12px; font-size: 13px; color: #4b5563; line-height: 1.5;">{{ \App\Models\ApplicationEliminatoryCheck::NE_STAR_FOOTNOTE }}</p>
    <div style="margin-bottom: 8px; font-weight: 600;">Napomena</div>
    <div style="white-space: pre-wrap; margin-bottom: 12px;">{{ $youthSharedNote !== '' ? $youthSharedNote : '—' }}</div>
@endif
