@extends('layouts.app')

@section('content')
<div class="kk-shell mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:16px; flex-wrap:wrap;">
        <div>
            <h1 style="font-size:28px; font-weight:700; margin:0; color:#111827;">Otkazan događaj</h1>
            <p class="text-sm text-gray-600 mt-1 mb-0">Status: {{ $entry->statusLabel() }} · ID {{ $entry->id }} · istorijski zapis (read-only)</p>
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
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Pregled (zaključano)</h2>
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
        <h2 class="text-lg font-semibold text-gray-900 mb-3">Održavanja (zaključano)</h2>
        <ul class="text-sm text-gray-800 space-y-2">
            @forelse($entry->occurrences as $occurrence)
                <li>
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
                    · {{ $occurrence->statusLabel() }}
                </li>
            @empty
                <li class="text-gray-500">Nema održavanja.</li>
            @endforelse
        </ul>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 p-6 max-w-3xl">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Razlog otkazivanja</h2>
        <p class="text-sm text-gray-600 mb-3">Jedino polje koje se smije mijenjati dok je događaj Otkazan.</p>
        <form method="POST" action="{{ route('cultural-event-entries.cancellation-reason', $entry) }}" class="space-y-3">
            @csrf
            @method('PUT')
            <div>
                <label for="cancellation_reason" class="block text-sm font-medium text-gray-700 mb-1">Napomena urednika</label>
                <textarea id="cancellation_reason" name="cancellation_reason" rows="4" required class="w-full rounded-md border-gray-300 shadow-sm">{{ old('cancellation_reason', $entry->cancellation_reason) }}</textarea>
            </div>
            <button type="submit" style="background:#b91c1c; color:#fff; padding:10px 16px; border-radius:8px; font-weight:600; border:0;">
                Sačuvaj razlog
            </button>
        </form>
    </div>
</div>
@endsection
