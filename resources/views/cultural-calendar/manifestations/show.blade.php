@extends('layouts.app')

@section('content')
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
        .kk-mf-program {
            margin-top: 1.5rem;
        }
        .kk-mf-program-day + .kk-mf-program-day {
            margin-top: 1.25rem;
            padding-top: 1.25rem;
            border-top: 1px solid #e5e7eb;
        }
        .kk-mf-program-item + .kk-mf-program-item {
            margin-top: 0.75rem;
            padding-top: 0.75rem;
            border-top: 1px solid #f3f4f6;
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
        <h1 class="text-2xl font-bold text-gray-900">{{ $manifestation->naziv }}</h1>
        <a href="{{ $backUrl }}" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
            Nazad
        </a>
    </div>

    <article class="kk-show-card">
        <div class="kk-show-layout">
            <div class="kk-show-photo">
                <img src="{{ $manifestation->imageUrl() }}" alt="{{ $manifestation->naziv }}">
            </div>
            <div class="kk-show-body">
                @if($manifestation->isCancelled())
                    <p class="kk-show-cancelled-notice" role="status">
                        Ova manifestacija je <strong>Otkazana</strong>.
                    </p>
                @elseif($manifestation->isArchived())
                    <p class="kk-show-meta" role="status">
                        <strong>Status:</strong> Arhivirana
                    </p>
                @endif

                @if($periodLabel)
                    <div class="kk-show-meta">
                        <strong>Period:</strong> {{ $periodLabel }}
                    </div>
                @endif

                @if($manifestation->organizer)
                    <div class="kk-show-meta">
                        <strong>Organizator:</strong> {{ $manifestation->organizer->naziv }}
                    </div>
                @endif

                @if(filled($manifestation->web_stranica))
                    <div class="kk-show-meta">
                        <strong>Web stranica:</strong>
                        <a
                            href="{{ $manifestation->web_stranica }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="text-blue-700 underline"
                        >{{ $manifestation->web_stranica }}</a>
                    </div>
                @endif

                @if($manifestation->opis)
                    <div class="kk-show-desc">{{ $manifestation->opis }}</div>
                @endif
            </div>
        </div>
    </article>

    <section class="kk-mf-program bg-white border border-gray-200 rounded-lg p-4 sm:p-6 mt-6" aria-labelledby="kk-mf-program-heading">
        <h2 id="kk-mf-program-heading" class="text-xl font-bold text-gray-900 mb-4">Program</h2>

        @include('cultural-calendar.manifestations.partials.program', [
            'programByDate' => $programByDate,
        ])
    </section>
</div>
@endsection
