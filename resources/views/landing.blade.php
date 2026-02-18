{{-- Governmental landing page for Digital Kotor --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Digital Kotor') }}</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    <style>
        :root { --primary:#0B3D91; --primary-dark:#0A347B; --secondary:#B8860B; }
        /* Match Tailwind preflight sizing so layout doesn't subtly shift */
        *, *::before, *::after { box-sizing: border-box; }
        html, body { height:100%; margin:0; padding:0; }
        body { font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", "Apple Color Emoji", "Segoe UI Emoji"; }
        .container { max-width: 1200px; margin: 0 auto; padding: 16px; }
        .header { display:flex; align-items:center; justify-content:space-between; padding:12px 0; }
        .brand { display:flex; align-items:center; gap:12px; }
        .brand-logo { width:188px; height:60px; display:flex; align-items:center; justify-content:center;}
        .brand-logo img { width:100%; height:100%; object-fit:cover; display:block; }
        .brand-name { color:var(--primary); font-weight:700; letter-spacing:.3px; }
        .nav { display:flex; gap:12px; align-items:center; }
        .nav-links { display:none; gap:12px; }
        .nav-link { color:#374151; text-decoration:none; font-weight:600; padding:8px 10px; border-radius:8px; }
        .nav-link:hover { color:var(--primary); text-decoration:underline; }
        .btn { display:inline-block; padding:10px 14px; border-radius:8px; font-weight:600; text-decoration:none; border:1px solid transparent; }
        .btn-primary { background:var(--primary); color:#fff; }
        .btn-primary:hover { background:var(--primary-dark); }
        .btn-outline { border-color:var(--primary); color:var(--primary); background:#fff; }
        .btn-outline:hover { border-color:var(--primary-dark); color:var(--primary-dark); }
        .masthead { position:relative; min-height:50vh; border-radius:0; overflow:hidden; margin-top:0; width:100%; background-color:var(--primary); }
        .masthead { background-size:cover; background-position:center center; background-repeat:no-repeat; }
        .masthead::after { content:""; position:absolute; inset:0; background:linear-gradient(180deg, rgba(0,0,0,.55), rgba(0,0,0,.35)); }
        .masthead-inner { position:relative; z-index:1; padding:20px; max-width:1200px; margin:0 auto; }
        .hero { display:grid; grid-template-columns:1fr; gap:20px; align-items:start; padding:12px 0; }
        .hero-card { background:#fff; border:1px solid #e5e7eb; border-radius:16px; padding:20px; box-shadow:0 1px 2px rgba(0,0,0,.06); }
        /* Card background images (inside each card) */
        .hero-card.has-bg { position:relative; overflow:hidden; }
        .hero-card.has-bg::before {
            content:"";
            position:absolute;
            inset:0;
            background-image: var(--card-bg, none);
            background-size:cover;
            background-position:center;
            background-repeat:no-repeat;
            opacity: var(--card-bg-opacity, 0.55);
        }
        .hero-card.has-bg::after {
            content:"";
            position:absolute;
            inset:0;
            /* Fade image down (stronger top → lighter bottom) */
            background: linear-gradient(
                180deg,
                rgba(255,255,255,.20) 0%,
                rgba(255,255,255,.75) 25%,
                rgba(255,255,255,.95) 50%
            );
        }
        .hero-card.has-bg > * { position:relative; z-index:1; }

        /* Make the SAME image feel continuous across columns (desktop only) */
        @media (min-width: 768px) {
            /* Grid columns are 1.8fr + .6fr => 75% + 25% */
            .hero-card.bg-slice-left::before {
                background-size: 133.333% auto; /* 100% / 0.75 */
                background-position: left top;
            }
            .hero-card.bg-slice-right::before {
                background-size: 400% auto; /* 100% / 0.25 */
                background-position: right top;
            }
        }
        .hero-title { font-size:28px; font-weight:700; line-height:1.2; color:#111827; margin:0 0 8px; }
        .hero-sub { color:#4b5563; margin:0 0 16px; }
        .services { display:grid; grid-template-columns:1fr; gap:12px; margin-top:12px; }
        .service { border:1px solid #e5e7eb; border-radius:12px; padding:14px; background:#ffffff; display:flex; gap:12px; align-items:flex-start; }
        .service-icon { width:36px; height:36px; border-radius:8px; border:1px solid var(--primary); color:var(--primary); background:#fff; display:flex; align-items:center; justify-content:center; font-weight:700; }
        .service h4 { margin:0 0 4px; font-size:16px; color:#111827; }
        .service p { margin:0; color:#6b7280; font-size:14px; }
        .cta { display:flex; gap:10px; flex-wrap:wrap; margin-top:12px; }
        .footer { border-top:1px solid #e5e7eb; padding:16px 0; color:#6b7280; font-size:14px; margin-top:24px; }
        @media (min-width: 768px) {
            .masthead { background-attachment:fixed; }
            .hero { grid-template-columns:1.8fr .6fr; }
            .hero-title { font-size:36px; }
            .services { grid-template-columns:repeat(2, 1fr); }
            .nav-links { display:flex; }
        }
        @media (min-width: 1024px) {
            .services { grid-template-columns:repeat(3, 1fr); }
        }
        .badge { display:inline-flex; align-items:center; gap:8px; background:#fff; border:1px solid #e5e7eb; border-radius:9999px; padding:6px 10px; color:#374151; font-weight:600; }
        .badge-dot { width:8px; height:8px; border-radius:9999px; background:var(--secondary); box-shadow:0 0 0 3px rgba(184,134,11,.15); }
        .note { font-size:12px; color:#6b7280; margin-top:8px; }
        .highlight { color:var(--secondary); font-weight:700; }
        .banner { background:linear-gradient(90deg, var(--primary), var(--primary-dark)); color:#fff; border-radius:14px; padding:14px 16px; }
        .banner small { opacity:.9; }
        .masthead .brand-name { color:#fff; }
        .masthead .brand-sub { color:rgba(255,255,255,.85); }
        .masthead .nav-link { color:#fff; }
        .masthead .nav-link:hover { color:#fff; text-decoration:underline; }
        .masthead .btn-outline { border-color:#fff; color:#fff !important; background:transparent; }
        .masthead .btn-outline:hover { background:rgba(255,255,255,.1); border-color:#fff; color:#fff !important; }

        /* Ensure semantic content stays styled even with CSS resets (e.g., Tailwind preflight) */
        .hero-card section h2 {
            margin: 16px 0 8px;
            font-size: 18px;
            font-weight: 700;
            line-height: 1.25;
            color: #111827;
        }
        .hero-card section p { margin: 0 0 12px; color: #374151; }
        .hero-card section strong { font-weight: 700; }
        .hero-card section ul {
            margin: 0 0 12px;
            padding-left: 20px;
            list-style: disc;
            list-style-position: outside;
            color: #374151;
        }
        .hero-card section li { margin: 6px 0; }
    </style>
    
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <meta name="theme-color" content="#0B3D91">
</head>
<body style="background:#f9fafb; margin:0; padding:0;">
    

    @php($hero = public_path('img/hero.jpg'))
    <section class="masthead" style="background-image:@if(file_exists($hero)) url('{{ asset('img/hero.jpg') }}') @endif;">
        <div class="masthead-inner">
            <header>
                <div class="header">
                    <div class="brand">
                        <div class="brand-logo">
                            @php($logo = public_path('img/logo.png'))
                            @if (file_exists($logo))
                                <img src="{{ asset('img/logo.png') }}" alt="Logo Opštine Kotor">
                            @else
                                DK
                            @endif
                        </div>
                        
                    </div>
                    <nav class="nav">
                        @auth
                            <a class="btn btn-outline" href="{{ route('dashboard') }}">Moj panel</a>
                        @else
                            <a class="btn btn-outline" href="{{ route('register') }}">Kreiraj nalog</a>
                            <a class="btn btn-primary" href="{{ route('login') }}">Prijava</a>
                        @endauth
                    </nav>
                </div>
                <div class="banner">
                    <small>Zvanični portal opštine KOTOR. Pažljivo čuvamo vaše podatke. <span class="highlight">e-Usluge</span> dostupne 24/7.</small>
                </div>
            </header>
            <section class="hero">
                <div class="hero-card has-bg bg-slice-left" style="--card-bg:url('{{ asset('img/hero-left.jpg') }}');">
                    <h1 class="hero-title">Dobrodošli na Bidon portal opštine Kotor</h1>
                    <section>
                        <h2>O platformi</h2>
                        <p>
                            <strong>Digital Kotor</strong> je savremena digitalna platforma namijenjena građanima, privredi i institucijama, 
                            koja omogućava jednostavno, brzo i sigurno obavljanje različitih administrativnih i poslovnih usluga putem interneta.
                        </p>

                        <h2>Na jednom mjestu možete:</h2>
                        <ul>
                            <li>Podnositi zahtjeve i dokumentaciju elektronskim putem</li>
                            <li>Pratiti i učestvovati u tenderima i javnim nabavkama</li>
                            <li>Prijavljivati se na konkurse</li>
                            <li>Izvršavati elektronska plaćanja taksi i drugih obaveza</li>
                            <li>Pratiti status svojih zahtjeva u realnom vremenu</li>
                        </ul>

                        <p>
                            Cilj platforme je da unaprijedi transparentnost, efikasnost i dostupnost javnih usluga, 
                            smanji administrativne procedure i omogući građanima i privredi da svoje obaveze završavaju brzo i bez čekanja u redovima.
                        </p>

                        <p>
                            Digital Kotor predstavlja korak ka modernoj i otvorenoj upravi, 
                            u skladu sa savremenim standardima digitalne transformacije.
                        </p>

                        <div class="highlight">
                            Jednostavno. Sigurno. Transparentno.
                        </div>
                    </section>
                </div>
                <div class="hero-card has-bg bg-slice-right" aria-hidden="true" style="--card-bg:url('{{ asset('img/hero-left.jpg') }}');">
                    <div style="text-align:center;">
                        <h1 class="hero-title" style="font-weight:bold;">Kategorije</h1>
                        <div style="font-size:14px; color:#6b7280; margin-top:8px; margin-bottom:16px;">Siguran pristup • Transparentno • 24/7</div>
                        <div style="display:flex; flex-direction:column; gap:8px; align-items:center;">
                            <a href="{{ route('payments.index') }}" style="color:#fff; text-decoration:none; font-weight:600; font-size:16px; padding:8px 16px; border-radius:8px; border:1px solid #0B3D91; background:#0B3D91; width:100%; max-width:200px;">Plaćanja</a>
                            <a href="{{ route('competitions.index') }}" style="color:#fff; text-decoration:none; font-weight:600; font-size:16px; padding:8px 16px; border-radius:8px; border:1px solid #0B3D91; background:#0B3D91; width:100%; max-width:200px;">Konkursi</a>
                            <a href="{{ route('tenders.index') }}" style="color:#fff; text-decoration:none; font-weight:600; font-size:16px; padding:8px 16px; border-radius:8px; border:1px solid #0B3D91; background:#0B3D91; width:100%; max-width:200px;">Tenderi</a>
                            <div style="color:#9ca3af; text-decoration:none; font-weight:600; font-size:16px; padding:8px 16px; border-radius:8px; border:1px solid #d1d5db; background:#f3f4f6; width:100%; max-width:200px; cursor:not-allowed; opacity:0.6;">Blero Bliznar</div>
                            <div style="color:#9ca3af; text-decoration:none; font-weight:600; font-size:16px; padding:8px 16px; border-radius:8px; border:1px solid #d1d5db; background:#f3f4f6; width:100%; max-width:200px; cursor:not-allowed; opacity:0.6;">Pudi Kator</div>
                            <div style="color:#9ca3af; text-decoration:none; font-weight:600; font-size:16px; padding:8px 16px; border-radius:8px; border:1px solid #d1d5db; background:#f3f4f6; width:100%; max-width:200px; cursor:not-allowed; opacity:0.6;">Vunjaš Aznavur</div>
                            <div style="color:#9ca3af; text-decoration:none; font-weight:600; font-size:16px; padding:8px 16px; border-radius:8px; border:1px solid #d1d5db; background:#f3f4f6; width:100%; max-width:200px; cursor:not-allowed; opacity:0.6;">Dunja Svibor</div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </section>

    <main class="container">
    </main>

    <footer class="container footer">
        <div>© {{ date('Y') }} Opština Kotor • Sva prava zadržana</div>
        <div style="font-size:12px;">Kontakt: <a href="mailto:info@kotor.me" style="color:var(--gov-blue); text-decoration:none;">info@kotor.me</a></div>
    </footer>
</body>
</html>


