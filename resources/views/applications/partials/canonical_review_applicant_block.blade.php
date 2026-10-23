{{-- Canonical MMSP applicant completeness / complaint status. Not part of Women/Youth Obrazac 3 / Prigovor. --}}
@php
    $canonicalReview = app(\App\Services\Competitions\ComplaintService::class)->applicantStatus($application);
    $canonicalReviewAt = fn ($at) => $at
        ? \Carbon\CarbonImmutable::parse($at)->timezone($canonicalReview['timezone'])->format('d.m.Y. H:i:s')
        : '—';
@endphp
@if($canonicalReview !== null)
    <div data-section="applicant-canonical-review-block" data-status="{{ $canonicalReview['status'] }}" style="margin-top: 8px; padding: 10px 12px; border: 1px solid #e5e7eb; border-radius: 8px; background: #f9fafb; font-size: 12px; color: #111827; line-height: 1.5; max-width: 560px;">
        <div><strong>Provjera potpunosti:</strong> {{ $canonicalReview['label'] }}</div>
        @if($canonicalReview['noticeSentAt'])
            <div>Obavještenje o nepotpunosti poslato: {{ $canonicalReviewAt($canonicalReview['noticeSentAt']) }}</div>
            <div>Rok za prigovor (3 dana, tačno vrijeme): {{ $canonicalReviewAt($canonicalReview['complaintDeadline']) }}</div>
        @endif
        @if($canonicalReview['complaintSubmittedAt'])
            <div>Prigovor podnesen: {{ $canonicalReviewAt($canonicalReview['complaintSubmittedAt']) }}</div>
            @if($canonicalReview['complaintDecision'] === null)
                <div>Odluka Komisije: čeka se odluka.</div>
            @else
                <div>
                    Odluka Komisije: prigovor je
                    <strong>{{ $canonicalReview['complaintDecision'] === \App\Models\ApplicationComplaint::ACCEPTED ? 'prihvaćen' : 'odbijen' }}</strong>
                    ({{ $canonicalReviewAt($canonicalReview['complaintDecidedAt']) }})
                </div>
                @if(filled($canonicalReview['complaintDecisionNotes']))
                    <div><strong>Obrazloženje odluke:</strong></div>
                    <div style="white-space: pre-wrap;">{{ $canonicalReview['complaintDecisionNotes'] }}</div>
                @endif
            @endif
        @elseif($canonicalReview['noticeSentAt'])
            <div>Prigovor nije podnesen.@if($canonicalReview['complaintOpen']) Prigovor se podnosi isključivo putem stranice statusa provjere.@endif</div>
        @endif
    </div>
@endif
