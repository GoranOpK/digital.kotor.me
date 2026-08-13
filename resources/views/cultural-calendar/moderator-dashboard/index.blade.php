@extends('layouts.app')

@section('content')
<div class="kk-shell mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:12px; margin-bottom:20px; flex-wrap:wrap;">
        <div>
            <h1 style="font-size:28px; font-weight:700; margin:0; color:#111827;">Kontrolna tabla</h1>
            <p class="text-sm text-gray-600 mt-1 mb-0">
                Organizator: {{ $activeOrganizer->naziv }}.
                Klik otvara postojeće liste sa filterom — bez poslovnih akcija ovdje.
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('cultural-moderator-workspace.index') }}" class="px-3 py-1.5 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">Moderiranje</a>
            <a href="{{ route('cultural-moderator-events.index') }}" class="px-3 py-1.5 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">Događaji</a>
            <a href="{{ route('cultural-moderator-manifestations.index') }}" class="px-3 py-1.5 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">Manifestacije</a>
        </div>
    </div>

    @if($availableOrganizers->count() > 1)
        <form method="POST" action="{{ route('cultural-moderator-context.update') }}" class="mb-4 flex flex-wrap gap-2 items-end">
            @csrf
            <div>
                <label for="switch_org" class="block text-xs text-gray-500 mb-1">Promijeni organizatora</label>
                <select id="switch_org" name="organizer_id" class="rounded-md border-gray-300 text-sm">
                    @foreach($availableOrganizers as $organizer)
                        <option value="{{ $organizer->id }}" @selected((int) $activeOrganizer->id === (int) $organizer->id)>
                            {{ $organizer->naziv }}
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="px-3 py-1.5 border border-gray-300 rounded-md text-sm">Primijeni</button>
        </form>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 max-w-5xl">
        @foreach($cards as $card)
            <a
                href="{{ $card['url'] }}"
                class="block bg-white rounded-lg border border-gray-200 p-5 hover:border-red-300 hover:shadow-sm transition"
            >
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1">{{ $card['id'] }}</p>
                        <h2 class="text-lg font-semibold text-gray-900 mb-1">{{ $card['title'] }}</h2>
                        <p class="text-sm text-gray-600 mb-0">{{ $card['description'] }}</p>
                    </div>
                    <div
                        class="shrink-0 min-w-[3rem] text-center rounded-md px-3 py-2"
                        style="background:#fef2f2; color:#991b1b; font-size:22px; font-weight:700;"
                    >
                        {{ $card['count'] }}
                    </div>
                </div>
            </a>
        @endforeach
    </div>
</div>
@endsection
