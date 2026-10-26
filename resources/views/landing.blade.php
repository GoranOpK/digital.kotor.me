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
        :root { --primary:#0B3D91; --primary-dark:#0A347B; --secondary:#B8860B; --page-bg:#eef2f7; }
        *, *::before, *::after { box-sizing: border-box; }
        html, body { margin:0; padding:0; }
        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", "Apple Color Emoji", "Segoe UI Emoji";
            background: var(--page-bg);
            color: #111827;
        }
        .container { max-width: 1120px; margin: 0 auto; padding: 0 16px; }

        /* Header — compact bar at top */
        .site-header {
            position: relative;
            flex-shrink: 0;
            overflow: hidden;
            color: #fff;
            background-color: #1a2332;
            background-size: cover;
            background-position: center;
        }
        .site-header::after {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(90deg, rgba(10,20,40,.72), rgba(10,20,40,.45));
        }
        .site-header-inner {
            position: relative;
            z-index: 1;
            max-width: 1120px;
            margin: 0 auto;
            padding: 14px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }
        .brand { display: flex; align-items: center; gap: 12px; min-width: 0; }
        .brand-logo { width: 168px; height: 54px; flex-shrink: 0; }
        .brand-logo img { width: 100%; height: 100%; object-fit: contain; display: block; }
        .brand-text { display: flex; flex-direction: column; gap: 2px; min-width: 0; }
        .brand-sub { margin: 0; font-size: 13px; line-height: 1.35; color: rgba(255,255,255,.9); }
        .nav { display: flex; gap: 10px; align-items: center; flex-shrink: 0; }
        .btn {
            display: inline-block;
            padding: 9px 14px;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            border: 1px solid transparent;
            font-size: 14px;
            cursor: pointer;
        }
        .btn-primary { background: var(--primary); color: #fff; }
        .btn-primary:hover { background: var(--primary-dark); }
        .btn-outline { border-color: #fff; color: #fff; background: transparent; }
        .btn-outline:hover { background: rgba(255,255,255,.12); }
        .btn-logout { background: #374151; color: #fff; border: none; }
        .btn-logout:hover { background: #1f2937; }

        /* Main two-column layout */
        .page-main { flex: 1 0 auto; padding: 20px 0 28px; }
        .page-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 16px;
            align-items: start;
        }
        .categories-card {
            background: #f4f6f9;
            border-radius: 16px;
            padding: 18px 0 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,.06);
            overflow: hidden;
        }
        .categories-title {
            margin: 0;
            padding: 0 18px 14px;
            font-size: 20px;
            font-weight: 700;
            color: var(--primary);
        }
        .category-list { display: flex; flex-direction: column; }
        .category-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 18px;
            margin: 0;
            border-radius: 0;
            background: transparent;
            color: #1e3a5f;
            text-decoration: none;
            font-weight: 600;
            font-size: 15px;
            border: 0;
            border-top: 1px solid #e5e9ef;
            transition: background .15s, color .15s;
        }
        .category-link:first-child { border-top: 1px solid #e5e9ef; }
        .category-link:hover,
        .category-link:focus-visible {
            background: #e8eef8;
            color: var(--primary);
            outline: none;
        }
        .category-link:hover .category-chevron,
        .category-link:focus-visible .category-chevron {
            color: var(--primary);
        }
        .category-icon {
            width: 22px;
            height: 22px;
            object-fit: contain;
            display: block;
            flex-shrink: 0;
        }
        .category-label { flex: 1; min-width: 0; }
        .category-chevron {
            color: #9aa3af;
            font-size: 18px;
            line-height: 1;
            flex-shrink: 0;
        }

        .main-col { display: flex; flex-direction: column; gap: 16px; min-width: 0; }

        .welcome-card {
            position: relative;
            overflow: hidden;
            background: #fff;
            border-radius: 14px;
            border: 1px solid #e5e7eb;
            min-height: 280px;
            box-shadow: 0 1px 2px rgba(0,0,0,.04);
        }
        .welcome-card-bg {
            position: absolute;
            inset: 0;
            background-size: cover;
            background-position: right center;
            background-repeat: no-repeat;
            pointer-events: none;
        }
        .welcome-card-content {
            position: relative;
            z-index: 1;
            max-width: 58%;
            padding: 28px 24px 22px;
        }
        .welcome-title {
            margin: 0 0 12px;
            font-size: 28px;
            font-weight: 700;
            line-height: 1.2;
            color: var(--primary);
        }
        .welcome-text {
            margin: 0 0 20px;
            color: #4b5563;
            font-size: 14px;
            line-height: 1.55;
        }
        .feature-row {
            display: flex;
            align-items: stretch;
            gap: 0;
            flex-wrap: wrap;
        }
        .feature-item {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 0 14px;
            min-width: 140px;
            flex: 1;
        }
        .feature-item:first-child { padding-left: 0; }
        .feature-item + .feature-item {
            border-left: 1px solid #d1d5db;
        }
        .feature-icon {
            width: 28px;
            height: 28px;
            color: var(--primary);
            flex-shrink: 0;
            margin-top: 2px;
        }
        .feature-icon svg { width: 100%; height: 100%; display: block; }
        .feature-copy strong {
            display: block;
            font-size: 13px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 2px;
        }
        .feature-copy span {
            display: block;
            font-size: 12px;
            color: #6b7280;
            line-height: 1.35;
        }

        /* Footer */
        .site-footer { margin-top: auto; flex-shrink: 0; }
        .footer-banner {
            width: 100%;
            height: 120px;
            background-size: cover;
            background-position: center;
            background-color: #1e3a5f;
        }
        .footer-meta {
            text-align: center;
            padding: 16px;
            color: #6b7280;
            font-size: 14px;
            background: #fff;
            border-top: 1px solid #e5e7eb;
        }
        .footer-meta a { color: var(--primary); text-decoration: none; }
        .footer-meta a:hover { text-decoration: underline; }

        @media (min-width: 900px) {
            .page-grid {
                grid-template-columns: 250px minmax(0, 1fr);
                gap: 18px;
            }
            .welcome-title { font-size: 32px; }
            .footer-banner { height: 140px; }
        }
        @media (max-width: 899px) {
            .brand-text { display: none; }
            .welcome-card-content { max-width: 100%; background: linear-gradient(180deg, rgba(255,255,255,.92) 55%, rgba(255,255,255,.75)); }
            .welcome-card { min-height: 0; }
            .welcome-card-bg { opacity: .35; }
            .feature-item + .feature-item { border-left: 0; border-top: 1px solid #e5e7eb; padding: 12px 0 0; margin-top: 12px; width: 100%; }
            .feature-item { padding: 0; min-width: 0; flex: 1 1 100%; }
            .feature-row { flex-direction: column; }
        }
        @media (max-width: 520px) {
            .site-header-inner { flex-wrap: wrap; }
            .nav { width: 100%; justify-content: flex-end; }
        }
    </style>
    <meta name="theme-color" content="#0B3D91">
</head>
<body>
    @php($headerBg = public_path('img/hero.jpg'))
    <header class="site-header" @if(file_exists($headerBg)) style="background-image:url('{{ asset('img/hero.jpg') }}')" @endif>
        <div class="site-header-inner">
            <div class="brand">
                <div class="brand-logo">
                    @php($logo = public_path('img/logo.png'))
                    @if (file_exists($logo))
                        <img src="{{ asset('img/logo.png') }}" alt="Digital Kotor">
                    @else
                        DK
                    @endif
                </div>
                <div class="brand-text">
                    <p class="brand-sub">Zvanični portal opštine Kotor</p>
                    <p class="brand-sub">Pažljivo čuvamo Vaše podatke</p>
                </div>
            </div>
            <nav class="nav" aria-label="Korisnički meni">
                @auth
                    <a class="btn btn-outline" href="{{ route('dashboard') }}">Moj panel</a>
                    <form method="POST" action="{{ route('logout') }}" style="margin:0;">
                        @csrf
                        <button type="submit" class="btn btn-logout">Odjava</button>
                    </form>
                @else
                    <a class="btn btn-outline" href="{{ route('register') }}">Kreiraj nalog</a>
                    <a class="btn btn-primary" href="{{ route('login') }}">Prijava</a>
                @endauth
            </nav>
        </div>
    </header>

    <main class="container page-main">
        <div class="page-grid">
            <aside class="categories-card" aria-labelledby="categories-heading">
                <h2 id="categories-heading" class="categories-title">Kategorije</h2>
                <nav class="category-list" aria-label="Kategorije">
                    @foreach ($landingCategories as $category)
                        <a class="category-link" href="{{ $category['url'] }}">
                            @if (! empty($category['icon']) && file_exists(public_path('img/'.$category['icon'])))
                                <img class="category-icon" src="{{ asset('img/'.$category['icon']) }}" alt="" width="22" height="22">
                            @endif
                            <span class="category-label">{{ $category['label'] }}</span>
                            <span class="category-chevron" aria-hidden="true">›</span>
                        </a>
                    @endforeach
                </nav>
            </aside>

            <div class="main-col">
                @php($heroRight = public_path('img/hero-right.jpg'))
                <section class="welcome-card" aria-labelledby="welcome-heading">
                    @if(file_exists($heroRight))
                        <div class="welcome-card-bg" style="background-image:url('{{ asset('img/hero-right.jpg') }}')"></div>
                    @endif
                    <div class="welcome-card-content">
                        <h1 id="welcome-heading" class="welcome-title">Dobrodošli na digitalnu platformu opštine Kotor</h1>
                        <p class="welcome-text">
                            Digital Kotor je zvanična platforma Opštine Kotor za elektronske usluge građana i privrede —
                            prijave, plaćanja, konkursi i informacije na jednom mjestu.
                        </p>
                        <div class="feature-row">
                            <div class="feature-item">
                                <span class="feature-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M12 3l7 3v5c0 4.5-3 8.2-7 9.5C8 19.2 5 15.5 5 11V6l7-3z"/>
                                        <path d="M9.5 12l1.8 1.8L15 10"/>
                                    </svg>
                                </span>
                                <div class="feature-copy">
                                    <strong>Siguran pristup</strong>
                                    <span>Vaši podaci su zaštićeni</span>
                                </div>
                            </div>
                            <div class="feature-item">
                                <span class="feature-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="8"/>
                                        <path d="M12 8v4l2.5 2.5"/>
                                    </svg>
                                </span>
                                <div class="feature-copy">
                                    <strong>Dostupno 24/7</strong>
                                    <span>Usluge kad god vam trebaju</span>
                                </div>
                            </div>
                            <div class="feature-item">
                                <span class="feature-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M7 3h7l5 5v13a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1z"/>
                                        <path d="M14 3v5h5"/>
                                        <path d="M9 13h6M9 17h6"/>
                                    </svg>
                                </span>
                                <div class="feature-copy">
                                    <strong>Transparentno</strong>
                                    <span>Jasan i otvoren rad</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                @include('partials.obavjestenja-panel')
            </div>
        </div>
    </main>

    <footer class="site-footer">
        @php($footerImg = public_path('img/footer.jpg'))
        @if(file_exists($footerImg))
            <div class="footer-banner" style="background-image:url('{{ asset('img/footer.jpg') }}')" role="img" aria-label="Boka Kotorska"></div>
        @endif
        <div class="footer-meta">
            <div>© {{ date('Y') }} Opština Kotor • Sva prava zadržana</div>
            <div style="font-size:12px; margin-top:4px;">Kontakt: <a href="mailto:informatika@kotor.me">informatika@kotor.me</a></div>
        </div>
    </footer>
</body>
</html>
