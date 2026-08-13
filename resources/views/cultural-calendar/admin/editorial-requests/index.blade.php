@extends('layouts.app')

@section('content')
@php
    $isOrganizers = ($section ?? 'organizatori') === 'organizatori';
    $orgUrl = route('cultural-editorial-requests.index', ['sekcija' => 'organizatori']);
    $modUrl = route('cultural-editorial-requests.index', ['sekcija' => 'moderatori']);
@endphp
<div class="kk-shell mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <h1 style="font-size:28px; font-weight:700; margin:0 0 16px; color:#111827;">Zahtjevi</h1>

    <div class="flex flex-wrap gap-2 mb-5" role="tablist" aria-label="Vrsta zahtjeva">
        <a
            href="{{ $orgUrl }}"
            role="tab"
            aria-selected="{{ $isOrganizers ? 'true' : 'false' }}"
            class="inline-flex items-center px-4 py-2 text-sm font-semibold rounded-md border"
            style="{{ $isOrganizers ? 'background:#7a0f17;color:#fff;border-color:#7a0f17;' : 'background:#fff;color:#374151;border-color:#d1d5db;' }}"
        >Zahtjevi za organizatore</a>
        <a
            href="{{ $modUrl }}"
            role="tab"
            aria-selected="{{ $isOrganizers ? 'false' : 'true' }}"
            class="inline-flex items-center px-4 py-2 text-sm font-semibold rounded-md border"
            style="{{ ! $isOrganizers ? 'background:#7a0f17;color:#fff;border-color:#7a0f17;' : 'background:#fff;color:#374151;border-color:#d1d5db;' }}"
        >Zahtjevi za moderatore</a>
    </div>

    @if(!empty($activeStatusFilter))
        <div class="mb-4 rounded-md bg-blue-50 border border-blue-200 text-blue-900 px-4 py-3 text-sm flex flex-wrap gap-3 items-center justify-between">
            <div>
                Aktivni filter: status =
                <strong>
                    @if($isOrganizers)
                        {{ \App\Models\CulturalOrganizerCreationRequest::STATUS_LABELS[$activeStatusFilter] ?? $activeStatusFilter }}
                    @else
                        {{ \App\Models\CulturalModeratorRequest::STATUS_LABELS[$activeStatusFilter] ?? $activeStatusFilter }}
                    @endif
                </strong>
            </div>
            <a href="{{ $isOrganizers ? $orgUrl : $modUrl }}" class="underline">Ukloni filter</a>
        </div>
    @endif

    @if(session('status'))
        <div class="mb-4 rounded-md bg-green-50 border border-green-200 text-green-800 px-4 py-3">{{ session('status') }}</div>
    @endif

    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        @if($isOrganizers)
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
        @else
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50">
                    <tr class="text-left text-gray-600">
                        <th class="px-4 py-3">Organizator</th>
                        <th class="px-4 py-3">Tip</th>
                        <th class="px-4 py-3">Ciljni korisnik</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Akcije</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($requests as $item)
                        <tr>
                            <td class="px-4 py-3">{{ $item->organizer?->naziv }}</td>
                            <td class="px-4 py-3">{{ $item->typeLabel() }}</td>
                            <td class="px-4 py-3">{{ $item->targetUser?->name }}</td>
                            <td class="px-4 py-3">{{ $item->statusLabel() }}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('cultural-moderator-requests.show', $item) }}" class="px-3 py-1.5 border border-gray-300 rounded-md">Pregled</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">Nema zahtjeva.</td></tr>
                    @endforelse
                </tbody>
            </table>
        @endif
        <div class="px-4 py-3 border-t">{{ $requests->links() }}</div>
    </div>
</div>
@endsection
