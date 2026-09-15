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
    .identity-completion .page-wrap { width:40%; max-width:560px; margin:0 auto; padding:0 16px; box-sizing:border-box; }
    .identity-completion .page-header { background:linear-gradient(90deg, var(--primary), var(--primary-dark)); color:#fff; padding:24px; border-radius:16px; margin-bottom:24px; }
    .identity-completion .page-header h1 { color:#fff; font-size:28px; font-weight:700; margin:0 0 8px; }
    .identity-completion .page-header p { color:rgba(255,255,255,.9); margin:0; font-size:14px; line-height:1.5; }
    .identity-card { background:#fff; border-radius:12px; padding:24px; margin-bottom:24px; box-shadow:0 1px 3px rgba(0,0,0,.1); }
    .identity-card h2 { font-size:18px; font-weight:700; color:#111827; margin:0 0 16px; }
    .form-group { margin-bottom:16px; }
    .form-label { display:block; font-weight:600; color:#374151; margin-bottom:8px; font-size:14px; }
    .form-label .required { color:#dc2626; }
    .form-control { width:100%; padding:10px 14px; border:1px solid #d1d5db; border-radius:8px; font-size:14px; box-sizing:border-box; }
    .form-control:focus { outline:none; border-color:var(--primary); box-shadow:0 0 0 3px rgba(11,61,145,.1); }
    .form-control.error { border-color:#dc2626; }
    .readonly-box { background:#f3f4f6; border-radius:8px; padding:12px 14px; color:#111827; font-weight:600; }
    .readonly-caption { color:#6b7280; font-size:12px; margin-top:4px; }
    .form-error { color:#dc2626; font-size:12px; margin-top:4px; }
    .form-note { color:#6b7280; font-size:12px; margin-top:4px; }
    .phone-wrapper { display:flex; gap:8px; align-items:stretch; }
    .phone-flag { position:relative; flex-shrink:0; }
    .phone-flag-trigger {
        display:flex; align-items:center; gap:8px;
        min-width:108px; height:100%; padding:10px 12px;
        border:1px solid #d1d5db; border-radius:8px; background:#fff;
        cursor:pointer; font-size:14px; color:#111827; box-sizing:border-box;
    }
    .phone-flag-trigger:focus { outline:none; border-color:var(--primary); box-shadow:0 0 0 3px rgba(11,61,145,.1); }
    .phone-flag-trigger[aria-expanded="true"] { border-color:var(--primary); }
    .phone-flag-trigger.error { border-color:#dc2626; }
    .phone-flag-img { width:20px; height:15px; object-fit:cover; border-radius:2px; flex-shrink:0; box-shadow:0 0 0 1px rgba(0,0,0,.08); }
    .phone-flag-code { font-variant-numeric:tabular-nums; white-space:nowrap; }
    .phone-flag-caret { margin-left:auto; color:#6b7280; font-size:10px; line-height:1; }
    .phone-flag-dropdown {
        display:none; position:absolute; z-index:40; top:calc(100% + 4px); left:0;
        width:min(320px, 80vw); max-height:280px; overflow:auto;
        margin:0; padding:6px 0; list-style:none;
        background:#fff; border:1px solid #e5e7eb; border-radius:10px;
        box-shadow:0 10px 25px rgba(0,0,0,.12);
    }
    .phone-flag-dropdown.open { display:block; }
    .phone-flag-option {
        display:flex; align-items:center; gap:10px;
        width:100%; padding:8px 12px; border:0; background:transparent;
        cursor:pointer; text-align:left; font-size:14px; color:#111827;
    }
    .phone-flag-option:hover,
    .phone-flag-option:focus { background:#f3f4f6; outline:none; }
    .phone-flag-option.is-selected { background:#eff6ff; }
    .phone-flag-option-name { flex:1; min-width:0; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .phone-flag-option-code { color:#6b7280; font-variant-numeric:tabular-nums; flex-shrink:0; }
    .phone-input { flex:1; min-width:0; }
    .btn-row { display:flex; gap:12px; flex-wrap:wrap; }
    .btn { display:inline-block; padding:12px 24px; border-radius:8px; font-weight:600; text-decoration:none; border:1px solid transparent; cursor:pointer; font-size:14px; }
    .btn-primary { background:var(--primary); color:#fff; border:none; }
    .btn-secondary { background:#fff; color:#374151; border:1px solid #d1d5db; }
    .conditional-field { display:none; }
    .conditional-field.show { display:block; }
    @media (max-width: 900px) {
        .identity-completion .page-wrap { width:100%; max-width:560px; }
    }
    @media (max-width: 640px) {
        .phone-wrapper { flex-direction:column; }
        .phone-flag-trigger { width:100%; }
        .phone-flag-dropdown { width:100%; }
    }
</style>
<div class="identity-completion">
    <div class="page-wrap">
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
                        @php
                            $phoneCountries = collect($callingCodes ?? [])
                                ->map(fn ($entry) => [
                                    'code' => $entry['calling_code'],
                                    'iso' => strtolower($entry['country_code']),
                                    'name' => $entry['label'],
                                ])
                                ->all();
                            $oldCallingCode = $old('phone_calling_code');
                            $selectedCountry = $phoneCountries[0] ?? ['code' => '', 'iso' => 'me', 'name' => ''];
                            if ($oldCallingCode) {
                                foreach ($phoneCountries as $country) {
                                    if ($country['code'] === $oldCallingCode) {
                                        $selectedCountry = $country;
                                        break;
                                    }
                                }
                            }
                        @endphp
                        <div class="phone-flag" id="phone_calling_code_picker">
                            <button type="button" class="phone-flag-trigger @error('phone_calling_code') error @enderror" id="phone_calling_code_btn" aria-haspopup="listbox" aria-expanded="false" aria-label="Izaberite pozivni broj države">
                                <img class="phone-flag-img" id="phone_calling_code_flag" src="https://flagcdn.com/w40/{{ $selectedCountry['iso'] }}.png" width="20" height="15" alt="">
                                <span class="phone-flag-code" id="phone_calling_code_label">{{ $selectedCountry['code'] }}</span>
                                <span class="phone-flag-caret" aria-hidden="true">▾</span>
                            </button>
                            <input type="hidden" name="phone_calling_code" id="phone_calling_code" value="{{ $selectedCountry['code'] }}" required>
                            <ul class="phone-flag-dropdown" id="phone_calling_code_list" role="listbox" hidden>
                                @foreach ($phoneCountries as $country)
                                    <li role="option"
                                        class="phone-flag-option{{ $country['iso'] === $selectedCountry['iso'] ? ' is-selected' : '' }}"
                                        tabindex="-1"
                                        data-code="{{ $country['code'] }}"
                                        data-iso="{{ $country['iso'] }}"
                                        data-name="{{ $country['name'] }}"
                                        aria-selected="{{ $country['iso'] === $selectedCountry['iso'] ? 'true' : 'false' }}">
                                        <img class="phone-flag-img" src="https://flagcdn.com/w40/{{ $country['iso'] }}.png" width="20" height="15" alt="" loading="lazy">
                                        <span class="phone-flag-option-name">{{ $country['name'] }}</span>
                                        <span class="phone-flag-option-code">{{ $country['code'] }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                        <input id="phone_national" name="phone_national" type="tel" inputmode="numeric" autocomplete="tel" class="form-control phone-input @error('phone_national') error @enderror" value="{{ $old('phone_national') }}" placeholder="Unesite broj mobilnog telefona" required>
                    </div>
                    <div class="form-note">Format: Unesite broj bez nacionalnog prefiksa i bez vodeće nule (npr. za +382, umjesto 069123456 unesite 69123456)</div>
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

    const phoneCallingCode = document.getElementById('phone_calling_code');
    const phoneNational = document.getElementById('phone_national');
    const phoneCallingCodeBtn = document.getElementById('phone_calling_code_btn');
    const phoneCallingCodeList = document.getElementById('phone_calling_code_list');
    const phoneCallingCodeFlag = document.getElementById('phone_calling_code_flag');
    const phoneCallingCodeLabel = document.getElementById('phone_calling_code_label');
    const phoneCallingCodeOptions = phoneCallingCodeList
        ? Array.from(phoneCallingCodeList.querySelectorAll('.phone-flag-option'))
        : [];

    function setPhoneCallingCodeOpen(isOpen) {
        if (!phoneCallingCodeBtn || !phoneCallingCodeList) return;
        phoneCallingCodeBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        phoneCallingCodeList.classList.toggle('open', isOpen);
        phoneCallingCodeList.hidden = !isOpen;
    }

    function selectPhoneCallingCode(option) {
        if (!option || !phoneCallingCode) return;
        const code = option.dataset.code;
        const iso = option.dataset.iso;
        const name = option.dataset.name;

        phoneCallingCode.value = code;
        if (phoneCallingCodeFlag) {
            phoneCallingCodeFlag.src = 'https://flagcdn.com/w40/' + iso + '.png';
            phoneCallingCodeFlag.alt = name || '';
        }
        if (phoneCallingCodeLabel) {
            phoneCallingCodeLabel.textContent = code;
        }

        phoneCallingCodeOptions.forEach(function (item) {
            const selected = item === option;
            item.classList.toggle('is-selected', selected);
            item.setAttribute('aria-selected', selected ? 'true' : 'false');
        });

        setPhoneCallingCodeOpen(false);
    }

    if (phoneCallingCodeBtn && phoneCallingCodeList) {
        phoneCallingCodeBtn.addEventListener('click', function (e) {
            e.preventDefault();
            const isOpen = phoneCallingCodeBtn.getAttribute('aria-expanded') === 'true';
            setPhoneCallingCodeOpen(!isOpen);
        });

        phoneCallingCodeOptions.forEach(function (option) {
            option.addEventListener('click', function () {
                selectPhoneCallingCode(option);
            });
        });

        document.addEventListener('click', function (e) {
            const picker = document.getElementById('phone_calling_code_picker');
            if (picker && !picker.contains(e.target)) {
                setPhoneCallingCodeOpen(false);
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                setPhoneCallingCodeOpen(false);
            }
        });
    }

    if (phoneNational) {
        phoneNational.addEventListener('input', function () {
            this.value = this.value.replace(/\D/g, '');
            if (this.value.startsWith('0')) {
                this.value = this.value.substring(1);
            }
        });
    }
})();
</script>
@endsection
