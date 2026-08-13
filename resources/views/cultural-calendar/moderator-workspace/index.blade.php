@extends('layouts.app')

@section('content')
<div class="kk-shell mx-auto px-4 sm:px-6 lg:px-8 py-6">
    @if($isActiveModerator && $activeOrganizer)
        <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:12px; margin-bottom:20px; flex-wrap:wrap;">
            <div>
                <h1 style="font-size:28px; font-weight:700; margin:0; color:#111827;">Moderiranje</h1>
                <p class="text-sm text-gray-600 mt-1 mb-0" data-kk-nav="active-organizer">
                    Organizator: {{ $activeOrganizer->naziv }}
                </p>
            </div>
            @if($availableOrganizers->count() > 1)
                <a
                    href="#promijeni-organizatora"
                    data-kk-nav="promijeni-organizatora"
                    class="px-3 py-1.5 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50"
                >Promijeni organizatora</a>
            @endif
        </div>

        @if(session('status'))
            <div class="mb-4 rounded-md bg-green-50 border border-green-200 text-green-800 px-4 py-3">{{ session('status') }}</div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 max-w-5xl mb-8">
            <a
                href="{{ route('cultural-moderator-events.index') }}"
                data-kk-nav="mod-events"
                class="block bg-white rounded-lg border border-gray-200 p-5 hover:border-red-300 hover:shadow-sm transition"
            >
                <h2 class="text-lg font-semibold text-gray-900 mb-1">Događaji organizatora</h2>
                <p class="text-sm text-gray-600 mb-0">Pregled i rad sa događajima aktivnog Organizatora.</p>
            </a>
            <a
                href="{{ route('cultural-moderator-manifestations.index') }}"
                data-kk-nav="mod-manifestations"
                class="block bg-white rounded-lg border border-gray-200 p-5 hover:border-red-300 hover:shadow-sm transition"
            >
                <h2 class="text-lg font-semibold text-gray-900 mb-1">Manifestacije organizatora</h2>
                <p class="text-sm text-gray-600 mb-0">Pregled i rad sa manifestacijama aktivnog Organizatora.</p>
            </a>
        </div>

        @if($availableOrganizers->count() > 1)
            <div id="promijeni-organizatora" class="bg-white rounded-lg border border-gray-200 p-4 mb-6 max-w-3xl">
                <h2 class="text-base font-semibold text-gray-900 mb-2">Promijeni organizatora</h2>
                <form method="POST" action="{{ route('cultural-moderator-context.update') }}" class="mt-1 flex flex-wrap gap-2 items-end">
                    @csrf
                    <div>
                        <label for="organizer_id" class="block text-xs text-gray-500 mb-1">Organizator</label>
                        <select id="organizer_id" name="organizer_id" required class="rounded-md border-gray-300 text-sm">
                            @foreach($availableOrganizers as $organizer)
                                <option value="{{ $organizer->id }}" @selected((int) $activeOrganizer->id === (int) $organizer->id)>
                                    {{ $organizer->naziv }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="px-3 py-1.5 border border-gray-300 rounded-md text-gray-800 hover:bg-gray-50">
                        Primijeni
                    </button>
                </form>
            </div>
        @endif
    @else
        <h1 style="font-size:28px; font-weight:700; margin:0 0 8px; color:#111827;">Izbor organizatora</h1>
        <p class="text-sm text-gray-600 mb-4">Izaberite Organizatora za koji radite kao Moderator.</p>

        @if(session('status'))
            <div class="mb-4 rounded-md bg-green-50 border border-green-200 text-green-800 px-4 py-3">{{ session('status') }}</div>
        @endif

        @if($isActiveModerator)
            <div class="bg-white rounded-lg border border-gray-200 p-4 mb-6 max-w-3xl">
                <form method="POST" action="{{ route('cultural-moderator-context.update') }}" class="flex flex-wrap gap-2 items-end">
                    @csrf
                    <div>
                        <label for="organizer_id" class="block text-xs text-gray-500 mb-1">Organizator</label>
                        <select id="organizer_id" name="organizer_id" required class="rounded-md border-gray-300 text-sm">
                            <option value="">— izaberi —</option>
                            @foreach($availableOrganizers as $organizer)
                                <option value="{{ $organizer->id }}">{{ $organizer->naziv }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="px-3 py-1.5 border border-gray-300 rounded-md text-gray-800 hover:bg-gray-50">
                        Nastavi
                    </button>
                </form>
            </div>
        @endif
    @endif

    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50">
                <tr class="text-left text-gray-600">
                    <th class="px-4 py-3">Organizator</th>
                    <th class="px-4 py-3 text-right">Akcije</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($authorizations as $auth)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $auth->organizer->naziv }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('cultural-moderator-requests.create', $auth->organizer) }}" class="px-3 py-1.5 border border-gray-300 rounded-md">Zahtjev za Moderatora</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2" class="px-4 py-8 text-center text-gray-500">
                            @if($isEditor)
                                Nemate aktivno moderatorsko ovlašćenje; pristupate kao Urednik.
                            @else
                                Nemate aktivno ovlašćenje nad aktivnim Organizatorom.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
