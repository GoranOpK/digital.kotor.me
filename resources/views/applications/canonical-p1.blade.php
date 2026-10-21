@extends('layouts.app')

@section('content')
@include('partials.canonical-ui')
@php
    $isEntrepreneur = $form->isEntrepreneur();
    $code = $form->formCode();
    $competition = $application->competition;
    $upBroj = $competition?->upNumber?->number ?? '—';
    $redniBroj = $application->redni_broj ?? '—';
    $brojPrijave = $upBroj.'/'.$redniBroj;
    $value = fn (string $field) => old($field, $field === 'registration_date' ? $form->registration_date?->toDateString() : $form->{$field});
    $required = array_merge(
        array_keys(app(\App\Services\Competitions\CanonicalApplicantFormService::class)->requiredFields($form->legal_form)),
        ['pib', 'accuracy_confirmed'],
    );
@endphp
<style>
    .mmsp-p1 {
        --mmsp-primary: #0B3D91;
        --mmsp-primary-dark: #0A347B;
        background: #f9fafb;
        min-height: 100vh;
        padding: 24px 0 40px;
    }
    .mmsp-p1-wrap {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 16px;
    }
    .mmsp-p1-card {
        background: #fff;
        border-radius: 16px;
        padding: 32px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        margin-bottom: 24px;
    }
    .mmsp-p1-card h2 {
        font-size: 20px;
        font-weight: 700;
        color: var(--mmsp-primary);
        margin: 0 0 8px;
        padding-bottom: 12px;
        border-bottom: 2px solid #e5e7eb;
    }
    .mmsp-p1-hint {
        font-size: 13px;
        color: #6b7280;
        margin: 0 0 20px;
    }
    .mmsp-p1-full,
    .mmsp-p1-field {
        margin-bottom: 20px;
    }
    .mmsp-p1-row {
        display: grid;
        grid-template-columns: 1fr;
        gap: 20px;
        margin-bottom: 0;
    }
    @media (min-width: 768px) {
        .mmsp-p1-row {
            grid-template-columns: 1fr 1fr;
        }
    }
    .mmsp-p1-label {
        display: block;
        font-size: 14px;
        font-weight: 600;
        color: #374151;
        margin-bottom: 8px;
    }
    .mmsp-p1-req {
        color: #ef4444;
        margin-left: 4px;
    }
    .mmsp-p1-control {
        width: 100%;
        padding: 10px 14px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        font-size: 14px;
        background: #fff;
        color: #111827;
        box-sizing: border-box;
    }
    .mmsp-p1-control:focus {
        outline: none;
        border-color: var(--mmsp-primary);
        box-shadow: 0 0 0 3px rgba(11, 61, 145, 0.1);
    }
    .mmsp-p1-control.is-invalid {
        border-color: #ef4444;
    }
    .mmsp-p1 fieldset:disabled .mmsp-p1-control,
    .mmsp-p1-control:disabled {
        background: #f3f4f6;
        color: #374151;
        cursor: not-allowed;
    }
    .mmsp-p1-help {
        font-size: 12px;
        color: #6b7280;
        margin-top: 4px;
    }
    .mmsp-p1-error {
        color: #ef4444;
        font-size: 12px;
        margin-top: 4px;
    }
    .mmsp-p1-check {
        display: flex;
        align-items: flex-start;
        gap: 12px;
    }
    .mmsp-p1-check input[type="checkbox"] {
        width: 18px;
        height: 18px;
        min-width: 18px;
        margin-top: 2px;
        cursor: pointer;
    }
    .mmsp-p1-check label {
        font-size: 14px;
        color: #374151;
        line-height: 1.5;
        margin: 0;
        cursor: pointer;
    }
    .mmsp-p1-submit {
        background: var(--mmsp-primary);
        color: #fff;
        padding: 12px 32px;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        font-size: 16px;
        cursor: pointer;
        margin: 0 0 24px;
    }
    .mmsp-p1-submit:hover {
        background: var(--mmsp-primary-dark);
    }
    .mmsp-p1-alert {
        border-radius: 12px;
        padding: 16px 20px;
        margin-bottom: 20px;
        border: 1px solid;
    }
    .mmsp-p1-alert-success {
        background: #ecfdf5;
        border-color: #10b981;
        color: #065f46;
    }
    .mmsp-p1-alert-error {
        background: #fee2e2;
        border-color: #f87171;
        color: #991b1b;
    }
    .mmsp-p1-alert-info {
        background: #dbeafe;
        border-color: #3b82f6;
        color: #1e40af;
    }
    .mmsp-p1-header {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 20px 24px;
        margin-bottom: 24px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.06);
    }
    .mmsp-p1-header-top {
        display: flex;
        align-items: flex-start;
        gap: 20px;
        margin-bottom: 16px;
    }
    .mmsp-p1-crest {
        flex-shrink: 0;
        line-height: 0;
    }
    .mmsp-p1-crest img {
        height: 2cm;
        width: auto;
        display: block;
    }
    .mmsp-p1-org {
        flex: 1;
        font-size: 13px;
        line-height: 1.5;
        color: #111;
    }
    .mmsp-p1-org p,
    .mmsp-p1-contact p {
        margin: 0 0 2px 0;
    }
    .mmsp-p1-contact {
        text-align: right;
        font-size: 13px;
        line-height: 1.5;
        color: #111;
    }
    .mmsp-p1-meta {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
        margin-top: 12px;
        padding-top: 12px;
        border-top: 1px solid #e5e7eb;
    }
    .mmsp-p1-number,
    .mmsp-p1-code {
        font-size: 14px;
        font-weight: 700;
        color: #111;
    }
    .mmsp-p1-code {
        color: #374151;
    }
    .mmsp-p1-title {
        width: 100%;
        text-align: center;
        font-size: 22px;
        font-weight: 700;
        letter-spacing: 0.02em;
        color: #111;
        margin: 8px 0 0;
    }
    .mmsp-p1-subtitle {
        width: 100%;
        text-align: center;
        font-size: 14px;
        line-height: 1.5;
        color: #374151;
        margin: 12px 0 0;
    }
    .mmsp-p1-call {
        text-align: center;
        font-size: 13px;
        color: #6b7280;
        margin: 8px 0 0;
    }
    .mmsp-p1-nav {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
    }
    .mmsp-p1-nav a {
        display: inline-block;
        background: #fff;
        color: var(--mmsp-primary);
        border: 1px solid #d1d5db;
        border-radius: 8px;
        padding: 10px 16px;
        font-size: 14px;
        font-weight: 600;
        text-decoration: none;
    }
    .mmsp-p1-nav a:hover {
        border-color: var(--mmsp-primary);
        background: #f8fafc;
    }
    @media (max-width: 640px) {
        .mmsp-p1-header-top {
            flex-direction: column;
        }
        .mmsp-p1-contact {
            text-align: left;
        }
        .mmsp-p1-card {
            padding: 20px;
        }
    }
