@extends('layouts.app')

@section('content')
<div class="kk-shell mx-auto px-4 sm:px-6 lg:px-8 py-6 max-w-4xl">
    <div class="mb-4 flex flex-wrap gap-2 items-center justify-between">
        <div>
            <h1 style="font-size:28px; font-weight:700; margin:0; color:#111827;">{{ $manifestation->naziv }}</h1>
            <p class="text-sm text-gray-600 mt-1">Status: <strong>{{ $manifestation->statusLabel() }}</strong>
                @if($period)
                    · Period: {{ $period['start']->format('d.m.Y') }} – {{ $period['end']->format('d.m.Y') }}
                @endif
            </p>
        </div>
        <a href="{{ route('cultural-manifestations.index') }}" class="px-3 py-1.5 border border-gray-300 rounded-md text-gray-700">Nazad</a>
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
            <form method="POST" action="{{ route('cultural-manifestations.submit', $manifestation) }}">
                @csrf
                <button type="submit" class="px-3 py-1.5 border border-blue-300 rounded-md text-blue-800 hover:bg-blue-50">Pošalji na odobrenje</button>
            </form>
        @endif
        @if($canCancel)
            <form method="POST" action="{{ route('cultural-manifestations.cancel', $manifestation) }}">
                @csrf
                <button type="submit" class="px-3 py-1.5 border border-red-300 rounded-md text-red-700 hover:bg-red-50">Otkaži</button>
            </form>
        @endif
    </div>

    <form method="POST" action="{{ route('cultural-manifestations.update', $manifestation) }}" class="bg-white rounded-lg border border-gray-200 p-6">
        @csrf
        @method('PUT')
        @include('cultural-calendar.admin.manifestations.partials.form', [
            'manifestation' => $manifestation,
            'contentEditable' => $contentEditable,
            'showOrganizerPicker' => true,
            'organizers' => $organizers,
            'mediaItems' => $mediaItems,
        ])
        @if($contentEditable)
            <div class="mt-6">
                <button type="submit" class="px-4 py-2 bg-red-700 text-white rounded-md font-semibold">Sačuvaj</button>
            </div>
        @endif
    </form>

    @include('cultural-calendar.admin.manifestations.partials.events', [
        'manifestation' => $manifestation,
        'linksEditable' => $linksEditable,
        'linkableEvents' => $linkableEvents,
        'moveCandidates' => $moveCandidates,
        'routeLink' => route('cultural-manifestations.events.link', $manifestation),
        'routeUnlink' => route('cultural-manifestations.events.unlink', $manifestation),
        'routeMove' => route('cultural-manifestations.events.move', $manifestation),
    ])
</div>
@endsection
