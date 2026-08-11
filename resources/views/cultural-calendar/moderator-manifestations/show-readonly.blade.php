@extends('layouts.app')

@section('content')
<div class="kk-shell mx-auto px-4 sm:px-6 lg:px-8 py-6 max-w-3xl">
    <h1 style="font-size:28px; font-weight:700; margin:0 0 8px; color:#111827;">{{ $manifestation->naziv }}</h1>
    <p class="text-sm text-gray-600 mb-4">Status: <strong>{{ $manifestation->statusLabel() }}</strong> — samo pregled.</p>

    <div class="bg-white rounded-lg border border-gray-200 p-6 mb-4 space-y-2 text-sm">
        <p><strong>Organizator:</strong> {{ $activeOrganizer->naziv }}</p>
        <p><strong>Opis:</strong> {{ $manifestation->opis ?: '—' }}</p>
        <p><strong>Web:</strong> {{ $manifestation->web_stranica ?: '—' }}</p>
        <p><strong>Događaji:</strong> {{ $manifestation->events->count() }}</p>
        @if($period)
            <p><strong>Period:</strong> {{ $period['start']->format('d.m.Y') }} – {{ $period['end']->format('d.m.Y') }}</p>
        @endif
    </div>

    <a href="{{ route('cultural-moderator-manifestations.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700">Nazad</a>
</div>
@endsection
