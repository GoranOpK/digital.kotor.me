@extends('layouts.app')

@section('content')
<div class="kk-shell mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:16px; flex-wrap:wrap;">
        <div>
            <h1 style="font-size:28px; font-weight:700; margin:0; color:#111827;">Pregled prijedloga</h1>
            <p class="text-sm text-gray-600 mt-1 mb-0">
                {{ $entry?->naslov ?: 'Događaj' }} · {{ $proposal->statusLabel() }} · ID {{ $proposal->id }}
            </p>
        </div>
        <a href="{{ route('cultural-event-change-proposals.index') }}" class="px-3 py-1.5 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">Nazad na listu</a>
    </div>

    @if(session('status'))
        <div class="mb-4 rounded-md bg-green-50 border border-green-200 text-green-800 px-4 py-3">{{ session('status') }}</div>
    @endif

    @if($errors->any())
        <div class="mb-4 rounded-md bg-red-50 border border-red-200 text-red-800 px-4 py-3">
            <ul class="list-disc list-inside">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="bg-white rounded-lg border border-gray-200 p-6 max-w-4xl mb-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Poređenje: kanonski vs predloženo</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50">
                    <tr class="text-left text-gray-600">
                        <th class="px-3 py-2">Polje</th>
                        <th class="px-3 py-2">Kanonski</th>
                        <th class="px-3 py-2">Predloženo</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr>
                        <td class="px-3 py-2 text-gray-500">Naslov</td>
                        <td class="px-3 py-2">{{ $entry?->naslov ?: '—' }}</td>
                        <td class="px-3 py-2 font-medium">{{ $proposal->proposed_naslov ?: '—' }}</td>
                    </tr>
                    <tr>
                        <td class="px-3 py-2 text-gray-500">Opis</td>
                        <td class="px-3 py-2 whitespace-pre-wrap">{{ $entry?->opis ?: '—' }}</td>
                        <td class="px-3 py-2 whitespace-pre-wrap">{{ $proposal->proposed_opis ?: '—' }}</td>
                    </tr>
                    <tr>
                        <td class="px-3 py-2 text-gray-500">Kategorija</td>
                        <td class="px-3 py-2">{{ $entry?->category?->naziv ?? '—' }}</td>
                        <td class="px-3 py-2">{{ $proposal->proposedCategory?->naziv ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="px-3 py-2 text-gray-500">Naslovni medij</td>
                        <td class="px-3 py-2">{{ $entry?->coverMedia?->naziv ?? '—' }}</td>
                        <td class="px-3 py-2">{{ $proposal->proposedCoverMedia?->naziv ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="px-3 py-2 text-gray-500">Oznake</td>
                        <td class="px-3 py-2">{{ $entry?->tags?->pluck('naziv')->join(', ') ?: '—' }}</td>
                        <td class="px-3 py-2">{{ $proposal->tags->pluck('naziv')->join(', ') ?: '—' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm mt-4">
            <div>
                <dt class="text-gray-500">Organizator</dt>
                <dd>{{ $proposal->organizer?->naziv ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">Poslato</dt>
                <dd>{{ $proposal->last_submitted_at?->format('d.m.Y H:i') ?? '—' }}</dd>
            </div>
            @if($proposal->review_started_at)
                <div>
                    <dt class="text-gray-500">Pregled pokrenut</dt>
                    <dd>{{ $proposal->review_started_at->format('d.m.Y H:i') }} · {{ $proposal->reviewStartedBy?->name ?? '—' }}</dd>
                </div>
            @endif
        </dl>
    </div>

    @if($proposal->isPendingReview())
        <div class="bg-white rounded-lg border border-gray-200 p-6 max-w-4xl space-y-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-0">Uredničke akcije</h2>

            @if($proposal->review_started_at === null)
                <form method="POST" action="{{ route('cultural-event-change-proposals.start-review', $proposal) }}">
                    @csrf
                    <button type="submit" style="background:#b91c1c; color:#fff; padding:10px 16px; border-radius:8px; font-weight:600; border:0;">
                        Pokreni pregled
                    </button>
                </form>
            @else
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('cultural-event-change-proposals.edit', $proposal) }}" class="px-4 py-2 border border-gray-300 rounded-md text-gray-800 hover:bg-gray-50 font-semibold">
                        Uredi prijedlog
                    </a>
                    <form method="POST" action="{{ route('cultural-event-change-proposals.approve', $proposal) }}">
                        @csrf
                        <button type="submit" style="background:#15803d; color:#fff; padding:10px 16px; border-radius:8px; font-weight:600; border:0;">
                            Odobri
                        </button>
                    </form>
                </div>

                <form method="POST" action="{{ route('cultural-event-change-proposals.return', $proposal) }}" class="space-y-3">
                    @csrf
                    <div>
                        <label for="return_reason" class="block text-sm font-medium text-gray-700 mb-1">Razlog vraćanja na doradu (obavezno)</label>
                        <textarea id="return_reason" name="return_reason" rows="3" required maxlength="2000" class="w-full rounded-md border-gray-300 shadow-sm">{{ old('return_reason') }}</textarea>
                        @error('return_reason')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <button type="submit" class="px-4 py-2 border border-amber-600 rounded-md text-amber-800 hover:bg-amber-50 font-semibold">
                        Vrati na doradu
                    </button>
                </form>
            @endif
        </div>
    @endif
</div>
@endsection
