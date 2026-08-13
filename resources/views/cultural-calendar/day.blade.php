@extends('layouts.app')

@section('content')
<div class="kk-shell mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Događaji za {{ $selectedDate->format('d.m.Y') }}</h1>
            <p class="text-sm text-gray-500 mt-1">Pregled objavljenih događaja za izabrani datum.</p>
        </div>
        <a href="{{ route('cultural-calendar.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
            Nazad na kalendar
        </a>
    </div>

    @if($events->isEmpty())
        <div class="bg-white border border-gray-200 rounded-lg p-8 text-center text-gray-500">
            Nema događaja za odabrani datum.
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach($events as $event)
                @php
                    $cardOcc = $event->occurrenceOnDate($selectedDate->toDateString());
                    $cardDatum = $cardOcc?->datum;
                    $cardVrijeme = $cardOcc?->vrijeme_od;
                    $cardKategorija = $event->category?->naziv;
                    $cardLokacija = $cardOcc?->publicLocationDisplayName();
                @endphp
                <article class="bg-white border border-gray-200 rounded-lg overflow-hidden">
                    <img src="{{ $event->imageUrl() }}" alt="{{ $event->naslov }}" class="w-full h-44 object-cover">
                    <div class="p-4">
                        <div class="text-xs text-gray-500 mb-1">
                            {{ optional($cardDatum)->format('d.m.Y') }}
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
                        @if($event->opis)
                            <p class="text-sm text-gray-700">{{ \Illuminate\Support\Str::limit($event->opis, 170) }}</p>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</div>
@endsection
