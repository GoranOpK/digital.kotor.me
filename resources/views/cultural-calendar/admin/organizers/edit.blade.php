@extends('layouts.app')

@section('content')
@php
    $activeModerators = $organizer->activeAuthorizations
        ->filter(fn ($authorization) => $authorization->user !== null)
        ->sortBy(fn ($authorization) => mb_strtolower((string) $authorization->user->name))
        ->values();
@endphp
<div class="kk-shell mx-auto px-4 sm:px-6 lg:px-8 py-6 max-w-3xl">
    <h1 style="font-size:28px; font-weight:700; margin:0 0 20px; color:#111827;">Uredi Organizatora</h1>

    @if($errors->any())
        <div class="mb-4 rounded-md bg-red-50 border border-red-200 text-red-800 px-4 py-3">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('cultural-organizers.update', $organizer) }}" class="space-y-5">
        @csrf
        @method('PUT')

        <section class="bg-white rounded-lg border border-gray-200 p-5 sm:p-6" data-section="osnovni-podaci">
            <h2 style="font-size:18px; font-weight:700; margin:0 0 4px; color:#111827;">Osnovni podaci</h2>
            <p class="text-sm text-gray-500 mb-4">Podaci Organizatora dostupni za uređivanje.</p>

            <div class="space-y-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Naziv *</label>
                    <input type="text" name="naziv" value="{{ old('naziv', $organizer->naziv) }}" required class="w-full border-gray-300 rounded-md">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Opis</label>
                    <textarea name="opis" rows="3" class="w-full border-gray-300 rounded-md resize-y min-h-[4.5rem]">{{ old('opis', $organizer->opis) }}</textarea>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kontakt e-mail</label>
                        <input type="email" name="contact_email" value="{{ old('contact_email', $organizer->contact_email) }}" class="w-full border-gray-300 rounded-md">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kontakt telefon</label>
                        <input type="text" name="contact_phone" value="{{ old('contact_phone', $organizer->contact_phone) }}" class="w-full border-gray-300 rounded-md">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Web sajt</label>
                    <input type="text" name="website" value="{{ old('website', $organizer->website) }}" class="w-full border-gray-300 rounded-md">
                </div>
            </div>
        </section>

        <section class="bg-white rounded-lg border border-gray-200 p-5 sm:p-6" data-section="moderatori-organizatora">
            <h2 style="font-size:18px; font-weight:700; margin:0 0 4px; color:#111827;">Moderatori Organizatora</h2>
            <p class="text-sm text-gray-500 mb-4">Pregled aktivnih Moderatora. Upravljanje ovlašćenjima ide isključivo kroz Zahtjeve.</p>

            @if($activeModerators->isEmpty())
                <div class="rounded-md bg-amber-50 border border-amber-200 text-amber-900 px-4 py-3 text-sm">
                    Upozorenje: Organizator nema aktivnog Moderatora.
                </div>
            @else
                <div class="overflow-x-auto">
                    <div class="hidden sm:grid sm:grid-cols-[minmax(0,1.2fr)_minmax(0,1.6fr)_5.5rem] gap-x-4 gap-y-2 border-b border-gray-200 pb-2 mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <div>Moderator</div>
                        <div>E-mail</div>
                        <div class="text-right">Status</div>
                    </div>
                    <ul class="divide-y divide-gray-100">
                        @foreach($activeModerators as $authorization)
                            <li class="py-3 sm:py-2.5 sm:grid sm:grid-cols-[minmax(0,1.2fr)_minmax(0,1.6fr)_5.5rem] sm:gap-x-4 sm:items-start">
                                <div class="mb-2 sm:mb-0">
                                    <div class="sm:hidden text-xs font-semibold uppercase tracking-wide text-gray-500 mb-0.5">Moderator</div>
                                    <div class="font-medium text-gray-900 text-sm break-words" data-moderator-name>{{ $authorization->user->name }}</div>
                                </div>
                                <div class="mb-2 sm:mb-0 min-w-0">
                                    <div class="sm:hidden text-xs font-semibold uppercase tracking-wide text-gray-500 mb-0.5">E-mail</div>
                                    <div class="text-sm text-gray-600 break-all" data-moderator-email>{{ $authorization->user->email }}</div>
                                </div>
                                <div class="sm:text-right">
                                    <div class="sm:hidden text-xs font-semibold uppercase tracking-wide text-gray-500 mb-0.5">Status</div>
                                    <span class="inline-flex text-xs font-medium text-emerald-800 bg-emerald-50 border border-emerald-200 rounded px-2 py-0.5" data-moderator-status>Aktivan</span>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </section>

        <section class="bg-white rounded-lg border border-gray-200 p-5 sm:p-6" data-section="status-organizatora">
            <div class="flex flex-col sm:flex-row sm:items-baseline sm:justify-between gap-1">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1">Status Organizatora</div>
                    <div class="text-sm font-semibold text-gray-900">{{ $organizer->statusLabel() }}</div>
                </div>
                <p class="text-xs text-gray-500">reaktivacija nije dio Koraka 1</p>
            </div>
        </section>

        <div class="flex flex-wrap gap-3 pt-1" data-section="akcije-forme">
            <button type="submit" class="px-4 py-2 bg-red-800 text-white rounded-md font-semibold">Sačuvaj</button>
            <a href="{{ route('cultural-organizers.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700">Nazad</a>
        </div>
    </form>
</div>
@endsection
