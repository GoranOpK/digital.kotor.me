@extends('layouts.app')

@section('content')
<div class="kk-shell mx-auto px-4 sm:px-6 lg:px-8 py-6">
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
        .kk-archive-grid {
            display: grid;
            grid-template-columns: repeat(1, minmax(0, 1fr));
            gap: 1.25rem;
        }
        @media (min-width: 768px) {
            .kk-archive-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        .kk-archive-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            overflow: hidden;
            height: 148px;
        }
        .kk-archive-card-link {
            display: flex;
            flex-direction: row;
            align-items: stretch;
            height: 100%;
            color: inherit;
            text-decoration: none;
            transition: background-color 150ms ease;
        }
        .kk-archive-card-link:hover {
            background: #f9fafb;
        }
        .kk-archive-photo {
            flex: 0 0 148px;
            width: 148px;
            height: 148px;
            background: #f3f4f6;
            overflow: hidden;
        }
        .kk-archive-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .kk-archive-body {
            flex: 1 1 auto;
            min-width: 0;
            padding: 12px 14px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            overflow: hidden;
        }
        .kk-archive-meta {
            font-size: 0.75rem;
            color: #6b7280;
            margin-bottom: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .kk-archive-title {
            font-size: 1rem;
            font-weight: 600;
            color: #111827;
            margin: 0 0 4px;
            line-height: 1.3;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .kk-archive-category,
        .kk-archive-location {
            font-size: 0.875rem;
            color: #4b5563;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .kk-archive-desc {
            font-size: 0.875rem;
            color: #374151;
            margin: 6px 0 0;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
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
        <div class="kk-archive-grid">
            @foreach($events as $event)
                <article class="kk-archive-card">
                    <a href="{{ route('cultural-calendar.show', ['event' => $event, 'back' => request()->getRequestUri()]) }}" class="kk-archive-card-link">
                        <div class="kk-archive-photo">
                            <img src="{{ $event->imageUrl() }}" alt="{{ $event->naslov }}">
                        </div>
                        <div class="kk-archive-body">
                            <div class="kk-archive-meta">
                                {{ optional($event->datum_od)->format('d.m.Y') }}
                                @if($event->datum_do)
                                    - {{ optional($event->datum_do)->format('d.m.Y') }}
                                @endif
                                @if($event->vrijeme)
                                    • {{ substr((string)$event->vrijeme, 0, 5) }}
                                @endif
                            </div>
                            <h3 class="kk-archive-title">{{ $event->naslov }}</h3>
                            <div class="kk-archive-category">{{ $event->kategorija }}</div>
                            @if($event->lokacija)
                                <div class="kk-archive-location">{{ $event->lokacija }}</div>
                            @endif
                            @if($event->opis)
                                <p class="kk-archive-desc">{{ \Illuminate\Support\Str::limit($event->opis, 120) }}</p>
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
