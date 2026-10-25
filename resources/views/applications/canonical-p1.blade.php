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
    // MMSP P1a only: show profile defaults in empty fields without modifying saved form data.
    $identity = app(\App\Identity\Runtime\CurrentIdentityResolver::class)->viewFor(auth()->user());
    $profileDefaults = $isEntrepreneur && $identity->hasCurrentSubjectIdentity() ? [
        'person_name' => $identity->displayName() === 'N/A' ? null : $identity->displayName(),
        'address' => \App\Support\KotorAddress::formatStreetAndCity($identity->address, $identity->city),
        'phone' => $identity->phone,
        'email' => auth()->user()?->email,
    ] : [];
    $prefillValue = function (string $field) use ($form, $profileDefaults): mixed {
        // old() (even an explicitly empty input) always wins after validation errors.
        $oldValues = session()->getOldInput();
        if (array_key_exists($field, $oldValues)) {
            return $oldValues[$field];
        }
        $stored = $field === 'registration_date' ? $form->registration_date?->toDateString() : $form->{$field};
        return $stored !== null && $stored !== '' ? $stored : ($profileDefaults[$field] ?? $stored);
    };
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
        margin: 0 auto;
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
        transition: border-color 0.2s;
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
        min-height: 18px;
        max-width: 18px;
        max-height: 18px;
        margin-top: 2px;
        cursor: pointer;
        flex-shrink: 0;
        box-sizing: border-box;
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
        border-color: #93c5fd;
        color: #1e40af;
    }
    .mmsp-p1-type-card {
        background:#fff;
        border-radius:16px;
        padding:28px 32px;
        box-shadow:0 1px 3px rgba(0,0,0,.1);
        margin-bottom:24px;
    }
    .mmsp-p1-type-note {
        color:#6b7280;
        font-size:13px;
        margin:0 0 14px;
        line-height:1.5;
    }
    .mmsp-p1-radio-group {
        display:flex;
        gap:24px;
        flex-wrap:wrap;
        align-items:center;
    }
    .mmsp-p1-radio-option {
        display:flex;
        align-items:center;
        gap:8px;
        font-size:14px;
        color:#374151;
    }
    .mmsp-p1-radio-option input {
        width:18px;
        height:18px;
        flex-shrink:0;
        cursor:pointer;
    }
    .mmsp-p1-section-divider {
        border-top:1px solid #e5e7eb;
        padding-top:20px;
        margin-top:8px;
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

    /* Only the canonical MMSP P1a/P1b print view; on-screen layout stays unchanged. */
    @media print {
        @page { size: A4 portrait; margin: 12mm 10mm; }
        /* Override the MMSP shared visual layer's 24px top padding in print only.
           24 CSS px = 18 PDF pt: the exact measured header offset versus Women. */
        .canonical-ui-page.mmsp-form-page {
            padding-top: 0 !important;
            padding-bottom: 0 !important;
        }
        body { background: #fff !important; }
        body > header, body > nav, body > footer,
        .mmsp-p1 .mmsp-p1-alert,
        .mmsp-p1 .mmsp-p1-type-card,
        .mmsp-p1 .mmsp-p1-nav,
        .mmsp-p1 .mmsp-p1-hint,
        .mmsp-p1 .mmsp-p1-help,
        .mmsp-p1 .mmsp-p1-error,
        .mmsp-p1 .mmsp-p1-screen-actions,
        .mmsp-p1 button,
        .mmsp-p1-submit { display: none !important; }
        .mmsp-p1,
        .mmsp-p1-wrap {
            background: white !important;
            min-height: 0 !important;
            padding: 0 !important;
            margin: 0 !important;
            width: 100% !important;
            max-width: none !important;
        }
        /* Keep the entire print form inside the printable A4 width. */
        .mmsp-p1 form,
        .mmsp-p1 form fieldset,
        .mmsp-p1 form .mmsp-p1-card {
            width: 100% !important;
            max-width: 100% !important;
            min-width: 0 !important;
            box-sizing: border-box !important;
        }
        /* The 7mm inset shifted the entire grid's right column left of the women's form.
           Grid minmax(0,1fr) plus border-box controls now prevent overflow. */
        .mmsp-p1 form fieldset {
            padding-right: 0 !important;
            padding-left: 0 !important;
            margin-right: 0 !important;
        }
        .mmsp-p1 form .mmsp-p1-card {
            width: 100% !important;
            max-width: 100% !important;
        }
        .mmsp-p1-header {
            padding: 12px 16px !important;
            margin: 0 0 12px !important;
            border: 1px solid #ddd !important;
            box-shadow: none !important;
            break-inside: avoid;
        }
        .mmsp-p1-header-top { margin-bottom: 16px !important; }
        .mmsp-p1-crest img { height: 2cm !important; }
        .mmsp-p1-meta { margin-top: 12px !important; padding-top: 12px !important; }
        .mmsp-p1-org, .mmsp-p1-contact { font-size: 11.7px !important; }
        .mmsp-p1-number, .mmsp-p1-code { font-size: 12.6px !important; }
        .mmsp-p1-title { font-size: 19.8px !important; margin-top: 8px !important; }
        .mmsp-p1-subtitle { font-size: 12.6px !important; margin-top: 12px !important; }
        .mmsp-p1-call { margin-top: 3px !important; }
        .mmsp-p1-card {
            padding: 0 !important;
            border: 0 !important;
            border-radius: 0 !important;
            box-shadow: none !important;
            margin: 0 !important;
            break-inside: auto;
        }
        /* Flatten existing edit-only rows into one consistent, document-style grid. */
        .mmsp-p1 .mmsp-p1-card:not(.mmsp-p1-screen-actions) {
            display: grid !important;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) !important;
            column-gap: 16px !important;
            align-items: start !important;
        }
        .mmsp-p1 .mmsp-p1-card:not(.mmsp-p1-screen-actions) > .mmsp-p1-row {
            display: contents !important;
        }
        .mmsp-p1 .mmsp-p1-card:not(.mmsp-p1-screen-actions) > .mmsp-p1-full,
        .mmsp-p1 .mmsp-p1-card:not(.mmsp-p1-screen-actions) .mmsp-p1-row > .mmsp-p1-field {
            grid-column: auto !important;
        }
        /* The subsidy selection is a section header and retains its full width. */
        .mmsp-p1 .mmsp-p1-card:not(.mmsp-p1-screen-actions) > .mmsp-p1-full:has(#subsidy_type),
        .mmsp-p1 .mmsp-p1-card:not(.mmsp-p1-screen-actions) > .mmsp-p1-section-divider {
            grid-column: 1 / -1 !important;
        }
        .mmsp-p1-field, .mmsp-p1-full {
            margin-bottom: 10px !important;
            min-width: 0 !important;
            break-inside: avoid;
        }
        .mmsp-p1-label {
            margin-bottom: 4px !important;
            font-size: 9.9pt !important;
            font-weight: 600 !important;
            color: #374151 !important;
        }
        .mmsp-p1 .mmsp-p1-full,
        .mmsp-p1 .mmsp-p1-field,
        .mmsp-p1 .mmsp-p1-row {
            width: 100% !important;
            max-width: 100% !important;
            min-width: 0 !important;
            box-sizing: border-box !important;
        }
        .mmsp-p1-control {
            display: block !important;
            width: 100% !important;
            max-width: 100% !important;
            min-width: 0 !important;
            box-sizing: border-box !important;
            border: 1px solid #d1d5db !important;
            outline: 1px solid #d1d5db !important;
            outline-offset: -1px !important;
            padding: 3px 5px !important;
            font-size: 9pt !important;
            font-weight: 400 !important;
            border-radius: 0 !important;
            background: #fff !important;
            height: auto !important;
            min-height: 29px !important;
            box-shadow: none !important;
        }
        .mmsp-p1-section-divider {
            margin-top: 5px !important;
            padding-top: 8px !important;
            break-inside: avoid;
        }
        .mmsp-p1-check { gap: 8px !important; }
        .mmsp-p1-check label { font-size: 11px !important; }
        .mmsp-p1 .mmsp-p1-section-divider .mmsp-p1-help {
            display: block !important;
            font-size: 10px !important;
            margin-top: 5px !important;
        }
    }
</style>

<div class="mmsp-p1 canonical-ui-page mmsp-form-page">
    <div class="mmsp-p1-wrap container mx-auto px-4">
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
                na javni konkurs za raspodjelu bespovratnih sredstava<br>
                namijenjenih za podršku mikro, malim i srednjim preduzećima<br>
                Tip prijave: <strong>{{ $isEntrepreneur ? 'PREDUZETNIK' : 'PRIVREDNO DRUŠTVO (DOO)' }}</strong>
            </p>
            @if($competition?->title)
                <p class="mmsp-p1-call">{{ $competition->title }}</p>
            @endif
        </div>

        @if(!$readOnly)
            <div class="mmsp-p1-alert mmsp-p1-alert-info">
                <strong>Nastavak popunjavanja:</strong> Već imate započetu prijavu. Možete je nastaviti popunjavati.
            </div>
        @elseif(session('success'))
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

        @unless($readOnly)
            <div class="mmsp-p1-type-card">
                <label class="mmsp-p1-label" style="margin-bottom:8px;">
                    Tip podnosioca prijave <span class="mmsp-p1-req">*</span>
                </label>
                <p class="mmsp-p1-type-note">
                    <strong>Napomena:</strong> Tip prijave birate na stranici konkursa i zaključan je za ovu prijavu.
                </p>
                <div class="mmsp-p1-radio-group">
                    <label class="mmsp-p1-radio-option">
                        <input type="radio" disabled @checked($isEntrepreneur)>
                        <span>Preduzetnik — Obrazac P1a</span>
                    </label>
                    <label class="mmsp-p1-radio-option">
                        <input type="radio" disabled @checked(!$isEntrepreneur)>
                        <span>Privredno društvo (DOO) — Obrazac P1b</span>
                    </label>
                </div>
            </div>

            <div class="mmsp-p1-alert mmsp-p1-alert-info">
                <strong>Važno:</strong> Odabrani tip prijave određuje Obrazac {{ $code }} i pripadajuću dokumentaciju prema MMSP Odluci.
            </div>
        @endunless

        <form method="POST" action="{{ route('applications.canonical-p1.update', $application) }}">
            @csrf
            @method('PUT')

            <fieldset @disabled($readOnly) style="border: 0; padding: 0; margin: 0;">
                <div class="mmsp-p1-card">
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
                            <label class="mmsp-p1-label" for="website">Website (opciono)</label>
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
                            <input id="person_name" type="text" name="person_name" value="{{ $prefillValue('person_name') }}" class="mmsp-p1-control @error('person_name') is-invalid @enderror">
                            @error('person_name')<div class="mmsp-p1-error">{{ $message }}</div>@enderror
                        </div>
                        <div class="mmsp-p1-full">
                            <label class="mmsp-p1-label" for="address">Adresa @if(in_array('address', $required, true))<span class="mmsp-p1-req">*</span>@endif</label>
                            <input id="address" type="text" name="address" value="{{ $prefillValue('address') }}" placeholder="npr. Njegoševa 1, 85330 Kotor" class="mmsp-p1-control @error('address') is-invalid @enderror">
                            @error('address')<div class="mmsp-p1-error">{{ $message }}</div>@enderror
                        </div>
                        <div class="mmsp-p1-full">
                            <label class="mmsp-p1-label" for="website">Website (opciono)</label>
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
                                <input id="phone" type="text" name="phone" value="{{ $prefillValue('phone') }}" class="mmsp-p1-control @error('phone') is-invalid @enderror">
                                @error('phone')<div class="mmsp-p1-error">{{ $message }}</div>@enderror
                            </div>
                            <div class="mmsp-p1-field">
                                <label class="mmsp-p1-label" for="email">E-mail @if(in_array('email', $required, true))<span class="mmsp-p1-req">*</span>@endif</label>
                                <input id="email" type="email" name="email" value="{{ $prefillValue('email') }}" class="mmsp-p1-control @error('email') is-invalid @enderror">
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

                    <div class="mmsp-p1-section-divider">
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
                </div>

                @unless($readOnly)
                    <div class="mmsp-p1-card mmsp-p1-screen-actions" style="text-align:center; padding:32px;">
                        <div style="display:flex; justify-content:center; flex-wrap:wrap;">
                            <button type="submit" class="mmsp-p1-submit" style="background:#6b7280; padding:12px 24px; font-size:14px; margin:0;">
                                Sačuvaj kao nacrt
                            </button>
                        </div>
                    </div>
                @endunless
            </fieldset>
        </form>

        <div class="mmsp-p1-card mmsp-p1-screen-actions" style="text-align:center; margin-top:8px; margin-bottom:24px;">
            <button type="button"
                    class="mmsp-p1-submit"
                    onclick="window.print();"
                    style="padding:10px 24px; font-size:14px; min-width:180px; margin:0;">
                Štampaj
            </button>
        </div>

    </div>
</div>
@endsection
