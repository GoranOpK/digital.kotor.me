@php
    $assessment = \App\Models\ApplicationCompletenessAssessment::query()
        ->where('application_id', $application->id)
        ->latest('id')
        ->first();
    $complaint = \App\Models\ApplicationComplaint::query()
        ->where('application_id', $application->id)
        ->latest('id')
        ->first();
    $isChair = ($commissionMember->canonical_seat ?? null) === \App\Services\Competitions\Commission\CanonicalCommissionGovernance::ROLE_PREDSJEDNIK
        && ($commissionMember->position ?? null) === 'predsjednik'
        && ($commissionMember->status ?? null) === 'active';
    $canRecord = $isChair
        && $assessment === null
        && app(\App\Services\Competitions\CompletenessAssessmentService::class)->recordBlockReason($application, auth()->user()) === null;
    $presentation = \App\Support\MmspP3Form::administrativePresentation($application);
    $criteria = \App\Support\MmspP3Form::CRITERIA;
    $selected = function (string $field) {
        $old = old($field);

        return $old === null || $old === 'da';
    };
@endphp
<div class="form-section form-section-compact" data-testid="mmsp-administrative-review">
    <div class="no-print" style="margin-bottom: 16px;">
        <div class="form-label form-label-large" style="margin-bottom: 6px;">Administrativna provjera</div>
        <p style="margin: 0; font-size: 13px; color: #4b5563; line-height: 1.5;">Predsjednik Komisije označava da li prijava ispunjava eliminatorne uslove.</p>
    </div>

    @if($canRecord)
        <form method="POST" action="{{ route('evaluation.completeness', $application) }}" id="mmspAdministrativeForm">
            @csrf
            @foreach($criteria as $key => $criterion)
                <label class="form-label form-label-large" @if(! $loop->first) style="margin-top: 16px;" @endif>
                    @if($loop->first) 3. @endif{{ $criterion['statement'] }}
                </label>
                <div class="radio-group">
                    <label class="radio-option">
                        <input type="radio" name="{{ $criterion['field'] }}" value="da" {{ $selected($criterion['field']) ? 'checked' : '' }} required>
                        <span>Da</span>
                    </label>
                    <label class="radio-option">
                        <input type="radio" name="{{ $criterion['field'] }}" value="ne" {{ old($criterion['field']) === 'ne' ? 'checked' : '' }} required>
                        <span>Ne*</span>
                    </label>
                </div>
                @error($criterion['field'])
                    <div class="error-message">{{ $message }}</div>
                @enderror
            @endforeach

            <p style="margin-top: 12px; font-size: 13px; color: #4b5563; line-height: 1.5;">{{ \App\Support\MmspP3Form::NE_STAR_FOOTNOTE }}</p>

            <label class="form-label" style="margin-top: 16px;">Napomena</label>
            <textarea name="notes" class="form-control" rows="4" placeholder="Napomena Obrasca P3">{{ old('notes') }}</textarea>
            @error('notes')
                <div class="error-message">{{ $message }}</div>
            @enderror

            <div class="no-print" style="margin-top: 16px;">
                <button type="submit" class="btn-primary" onclick="return acknowledgeMmspAdministrativeConfirm(this.form);">Potvrdi administrativnu provjeru</button>
                <p style="margin: 8px 0 0; font-size: 13px; color: #4b5563; line-height: 1.5;">Zaključava provjeru. Ocjena se nakon potvrde ne može mijenjati.</p>
            </div>
        </form>
    @else
        @if($assessment === null)
            <p style="margin: 0 0 12px;">{{ \App\Services\Competitions\Commission\CanonicalCommissionGovernance::CHAIR_COMPLETENESS_REQUIRED_MESSAGE }}</p>
        @endif
        <div style="padding: 16px; background: #f9fafb; border-radius: 8px; margin-top: 12px; border: 1px solid #e5e7eb;">
            @foreach($criteria as $key => $criterion)
                @if($loop->first)
                    <label class="form-label form-label-large">3. {{ $criterion['statement'] }}</label>
                    <div style="margin-bottom: 12px; margin-top: 12px;">
                        <strong>{{ $presentation['answers'][$key] }}</strong>
                    </div>
                @else
                    <div style="margin-bottom: 8px; font-weight: 600;">{{ $criterion['statement'] }}</div>
                    <div style="margin-bottom: 12px;">
                        <strong>{{ $presentation['answers'][$key] }}</strong>
                    </div>
                @endif
            @endforeach
            <p style="margin-bottom: 12px; font-size: 13px; color: #4b5563; line-height: 1.5;">{{ \App\Support\MmspP3Form::NE_STAR_FOOTNOTE }}</p>
            <div style="margin-bottom: 8px; font-weight: 600;">Napomena</div>
            <div style="white-space: pre-wrap; margin-bottom: 12px;">{{ $presentation['explanation'] !== '' ? $presentation['explanation'] : '—' }}</div>
            <div>
                <strong>Rezultat:</strong>
                {{ $presentation['result'] }}
            </div>
        </div>
    @endif

    @if($complaint)
        <div data-testid="mmsp-complaint" class="no-print" style="margin-top: 16px; padding-top: 12px; border-top: 1px solid #e5e7eb;">
            <h3 style="font-size: 16px; font-weight: 700; margin: 0 0 8px;">Prigovor</h3>
            <p style="margin: 0 0 8px; white-space: pre-wrap;">{{ $complaint->body }}</p>
            @if($complaint->decision)
                <p style="margin: 0;"><strong>{{ $complaint->decision === \App\Models\ApplicationComplaint::ACCEPTED ? 'Prigovor je prihvaćen.' : 'Prigovor je odbijen.' }}</strong></p>
                @if(filled($complaint->decision_notes))
                    <p style="margin: 8px 0 0; white-space: pre-wrap;">{{ $complaint->decision_notes }}</p>
                @endif
            @elseif($isChair)
                <form method="POST" action="{{ route('evaluation.complaint-decision', [$application, $complaint]) }}">
                    @csrf
                    <label style="margin-right: 12px;"><input type="radio" name="decision" value="accepted" required @checked(old('decision') === 'accepted')> Prihvati</label>
                    <label><input type="radio" name="decision" value="rejected" @checked(old('decision') === 'rejected')> Odbij</label>
                    <label style="display: block; margin: 8px 0;">Obrazloženje odluke
                        <textarea name="decision_notes" rows="3" required style="width: 100%;">{{ old('decision_notes') }}</textarea>
                    </label>
                    <button type="submit" class="btn-primary">Evidentiraj odluku o prigovoru</button>
                </form>
            @else
                <p style="margin: 0;">Odluka o prigovoru još nije evidentirana.</p>
            @endif
        </div>
    @endif
    <div class="no-print" style="margin-top: 24px; border-top: 2px solid #d1d5db;"></div>
</div>
