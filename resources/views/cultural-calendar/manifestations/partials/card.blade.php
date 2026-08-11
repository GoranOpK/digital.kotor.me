@php
    $periodLabel = $manifestationQuery->formatDerivedPeriodLabel(
        $manifestation->getAttribute('derived_period_start'),
        $manifestation->getAttribute('derived_period_end')
    );
    $cardHref = route('cultural-calendar.manifestation', [
        'manifestacija' => $manifestation,
        'back' => request()->getRequestUri(),
    ]);
    $publishedCount = (int) ($manifestation->published_events_count ?? 0);
@endphp
<article class="bg-white border border-gray-200 rounded-lg overflow-hidden">
    <a href="{{ $cardHref }}" class="block hover:bg-gray-50 transition-colors duration-150">
        <div class="kk-public-status-photo relative">
            <img
                src="{{ $manifestation->imageUrl() }}"
                alt="{{ $manifestation->naziv }}"
                class="w-full h-44 object-cover"
            >
            @if($manifestation->isCancelled())
                <span class="kk-mf-badge-cancelled absolute top-2 left-2 inline-block text-xs font-bold text-red-900 bg-red-100 border border-red-300 rounded px-2 py-0.5">
                    Otkazana
                </span>
            @endif
        </div>
        <div class="p-4">
            @if($periodLabel)
                <div class="text-xs text-gray-500 mb-1">{{ $periodLabel }}</div>
            @endif
            <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ $manifestation->naziv }}</h3>
            @if($manifestation->opis)
                <p class="text-sm text-gray-700 mb-2">{{ \Illuminate\Support\Str::limit($manifestation->opis, 150) }}</p>
            @endif
            <div class="text-sm text-gray-600 mb-2">
                {{ $publishedCount }} {{ $publishedCount === 1 ? 'objavljeni događaj' : 'objavljenih događaja' }}
            </div>
            <div class="text-sm font-medium text-gray-800">Detalji manifestacije</div>
        </div>
    </a>
</article>
