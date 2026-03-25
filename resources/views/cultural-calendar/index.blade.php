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
    .kk-featured { display: grid; grid-template-columns: 1fr; gap: 12px; align-items: stretch; }
    .kk-feature-card {
        border: 1px solid var(--kk-border);
        border-radius: 10px;
        overflow: hidden;
        background: #fff;
    }
    .kk-feature-image {
        width: 100%;
        height: 150px;
        object-fit: contain;
        background: #f3f4f6;
        display: block;
    }
    .kk-feature-content {
        padding: 12px 14px;
    }
    .kk-feature-meta { font-size: .88rem; color: var(--kk-muted); margin-bottom: 8px; }
    .kk-feature-title { font-size: 1rem; font-weight: 700; margin-bottom: 8px; line-height: 1.3; }
    .kk-feature-desc { font-size: .88rem; color: #4b5563; margin: 0; }
    .kk-bottom { display: grid; grid-template-columns: 1.2fr .8fr; gap: 16px; margin-bottom: 30px; align-items: start; }
    .kk-calendar { padding: 20px; }
    .kk-featured-wrap { padding: 20px; }
    .kk-calendar-header {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 10px;
        margin-bottom: 10px;
    }
    .kk-month-select {
        min-width: 220px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        background: #fff;
        color: #111827;
        font-weight: 600;
        font-size: 13px;
        padding: 8px 10px;
    }
    .kk-calendar-grid { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: 8px; margin-top: 12px; }
    .kk-upcoming {
        margin-top: 16px;
        border-top: 1px solid #e5e7eb;
        padding-top: 12px;
    }
    .kk-upcoming-title {
        text-align: center;
        font-size: 1.25rem;
        font-weight: 700;
        color: #111827;
        margin-bottom: 10px;
    }
    .kk-upcoming-list {
        display: grid;
        gap: 8px;
    }
    .kk-upcoming-item {
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 8px 10px;
        background: #fff;
    }
    .kk-upcoming-image {
        width: 100%;
        height: 70px;
        object-fit: contain;
        background: #f3f4f6;
        border-radius: 6px;
        display: block;
        margin-bottom: 8px;
    }
    .kk-upcoming-meta {
        font-size: 12px;
        color: #6b7280;
        margin-bottom: 3px;
    }
    .kk-upcoming-name {
        font-size: 13px;
        color: #111827;
        font-weight: 700;
        line-height: 1.3;
    }
    .kk-weekdays {
        display: grid;
        grid-template-columns: repeat(7, minmax(0, 1fr));
        gap: 8px;
        margin-top: 8px;
    }
    .kk-weekday {
        text-align: center;
        font-size: 12px;
        font-weight: 700;
        color: #6b7280;
        padding: 4px 0;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    .kk-day {
        position: relative;
        border-radius: 8px;
        border: 1px solid var(--kk-border);
        text-align: center;
        padding: 8px 0;
        font-size: .9rem;
        background: #fff;
    }
    .kk-day.placeholder {
        background: transparent;
        border: none;
        pointer-events: none;
    }
    .kk-day.has-event-1 {
        border-color: #f3c0c4;
        background: #fff4f5;
        color: var(--kk-burgundy);
        font-weight: 600;
    }
    .kk-day.has-event-2plus {
        border-color: #a71524;
        background: #7a0f17;
        color: #fff;
        font-weight: 700;
    }
    .kk-day.is-today {
        box-shadow: 0 0 0 2px #2563eb;
    }
    .kk-day-link {
        display: block;
        text-decoration: none;
        color: inherit;
        line-height: 1;
    }
    .kk-day-link-disabled {
        pointer-events: none;
        cursor: default;
        opacity: .6;
    }
    .kk-day-count {
        position: absolute;
        top: 2px;
        right: 4px;
        min-width: 16px;
        height: 16px;
        border-radius: 9999px;
        font-size: 10px;
        line-height: 16px;
        text-align: center;
        background: rgba(255, 255, 255, 0.9);
        color: #7a0f17;
        font-weight: 700;
        padding: 0 3px;
    }
    .kk-day.has-event-2plus .kk-day-count {
        background: rgba(255, 255, 255, 0.2);
        color: #fff;
        border: 1px solid rgba(255, 255, 255, 0.3);
    }
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
        .kk-grid-3, .kk-bottom { grid-template-columns: 1fr; }
    }
</style>

<div class="container py-4 kk-page">
    <section class="kk-hero">
        <img src="{{ asset('img/kalendar-kulture-logo.png') }}" alt="Logo Kalendara kulture" class="kk-logo">
        <h1 class="kk-hero-title">Kalendar kulture</h1>
        <p class="kk-hero-text">
            Savremeni pregled kulturnih događaja u Kotoru: koncerti, izložbe, predstave, dječji program i manifestacije na jednom mjestu
        </p>
        <div class="kk-hero-actions">
            <a href="#" class="kk-btn-secondary">Kotor grad kulture</a>
            <a href="{{ route('cultural-calendar.events') }}" class="kk-btn-primary">Pogledaj događaje</a>
        </div>
    </section>

    <section class="kk-grid-3">
        <article class="kk-stat-card">
            <div class="kk-stat-label">Danas</div>
            <div class="kk-stat-value">{{ $todayCount }} događaja</div>
        </article>
        <article class="kk-stat-card">
            <div class="kk-stat-label">Ove sedmice</div>
            <div class="kk-stat-value">{{ $weekCount }} događaja</div>
        </article>
        <article class="kk-stat-card">
            <div class="kk-stat-label">Ovog mjeseca</div>
            <div class="kk-stat-value">{{ $monthCount }} događaja</div>
        </article>
    </section>

    <section class="kk-bottom">
        <article class="kk-card kk-calendar" id="kalendar-kulture">
            <div class="kk-calendar-header">
                <form method="GET" action="{{ route('cultural-calendar.index') }}">
                    <select name="month" class="kk-month-select" onchange="this.form.submit()">
                        @foreach($monthOptions as $option)
                            <option value="{{ $option['value'] }}" @selected($selectedMonthValue === $option['value'])>{{ $option['label'] }}</option>
                        @endforeach
                    </select>
                </form>
            </div>
            <div class="kk-weekdays">
                <div class="kk-weekday">Ponedeljak</div>
                <div class="kk-weekday">Utorak</div>
                <div class="kk-weekday">Srijeda</div>
                <div class="kk-weekday">Četvrtak</div>
                <div class="kk-weekday">Petak</div>
                <div class="kk-weekday">Subota</div>
                <div class="kk-weekday">Nedjelja</div>
            </div>
            <div class="kk-calendar-grid">
                @foreach($calendarDays as $day)
                    @if(!empty($day['is_placeholder']))
                        <div class="kk-day placeholder"></div>
                    @else
                        <div class="kk-day
                            {{ $day['event_count'] === 1 ? 'has-event-1' : '' }}
                            {{ $day['event_count'] >= 2 ? 'has-event-2plus' : '' }}
                            {{ $day['is_today'] ? 'is-today' : '' }}">
                            @if(!empty($isKkAdmin))
                                <a href="{{ route('cultural-calendar.day', $day['date']) }}" class="kk-day-link">{{ $day['day'] }}</a>
                            @else
                                @if(!empty($day['has_event']))
                                    <a href="{{ route('cultural-calendar.index', ['month' => $selectedMonthValue, 'date' => $day['date']]) . '#kalendar-kulture' }}" class="kk-day-link">{{ $day['day'] }}</a>
                                @else
                                    <span class="kk-day-link kk-day-link-disabled">{{ $day['day'] }}</span>
                                @endif
                            @endif
                            @if($day['event_count'] > 0)
                                <span class="kk-day-count">{{ $day['event_count'] }}</span>
                            @endif
                        </div>
                    @endif
                @endforeach
            </div>

            <div class="kk-upcoming">
                @if(!is_null($selectedDateEvents))
                    <div class="kk-upcoming-title">
                        Događaji za {{ $selectedDate ? $selectedDate->format('d.m.Y') : '' }}
                    </div>
                    <div class="kk-upcoming-list">
                        @forelse($selectedDateEvents as $event)
                            <div class="kk-upcoming-item">
                                <img
                                    src="{{ $event->slika ? asset('storage/' . $event->slika) : asset('img/kalendar-kulture-default-event.png') }}"
                                    alt="{{ $event->naslov }}"
                                    class="kk-upcoming-image"
                                >
                                <div class="kk-upcoming-meta">
                                    {{ optional($event->datum_od)->format('d.m.Y') }}
                                    @if($event->vrijeme)
                                        • {{ substr((string) $event->vrijeme, 0, 5) }}
                                    @endif
                                    @if($event->lokacija)
                                        • {{ $event->lokacija }}
                                    @endif
                                </div>
                                <div class="kk-upcoming-name">{{ $event->naslov }}</div>
                            </div>
                        @empty
                            <div class="kk-upcoming-item">
                                <div class="kk-upcoming-name" style="font-weight:500; color:#6b7280;">Nema događaja za odabrani datum.</div>
                            </div>
                        @endforelse
                    </div>
                @else
                    <div class="kk-upcoming-title">Naredni događaji</div>
                    <div class="kk-upcoming-list">
                        @forelse($upcomingEvents as $event)
                            <div class="kk-upcoming-item">
                                <img
                                    src="{{ $event->slika ? asset('storage/' . $event->slika) : asset('img/kalendar-kulture-default-event.png') }}"
                                    alt="{{ $event->naslov }}"
                                    class="kk-upcoming-image"
                                >
                                <div class="kk-upcoming-meta">
                                    {{ optional($event->datum_od)->format('d.m.Y') }}
                                    @if($event->vrijeme)
                                        • {{ substr((string) $event->vrijeme, 0, 5) }}
                                    @endif
                                    @if($event->lokacija)
                                        • {{ $event->lokacija }}
                                    @endif
                                </div>
                                <div class="kk-upcoming-name">{{ $event->naslov }}</div>
                            </div>
                        @empty
                            <div class="kk-upcoming-item">
                                <div class="kk-upcoming-name" style="font-weight:500; color:#6b7280;">Nema narednih događaja.</div>
                            </div>
                        @endforelse
                    </div>
                @endif
            </div>
        </article>

        <aside class="kk-card kk-featured-wrap">
            <h2 class="kk-section-title">Istaknuti događaji</h2>
            <div class="kk-featured">
                @if($featuredEvents->isNotEmpty())
                    @foreach($featuredEvents as $event)
                        <article class="kk-feature-card">
                            <img
                                src="{{ $event->slika ? asset('storage/' . $event->slika) : asset('img/kalendar-kulture-default-event.png') }}"
                                alt="{{ $event->naslov }}"
                                class="kk-feature-image"
                            >
                            <div class="kk-feature-content">
                                <div class="kk-feature-meta">
                                    {{ optional($event->datum_od)->format('d.m.Y') }}
                                    @if($event->vrijeme)
                                        • {{ substr((string) $event->vrijeme, 0, 5) }}
                                    @endif
                                    @if($event->lokacija)
                                        • {{ $event->lokacija }}
                                    @endif
                                </div>
                                <div class="kk-feature-title">{{ $event->naslov }}</div>
                                <p class="kk-feature-desc">{{ \Illuminate\Support\Str::limit($event->opis ?? '', 120) }}</p>
                            </div>
                        </article>
                    @endforeach
                @else
                    <article class="kk-feature-card">
                        <img
                            src="{{ asset('img/kalendar-kulture-default-event.png') }}"
                            alt="Podrazumijevana slika događaja"
                            class="kk-feature-image"
                        >
                        <div class="kk-feature-content">
                        <div class="kk-feature-meta">Nema istaknutih događaja</div>
                        <div class="kk-feature-title">Dodajte istaknuti događaj iz administracije</div>
                        <p class="kk-feature-desc">Kada označite događaj kao istaknuti, biće prikazan na ovoj početnoj stranici.</p>
                        </div>
                    </article>
                @endif
            </div>
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
