@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto p-6">
    <style>
        .kk-top-tabs {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin: 0 0 20px;
            flex-wrap: wrap;
        }
        .kk-top-tab {
            display: inline-block;
            padding: 9px 14px;
            border-radius: 8px;
            border: 1px solid #d1d5db;
            background: #fff;
            color: #374151;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
        }
        .kk-top-tab.active {
            background: #7a0f17;
            border-color: #7a0f17;
            color: #fff;
        }
    </style>

    <div class="kk-top-tabs">
        <a href="{{ route('cultural-calendar.events') }}" class="kk-top-tab">Pregled događaja</a>
        <a href="{{ route('cultural-calendar.archive') }}" class="kk-top-tab active">Arhiva događaja</a>
    </div>

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Arhiva događaja</h1>
        <a href="{{ route('cultural-calendar.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
            Nazad na Kalendar kulture
        </a>
    </div>

    @if($events->isEmpty())
        <div class="bg-white border border-gray-200 rounded-lg p-8 text-center text-gray-500">
            U arhivi trenutno nema događaja.
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach($events as $event)
                <article class="bg-white border border-gray-200 rounded-lg overflow-hidden">
                    <img
                        src="{{ $event->slika ? asset('storage/' . $event->slika) : asset('img/kalendar-kulture-default-event.png') }}"
                        alt="{{ $event->naslov }}"
                        class="w-full"
                        style="height:53px; object-fit:cover;"
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
