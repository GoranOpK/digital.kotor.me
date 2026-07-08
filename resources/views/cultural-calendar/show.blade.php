@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto p-6">
    <div class="flex items-center justify-between mb-6 gap-3 flex-wrap">
        <h1 class="text-2xl font-bold text-gray-900">{{ $event->naslov }}</h1>
        <a href="{{ $backUrl }}" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
            Nazad
        </a>
    </div>

    <article class="bg-white border border-gray-200 rounded-lg overflow-hidden">
        <img
            src="{{ $event->slika ? asset('storage/' . $event->slika) : asset('img/kalendar-kulture-default-event.png') }}"
            alt="{{ $event->naslov }}"
            class="w-full"
            style="max-height:420px; object-fit:contain; background:#f3f4f6;"
        >

        <div class="p-6">
            <div class="text-sm text-gray-600 mb-3">
                <strong>Datum:</strong>
                {{ optional($event->datum_od)->format('d.m.Y') }}
                @if($event->datum_do)
                    - {{ optional($event->datum_do)->format('d.m.Y') }}
                @endif
            </div>

            @if($event->vrijeme)
                <div class="text-sm text-gray-600 mb-3">
                    <strong>Vrijeme:</strong>
                    {{ substr((string) $event->vrijeme, 0, 5) }}
                    @if($event->vrijeme_do)
                        - {{ substr((string) $event->vrijeme_do, 0, 5) }}
                    @endif
                </div>
            @endif

            <div class="text-sm text-gray-600 mb-3">
                <strong>Kategorija:</strong> {{ $event->kategorija }}
            </div>

            @if($event->lokacija)
                <div class="text-sm text-gray-600 mb-4">
                    <strong>Lokacija:</strong> {{ $event->lokacija }}
                </div>
            @endif

            @if($event->opis)
                <div class="text-gray-800 leading-7 whitespace-pre-line">{{ $event->opis }}</div>
            @else
                <div class="text-gray-500">Opis događaja nije unesen.</div>
            @endif
        </div>
    </article>
</div>
@endsection

