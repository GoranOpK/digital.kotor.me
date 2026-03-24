@extends('layouts.app')

@section('content')
<style>
    .kk-page {
        --kk-burgundy: #7a0f17;
        --kk-muted: #6b7280;
        --kk-border: #e5e7eb;
        --kk-bg-soft: #f8f9fb;
        max-width: 1120px;
        margin: 0 auto;
    }
    .kk-hero {
        border-radius: 16px;
        padding: 52px 32px;
        background:
            linear-gradient(rgba(20, 20, 20, 0.45), rgba(20, 20, 20, 0.45)),
            url('{{ asset('img/hero.jpg') }}') center/cover no-repeat;
        color: #fff;
        margin-bottom: 30px;
        text-align: center;
    }
    .kk-logo {
        max-width: 170px;
        width: 100%;
        height: auto;
        margin: 0 auto 18px;
        background: rgba(255, 255, 255, 0.94);
        border-radius: 10px;
        padding: 10px;
    }
    .kk-hero-title { font-size: 2.1rem; line-height: 1.2; margin-bottom: 10px; font-weight: 700; }
    .kk-hero-text { max-width: 640px; color: #f3f4f6; margin: 0 auto 16px; }
    .kk-hero-actions { display: flex; justify-content: center; gap: 10px; flex-wrap: wrap; }
    .kk-btn-primary, .kk-btn-secondary {
        display: inline-block; text-decoration: none; border-radius: 8px; padding: 9px 14px; font-weight: 600; font-size: .95rem;
    }
    .kk-btn-primary { background: var(--kk-burgundy); color: #fff; border: 1px solid var(--kk-burgundy); }
    .kk-btn-secondary { background: transparent; color: #fff; border: 1px solid rgba(255,255,255,.55); }
    .kk-grid-3 { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; margin-bottom: 30px; }
    .kk-stat-card, .kk-card { border: 1px solid var(--kk-border); border-radius: 12px; background: #fff; }
    .kk-stat-card { padding: 18px; min-height: 110px; text-align: center; display: flex; flex-direction: column; justify-content: center; }
    .kk-stat-label { font-size: .88rem; color: var(--kk-muted); margin-bottom: 6px; }
    .kk-stat-value { font-size: 1.45rem; font-weight: 700; color: #111827; }
    .kk-section-title { font-size: 1.25rem; margin-bottom: 14px; font-weight: 700; color: #111827; text-align: center; }
    .kk-featured { display: grid; grid-template-columns: 1.2fr .8fr; gap: 16px; margin-bottom: 30px; align-items: stretch; }
    .kk-feature-main { background: var(--kk-bg-soft); padding: 20px; }
    .kk-feature-meta { font-size: .88rem; color: var(--kk-muted); margin-bottom: 8px; }
    .kk-feature-title { font-size: 1.2rem; font-weight: 700; margin-bottom: 10px; }
    .kk-feature-list { display: grid; gap: 12px; }
    .kk-feature-item { padding: 14px; background: #fff; min-height: 90px; }
    .kk-bottom { display: grid; grid-template-columns: 1.2fr .8fr; gap: 16px; }
    .kk-calendar, .kk-filters { padding: 20px; }
    .kk-calendar-grid { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: 8px; margin-top: 12px; }
    .kk-day { border-radius: 8px; border: 1px solid var(--kk-border); text-align: center; padding: 8px 0; font-size: .9rem; background: #fff; }
    .kk-day.has-event { border-color: #f3c0c4; background: #fff4f5; color: var(--kk-burgundy); font-weight: 600; }
    .kk-filter-label { font-size: .88rem; color: var(--kk-muted); margin-bottom: 5px; display: block; }
    .kk-filter-box { border: 1px solid var(--kk-border); border-radius: 8px; padding: 9px 10px; margin-bottom: 10px; color: #111827; background: #fff; min-height: 40px; display: flex; align-items: center; }
    .kk-block-title { text-align: center; margin-bottom: 12px; }
    .kk-footer-wrap {
        margin-top: 34px;
        border-radius: 14px;
        overflow: hidden;
        background:
            linear-gradient(rgba(11, 20, 37, 0.78), rgba(11, 20, 37, 0.78)),
            url('{{ asset('img/kotor-bedemi-view.png') }}') center/cover no-repeat;
        color: #fff;
    }
    .kk-newsletter {
        padding: 34px 20px 30px;
        text-align: center;
        border-bottom: 1px solid rgba(255, 255, 255, 0.15);
    }
    .kk-newsletter h3 {
        margin-bottom: 8px;
        font-size: 1.9rem;
        font-weight: 700;
    }
    .kk-newsletter h3 span {
        color: #d7263d;
    }
    .kk-newsletter p {
        margin: 0 auto 16px;
        max-width: 700px;
        color: rgba(255, 255, 255, 0.9);
        font-size: 0.92rem;
    }
    .kk-news-form {
        display: flex;
        justify-content: center;
        gap: 10px;
        align-items: center;
        flex-wrap: wrap;
    }
    .kk-news-form input[type="email"] {
        width: min(213px, 100%);
        border: 1px solid rgba(255, 255, 255, 0.4);
        background: rgba(255, 255, 255, 0.95);
        border-radius: 6px;
        padding: 9px 10px;
        font-size: 0.9rem;
    }
    .kk-news-check {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 0.88rem;
        color: rgba(255, 255, 255, 0.92);
    }
    .kk-news-btn {
        border: 1px solid #d7263d;
        color: #fff;
        background: #d7263d;
        border-radius: 6px;
        padding: 9px 16px;
        font-size: 0.88rem;
        font-weight: 600;
    }
    .kk-contact {
        text-align: center;
        padding: 24px 16px 28px;
    }
    .kk-contact-title {
        font-size: 1.05rem;
        font-weight: 700;
        margin-bottom: 10px;
        color: #fff;
    }
    .kk-contact p {
        margin-bottom: 6px;
        color: rgba(255, 255, 255, 0.92);
    }
    .kk-contact a {
        color: #fff;
        text-decoration: underline;
    }
    @media (max-width: 992px) {
        .kk-grid-3, .kk-featured, .kk-bottom { grid-template-columns: 1fr; }
    }
</style>

<div class="container py-4 kk-page">
    <section class="kk-hero">
        <img src="{{ asset('img/kalendar-kulture-logo.png') }}" alt="Logo Kalendara kulture" class="kk-logo">
        <h1 class="kk-hero-title">Kalendar kulture</h1>
        <p class="kk-hero-text">
            Savremeni pregled kulturnih događaja u Kotoru: koncerti, izložbe, predstave, dječji program i manifestacije na jednom mjestu.
        </p>
        <div class="kk-hero-actions">
            <a href="#" class="kk-btn-secondary">Kotor grad kulture</a>
            <a href="#" class="kk-btn-primary">Pogledaj događaje</a>
            <a href="{{ route('cultural-events.index') }}" class="kk-btn-secondary">Administracija događaja</a>
        </div>
    </section>

    <section class="kk-grid-3">
        <article class="kk-stat-card">
            <div class="kk-stat-label">Danas</div>
            <div class="kk-stat-value">4 događaja</div>
        </article>
        <article class="kk-stat-card">
            <div class="kk-stat-label">Ove sedmice</div>
            <div class="kk-stat-value">17 događaja</div>
        </article>
        <article class="kk-stat-card">
            <div class="kk-stat-label">Ovog mjeseca</div>
            <div class="kk-stat-value">42 događaja</div>
        </article>
    </section>

    <section>
        <h2 class="kk-section-title">Istaknuti događaji</h2>
        <div class="kk-featured">
            <article class="kk-card kk-feature-main">
                <div class="kk-feature-meta">Petak, 27. mart • 20:00 • Kulturni centar Kotor</div>
                <div class="kk-feature-title">Veče kamerne muzike: Gudački kvartet Mediteran</div>
                <p class="mb-0 text-muted">Placeholder opis događaja za wireframe. Ovdje će ići kratka najava, izvođači i osnovne informacije.</p>
            </article>
            <div class="kk-feature-list">
                <article class="kk-card kk-feature-item">
                    <div class="kk-feature-meta">Subota • Stari grad</div>
                    <div class="fw-semibold">Otvaranje izložbe savremene fotografije</div>
                </article>
                <article class="kk-card kk-feature-item">
                    <div class="kk-feature-meta">Nedjelja • Kino</div>
                    <div class="fw-semibold">Filmsko veče: Mediteranske priče</div>
                </article>
                <article class="kk-card kk-feature-item">
                    <div class="kk-feature-meta">Ponedjeljak • Trg od oružja</div>
                    <div class="fw-semibold">Dječji kulturni program na otvorenom</div>
                </article>
            </div>
        </div>
    </section>

    <section class="kk-bottom">
        <article class="kk-card kk-calendar">
            <h3 class="h5 mb-0 kk-block-title">Mjesečni kalendar (placeholder)</h3>
            <div class="text-muted small mt-1 text-center">Mart 2026</div>
            <div class="kk-calendar-grid">
                <div class="kk-day">1</div><div class="kk-day">2</div><div class="kk-day has-event">3</div><div class="kk-day">4</div><div class="kk-day">5</div><div class="kk-day">6</div><div class="kk-day">7</div>
                <div class="kk-day">8</div><div class="kk-day">9</div><div class="kk-day has-event">10</div><div class="kk-day">11</div><div class="kk-day">12</div><div class="kk-day">13</div><div class="kk-day has-event">14</div>
                <div class="kk-day">15</div><div class="kk-day">16</div><div class="kk-day">17</div><div class="kk-day has-event">18</div><div class="kk-day">19</div><div class="kk-day">20</div><div class="kk-day">21</div>
            </div>
        </article>

        <aside class="kk-card kk-filters">
            <h3 class="h5 mb-3 kk-block-title">Filteri (placeholder)</h3>
            <label class="kk-filter-label">Kategorija</label>
            <div class="kk-filter-box">Svi događaji</div>
            <label class="kk-filter-label">Lokacija</label>
            <div class="kk-filter-box">Sve lokacije</div>
            <label class="kk-filter-label">Datum od</label>
            <div class="kk-filter-box">dd.mm.gggg</div>
            <label class="kk-filter-label">Datum do</label>
            <div class="kk-filter-box">dd.mm.gggg</div>
        </aside>
    </section>

    <section class="kk-footer-wrap">
        <div class="kk-newsletter">
            <h3>Pratite <span>kalendar kulture</span></h3>
            <p>Informišite se o kulturnim dešavanjima u Kotoru putem e-mail obavještenja.</p>
            <div class="kk-news-form">
                <input type="email" placeholder="email@email.com">
                <label class="kk-news-check">
                    <span>Odjavi me</span>
                    <input type="checkbox">
                </label>
                <button type="button" class="kk-news-btn">Pošalji</button>
            </div>
        </div>

        <div class="kk-contact">
            <h3 class="kk-contact-title">Sekretarijat za kulturu, sport i društvene djelatnosti</h3>
            <p><strong>Radno vrijeme:</strong></p>
            <p>Radnim danima od 7:00 do 15:00 časova.</p>
            <p class="mt-3 mb-1"><strong>Kontakt:</strong></p>
            <p class="mb-1">tel. 032/325-874</p>
            <p class="mb-0">E-mail adresa: <a href="mailto:kultura@kotor.me">kultura@kotor.me</a></p>
        </div>
    </section>
</div>
@endsection
