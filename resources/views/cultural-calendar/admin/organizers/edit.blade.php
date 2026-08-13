@extends('layouts.app')

@section('content')
@php
    $activeModerators = $organizer->activeAuthorizations
        ->filter(fn ($authorization) => $authorization->user !== null)
        ->sortBy(fn ($authorization) => mb_strtolower((string) $authorization->user->name))
        ->values();
@endphp
<div class="kk-shell mx-auto px-4 sm:px-6 lg:px-8 py-6 max-w-3xl">
    <h1 style="font-size:28px; font-weight:700; margin:0 0 16px; color:#111827;">Uredi Organizatora</h1>

    @if($errors->any())
        <div class="mb-4 rounded-md bg-red-50 border border-red-200 text-red-800 px-4 py-3">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white rounded-lg border border-gray-200 p-6 mb-5" data-section="moderatori-organizatora">
        <h2 style="font-size:18px; font-weight:700; margin:0 0 12px; color:#111827;">Moderatori Organizatora</h2>
        <p class="text-sm text-gray-500 mb-3">Pregled aktivnih Moderatora. Upravljanje ovlašćenjima ide isključivo kroz Zahtjeve.</p>

        @if($activeModerators->isEmpty())
            <div class="rounded-md bg-amber-50 border border-amber-200 text-amber-900 px-4 py-3 text-sm">
                Upozorenje: Organizator nema aktivnog Moderatora.
            </div>
        @else
            <ul class="space-y-2 text-sm">
                @foreach($activeModerators as $authorization)
                    <li class="flex flex-wrap items-baseline gap-x-3 gap-y-1 border-b border-gray-100 pb-2 last:border-0 last:pb-0">
                        <span class="font-medium text-gray-900">{{ $authorization->user->name }}</span>
                        <span class="text-gray-600">{{ $authorization->user->email }}</span>
                        <span class="text-xs text-gray-500">Aktivan</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    <form method="POST" action="{{ route('cultural-organizers.update', $organizer) }}" class="bg-white rounded-lg border border-gray-200 p-6 space-y-4">
        @csrf
        @method('PUT')
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Naziv *</label>
            <input type="text" name="naziv" value="{{ old('naziv', $organizer->naziv) }}" required class="w-full border-gray-300 rounded-md">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Opis</label>
            <textarea name="opis" rows="3" class="w-full border-gray-300 rounded-md">{{ old('opis', $organizer->opis) }}</textarea>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Kontakt e-mail</label>
            <input type="email" name="contact_email" value="{{ old('contact_email', $organizer->contact_email) }}" class="w-full border-gray-300 rounded-md">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Kontakt telefon</label>
            <input type="text" name="contact_phone" value="{{ old('contact_phone', $organizer->contact_phone) }}" class="w-full border-gray-300 rounded-md">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Web sajt</label>
            <input type="text" name="website" value="{{ old('website', $organizer->website) }}" class="w-full border-gray-300 rounded-md">
        </div>
        <p class="text-sm text-gray-500">Status: {{ $organizer->statusLabel() }} (reaktivacija nije dio Koraka 1)</p>
        <div class="flex gap-3">
            <button type="submit" class="px-4 py-2 bg-red-800 text-white rounded-md font-semibold">Sačuvaj</button>
            <a href="{{ route('cultural-organizers.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700">Nazad</a>
        </div>
    </form>
</div>
@endsection
