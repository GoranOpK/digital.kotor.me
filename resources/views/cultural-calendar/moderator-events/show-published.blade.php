@extends('layouts.app')

@section('content')
<div class="kk-shell mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:16px; flex-wrap:wrap;">
        <div>
            <h1 style="font-size:28px; font-weight:700; margin:0; color:#111827;">Objavljen događaj</h1>
            <p class="text-sm text-gray-600 mt-1 mb-0">{{ $activeOrganizer?->naziv }} · {{ $entry->statusLabel() }} · ID {{ $entry->id }}</p>
        </div>
        <a href="{{ route('cultural-moderator-events.index') }}" class="px-3 py-1.5 border border-gray-300 rounded-md">Nazad</a>
    </div>

    @if(session('status'))
        <div class="mb-4 rounded-md bg-green-50 border border-green-200 text-green-800 px-4 py-3">{{ session('status') }}</div>
    @endif

    @if($errors->any())
        <div class="mb-4 rounded-md bg-red-50 border border-red-200 text-red-800 px-4 py-3">
            <ul class="list-disc list-inside">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="bg-white rounded-lg border border-gray-200 p-6 max-w-3xl mb-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Pregled (zaključano)</h2>
        <dl class="grid grid-cols-1 gap-3 text-sm">
            <div>
                <dt class="text-gray-500">Naslov</dt>
                <dd class="font-medium">{{ $entry->naslov ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">Opis</dt>
                <dd class="whitespace-pre-wrap">{{ $entry->opis ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">Kategorija</dt>
                <dd>{{ $entry->category?->naziv ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">Naslovni medij</dt>
                <dd>{{ $entry->coverMedia?->naziv ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">Oznake</dt>
                <dd>
                    @if($entry->tags->isEmpty())
                        —
                    @else
                        {{ $entry->tags->pluck('naziv')->join(', ') }}
                    @endif
                </dd>
            </div>
            <div>
                <dt class="text-gray-500">Status</dt>
                <dd>{{ $entry->statusLabel() }}</dd>
            </div>
        </dl>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 p-6 max-w-3xl mb-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-3">Prijedlog izmjene</h2>
        @if($activeProposal)
            <p class="text-sm text-gray-700 mb-3">
                Aktivan prijedlog: <strong>{{ $activeProposal->statusLabel() }}</strong> · ID {{ $activeProposal->id }}
            </p>
            <a href="{{ route('cultural-moderator-proposals.edit', $activeProposal) }}"
               style="display:inline-block; background:#b91c1c; color:#fff; text-decoration:none; padding:10px 14px; border-radius:8px; font-weight:600;">
                Otvori prijedlog
            </a>
        @else
            <form method="POST" action="{{ route('cultural-moderator-proposals.store', $entry) }}">
                @csrf
                <button type="submit" style="background:#b91c1c; color:#fff; padding:10px 16px; border-radius:8px; font-weight:600; border:0;">
                    Pokreni prijedlog izmjene
                </button>
            </form>
        @endif
    </div>

    <div class="bg-white rounded-lg border border-gray-200 p-6 max-w-3xl">
        <h2 class="text-lg font-semibold mb-3">Održavanja</h2>
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
                    <div class="flex flex-wrap gap-2 items-start">
                        @if($occurrence->isPlanned())
                            <form method="POST" action="{{ route('cultural-moderator-events.occurrences.postpone', [$entry, $occurrence]) }}">
                                @csrf
                                <button type="submit" class="px-3 py-1.5 border border-amber-300 rounded-md text-amber-800 hover:bg-amber-50">
                                    Odgodi
                                </button>
                            </form>
                            <form method="POST" action="{{ route('cultural-moderator-events.occurrences.cancel', [$entry, $occurrence]) }}">
                                @csrf
                                <button type="submit" class="px-3 py-1.5 border border-red-300 rounded-md text-red-700 hover:bg-red-50">
                                    Otkaži
                                </button>
                            </form>
                        @elseif($occurrence->isPostponed())
                            <form method="POST" action="{{ route('cultural-moderator-events.occurrences.resume', [$entry, $occurrence]) }}"
                                  class="w-full border border-gray-200 rounded-md p-3 space-y-2 bg-gray-50">
                                @csrf
                                <p class="text-xs font-semibold text-gray-700 mb-1">Novi termin → Planirano</p>
                                <div class="flex flex-wrap gap-2 items-end">
                                    <div>
                                        <label class="block text-xs text-gray-600" for="datum-{{ $occurrence->id }}">Datum</label>
                                        <input id="datum-{{ $occurrence->id }}" type="date" name="datum" required
                                               value="{{ old('datum', $occurrence->datum?->toDateString()) }}"
                                               class="rounded-md border-gray-300 text-sm">
                                    </div>
                                    <div>
                                        <label class="inline-flex items-center gap-1 text-xs text-gray-600 mt-5">
                                            <input type="checkbox" name="cjelodnevno" value="1"
                                                   @checked(old('cjelodnevno', $occurrence->cjelodnevno))>
                                            Cjelodnevno
                                        </label>
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-600" for="vrijeme_od-{{ $occurrence->id }}">Od</label>
                                        <input id="vrijeme_od-{{ $occurrence->id }}" type="time" name="vrijeme_od"
                                               value="{{ old('vrijeme_od', $occurrence->vrijeme_od ? \Illuminate\Support\Str::substr($occurrence->vrijeme_od, 0, 5) : '') }}"
                                               class="rounded-md border-gray-300 text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-600" for="vrijeme_do-{{ $occurrence->id }}">Do</label>
                                        <input id="vrijeme_do-{{ $occurrence->id }}" type="time" name="vrijeme_do"
                                               value="{{ old('vrijeme_do', $occurrence->vrijeme_do ? \Illuminate\Support\Str::substr($occurrence->vrijeme_do, 0, 5) : '') }}"
                                               class="rounded-md border-gray-300 text-sm">
                                    </div>
                                    <button type="submit" class="px-3 py-1.5 border border-green-600 rounded-md text-green-800 hover:bg-green-50">
                                        Vrati u Planirano
                                    </button>
                                </div>
                            </form>
                            <form method="POST" action="{{ route('cultural-moderator-events.occurrences.cancel', [$entry, $occurrence]) }}">
                                @csrf
                                <button type="submit" class="px-3 py-1.5 border border-red-300 rounded-md text-red-700 hover:bg-red-50">
                                    Otkaži
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
