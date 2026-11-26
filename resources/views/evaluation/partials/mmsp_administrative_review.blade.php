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
        && app(\App\Services\Competitions\CompletenessAssessmentService::class)->recordBlockReason($application, auth()->user()) === null;
@endphp
<div data-testid="mmsp-administrative-review" style="border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; margin-bottom: 20px; background: #fff;">
    <h2 style="font-size: 18px; font-weight: 700; margin: 0 0 8px;">Administrativna provjera</h2>
    <p style="color: #4b5563; margin: 0 0 12px;">Kriterijumi iz Odluke: potpunost dokumentacije, izvještaj o prethodnoj realizaciji kada je obavezan, i veza plana ulaganja sa vrstom subvencije.</p>

    @if($assessment)
        <p style="margin: 0 0 8px;"><strong>{{ $assessment->outcome === \App\Models\ApplicationCompletenessAssessment::COMPLETE ? 'Prijava je potpuna.' : 'Prijava nije potpuna.' }}</strong></p>
        @if(filled($assessment->notes))
            <p style="margin: 0; white-space: pre-wrap;">{{ $assessment->notes }}</p>
        @endif
    @elseif($canRecord)
        <form method="POST" action="{{ route('evaluation.completeness', $application) }}">
            @csrf
            @foreach([
                'criterion_documentation' => 'Dokumentacija je potpuna',
                'criterion_report' => 'Dostavljen je izvještaj o prethodnoj realizaciji, ako je obavezan',
                'criterion_related' => 'Plan ulaganja se odnosi na vrstu subvencije',
            ] as $name => $label)
                <div style="margin-bottom: 10px;">
                    <div style="font-weight: 600;">{{ $label }}</div>
                    <label style="margin-right: 12px;"><input type="radio" name="{{ $name }}" value="da" required @checked(old($name) === 'da')> Da</label>
                    <label><input type="radio" name="{{ $name }}" value="ne" @checked(old($name) === 'ne')> Ne*</label>
                </div>
            @endforeach
            <label style="display: block; margin-bottom: 10px;">Obrazloženje
                <textarea name="notes" rows="3" style="width: 100%;">{{ old('notes') }}</textarea>
            </label>
            <button type="submit" class="btn-primary">Potvrdi administrativnu provjeru</button>
        </form>
    @else
        <p style="margin: 0;">{{ \App\Services\Competitions\Commission\CanonicalCommissionGovernance::CHAIR_COMPLETENESS_REQUIRED_MESSAGE }}</p>
    @endif

    @if($complaint)
        <div data-testid="mmsp-complaint" style="margin-top: 16px; padding-top: 12px; border-top: 1px solid #e5e7eb;">
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
</div>
