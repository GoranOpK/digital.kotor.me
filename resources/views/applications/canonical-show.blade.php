@extends('layouts.app')

@section('content')
@include('partials.canonical-ui')
@php
    $formCode = $form ? $form->formCode() : 'P1a/P1b';
    $statusLabels = ['draft' => 'Nacrt (nije konačno podnesena)', 'submitted' => 'Konačno podnesena'];
    $uploadRows = collect($checklist)->where('source', \App\Support\CanonicalDocumentCatalog::SOURCE_UPLOAD);
    $tipPrijave = $form
        ? ($form->legal_form === \App\Support\CanonicalLegalForm::PREDUZETNIK ? 'Preduzetnik' : 'Privredno društvo')
        : '—';
@endphp

<div class="canonical-ui-page mmsp-form-page">
    <div class="container mx-auto px-4" style="max-width: 1200px;">
        <div class="mmsp-page-header">
            <h1>Prijava na javni poziv</h1>
            <p>
                Podrška mikro, malim i srednjim preduzećima<br>
                <strong>Konkurs: {{ $competition->title }}</strong>
            </p>
        </div>

        @foreach(['success' => 'success', 'info' => 'info'] as $key => $type)
            @if(session($key))
                <div class="mmsp-alert mmsp-alert-{{ $type }}">{{ session($key) }}</div>
            @endif
        @endforeach

        @if($errors->any())
            <div class="mmsp-alert mmsp-alert-error">
                <strong>Ispravite sljedeće greške:</strong>
                <ul style="margin:8px 0 0; padding-left:20px;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="mmsp-form-card">
            <div class="mmsp-form-section">
                <h2>Podaci o prijavi</h2>
                <div class="mmsp-form-row">
                    <div>
                        <div class="mmsp-form-text">Status prijave</div>
                        <strong>{{ $statusLabels[$application->status] ?? $application->status }}</strong>
                    </div>
                    <div>
                        <div class="mmsp-form-text">Tip prijave</div>
                        <strong>{{ $tipPrijave }}</strong>
                        @if($form) · Obrazac {{ $formCode }} @endif
                    </div>
                    <div>
                        <div class="mmsp-form-text">Broj konkursa</div>
                        <strong>{{ $competition->upNumber?->number ?? '—' }}</strong>
                    </div>
                    <div>
                        <div class="mmsp-form-text">Redni broj prijave</div>
                        <strong>{{ $application->redni_broj ?? 'Biće dodijeljen nakon konačne predaje' }}</strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="mmsp-form-card">
            <div class="mmsp-form-section">
                <h2>Koraci prijave</h2>
                <div style="overflow-x:auto;">
                    <table class="mmsp-dynamic-table">
                        <thead>
                            <tr>
                                <th>Korak</th>
                                <th>Status</th>
                                <th style="width:190px;">Akcija</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>1. Obrazac {{ $formCode }}</strong></td>
                                <td>
                                    @if($form)
                                        {{ $form->subsidy_type ? ($subsidyLabels[$form->subsidy_type] ?? $form->subsidy_type) : 'Vrsta subvencije nije izabrana' }}
                                        @if($form->isFrozen()) · zamrznut @endif
                                    @else
                                        Obrazac nije popunjen
                                    @endif
                                </td>
                                <td>
                                    @if($form)
                                        <a class="mmsp-btn-secondary" href="{{ route('applications.canonical-p1.edit', $application) }}">{{ $canManage ? 'Popuni / izmijeni' : 'Pregled' }}</a>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td><strong>2. Plan ulaganja (Obrazac P2)</strong></td>
                                <td>
                                    @if($plan)
                                        {{ $plan->subsidy_type ? ($subsidyLabels[$plan->subsidy_type] ?? $plan->subsidy_type) : 'Vrsta subvencije nije izabrana' }}
                                        @if($plan->total_investment) · ukupno {{ $plan->total_investment }} EUR @endif
                                        @if($plan->isFrozen()) · zamrznut @endif
                                    @else
                                        Plan ulaganja nije popunjen
                                    @endif
                                </td>
                                <td><a class="mmsp-btn-secondary" href="{{ route('applications.canonical-p2.edit', $application) }}">{{ $canManage ? 'Popuni / izmijeni' : 'Pregled' }}</a></td>
                            </tr>
                            <tr>
                                <td><strong>3. Prateća dokumentacija</strong></td>
                                <td>
                                    Priloženo {{ $uploadRows->whereNotNull('document')->count() }}
                                    · obavezno nedostaje {{ $uploadRows->where('required_now', true)->whereNull('document')->count() }}
                                </td>
                                <td><a class="mmsp-btn-secondary" href="{{ route('applications.canonical-documents.index', $application) }}">{{ $canManage ? 'Priloži / pregled' : 'Pregled' }}</a></td>
                            </tr>
                            @if($application->finalClaim)
                                <tr>
                                    <td><strong>4. Provjera potpunosti i prigovor</strong></td>
                                    <td>
                                        Status administrativne provjere vaše prijave
                                        @include('applications.partials.canonical_review_applicant_block')
                                    </td>
                                    <td><a class="mmsp-btn-secondary" href="{{ route('applications.canonical-review.show', $application) }}">Pregled statusa</a></td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        @if($application->status === 'draft')
            @if($blockers !== [])
                <div class="mmsp-alert mmsp-alert-warning">
                    <strong>Prije konačne predaje potrebno je:</strong>
                    <ul style="margin:8px 0 0 20px;">
                        @foreach($blockers as $blocker)
                            <li>{{ $blocker }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="mmsp-form-card">
                <div class="mmsp-form-section">
                    <h2>Konačna predaja</h2>
                    <p style="color:#4b5563; margin:0 0 16px;">
                        Konačnom predajom Obrazac {{ $formCode }}, Plan ulaganja i priložena dokumentacija se zamrzavaju i više se ne mogu mijenjati.
                    </p>
                    <div class="mmsp-actions">
                        @if($canSubmit)
                            <form method="POST" action="{{ route('applications.final-submit', $application) }}">
                                @csrf
                                <button type="submit" class="btn-primary">Konačno podnesi prijavu</button>
                            </form>
                        @endif
                        @if($canManage)
                            <form method="POST" action="{{ route('applications.destroy', $application) }}" onsubmit="return confirm('Obrisati nacrt prijave?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="mmsp-btn-secondary">Obriši nacrt prijave</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        @if($canStartAnother)
            <div class="mmsp-alert mmsp-alert-info">
                Na isti javni poziv možete podnijeti još jednu prijavu, za drugu vrstu subvencije.
                <a href="{{ route('applications.canonical.legal-form', $competition) }}">Započni drugu prijavu</a>
            </div>
        @endif

        <div class="mmsp-actions">
            <a class="mmsp-btn-secondary" href="{{ route('competitions.show', $competition) }}">← Nazad na konkurs</a>
            <a class="mmsp-btn-secondary" href="{{ route('dashboard') }}">Moj panel</a>
        </div>
    </div>
</div>
@endsection
