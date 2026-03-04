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
        .container { max-width: 900px; margin: 40px auto; padding: 16px; }
        .register-card { background:#fff; border:1px solid #e5e7eb; border-radius:16px; padding:32px; box-shadow:0 1px 3px rgba(0,0,0,.1); }
        .register-title { font-size:28px; color:#111827; margin:0 0 8px; font-weight:700; }
        .register-subtitle { color:#6b7280; margin:0 0 24px; font-size:14px; }
        .form-group { margin-bottom:20px; }
        .form-label { display:block; font-weight:600; color:#374151; margin-bottom:8px; font-size:14px; }
        .form-label .required { color:#dc2626; }
        .form-control { width:100%; padding:10px 14px; border:1px solid #d1d5db; border-radius:8px; font-size:14px; transition:border-color .2s; }
        .form-control:focus { outline:none; border-color:var(--primary); box-shadow:0 0 0 3px rgba(11,61,145,.1); }
        .form-control.error { border-color:#dc2626; }
        .form-control.uppercase { text-transform:uppercase; }
        .form-error { color:#dc2626; font-size:12px; margin-top:4px; display:none; }
        .form-error.show { display:block; }
        .form-note { color:#6b7280; font-size:12px; margin-top:4px; }
        .phone-wrapper { display:flex; gap:8px; }
        .phone-flag { position:relative; }
        .phone-flag-select { width:80px; padding:10px 14px; border:1px solid #d1d5db; border-radius:8px; font-size:14px; cursor:pointer; background:#fff; }
        .phone-flag-select:focus { outline:none; border-color:var(--primary); box-shadow:0 0 0 3px rgba(11,61,145,.1); }
        .phone-input { flex:1; }
        .btn { display:inline-block; padding:12px 24px; border-radius:8px; font-weight:600; text-decoration:none; border:1px solid transparent; cursor:pointer; font-size:14px; transition:background-color .2s; }
        .btn-primary { background:var(--primary); color:#fff; border:none; }
        .btn-primary:hover { background:var(--primary-dark); }
        .btn-primary:disabled { opacity:.6; cursor:not-allowed; }
        .btn-link { color:var(--primary); text-decoration:none; font-size:14px; }
        .btn-link:hover { text-decoration:underline; }
        .form-footer { margin-top:24px; padding-top:24px; border-top:1px solid #e5e7eb; text-align:center; }
        .conditional-field { display:none; }
        .conditional-field.show { display:block; }
        @media (max-width: 640px) {
            .phone-wrapper { flex-direction:column; }
            .phone-flag-select { width:100%; }
        }
    </style>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <meta name="theme-color" content="#0B3D91">
</head>
<body>
    <div class="container">
        <div class="register-card">
            <h1 class="register-title">Kreiraj nalog</h1>
            <p class="register-subtitle">Popunite formu za kreiranje novog naloga</p>
            
            <form method="POST" action="{{ route('register') }}" id="registerForm">
        @csrf
                
                {{-- Vrsta korisnika --}}
                <div class="form-group">
                    <label for="user_type" class="form-label">Vrsta korisnika <span class="required">*</span></label>
                    <select name="user_type" id="user_type" class="form-control" required>
                        <option value="">Izaberite vrstu korisnika</option>
                        <option value="Fizičko lice">Fizičko lice</option>
                        <option value="Registrovan privredni subjekat">Registrovan privredni subjekat</option>
                    </select>
                    <div class="form-error" id="user_type_error"></div>
                </div>

                {{-- Registrovani privredni subjekt - tip --}}
                <div class="form-group conditional-field" id="business_type_group">
                    <label for="business_type" class="form-label">Tip privrednog subjekta <span class="required">*</span></label>
                    <select name="business_type" id="business_type" class="form-control">
                        <option value="">Izaberite tip</option>
                        <option value="Preduzetnik">Preduzetnik</option>
                        <option value="Ortačko društvo">Ortačko društvo</option>
                        <option value="Komanditno društvo">Komanditno društvo</option>
                        <option value="Društvo sa ograničenom odgovornošću">Društvo sa ograničenom odgovornošću</option>
                        <option value="Akcionarsko društvo">Akcionarsko društvo</option>
                        <option value="Dio stranog društva (predstavništvo ili poslovna jedinica)">Dio stranog društva (predstavništvo ili poslovna jedinica)</option>
                        <option value="Udruženje (nvo, fondacije, sportske organizacije)">Udruženje (nvo, fondacije, sportske organizacije)</option>
                        <option value="Ustanova (državne i privatne)">Ustanova (državne i privatne)</option>
                        <option value="Druge organizacije (Političke partije, Verske zajednice, Komore, Sindikati)">Druge organizacije (Političke partije, Verske zajednice, Komore, Sindikati)</option>
                    </select>
                    <div class="form-error" id="business_type_error"></div>
                </div>

                {{-- Residential status (samo za Fizičko lice i Preduzetnik) --}}
                <div class="form-group conditional-field" id="residential_status_group">
                    <label for="residential_status" class="form-label">Status prebivališta <span class="required">*</span></label>
                    <select name="residential_status" id="residential_status" class="form-control">
                        <option value="">Izaberite status</option>
                        <option value="resident">Rezident</option>
                        <option value="non-resident">Nerezident</option>
                    </select>
                    <div class="form-error" id="residential_status_error"></div>
                </div>

                {{-- Ime --}}
                <div class="form-group">
                    <label for="first_name" class="form-label">Ime / Ime ovlašćenog lica <span class="required">*</span></label>
                    <input type="text" name="first_name" id="first_name" class="form-control" required autocomplete="given-name">
                    <div class="form-error" id="first_name_error"></div>
                </div>

                {{-- Prezime --}}
                <div class="form-group">
                    <label for="last_name" class="form-label">Prezime / Prezime ovlašćenog lica <span class="required">*</span></label>
                    <input type="text" name="last_name" id="last_name" class="form-control" required autocomplete="family-name">
                    <div class="form-error" id="last_name_error"></div>
                </div>

                {{-- JMB (za rezidenti) --}}
                <div class="form-group conditional-field" id="jmb_group">
                    <label for="jmb" class="form-label">Jedinstveni matični broj (JMB) <span class="required">*</span></label>
                    <input type="text" name="jmb" id="jmb" class="form-control" maxlength="13" pattern="[0-9]{13}" placeholder="13 cifara">
                    <div class="form-note">Format: DDMMGGGRRBBBK (13 cifara bez razmaka)</div>
                    <div class="form-error" id="jmb_error"></div>
                </div>

                {{-- Nerezident - izbor identifikacije --}}
                <div class="form-group conditional-field" id="non_resident_id_type_group">
                    <label for="non_resident_id_type" class="form-label">Vrsta identifikacije <span class="required">*</span></label>
                    <select name="non_resident_id_type" id="non_resident_id_type" class="form-control">
                        <option value="">Izaberite vrstu</option>
                        <option value="jmb">JMB</option>
                        <option value="passport">Broj pasoša</option>
                    </select>
                    <div class="form-error" id="non_resident_id_type_error"></div>
                </div>

                {{-- JMB za nerezident --}}
                <div class="form-group conditional-field" id="jmb_non_resident_group">
                    <label for="jmb_non_resident" class="form-label">Jedinstveni matični broj (JMB) <span class="required">*</span></label>
                    <input type="text" name="jmb_non_resident" id="jmb_non_resident" class="form-control" maxlength="13" pattern="[0-9]{13}" placeholder="13 cifara">
                    <div class="form-note">Format: DDMMGGGRRBBBK (13 cifara bez razmaka)</div>
                    <div class="form-error" id="jmb_non_resident_error"></div>
                </div>

                {{-- Broj pasoša --}}
                <div class="form-group conditional-field" id="passport_group">
                    <label for="passport_number" class="form-label">Broj pasoša <span class="required">*</span></label>
                    <input type="text" name="passport_number" id="passport_number" class="form-control uppercase" placeholder="Brojevi i velika slova">
                    <div class="form-note">Kombinacija brojeva i velikih slova</div>
                    <div class="form-error" id="passport_number_error"></div>
                </div>

                {{-- PIB (za privredne subjekte osim Preduzetnika) --}}
                <div class="form-group conditional-field" id="pib_group">
                    <label for="pib" class="form-label">Poreski identifikacioni broj (PIB) <span class="required">*</span></label>
                    <input type="text" name="pib" id="pib" class="form-control" maxlength="8" pattern="[0-9]{8}" placeholder="8 cifara">
                    <div class="form-note">Format: 8 cifara</div>
                    <div class="form-error" id="pib_error"></div>
                </div>

                {{-- Email --}}
                <div class="form-group">
                    <label for="email" class="form-label">E-mail <span class="required">*</span></label>
                    <input type="email" name="email" id="email" class="form-control" required autocomplete="email">
                    <div class="form-error" id="email_error"></div>
                </div>

                {{-- Potvrda emaila --}}
                <div class="form-group">
                    <label for="email_confirmation" class="form-label">Potvrdi e-mail <span class="required">*</span></label>
                    <input type="email" name="email_confirmation" id="email_confirmation" class="form-control" required autocomplete="email">
                    <div class="form-error" id="email_confirmation_error"></div>
                </div>

                {{-- Lozinka --}}
                <div class="form-group">
                    <label for="password" class="form-label">Korisnička lozinka <span class="required">*</span></label>
                    <input type="password" name="password" id="password" class="form-control" required autocomplete="new-password" minlength="8">
                    <div class="form-note">Lozinka mora imati najmanje 8 karaktera</div>
                    <div class="form-error" id="password_error"></div>
                </div>

                {{-- Potvrda lozinke --}}
                <div class="form-group">
                    <label for="password_confirmation" class="form-label">Potvrdi lozinku <span class="required">*</span></label>
                    <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" required autocomplete="new-password">
                    <div class="form-error" id="password_confirmation_error"></div>
                </div>

                {{-- Telefon --}}
                <div class="form-group">
                    <label for="phone" class="form-label">Broj mobilnog telefona <span class="required">*</span></label>
                    <div class="phone-wrapper">
                        <div class="phone-flag">
                            <select id="phone_country" class="phone-flag-select" required>
                                @php
                                $countries = [
                                    ['code' => '+382', 'flag' => '🇲🇪', 'name' => 'Crna Gora'],
                                    ['code' => '+381', 'flag' => '🇷🇸', 'name' => 'Srbija'],
                                    ['code' => '+387', 'flag' => '🇧🇦', 'name' => 'Bosna i Hercegovina'],
                                    ['code' => '+386', 'flag' => '🇸🇮', 'name' => 'Slovenija'],
                                    ['code' => '+385', 'flag' => '🇭🇷', 'name' => 'Hrvatska'],
                                    ['code' => '+383', 'flag' => '🇽🇰', 'name' => 'Kosovo'],
                                    ['code' => '+389', 'flag' => '🇲🇰', 'name' => 'Severna Makedonija'],
                                    ['code' => '+355', 'flag' => '🇦🇱', 'name' => 'Albanija'],
                                    ['code' => '+359', 'flag' => '🇧🇬', 'name' => 'Bugarska'],
                                    ['code' => '+40', 'flag' => '🇷🇴', 'name' => 'Rumunija'],
                                    ['code' => '+36', 'flag' => '🇭🇺', 'name' => 'Mađarska'],
                                    ['code' => '+421', 'flag' => '🇸🇰', 'name' => 'Slovačka'],
                                    ['code' => '+420', 'flag' => '🇨🇿', 'name' => 'Češka'],
                                    ['code' => '+48', 'flag' => '🇵🇱', 'name' => 'Poljska'],
                                    ['code' => '+49', 'flag' => '🇩🇪', 'name' => 'Nemačka'],
                                    ['code' => '+43', 'flag' => '🇦🇹', 'name' => 'Austrija'],
                                    ['code' => '+41', 'flag' => '🇨🇭', 'name' => 'Švajcarska'],
                                    ['code' => '+39', 'flag' => '🇮🇹', 'name' => 'Italija'],
                                    ['code' => '+33', 'flag' => '🇫🇷', 'name' => 'Francuska'],
                                    ['code' => '+32', 'flag' => '🇧🇪', 'name' => 'Belgija'],
                                    ['code' => '+31', 'flag' => '🇳🇱', 'name' => 'Holandija'],
                                    ['code' => '+44', 'flag' => '🇬🇧', 'name' => 'Ujedinjeno Kraljevstvo'],
                                    ['code' => '+353', 'flag' => '🇮🇪', 'name' => 'Irska'],
                                    ['code' => '+45', 'flag' => '🇩🇰', 'name' => 'Danska'],
                                    ['code' => '+46', 'flag' => '🇸🇪', 'name' => 'Švedska'],
                                    ['code' => '+47', 'flag' => '🇳🇴', 'name' => 'Norveška'],
                                    ['code' => '+358', 'flag' => '🇫🇮', 'name' => 'Finska'],
                                    ['code' => '+354', 'flag' => '🇮🇸', 'name' => 'Island'],
                                    ['code' => '+351', 'flag' => '🇵🇹', 'name' => 'Portugalija'],
                                    ['code' => '+34', 'flag' => '🇪🇸', 'name' => 'Španija'],
                                    ['code' => '+30', 'flag' => '🇬🇷', 'name' => 'Grčka'],
                                    ['code' => '+357', 'flag' => '🇨🇾', 'name' => 'Kipar'],
                                    ['code' => '+356', 'flag' => '🇲🇹', 'name' => 'Malta'],
                                    ['code' => '+352', 'flag' => '🇱🇺', 'name' => 'Luksemburg'],
                                    ['code' => '+423', 'flag' => '🇱🇮', 'name' => 'Lihtenštajn'],
                                    ['code' => '+377', 'flag' => '🇲🇨', 'name' => 'Monako'],
                                    ['code' => '+376', 'flag' => '🇦🇩', 'name' => 'Andora'],
                                    ['code' => '+378', 'flag' => '🇸🇲', 'name' => 'San Marino'],
                                    ['code' => '+39', 'flag' => '🇻🇦', 'name' => 'Vatikan'],
                                    ['code' => '+7', 'flag' => '🇷🇺', 'name' => 'Rusija'],
                                    ['code' => '+7', 'flag' => '🇰🇿', 'name' => 'Kazahstan'],
                                    ['code' => '+380', 'flag' => '🇺🇦', 'name' => 'Ukrajina'],
                                    ['code' => '+375', 'flag' => '🇧🇾', 'name' => 'Belorusija'],
                                    ['code' => '+370', 'flag' => '🇱🇹', 'name' => 'Litvanija'],
                                    ['code' => '+371', 'flag' => '🇱🇻', 'name' => 'Latvija'],
                                    ['code' => '+372', 'flag' => '🇪🇪', 'name' => 'Estonija'],
                                    ['code' => '+373', 'flag' => '🇲🇩', 'name' => 'Moldavija'],
                                    ['code' => '+374', 'flag' => '🇦🇲', 'name' => 'Jermenija'],
                                    ['code' => '+995', 'flag' => '🇬🇪', 'name' => 'Gruzija'],
                                    ['code' => '+994', 'flag' => '🇦🇿', 'name' => 'Azerbejdžan'],
                                    ['code' => '+90', 'flag' => '🇹🇷', 'name' => 'Turska'],
                                    ['code' => '+1', 'flag' => '🇺🇸', 'name' => 'SAD'],
                                    ['code' => '+1', 'flag' => '🇨🇦', 'name' => 'Kanada'],
                                    ['code' => '+52', 'flag' => '🇲🇽', 'name' => 'Meksiko'],
                                    ['code' => '+54', 'flag' => '🇦🇷', 'name' => 'Argentina'],
                                    ['code' => '+55', 'flag' => '🇧🇷', 'name' => 'Brazil'],
                                    ['code' => '+56', 'flag' => '🇨🇱', 'name' => 'Čile'],
                                    ['code' => '+57', 'flag' => '🇨🇴', 'name' => 'Kolumbija'],
                                    ['code' => '+51', 'flag' => '🇵🇪', 'name' => 'Peru'],
                                    ['code' => '+58', 'flag' => '🇻🇪', 'name' => 'Venecuela'],
                                    ['code' => '+591', 'flag' => '🇧🇴', 'name' => 'Bolivija'],
                                    ['code' => '+593', 'flag' => '🇪🇨', 'name' => 'Ekvador'],
                                    ['code' => '+595', 'flag' => '🇵🇾', 'name' => 'Paragvaj'],
                                    ['code' => '+598', 'flag' => '🇺🇾', 'name' => 'Urugvaj'],
                                    ['code' => '+592', 'flag' => '🇬🇾', 'name' => 'Gvajana'],
                                    ['code' => '+597', 'flag' => '🇸🇷', 'name' => 'Surinam'],
                                    ['code' => '+594', 'flag' => '🇬🇫', 'name' => 'Francuska Gvajana'],
                                    ['code' => '+86', 'flag' => '🇨🇳', 'name' => 'Kina'],
                                    ['code' => '+81', 'flag' => '🇯🇵', 'name' => 'Japan'],
                                    ['code' => '+82', 'flag' => '🇰🇷', 'name' => 'Južna Koreja'],
                                    ['code' => '+84', 'flag' => '🇻🇳', 'name' => 'Vijetnam'],
                                    ['code' => '+66', 'flag' => '🇹🇭', 'name' => 'Tajland'],
                                    ['code' => '+65', 'flag' => '🇸🇬', 'name' => 'Singapur'],
                                    ['code' => '+60', 'flag' => '🇲🇾', 'name' => 'Malezija'],
                                    ['code' => '+62', 'flag' => '🇮🇩', 'name' => 'Indonezija'],
                                    ['code' => '+63', 'flag' => '🇵🇭', 'name' => 'Filipini'],
                                    ['code' => '+64', 'flag' => '🇳🇿', 'name' => 'Novi Zeland'],
                                    ['code' => '+61', 'flag' => '🇦🇺', 'name' => 'Australija'],
                                    ['code' => '+91', 'flag' => '🇮🇳', 'name' => 'Indija'],
                                    ['code' => '+92', 'flag' => '🇵🇰', 'name' => 'Pakistan'],
                                    ['code' => '+880', 'flag' => '🇧🇩', 'name' => 'Bangladeš'],
                                    ['code' => '+94', 'flag' => '🇱🇰', 'name' => 'Šri Lanka'],
                                    ['code' => '+95', 'flag' => '🇲🇲', 'name' => 'Mjanmar'],
                                    ['code' => '+855', 'flag' => '🇰🇭', 'name' => 'Kambodža'],
                                    ['code' => '+856', 'flag' => '🇱🇦', 'name' => 'Laos'],
                                    ['code' => '+673', 'flag' => '🇧🇳', 'name' => 'Brunej'],
                                    ['code' => '+20', 'flag' => '🇪🇬', 'name' => 'Egipat'],
                                    ['code' => '+212', 'flag' => '🇲🇦', 'name' => 'Maroko'],
                                    ['code' => '+213', 'flag' => '🇩🇿', 'name' => 'Alžir'],
                                    ['code' => '+216', 'flag' => '🇹🇳', 'name' => 'Tunis'],
                                    ['code' => '+218', 'flag' => '🇱🇾', 'name' => 'Libija'],
                                    ['code' => '+220', 'flag' => '🇬🇲', 'name' => 'Gambija'],
                                    ['code' => '+221', 'flag' => '🇸🇳', 'name' => 'Senegal'],
                                    ['code' => '+222', 'flag' => '🇲🇷', 'name' => 'Mauritanija'],
                                    ['code' => '+223', 'flag' => '🇲🇱', 'name' => 'Mali'],
                                    ['code' => '+224', 'flag' => '🇬🇳', 'name' => 'Gvineja'],
                                    ['code' => '+225', 'flag' => '🇨🇮', 'name' => 'Obala Slonovače'],
                                    ['code' => '+226', 'flag' => '🇧🇫', 'name' => 'Burkina Faso'],
                                    ['code' => '+227', 'flag' => '🇳🇪', 'name' => 'Niger'],
                                    ['code' => '+228', 'flag' => '🇹🇬', 'name' => 'Togo'],
                                    ['code' => '+229', 'flag' => '🇧🇯', 'name' => 'Benin'],
                                    ['code' => '+230', 'flag' => '🇲🇺', 'name' => 'Mauricijus'],
                                    ['code' => '+231', 'flag' => '🇱🇷', 'name' => 'Liberija'],
                                    ['code' => '+232', 'flag' => '🇸🇱', 'name' => 'Sijera Leone'],
                                    ['code' => '+233', 'flag' => '🇬🇭', 'name' => 'Gana'],
                                    ['code' => '+234', 'flag' => '🇳🇬', 'name' => 'Nigerija'],
                                    ['code' => '+235', 'flag' => '🇹🇩', 'name' => 'Čad'],
                                    ['code' => '+236', 'flag' => '🇨🇫', 'name' => 'Centralnoafrička Republika'],
                                    ['code' => '+237', 'flag' => '🇨🇲', 'name' => 'Kamerun'],
                                    ['code' => '+238', 'flag' => '🇨🇻', 'name' => 'Zelenortska Ostrva'],
                                    ['code' => '+239', 'flag' => '🇸🇹', 'name' => 'Sao Tome i Principe'],
                                    ['code' => '+240', 'flag' => '🇬🇶', 'name' => 'Ekvatorijalna Gvineja'],
                                    ['code' => '+241', 'flag' => '🇬🇦', 'name' => 'Gabon'],
                                    ['code' => '+242', 'flag' => '🇨🇬', 'name' => 'Kongo'],
                                    ['code' => '+243', 'flag' => '🇨🇩', 'name' => 'DR Kongo'],
                                    ['code' => '+244', 'flag' => '🇦🇴', 'name' => 'Angola'],
                                    ['code' => '+245', 'flag' => '🇬🇼', 'name' => 'Gvineja Bisau'],
                                    ['code' => '+246', 'flag' => '🇮🇴', 'name' => 'Britanska Teritorija Indijskog Okeana'],
                                    ['code' => '+248', 'flag' => '🇸🇨', 'name' => 'Sejšeli'],
                                    ['code' => '+249', 'flag' => '🇸🇩', 'name' => 'Sudan'],
                                    ['code' => '+250', 'flag' => '🇷🇼', 'name' => 'Ruanda'],
                                    ['code' => '+251', 'flag' => '🇪🇹', 'name' => 'Etiopija'],
                                    ['code' => '+252', 'flag' => '🇸🇴', 'name' => 'Somalija'],
                                    ['code' => '+253', 'flag' => '🇩🇯', 'name' => 'Džibuti'],
                                    ['code' => '+254', 'flag' => '🇰🇪', 'name' => 'Kenija'],
                                    ['code' => '+255', 'flag' => '🇹🇿', 'name' => 'Tanzanija'],
                                    ['code' => '+256', 'flag' => '🇺🇬', 'name' => 'Uganda'],
                                    ['code' => '+257', 'flag' => '🇧🇮', 'name' => 'Burundi'],
                                    ['code' => '+258', 'flag' => '🇲🇿', 'name' => 'Mozambik'],
                                    ['code' => '+260', 'flag' => '🇿🇲', 'name' => 'Zambija'],
                                    ['code' => '+261', 'flag' => '🇲🇬', 'name' => 'Madagaskar'],
                                    ['code' => '+262', 'flag' => '🇷🇪', 'name' => 'Reunion'],
                                    ['code' => '+263', 'flag' => '🇿🇼', 'name' => 'Zimbabve'],
                                    ['code' => '+264', 'flag' => '🇳🇦', 'name' => 'Namibija'],
                                    ['code' => '+265', 'flag' => '🇲🇼', 'name' => 'Malavi'],
                                    ['code' => '+266', 'flag' => '🇱🇸', 'name' => 'Lesoto'],
                                    ['code' => '+267', 'flag' => '🇧🇼', 'name' => 'Bocvana'],
                                    ['code' => '+268', 'flag' => '🇸🇿', 'name' => 'Esvatini'],
                                    ['code' => '+269', 'flag' => '🇰🇲', 'name' => 'Komori'],
                                    ['code' => '+27', 'flag' => '🇿🇦', 'name' => 'Južna Afrika'],
                                    ['code' => '+290', 'flag' => '🇸🇭', 'name' => 'Sveta Jelena'],
                                    ['code' => '+291', 'flag' => '🇪🇷', 'name' => 'Eritreja'],
                                    ['code' => '+297', 'flag' => '🇦🇼', 'name' => 'Aruba'],
                                    ['code' => '+298', 'flag' => '🇫🇴', 'name' => 'Farska Ostrva'],
                                    ['code' => '+299', 'flag' => '🇬🇱', 'name' => 'Grenland'],
                                    ['code' => '+350', 'flag' => '🇬🇮', 'name' => 'Gibraltar'],
                                ];
                                @endphp
                                @foreach($countries as $country)
                                    <option value="{{ $country['code'] }}" data-flag="{{ $country['flag'] }}">{{ $country['flag'] }} {{ $country['name'] }} ({{ $country['code'] }})</option>
                                @endforeach
                            </select>
                        </div>
                        <input type="tel" name="phone" id="phone" class="form-control phone-input" required autocomplete="tel" placeholder="Unesite broj telefona">
                    </div>
                    <input type="hidden" name="phone_full" id="phone_full">
                    <div class="form-note" id="phone_format_note">Format: Unesite broj bez nacionalnog prefiksa i bez vodeće nule (npr. za +382, umesto 069123456 unesite 69123456)</div>
                    <div class="form-error" id="phone_error"></div>
                </div>

                {{-- Adresa --}}
                <div class="form-group">
                    <label for="address" class="form-label">Adresa <span class="required">*</span></label>
                    <input type="text" name="address" id="address" class="form-control" required autocomplete="street-address" placeholder="Ulica i broj, mjesto">
                    <div class="form-error" id="address_error"></div>
                </div>

                {{-- Greške --}}
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
            const nonResidentIdType = document.getElementById('non_resident_id_type');
            const phoneCountry = document.getElementById('phone_country');
            const phone = document.getElementById('phone');
            const phoneFull = document.getElementById('phone_full');
            const jmb = document.getElementById('jmb');
            const jmbNonResident = document.getElementById('jmb_non_resident');
            const passportNumber = document.getElementById('passport_number');
            const pib = document.getElementById('pib');

            // Funkcija za prikaz/sakrivanje polja
            function toggleField(fieldId, show) {
                const field = document.getElementById(fieldId);
                if (field) {
                    if (show) {
                        field.classList.add('show');
                        const input = field.querySelector('input, select');
                        if (input) input.required = true;
                    } else {
                        field.classList.remove('show');
                        const input = field.querySelector('input, select');
                        if (input) {
                            input.required = false;
                            input.value = '';
                        }
                    }
                }
            }

            // Funkcija za validaciju JMB (13 cifara sa kontrolnom cifrom)
            function validateJMB(jmbValue) {
                if (!jmbValue || jmbValue.length !== 13) {
                    return { valid: false, message: 'JMB mora imati tačno 13 cifara' };
                }
                
                if (!/^[0-9]{13}$/.test(jmbValue)) {
                    return { valid: false, message: 'JMB mora sadržati samo cifre' };
                }

                // Izdvajanje delova
                const DD = parseInt(jmbValue.substring(0, 2));
                const MM = parseInt(jmbValue.substring(2, 4));
                const GGG = parseInt(jmbValue.substring(4, 7));
                const RR = parseInt(jmbValue.substring(7, 9));
                const BBB = parseInt(jmbValue.substring(9, 12));
                const K = parseInt(jmbValue.substring(12, 13));

                // Validacija dana (1-31)
                if (DD < 1 || DD > 31) {
                    return { valid: false, message: 'Dan rođenja mora biti između 01 i 31' };
                }

                // Validacija meseca (1-12)
                if (MM < 1 || MM > 12) {
                    return { valid: false, message: 'Mesec rođenja mora biti između 01 i 12' };
                }

                // Validacija godine u JMB-u
                // Format: 
                // - 900 <= GGG <= 999 → godina = 1900 + (GGG - 900) = 1000 + GGG (period 1900-1999)
                // - 000 <= GGG <= [trenutna godina - 2000] → godina = 2000 + GGG (period 2000-trenutna godina)
                const currentYear = new Date().getFullYear();
                const currentYearLastTwo = currentYear - 2000; // npr. 2025 -> 25
                let yearFull;
                
                if (GGG >= 900 && GGG <= 999) {
                    // Period 1900-1999
                    yearFull = 1000 + GGG;
                    // Provera: godina mora biti između 1900 i 1999
                    if (yearFull < 1900 || yearFull > 1999) {
                        return { valid: false, message: 'Godina rođenja nije validna' };
                    }
                } else if (GGG >= 0 && GGG <= currentYearLastTwo) {
                    // Period 2000-trenutna godina
                    yearFull = 2000 + GGG;
                    // Provera: godina ne može biti veća od trenutne
                    if (yearFull > currentYear) {
                        return { valid: false, message: 'Godina rođenja nije validna' };
                    }
                } else {
                    // GGG je van validnog opsega
                    return { valid: false, message: 'Godina rođenja nije validna' };
                }

                // Validacija regiona (00-99)
                if (RR < 0 || RR > 99) {
                    return { valid: false, message: 'Region mora biti između 00 i 99' };
                }

                // Validacija BBB (000-999)
                if (BBB < 0 || BBB > 999) {
                    return { valid: false, message: 'Jedinstveni broj mora biti između 000 i 999' };
                }

                // Validacija kontrolne cifre
                const weights = [7, 6, 5, 4, 3, 2, 7, 6, 5, 4, 3, 2];
                let sum = 0;
                for (let i = 0; i < 12; i++) {
                    sum += parseInt(jmbValue[i]) * weights[i];
                }
                const m = sum % 11;
                let calculatedK;
                if (m === 0) {
                    calculatedK = 0;
                } else if (m === 1) {
                    return { valid: false, message: 'JMB je neispravan (kontrolna cifra)' };
                } else {
                    calculatedK = 11 - m;
                }

                if (calculatedK !== K) {
                    return { valid: false, message: 'JMB je neispravan (kontrolna cifra ne odgovara)' };
                }

                return { valid: true };
            }

            // Funkcija za validaciju broja pasoša
            function validatePassport(passport) {
                if (!passport || passport.length < 3) {
                    return { valid: false, message: 'Broj pasoša mora imati najmanje 3 karaktera' };
                }
                if (!/^[A-Z0-9]+$/.test(passport)) {
                    return { valid: false, message: 'Broj pasoša može sadržati samo velika slova i brojeve' };
                }
                return { valid: true };
            }

            // Funkcija za validaciju PIB
            function validatePIB(pibValue) {
                if (!pibValue || pibValue.length !== 8) {
                    return { valid: false, message: 'PIB mora imati tačno 8 cifara' };
                }
                if (!/^[0-9]{8}$/.test(pibValue)) {
                    return { valid: false, message: 'PIB mora sadržati samo cifre' };
                }
                return { valid: true };
            }

            // Funkcija za prikaz greške
            function showError(id, message) {
                const errorEl = document.getElementById(id);
                if (errorEl) {
                    errorEl.textContent = message;
                    errorEl.classList.add('show');
                }
                const inputEl = document.getElementById(id.replace('_error', ''));
                if (inputEl) inputEl.classList.add('error');
            }

            // Funkcija za sakrivanje greške
            function hideError(id) {
                const errorEl = document.getElementById(id);
                if (errorEl) errorEl.classList.remove('show');
                const inputEl = document.getElementById(id.replace('_error', ''));
                if (inputEl) inputEl.classList.remove('error');
            }

            // Upravljanje prikazom polja na osnovu tipa korisnika
            userType.addEventListener('change', function() {
                const value = this.value;
                
                // Reset svih polja
                toggleField('business_type_group', false);
                toggleField('residential_status_group', false);
                toggleField('jmb_group', false);
                toggleField('non_resident_id_type_group', false);
                toggleField('jmb_non_resident_group', false);
                toggleField('passport_group', false);
                toggleField('pib_group', false);

                if (value === 'Fizičko lice') {
                    toggleField('residential_status_group', true);
                } else if (value === 'Registrovan privredni subjekt') {
                    toggleField('business_type_group', true);
                }
            });

            // Upravljanje prikazom polja na osnovu tipa privrednog subjekta
            businessType.addEventListener('change', function() {
                const value = this.value;
                
                if (value === 'Preduzetnik') {
                    toggleField('residential_status_group', true);
                    toggleField('pib_group', false);
                } else if (value && value !== 'Preduzetnik') {
                    toggleField('residential_status_group', false);
                    toggleField('pib_group', true);
                    toggleField('jmb_group', false);
                    toggleField('non_resident_id_type_group', false);
                }
            });

            // Upravljanje prikazom polja na osnovu residential statusa
            residentialStatus.addEventListener('change', function() {
                const value = this.value;
                const isBusiness = userType.value === 'Registrovan privredni subjekt' && businessType.value === 'Preduzetnik';
                
                if (value === 'resident') {
                    toggleField('jmb_group', true);
                    toggleField('non_resident_id_type_group', false);
                    toggleField('jmb_non_resident_group', false);
                    toggleField('passport_group', false);
                } else if (value === 'non-resident') {
                    toggleField('jmb_group', false);
                    toggleField('non_resident_id_type_group', true);
                }
            });

            // Upravljanje prikazom polja na osnovu tipa identifikacije za nerezidenta
            nonResidentIdType.addEventListener('change', function() {
                const value = this.value;
                
                if (value === 'jmb') {
                    toggleField('jmb_non_resident_group', true);
                    toggleField('passport_group', false);
                } else if (value === 'passport') {
                    toggleField('jmb_non_resident_group', false);
                    toggleField('passport_group', true);
                }
            });

            // Validacija JMB-a
            jmb.addEventListener('blur', function() {
                this.value = this.value.replace(/\D/g, '');
                const validation = validateJMB(this.value);
                if (this.value && !validation.valid) {
                    showError('jmb_error', validation.message);
                } else {
                    hideError('jmb_error');
                }
            });

            jmbNonResident.addEventListener('blur', function() {
                this.value = this.value.replace(/\D/g, '');
                const validation = validateJMB(this.value);
                if (this.value && !validation.valid) {
                    showError('jmb_non_resident_error', validation.message);
                } else {
                    hideError('jmb_non_resident_error');
                }
            });

            // Validacija broja pasoša
            passportNumber.addEventListener('input', function() {
                this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
            });

            passportNumber.addEventListener('blur', function() {
                const validation = validatePassport(this.value);
                if (this.value && !validation.valid) {
                    showError('passport_number_error', validation.message);
                } else {
                    hideError('passport_number_error');
                }
            });

            // Validacija PIB-a
            pib.addEventListener('input', function() {
                this.value = this.value.replace(/\D/g, '');
            });

            pib.addEventListener('blur', function() {
                const validation = validatePIB(this.value);
                if (this.value && !validation.valid) {
                    showError('pib_error', validation.message);
                } else {
                    hideError('pib_error');
                }
            });

            // Telefon - kombinovanje sa prefiksom i obaveštenje o formatu
            const phoneFormatNote = document.getElementById('phone_format_note');
            const phoneExamples = {
                '+382': '69123456',
                '+381': '641234567',
                '+387': '61234567',
                '+386': '40123456',
                '+385': '912345678',
                '+383': '44123456',
                '+389': '70123456',
                '+355': '691234567',
                '+359': '888123456',
                '+40': '712345678',
                '+36': '201234567',
                '+421': '912123456',
                '+420': '601123456',
                '+48': '512345678',
                '+49': '15123456789',
                '+43': '664123456',
                '+41': '781234567',
                '+39': '3123456789',
                '+33': '612345678',
                '+32': '470123456',
                '+31': '612345678',
                '+44': '7123456789',
                '+353': '851234567',
                '+45': '20123456',
                '+46': '701234567',
                '+47': '41234567',
                '+358': '501234567',
                '+354': '6612345',
                '+351': '912345678',
                '+34': '612345678',
                '+30': '6941234567',
                '+357': '96123456',
                '+356': '79123456',
                '+352': '621123456',
                '+423': '79123456',
                '+377': '612345678',
                '+376': '312345',
                '+378': '666123456',
                '+7': '9123456789',
                '+380': '501234567',
                '+375': '291234567',
                '+370': '61234567',
                '+371': '21234567',
                '+372': '51234567',
                '+373': '61234567',
                '+374': '77123456',
                '+995': '555123456',
                '+994': '501234567',
                '+90': '5321234567',
                '+1': '5551234567',
                '+52': '5551234567',
                '+54': '91123456789',
                '+55': '11912345678',
                '+56': '912345678',
                '+57': '3001234567',
                '+51': '987654321',
                '+58': '4121234567',
                '+591': '71234567',
                '+593': '991234567',
                '+595': '981123456',
                '+598': '99123456',
                '+592': '6123456',
                '+597': '7123456',
                '+594': '694123456',
                '+86': '13800138000',
                '+81': '9012345678',
                '+82': '1012345678',
                '+84': '9123456789',
                '+66': '812345678',
                '+65': '91234567',
                '+60': '123456789',
                '+62': '81234567890',
                '+63': '9171234567',
                '+64': '211234567',
                '+61': '412345678',
                '+91': '9876543210',
                '+92': '3001234567',
                '+880': '1712345678',
                '+94': '771234567',
                '+95': '9123456789',
                '+855': '12345678',
                '+856': '2012345678',
                '+673': '7123456',
                '+20': '1012345678',
                '+212': '612345678',
                '+213': '551234567',
                '+216': '20123456',
                '+218': '912345678',
                '+220': '3123456',
                '+221': '771234567',
                '+222': '22123456',
                '+223': '20123456',
                '+224': '601234567',
                '+225': '07123456',
                '+226': '70123456',
                '+227': '90123456',
                '+228': '90123456',
                '+229': '96123456',
                '+230': '51234567',
                '+231': '771234567',
                '+232': '25123456',
                '+233': '241234567',
                '+234': '8021234567',
                '+235': '62123456',
                '+236': '70123456',
                '+237': '612345678',
                '+238': '9912345',
                '+239': '9912345',
                '+240': '222123456',
                '+241': '061234567',
                '+242': '061234567',
                '+243': '991234567',
                '+244': '923123456',
                '+245': '955123456',
                '+246': '3801234',
                '+248': '2512345',
                '+249': '912345678',
                '+250': '721234567',
                '+251': '911234567',
                '+252': '712345678',
                '+253': '77123456',
                '+254': '712123456',
                '+255': '712345678',
                '+256': '712345678',
                '+257': '79561234',
                '+258': '821234567',
                '+260': '955123456',
                '+261': '321234567',
                '+262': '692123456',
                '+263': '712345678',
                '+264': '811234567',
                '+265': '991234567',
                '+266': '58123456',
                '+267': '71123456',
                '+268': '76123456',
                '+269': '3212345',
                '+27': '821234567',
                                '+290': '22123',
                                '+291': '7123456',
                                '+297': '5601234',
                                '+298': '221234',
                                '+299': '221234',
                                '+350': '57123456'
            };

            function updatePhonePlaceholder() {
                const prefix = phoneCountry.value;
                const example = phoneExamples[prefix] || '123456789';
                phone.placeholder = `Primjer: ${example}`;
                
                // Ažuriraj obaveštenje
                const selectedOption = phoneCountry.options[phoneCountry.selectedIndex];
                const countryName = selectedOption ? selectedOption.text.split(' ')[1] : 'odabranoj državi';
                phoneFormatNote.textContent = `Format: Unesite broj bez nacionalnog prefiksa ${prefix} i bez vodeće nule. Primjer: ${example}`;
            }

            phoneCountry.addEventListener('change', function() {
                updatePhonePlaceholder();
                updatePhoneFull();
            });

            phone.addEventListener('input', function() {
                // Ukloni sve što nije broj
                this.value = this.value.replace(/\D/g, '');
                // Ako počinje sa 0, ukloni ga
                if (this.value.startsWith('0')) {
                    this.value = this.value.substring(1);
                }
                updatePhoneFull();
            });

            // Inicijalizuj placeholder i obaveštenje
            updatePhonePlaceholder();

            function updatePhoneFull() {
                const prefix = phoneCountry.value;
                const number = phone.value.replace(/\D/g, '');
                phoneFull.value = number ? prefix + number : '';
            }

            // Validacija forme pre slanja
            form.addEventListener('submit', function(e) {
                let isValid = true;

                // Validacija tipa korisnika
                if (!userType.value) {
                    showError('user_type_error', 'Izaberite vrstu korisnika');
                    isValid = false;
                }

                // Validacija tipa privrednog subjekta
                if (userType.value === 'Registrovan privredni subjekt' && !businessType.value) {
                    showError('business_type_error', 'Izaberite tip privrednog subjekta');
                    isValid = false;
                }

                // Validacija residential statusa
                if (userType.value === 'Fizičko lice' && !residentialStatus.value) {
                    showError('residential_status_error', 'Izaberite status prebivališta');
                    isValid = false;
                }

                if ((userType.value === 'Registrovan privredni subjekt' && businessType.value === 'Preduzetnik') && !residentialStatus.value) {
                    showError('residential_status_error', 'Izaberite status prebivališta');
                    isValid = false;
                }

                // Validacija JMB/PIB/Pasport
                if (residentialStatus.value === 'resident' && (userType.value === 'Fizičko lice' || businessType.value === 'Preduzetnik')) {
                    if (!jmb.value) {
                        showError('jmb_error', 'JMB je obavezan za rezidente');
                    isValid = false;
                } else {
                        const validation = validateJMB(jmb.value);
                        if (!validation.valid) {
                            showError('jmb_error', validation.message);
                            isValid = false;
                        }
                    }
                }

                if (residentialStatus.value === 'non-resident') {
                    if (!nonResidentIdType.value) {
                        showError('non_resident_id_type_error', 'Izaberite vrstu identifikacije');
                        isValid = false;
                    } else if (nonResidentIdType.value === 'jmb') {
                        if (!jmbNonResident.value) {
                            showError('jmb_non_resident_error', 'JMB je obavezan');
                            isValid = false;
                        } else {
                            const validation = validateJMB(jmbNonResident.value);
                            if (!validation.valid) {
                                showError('jmb_non_resident_error', validation.message);
                                isValid = false;
                            }
                        }
                    } else if (nonResidentIdType.value === 'passport') {
                        if (!passportNumber.value) {
                            showError('passport_number_error', 'Broj pasoša je obavezan');
                    isValid = false;
                } else {
                            const validation = validatePassport(passportNumber.value);
                            if (!validation.valid) {
                                showError('passport_number_error', validation.message);
                                isValid = false;
                            }
                        }
                    }
                }

                // Validacija PIB za privredne subjekte (osim Preduzetnika)
                if (userType.value === 'Registrovan privredni subjekt' && businessType.value && businessType.value !== 'Preduzetnik') {
                    if (!pib.value) {
                        showError('pib_error', 'PIB je obavezan');
                    isValid = false;
                } else {
                        const validation = validatePIB(pib.value);
                        if (!validation.valid) {
                            showError('pib_error', validation.message);
                            isValid = false;
                        }
                    }
                }

                // Validacija emaila
                const email = document.getElementById('email').value;
                const emailConfirmation = document.getElementById('email_confirmation').value;
                if (!email || !emailConfirmation || email !== emailConfirmation) {
                    showError('email_confirmation_error', 'E-mail adrese se ne poklapaju');
                    isValid = false;
                }

                // Validacija lozinke
                const password = document.getElementById('password').value;
                const passwordConfirmation = document.getElementById('password_confirmation').value;
                if (!password || !passwordConfirmation || password !== passwordConfirmation) {
                    showError('password_confirmation_error', 'Lozinke se ne poklapaju');
                    isValid = false;
                }

                // Validacija adrese
                const address = document.getElementById('address');
                if (address && !address.value.trim()) {
                    showError('address_error', 'Adresa je obavezna');
                    isValid = false;
                }

                if (!isValid) {
                    e.preventDefault();
                    return false;
                }

                // Kombinovanje JMB iz različitih polja u jedno polje pre slanja
                // Ako je rezident, koristi jmb polje
                if (residentialStatus.value === 'resident' && jmb.value) {
                    // JMB za rezident ostaje u jmb polju, samo obriši jmb_non_resident
                    if (jmbNonResident) {
                        jmbNonResident.value = '';
                        jmbNonResident.name = '';
                    }
                } 
                // Ako je nerezident sa JMB-om, prebaci ga u jmb polje
                else if (residentialStatus.value === 'non-resident' && nonResidentIdType.value === 'jmb' && jmbNonResident.value) {
                    jmb.value = jmbNonResident.value;
                    jmbNonResident.value = '';
                    jmbNonResident.name = '';
                }
                // Ako je nerezident sa pasošem, obriši sve JMB polja
                else if (residentialStatus.value === 'non-resident' && nonResidentIdType.value === 'passport') {
                    jmb.value = '';
                    jmb.name = '';
                    if (jmbNonResident) {
                        jmbNonResident.value = '';
                        jmbNonResident.name = '';
                    }
                }

                // Pre slanja, kombinuj sve podatke
                updatePhoneFull();
            });
        })();
    </script>
</body>
</html>
