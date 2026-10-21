@extends('layouts.app')

@section('content')
@include('partials.canonical-ui')
@php
    $box = 'border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; margin-bottom: 16px; background: #fff;';
    $warn = 'background: #fffbeb; border: 1px solid #f59e0b; padding: 10px; border-radius: 8px; margin-bottom: 8px;';
    $bad = 'background: #fef2f2; border: 1px solid #ef4444; padding: 10px; border-radius: 8px; margin-bottom: 8px;';
    $ok = 'background: #ecfdf5; border: 1px solid #10b981; padding: 10px; border-radius: 8px; margin-bottom: 8px;';
    $fmt = fn ($at) => $at ? \Carbon\CarbonImmutable::parse($at)->timezone($review['timezone'])->format('d.m.Y H:i:s') : '—';
@endphp
<div class="canonical-ui-page mmsp-form-page"><div class="container mx-auto px-4" style="max-width: 1200px;">
    <div class="mmsp-page-header">
        <h1>Status provjere potpunosti prijave</h1>
        <p>
            Podrška mikro, malim i srednjim preduzećima<br>
            <strong>Konkurs: {{ $application->competition?->title }}</strong>
            @if($application->redni_broj) · redni broj {{ $application->redni_broj }} @endif
        </p>
    </div>

    @if(session('success'))
        <div style="{{ $ok }}">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div style="{{ $bad }}">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div style="{{ $box }}" data-section="applicant-review-status" data-status="{{ $review['status'] }}">
        <h2 style="font-size: 18px; font-weight: 600;">Trenutni status</h2>
        <p><strong>{{ $review['label'] }}</strong></p>
        @if($review['closed'])
            <p>Postupak po ovom Pozivu je zatvoren.</p>
        @elseif($review['rankingFormed'] && $review['proceedsToScoring'])
            <p>Bodovanje je završeno i formirana je preliminarna rang lista.</p>
        @endif
    </div>

    @if($review['incompleteReason'] !== null)
        <div class="mmsp-form-card" data-section="applicant-review-incomplete">
            <h2 style="font-size: 18px; font-weight: 600;">Nalaz o nepotpunosti</h2>
            <p>Obrazloženje Komisije: {{ $review['incompleteReason'] }}</p>
            <p>Obavještenje poslato: {{ $fmt($review['noticeSentAt']) }}</p>
            <p>Rok za prigovor (3 dana od slanja obavještenja, tačno vrijeme): <strong data-complaint-deadline>{{ $fmt($review['complaintDeadline']) }}</strong></p>
            <p style="color: #4b5563;">Prigovor ne otvara prijavu za izmjenu niti za naknadno dostavljanje dokumenata. Komisija odlučuje na osnovu podnesenog dosijea.</p>
        </div>
    @endif

    @if($review['complaintSubmittedAt'])
        <div class="mmsp-form-card" data-section="applicant-review-complaint">
            <h2 style="font-size: 18px; font-weight: 600;">Vaš prigovor</h2>
            <p>Podnesen: {{ $fmt($review['complaintSubmittedAt']) }}</p>
            @if($review['complaintDecision'] === null)
                <p>Prigovor čeka odluku Komisije.</p>
            @else
                <p>
                    Odluka od {{ $fmt($review['complaintDecidedAt']) }}:
                    <strong>prigovor je {{ $review['complaintDecision'] === \App\Models\ApplicationComplaint::ACCEPTED ? 'prihvaćen' : 'odbijen' }}</strong>.
                </p>
                @if(filled($review['complaintDecisionNotes']))
                    <p data-complaint-decision-notes><strong>Obrazloženje odluke Komisije:</strong></p>
                    <p style="white-space: pre-wrap;">{{ $review['complaintDecisionNotes'] }}</p>
                @endif
            @endif
        </div>
    @elseif($review['complaintOpen'])
        <div class="mmsp-form-card" data-section="applicant-review-complaint-form">
            <h2 style="font-size: 18px; font-weight: 600;">Podnošenje prigovora</h2>
            <p>Prigovor se podnosi jednom. Nakon podnošenja ne može se mijenjati ni povući.</p>
            <form method="POST" action="{{ route('applications.complaint', $application) }}">
                @csrf
                <label>Obrazloženje prigovora
                    <textarea name="body" rows="5" maxlength="5000" required style="width: 100%;">{{ old('body') }}</textarea>
                </label>
                <button type="submit" class="btn-primary">Podnesi prigovor</button>
            </form>
        </div>
    @endif

    <div class="mmsp-actions"><a class="mmsp-btn-secondary" href="{{ route('applications.show', $application) }}">← Nazad na prijavu</a></div>
</div></div>
@endsection