</style>

<div class="mmsp-p1 canonical-ui-page mmsp-form-page">
    <div class="mmsp-p1-wrap">
        <div class="mmsp-p1-header">
            <div class="mmsp-p1-header-top">
                <div class="mmsp-p1-crest">
                    <img src="{{ asset('images/srednji_grb.png') }}" alt="Grb Opštine Kotor" onerror="this.onerror=null; this.src='{{ asset('images/srednji_grb.svg') }}';">
                </div>
                <div class="mmsp-p1-org">
                    <p><strong>Crna Gora</strong></p>
                    <p>Opština Kotor</p>
                    <p>Sekretarijat za razvoj preduzetništva,</p>
                    <p>komunalne poslove i saobraćaj</p>
                </div>
                <div class="mmsp-p1-contact">
                    <p>Stari grad 317</p>
                    <p>85330 Kotor, Crna Gora</p>
                    <p>tel. +382(0)32 325 865</p>
                    <p>privreda@kotor.me</p>
                    <p>www.kotor.me</p>
                </div>
            </div>
            <div class="mmsp-p1-meta">
                <div class="mmsp-p1-number">Broj prijave: {{ $brojPrijave }}</div>
                <div class="mmsp-p1-code">Obrazac {{ $code }}</div>
            </div>
            <h1 class="mmsp-p1-title">PRIJAVA</h1>
            <p class="mmsp-p1-subtitle">
                na javni poziv za podršku mikro, malim i srednjim preduzećima<br>
                Tip prijave: <strong>{{ $isEntrepreneur ? 'PREDUZETNIK' : 'PRIVREDNO DRUŠTVO (DOO)' }}</strong>
            </p>
            @if($competition?->title)
                <p class="mmsp-p1-call">{{ $competition->title }}</p>
            @endif
        </div>

        @if(session('success'))
            <div class="mmsp-p1-alert mmsp-p1-alert-success">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="mmsp-p1-alert mmsp-p1-alert-error">
                <strong>Ispravite sljedeće greške:</strong>
                <ul style="margin: 8px 0 0; padding-left: 20px;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        @if($readOnly)
            <div class="mmsp-p1-alert mmsp-p1-alert-info">
                {{ $form->isFrozen() ? \App\Models\CanonicalApplicantForm::FROZEN_MESSAGE : 'Prijava je zaključana i Obrazac '.$code.' više nije moguće mijenjati.' }}
            </div>
        @endif

        <form method="POST" action="{{ route('applications.canonical-p1.update', $application) }}">
            @csrf
            @method('PUT')

            <fieldset @disabled($readOnly) style="border: 0; padding: 0; margin: 0;">
                <div class="mmsp-p1-card">
                    <h2>Podaci prijave</h2>
                    <p class="mmsp-p1-hint">Polja označena <span class="mmsp-p1-req">*</span> obavezna su za konačnu predaju. Obrazac se može sačuvati i dok nijesu sva popunjena.</p>

                    <div class="mmsp-p1-full">
                        <label class="mmsp-p1-label" for="subsidy_type">Vrsta subvencije @if(in_array('subsidy_type', $required, true))<span class="mmsp-p1-req">*</span>@endif</label>
                        <select id="subsidy_type" name="subsidy_type" class="mmsp-p1-control @error('subsidy_type') is-invalid @enderror">
                            <option value="">— izaberite vrstu subvencije —</option>
                            @foreach($subsidyTypes as $typeCode => $label)
                                <option value="{{ $typeCode }}" @selected($value('subsidy_type') === $typeCode)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <div class="mmsp-p1-help">Mora biti ista vrsta subvencije kao u Planu ulaganja (Obrazac P2).</div>
                        @error('subsidy_type')<div class="mmsp-p1-error">{{ $message }}</div>@enderror
                    </div>

                    @unless($isEntrepreneur)
                        <div class="mmsp-p1-full">
                            <label class="mmsp-p1-label" for="company_name">Naziv društva @if(in_array('company_name', $required, true))<span class="mmsp-p1-req">*</span>@endif</label>
                            <input id="company_name" type="text" name="company_name" value="{{ $value('company_name') }}" class="mmsp-p1-control @error('company_name') is-invalid @enderror">
                            @error('company_name')<div class="mmsp-p1-error">{{ $message }}</div>@enderror
                        </div>
                        <div class="mmsp-p1-full">
                            <label class="mmsp-p1-label" for="address">Sjedište @if(in_array('address', $required, true))<span class="mmsp-p1-req">*</span>@endif</label>
                            <input id="address" type="text" name="address" value="{{ $value('address') }}" placeholder="npr. Njegoševa 1, 85330 Kotor" class="mmsp-p1-control @error('address') is-invalid @enderror">
                            @error('address')<div class="mmsp-p1-error">{{ $message }}</div>@enderror
                        </div>
                        <div class="mmsp-p1-full">
                            <label class="mmsp-p1-label" for="website">Website</label>
                            <input id="website" type="text" name="website" value="{{ $value('website') }}" class="mmsp-p1-control @error('website') is-invalid @enderror">
                            @error('website')<div class="mmsp-p1-error">{{ $message }}</div>@enderror
                        </div>
                        <div class="mmsp-p1-row">
                            <div class="mmsp-p1-field">
                                <label class="mmsp-p1-label" for="person_name">Ime i prezime odgovornog lica @if(in_array('person_name', $required, true))<span class="mmsp-p1-req">*</span>@endif</label>
                                <input id="person_name" type="text" name="person_name" value="{{ $value('person_name') }}" class="mmsp-p1-control @error('person_name') is-invalid @enderror">
                                @error('person_name')<div class="mmsp-p1-error">{{ $message }}</div>@enderror
                            </div>
                            <div class="mmsp-p1-field">
                                <label class="mmsp-p1-label" for="pib">PIB društva @if(in_array('pib', $required, true))<span class="mmsp-p1-req">*</span>@endif</label>
                                <input id="pib" type="text" name="pib" inputmode="numeric" value="{{ old('pib', $application->pib) }}" class="mmsp-p1-control @error('pib') is-invalid @enderror">
                                @error('pib')<div class="mmsp-p1-error">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="mmsp-p1-row">
                            <div class="mmsp-p1-field">
                                <label class="mmsp-p1-label" for="registration_date">Datum osnivanja @if(in_array('registration_date', $required, true))<span class="mmsp-p1-req">*</span>@endif</label>
                                <input id="registration_date" type="date" name="registration_date" value="{{ $value('registration_date') }}" class="mmsp-p1-control @error('registration_date') is-invalid @enderror">
                                @error('registration_date')<div class="mmsp-p1-error">{{ $message }}</div>@enderror
                            </div>
                            <div class="mmsp-p1-field">
                                <label class="mmsp-p1-label" for="activity_code">Šifra djelatnosti @if(in_array('activity_code', $required, true))<span class="mmsp-p1-req">*</span>@endif</label>
                                <input id="activity_code" type="text" name="activity_code" value="{{ $value('activity_code') }}" placeholder="00.00" class="mmsp-p1-control @error('activity_code') is-invalid @enderror">
                                @error('activity_code')<div class="mmsp-p1-error">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="mmsp-p1-row">
                            <div class="mmsp-p1-field">
                                <label class="mmsp-p1-label" for="employee_count">Broj zaposlenih @if(in_array('employee_count', $required, true))<span class="mmsp-p1-req">*</span>@endif</label>
                                <input id="employee_count" type="number" min="0" name="employee_count" value="{{ $value('employee_count') }}" class="mmsp-p1-control @error('employee_count') is-invalid @enderror">
                                @error('employee_count')<div class="mmsp-p1-error">{{ $message }}</div>@enderror
                            </div>
                            <div class="mmsp-p1-field">
                                <label class="mmsp-p1-label" for="bank_account">Broj žiro računa @if(in_array('bank_account', $required, true))<span class="mmsp-p1-req">*</span>@endif</label>
                                <input id="bank_account" type="text" name="bank_account" value="{{ $value('bank_account') }}" class="mmsp-p1-control @error('bank_account') is-invalid @enderror">
                                @error('bank_account')<div class="mmsp-p1-error">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="mmsp-p1-row">
                            <div class="mmsp-p1-field">
                                <label class="mmsp-p1-label" for="phone">Kontakt telefon odgovornog lica @if(in_array('phone', $required, true))<span class="mmsp-p1-req">*</span>@endif</label>
                                <input id="phone" type="text" name="phone" value="{{ $value('phone') }}" class="mmsp-p1-control @error('phone') is-invalid @enderror">
                                @error('phone')<div class="mmsp-p1-error">{{ $message }}</div>@enderror
                            </div>
                            <div class="mmsp-p1-field">
                                <label class="mmsp-p1-label" for="email">E-mail odgovornog lica @if(in_array('email', $required, true))<span class="mmsp-p1-req">*</span>@endif</label>
                                <input id="email" type="email" name="email" value="{{ $value('email') }}" class="mmsp-p1-control @error('email') is-invalid @enderror">
                                @error('email')<div class="mmsp-p1-error">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    @else
                        <div class="mmsp-p1-full">
                            <label class="mmsp-p1-label" for="person_name">Ime i prezime preduzetnika @if(in_array('person_name', $required, true))<span class="mmsp-p1-req">*</span>@endif</label>
                            <input id="person_name" type="text" name="person_name" value="{{ $value('person_name') }}" class="mmsp-p1-control @error('person_name') is-invalid @enderror">
                            @error('person_name')<div class="mmsp-p1-error">{{ $message }}</div>@enderror
                        </div>
                        <div class="mmsp-p1-full">
                            <label class="mmsp-p1-label" for="address">Adresa @if(in_array('address', $required, true))<span class="mmsp-p1-req">*</span>@endif</label>
                            <input id="address" type="text" name="address" value="{{ $value('address') }}" placeholder="npr. Njegoševa 1, 85330 Kotor" class="mmsp-p1-control @error('address') is-invalid @enderror">
                            @error('address')<div class="mmsp-p1-error">{{ $message }}</div>@enderror
                        </div>
                        <div class="mmsp-p1-full">
                            <label class="mmsp-p1-label" for="website">Website</label>
                            <input id="website" type="text" name="website" value="{{ $value('website') }}" class="mmsp-p1-control @error('website') is-invalid @enderror">
                            @error('website')<div class="mmsp-p1-error">{{ $message }}</div>@enderror
                        </div>
                        <div class="mmsp-p1-row">
                            <div class="mmsp-p1-field">
                                <label class="mmsp-p1-label" for="pib">JMBG/PIB — PIB preduzetnika @if(in_array('pib', $required, true))<span class="mmsp-p1-req">*</span>@endif</label>
                                <input id="pib" type="text" name="pib" inputmode="numeric" value="{{ old('pib', $application->pib) }}" class="mmsp-p1-control @error('pib') is-invalid @enderror">
                                <div class="mmsp-p1-help">JMBG preduzetnika preuzima se iz verifikovanog identiteta naloga pri konačnoj predaji, čuva se šifrovano i ne prikazuje se.</div>
                                @error('pib')<div class="mmsp-p1-error">{{ $message }}</div>@enderror
                            </div>
                            <div class="mmsp-p1-field">
                                <label class="mmsp-p1-label" for="activity_code">Šifra djelatnosti @if(in_array('activity_code', $required, true))<span class="mmsp-p1-req">*</span>@endif</label>
                                <input id="activity_code" type="text" name="activity_code" value="{{ $value('activity_code') }}" placeholder="00.00" class="mmsp-p1-control @error('activity_code') is-invalid @enderror">
                                @error('activity_code')<div class="mmsp-p1-error">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="mmsp-p1-row">
                            <div class="mmsp-p1-field">
                                <label class="mmsp-p1-label" for="employee_count">Broj zaposlenih @if(in_array('employee_count', $required, true))<span class="mmsp-p1-req">*</span>@endif</label>
                                <input id="employee_count" type="number" min="0" name="employee_count" value="{{ $value('employee_count') }}" class="mmsp-p1-control @error('employee_count') is-invalid @enderror">
                                @error('employee_count')<div class="mmsp-p1-error">{{ $message }}</div>@enderror
                            </div>
                            <div class="mmsp-p1-field">
                                <label class="mmsp-p1-label" for="bank_account">Broj žiro računa @if(in_array('bank_account', $required, true))<span class="mmsp-p1-req">*</span>@endif</label>
                                <input id="bank_account" type="text" name="bank_account" value="{{ $value('bank_account') }}" class="mmsp-p1-control @error('bank_account') is-invalid @enderror">
                                @error('bank_account')<div class="mmsp-p1-error">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="mmsp-p1-row">
                            <div class="mmsp-p1-field">
                                <label class="mmsp-p1-label" for="phone">Kontakt telefon @if(in_array('phone', $required, true))<span class="mmsp-p1-req">*</span>@endif</label>
                                <input id="phone" type="text" name="phone" value="{{ $value('phone') }}" class="mmsp-p1-control @error('phone') is-invalid @enderror">
                                @error('phone')<div class="mmsp-p1-error">{{ $message }}</div>@enderror
                            </div>
                            <div class="mmsp-p1-field">
                                <label class="mmsp-p1-label" for="email">E-mail @if(in_array('email', $required, true))<span class="mmsp-p1-req">*</span>@endif</label>
                                <input id="email" type="email" name="email" value="{{ $value('email') }}" class="mmsp-p1-control @error('email') is-invalid @enderror">
                                @error('email')<div class="mmsp-p1-error">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="mmsp-p1-row">
                            <div class="mmsp-p1-field">
                                <label class="mmsp-p1-label" for="registration_date">Datum registracije u CRPS @if(in_array('registration_date', $required, true))<span class="mmsp-p1-req">*</span>@endif</label>
                                <input id="registration_date" type="date" name="registration_date" value="{{ $value('registration_date') }}" class="mmsp-p1-control @error('registration_date') is-invalid @enderror">
                                @error('registration_date')<div class="mmsp-p1-error">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    @endunless
                </div>

                <div class="mmsp-p1-card">
                    <h2>Izjava o tačnosti podataka</h2>
                    <div class="mmsp-p1-check">
                        <input id="accuracy_confirmed" type="checkbox" name="accuracy_confirmed" value="1" @checked(old('accuracy_confirmed', $form->accuracy_confirmed_at !== null)) @disabled($readOnly)>
                        <label for="accuracy_confirmed">
                            @if(in_array('accuracy_confirmed', $required, true))<span class="mmsp-p1-req">*</span>@endif
                            {{ $accuracyStatement }}
                        </label>
                    </div>
                    <div class="mmsp-p1-help" style="margin-top: 12px;">
                        {{ $isEntrepreneur ? 'Potpis preduzetnika' : 'Potpis odgovornog lica' }}: elektronska potvrda podnosioca prijavljenog na nalog.
                        @if($form->accuracy_confirmed_at)
                            Potvrđeno {{ $form->accuracy_confirmed_at->timezone('Europe/Belgrade')->format('d.m.Y. H:i') }}.
                        @endif
                    </div>
                    @error('accuracy_confirmed')<div class="mmsp-p1-error">{{ $message }}</div>@enderror
                </div>

                @unless($readOnly)
                    <button type="submit" class="mmsp-p1-submit">Sačuvaj Obrazac {{ $code }}</button>
                @endunless
            </fieldset>
        </form>

        <nav class="mmsp-p1-nav" aria-label="Navigacija obrasca">
            <a href="{{ route('applications.show', $application) }}">Nazad na prijavu</a>
            <a href="{{ route('applications.canonical-p2.edit', $application) }}">Plan ulaganja (Obrazac P2)</a>
            <a href="{{ route('applications.canonical-documents.index', $application) }}">Prateća dokumentacija</a>
        </nav>
    </div>
</div>
@endsection
