@extends('layouts.app')

@section('content')
<div class="kk-shell mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:16px; flex-wrap:wrap;">
        <div>
            <h1 style="font-size:28px; font-weight:700; margin:0; color:#111827;">Događaji Organizatora</h1>
            <p class="text-sm text-gray-600 mt-1 mb-0">Kontekst: {{ $activeOrganizer->naziv }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('cultural-moderator-workspace.index') }}" class="px-3 py-1.5 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">Workspace</a>
            <a href="{{ route('cultural-moderator-events.create') }}" style="display:inline-block; background:#b91c1c; color:#fff; text-decoration:none; padding:10px 14px; border-radius:8px; font-weight:600;">
                + Novi događaj
            </a>
        </div>
    </div>

    @if(session('status'))
        <div class="mb-4 rounded-md bg-green-50 border border-green-200 text-green-800 px-4 py-3">{{ session('status') }}</div>
    @endif

    @if($availableOrganizers->count() > 1)
        <form method="POST" action="{{ route('cultural-moderator-context.update') }}" class="mb-4 flex flex-wrap gap-2 items-end">
            @csrf
            <div>
                <label for="switch_org" class="block text-xs text-gray-500 mb-1">Promijeni kontekst</label>
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

    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50">
                <tr class="text-left text-gray-600">
                    <th class="px-4 py-3">Naslov</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Kategorija</th>
                    <th class="px-4 py-3">Održavanja</th>
                    <th class="px-4 py-3 text-right">Akcije</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($entries as $entry)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $entry->naslov ?: '— bez naslova —' }}</td>
                        <td class="px-4 py-3">{{ $entry->statusLabel() }}</td>
                        <td class="px-4 py-3">{{ $entry->category?->naziv ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $entry->occurrences_count }}</td>
                        <td class="px-4 py-3 text-right">
                            @if($entry->isDraft() || $entry->isPendingApproval())
                                <a href="{{ route('cultural-moderator-events.edit', $entry) }}" class="px-3 py-1.5 border border-gray-300 rounded-md">
                                    {{ $entry->isDraft() ? 'Uredi' : 'Pregled' }}
                                </a>
                            @else
                                <span class="text-gray-400">{{ $entry->statusLabel() }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-gray-500">Nema događaja za ovaj Organizator.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-4 py-3 border-t border-gray-100">{{ $entries->links() }}</div>
    </div>
</div>
@endsection
