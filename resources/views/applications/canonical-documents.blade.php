@extends('layouts.app')

@section('content')
@include('partials.canonical-ui')

<div class="canonical-ui-page mmsp-form-page">
    <div class="container mx-auto px-4" style="max-width: 1200px;">
        <div class="mmsp-page-header">
            <h1>Prateća dokumentacija</h1>
            <p>
                Podrška mikro, malim i srednjim preduzećima<br>
                <strong>Konkurs: {{ $application->competition->title }}</strong>
            </p>
        </div>

        @if(session('success'))
            <div class="mmsp-alert mmsp-alert-success">{{ session('success') }}</div>
        @endif
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
        @if($readOnly)
            <div class="mmsp-alert mmsp-alert-info">
                {{ $application->status === 'draft'
                    ? 'Rok za prijave je istekao. Dokumentaciju više nije moguće mijenjati.'
                    : 'Prijava je konačno podnesena; priložena dokumentacija je zamrznuta.' }}
            </div>
        @endif

        <div class="mmsp-form-card">
            <div class="mmsp-form-section">
                <h2>Obavezna dokumentacija prema članu 14 Odluke</h2>
                <p class="mmsp-form-text" style="margin-bottom:18px;">
                    Dokumentacija je prilagođena tipu vaše prijave. Prilozi se učitavaju elektronski u PDF formatu.
                    Uvjerenja i potvrde označene rokom ne smiju biti starije od 30 dana na dan konačne predaje.
                </p>

                <div style="overflow-x:auto;">
                    <table class="mmsp-dynamic-table">
                        <thead>
                            <tr>
                                <th style="width:56px;">Br.</th>
                                <th>Dokument</th>
                                <th style="width:390px;">Status / prilaganje</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($checklist as $row)
                                <tr>
                                    <td>{{ $row['item'] }}.</td>
                                    <td>
                                        <strong>{{ $row['label'] }}</strong>
                                        @if($row['condition'])
                                            <div class="mmsp-form-text">{{ $row['condition'] }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        @if($row['source'] === \App\Support\CanonicalDocumentCatalog::SOURCE_FORM)
                                            @if($row['key'] === \App\Support\CanonicalDocumentCatalog::FORM_P1)
                                                <a class="mmsp-btn-secondary" href="{{ route('applications.canonical-p1.edit', $application) }}">Otvori Obrazac P1</a>
                                            @else
                                                <a class="mmsp-btn-secondary" href="{{ route('applications.canonical-p2.edit', $application) }}">Otvori Obrazac P2</a>
                                            @endif
                                        @elseif($row['document'])
                                            <div style="font-weight:600; color:#065f46; margin-bottom:6px;">✓ Priloženo</div>
                                            <div>
                                                <a href="{{ route('applications.document.view', [$application, $row['document']]) }}" target="_blank">
                                                    {{ $row['document']->name }}
                                                </a>
                                            </div>
                                            @if($row['variant_label'])
                                                <div class="mmsp-form-text">{{ $row['variant_label'] }}</div>
                                            @endif
                                            @if($row['issued_on'])
                                                <div class="mmsp-form-text">Datum izdavanja: {{ \Carbon\Carbon::parse($row['issued_on'])->format('d.m.Y.') }}</div>
                                            @endif
                                            @if($row['validity_issue'])
                                                <div style="color:#b91c1c; font-size:12px; margin-top:4px;">{{ $row['validity_issue'] }}</div>
                                            @endif
                                            @unless($readOnly)
                                                <form method="POST" action="{{ route('applications.document.destroy', [$application, $row['document']]) }}" style="margin-top:8px;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="mmsp-btn-secondary">Ukloni</button>
                                                </form>
                                            @endunless
                                        @else
                                            <div style="font-weight:600; color:{{ $row['required_now'] ? '#b91c1c' : '#6b7280' }}; margin-bottom:8px;">
                                                {{ $row['required_now'] ? 'Nedostaje — obavezno' : 'Nije obavezno u vašem slučaju' }}
                                            </div>

                                            @unless($readOnly)
                                                <form method="POST" action="{{ route('applications.upload', $application) }}" enctype="multipart/form-data">
                                                    @csrf
                                                    <input type="hidden" name="document_type" value="{{ $row['key'] }}">

                                                    @if(!empty($row['variants']))
                                                        <div class="mmsp-form-group" style="margin-bottom:10px;">
                                                            @foreach($row['variants'] as $variantCode => $variantLabel)
                                                                <label style="display:flex; gap:8px; align-items:flex-start; font-size:13px; margin-bottom:6px;">
                                                                    <input type="radio" name="document_variant" value="{{ $variantCode }}" style="margin-top:3px;">
                                                                    <span>{{ $variantLabel }}</span>
                                                                </label>
                                                            @endforeach
                                                        </div>
                                                    @endif

                                                    @if($row['needs_issue_date'])
                                                        <div class="mmsp-form-group" style="margin-bottom:10px;">
                                                            <label class="mmsp-form-label" for="issued_on_{{ $row['key'] }}">Datum izdavanja</label>
                                                            <input id="issued_on_{{ $row['key'] }}" type="date" name="issued_on" class="mmsp-form-control">
                                                        </div>
                                                    @endif

                                                    <div class="mmsp-form-group" style="margin-bottom:10px;">
                                                        <label class="mmsp-form-label">PDF dokument</label>
                                                        <input type="file" name="files[]" accept="application/pdf" class="mmsp-form-control">
                                                    </div>

                                                    @if($libraryDocuments->isNotEmpty())
                                                        <div class="mmsp-form-group" style="margin-bottom:10px;">
                                                            <label class="mmsp-form-label">Ili izaberite iz biblioteke</label>
                                                            <select name="user_document_id" class="mmsp-form-control">
                                                                <option value="">— izaberite PDF iz biblioteke —</option>
                                                                @foreach($libraryDocuments as $libraryDocument)
                                                                    <option value="{{ $libraryDocument->id }}">{{ $libraryDocument->name }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                    @endif

                                                    <button type="submit" class="btn-primary">Priloži dokument</button>
                                                </form>
                                            @endunless
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="mmsp-actions">
            <a class="mmsp-btn-secondary" href="{{ route('applications.show', $application) }}">← Nazad na prijavu</a>
            <a class="mmsp-btn-secondary" href="{{ route('applications.canonical-p1.edit', $application) }}">Obrazac {{ $application->canonicalApplicantForm?->formCode() ?? 'P1' }}</a>
            <a class="mmsp-btn-secondary" href="{{ route('applications.canonical-p2.edit', $application) }}">Plan ulaganja (P2)</a>
        </div>
    </div>
</div>
@endsection
