@extends('layouts.app')

@section('content')
@include('partials.canonical-ui')
@php
    $formCode = $form ? $form->formCode() : 'P1a/P1b';
    $statusLabels = ['draft' => 'Nacrt (nije konačno podnesena)', 'submitted' => 'Konačno podnesena'];
    $uploadRows = collect($checklist)->where('source', \App\Support\CanonicalDocumentCatalog::SOURCE_UPLOAD);
@endphp
<div class="container mx-auto px-4 py-6 canonical-ui-page" style="max-width: 1000px;">
    <h1 style="font-size: 24px; font-weight: 700; margin-bottom: 4px;">Prijava na javni poziv — Podrška mikro, malim i srednjim preduzećima</h1>
    <p style="color: rgba(255,255,255,0.9); margin-bottom: 4px;"><strong>Konkurs: {{ $competition->title }}</strong></p>
    <p style="color: rgba(255,255,255,0.9); margin-bottom: 16px;">
        Status: <strong>{{ $statusLabels[$application->status] ?? $application->status }}</strong>
        @if($application->redni_broj) · Redni broj prijave: {{ $application->redni_broj }} @endif
        @if($form)
            · Tip prijave:
            <strong>{{ $form->legal_form === \App\Support\CanonicalLegalForm::PREDUZETNIK ? 'Preduzetnik' : 'Privredno društvo' }}</strong>
            (Obrazac {{ $formCode }})
        @endif
    </p>

    @foreach(['success' => ['#ecfdf5', '#10b981'], 'info' => ['#eff6ff', '#3b82f6']] as $key => [$bg, $border])
        @if(session($key))
            <div style="background: {{ $bg }}; border: 1px solid {{ $border }}; padding: 12px; border-radius: 8px; margin-bottom: 16px;">{{ session($key) }}</div>
        @endif
    @endforeach
    @if($errors->any())
        <div style="background: #fef2f2; border: 1px solid #ef4444; padding: 12px; border-radius: 8px; margin-bottom: 16px;">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
        <tbody>
            <tr style="border-top: 1px solid #e5e7eb;">
                <td style="padding: 10px;"><strong>1. Obrazac {{ $formCode }}</strong></td>
                <td style="padding: 10px;">
                    @if($form)
                        {{ $form->subsidy_type ? ($subsidyLabels[$form->subsidy_type] ?? $form->subsidy_type) : 'Vrsta subvencije nije izabrana' }}
                        @if($form->isFrozen()) · zamrznut @endif
                    @else
                        Obrazac nije popunjen
                    @endif
                </td>
                <td style="padding: 10px;">
                    @if($form)
                        <a href="{{ route('applications.canonical-p1.edit', $application) }}">{{ $canManage ? 'Popuni / izmijeni' : 'Pregled' }}</a>
                    @endif
                </td>
            </tr>
            <tr style="border-top: 1px solid #e5e7eb;">
                <td style="padding: 10px;"><strong>2. Plan ulaganja (Obrazac P2)</strong></td>
                <td style="padding: 10px;">
                    @if($plan)
                        {{ $plan->subsidy_type ? ($subsidyLabels[$plan->subsidy_type] ?? $plan->subsidy_type) : 'Vrsta subvencije nije izabrana' }}
                        @if($plan->total_investment) · ukupno {{ $plan->total_investment }} EUR @endif
                        @if($plan->isFrozen()) · zamrznut @endif
                    @else
                        Plan ulaganja nije popunjen
                    @endif
                </td>
                <td style="padding: 10px;"><a href="{{ route('applications.canonical-p2.edit', $application) }}">{{ $canManage ? 'Popuni / izmijeni' : 'Pregled' }}</a></td>
            </tr>
            <tr style="border-top: 1px solid #e5e7eb;">
                <td style="padding: 10px;"><strong>3. Prateća dokumentacija (čl.14)</strong></td>
                <td style="padding: 10px;">
                    Priloženo {{ $uploadRows->whereNotNull('document')->count() }}
                    · obavezno nedostaje {{ $uploadRows->where('required_now', true)->whereNull('document')->count() }}
                </td>
                <td style="padding: 10px;"><a href="{{ route('applications.canonical-documents.index', $application) }}">{{ $canManage ? 'Priloži / pregled' : 'Pregled' }}</a></td>
            </tr>
            @if($application->finalClaim)
                <tr style="border-top: 1px solid #e5e7eb;">
                    <td style="padding: 10px;"><strong>4. Provjera potpunosti i prigovor</strong></td>
                    <td style="padding: 10px;">
                        Status administrativne provjere vaše prijave
                        @include('applications.partials.canonical_review_applicant_block')
                    </td>
                    <td style="padding: 10px;"><a href="{{ route('applications.canonical-review.show', $application) }}">Status provjere potpunosti / prigovor</a></td>
                </tr>
            @endif
        </tbody>
    </table>

    @if($application->status === 'draft')
        @if($blockers !== [])
            <div style="background: #fffbeb; border: 1px solid #f59e0b; padding: 12px; border-radius: 8px; margin-bottom: 16px;">
                <strong>Prije konačne predaje potrebno je:</strong>
                <ul style="margin: 6px 0 0 18px; list-style: disc;">
                    @foreach($blockers as $blocker)
                        <li>{{ $blocker }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if($canSubmit)
            <form method="POST" action="{{ route('applications.final-submit', $application) }}" style="margin-bottom: 12px;">
                @csrf
                <p style="margin-bottom: 8px;">Konačnom predajom Obrazac {{ $formCode }}, Plan ulaganja i priložena dokumentacija se zamrzavaju i više se ne mogu mijenjati.</p>
                <button type="submit" class="btn btn-primary">Konačno podnesi prijavu</button>
            </form>
        @endif

        @if($canManage)
            <form method="POST" action="{{ route('applications.destroy', $application) }}" onsubmit="return confirm('Obrisati nacrt prijave?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm">Obriši nacrt prijave</button>
            </form>
        @endif
    @endif

    @if($canStartAnother)
        <div style="background: #f9fafb; border: 1px solid #d1d5db; padding: 12px; border-radius: 8px; margin-top: 16px;">
            Na isti javni poziv možete podnijeti još jednu prijavu, za drugu vrstu subvencije.
            <a href="{{ route('applications.canonical.legal-form', $competition) }}">Započni drugu prijavu</a>
        </div>
    @endif

    <p style="margin-top: 20px;"><a href="{{ route('dashboard') }}">← Nazad</a></p>
</div>
@endsection
