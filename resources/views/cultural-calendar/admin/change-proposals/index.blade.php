@extends('layouts.app')

@section('content')
<div class="kk-shell mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:16px; flex-wrap:wrap;">
        <div>
            <h1 style="font-size:28px; font-weight:700; margin:0; color:#111827;">Prijedlozi izmjena</h1>
            <p class="text-sm text-gray-600 mt-1 mb-0">Urednički pregled prijedloga izmjene objavljenih događaja.</p>
        </div>
    </div>

    @if(!empty($pendingFilter))
        <div class="mb-4 rounded-md bg-blue-50 border border-blue-200 text-blue-900 px-4 py-3 text-sm flex flex-wrap gap-3 items-center justify-between">
            <div>Aktivni filter: status = <strong>Na pregledu</strong></div>
            <a href="{{ route('cultural-event-change-proposals.index') }}" class="underline">Ukloni filter</a>
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

    <div class="mb-4 flex flex-wrap gap-2">
        <a href="{{ route('cultural-event-change-proposals.index', ['proposal_status' => 'pending_review']) }}"
           class="px-3 py-1.5 border rounded-md text-sm {{ !empty($pendingFilter) ? 'border-blue-400 bg-blue-50 text-blue-900' : 'border-gray-300 text-gray-700 hover:bg-gray-50' }}">
            Na pregledu
        </a>
        <a href="{{ route('cultural-event-change-proposals.index') }}"
           class="px-3 py-1.5 border rounded-md text-sm {{ empty($pendingFilter) ? 'border-blue-400 bg-blue-50 text-blue-900' : 'border-gray-300 text-gray-700 hover:bg-gray-50' }}">
            Svi
        </a>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50">
                    <tr class="text-left text-gray-600">
                        <th class="px-4 py-3">Događaj</th>
                        <th class="px-4 py-3">Organizator</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Poslato</th>
                        <th class="px-4 py-3 text-right">Akcije</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($proposals as $proposal)
                        <tr>
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $proposal->eventEntry?->naslov ?: '—' }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $proposal->organizer?->naziv ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $proposal->statusLabel() }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $proposal->last_submitted_at?->format('d.m.Y H:i') ?? '—' }}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('cultural-event-change-proposals.show', $proposal) }}" class="px-3 py-1.5 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                                    Pregled
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-gray-500">Nema prijedloga.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($proposals->hasPages())
        <div class="mt-4">{{ $proposals->links() }}</div>
    @endif
</div>
@endsection
