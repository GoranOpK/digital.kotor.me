@extends('layouts.app')

@section('content')
<div class="kk-shell mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <h1 style="font-size:28px; font-weight:700; margin:0 0 16px; color:#111827;">Zahtjevi za kreiranje Organizatora</h1>
    @if(session('status'))
        <div class="mb-4 rounded-md bg-green-50 border border-green-200 text-green-800 px-4 py-3">{{ session('status') }}</div>
    @endif
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50">
                <tr class="text-left text-gray-600">
                    <th class="px-4 py-3">Naziv</th>
                    <th class="px-4 py-3">Podnosilac</th>
                    <th class="px-4 py-3">Predloženi Moderator</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Akcije</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($requests as $item)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $item->proposed_naziv }}</td>
                        <td class="px-4 py-3">{{ $item->submitter?->name }}</td>
                        <td class="px-4 py-3">{{ $item->proposedModerator?->name }}</td>
                        <td class="px-4 py-3">{{ $item->statusLabel() }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('cultural-organizer-creation-requests.show', $item) }}" class="px-3 py-1.5 border border-gray-300 rounded-md">Pregled</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">Nema zahtjeva.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-4 py-3 border-t">{{ $requests->links() }}</div>
    </div>
</div>
@endsection
