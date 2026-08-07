@extends('layouts.app')

@section('content')
<div class="kk-shell mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:16px; flex-wrap:wrap;">
        <div>
            <h1 style="font-size:28px; font-weight:700; margin:0; color:#111827;">Prijedlog izmjene</h1>
            <p class="text-sm text-gray-600 mt-1 mb-0">
                {{ $entry?->naslov ?: 'Događaj' }} · {{ $proposal->statusLabel() }} · ID {{ $proposal->id }}
            </p>
        </div>
        <a href="{{ route('cultural-moderator-events.edit', $entry) }}" class="px-3 py-1.5 border border-gray-300 rounded-md">Nazad na događaj</a>
    </div>

    @if(session('status'))
        <div class="mb-4 rounded-md bg-green-50 border border-green-200 text-green-800 px-4 py-3">{{ session('status') }}</div>
    @endif

    @if($errors->any())
        <div class="mb-4 rounded-md bg-red-50 border border-red-200 text-red-800 px-4 py-3">
            <ul class="list-disc list-inside">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="bg-white rounded-lg border border-gray-200 p-6 max-w-3xl mb-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Predloženi sadržaj (zaključano)</h2>
        <dl class="grid grid-cols-1 gap-3 text-sm">
            <div>
                <dt class="text-gray-500">Naslov</dt>
                <dd class="font-medium">{{ $proposal->proposed_naslov ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">Opis</dt>
                <dd class="whitespace-pre-wrap">{{ $proposal->proposed_opis ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">Kategorija</dt>
                <dd>{{ $proposal->proposedCategory?->naziv ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">Naslovni medij</dt>
                <dd>{{ $proposal->proposedCoverMedia?->naziv ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">Oznake</dt>
                <dd>
                    @if($proposal->tags->isEmpty())
                        —
                    @else
                        {{ $proposal->tags->pluck('naziv')->join(', ') }}
                    @endif
                </dd>
            </div>
            <div>
                <dt class="text-gray-500">Status</dt>
                <dd>{{ $proposal->statusLabel() }}</dd>
            </div>
            @if($proposal->last_submitted_at)
                <div>
                    <dt class="text-gray-500">Poslato</dt>
                    <dd>{{ $proposal->last_submitted_at->format('d.m.Y H:i') }}</dd>
                </div>
            @endif
        </dl>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 p-6 max-w-3xl mb-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-3">Predložene izmjene održavanja</h2>
        @if($proposal->occurrenceOps->isEmpty())
            <p class="text-sm text-gray-500 mb-0">Nema predloženih operacija nad održavanjima.</p>
        @else
            <ul class="text-sm space-y-2 mb-0">
                @foreach($proposal->occurrenceOps->sortBy('id') as $op)
                    <li>
                        <strong>{{ $op->isAdd() ? 'Dodavanje' : 'Izmjena' }}</strong>
                        · {{ $op->proposed_datum?->format('d.m.Y') }}
                        · {{ $op->proposedLocation?->naziv ?? $op->proposed_location_manual_name ?? 'bez lokacije' }}
                        @if($op->isUpdate() && $op->sourceOccurrence)
                            <span class="text-gray-500">(kanonski #{{ $op->source_occurrence_id }})</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    @if($proposal->canBeWithdrawn())
        <div class="bg-white rounded-lg border border-gray-200 p-6 max-w-3xl">
            <h2 class="text-lg font-semibold text-gray-900 mb-3">Akcije</h2>
            <form method="POST" action="{{ route('cultural-moderator-proposals.withdraw', $proposal) }}">
                @csrf
                <button type="submit" class="px-4 py-2 border border-amber-600 rounded-md text-amber-800 hover:bg-amber-50 font-semibold">
                    Povuci prijedlog
                </button>
            </form>
        </div>
    @endif
</div>
@endsection
