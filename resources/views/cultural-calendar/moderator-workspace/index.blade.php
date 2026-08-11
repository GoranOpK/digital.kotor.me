@extends('layouts.app')

@section('content')
<div class="kk-shell mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <h1 style="font-size:28px; font-weight:700; margin:0 0 8px; color:#111827;">Moderatorski workspace</h1>
    <p class="text-sm text-gray-600 mb-4">Pristup na osnovu aktivnog moderatorskog ovlašćenja (PO-ORG-04 / TS-010.1).</p>

    @if(session('status'))
        <div class="mb-4 rounded-md bg-green-50 border border-green-200 text-green-800 px-4 py-3">{{ session('status') }}</div>
    @endif

    @if($isActiveModerator)
        <div class="bg-white rounded-lg border border-gray-200 p-4 mb-6 max-w-3xl">
            <h2 class="text-base font-semibold text-gray-900 mb-2">Aktivni Organizator</h2>
            @if($activeOrganizer)
                <p class="text-sm text-gray-700 mb-3">Trenutni kontekst: <strong>{{ $activeOrganizer->naziv }}</strong></p>
                <a href="{{ route('cultural-moderator-events.index') }}" class="inline-block px-3 py-1.5 border border-blue-300 rounded-md text-blue-800 hover:bg-blue-50 font-semibold">
                    Događaji Organizatora
                </a>
                <a href="{{ route('cultural-moderator-manifestations.index') }}" class="inline-block px-3 py-1.5 border border-blue-300 rounded-md text-blue-800 hover:bg-blue-50 font-semibold ml-2">
                    Manifestacije
                </a>
            @else
                <p class="text-sm text-gray-600 mb-3">Izaberite Organizator za rad (obavezno kada ih imate više).</p>
            @endif

            @if($availableOrganizers->count() > 1 || $activeOrganizer === null)
                <form method="POST" action="{{ route('cultural-moderator-context.update') }}" class="mt-3 flex flex-wrap gap-2 items-end">
                    @csrf
                    <div>
                        <label for="organizer_id" class="block text-xs text-gray-500 mb-1">Organizator</label>
                        <select id="organizer_id" name="organizer_id" required class="rounded-md border-gray-300 text-sm">
                            <option value="">— izaberi —</option>
                            @foreach($availableOrganizers as $organizer)
                                <option value="{{ $organizer->id }}" @selected($activeOrganizer && (int) $activeOrganizer->id === (int) $organizer->id)>
                                    {{ $organizer->naziv }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="px-3 py-1.5 border border-gray-300 rounded-md text-gray-800 hover:bg-gray-50">
                        Postavi kontekst
                    </button>
                </form>
            @endif
        </div>
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
