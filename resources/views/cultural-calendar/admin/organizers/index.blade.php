@extends('layouts.app')

@section('content')
<div class="kk-shell mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:16px; flex-wrap:wrap;">
        <h1 style="font-size:28px; font-weight:700; margin:0; color:#111827;">Organizatori</h1>
    </div>

    @if(session('status'))
        <div class="mb-4 rounded-md bg-green-50 border border-green-200 text-green-800 px-4 py-3">{{ session('status') }}</div>
    @endif

    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50">
                    <tr class="text-left text-gray-600">
                        <th class="px-4 py-3">Naziv</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Aktivni moderatori</th>
                        <th class="px-4 py-3 text-right">Akcije</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($organizers as $organizer)
                        <tr>
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $organizer->naziv }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $organizer->statusLabel() }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $organizer->active_moderators_count }}</td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2 flex-wrap">
                                    <a href="{{ route('cultural-organizers.edit', $organizer) }}" class="px-3 py-1.5 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">Uredi</a>
                                    @if($organizer->isActive())
                                        <form method="POST" action="{{ route('cultural-organizers.deactivate', $organizer) }}">
                                            @csrf
                                            <button type="submit" class="px-3 py-1.5 border border-amber-300 rounded-md text-amber-800 hover:bg-amber-50">Deaktiviraj</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-gray-500">Nema Organizatora.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-gray-100">{{ $organizers->links() }}</div>
    </div>
</div>
@endsection
