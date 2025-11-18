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
        .container { max-width: 800px; margin: 40px auto; padding: 16px; }
        .register-card { background:#fff; border:1px solid #e5e7eb; border-radius:16px; padding:32px; box-shadow:0 1px 3px rgba(0,0,0,.1); }
        .register-title { font-size:28px; color:#111827; margin:0 0 8px; font-weight:700; }
        .register-subtitle { color:#6b7280; margin:0 0 24px; font-size:14px; }
        .form-group { margin-bottom:20px; }
        .form-label { display:block; font-weight:600; color:#374151; margin-bottom:8px; font-size:14px; }
        .form-label .required { color:#dc2626; }
        .form-control { width:100%; padding:10px 14px; border:1px solid #d1d5db; border-radius:8px; font-size:14px; transition:border-color .2s; }
        .form-control:focus { outline:none; border-color:var(--primary); box-shadow:0 0 0 3px rgba(11,61,145,.1); }
        .form-control.error { border-color:#dc2626; }
        .form-error { color:#dc2626; font-size:12px; margin-top:4px; display:none; }
        .form-error.show { display:block; }
        .phone-wrapper { display:flex; gap:8px; }
        .phone-flag { position:relative; }
        .phone-flag-select { width:80px; padding:10px 14px; border:1px solid #d1d5db; border-radius:8px; font-size:14px; cursor:pointer; background:#fff; }
        .phone-flag-select:focus { outline:none; border-color:var(--primary); box-shadow:0 0 0 3px rgba(11,61,145,.1); }
        .phone-input { flex:1; }
        .date-wrapper { display:grid; grid-template-columns:1fr 1fr 1fr; gap:8px; }
        .btn { display:inline-block; padding:12px 24px; border-radius:8px; font-weight:600; text-decoration:none; border:1px solid transparent; cursor:pointer; font-size:14px; transition:background-color .2s; }
        .btn-primary { background:var(--primary); color:#fff; border:none; }
        .btn-primary:hover { background:var(--primary-dark); }
        .btn-primary:disabled { opacity:.6; cursor:not-allowed; }
        .btn-link { color:var(--primary); text-decoration:none; font-size:14px; }
        .btn-link:hover { text-decoration:underline; }
        .form-footer { margin-top:24px; padding-top:24px; border-top:1px solid #e5e7eb; text-align:center; }
        .flag-emoji { font-size:18px; margin-right:6px; }
        @media (max-width: 640px) {
            .date-wrapper { grid-template-columns:1fr; }
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
                
                <div class="form-group">
                    <label for="user_type" class="form-label">Vrsta korisnika <span class="required">*</span></label>
                    <select name="user_type" id="user_type" class="form-control" required>
                        <option value="">Izaberite vrstu korisnika</option>
                        <option value="Fizičko lice">Fizičko lice</option>
                        <option value="Registrovan privredni subjekt">Registrovan privredni subjekt</option>
                    </select>
                    <div class="form-error" id="user_type_error"></div>
                </div>

                <div class="form-group">
                    <label for="first_name" class="form-label">Ime <span class="required">*</span></label>
                    <input type="text" name="first_name" id="first_name" class="form-control" required autocomplete="given-name">
                    <div class="form-error" id="first_name_error"></div>
                </div>

                <div class="form-group">
                    <label for="last_name" class="form-label">Prezime <span class="required">*</span></label>
                    <input type="text" name="last_name" id="last_name" class="form-control" required autocomplete="family-name">
                    <div class="form-error" id="last_name_error"></div>
                </div>

                <div class="form-group">
                    <label for="email" class="form-label">E-mail <span class="required">*</span></label>
                    <input type="email" name="email" id="email" class="form-control" required autocomplete="email">
                    <div class="form-error" id="email_error"></div>
                </div>

                <div class="form-group">
                    <label for="email_confirmation" class="form-label">Potvrdi e-mail <span class="required">*</span></label>
                    <input type="email" name="email_confirmation" id="email_confirmation" class="form-control" required autocomplete="email">
                    <div class="form-error" id="email_confirmation_error"></div>
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Korisnička lozinka <span class="required">*</span></label>
                    <input type="password" name="password" id="password" class="form-control" required autocomplete="new-password" minlength="8">
                    <div class="form-error" id="password_error"></div>
                    <div class="note" style="margin-top: 4px;">Lozinka mora imati najmanje 8 karaktera</div>
                </div>

                <div class="form-group">
                    <label for="password_confirmation" class="form-label">Potvrdi lozinku <span class="required">*</span></label>
                    <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" required autocomplete="new-password">
                    <div class="form-error" id="password_confirmation_error"></div>
                </div>

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
                        <input type="tel" name="phone" id="phone" class="form-control phone-input" required autocomplete="tel" placeholder="Broj telefona">
                    </div>
                    <input type="hidden" name="phone_full" id="phone_full">
                    <div class="form-error" id="phone_error"></div>
                </div>

                <div class="form-group">
                    <label class="form-label">Datum rođenja <span class="required">*</span></label>
                    <div class="date-wrapper">
                        <select name="birth_day" id="birth_day" class="form-control" required>
                            <option value="">Dan</option>
                            @for($i = 1; $i <= 31; $i++)
                                <option value="{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}">{{ $i }}</option>
                            @endfor
                        </select>
                        <select name="birth_month" id="birth_month" class="form-control" required>
                            <option value="">Mesec</option>
                            @foreach(['01' => 'Januar', '02' => 'Februar', '03' => 'Mart', '04' => 'April', '05' => 'Maj', '06' => 'Jun', '07' => 'Jul', '08' => 'Avgust', '09' => 'Septembar', '10' => 'Oktobar', '11' => 'Novembar', '12' => 'Decembar'] as $num => $name)
                                <option value="{{ $num }}">{{ $name }}</option>
                            @endforeach
                        </select>
                        <select name="birth_year" id="birth_year" class="form-control" required>
                            <option value="">Godina</option>
                            @for($i = date('Y'); $i >= 1920; $i--)
                                <option value="{{ $i }}">{{ $i }}</option>
                            @endfor
                        </select>
                    </div>
                    <input type="hidden" name="date_of_birth" id="date_of_birth">
                    <div class="form-error" id="date_of_birth_error"></div>
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
            const firstName = document.getElementById('first_name');
            const lastName = document.getElementById('last_name');
            const email = document.getElementById('email');
            const emailConfirmation = document.getElementById('email_confirmation');
            const password = document.getElementById('password');
            const passwordConfirmation = document.getElementById('password_confirmation');
            const phoneCountry = document.getElementById('phone_country');
            const phone = document.getElementById('phone');
            const phoneFull = document.getElementById('phone_full');
            const birthDay = document.getElementById('birth_day');
            const birthMonth = document.getElementById('birth_month');
            const birthYear = document.getElementById('birth_year');
            const dateOfBirth = document.getElementById('date_of_birth');

            // Capitalize prvo slovo za ime i prezime
            function capitalizeFirst(str) {
                if (!str) return '';
                return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase();
            }

            firstName.addEventListener('blur', function() {
                this.value = capitalizeFirst(this.value);
            });

            lastName.addEventListener('blur', function() {
                this.value = capitalizeFirst(this.value);
            });

            // Email validacija
            function validateEmail(emailValue) {
                const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return re.test(emailValue);
            }

            email.addEventListener('blur', function() {
                if (this.value && !validateEmail(this.value)) {
                    showError('email_error', 'Unesite validnu e-mail adresu');
                    this.classList.add('error');
                } else {
                    hideError('email_error');
                    this.classList.remove('error');
                }
            });

            emailConfirmation.addEventListener('blur', function() {
                if (this.value && this.value !== email.value) {
                    showError('email_confirmation_error', 'E-mail adrese se ne poklapaju');
                    this.classList.add('error');
                } else {
                    hideError('email_confirmation_error');
                    this.classList.remove('error');
                }
            });

            // Lozinka validacija
            passwordConfirmation.addEventListener('blur', function() {
                if (this.value && this.value !== password.value) {
                    showError('password_confirmation_error', 'Lozinke se ne poklapaju');
                    this.classList.add('error');
                } else {
                    hideError('password_confirmation_error');
                    this.classList.remove('error');
                }
            });

            // Telefon - uklanjanje nule na početku i kombinovanje sa prefiksom
            phoneCountry.addEventListener('change', function() {
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

            function updatePhoneFull() {
                const prefix = phoneCountry.value;
                const number = phone.value.replace(/\D/g, '');
                if (number) {
                    phoneFull.value = prefix + number;
                } else {
                    phoneFull.value = '';
                }
            }

            // Datum rođenja - kombinovanje u jedan string
            function updateDateOfBirth() {
                const day = birthDay.value;
                const month = birthMonth.value;
                const year = birthYear.value;
                if (day && month && year) {
                    dateOfBirth.value = year + '-' + month + '-' + day;
                } else {
                    dateOfBirth.value = '';
                }
            }

            birthDay.addEventListener('change', updateDateOfBirth);
            birthMonth.addEventListener('change', updateDateOfBirth);
            birthYear.addEventListener('change', updateDateOfBirth);

            // Form validacija pre slanja
            form.addEventListener('submit', function(e) {
                let isValid = true;

                // Validacija imena i prezimena
                if (!firstName.value.trim()) {
                    showError('first_name_error', 'Ime je obavezno');
                    firstName.classList.add('error');
                    isValid = false;
                } else {
                    firstName.value = capitalizeFirst(firstName.value);
                    hideError('first_name_error');
                    firstName.classList.remove('error');
                }

                if (!lastName.value.trim()) {
                    showError('last_name_error', 'Prezime je obavezno');
                    lastName.classList.add('error');
                    isValid = false;
                } else {
                    lastName.value = capitalizeFirst(lastName.value);
                    hideError('last_name_error');
                    lastName.classList.remove('error');
                }

                // Validacija emaila
                if (!email.value || !validateEmail(email.value)) {
                    showError('email_error', 'Unesite validnu e-mail adresu');
                    email.classList.add('error');
                    isValid = false;
                } else {
                    hideError('email_error');
                    email.classList.remove('error');
                }

                if (!emailConfirmation.value || emailConfirmation.value !== email.value) {
                    showError('email_confirmation_error', 'E-mail adrese se ne poklapaju');
                    emailConfirmation.classList.add('error');
                    isValid = false;
                } else {
                    hideError('email_confirmation_error');
                    emailConfirmation.classList.remove('error');
                }

                // Validacija lozinke
                if (!password.value) {
                    showError('password_error', 'Lozinka je obavezna');
                    password.classList.add('error');
                    isValid = false;
                } else {
                    hideError('password_error');
                    password.classList.remove('error');
                }

                if (!passwordConfirmation.value || passwordConfirmation.value !== password.value) {
                    showError('password_confirmation_error', 'Lozinke se ne poklapaju');
                    passwordConfirmation.classList.add('error');
                    isValid = false;
                } else {
                    hideError('password_confirmation_error');
                    passwordConfirmation.classList.remove('error');
                }

                // Validacija telefona
                updatePhoneFull();
                if (!phone.value.trim()) {
                    showError('phone_error', 'Broj telefona je obavezan');
                    phone.classList.add('error');
                    isValid = false;
                } else {
                    hideError('phone_error');
                    phone.classList.remove('error');
                }

                // Validacija datuma
                updateDateOfBirth();
                if (!birthDay.value || !birthMonth.value || !birthYear.value) {
                    showError('date_of_birth_error', 'Datum rođenja je obavezan');
                    isValid = false;
                } else {
                    hideError('date_of_birth_error');
                }

                if (!isValid) {
                    e.preventDefault();
                    return false;
                }
            });

            function showError(id, message) {
                const errorEl = document.getElementById(id);
                if (errorEl) {
                    errorEl.textContent = message;
                    errorEl.classList.add('show');
                }
            }

            function hideError(id) {
                const errorEl = document.getElementById(id);
                if (errorEl) {
                    errorEl.classList.remove('show');
                }
            }
        })();
    </script>
</body>
</html>
