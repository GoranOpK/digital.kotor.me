@php
    $complaint = \App\Models\ApplicationComplaint::query()
        ->where('application_id', $application->id)
        ->latest('id')
        ->first();
    $isChair = ($commissionMember->canonical_seat ?? null) === \App\Services\Competitions\Commission\CanonicalCommissionGovernance::ROLE_PREDSJEDNIK
        && ($commissionMember->position ?? null) === 'predsjednik'
        && ($commissionMember->status ?? null) === 'active';
    $policy = $application->competition?->profile();
    $timezone = $policy?->publicationTimezone() ?? config('app.timezone');
    $fmt = function ($value) use ($timezone) {
        if ($value === null) {
            return null;
        }

        return \Illuminate\Support\Carbon::parse($value)->timezone($timezone)->format('d.m.Y. H:i');
    };
    $incomplete = \App\Models\ApplicationCompletenessAssessment::query()
        ->where('application_id', $application->id)
        ->where('outcome', \App\Models\ApplicationCompletenessAssessment::INCOMPLETE)
        ->orderBy('id')
        ->first();
    $noticeSentAt = $incomplete?->notice_sent_at;
    $complaintDeadline = ($noticeSentAt && $policy) ? $policy->complaintWindowEndsAt($noticeSentAt) : null;
@endphp
@if($complaint)
    <div data-testid="mmsp-complaint" class="kn-prigovor-card no-print">
        <h2>Prigovor</h2>
        @if($noticeSentAt)
            <p>
                Rok za prigovor: {{ $fmt($complaintDeadline) }}
                (3 dana od slanja obavještenja {{ $fmt($noticeSentAt) }}).
            </p>
        @endif
        <p style="white-space: pre-wrap;">{{ $complaint->body }}</p>
        <p>Podnesen: {{ $fmt($complaint->submitted_at) }}</p>
        <p>Rok za odluku (7 dana, tačno vrijeme): {{ $fmt($complaint->decision_due_at) }}</p>
        @if($complaint->decision)
            <p>
                <strong>{{ $complaint->decision === \App\Models\ApplicationComplaint::ACCEPTED ? 'Prigovor je prihvaćen.' : 'Prigovor je odbijen.' }}</strong>
                @if($complaint->decided_at)
                    ({{ $fmt($complaint->decided_at) }})
                @endif
            </p>
            @if($complaint->decision_late && filled($complaint->compliance_warning))
                <p>{{ $complaint->compliance_warning }}</p>
            @endif
            @if(filled($complaint->decision_notes))
                <p><strong>Obrazloženje odluke:</strong></p>
                <p style="white-space: pre-wrap;">{{ $complaint->decision_notes }}</p>
            @endif
        @elseif($isChair)
            <form method="POST" action="{{ route('evaluation.complaint-decision', [$application, $complaint]) }}">
                @csrf
                <p><strong>Odluka o prigovoru</strong></p>
                <div class="kn-prigovor-actions">
                    <label>
                        <input type="radio" name="decision" value="accepted" required @checked(old('decision') === 'accepted')>
                        Prihvati
                    </label>
                    <label>
                        <input type="radio" name="decision" value="rejected" @checked(old('decision') === 'rejected')>
                        Odbij
                    </label>
                </div>
                <label for="mmsp-prigovor-decision-notes"><strong>Obrazloženje odluke</strong></label>
                <textarea id="mmsp-prigovor-decision-notes" name="decision_notes" class="kn-prigovor-textarea" required>{{ old('decision_notes') }}</textarea>
                @error('decision_notes')
                    <p style="color: #ef4444;">{{ $message }}</p>
                @enderror
                <div class="kn-prigovor-actions">
                    <button type="submit" class="kn-prigovor-btn">Evidentiraj odluku o prigovoru</button>
                </div>
            </form>
        @else
            <p>Odluka o prigovoru još nije evidentirana.</p>
        @endif
    </div>
@endif
