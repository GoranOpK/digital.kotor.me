@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto p-6">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Kalendar kulture - Događaji</h1>
        <a href="{{ route('cultural-events.create') }}" class="px-4 py-2 bg-red-700 text-white rounded-md hover:bg-red-800">
            + Novi događaj
        </a>
    </div>

    @if(session('status'))
        <div class="mb-4 rounded-md bg-green-50 border border-green-200 text-green-800 px-4 py-3">
            {{ session('status') }}
        </div>
    @endif

    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50">
                    <tr class="text-left text-gray-600">
                        <th class="px-4 py-3">Naziv</th>
                        <th class="px-4 py-3">Kategorija</th>
                        <th class="px-4 py-3">Datum</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Istaknuto</th>
                        <th class="px-4 py-3 text-right">Akcije</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($events as $event)
                        <tr>
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $event->naslov }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $event->kategorija }}</td>
                            <td class="px-4 py-3 text-gray-700">
                                {{ optional($event->datum_od)->format('d.m.Y') }}
                                @if($event->datum_do)
                                    - {{ optional($event->datum_do)->format('d.m.Y') }}
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-700">{{ $event->status }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $event->featured ? 'Da' : 'Ne' }}</td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('cultural-events.edit', $event) }}" class="px-3 py-1.5 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                                        Uredi
                                    </a>
                                    <form method="POST" action="{{ route('cultural-events.destroy', $event) }}" onsubmit="return confirm('Da li ste sigurni da želite obrisati događaj?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="px-3 py-1.5 border border-red-300 rounded-md text-red-700 hover:bg-red-50">
                                            Obriši
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                                Nema unesenih događaja.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-gray-100">
            {{ $events->links() }}
        </div>
    </div>
</div>
@endsection
