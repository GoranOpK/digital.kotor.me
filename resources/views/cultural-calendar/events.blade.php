@extends('layouts.app')

@section('content')
@php
    use App\Models\CulturalEventEntry;
    use App\Models\CulturalManifestation;
    use App\Services\CulturalCalendar\CulturalPublicSearchHit;
    use App\Services\CulturalCalendar\CulturalPublicSearchQuery;

    $tip = $tip ?? CulturalPublicSearchQuery::TIP_SVE;
    $eventFiltersApplicable = $eventFiltersApplicable ?? ($tip === CulturalPublicSearchQuery::TIP_DOGADJAJI);
    $results = $results ?? $events;
    $manifestationQuery = $manifestationQuery ?? app(\App\Services\CulturalCalendar\CulturalPublicManifestationQuery::class);

    $filterQuery = request()->query();
    unset($filterQuery['page']);

    $eventsFilterUrl = function (array $except = []) use ($filterQuery) {
        $params = $filterQuery;
        foreach ($except as $key) {
            unset($params[$key]);
        }

        return route('cultural-calendar.events', $params);
    };

    $hasActiveFilters = ($tip !== CulturalPublicSearchQuery::TIP_SVE)
        || $date
        || ($weekStart && $weekEnd)
        || ! empty($selectedMonthValue)
        || $q !== null
        || $category !== null
        || $location !== null;
