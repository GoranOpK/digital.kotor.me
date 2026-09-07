{{-- Forma za registraciju korisnika --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Registracija - {{ config('app.name', 'Digital Kotor') }}</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    <style>
        :root { --primary:#0B3D91; --primary-dark:#0A347B; --secondary:#B8860B; }
        html, body { height:100%; margin:0; padding:0; }
        body { font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", "Apple Color Emoji", "Segoe UI Emoji"; background:#f9fafb; }
        .container { width: 40%; max-width: 560px; margin: 40px auto; padding: 16px; box-sizing: border-box; }
        .register-card { background:#fff; border:1px solid #e5e7eb; border-radius:16px; padding:32px; box-shadow:0 1px 3px rgba(0,0,0,.1); }
        .register-title { font-size:28px; color:#111827; margin:0 0 8px; font-weight:700; }
        .register-subtitle { color:#6b7280; margin:0 0 24px; font-size:14px; }
        .form-group { margin-bottom:20px; }
        .form-label { display:block; font-weight:600; color:#374151; margin-bottom:8px; font-size:14px; }
        .form-label .required { color:#dc2626; }
        .form-control { width:100%; padding:10px 14px; border:1px solid #d1d5db; border-radius:8px; font-size:14px; transition:border-color .2s; box-sizing: border-box; }
        .form-control:focus { outline:none; border-color:var(--primary); box-shadow:0 0 0 3px rgba(11,61,145,.1); }
        .form-control.error { border-color:#dc2626; }
        .form-control.uppercase { text-transform:uppercase; }
        .form-error { color:#dc2626; font-size:12px; margin-top:4px; display:none; }
        .form-error.show { display:block; }
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
        .btn { display:inline-block; padding:12px 24px; border-radius:8px; font-weight:600; text-decoration:none; border:1px solid transparent; cursor:pointer; font-size:14px; transition:background-color .2s; }
        .btn-primary { background:var(--primary); color:#fff; border:none; }
        .btn-primary:hover { background:var(--primary-dark); }
        .btn-primary:disabled { opacity:.6; cursor:not-allowed; }
        .btn-link { color:var(--primary); text-decoration:none; font-size:14px; }
        .btn-link:hover { text-decoration:underline; }
        .form-footer { margin-top:24px; padding-top:24px; border-top:1px solid #e5e7eb; text-align:center; }
        .conditional-field { display:none; }
        .conditional-field.show { display:block; }
        .identity-block { border:1px solid #e5e7eb; border-radius:12px; padding:16px; margin-bottom:20px; }
        .identity-block h2 { font-size:16px; margin:0 0 16px; color:#111827; }
        @media (max-width: 900px) {
            .container { width: 100%; max-width: 560px; }
        }
        @media (max-width: 640px) {
            .phone-wrapper { flex-direction:column; }
            .phone-flag-trigger { width:100%; }
            .phone-flag-dropdown { width:100%; }
        }
    </style>
    <meta name="theme-color" content="#0B3D91">
</head>
<body>
    <div class="container">
        <div class="register-card">
            <h1 class="register-title">Kreiraj nalog</h1>
            <p class="register-subtitle">Popunite formu za kreiranje novog naloga</p>

            <form method="POST" action="{{ route('register') }}" id="registerForm">
                @csrf

                <div class="form-group">
                    <label for="user_type" class="form-label">Vrsta subjekta <span class="required">*</span></label>
                    <select name="user_type" id="user_type" class="form-control" required>
                        <option value="">Izaberite vrstu subjekta</option>
                        <option value="Fizičko lice" @selected(old('user_type') === 'Fizičko lice')>Fizičko lice</option>
                        <option value="Pravno lice" @selected(old('user_type') === 'Pravno lice')>Pravno lice</option>
                        <option value="Dio stranog privrednog društva" @selected(old('user_type') === 'Dio stranog privrednog društva')>Dio stranog privrednog društva</option>
                    </select>
                    <div class="form-error" id="user_type_error"></div>
                </div>

                <div class="form-group conditional-field" id="business_type_group">
                    <label for="registers_as_entrepreneur" class="form-label" id="entrepreneur_choice_label">Da li se registrujete kao preduzetnik? <span class="required">*</span></label>
                    <select name="registers_as_entrepreneur" id="registers_as_entrepreneur" class="form-control">
                        <option value="">Izaberite</option>
                        <option value="0" @selected((string) old('registers_as_entrepreneur') === '0')>Ne</option>
                        <option value="1" @selected((string) old('registers_as_entrepreneur') === '1')>Da</option>
                    </select>
                    <div class="form-error" id="registers_as_entrepreneur_error"></div>
                    <label for="business_type" class="form-label" id="business_type_label">Pravni oblik <span class="required">*</span></label>
                    <select name="business_type" id="business_type" class="form-control">
                        <option value="">Izaberite pravni oblik</option>
                        @foreach(($businessTypeOptions ?? []) as $value => $label)
                            <option value="{{ $value }}" @selected(old('business_type') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <div class="form-error" id="business_type_error"></div>
                </div>

                <div class="form-group conditional-field" id="residential_status_group">
                    <label for="residential_status" class="form-label">Status rezidentnosti <span class="required">*</span></label>
                    <select name="residential_status" id="residential_status" class="form-control">
                        <option value="">Izaberite status</option>
                        <option value="resident" @selected(old('residential_status') === 'resident')>Rezident</option>
                        <option value="non-resident" @selected(old('residential_status') === 'non-resident')>Nerezident</option>
                    </select>
                    <div class="form-error" id="residential_status_error"></div>
                </div>

                <div class="form-group conditional-field" id="person_name_group">
                    <label for="first_name" class="form-label">Ime <span class="required">*</span></label>
                    <input type="text" name="first_name" id="first_name" class="form-control" autocomplete="given-name" value="{{ old('first_name') }}">
                    <div class="form-error" id="first_name_error"></div>
                </div>

                <div class="form-group conditional-field" id="person_last_name_group">
                    <label for="last_name" class="form-label">Prezime <span class="required">*</span></label>
                    <input type="text" name="last_name" id="last_name" class="form-control" autocomplete="family-name" value="{{ old('last_name') }}">
                    <div class="form-error" id="last_name_error"></div>
                </div>

                <div class="form-group conditional-field" id="id_document_type_group">
                    <label for="id_document_type" class="form-label">Vrsta identifikacionog dokumenta <span class="required">*</span></label>
                    <select name="id_document_type" id="id_document_type" class="form-control">
                        <option value="">Izaberite vrstu</option>
                        <option value="jmb" @selected(old('id_document_type') === 'jmb')>JMB</option>
                        <option value="passport" @selected(old('id_document_type') === 'passport')>Broj pasoša</option>
                    </select>
                    <div class="form-error" id="id_document_type_error"></div>
                </div>

                <div class="form-group conditional-field" id="jmb_group">
                    <label for="jmb" class="form-label">JMB <span class="required">*</span></label>
                    <input type="text" name="jmb" id="jmb" class="form-control" maxlength="13" inputmode="numeric" placeholder="13 cifara" value="{{ old('jmb') }}">
                    <div class="form-note">Tačno 13 cifara. Validira se kontrolna cifra.</div>
                    <div class="form-error" id="jmb_error"></div>
                </div>

                <div class="form-group conditional-field" id="passport_group">
                    <label for="passport_number" class="form-label">Broj pasoša <span class="required">*</span></label>
                    <input type="text" name="passport_number" id="passport_number" class="form-control uppercase" value="{{ old('passport_number') }}">
                    <div class="form-error" id="passport_number_error"></div>
                </div>

                <div class="form-group conditional-field" id="residence_country_group">
                    <label for="residence_country_code" class="form-label">Država prebivališta <span class="required">*</span></label>
                    <select name="residence_country_code" id="residence_country_code" class="form-control">
                        <option value="">Izaberite državu prebivališta</option>
                        @foreach(($countryOptions ?? []) as $code => $label)
                            <option value="{{ $code }}" @selected(old('residence_country_code') === $code)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <div class="form-error" id="residence_country_code_error"></div>
                </div>

                <div class="form-group conditional-field" id="entrepreneur_name_group">
                    <label for="entrepreneur_business_name" class="form-label">Naziv preduzetnika <span class="required">*</span></label>
                    <input type="text" name="entrepreneur_business_name" id="entrepreneur_business_name" class="form-control" value="{{ old('entrepreneur_business_name') }}">
                    <div class="form-error" id="entrepreneur_business_name_error"></div>
                </div>

                <div class="form-group conditional-field" id="legal_name_group">
                    <label for="legal_name" class="form-label">Puni naziv pravnog lica <span class="required">*</span></label>
                    <input type="text" name="legal_name" id="legal_name" class="form-control" value="{{ old('legal_name') }}" autocomplete="organization">
                    <div class="form-error" id="legal_name_error"></div>
                </div>

                <div class="form-group conditional-field" id="foreign_company_name_group">
                    <label for="foreign_company_name" class="form-label">Naziv stranog privrednog društva <span class="required">*</span></label>
                    <input type="text" name="foreign_company_name" id="foreign_company_name" class="form-control" value="{{ old('foreign_company_name') }}">
                    <div class="form-error" id="foreign_company_name_error"></div>
                </div>

                <div class="form-group conditional-field" id="branch_name_group">
                    <label for="branch_name_in_montenegro" class="form-label">Naziv dijela u Crnoj Gori <span class="required">*</span></label>
                    <input type="text" name="branch_name_in_montenegro" id="branch_name_in_montenegro" class="form-control" value="{{ old('branch_name_in_montenegro') }}">
                    <div class="form-error" id="branch_name_in_montenegro_error"></div>
                </div>

                <div class="form-group conditional-field" id="pib_group">
                    <label for="pib" class="form-label">PIB <span class="required">*</span></label>
                    <input type="text" name="pib" id="pib" class="form-control" maxlength="8" inputmode="numeric" placeholder="8 cifara" value="{{ old('pib') }}">
                    <div class="form-error" id="pib_error"></div>
                </div>

                <div class="form-group conditional-field" id="crps_group">
                    <label for="crps_number" class="form-label">CRPS registracioni broj <span class="required">*</span></label>
                    <input type="text" name="crps_number" id="crps_number" class="form-control" maxlength="8" inputmode="numeric" placeholder="8 cifara" value="{{ old('crps_number') }}">
                    <div class="form-error" id="crps_number_error"></div>
                </div>

                <div class="identity-block conditional-field" id="authorized_person_group">
                    <h2>Ovlašćeno lice</h2>
                    <div class="form-group">
                        <label for="authorized_first_name" class="form-label">Ime <span class="required">*</span></label>
                        <input type="text" name="authorized_first_name" id="authorized_first_name" class="form-control" value="{{ old('authorized_first_name') }}">
                        <div class="form-error" id="authorized_first_name_error"></div>
                    </div>
                    <div class="form-group">
                        <label for="authorized_last_name" class="form-label">Prezime <span class="required">*</span></label>
                        <input type="text" name="authorized_last_name" id="authorized_last_name" class="form-control" value="{{ old('authorized_last_name') }}">
                        <div class="form-error" id="authorized_last_name_error"></div>
                    </div>
                    <div class="form-group">
                        <label for="authorized_id_document_type" class="form-label">Vrsta identifikacionog dokumenta <span class="required">*</span></label>
                        <select name="authorized_id_document_type" id="authorized_id_document_type" class="form-control">
                            <option value="">Izaberite vrstu</option>
                            <option value="jmb" @selected(old('authorized_id_document_type') === 'jmb')>JMB</option>
                            <option value="passport" @selected(old('authorized_id_document_type') === 'passport')>Broj pasoša</option>
                        </select>
                        <div class="form-error" id="authorized_id_document_type_error"></div>
                    </div>
                    <div class="form-group conditional-field" id="authorized_jmb_group">
                        <label for="authorized_jmb" class="form-label">JMB <span class="required">*</span></label>
                        <input type="text" name="authorized_jmb" id="authorized_jmb" class="form-control" maxlength="13" inputmode="numeric" value="{{ old('authorized_jmb') }}">
                        <div class="form-error" id="authorized_jmb_error"></div>
                    </div>
                    <div class="form-group conditional-field" id="authorized_passport_group">
                        <label for="authorized_passport_number" class="form-label">Broj pasoša <span class="required">*</span></label>
                        <input type="text" name="authorized_passport_number" id="authorized_passport_number" class="form-control uppercase" value="{{ old('authorized_passport_number') }}">
                        <div class="form-error" id="authorized_passport_number_error"></div>
                    </div>
                    <div class="form-group conditional-field" id="authorized_passport_country_group">
                        <label for="authorized_passport_issuing_country_code" class="form-label">Država izdavanja pasoša <span class="required">*</span></label>
                        <select name="authorized_passport_issuing_country_code" id="authorized_passport_issuing_country_code" class="form-control">
                            <option value="">Izaberite državu izdavanja pasoša</option>
                            @foreach(($countryOptions ?? []) as $code => $label)
                                <option value="{{ $code }}" @selected(old('authorized_passport_issuing_country_code') === $code)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <div class="form-error" id="authorized_passport_issuing_country_code_error"></div>
                    </div>
                </div>

                <div class="identity-block conditional-field" id="representative_group">
                    <h2>Zastupnik</h2>
                    <div class="form-group">
                        <label for="representative_first_name" class="form-label">Ime <span class="required">*</span></label>
                        <input type="text" name="representative_first_name" id="representative_first_name" class="form-control" value="{{ old('representative_first_name') }}">
                        <div class="form-error" id="representative_first_name_error"></div>
                    </div>
                    <div class="form-group">
                        <label for="representative_last_name" class="form-label">Prezime <span class="required">*</span></label>
                        <input type="text" name="representative_last_name" id="representative_last_name" class="form-control" value="{{ old('representative_last_name') }}">
                        <div class="form-error" id="representative_last_name_error"></div>
                    </div>
                    <div class="form-group">
                        <label for="representative_id_document_type" class="form-label">Vrsta identifikacionog dokumenta <span class="required">*</span></label>
                        <select name="representative_id_document_type" id="representative_id_document_type" class="form-control">
                            <option value="">Izaberite vrstu</option>
                            <option value="jmb" @selected(old('representative_id_document_type') === 'jmb')>JMB</option>
                            <option value="passport" @selected(old('representative_id_document_type') === 'passport')>Broj pasoša</option>
                        </select>
                        <div class="form-error" id="representative_id_document_type_error"></div>
                    </div>
                    <div class="form-group conditional-field" id="representative_jmb_group">
                        <label for="representative_jmb" class="form-label">JMB <span class="required">*</span></label>
                        <input type="text" name="representative_jmb" id="representative_jmb" class="form-control" maxlength="13" inputmode="numeric" value="{{ old('representative_jmb') }}">
                        <div class="form-error" id="representative_jmb_error"></div>
                    </div>
                    <div class="form-group conditional-field" id="representative_passport_group">
                        <label for="representative_passport_number" class="form-label">Broj pasoša <span class="required">*</span></label>
                        <input type="text" name="representative_passport_number" id="representative_passport_number" class="form-control uppercase" value="{{ old('representative_passport_number') }}">
                        <div class="form-error" id="representative_passport_number_error"></div>
                    </div>
                    <div class="form-group conditional-field" id="representative_passport_country_group">
                        <label for="representative_passport_issuing_country_code" class="form-label">Država izdavanja pasoša <span class="required">*</span></label>
                        <select name="representative_passport_issuing_country_code" id="representative_passport_issuing_country_code" class="form-control">
                            <option value="">Izaberite državu izdavanja pasoša</option>
                            @foreach(($countryOptions ?? []) as $code => $label)
                                <option value="{{ $code }}" @selected(old('representative_passport_issuing_country_code') === $code)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <div class="form-error" id="representative_passport_issuing_country_code_error"></div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="email" class="form-label">E-mail adresa <span class="required">*</span></label>
                    <input type="email" name="email" id="email" class="form-control" required autocomplete="email" value="{{ old('email') }}">
                    <div class="form-error" id="email_error"></div>
                </div>

                <div class="form-group">
                    <label for="email_confirmation" class="form-label">Potvrda e-mail adrese <span class="required">*</span></label>
                    <input type="email" name="email_confirmation" id="email_confirmation" class="form-control" required autocomplete="email" value="{{ old('email_confirmation') }}">
                    <div class="form-error" id="email_confirmation_error"></div>
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Korisnička lozinka <span class="required">*</span></label>
                    <input type="password" name="password" id="password" class="form-control" required autocomplete="new-password" minlength="8">
                    <div class="form-note">Lozinka mora imati najmanje 8 karaktera</div>
                    <div class="form-error" id="password_error"></div>
                </div>

                <div class="form-group">
                    <label for="password_confirmation" class="form-label">Potvrda korisničke lozinke <span class="required">*</span></label>
                    <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" required autocomplete="new-password">
                    <div class="form-error" id="password_confirmation_error"></div>
                </div>

                <div class="form-group">
                    <label for="phone_national" class="form-label">Broj mobilnog telefona <span class="required">*</span></label>
                    <div class="phone-wrapper">
                        @php
                        $phoneCountries = collect($phonePickerEntries ?? [])
                            ->map(fn ($entry) => [
                                'code' => $entry['calling_code'],
                                'iso' => strtolower($entry['country_code']),
                                'name' => $entry['label'],
                            ])
                            ->all();
                        $oldCallingCode = old('phone_calling_code');
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
                            <button type="button" class="phone-flag-trigger" id="phone_calling_code_btn" aria-haspopup="listbox" aria-expanded="false" aria-label="Izaberite pozivni broj države">
                                <img class="phone-flag-img" id="phone_calling_code_flag" src="https://flagcdn.com/w40/{{ $selectedCountry['iso'] }}.png" width="20" height="15" alt="">
                                <span class="phone-flag-code" id="phone_calling_code_label">{{ $selectedCountry['code'] }}</span>
                                <span class="phone-flag-caret" aria-hidden="true">▾</span>
                            </button>
                            <input type="hidden" name="phone_calling_code" id="phone_calling_code" value="{{ $selectedCountry['code'] }}" required>
                            <ul class="phone-flag-dropdown" id="phone_calling_code_list" role="listbox" hidden>
                                @foreach($phoneCountries as $country)
                                    <li role="option"
                                        class="phone-flag-option{{ $country['iso'] === $selectedCountry['iso'] ? ' is-selected' : '' }}"
                                        tabindex="-1"
                                        value="{{ $country['code'] }}"
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
                        <input type="tel" name="phone_national" id="phone_national" class="form-control phone-input" required autocomplete="tel" inputmode="numeric" placeholder="Unesite broj mobilnog telefona" value="{{ old('phone_national') }}">
                    </div>
                    <div class="form-note" id="phone_format_note">Format: Unesite broj bez nacionalnog prefiksa i bez vodeće nule (npr. za +382, umjesto 069123456 unesite 69123456)</div>
                    <div class="form-error" id="phone_calling_code_error"></div>
                    <div class="form-error" id="phone_national_error"></div>
                </div>

                <div class="form-group">
                    <label for="address" class="form-label">Ulica i broj <span class="required">*</span></label>
                    <input type="text" name="address" id="address" class="form-control" required autocomplete="address-line1" placeholder="Npr. Njegoševa 12 ili Njegoševa bb" value="{{ old('address') }}">
                    <div class="form-note">Unesite ulicu i broj ili oznaku bb (bez broja). Grad unosite u posebno polje ispod.</div>
                    <div class="form-error" id="address_error"></div>
                </div>

                <div class="form-group">
                    <label for="city" class="form-label">Grad <span class="required">*</span></label>
                    <input type="text" name="city" id="city" class="form-control" required autocomplete="address-level2" value="{{ old('city') }}">
                    <div class="form-error" id="city_error"></div>
                </div>

                @if($errors->any())
                    <div class="form-group">
                        <div style="background:#fee; border:1px solid #fcc; border-radius:8px; padding:12px; color:#c33;">
                            <strong>Greške:</strong>
                            <ul style="margin:8px 0 0 20px; padding:0;">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

                <button type="submit" class="btn btn-primary" id="submitBtn">Registruj se</button>

                <div class="form-footer">
                    <a href="{{ route('login') }}" class="btn-link">Već imate nalog? Prijavite se</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        (function() {
            const form = document.getElementById('registerForm');
            const userType = document.getElementById('user_type');
            const businessType = document.getElementById('business_type');
            const residentialStatus = document.getElementById('residential_status');
            const idDocumentType = document.getElementById('id_document_type');
            const authorizedIdType = document.getElementById('authorized_id_document_type');
            const representativeIdType = document.getElementById('representative_id_document_type');
            const crpsRequiredForms = @json($crpsRequiredForms ?? []);
            const registersAsEntrepreneur = document.getElementById('registers_as_entrepreneur');

            function toggleField(fieldId, show) {
                const field = document.getElementById(fieldId);
                if (!field) return;
                field.classList.toggle('show', !!show);
            }

            function toggleDisplay(id, show) {
                const el = document.getElementById(id);
                if (!el) return;
                el.style.display = show ? '' : 'none';
            }

            function isPhysical() { return userType.value === 'Fizičko lice'; }
            function isLegalEntityGroup() { return userType.value === 'Pravno lice'; }
            function isDspd() { return userType.value === 'Dio stranog privrednog društva'; }
            function isEntrepreneur() { return isPhysical() && registersAsEntrepreneur && registersAsEntrepreneur.value === '1'; }
            function isLegal() { return isLegalEntityGroup() && businessType.value; }
            function isNatural() { return isPhysical(); }

            function updateVisibility() {
                toggleField('business_type_group', isPhysical() || isLegalEntityGroup());
                toggleDisplay('entrepreneur_choice_label', isPhysical());
                toggleDisplay('registers_as_entrepreneur', isPhysical());
                toggleDisplay('registers_as_entrepreneur_error', isPhysical());
                toggleDisplay('business_type_label', isLegalEntityGroup());
                toggleDisplay('business_type', isLegalEntityGroup());
                toggleDisplay('business_type_error', isLegalEntityGroup());
                toggleField('residential_status_group', isNatural());
                toggleField('person_name_group', isNatural());
                toggleField('person_last_name_group', isNatural());
                toggleField('id_document_type_group', isNatural() && residentialStatus.value === 'non-resident');
                toggleField('residence_country_group', isNatural() && residentialStatus.value === 'non-resident');
                toggleField('entrepreneur_name_group', isEntrepreneur());
                toggleField('legal_name_group', isLegal());
                toggleField('foreign_company_name_group', isDspd());
                toggleField('branch_name_group', isDspd());
                toggleField('pib_group', isEntrepreneur() || isLegal() || isDspd());
                toggleField('crps_group', isDspd() || (isEntrepreneur() || (isLegal() && crpsRequiredForms.indexOf(businessType.value) !== -1)));
                toggleField('authorized_person_group', isLegal());
                toggleField('representative_group', isDspd());

                const showJmb = isNatural() && (residentialStatus.value === 'resident' || (residentialStatus.value === 'non-resident' && idDocumentType.value === 'jmb'));
                const showPassport = isNatural() && residentialStatus.value === 'non-resident' && idDocumentType.value === 'passport';
                toggleField('jmb_group', showJmb);
                toggleField('passport_group', showPassport);

                toggleField('authorized_jmb_group', isLegal() && authorizedIdType.value === 'jmb');
                toggleField('authorized_passport_group', isLegal() && authorizedIdType.value === 'passport');
                toggleField('authorized_passport_country_group', isLegal() && authorizedIdType.value === 'passport');
                toggleField('representative_jmb_group', isDspd() && representativeIdType.value === 'jmb');
                toggleField('representative_passport_group', isDspd() && representativeIdType.value === 'passport');
                toggleField('representative_passport_country_group', isDspd() && representativeIdType.value === 'passport');
            }

            function jmbChecksumOk(value) {
                if (!/^[0-9]{13}$/.test(value)) return false;
                const weights = [7, 6, 5, 4, 3, 2, 7, 6, 5, 4, 3, 2];
                let sum = 0;
                for (let i = 0; i < 12; i++) sum += parseInt(value[i], 10) * weights[i];
                const m = sum % 11;
                if (m === 1) return false;
                const k = m === 0 ? 0 : 11 - m;
                return k === parseInt(value[12], 10);
            }

            ['user_type', 'registers_as_entrepreneur', 'business_type', 'residential_status', 'id_document_type', 'authorized_id_document_type', 'representative_id_document_type'].forEach(function (id) {
                const el = document.getElementById(id);
                if (el) el.addEventListener('change', updateVisibility);
            });

            ['jmb', 'authorized_jmb', 'representative_jmb', 'pib', 'crps_number'].forEach(function (id) {
                const el = document.getElementById(id);
                if (!el) return;
                el.addEventListener('input', function () {
                    this.value = this.value.replace(/\D/g, '');
                });
            });

            ['passport_number', 'authorized_passport_number', 'representative_passport_number'].forEach(function (id) {
                const el = document.getElementById(id);
                if (!el) return;
                el.addEventListener('input', function () {
                    this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
                });
            });

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

            updateVisibility();

            form.addEventListener('submit', function (e) {
                let valid = true;
                document.querySelectorAll('.form-error').forEach(function (el) { el.classList.remove('show'); });

                function fail(id, message) {
                    const errorEl = document.getElementById(id);
                    if (errorEl) {
                        errorEl.textContent = message;
                        errorEl.classList.add('show');
                    }
                    valid = false;
                }

                if (isNatural() && residentialStatus.value === 'resident') {
                    const jmb = document.getElementById('jmb').value;
                    if (!jmbChecksumOk(jmb)) fail('jmb_error', 'JMB nije ispravan.');
                }
                if (isNatural() && residentialStatus.value === 'non-resident' && idDocumentType.value === 'jmb') {
                    const jmb = document.getElementById('jmb').value;
                    if (!jmbChecksumOk(jmb)) fail('jmb_error', 'JMB nije ispravan.');
                }
                if (isLegal() && authorizedIdType.value === 'jmb') {
                    const jmb = document.getElementById('authorized_jmb').value;
                    if (!jmbChecksumOk(jmb)) fail('authorized_jmb_error', 'JMB nije ispravan.');
                }
                if (isDspd() && representativeIdType.value === 'jmb') {
                    const jmb = document.getElementById('representative_jmb').value;
                    if (!jmbChecksumOk(jmb)) fail('representative_jmb_error', 'JMB nije ispravan.');
                }

                const email = document.getElementById('email').value;
                const emailConfirmation = document.getElementById('email_confirmation').value;
                if (email !== emailConfirmation) fail('email_confirmation_error', 'E-mail adrese se ne podudaraju.');

                if (!valid) e.preventDefault();
            });
        })();
    </script>
</body>
</html>
