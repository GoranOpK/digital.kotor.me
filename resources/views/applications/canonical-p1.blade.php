@extends('layouts.app')

@section('content')
@include('partials.canonical-ui')
@php
    $isEntrepreneur = $form->isEntrepreneur();
    $code = $form->formCode();
    $fieldStyle = 'width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 6px;';
    $value = fn (string $field) => old($field, $field === 'registration_date' ? $form->registration_date?->toDateString() : $form->{$field});
@endphp
<div class="container mx-auto px-4 py-6 canonical-ui-page" style="max-width: 900px;">
    <h1 style="font-size: 24px; font-weight: 700; margin-bottom: 4px;">Obrazac {{ $code }} — Prijava na Javni poziv</h1>
    <p style="color: #4b5563; margin-bottom: 4px;">
        Oblik registracije: <strong>{{ $isEntrepreneur ? 'PREDUZETNIK' : 'DRUŠTVO (DOO)' }}</strong>
    </p>
    <p style="color: #4b5563; margin-bottom: 16px;">{{ $application->competition->title }}</p>

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
            {{ $form->isFrozen() ? \App\Models\CanonicalApplicantForm::FROZEN_MESSAGE : 'Prijava je zaključana i Obrazac '.$code.' više nije moguće mijenjati.' }}
        </div>
    @endif

    <form method="POST" action="{{ route('applications.canonical-p1.update', $application) }}">
        @csrf
        @method('PUT')

        <fieldset @disabled($readOnly) style="border: 0; padding: 0; margin: 0; display: grid; gap: 12px;">
            <label>Vrsta subvencije
                <select name="subsidy_type" style="{{ $fieldStyle }}">
                    <option value="">— izaberite vrstu subvencije —</option>
                    @foreach($subsidyTypes as $typeCode => $label)
                        <option value="{{ $typeCode }}" @selected($value('subsidy_type') === $typeCode)>{{ $label }}</option>
                    @endforeach
                </select>
                <small style="color: #6b7280;">Mora biti ista vrsta subvencije kao u Planu ulaganja (Obrazac P2).</small>
            </label>

            @unless($isEntrepreneur)
                <label>Naziv društva
                    <input type="text" name="company_name" value="{{ $value('company_name') }}" style="{{ $fieldStyle }}">
                </label>
            @endunless

            <label>{{ $isEntrepreneur ? 'Ime i prezime preduzetnika' : 'Ime i prezime odgovornog lica' }}
                <input type="text" name="person_name" value="{{ $value('person_name') }}" style="{{ $fieldStyle }}">
            </label>

            <label>{{ $isEntrepreneur ? 'JMBG/PIB — PIB preduzetnika' : 'PIB društva' }}
                <input type="text" name="pib" inputmode="numeric" value="{{ old('pib', $application->pib) }}" style="{{ $fieldStyle }}">
                @if($isEntrepreneur)
                    <small style="color: #6b7280;">JMBG preduzetnika preuzima se iz verifikovanog identiteta naloga pri konačnoj predaji, čuva se šifrovano i ne prikazuje se.</small>
                @endif
            </label>

            <label>{{ $isEntrepreneur ? 'Adresa' : 'Sjedište' }}
                <input type="text" name="address" value="{{ $value('address') }}" placeholder="npr. Njegoševa 1, 85330 Kotor" style="{{ $fieldStyle }}">
            </label>

            @unless($isEntrepreneur)
                <label>Datum osnivanja
                    <input type="date" name="registration_date" value="{{ $value('registration_date') }}" style="{{ $fieldStyle }}">
                </label>
            @endunless

            <label>Šifra djelatnosti
                <input type="text" name="activity_code" value="{{ $value('activity_code') }}" placeholder="00.00" style="{{ $fieldStyle }}">
            </label>

            <label>Broj zaposlenih
                <input type="number" min="0" name="employee_count" value="{{ $value('employee_count') }}" style="{{ $fieldStyle }}">
            </label>

            <label>Broj žiro računa
                <input type="text" name="bank_account" value="{{ $value('bank_account') }}" style="{{ $fieldStyle }}">
            </label>

            <label>{{ $isEntrepreneur ? 'Kontakt telefon' : 'Kontakt telefon odgovornog lica' }}
                <input type="text" name="phone" value="{{ $value('phone') }}" style="{{ $fieldStyle }}">
            </label>

            <label>{{ $isEntrepreneur ? 'E-mail' : 'E-mail odgovornog lica' }}
                <input type="email" name="email" value="{{ $value('email') }}" style="{{ $fieldStyle }}">
            </label>

            <label>Website
                <input type="text" name="website" value="{{ $value('website') }}" style="{{ $fieldStyle }}">
            </label>

            @if($isEntrepreneur)
                <label>Datum registracije u CRPS
                    <input type="date" name="registration_date" value="{{ $value('registration_date') }}" style="{{ $fieldStyle }}">
                </label>
            @endif

            <label style="display: block; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px;">
                <input type="checkbox" name="accuracy_confirmed" value="1" @checked(old('accuracy_confirmed', $form->accuracy_confirmed_at !== null))>
                {{ $accuracyStatement }}
                <br>
                <small style="color: #6b7280;">
                    {{ $isEntrepreneur ? 'Potpis preduzetnika' : 'Potpis odgovornog lica' }}: elektronska potvrda podnosioca prijavljenog na nalog.
                    @if($form->accuracy_confirmed_at)
                        Potvrđeno {{ $form->accuracy_confirmed_at->timezone('Europe/Belgrade')->format('d.m.Y. H:i') }}.
                    @endif
                </small>
            </label>

            @unless($readOnly)
                <button type="submit" class="btn btn-primary">Sačuvaj Obrazac {{ $code }}</button>
            @endunless
        </fieldset>
    </form>

    <p style="margin-top: 20px;">
        <a href="{{ route('applications.show', $application) }}">← Nazad na prijavu</a>
        · <a href="{{ route('applications.canonical-p2.edit', $application) }}">Plan ulaganja (Obrazac P2)</a>
        · <a href="{{ route('applications.canonical-documents.index', $application) }}">Prateća dokumentacija</a>
    </p>
</div>
@endsection
