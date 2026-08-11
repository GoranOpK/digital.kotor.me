@extends('layouts.app')

@section('content')
<div class="kk-shell mx-auto px-4 sm:px-6 lg:px-8 py-6 max-w-4xl">
    <div class="mb-4 flex flex-wrap gap-2 items-center justify-between">
        <div>
            <h1 style="font-size:28px; font-weight:700; margin:0; color:#111827;">{{ $manifestation->naziv }}</h1>
            <p class="text-sm text-gray-600 mt-1">Status: <strong>{{ $manifestation->statusLabel() }}</strong>
                · Organizator: <strong>{{ $activeOrganizer->naziv }}</strong>
                @if($period)
                    · Period: {{ $period['start']->format('d.m.Y') }} – {{ $period['end']->format('d.m.Y') }}
                @endif
            </p>
        </div>
        <a href="{{ route('cultural-moderator-manifestations.index') }}" class="px-3 py-1.5 border border-gray-300 rounded-md">Nazad</a>
    </div>

    @if(session('status'))
        <div class="mb-4 rounded-md bg-green-50 border border-green-200 text-green-800 px-4 py-3">{{ session('status') }}</div>
    @endif
    @if($errors->any())
        <div class="mb-4 rounded-md bg-red-50 border border-red-200 text-red-800 px-4 py-3">
            <ul class="list-disc list-inside">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="mb-4 flex flex-wrap gap-2">
        @if($canSubmit)
            <form method="POST" action="{{ route('cultural-moderator-manifestations.submit', $manifestation) }}">
                @csrf
                <button type="submit" class="px-3 py-1.5 border border-blue-300 rounded-md text-blue-800 hover:bg-blue-50">Pošalji na odobrenje</button>
            </form>
        @endif
        @if($canCancel)
            <form method="POST" action="{{ route('cultural-moderator-manifestations.cancel', $manifestation) }}">
                @csrf
                <button type="submit" class="px-3 py-1.5 border border-red-300 rounded-md text-red-700 hover:bg-red-50">Otkaži</button>
            </form>
        @endif
    </div>

    @if($contentEditable)
        <form method="POST" action="{{ route('cultural-moderator-manifestations.update', $manifestation) }}" class="bg-white rounded-lg border border-gray-200 p-6">
            @csrf
            @method('PUT')
            @include('cultural-calendar.admin.manifestations.partials.form', [
                'manifestation' => $manifestation,
                'contentEditable' => true,
                'showOrganizerPicker' => false,
                'activeOrganizer' => $activeOrganizer,
                'mediaItems' => $mediaItems,
                'organizers' => collect(),
            ])
            <div class="mt-6">
                <button type="submit" class="px-4 py-2 bg-red-700 text-white rounded-md font-semibold">Sačuvaj</button>
            </div>
        </form>
    @else
        <div class="bg-white rounded-lg border border-gray-200 p-6 mb-4 space-y-2 text-sm">
            <p class="text-amber-800 mb-2">Sadržaj Manifestacije je zaključan za Moderatora (Objavljena).</p>
            <p><strong>Opis:</strong> {{ $manifestation->opis ?: '—' }}</p>
            <p><strong>Web:</strong> {{ $manifestation->web_stranica ?: '—' }}</p>
        </div>
    @endif

    @include('cultural-calendar.admin.manifestations.partials.events', [
        'manifestation' => $manifestation,
        'linksEditable' => $linksEditable,
        'linkableEvents' => $linkableEvents,
        'moveCandidates' => $moveCandidates,
        'routeLink' => route('cultural-moderator-manifestations.events.link', $manifestation),
        'routeUnlink' => route('cultural-moderator-manifestations.events.unlink', $manifestation),
        'routeMove' => route('cultural-moderator-manifestations.events.move', $manifestation),
    ])
</div>
@endsection
