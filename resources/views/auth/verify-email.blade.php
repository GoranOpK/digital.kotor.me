{{-- Stranica za verifikaciju email adrese --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verifikacija email adrese - {{ config('app.name', 'Digital Kotor') }}</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    <style>
        :root { --primary:#0B3D91; --primary-dark:#0A347B; --secondary:#B8860B; }
        html, body { height:100%; margin:0; padding:0; }
        body { font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", "Apple Color Emoji", "Segoe UI Emoji"; background:#f9fafb; }
        .container { max-width: 600px; margin: 40px auto; padding: 16px; }
        .verify-card { background:#fff; border:1px solid #e5e7eb; border-radius:16px; padding:32px; box-shadow:0 1px 3px rgba(0,0,0,.1); }
        .verify-title { font-size:24px; color:#111827; margin:0 0 16px; font-weight:700; }
        .verify-message { color:#374151; margin:0 0 24px; line-height:1.6; }
        .verify-success { background:#d1fae5; border:1px solid #10b981; border-radius:8px; padding:12px 16px; color:#065f46; margin-bottom:24px; font-size:14px; }
        .verify-info { background:#dbeafe; border:1px solid #3b82f6; border-radius:8px; padding:12px 16px; color:#1e40af; margin-bottom:24px; font-size:14px; }
        .btn { display:inline-block; padding:12px 24px; border-radius:8px; font-weight:600; text-decoration:none; border:1px solid transparent; cursor:pointer; font-size:14px; transition:background-color .2s; }
        .btn-primary { background:var(--primary); color:#fff; border:none; }
        .btn-primary:hover { background:var(--primary-dark); }
        .btn-outline { border-color:var(--primary); color:var(--primary); background:#fff; }
        .btn-outline:hover { border-color:var(--primary-dark); color:var(--primary-dark); }
        .btn-link { color:var(--primary); text-decoration:none; font-size:14px; padding:8px 0; }
        .btn-link:hover { text-decoration:underline; }
        .actions { display:flex; gap:16px; flex-wrap:wrap; margin-top:24px; }
        .email-address { font-weight:600; color:var(--primary); }
    </style>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <meta name="theme-color" content="#0B3D91">
</head>
<body>
    <div class="container">
        <div class="verify-card">
            <h1 class="verify-title">Verifikacija email adrese</h1>
            
            @if (session('status') == 'registration-success')
                <div class="verify-success">
                    <strong>Uspešno ste kreirali nalog!</strong> Podaci su sačuvani. Molimo vas da proverite email adresu <span class="email-address">{{ auth()->user()->email }}</span> i kliknite na link za verifikaciju.
                </div>
            @endif

            <div class="verify-message">
                Hvala vam što ste se registrovali! Pre nego što počnete, molimo vas da verifikujete svoju email adresu klikom na link koji smo vam poslali na email adresu <span class="email-address">{{ auth()->user()->email }}</span>.
            </div>

            <div class="verify-info">
                Ako niste primili email, možemo vam poslati novi link za verifikaciju.
            </div>

            @if (session('status') == 'verification-link-sent')
                <div class="verify-success">
                    Novi link za verifikaciju je poslat na vašu email adresu.
                </div>
            @endif

            <div class="actions">
                <form method="POST" action="{{ route('verification.send') }}">
                    @csrf
                    <button type="submit" class="btn btn-primary">
                        Pošalji ponovo link za verifikaciju
                    </button>
                </form>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-outline">
                        Odjavi se
                    </button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
