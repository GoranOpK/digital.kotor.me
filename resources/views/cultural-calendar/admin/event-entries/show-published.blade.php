@extends('layouts.app')

@section('content')
<div class="kk-shell mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:16px; flex-wrap:wrap;">
        <div>
            <h1 style="font-size:28px; font-weight:700; margin:0; color:#111827;">Objavljen događaj</h1>
            <p class="text-sm text-gray-600 mt-1 mb-0">Status: {{ $entry->statusLabel() }} · ID {{ $entry->id }}</p>
        </div>
        <a href="{{ route('cultural-event-entries.index') }}" class="px-3 py-1.5 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">Nazad na listu</a>
    </div>

    @if(session('status'))
        <div class="mb-4 rounded-md bg-green-50 border border-green-200 text-green-800 px-4 py-3">
            {{ session('status') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-4 rounded-md bg-red-50 border border-red-200 text-red-800 px-4 py-3">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white rounded-lg border border-gray-200 p-6 max-w-3xl mb-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Pregled</h2>
        <dl class="grid grid-cols-1 gap-3 text-sm">
            <div>
                <dt class="text-gray-500">Naslov</dt>
                <dd class="text-gray-900 font-medium">{{ $entry->naslov ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">Opis</dt>
                <dd class="text-gray-900 whitespace-pre-wrap">{{ $entry->opis ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">Organizator</dt>
                <dd class="text-gray-900">{{ $entry->organizer?->naziv ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">Kategorija</dt>
                <dd class="text-gray-900">{{ $entry->category?->naziv ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">Istaknut</dt>
                <dd class="text-gray-900">{{ $entry->featured ? 'Da' : 'Ne' }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">Oznake</dt>
                <dd class="text-gray-900">
                    @if($entry->tags->isEmpty())
                        —
                    @else
                        {{ $entry->tags->pluck('naziv')->join(', ') }}
                    @endif
                </dd>
            </div>
        </dl>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 p-6 max-w-3xl mb-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Akcije događaja</h2>
        <div class="flex flex-wrap gap-3 mb-6">
            @if($entry->featured)
                <form method="POST" action="{{ route('cultural-event-entries.featured', $entry) }}">
                    @csrf
                    <input type="hidden" name="featured" value="0">
                    <button type="submit" class="px-4 py-2 border border-gray-400 rounded-md text-gray-800 hover:bg-gray-50 font-semibold">
                        Ukloni isticanje
                    </button>
                </form>
            @else
                <form method="POST" action="{{ route('cultural-event-entries.featured', $entry) }}">
                    @csrf
                    <input type="hidden" name="featured" value="1">
                    <button type="submit" class="px-4 py-2 border border-indigo-400 rounded-md text-indigo-900 hover:bg-indigo-50 font-semibold">
                        Istakni
                    </button>
                </form>
            @endif
        </div>

        <form method="POST" action="{{ route('cultural-event-entries.cancel', $entry) }}" class="space-y-3">
            @csrf
            <div>
                <label for="cancellation_reason" class="block text-sm font-medium text-gray-700 mb-1">Razlog otkazivanja (obavezno)</label>
                <textarea id="cancellation_reason" name="cancellation_reason" rows="3" required class="w-full rounded-md border-gray-300 shadow-sm">{{ old('cancellation_reason') }}</textarea>
            </div>
            <button type="submit" class="px-4 py-2 border border-red-600 rounded-md text-red-800 hover:bg-red-50 font-semibold">
                Otkaži događaj
            </button>
        </form>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 p-6 max-w-3xl">
        <h2 class="text-lg font-semibold text-gray-900 mb-3">Održavanja</h2>
        <ul class="space-y-4 text-sm">
            @forelse($entry->occurrences as $occurrence)
                <li class="border border-gray-100 rounded-md p-3">
                    <div class="text-gray-900 mb-2">
                        {{ $occurrence->datum?->format('d.m.Y') }}
                        @if($occurrence->cjelodnevno)
                            · cjelodnevno
                        @elseif($occurrence->vrijeme_od)
                            · {{ \Illuminate\Support\Str::substr($occurrence->vrijeme_od, 0, 5) }}
                            @if($occurrence->vrijeme_do)
                                – {{ \Illuminate\Support\Str::substr($occurrence->vrijeme_do, 0, 5) }}
                            @endif
                        @endif
                        · {{ $occurrence->location?->naziv ?? $occurrence->location_manual_name ?? 'bez lokacije' }}
                        · <strong>{{ $occurrence->statusLabel() }}</strong>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @if($occurrence->isPlanned())
                            <form method="POST" action="{{ route('cultural-event-entries.occurrences.postpone', [$entry, $occurrence]) }}">
                                @csrf
                                <button type="submit" class="px-3 py-1.5 border border-amber-300 rounded-md text-amber-800 hover:bg-amber-50">
                                    Odgodi
                                </button>
                            </form>
                            <form method="POST" action="{{ route('cultural-event-entries.occurrences.cancel', [$entry, $occurrence]) }}">
                                @csrf
                                <button type="submit" class="px-3 py-1.5 border border-red-300 rounded-md text-red-700 hover:bg-red-50">
                                    Otkaži održavanje
                                </button>
                            </form>
                        @elseif($occurrence->isPostponed())
                            <form method="POST" action="{{ route('cultural-event-entries.occurrences.cancel', [$entry, $occurrence]) }}">
                                @csrf
                                <button type="submit" class="px-3 py-1.5 border border-red-300 rounded-md text-red-700 hover:bg-red-50">
                                    Otkaži održavanje
                                </button>
                            </form>
                        @endif
                    </div>
                </li>
            @empty
                <li class="text-gray-500">Nema održavanja.</li>
            @endforelse
        </ul>
    </div>
</div>
@endsection
