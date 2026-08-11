@extends('layouts.app')

@section('content')
<div class="kk-shell mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:16px; flex-wrap:wrap;">
        <div>
            <h1 style="font-size:28px; font-weight:700; margin:0; color:#111827;">Manifestacije</h1>
            <p class="text-sm text-gray-600 mt-1">Organizator: <strong>{{ $activeOrganizer->naziv }}</strong></p>
        </div>
        <a href="{{ route('cultural-moderator-manifestations.create') }}" style="display:inline-block; background:#b91c1c; color:#fff; text-decoration:none; padding:10px 14px; border-radius:8px; font-weight:600;">
            + Nova manifestacija
        </a>
    </div>

    @if($availableOrganizers->count() > 1)
        <form method="POST" action="{{ route('cultural-moderator-context.update') }}" class="mb-4 flex flex-wrap gap-2 items-end">
            @csrf
            <select name="organizer_id" class="rounded-md border-gray-300 text-sm">
                @foreach($availableOrganizers as $organizer)
                    <option value="{{ $organizer->id }}" @selected((int) $activeOrganizer->id === (int) $organizer->id)>{{ $organizer->naziv }}</option>
                @endforeach
            </select>
            <button type="submit" class="px-3 py-1.5 border border-gray-300 rounded-md text-sm">Promijeni kontekst</button>
        </form>
    @endif

    @if(session('status'))
        <div class="mb-4 rounded-md bg-green-50 border border-green-200 text-green-800 px-4 py-3">{{ session('status') }}</div>
    @endif
    @if($errors->any())
        <div class="mb-4 rounded-md bg-red-50 border border-red-200 text-red-800 px-4 py-3">
            <ul class="list-disc list-inside">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50">
                <tr class="text-left text-gray-600">
                    <th class="px-4 py-3">Naziv</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Period</th>
                    <th class="px-4 py-3">Događaji</th>
                    <th class="px-4 py-3 text-right">Akcije</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($manifestations as $manifestation)
                    @php $period = $periods[$manifestation->id] ?? null; @endphp
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $manifestation->naziv }}</td>
                        <td class="px-4 py-3">{{ $manifestation->statusLabel() }}</td>
                        <td class="px-4 py-3">
                            @if($period)
                                {{ $period['start']->format('d.m.Y') }} – {{ $period['end']->format('d.m.Y') }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $manifestation->events_count }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('cultural-moderator-manifestations.edit', $manifestation) }}" class="px-3 py-1.5 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                                {{ in_array($manifestation->status, ['pending_approval', 'cancelled', 'archived'], true) ? 'Pregled' : 'Uredi' }}
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">Nema Manifestacija za ovaj Organizator.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $manifestations->links() }}</div>
</div>
@endsection
