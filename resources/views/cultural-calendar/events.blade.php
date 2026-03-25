@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto p-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">
                @if($weekStart && $weekEnd)
                    Događaji za narednu sedmicu
                @elseif($date)
                    Događaji za {{ \Carbon\Carbon::createFromFormat('Y-m-d', $date)->format('d.m.Y') }}
                @else
                    Pregled događaja
                @endif
            </h1>
            @if($weekStart && $weekEnd)
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

    @if($events->isEmpty())
        <div class="bg-white border border-gray-200 rounded-lg p-8 text-center text-gray-500">
            Trenutno nema objavljenih događaja.
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach($events as $event)
                <article class="bg-white border border-gray-200 rounded-lg overflow-hidden">
                    <img
                        src="{{ $event->slika ? asset('storage/' . $event->slika) : asset('img/kalendar-kulture-default-event.png') }}"
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
                </article>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $events->links() }}
        </div>
    @endif
</div>
@endsection
