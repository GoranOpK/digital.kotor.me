@extends('layouts.app')

@section('content')
@php
    $filterQuery = request()->query();
    unset($filterQuery['page']);

    $eventsFilterUrl = function (array $except = []) use ($filterQuery) {
        $params = $filterQuery;
        foreach ($except as $key) {
            unset($params[$key]);
        }

        return route('cultural-calendar.events', $params);
    };

    $hasActiveFilters = $date
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
                @if($weekStart && $weekEnd)
                    Događaji za narednu sedmicu
                @elseif($date)
                    Događaji za {{ \Carbon\Carbon::createFromFormat('Y-m-d', $date)->format('d.m.Y') }}
                @else
                    Pretraga i pregled
                @endif
            </h1>
            @if(!empty($selectedMonthLabel))
                <p class="text-sm text-gray-500 mt-1">
                    Izabrani mjesec: {{ $selectedMonthLabel }}
                </p>
            @elseif($weekStart && $weekEnd)
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
        @if($date)
            <input type="hidden" name="date" value="{{ $date }}">
        @endif
        @if($weekStart && $weekEnd)
            <input type="hidden" name="week_start" value="{{ $weekStart->toDateString() }}">
            <input type="hidden" name="week_end" value="{{ $weekEnd->toDateString() }}">
        @endif
        @if(!empty($selectedMonthValue))
            <input type="hidden" name="month" value="{{ $selectedMonthValue }}">
        @endif

        <div class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
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
            @if($date)
                <a
                    href="{{ $eventsFilterUrl(['date']) }}"
                    class="inline-flex items-center gap-2 px-3 py-1.5 text-sm border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50"
                >
                    <span>Datum: {{ \Carbon\Carbon::createFromFormat('Y-m-d', $date)->format('d.m.Y') }}</span>
                    <span aria-hidden="true">×</span>
                    <span class="sr-only">Ukloni filter datuma</span>
                </a>
            @endif
            @if($weekStart && $weekEnd)
                <a
                    href="{{ $eventsFilterUrl(['week_start', 'week_end']) }}"
                    class="inline-flex items-center gap-2 px-3 py-1.5 text-sm border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50"
                >
                    <span>Sedmica: {{ $weekStart->format('d.m.Y') }} - {{ $weekEnd->format('d.m.Y') }}</span>
                    <span aria-hidden="true">×</span>
                    <span class="sr-only">Ukloni filter sedmice</span>
                </a>
            @endif
            @if(!empty($selectedMonthValue))
                <a
                    href="{{ $eventsFilterUrl(['month']) }}"
                    class="inline-flex items-center gap-2 px-3 py-1.5 text-sm border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50"
                >
                    <span>Mjesec: {{ $selectedMonthLabel }}</span>
                    <span aria-hidden="true">×</span>
                    <span class="sr-only">Ukloni filter mjeseca</span>
                </a>
            @endif
            @if($q !== null)
                <a
                    href="{{ $eventsFilterUrl(['q']) }}"
                    class="inline-flex items-center gap-2 px-3 py-1.5 text-sm border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50"
                >
                    <span>Pretraga: {{ $q }}</span>
                    <span aria-hidden="true">×</span>
                    <span class="sr-only">Ukloni tekstualnu pretragu</span>
                </a>
            @endif
            @if($category !== null)
                <a
                    href="{{ $eventsFilterUrl(['category']) }}"
                    class="inline-flex items-center gap-2 px-3 py-1.5 text-sm border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50"
                >
                    <span>Kategorija: {{ $category }}</span>
                    <span aria-hidden="true">×</span>
                    <span class="sr-only">Ukloni filter kategorije</span>
                </a>
            @endif
            @if($location !== null)
                <a
                    href="{{ $eventsFilterUrl(['location']) }}"
                    class="inline-flex items-center gap-2 px-3 py-1.5 text-sm border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50"
                >
                    <span>Lokacija: {{ $location }}</span>
                    <span aria-hidden="true">×</span>
                    <span class="sr-only">Ukloni filter lokacije</span>
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

    @if($events->isEmpty())
        <div class="bg-white border border-gray-200 rounded-lg p-8 text-center text-gray-500">
            Trenutno nema objavljenih događaja.
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach($events as $event)
                <article class="bg-white border border-gray-200 rounded-lg overflow-hidden">
                    <a href="{{ route('cultural-calendar.show', ['event' => $event, 'back' => request()->getRequestUri()]) }}" class="block hover:bg-gray-50 transition-colors duration-150">
                    <img
                        src="{{ $event->imageUrl() }}"
                        alt="{{ $event->naslov }}"
                        class="w-full h-44 object-cover"
                    >
                    <div class="p-4">
                        <div class="text-xs text-gray-500 mb-1">
                            {{ optional($event->datum_od)->format('d.m.Y') }}
                            @if($event->datum_do)
                                - {{ optional($event->datum_do)->format('d.m.Y') }}
                            @endif
                            @if($event->vrijeme)
                                • {{ substr((string)$event->vrijeme, 0, 5) }}
                            @endif
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ $event->naslov }}</h3>
                        <div class="text-sm text-gray-600 mb-2">{{ $event->kategorija }}</div>
                        @if($event->lokacija)
                            <div class="text-sm text-gray-600 mb-2">{{ $event->lokacija }}</div>
                        @endif
                        @if($event->opis)
                            <p class="text-sm text-gray-700">{{ \Illuminate\Support\Str::limit($event->opis, 150) }}</p>
                        @endif
                    </div>
                    </a>
                </article>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $events->links() }}
        </div>
    @endif
</div>
@endsection
