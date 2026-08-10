@extends('layouts.app')

@section('content')
@php
    $isCanonicalEntry = $event instanceof \App\Models\CulturalEventEntry;
@endphp
<div class="kk-shell mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <style>
        .kk-show-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 1.5rem;
        }
        .kk-show-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            overflow: hidden;
        }
        .kk-show-layout {
            display: grid;
            grid-template-columns: minmax(0, 0.42fr) minmax(0, 0.58fr);
            align-items: stretch;
            min-height: 320px;
        }
        .kk-show-photo {
            background: #f3f4f6;
            min-height: 280px;
            aspect-ratio: 1 / 1;
            max-height: 520px;
        }
        .kk-show-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            display: block;
        }
        .kk-show-body {
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
        }
        .kk-show-meta {
            font-size: 0.875rem;
            color: #4b5563;
            margin-bottom: 0.75rem;
        }
        .kk-show-meta strong {
            color: #111827;
        }
        .kk-show-desc {
            color: #1f2937;
            line-height: 1.75;
            white-space: pre-line;
            margin-top: 0.5rem;
        }
        .kk-show-desc-empty {
            color: #6b7280;
            margin-top: 0.5rem;
        }
        .kk-show-cancelled-notice {
            margin: 0 0 0.75rem;
            padding: 0.75rem 1rem;
            border: 1px solid #fca5a5;
            border-radius: 0.375rem;
            background: #fef2f2;
            color: #991b1b;
            font-size: 0.9375rem;
            line-height: 1.5;
        }
        .kk-show-occurrence + .kk-show-occurrence {
            margin-top: 0.75rem;
            padding-top: 0.75rem;
            border-top: 1px solid #e5e7eb;
        }
        .kk-show-occ-status {
            display: inline-block;
            margin-left: 0.5rem;
            font-size: 0.75rem;
            font-weight: 700;
            color: #7f1d1d;
            background: #fde8e8;
            border: 1px solid #f5b5b5;
            border-radius: 0.25rem;
            padding: 0.1rem 0.4rem;
            vertical-align: middle;
        }
        .kk-show-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 0.4rem 0.5rem;
            margin-top: 0.35rem;
        }
        .kk-show-tag {
            display: inline-block;
            max-width: 100%;
            box-sizing: border-box;
            font-size: 0.75rem;
            font-weight: 500;
            line-height: 1.25;
            color: #4b5563;
            background: #f3f4f6;
            border: 1px solid #e5e7eb;
            border-radius: 9999px;
            padding: 0.2rem 0.65rem;
            white-space: normal;
            overflow-wrap: anywhere;
            word-break: break-word;
            cursor: default;
            pointer-events: none;
            user-select: text;
        }
        @media (max-width: 768px) {
            .kk-show-layout {
                grid-template-columns: 1fr;
                min-height: 0;
            }
            .kk-show-photo {
                aspect-ratio: 16 / 10;
                max-height: 280px;
                min-height: 180px;
            }
        }
    </style>

    <div class="kk-show-header">
        <h1 class="text-2xl font-bold text-gray-900">{{ $event->naslov }}</h1>
        <a href="{{ $backUrl }}" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
            Nazad
        </a>
    </div>

    <article class="kk-show-card">
        <div class="kk-show-layout">
            <div class="kk-show-photo">
                <img src="{{ $event->imageUrl() }}" alt="{{ $event->naslov }}">
            </div>
            <div class="kk-show-body">
                @include('cultural-calendar.partials.public-status-badge', ['event' => $event, 'variant' => 'detail'])

                @if($event->status === 'cancelled'
                    || ($isCanonicalEntry
                        && $event->status === \App\Models\CulturalEventEntry::STATUS_ARCHIVED
                        && $event->archived_from_status === \App\Models\CulturalEventEntry::STATUS_CANCELLED))
                    <p class="kk-show-cancelled-notice" role="status">
                        Ovaj događaj je otkazan i neće biti održan u planiranom terminu.
                    </p>
                    @if($isCanonicalEntry && ($cancelNotice = $event->publicCancellationNotice()))
                        <p class="kk-show-cancelled-notice" role="note">
                            <strong>Napomena:</strong> {{ $cancelNotice }}
                        </p>
                    @endif
                @endif

                @if($isCanonicalEntry)
                    @if($organizerName = $event->publicOrganizerDisplayName())
                        <div class="kk-show-meta">
                            <strong>Organizator:</strong> {{ $organizerName }}
                        </div>
                    @endif

                    @include('cultural-calendar.partials.show-occurrences', [
                        'occurrences' => $event->publicDetailOccurrences(),
                    ])

                    @if($event->publicCategoryName())
                        <div class="kk-show-meta">
                            <strong>Kategorija:</strong> {{ $event->publicCategoryName() }}
                        </div>
                    @endif

                    @if($event->tags->isNotEmpty())
                        <div class="kk-show-meta">
                            <strong>Oznake:</strong>
                            <div class="kk-show-tags">
                                @foreach($event->tags as $tag)
                                    <span class="kk-show-tag">{{ $tag->naziv }}</span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @else
                    <div class="kk-show-meta">
                        <strong>Datum:</strong>
                        {{ optional($event->datum_od)->format('d.m.Y') }}
                        @if($event->datum_do)
                            - {{ optional($event->datum_do)->format('d.m.Y') }}
                        @endif
                    </div>

                    @if($event->vrijeme)
                        <div class="kk-show-meta">
                            <strong>Vrijeme:</strong>
                            {{ substr((string) $event->vrijeme, 0, 5) }}
                            @if($event->vrijeme_do)
                                - {{ substr((string) $event->vrijeme_do, 0, 5) }}
                            @endif
                        </div>
                    @endif

                    <div class="kk-show-meta">
                        <strong>Kategorija:</strong> {{ $event->kategorija }}
                    </div>

                    @if($event->lokacija)
                        <div class="kk-show-meta">
                            <strong>Lokacija:</strong> {{ $event->lokacija }}
                        </div>
                    @endif
                @endif

                @if($event->opis)
                    <div class="kk-show-desc">{{ $event->opis }}</div>
                @else
                    <div class="kk-show-desc-empty">Opis događaja nije unesen.</div>
                @endif
            </div>
        </div>
    </article>
</div>
@endsection
