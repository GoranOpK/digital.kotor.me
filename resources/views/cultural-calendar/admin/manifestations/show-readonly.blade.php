@extends('layouts.app')

@section('content')
<div class="kk-shell mx-auto px-4 sm:px-6 lg:px-8 py-6 max-w-3xl">
    <h1 style="font-size:28px; font-weight:700; margin:0 0 8px; color:#111827;">{{ $manifestation->naziv }}</h1>
    <p class="text-sm text-gray-600 mb-4">Status: <strong>{{ $manifestation->statusLabel() }}</strong> — samo pregled.</p>

    <div class="bg-white rounded-lg border border-gray-200 p-6 mb-4 space-y-2 text-sm">
        <p><strong>Organizator:</strong> {{ $manifestation->organizer?->naziv ?? '— platformska —' }}</p>
        <p><strong>Opis:</strong> {{ $manifestation->opis ?: '—' }}</p>
        <p><strong>Web:</strong> {{ $manifestation->web_stranica ?: '—' }}</p>
        <p><strong>Događaji:</strong> {{ $manifestation->events->count() }}</p>
        @if($period)
            <p><strong>Period:</strong> {{ $period['start']->format('d.m.Y') }} – {{ $period['end']->format('d.m.Y') }}</p>
        @endif
    </div>

    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden mb-4">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50"><tr class="text-left text-gray-600"><th class="px-4 py-2">Naslov</th><th class="px-4 py-2">Status</th><th class="px-4 py-2">Organizator</th></tr></thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($manifestation->events as $event)
                    <tr>
                        <td class="px-4 py-2">{{ $event->naslov ?: '—' }}</td>
                        <td class="px-4 py-2">{{ $event->statusLabel() }}</td>
                        <td class="px-4 py-2">{{ $event->organizer?->naziv ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <a href="{{ route('cultural-manifestations.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700">Nazad</a>
</div>
@endsection
