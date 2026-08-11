@extends('layouts.app')

@section('content')
<div class="kk-shell mx-auto px-4 sm:px-6 lg:px-8 py-6 max-w-3xl">
    <h1 style="font-size:28px; font-weight:700; margin:0 0 8px; color:#111827;">{{ $manifestation->naziv }}</h1>
    <p class="text-sm text-gray-600 mb-4">Status: <strong>{{ $manifestation->statusLabel() }}</strong> — sadržaj je zaključan.</p>

    @if(session('status'))
        <div class="mb-4 rounded-md bg-green-50 border border-green-200 text-green-800 px-4 py-3">{{ session('status') }}</div>
    @endif
    @if($errors->any())
        <div class="mb-4 rounded-md bg-red-50 border border-red-200 text-red-800 px-4 py-3">
            <ul class="list-disc list-inside">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="bg-white rounded-lg border border-gray-200 p-6 mb-4 space-y-2 text-sm">
        <p><strong>Organizator:</strong> {{ $manifestation->organizer?->naziv ?? '— platformska —' }}</p>
        <p><strong>Opis:</strong> {{ $manifestation->opis ?: '—' }}</p>
        <p><strong>Web:</strong> {{ $manifestation->web_stranica ?: '—' }}</p>
        <p><strong>Događaji:</strong> {{ $manifestation->events->count() }}</p>
        @if($period)
            <p><strong>Period:</strong> {{ $period['start']->format('d.m.Y') }} – {{ $period['end']->format('d.m.Y') }}</p>
        @endif
    </div>

    <div class="flex flex-wrap gap-2">
        <form method="POST" action="{{ route('cultural-manifestations.publish', $manifestation) }}">
            @csrf
            <button type="submit" class="px-4 py-2 border border-green-600 rounded-md text-green-800 hover:bg-green-50 font-semibold">Objavi</button>
        </form>
        <form method="POST" action="{{ route('cultural-manifestations.return', $manifestation) }}">
            @csrf
            <button type="submit" class="px-4 py-2 border border-amber-400 rounded-md text-amber-900 hover:bg-amber-50 font-semibold">Vrati na doradu</button>
        </form>
        <a href="{{ route('cultural-manifestations.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700">Nazad</a>
    </div>
</div>
@endsection