@endphp
<div class="kk-shell mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">
                @if($eventFiltersApplicable && $weekStart && $weekEnd)
                    Događaji za narednu sedmicu
                @elseif($eventFiltersApplicable && $date)
                    Događaji za {{ \Carbon\Carbon::createFromFormat('Y-m-d', $date)->format('d.m.Y') }}
                @else
                    Pretraga i pregled
                @endif
            </h1>
            @if($eventFiltersApplicable && !empty($selectedMonthLabel))
                <p class="text-sm text-gray-500 mt-1">
                    Izabrani mjesec: {{ $selectedMonthLabel }}
                </p>
            @elseif($eventFiltersApplicable && $weekStart && $weekEnd)
                <p class="text-sm text-gray-500 mt-1">
                    Period: {{ $weekStart->format('d.m.Y') }} - {{ $weekEnd->format('d.m.Y') }}.
                    Prije dolaska na događaj provjerite eventualne izmjene termina, otkazivanja ili nova dešavanja.
                </p>
            @endif
        </div>
        <a href="{{ route('cultural-calendar.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
            Nazad na Kalendar kulture
        </a>
    </div>

    <form
        method="GET"
        action="{{ route('cultural-calendar.events') }}"
        class="mb-5 bg-white border border-gray-200 rounded-lg p-4"
        role="search"
        aria-label="Filteri pretrage i pregleda"
    >
        @if($eventFiltersApplicable && $date)
            <input type="hidden" name="date" value="{{ $date }}">
        @endif
        @if($eventFiltersApplicable && $weekStart && $weekEnd)
            <input type="hidden" name="week_start" value="{{ $weekStart->toDateString() }}">
            <input type="hidden" name="week_end" value="{{ $weekEnd->toDateString() }}">
        @endif
        @if($eventFiltersApplicable && !empty($selectedMonthValue))
            <input type="hidden" name="month" value="{{ $selectedMonthValue }}">
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 {{ $eventFiltersApplicable ? 'lg:grid-cols-5' : 'lg:grid-cols-3' }} gap-3 items-end">
            <div>
                <label for="kk-filter-tip" class="block text-sm font-medium text-gray-700 mb-1">Tip sadržaja</label>
                <select
                    id="kk-filter-tip"
                    name="tip"
                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-900 bg-white"
                >
                    <option value="" @selected($tip === CulturalPublicSearchQuery::TIP_SVE)>Sve</option>
                    <option value="dogadjaji" @selected($tip === CulturalPublicSearchQuery::TIP_DOGADJAJI)>Događaji</option>
                    <option value="manifestacije" @selected($tip === CulturalPublicSearchQuery::TIP_MANIFESTACIJE)>Manifestacije</option>
                </select>
            </div>
            <div>
                <label for="kk-filter-q" class="block text-sm font-medium text-gray-700 mb-1">Pretraga</label>
                <input
                    id="kk-filter-q"
                    type="search"
                    name="q"
                    value="{{ $q ?? '' }}"
                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-900"
                    autocomplete="off"
                >
            </div>
            @if($eventFiltersApplicable)
                <div>
                    <label for="kk-filter-category" class="block text-sm font-medium text-gray-700 mb-1">Kategorija</label>
                    <select
                        id="kk-filter-category"
                        name="category"
                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-900 bg-white"
                    >
                        <option value="">Sve kategorije</option>
                        @foreach($categoryOptions as $option)
                            <option value="{{ $option }}" @selected($category === $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="kk-filter-location" class="block text-sm font-medium text-gray-700 mb-1">Lokacija</label>
                    <select
                        id="kk-filter-location"
                        name="location"
                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-900 bg-white"
                    >
                        <option value="">Sve lokacije</option>
                        @foreach($locationOptions as $option)
                            <option value="{{ $option }}" @selected($location === $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div>
                <button
                    type="submit"
                    class="w-full px-4 py-2 border border-gray-300 rounded-md text-sm font-semibold text-gray-700 hover:bg-gray-50"
                >
                    Pretraži
                </button>
            </div>
        </div>
    </form>

    @if($hasActiveFilters)
        <div class="mb-5 flex flex-wrap items-center gap-2" aria-label="Aktivni filteri">
            @if($tip !== CulturalPublicSearchQuery::TIP_SVE)
                <a
                    href="{{ $eventsFilterUrl(['tip']) }}"
                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full bg-gray-100 text-sm text-gray-800 hover:bg-gray-200"
                >
                    <span>Tip: {{ $tip === CulturalPublicSearchQuery::TIP_DOGADJAJI ? 'Događaji' : 'Manifestacije' }}</span>
                    <span aria-hidden="true">×</span>
                </a>
            @endif

            @if($eventFiltersApplicable && $date)
                <a
                    href="{{ $eventsFilterUrl(['date']) }}"
                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full bg-gray-100 text-sm text-gray-800 hover:bg-gray-200"
                >
                    <span>Datum: {{ \Carbon\Carbon::createFromFormat('Y-m-d', $date)->format('d.m.Y') }}</span>
                    <span aria-hidden="true">×</span>
                </a>
            @endif

            @if($eventFiltersApplicable && $weekStart && $weekEnd)
                <a
                    href="{{ $eventsFilterUrl(['week_start', 'week_end']) }}"
                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full bg-gray-100 text-sm text-gray-800 hover:bg-gray-200"
                >
                    <span>Sedmica: {{ $weekStart->format('d.m.Y') }} - {{ $weekEnd->format('d.m.Y') }}</span>
                    <span aria-hidden="true">×</span>
                </a>
            @endif

            @if($eventFiltersApplicable && !empty($selectedMonthValue))
                <a
                    href="{{ $eventsFilterUrl(['month']) }}"
                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full bg-gray-100 text-sm text-gray-800 hover:bg-gray-200"
                >
                    <span>Mjesec: {{ $selectedMonthLabel }}</span>
                    <span aria-hidden="true">×</span>
                </a>
            @endif

            @if($q !== null)
                <a
                    href="{{ $eventsFilterUrl(['q']) }}"
                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full bg-gray-100 text-sm text-gray-800 hover:bg-gray-200"
                >
                    <span>Pretraga: {{ $q }}</span>
                    <span aria-hidden="true">×</span>
                </a>
            @endif

            @if($eventFiltersApplicable && $category !== null)
                <a
                    href="{{ $eventsFilterUrl(['category']) }}"
                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full bg-gray-100 text-sm text-gray-800 hover:bg-gray-200"
                >
                    <span>Kategorija: {{ $category }}</span>
                    <span aria-hidden="true">×</span>
                </a>
            @endif

            @if($eventFiltersApplicable && $location !== null)
                <a
                    href="{{ $eventsFilterUrl(['location']) }}"
                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full bg-gray-100 text-sm text-gray-800 hover:bg-gray-200"
                >
                    <span>Lokacija: {{ $location }}</span>
                    <span aria-hidden="true">×</span>
                </a>
            @endif

            <a
                href="{{ route('cultural-calendar.events') }}"
                class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-gray-700 underline hover:text-gray-900"
            >
                Poništi sve filtere
            </a>
        </div>
    @endif

    @if($results->isEmpty())
        <div class="bg-white border border-gray-200 rounded-lg p-8 text-center text-gray-500">
            @if($tip === CulturalPublicSearchQuery::TIP_MANIFESTACIJE)
                Trenutno nema objavljenih manifestacija.
            @elseif($tip === CulturalPublicSearchQuery::TIP_SVE)
                Trenutno nema rezultata.
            @else
                Trenutno nema objavljenih događaja.
            @endif
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach($results as $result)
                @php
                    $hit = $result instanceof CulturalPublicSearchHit ? $result : null;
                    $item = $hit?->model ?? $result;
                @endphp

                @if($item instanceof CulturalManifestation)
                    @include('cultural-calendar.manifestations.partials.card', [
                        'manifestation' => $item,
                        'manifestationQuery' => $manifestationQuery,
                    ])
                @elseif($item instanceof CulturalEventEntry || $item instanceof \App\Models\CulturalEvent)
                    @php
                        $event = $item;
                        $isCanonicalEntry = $event instanceof CulturalEventEntry;
                        if ($isCanonicalEntry) {
                            $cardOcc = $event->nextRelevantOccurrence();
                            $cardDatumOd = $cardOcc?->datum;
                            $cardDatumDo = null;
                            $cardVrijeme = $cardOcc?->vrijeme_od;
                            $cardLokacija = $cardOcc?->publicLocationDisplayName();
                            $cardKategorija = $event->publicCategoryName();
                            $additionalCount = $event->additionalRelevantOccurrencesCount();
                            $cardHref = route('cultural-calendar.show', [
                                'event' => $event,
                                'back' => request()->getRequestUri(),
                            ]);
                        } else {
                            $cardDatumOd = $event->datum_od;
                            $cardDatumDo = $event->datum_do;
                            $cardVrijeme = $event->vrijeme;
                            $cardLokacija = $event->lokacija;
                            $cardKategorija = $event->kategorija;
                            $additionalCount = 0;
                            $cardHref = route('cultural-calendar.show', [
                                'event' => $event,
                                'back' => request()->getRequestUri(),
                            ]);
                        }
                    @endphp
                    <article class="bg-white border border-gray-200 rounded-lg overflow-hidden">
                        @if($cardHref)
                            <a href="{{ $cardHref }}" class="block hover:bg-gray-50 transition-colors duration-150">
                        @else
                            <div class="block">
                        @endif
                        <div class="kk-public-status-photo">
                            <img
                                src="{{ $event->imageUrl() }}"
                                alt="{{ $event->naslov }}"
                                class="w-full h-44 object-cover"
                            >
                            @include('cultural-calendar.partials.public-status-badge', ['event' => $event, 'variant' => 'card'])
                        </div>
                        <div class="p-4">
                            <div class="text-xs text-gray-500 mb-1">
                                {{ optional($cardDatumOd)->format('d.m.Y') }}
                                @if($cardDatumDo)
                                    - {{ optional($cardDatumDo)->format('d.m.Y') }}
                                @endif
                                @if($cardVrijeme)
                                    • {{ substr((string) $cardVrijeme, 0, 5) }}
                                @endif
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ $event->naslov }}</h3>
                            @if($cardKategorija)
                                <div class="text-sm text-gray-600 mb-2">{{ $cardKategorija }}</div>
                            @endif
                            @if($cardLokacija)
                                <div class="text-sm text-gray-600 mb-2">{{ $cardLokacija }}</div>
                            @endif
                            @if($additionalCount > 0)
                                <div class="text-sm text-gray-600 mb-2">+ još {{ $additionalCount }} {{ $additionalCount === 1 ? 'termin' : 'termina' }}</div>
                            @endif
                            @if($event->opis)
                                <p class="text-sm text-gray-700">{{ \Illuminate\Support\Str::limit($event->opis, 150) }}</p>
                            @endif
                        </div>
                        @if($cardHref)
                            </a>
                        @else
                            </div>
                        @endif
                    </article>
                @endif
            @endforeach
        </div>

        <div class="mt-6">
            {{ $results->links() }}
        </div>
    @endif
</div>
@endsection
