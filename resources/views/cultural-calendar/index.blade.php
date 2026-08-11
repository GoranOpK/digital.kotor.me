@extends('layouts.app')

@section('content')
<style>
    .kk-page {
        --kk-burgundy: #7a0f17;
        --kk-muted: #6b7280;
        --kk-border: #e5e7eb;
        --kk-bg-soft: #f8f9fb;
    }
    .kk-hero {
        border-radius: 16px;
        padding: 18px 20px;
        max-height: min(42vh, 340px);
        min-height: 0;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        overflow: hidden;
        box-sizing: border-box;
        background:
            url('{{ asset('img/heroKK.jpg') }}') center/cover no-repeat;
        color: #fff;
        margin-bottom: 30px;
        text-align: center;
    }
    .kk-hero .kk-logo {
        max-width: 260px;
        margin-bottom: 0;
    }
    .kk-logo {
        max-width: 300px;
        width: 100%;
        height: auto;
        margin: 0 auto 18px;
        display: block;
        object-fit: contain;
    }
    .kk-grid-3 { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; margin-bottom: 30px; }
    .kk-stat-card, .kk-card { border: 1px solid var(--kk-border); border-radius: 12px; background: #fff; }
    .kk-stat-card { padding: 18px; min-height: 110px; text-align: center; display: flex; flex-direction: column; justify-content: center; }
    a.kk-stat-card { text-decoration: none; color: inherit; transition: border-color .15s ease, box-shadow .15s ease; }
    a.kk-stat-card:hover { border-color: #c4c9d2; box-shadow: 0 1px 4px rgba(17, 24, 39, 0.06); }
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
        aspect-ratio: 1 / 1;
        height: auto;
        object-fit: cover;
        object-position: center;
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
        background: #fff;
        overflow: hidden;
        height: 96px;
    }
    .kk-upcoming-item-empty {
        display: flex;
        align-items: center;
        padding: 8px 10px;
        height: auto;
        min-height: 48px;
    }
    .kk-upcoming-link {
        display: flex;
        flex-direction: row;
        align-items: stretch;
        height: 100%;
        color: inherit;
        text-decoration: none;
    }
    .kk-upcoming-link:hover {
        background: #f9fafb;
    }
    .kk-show-all {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-top: 12px;
        width: 100%;
        padding: 10px 14px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        background: #fff;
        color: #111827;
        font-size: .95rem;
        font-weight: 600;
        text-decoration: none;
        box-sizing: border-box;
    }
    .kk-show-all:hover {
        background: #f9fafb;
        border-color: #c4c9d2;
    }
    .kk-upcoming-photo {
        flex: 0 0 96px;
        width: 96px;
        height: 96px;
        background: #f3f4f6;
        overflow: hidden;
    }
    .kk-upcoming-photo img,
    .kk-upcoming-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
        margin: 0;
        border-radius: 0;
    }
    .kk-upcoming-body {
        flex: 1 1 auto;
        min-width: 0;
        padding: 10px 12px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        overflow: hidden;
    }
    .kk-upcoming-meta {
        font-size: 12px;
        color: #6b7280;
        margin-bottom: 3px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .kk-upcoming-name {
        font-size: 13px;
        color: #111827;
        font-weight: 700;
        line-height: 1.3;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
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
    .kk-block-title { text-align: center; margin-bottom: 0; }
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
        color: #111827;
        border-radius: 6px;
        padding: 9px 10px;
        font-size: 0.9rem;
    }
    .kk-news-form input[type="email"]::placeholder {
        color: #6b7280;
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

<div class="kk-shell mx-auto px-4 sm:px-6 lg:px-8 py-4 kk-page">
    <section class="kk-hero" aria-label="Kalendar kulture">
        <img src="{{ asset('img/KKLOGOC.png') }}" alt="Logo Kalendara kulture" class="kk-logo">
    </section>

    @if(session('newsletter_status'))
        <div style="margin: 0 0 18px; padding: 12px 14px; border-radius: 8px; background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; font-size: 14px;">
            {{ session('newsletter_status') }}
        </div>
    @endif

    @error('email')
        <div style="margin: 0 0 18px; padding: 12px 14px; border-radius: 8px; background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; font-size: 14px;">
            {{ $message }}
        </div>
    @enderror

    <section class="kk-grid-3">
        <a
            href="{{ route('cultural-calendar.events', ['tip' => 'dogadjaji', 'date' => $today->toDateString()]) }}"
            class="kk-stat-card"
        >
            <div class="kk-stat-label">Danas</div>
            <div class="kk-stat-value">{{ $todayCount }} događaja</div>
        </a>
        <a
            href="{{ route('cultural-calendar.events', ['tip' => 'dogadjaji', 'week_start' => $today->toDateString(), 'week_end' => $weekEnd->toDateString()]) }}"
            class="kk-stat-card"
        >
            <div class="kk-stat-label">Ove sedmice</div>
            <div class="kk-stat-value">{{ $weekCount }} događaja</div>
        </a>
        <a
            href="{{ route('cultural-calendar.events', ['tip' => 'dogadjaji', 'month' => $selectedMonthValue]) }}"
            class="kk-stat-card"
        >
            <div class="kk-stat-label">{{ $calendarMonthLabel }}</div>
            <div class="kk-stat-value">{{ $monthCount }} događaja</div>
        </a>
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
                            @php
                                $isCanonicalEntry = $event instanceof \App\Models\CulturalEventEntry;
                                if ($isCanonicalEntry) {
                                    $cardOcc = $selectedDate
                                        ? $event->occurrenceOnDate($selectedDate->toDateString())
                                        : $event->nextRelevantOccurrence();
                                    $cardDatum = $cardOcc?->datum;
                                    $cardVrijeme = $cardOcc?->vrijeme_od;
                                    $cardLokacija = $cardOcc?->publicLocationDisplayName();
                                    $cardHref = route('cultural-calendar.show', [
                                        'event' => $event,
                                        'back' => request()->getRequestUri(),
                                    ]);
                                } else {
                                    $cardDatum = $event->datum_od;
                                    $cardVrijeme = $event->vrijeme;
                                    $cardLokacija = $event->lokacija;
                                    $cardHref = route('cultural-calendar.show', [
                                        'event' => $event,
                                        'back' => request()->getRequestUri(),
                                    ]);
                                }
                            @endphp
                            <div class="kk-upcoming-item">
                                @if($cardHref)
                                    <a href="{{ $cardHref }}" class="kk-upcoming-link">
                                @else
                                    <div class="kk-upcoming-link">
                                @endif
                                    <div class="kk-upcoming-photo kk-public-status-photo">
                                        <img src="{{ $event->imageUrl() }}" alt="{{ $event->naslov }}">
                                        @include('cultural-calendar.partials.public-status-badge', ['event' => $event, 'variant' => 'card'])
                                    </div>
                                    <div class="kk-upcoming-body">
                                        <div class="kk-upcoming-meta">
                                            {{ optional($cardDatum)->format('d.m.Y') }}
                                            @if($cardVrijeme)
                                                • {{ substr((string) $cardVrijeme, 0, 5) }}
                                            @endif
                                            @if($cardLokacija)
                                                • {{ $cardLokacija }}
                                            @endif
                                        </div>
                                        <div class="kk-upcoming-name">{{ $event->naslov }}</div>
                                    </div>
                                @if($cardHref)
                                    </a>
                                @else
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div class="kk-upcoming-item kk-upcoming-item-empty">
                                <div class="kk-upcoming-name" style="font-weight:500; color:#6b7280;">Nema događaja za odabrani datum.</div>
                            </div>
                        @endforelse
                    </div>
                    <a
                        href="{{ route('cultural-calendar.events', ['tip' => 'dogadjaji', 'date' => $selectedDate->toDateString()]) }}"
                        class="kk-show-all"
                    >Prikaži sve događaje</a>
                @else
                    <div class="kk-upcoming-title">Naredni događaji</div>
                    <div class="kk-upcoming-list">
                        @forelse($upcomingEvents as $event)
                            @php
                                $isCanonicalEntry = $event instanceof \App\Models\CulturalEventEntry;
                                $homepageMode = $isCanonicalEntry
                                    ? (string) ($event->homepage_card_mode ?? 'planned')
                                    : 'planned';
                                $isPostponedInfo = $isCanonicalEntry && $homepageMode === 'postponed_info';
                                $additionalCount = ($isCanonicalEntry && ! $isPostponedInfo)
                                    ? $event->additionalRelevantOccurrencesCount()
                                    : 0;
                                if ($isCanonicalEntry) {
                                    $cardOcc = $isPostponedInfo
                                        ? ($event->relationLoaded('homepageSelectedOccurrence')
                                            ? $event->getRelation('homepageSelectedOccurrence')
                                            : null)
                                        : ($event->relationLoaded('homepageSelectedOccurrence')
                                            ? $event->getRelation('homepageSelectedOccurrence')
                                            : $event->nextRelevantOccurrence());
                                    $cardDatum = $cardOcc?->datum;
                                    $cardVrijeme = $isPostponedInfo ? null : $cardOcc?->vrijeme_od;
                                    $cardLokacija = $isPostponedInfo ? null : $cardOcc?->publicLocationDisplayName();
                                    $cardHref = route('cultural-calendar.show', [
                                        'event' => $event,
                                        'back' => request()->getRequestUri(),
                                    ]);
                                } else {
                                    $cardDatum = $event->datum_od;
                                    $cardVrijeme = $event->vrijeme;
                                    $cardLokacija = $event->lokacija;
                                    $cardHref = route('cultural-calendar.show', [
                                        'event' => $event,
                                        'back' => request()->getRequestUri(),
                                    ]);
                                }
                            @endphp
                            <div class="kk-upcoming-item">
                                @if($cardHref)
                                    <a href="{{ $cardHref }}" class="kk-upcoming-link">
                                @else
                                    <div class="kk-upcoming-link">
                                @endif
                                    <div class="kk-upcoming-photo kk-public-status-photo">
                                        <img src="{{ $event->imageUrl() }}" alt="{{ $event->naslov }}">
                                        @if(! $isPostponedInfo)
                                            @include('cultural-calendar.partials.public-status-badge', ['event' => $event, 'variant' => 'card'])
                                        @endif
                                    </div>
                                    <div class="kk-upcoming-body">
                                        <div class="kk-upcoming-meta">
                                            @if($isPostponedInfo)
                                                <span>Odgođeno</span>
                                                <span>• Prvobitni termin: {{ optional($cardDatum)->format('d.m.Y') }}</span>
                                            @else
                                                {{ optional($cardDatum)->format('d.m.Y') }}
                                                @if($cardVrijeme)
                                                    • {{ substr((string) $cardVrijeme, 0, 5) }}
                                                @endif
                                                @if($cardLokacija)
                                                    • {{ $cardLokacija }}
                                                @endif
                                            @endif
                                        </div>
                                        <div class="kk-upcoming-name">{{ $event->naslov }}</div>
                                        @if($additionalCount > 0)
                                            <div class="kk-upcoming-meta">+ još {{ $additionalCount }} {{ $additionalCount === 1 ? 'termin' : 'termina' }}</div>
                                        @endif
                                    </div>
                                @if($cardHref)
                                    </a>
                                @else
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div class="kk-upcoming-item kk-upcoming-item-empty">
                                <div class="kk-upcoming-name" style="font-weight:500; color:#6b7280;">Nema narednih događaja.</div>
                            </div>
                        @endforelse
                    </div>
                    <a
                        href="{{ route('cultural-calendar.events') }}"
                        class="kk-show-all"
                    >Prikaži sve događaje</a>
                @endif
            </div>
        </article>

        <aside class="kk-card kk-featured-wrap">
            <h2 class="kk-section-title">Istaknuti događaji</h2>
            <div class="kk-featured">
                @if($featuredEvents->isNotEmpty())
                    @foreach($featuredEvents as $event)
                        @php
                            $isCanonicalEntry = $event instanceof \App\Models\CulturalEventEntry;
                            if ($isCanonicalEntry) {
                                $cardOcc = $event->nextRelevantOccurrence();
                                $cardDatum = $cardOcc?->datum;
                                $cardVrijeme = $cardOcc?->vrijeme_od;
                                $cardLokacija = $cardOcc?->publicLocationDisplayName();
                                $cardHref = route('cultural-calendar.show', [
                                    'event' => $event,
                                    'back' => request()->getRequestUri(),
                                ]);
                            } else {
                                $cardDatum = $event->datum_od;
                                $cardVrijeme = $event->vrijeme;
                                $cardLokacija = $event->lokacija;
                                $cardHref = route('cultural-calendar.show', [
                                    'event' => $event,
                                    'back' => request()->getRequestUri(),
                                ]);
                            }
                        @endphp
                        <article class="kk-feature-card">
                            @if($cardHref)
                                <a href="{{ $cardHref }}" style="display:block; color:inherit; text-decoration:none;">
                            @else
                                <div style="display:block; color:inherit;">
                            @endif
                            <div class="kk-public-status-photo">
                                <img
                                    src="{{ $event->imageUrl() }}"
                                    alt="{{ $event->naslov }}"
                                    class="kk-feature-image"
                                >
                                @include('cultural-calendar.partials.public-status-badge', ['event' => $event, 'variant' => 'card'])
                            </div>
                            <div class="kk-feature-content">
                                <div class="kk-feature-meta">
                                    {{ optional($cardDatum)->format('d.m.Y') }}
                                    @if($cardVrijeme)
                                        • {{ substr((string) $cardVrijeme, 0, 5) }}
                                    @endif
                                    @if($cardLokacija)
                                        • {{ $cardLokacija }}
                                    @endif
                                </div>
                                <div class="kk-feature-title">{{ $event->naslov }}</div>
                                <p class="kk-feature-desc">{{ \Illuminate\Support\Str::limit($event->opis ?? '', 120) }}</p>
                            </div>
                            @if($cardHref)
                                </a>
                            @else
                                </div>
                            @endif
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
                            <div class="kk-feature-title">Trenutno nema istaknutih događaja.</div>
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
            <form method="POST" action="{{ route('cultural-calendar.newsletter.store') }}" class="kk-news-form">
                @csrf
                <input
                    type="email"
                    name="email"
                    value="{{ old('email', '') }}"
                    placeholder="email@email.com"
                    required
                >
                <label class="kk-news-check">
                    <span>Odjavi me</span>
                    <input type="checkbox" name="unsubscribe" value="1" @checked(old('unsubscribe'))>
                </label>
                <button type="submit" class="kk-news-btn">Pošalji</button>
            </form>
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
