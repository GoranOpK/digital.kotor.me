@extends('layouts.app')

@section('content')
<div class="kk-shell mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:16px; flex-wrap:wrap;">
        <div>
            <h1 style="font-size:28px; font-weight:700; margin:0; color:#111827;">Kanonski događaji</h1>
            <p class="text-sm text-gray-600 mt-1 mb-0">Draft UI za novi domen (TS-003/004). Legacy „Događaji“ ostaju odvojeni.</p>
        </div>
        <a href="{{ route('cultural-event-entries.create') }}" style="display:inline-block; background:#b91c1c; color:#fff; text-decoration:none; padding:10px 14px; border-radius:8px; font-weight:600;">
            + Novi nacrt
        </a>
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

    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50">
                    <tr class="text-left text-gray-600">
                        <th class="px-4 py-3">Naslov</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Organizator</th>
                        <th class="px-4 py-3">Kategorija</th>
                        <th class="px-4 py-3">Održavanja</th>
                        <th class="px-4 py-3">Featured</th>
                        <th class="px-4 py-3">Kreiran</th>
                        <th class="px-4 py-3 text-right">Akcije</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($entries as $entry)
                        <tr>
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $entry->naslov ?: '— bez naslova —' }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $entry->statusLabel() }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $entry->organizer?->naziv ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $entry->category?->naziv ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $entry->occurrences_count }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $entry->featured ? 'Da' : 'Ne' }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $entry->created_at?->format('d.m.Y H:i') }}</td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2 flex-wrap items-center">
                                    @if($entry->isDraft())
                                        <a href="{{ route('cultural-event-entries.edit', $entry) }}" class="px-3 py-1.5 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                                            Uredi
                                        </a>
                                        <form method="POST" action="{{ route('cultural-event-entries.submit', $entry) }}">
                                            @csrf
                                            <button type="submit" class="px-3 py-1.5 border border-blue-300 rounded-md text-blue-800 hover:bg-blue-50">
                                                Pošalji na odobrenje
                                            </button>
                                        </form>
                                    @elseif($entry->isPendingApproval())
                                        <a href="{{ route('cultural-event-entries.edit', $entry) }}" class="px-3 py-1.5 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                                            Pregled
                                        </a>
                                        <form method="POST" action="{{ route('cultural-event-entries.approve', $entry) }}">
                                            @csrf
                                            <button type="submit" class="px-3 py-1.5 border border-green-300 rounded-md text-green-800 hover:bg-green-50">
                                                Odobri
                                            </button>
                                        </form>
                                        <a href="{{ route('cultural-event-entries.edit', $entry) }}" class="px-3 py-1.5 border border-amber-300 rounded-md text-amber-800 hover:bg-amber-50">
                                            Vrati na doradu
                                        </a>
                                    @else
                                        <span class="px-3 py-1.5 text-gray-400">{{ $entry->statusLabel() }}</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                                Nema kanonskih događaja.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-gray-100">
            {{ $entries->links() }}
        </div>
    </div>
</div>
@endsection
