@extends('layouts.app')

@section('content')
@include('partials.canonical-ui')
@php
    $fieldStyle = 'padding: 6px; border: 1px solid #d1d5db; border-radius: 6px;';
@endphp
<div class="container mx-auto px-4 py-6 canonical-ui-page" style="max-width: 1100px;">
    <h1 style="font-size: 24px; font-weight: 700; margin-bottom: 4px;">Prateća dokumentacija (čl.14 Odluke)</h1>
    <p style="color: #4b5563; margin-bottom: 16px;">
        {{ $application->competition->title }} — prilozi se učitavaju elektronski u PDF formatu. Uvjerenja i potvrde označene rokom ne smiju biti starije od 30 dana na dan konačne predaje.
    </p>

    @if(session('success'))
        <div style="background: #ecfdf5; border: 1px solid #10b981; padding: 12px; border-radius: 8px; margin-bottom: 16px;">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div style="background: #fef2f2; border: 1px solid #ef4444; padding: 12px; border-radius: 8px; margin-bottom: 16px;">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif
    @if($readOnly)
        <div style="background: #eff6ff; border: 1px solid #3b82f6; padding: 12px; border-radius: 8px; margin-bottom: 16px;">
            {{ $application->status === 'draft' ? 'Rok za prijave je istekao. Dokumentaciju više nije moguće mijenjati.' : 'Prijava je konačno podnesena; priložena dokumentacija je zamrznuta.' }}
        </div>
    @endif

    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="background: #f3f4f6;">
                <th style="text-align: left; padding: 8px; width: 40px;">Br.</th>
                <th style="text-align: left; padding: 8px;">Dokument</th>
                <th style="text-align: left; padding: 8px; width: 380px;">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($checklist as $row)
                <tr style="border-top: 1px solid #e5e7eb; vertical-align: top;">
                    <td style="padding: 8px;">{{ $row['item'] }}.</td>
                    <td style="padding: 8px;">
                        {{ $row['label'] }}
                        @if($row['condition'])
                            <div style="color: #6b7280; font-size: 13px;">{{ $row['condition'] }}</div>
                        @endif
                    </td>
                    <td style="padding: 8px;">
                        @if($row['source'] === \App\Support\CanonicalDocumentCatalog::SOURCE_FORM)
                            @if($row['key'] === \App\Support\CanonicalDocumentCatalog::FORM_P1)
                                <a href="{{ route('applications.canonical-p1.edit', $application) }}">Obrazac P1 (elektronski)</a>
                            @else
                                <a href="{{ route('applications.canonical-p2.edit', $application) }}">Obrazac P2 (elektronski)</a>
                            @endif
                        @elseif($row['document'])
                            <div>Priloženo: <a href="{{ route('applications.document.view', [$application, $row['document']]) }}" target="_blank">{{ $row['document']->name }}</a></div>
                            @if($row['variant_label'])
                                <div style="font-size: 13px;">{{ $row['variant_label'] }}</div>
                            @endif
                            @if($row['issued_on'])
                                <div style="font-size: 13px;">Datum izdavanja: {{ \Carbon\Carbon::parse($row['issued_on'])->format('d.m.Y.') }}</div>
                            @endif
                            @if($row['validity_issue'])
                                <div style="color: #b91c1c; font-size: 13px;">{{ $row['validity_issue'] }}</div>
                            @endif
                            @unless($readOnly)
                                <form method="POST" action="{{ route('applications.document.destroy', [$application, $row['document']]) }}" style="margin-top: 4px;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm">Ukloni</button>
                                </form>
                            @endunless
                        @else
                            <div style="color: {{ $row['required_now'] ? '#b91c1c' : '#6b7280' }};">
                                {{ $row['required_now'] ? 'Nedostaje (obavezno)' : 'Nije obavezno u vašem slučaju' }}
                            </div>
                            @unless($readOnly)
                                <form method="POST" action="{{ route('applications.upload', $application) }}" enctype="multipart/form-data" style="margin-top: 6px; display: grid; gap: 6px;">
                                    @csrf
                                    <input type="hidden" name="document_type" value="{{ $row['key'] }}">
                                    @foreach($row['variants'] as $variantCode => $variantLabel)
                                        <label style="font-size: 13px;">
                                            <input type="radio" name="document_variant" value="{{ $variantCode }}"> {{ $variantLabel }}
                                        </label>
                                    @endforeach
                                    @if($row['needs_issue_date'])
                                        <label style="font-size: 13px;">Datum izdavanja
                                            <input type="date" name="issued_on" style="{{ $fieldStyle }}">
                                        </label>
                                    @endif
                                    <input type="file" name="files[]" accept="application/pdf">
                                    @if($libraryDocuments->isNotEmpty())
                                        <select name="user_document_id" style="{{ $fieldStyle }}">
                                            <option value="">— ili izaberite PDF iz biblioteke —</option>
                                            @foreach($libraryDocuments as $libraryDocument)
                                                <option value="{{ $libraryDocument->id }}">{{ $libraryDocument->name }}</option>
                                            @endforeach
                                        </select>
                                    @endif
                                    <button type="submit" class="btn btn-sm btn-primary">Priloži</button>
                                </form>
                            @endunless
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p style="margin-top: 20px;"><a href="{{ route('applications.show', $application) }}">← Nazad na prijavu</a></p>
</div>
@endsection
