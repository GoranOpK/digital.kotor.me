@extends('layouts.app')

@section('content')
@include('partials.canonical-ui')
<div class="container mx-auto px-4 py-6 canonical-ui-page" style="max-width: 800px;">
    <h1 style="font-size: 24px; font-weight: 700; margin-bottom: 4px;">Prijava na javni poziv — pravna forma podnosioca</h1>
    <p style="color: #4b5563; margin-bottom: 16px;">{{ $competition->title }}</p>

    @if($errors->any())
        <div style="background: #fef2f2; border: 1px solid #ef4444; padding: 12px; border-radius: 8px; margin-bottom: 16px;">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <p style="margin-bottom: 12px;">
        Izaberite oblik registracije podnosioca prijave. Preduzetnik popunjava Obrazac P1a, a društvo Obrazac P1b.
        Pravna forma određuje obrazac i listu prateće dokumentacije i ne mijenja se nakon izbora.
    </p>

    <form method="POST" action="{{ route('applications.canonical.start', $competition) }}">
        @csrf
        @foreach($legalForms as $code => $label)
            <label style="display: block; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; margin-bottom: 8px;">
                <input type="radio" name="legal_form" value="{{ $code }}" @checked(old('legal_form') === $code)>
                <strong>{{ $label }}</strong>
                — Obrazac {{ \App\Support\CanonicalLegalForm::formCode($code) }}
            </label>
        @endforeach
        <button type="submit" class="btn btn-primary" style="margin-top: 12px;">Započni prijavu</button>
    </form>

    <p style="margin-top: 20px;"><a href="{{ route('competitions.show', $competition) }}">← Nazad na javni poziv</a></p>
</div>
@endsection
