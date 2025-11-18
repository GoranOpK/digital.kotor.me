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
        .hamburger { display:inline-flex; width:40px; height:40px; align-items:center; justify-content:center; border-radius:8px; border:1px solid #e5e7eb; background:#fff; }
        .hamburger:focus { outline:2px solid rgba(0,0,0,.15); }
        .mobile-menu { display:none; flex-direction:column; gap:8px; padding:12px; border:1px solid #e5e7eb; border-radius:12px; background:#fff; margin-top:8px; }
        .btn { display:inline-block; padding:10px 14px; border-radius:8px; font-weight:600; text-decoration:none; border:1px solid transparent; }
        .btn-primary { background:var(--primary); color:#fff; }
        .btn-primary:hover { background:var(--primary-dark); }
        .btn-outline { border-color:var(--primary); color:var(--primary); background:#fff; }
        .btn-outline:hover { border-color:var(--primary-dark); color:var(--primary-dark); }
        .masthead { position:relative; min-height:50vh; border-radius:0; overflow:hidden; margin-top:0; width:100%; background-color:var(--primary); }
        .masthead { background-size:cover; background-position:center center; background-repeat:no-repeat; }
        .masthead::after { content:""; position:absolute; inset:0; background:linear-gradient(180deg, rgba(0,0,0,.55), rgba(0,0,0,.35)); }
        .masthead-inner { position:relative; z-index:1; padding:20px; max-width:1200px; margin:0 auto; }
        .hero { display:grid; grid-template-columns:1fr; gap:20px; align-items:center; padding:12px 0; }
        .hero-card { background:#fff; border:1px solid #e5e7eb; border-radius:16px; padding:20px; box-shadow:0 1px 2px rgba(0,0,0,.06); }
        .hero-title { font-size:28px; line-height:1.2; color:#111827; margin:0 0 8px; }
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
            .hero { grid-template-columns:1.2fr .8fr; }
            .hero-title { font-size:36px; }
            .services { grid-template-columns:repeat(2, 1fr); }
            .nav-links { display:flex; }
            .hamburger { display:none; }
            .mobile-menu { display:none !important; }
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
        .masthead .hamburger { border-color:rgba(255,255,255,.5); background:transparent; color:#fff; }
        .masthead .hamburger:focus { outline:2px solid rgba(255,255,255,.4); }
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
                        <div>
                            <div class="brand-sub" style="font-size:12px;">Digitalni servisi za građane i privredu</div>
                        </div>
                    </div>
                    <nav class="nav">
                        <div class="nav-links" aria-label="Primarna navigacija">
                            <a class="nav-link" href="{{ route('home') }}">Početna</a>
                            <a class="nav-link" href="#placanja">Plaćanja</a>
                            <a class="nav-link" href="#konkursi">Konkursi</a>
                            <a class="nav-link" href="#tenderi">Tenderi</a>
                        </div>
                        @auth
                            <a class="btn btn-outline" href="{{ route('dashboard') }}">Moj panel</a>
                        @else
                            <a class="btn btn-outline" href="{{ route('register') }}">Kreiraj nalog</a>
                            <a class="btn btn-primary" href="{{ route('login') }}">Prijava</a>
                        @endauth
                        <button id="menuToggle" class="hamburger" aria-controls="mobileMenu" aria-expanded="false" aria-label="Meni">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#374151" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="3" y1="6" x2="21" y2="6"/>
                                <line x1="3" y1="12" x2="21" y2="12"/>
                                <line x1="3" y1="18" x2="21" y2="18"/>
                            </svg>
                        </button>
                    </nav>
                </div>
                <div id="mobileMenu" class="mobile-menu" role="menu" aria-labelledby="menuToggle">
                    <a class="nav-link" href="{{ route('home') }}" role="menuitem">Početna</a>
                    <a class="nav-link" href="#placanja" role="menuitem">Plaćanja</a>
                    <a class="nav-link" href="#konkursi" role="menuitem">Konkursi</a>
                    <a class="nav-link" href="#tenderi" role="menuitem">Tenderi</a>
                </div>
                <div class="banner">
                    <small>Zvanični portal opštine KOTOR. Pažljivo čuvamo vaše podatke. <span class="highlight">e-Usluge</span> dostupne 24/7.</small>
                </div>
            </header>
            <section class="hero">
                <div class="hero-card">
                    <div class="badge"><span class="badge-dot"></span> Digital Kotor</div>
                    <h1 class="hero-title">Dobrodošli na centralni portal opštine Kotor</h1>
                    <p class="hero-sub">Pristupite bezbjedno svim opštinskim uslugama: plaćanja, prijave na konkurse, tenderska dokumentacija i više.</p>
                    <div class="cta">
                        @auth
                            <a class="btn btn-primary" href="{{ route('dashboard') }}">Moj panel</a>
                        @else
                            <a class="btn btn-primary" href="{{ route('login') }}">Prijavi se</a>
                            <a class="btn btn-outline" href="{{ route('register') }}">Kreiraj nalog</a>
                        @endauth
                    </div>
                    <p class="note">Nakon prijave, pristupate personalizovanom panelu i istoriji zahtjeva.</p>
                    <div class="services">
                        <a href="#placanja" class="service">
                            <div class="service-icon">₿</div>
                            <div>
                                <h4>Online plaćanja</h4>
                                <p>Uplate komunalija, taksi i drugih opštinskih naknada.</p>
                            </div>
                        </a>
                        <a href="#konkursi" class="service">
                            <div class="service-icon">★</div>
                            <div>
                                <h4>Konkursi</h4>
                                <p>Prijava i praćenje statusa na programe podrške.</p>
                            </div>
                        </a>
                        <a href="#tenderi" class="service">
                            <div class="service-icon">§</div>
                            <div>
                                <h4>Tenderska dokumentacija</h4>
                                <p>Pregled, preuzimanje i otkup dokumentacije.</p>
                            </div>
                        </a>
                    </div>
                </div>
                <div class="hero-card" aria-hidden="true">
                    <div style="height:100%; display:grid; place-items:center; text-align:center; color:#0B3D91;">
                        <div>
                            <div style="font-size:56px; font-weight:800; letter-spacing:.5px;">KOTOR</div>
                            <div style="font-size:14px; color:#6b7280;">Siguran pristup • Transparentno • 24/7</div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </section>

    <main class="container">
        <script>
            (function(){
                var toggle = document.getElementById('menuToggle');
                var menu = document.getElementById('mobileMenu');
                if (toggle && menu) {
                    toggle.addEventListener('click', function(){
                        var open = menu.style.display === 'flex';
                        menu.style.display = open ? 'none' : 'flex';
                        toggle.setAttribute('aria-expanded', String(!open));
                    });
                }
            })();
        </script>
    </main>

    <footer class="container footer">
        <div>© {{ date('Y') }} Opština Kotor • Sva prava zadržana</div>
        <div style="font-size:12px;">Kontakt: <a href="mailto:info@kotor.me" style="color:var(--gov-blue); text-decoration:none;">info@kotor.me</a></div>
    </footer>
</body>
</html>


