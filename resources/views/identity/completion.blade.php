@extends('layouts.app')

@section('content')
@php
    $isDoo = $branch === 'doo';
    $isPhysical = $branch === 'physical_person';
    $hideJmb = $isPhysical && ! empty($prefill['jmb_hidden']);
    $old = fn (string $key) => old($key, $prefill[$key] ?? '');
@endphp
<style>
    :root { --primary:#0B3D91; --primary-dark:#0A347B; --secondary:#B8860B; }
    .identity-completion { background:#f9fafb; min-height:100vh; padding:24px 0; }
    .identity-completion .page-header { background:linear-gradient(90deg, var(--primary), var(--primary-dark)); color:#fff; padding:24px; border-radius:16px; margin-bottom:24px; }
    .identity-completion .page-header h1 { color:#fff; font-size:28px; font-weight:700; margin:0 0 8px; }
    .identity-completion .page-header p { color:rgba(255,255,255,.9); margin:0; font-size:14px; line-height:1.5; }
    .identity-card { background:#fff; border-radius:12px; padding:24px; margin-bottom:24px; box-shadow:0 1px 3px rgba(0,0,0,.1); }
    .identity-card h2 { font-size:18px; font-weight:700; color:#111827; margin:0 0 16px; }
    .form-group { margin-bottom:16px; }
    .form-label { display:block; font-weight:600; color:#374151; margin-bottom:8px; font-size:14px; }
    .form-label .required { color:#dc2626; }
    .form-control { width:100%; padding:10px 14px; border:1px solid #d1d5db; border-radius:8px; font-size:14px; }
    .form-control:focus { outline:none; border-color:var(--primary); box-shadow:0 0 0 3px rgba(11,61,145,.1); }
    .form-control.error { border-color:#dc2626; }
    .readonly-box { background:#f3f4f6; border-radius:8px; padding:12px 14px; color:#111827; font-weight:600; }
    .readonly-caption { color:#6b7280; font-size:12px; margin-top:4px; }
    .form-error { color:#dc2626; font-size:12px; margin-top:4px; }
    .form-note { color:#6b7280; font-size:12px; margin-top:4px; }
    .phone-wrapper { display:flex; gap:8px; }
    .phone-flag-select { min-width:220px; }
    .phone-input { flex:1; }
    .btn-row { display:flex; gap:12px; flex-wrap:wrap; }
    .btn { display:inline-block; padding:12px 24px; border-radius:8px; font-weight:600; text-decoration:none; border:1px solid transparent; cursor:pointer; font-size:14px; }
    .btn-primary { background:var(--primary); color:#fff; border:none; }
    .btn-secondary { background:#fff; color:#374151; border:1px solid #d1d5db; }
    .conditional-field { display:none; }
    .conditional-field.show { display:block; }
    @media (max-width: 640px) {
        .phone-wrapper { flex-direction:column; }
        .phone-flag-select { width:100%; min-width:0; }
    }
</style>
<div class="identity-completion">
    <div class="max-w-4xl mx-auto px-4">
        <div class="page-header">
            @if ($isPhysical)
                <h1>Dopuna podataka</h1>
                <p>Prije nastavka potrebno je da provjerite i dopunite podatke svog profila.</p>
            @else
                <h1>Dopunite podatke o subjektu</h1>
                <p>Nalog postoji, ali je potrebno dopuniti podatke o subjektu prije nastavka sa zahtijevanom uslugom. Ovo nije nova registracija.</p>
            @endif
        </div>

        @if ($errors->any())
            <div class="identity-card" style="border:1px solid #fecaca; background:#fef2f2;">
                <ul class="form-error" style="margin:0; padding-left:18px;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('identity.completion.store') }}" id="identityCompletionForm">
            @csrf

            <div class="identity-card">
                @if ($isDoo)
                    <div class="form-group">
                        <div class="form-label">Vrsta subjekta</div>
                        <div class="readonly-box">Pravno lice</div>
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <div class="form-label">Pravni oblik</div>
                        <div class="readonly-box">DOO — Društvo sa ograničenom odgovornošću</div>
                    </div>
                @elseif ($isPhysical)
                    <div class="form-group" style="margin-bottom:0;">
                        <div class="form-label">Vrsta subjekta</div>
                        <div class="readonly-box">Fizičko lice</div>
                    </div>
                @else
                    <div class="form-group">
                        <div class="form-label">Vrsta subjekta</div>
                        <div class="readonly-box">Fizičko lice</div>
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <div class="form-label">Registrovani preduzetnik</div>
                        <div class="readonly-box">Da</div>
                    </div>
                @endif
            </div>

            @if ($isDoo)
                <div class="identity-card">
                    <h2>Privredni subjekt</h2>
                    <div class="form-group">
                        <label for="legal_name" class="form-label">Puni naziv pravnog lica <span class="required">*</span></label>
                        <input id="legal_name" name="legal_name" type="text" class="form-control @error('legal_name') error @enderror" value="{{ $old('legal_name') }}" required>
                        @error('legal_name')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label for="pib" class="form-label">PIB <span class="required">*</span></label>
                        <input id="pib" name="pib" type="text" inputmode="numeric" maxlength="8" class="form-control @error('pib') error @enderror" value="{{ $old('pib') }}" required>
                        @error('pib')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label for="crps_number" class="form-label">CRPS registracioni broj <span class="required">*</span></label>
                        <input id="crps_number" name="crps_number" type="text" inputmode="numeric" maxlength="8" class="form-control @error('crps_number') error @enderror" value="{{ $old('crps_number') }}" required>
                        @error('crps_number')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                </div>
            @else
                <div class="identity-card">
                    <h2>Lični identitet</h2>
                    <div class="form-group">
                        <label for="first_name" class="form-label">Ime <span class="required">*</span></label>
                        <input id="first_name" name="first_name" type="text" class="form-control @error('first_name') error @enderror" value="{{ $old('first_name') }}" required>
                        @error('first_name')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label for="last_name" class="form-label">Prezime <span class="required">*</span></label>
                        <input id="last_name" name="last_name" type="text" class="form-control @error('last_name') error @enderror" value="{{ $old('last_name') }}" required>
                        @error('last_name')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label for="residential_status" class="form-label">Status rezidentnosti <span class="required">*</span></label>
                        <select id="residential_status" name="residential_status" class="form-control @error('residential_status') error @enderror" required>
                            <option value="">Izaberite status rezidentnosti</option>
                            <option value="resident" @selected($old('residential_status') === 'resident')>Rezident</option>
                            <option value="non-resident" @selected($old('residential_status') === 'non-resident')>Nerezident</option>
                        </select>
                        @error('residential_status')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                    @unless ($hideJmb)
                    <div class="form-group conditional-field" id="id_document_type_group">
                        <label for="id_document_type" class="form-label">Vrsta identifikacionog dokumenta <span class="required">*</span></label>
                        <select id="id_document_type" name="id_document_type" class="form-control @error('id_document_type') error @enderror">
                            <option value="">Izaberite vrstu dokumenta</option>
                            <option value="jmb" @selected($old('id_document_type') === 'jmb')>JMB</option>
                            <option value="passport" @selected($old('id_document_type') === 'passport')>Pasoš</option>
                        </select>
                        @error('id_document_type')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                    @unless ($hideJmb)
                    <div class="form-group conditional-field" id="jmb_group">
                        <label for="jmb" class="form-label">JMB <span class="required">*</span></label>
                        <input id="jmb" name="jmb" type="text" inputmode="numeric" maxlength="13" class="form-control @error('jmb') error @enderror" value="{{ $old('jmb') }}">
                        @error('jmb')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                    @endunless
                    <div class="form-group conditional-field" id="passport_group">
                        <label for="passport_number" class="form-label">Broj pasoša <span class="required">*</span></label>
                        <input id="passport_number" name="passport_number" type="text" class="form-control @error('passport_number') error @enderror" value="{{ $old('passport_number') }}">
                        @error('passport_number')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                    @endunless
                    <div class="form-group conditional-field" id="residence_country_group" style="margin-bottom:0;">
                        <label for="residence_country_code" class="form-label">Država prebivališta <span class="required">*</span></label>
                        <select id="residence_country_code" name="residence_country_code" class="form-control @error('residence_country_code') error @enderror">
                            <option value="">Izaberite državu</option>
                            @foreach ($countryEntries as $entry)
                                <option value="{{ $entry['code'] }}" @selected($old('residence_country_code') === $entry['code'])>{{ $entry['label'] }}</option>
                            @endforeach
                        </select>
                        @error('residence_country_code')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                </div>

                @unless ($isPhysical)
                <div class="identity-card">
                    <h2>Poslovanje</h2>
                    <div class="form-group">
                        <label for="entrepreneur_business_name" class="form-label">Naziv preduzetnika <span class="required">*</span></label>
                        <input id="entrepreneur_business_name" name="entrepreneur_business_name" type="text" class="form-control @error('entrepreneur_business_name') error @enderror" value="{{ $old('entrepreneur_business_name') }}" required>
                        @error('entrepreneur_business_name')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label for="pib" class="form-label">PIB <span class="required">*</span></label>
                        <input id="pib" name="pib" type="text" inputmode="numeric" maxlength="8" class="form-control @error('pib') error @enderror" value="{{ $old('pib') }}" required>
                        @error('pib')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label for="crps_number" class="form-label">CRPS registracioni broj <span class="required">*</span></label>
                        <input id="crps_number" name="crps_number" type="text" inputmode="numeric" maxlength="8" class="form-control @error('crps_number') error @enderror" value="{{ $old('crps_number') }}" required>
                        @error('crps_number')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                </div>
                @endunless
            @endif

            <div class="identity-card">
                <h2>Kontakt i adresa</h2>
                <div class="form-group">
                    <label for="phone_national" class="form-label">Broj mobilnog telefona <span class="required">*</span></label>
                    <div class="phone-wrapper">
                        <select id="phone_calling_code" name="phone_calling_code" class="form-control phone-flag-select @error('phone_calling_code') error @enderror" required>
                            <option value="">Pozivni broj</option>
                            @foreach ($callingCodes as $entry)
                                <option value="{{ $entry['calling_code'] }}" @selected($old('phone_calling_code') === $entry['calling_code'])>{{ $entry['label'] }} ({{ $entry['calling_code'] }})</option>
                            @endforeach
                        </select>
                        <input id="phone_national" name="phone_national" type="text" inputmode="numeric" class="form-control phone-input @error('phone_national') error @enderror" value="{{ $old('phone_national') }}" required>
                    </div>
                    <div class="form-note">Pozivni broj nije država. Država identiteta je ISO alpha-2 / XK.</div>
                    @error('phone_calling_code')<div class="form-error">{{ $message }}</div>@enderror
                    @error('phone_national')<div class="form-error">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label for="street_and_number" class="form-label">Ulica i broj <span class="required">*</span></label>
                    <input id="street_and_number" name="street_and_number" type="text" class="form-control @error('street_and_number') error @enderror" value="{{ $old('street_and_number') }}" required>
                    @error('street_and_number')<div class="form-error">{{ $message }}</div>@enderror
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label for="city" class="form-label">Grad <span class="required">*</span></label>
                    <input id="city" name="city" type="text" class="form-control @error('city') error @enderror" value="{{ $old('city') }}" required>
                    @error('city')<div class="form-error">{{ $message }}</div>@enderror
                </div>
            </div>

            @if ($isDoo)
                <div class="identity-card">
                    <h2>Ovlašćeno lice</h2>
                    <p class="form-note" style="margin-top:0; margin-bottom:16px;">Ime i prezime nosioca naloga se automatski ne tretiraju kao ovlašćeno lice. Unesite podatke ovlašćenog lica.</p>
                    <div class="form-group">
                        <label for="authorized_first_name" class="form-label">Ime <span class="required">*</span></label>
                        <input id="authorized_first_name" name="authorized_first_name" type="text" class="form-control @error('authorized_first_name') error @enderror" value="{{ $old('authorized_first_name') }}" required>
                        @error('authorized_first_name')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label for="authorized_last_name" class="form-label">Prezime <span class="required">*</span></label>
                        <input id="authorized_last_name" name="authorized_last_name" type="text" class="form-control @error('authorized_last_name') error @enderror" value="{{ $old('authorized_last_name') }}" required>
                        @error('authorized_last_name')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label for="authorized_id_document_type" class="form-label">Vrsta identifikacionog dokumenta <span class="required">*</span></label>
                        <select id="authorized_id_document_type" name="authorized_id_document_type" class="form-control @error('authorized_id_document_type') error @enderror" required>
                            <option value="">Izaberite vrstu dokumenta</option>
                            <option value="jmb" @selected($old('authorized_id_document_type') === 'jmb')>JMB</option>
                            <option value="passport" @selected($old('authorized_id_document_type') === 'passport')>Pasoš</option>
                        </select>
                        @error('authorized_id_document_type')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group conditional-field" id="authorized_jmb_group">
                        <label for="authorized_jmb" class="form-label">JMB <span class="required">*</span></label>
                        <input id="authorized_jmb" name="authorized_jmb" type="text" inputmode="numeric" maxlength="13" class="form-control @error('authorized_jmb') error @enderror" value="{{ $old('authorized_jmb') }}">
                        @error('authorized_jmb')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group conditional-field" id="authorized_passport_group">
                        <label for="authorized_passport_number" class="form-label">Broj pasoša <span class="required">*</span></label>
                        <input id="authorized_passport_number" name="authorized_passport_number" type="text" class="form-control @error('authorized_passport_number') error @enderror" value="{{ $old('authorized_passport_number') }}">
                        @error('authorized_passport_number')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group conditional-field" id="authorized_issuing_country_group" style="margin-bottom:0;">
                        <label for="authorized_passport_issuing_country_code" class="form-label">Država izdavanja pasoša <span class="required">*</span></label>
                        <select id="authorized_passport_issuing_country_code" name="authorized_passport_issuing_country_code" class="form-control @error('authorized_passport_issuing_country_code') error @enderror">
                            <option value="">Izaberite državu</option>
                            @foreach ($countryEntries as $entry)
                                <option value="{{ $entry['code'] }}" @selected($old('authorized_passport_issuing_country_code') === $entry['code'])>{{ $entry['label'] }}</option>
                            @endforeach
                        </select>
                        @error('authorized_passport_issuing_country_code')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                </div>
            @endif

            <div class="identity-card">
                <div class="btn-row">
                    <button type="submit" class="btn btn-primary">Sačuvaj i nastavi</button>
                    <a href="{{ route('dashboard') }}" class="btn btn-secondary">Odustani</a>
                </div>
            </div>
        </form>
    </div>
</div>
<script>
(function () {
    const isDoo = @json($isDoo);
    const hideJmb = @json($hideJmb);

    function show(id, visible) {
        const el = document.getElementById(id);
        if (!el) return;
        el.classList.toggle('show', visible);
        el.querySelectorAll('input, select').forEach(function (field) {
            if (visible) {
                field.removeAttribute('disabled');
            } else if (field.name) {
                field.setAttribute('disabled', 'disabled');
            }
        });
    }

    function syncPreduzetnik() {
        const residential = document.getElementById('residential_status')?.value;
        const documentType = document.getElementById('id_document_type')?.value;
        const nonResident = residential === 'non-resident';
        const resident = residential === 'resident';
        show('id_document_type_group', nonResident && ! hideJmb);
        show('residence_country_group', nonResident);
        show('jmb_group', ! hideJmb && (resident || (nonResident && documentType === 'jmb')));
        show('passport_group', ! hideJmb && nonResident && documentType === 'passport');
    }

    function syncDoo() {
        const documentType = document.getElementById('authorized_id_document_type')?.value;
        show('authorized_jmb_group', documentType === 'jmb');
        show('authorized_passport_group', documentType === 'passport');
        show('authorized_issuing_country_group', documentType === 'passport');
    }

    if (isDoo) {
        document.getElementById('authorized_id_document_type')?.addEventListener('change', syncDoo);
        syncDoo();
    } else {
        document.getElementById('residential_status')?.addEventListener('change', syncPreduzetnik);
        document.getElementById('id_document_type')?.addEventListener('change', syncPreduzetnik);
        syncPreduzetnik();
    }
})();
</script>
@endsection
