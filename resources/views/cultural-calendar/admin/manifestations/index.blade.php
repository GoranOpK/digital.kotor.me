@extends('layouts.app')

@section('content')
<div class="kk-shell mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:16px; flex-wrap:wrap;">
        <h1 style="font-size:28px; font-weight:700; margin:0; color:#111827;">Manifestacije</h1>
        <a href="{{ route('cultural-manifestations.create') }}" style="display:inline-block; background:#b91c1c; color:#fff; text-decoration:none; padding:10px 14px; border-radius:8px; font-weight:600;">
            + Nova manifestacija
        </a>
    </div>

    @if(!empty($activeFilters['status']))
        <div class="mb-4 rounded-md bg-blue-50 border border-blue-200 text-blue-900 px-4 py-3 text-sm flex flex-wrap gap-3 items-center justify-between">
            <div>Aktivni filter: status = <strong>{{ \App\Models\CulturalManifestation::STATUS_LABELS[$activeFilters['status']] ?? $activeFilters['status'] }}</strong></div>
            <a href="{{ route('cultural-manifestations.index') }}" class="underline">Ukloni filter</a>
        </div>
    @endif

    @if(session('status'))
        <div class="mb-4 rounded-md bg-green-50 border border-green-200 text-green-800 px-4 py-3">{{ session('status') }}</div>
    @endif

    @if($errors->any())
        <div class="mb-4 rounded-md bg-red-50 border border-red-200 text-red-800 px-4 py-3">
            <ul class="list-disc list-inside">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="GET" action="{{ route('cultural-manifestations.index') }}" class="mb-4 flex flex-wrap gap-2 items-end">
        <div>
            <label for="status" class="block text-xs text-gray-500 mb-1">Status</label>
            <select id="status" name="status" class="rounded-md border-gray-300 text-sm">
                <option value="">— svi —</option>
                @foreach(\App\Models\CulturalManifestation::STATUS_LABELS as $value => $label)
                    <option value="{{ $value }}" @selected(($activeFilters['status'] ?? null) === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="px-3 py-1.5 border border-gray-300 rounded-md text-gray-800 hover:bg-gray-50">Filtriraj</button>
    </form>

    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50">
                    <tr class="text-left text-gray-600">
                        <th class="px-4 py-3">Naziv</th>
                        <th class="px-4 py-3">Organizator</th>
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
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $manifestation->naziv }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $manifestation->organizer?->naziv ?? '— platformska —' }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $manifestation->statusLabel() }}</td>
                            <td class="px-4 py-3 text-gray-700">
                                @if($period)
                                    {{ $period['start']->format('d.m.Y') }} – {{ $period['end']->format('d.m.Y') }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-700">{{ $manifestation->events_count }}</td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2 flex-wrap">
                                    <a href="{{ route('cultural-manifestations.edit', $manifestation) }}" class="px-3 py-1.5 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                                        {{ $manifestation->isPendingApproval() ? 'Pregled' : (($manifestation->isCancelled() || $manifestation->isArchived()) ? 'Pregled' : 'Uredi') }}
                                    </a>
                                    @if($manifestation->isDraft() || $manifestation->isReturnedForRevision())
                                        <form method="POST" action="{{ route('cultural-manifestations.submit', $manifestation) }}">
                                            @csrf
                                            <button type="submit" class="px-3 py-1.5 border border-blue-300 rounded-md text-blue-800 hover:bg-blue-50">Pošalji na odobrenje</button>
                                        </form>
                                    @elseif($manifestation->isPendingApproval())
                                        <form method="POST" action="{{ route('cultural-manifestations.publish', $manifestation) }}">
                                            @csrf
                                            <button type="submit" class="px-3 py-1.5 border border-green-300 rounded-md text-green-800 hover:bg-green-50">Objavi</button>
                                        </form>
                                        <form method="POST" action="{{ route('cultural-manifestations.return', $manifestation) }}">
                                            @csrf
                                            <button type="submit" class="px-3 py-1.5 border border-amber-300 rounded-md text-amber-800 hover:bg-amber-50">Vrati na doradu</button>
                                        </form>
                                    @elseif($manifestation->isPublished())
                                        <form method="POST" action="{{ route('cultural-manifestations.cancel', $manifestation) }}">
                                            @csrf
                                            <button type="submit" class="px-3 py-1.5 border border-red-300 rounded-md text-red-700 hover:bg-red-50">Otkaži</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500">Nema Manifestacija.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $manifestations->links() }}</div>
</div>
@endsection
