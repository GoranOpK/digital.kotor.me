@extends('layouts.app')

@section('content')
@php
    $publishedDirectEdit = $publishedDirectEdit ?? false;
    $activeOrganizers = $activeOrganizers ?? collect();
@endphp
<div class="kk-shell mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:16px; flex-wrap:wrap;">
        <div>
            <h1 style="font-size:28px; font-weight:700; margin:0; color:#111827;">Uredi događaj</h1>
            <p class="text-sm text-gray-600 mt-1 mb-0">Status: {{ $entry->editorialStatusLabel() }} · ID {{ $entry->id }}</p>
        </div>
        <a href="{{ route('cultural-event-entries.index') }}" class="px-3 py-1.5 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">Nazad na listu</a>
    </div>

    @if(session('status'))
        <div class="mb-4 rounded-md bg-green-50 border border-green-200 text-green-800 px-4 py-3">
            {{ session('status') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-4 rounded-md bg-red-50 border border-red-200 text-red-800 px-4 py-3">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($entry->return_reason && ! $publishedDirectEdit)
        <div class="mb-4 rounded-md bg-amber-50 border border-amber-200 text-amber-900 px-4 py-3 max-w-3xl">
            <p class="font-semibold mb-1">Razlog vraćanja na doradu</p>
            <p class="mb-0 whitespace-pre-wrap">{{ $entry->return_reason }}</p>
        </div>
    @endif

    <div class="bg-white rounded-lg border border-gray-200 p-6 md:p-8 max-w-4xl mb-8">
        <div class="flex flex-wrap justify-between gap-3 items-start mb-4">
            <h2 class="text-lg font-semibold text-gray-900 mb-0">Podaci događaja</h2>
            @if(! $publishedDirectEdit)
                <div class="flex flex-wrap gap-2">
                    @if($entry->organizer_id === null)
                        <form method="POST" action="{{ route('cultural-event-entries.publish', $entry) }}">
                            @csrf
                            <button type="submit" class="px-3 py-1.5 border border-green-600 rounded-md text-green-800 hover:bg-green-50 font-semibold">
                                Objavi
                            </button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('cultural-event-entries.submit', $entry) }}">
                            @csrf
                            <button type="submit" class="px-3 py-1.5 border border-blue-300 rounded-md text-blue-800 hover:bg-blue-50 font-semibold">
                                Pošalji na odobrenje
                            </button>
                        </form>
                    @endif
                </div>
            @endif
        </div>
        <form method="POST" action="{{ route('cultural-event-entries.update', $entry) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            @include('cultural-calendar.admin.event-entries.partials.form', ['entry' => $entry])
            <div class="mt-6">
                <button type="submit" style="background:#b91c1c; color:#fff; padding:10px 16px; border-radius:8px; font-weight:600; border:0;">
                    Sačuvaj izmjene
                </button>
            </div>
        </form>
    </div>

    @if($publishedDirectEdit)
        @include('cultural-calendar.admin.event-entries.partials.published-lifecycle', [
            'entry' => $entry,
            'activeOrganizers' => $activeOrganizers,
        ])
    @else
        <div class="bg-white rounded-lg border border-gray-200 p-6 max-w-4xl">
            <h2 class="text-lg font-semibold text-gray-900 mb-2">Održavanja</h2>
            <p class="text-sm text-gray-600 mb-4">Događaj u pripremi može imati 0 ili više održavanja. Fizičko uklanjanje samo dok događaj nije objavljen (N-TR-04).</p>

            <div class="overflow-x-auto mb-6">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr class="text-left text-gray-600">
                            <th class="px-3 py-2">Datum</th>
                            <th class="px-3 py-2">Vrijeme</th>
                            <th class="px-3 py-2">Lokacija</th>
                            <th class="px-3 py-2">Status</th>
                            <th class="px-3 py-2 text-right">Akcije</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($entry->occurrences as $occurrence)
                            <tr>
                                <td class="px-3 py-2" colspan="5">
                                    <form method="POST" action="{{ route('cultural-event-entries.occurrences.update', [$entry, $occurrence]) }}" class="grid grid-cols-1 md:grid-cols-6 gap-2 items-end">
                                        @csrf
                                        @method('PUT')
                                        <div>
                                            <label class="block text-xs text-gray-500 mb-1">Datum</label>
                                            <input type="date" name="datum" value="{{ old('datum', $occurrence->datum?->format('Y-m-d')) }}" required class="w-full rounded-md border-gray-300 text-sm">
                                        </div>
                                        <div>
                                            <label class="block text-xs text-gray-500 mb-1">Od</label>
                                            <input type="time" name="vrijeme_od" value="{{ old('vrijeme_od', $occurrence->vrijeme_od ? \Illuminate\Support\Str::substr($occurrence->vrijeme_od, 0, 5) : '') }}" class="w-full rounded-md border-gray-300 text-sm">
                                        </div>
                                        <div>
                                            <label class="block text-xs text-gray-500 mb-1">Do</label>
                                            <input type="time" name="vrijeme_do" value="{{ old('vrijeme_do', $occurrence->vrijeme_do ? \Illuminate\Support\Str::substr($occurrence->vrijeme_do, 0, 5) : '') }}" class="w-full rounded-md border-gray-300 text-sm">
                                        </div>
                                        <div>
                                            <label class="block text-xs text-gray-500 mb-1">Cjelodnevno</label>
                                            <label class="inline-flex items-center gap-2 text-sm">
                                                <input type="checkbox" name="cjelodnevno" value="1" @checked(old('cjelodnevno', $occurrence->cjelodnevno)) class="rounded border-gray-300 text-red-700">
                                                Da
                                            </label>
                                        </div>
                                        <div>
                                            <label class="block text-xs text-gray-500 mb-1">Kataloška lokacija</label>
                                            <select name="location_id" class="w-full rounded-md border-gray-300 text-sm">
                                                <option value="">—</option>
                                                @foreach($locations as $location)
                                                    <option value="{{ $location->id }}" @selected((string) old('location_id', $occurrence->location_id) === (string) $location->id)>
                                                        {{ $location->naziv }}
                                                    </option>
                                                @endforeach
                                                @if($occurrence->location && ! $locations->contains('id', $occurrence->location_id))
                                                    <option value="{{ $occurrence->location->id }}" selected>{{ $occurrence->location->naziv }} (istorijska)</option>
                                                @endif
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-xs text-gray-500 mb-1">Ručni naziv</label>
                                            <input type="text" name="location_manual_name" value="{{ old('location_manual_name', $occurrence->location_manual_name) }}" class="w-full rounded-md border-gray-300 text-sm">
                                        </div>
                                        <div class="md:col-span-6 flex gap-2 justify-end">
                                            <span class="text-xs text-gray-500 self-center mr-auto">Status: {{ $occurrence->statusLabel() }}</span>
                                            <button type="submit" class="px-3 py-1.5 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">Sačuvaj</button>
                                        </div>
                                    </form>
                                    <form method="POST" action="{{ route('cultural-event-entries.occurrences.destroy', [$entry, $occurrence]) }}" class="mt-2 flex justify-end" onsubmit="return confirm('Ukloniti ovo održavanje?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="px-3 py-1.5 border border-red-300 rounded-md text-red-700 hover:bg-red-50">Ukloni</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-3 py-6 text-center text-gray-500">Nema održavanja (dozvoljeno u pripremi).</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <h3 class="text-base font-semibold text-gray-900 mb-3">Dodaj održavanje</h3>
            <form method="POST" action="{{ route('cultural-event-entries.occurrences.store', $entry) }}" class="grid grid-cols-1 md:grid-cols-3 gap-3">
                @csrf
                <div>
                    <label for="new_datum" class="block text-sm font-medium text-gray-700 mb-1">Datum</label>
                    <input type="date" id="new_datum" name="datum" value="{{ old('datum') }}" required class="w-full rounded-md border-gray-300 shadow-sm">
                </div>
                <div>
                    <label for="new_vrijeme_od" class="block text-sm font-medium text-gray-700 mb-1">Vrijeme od</label>
                    <input type="time" id="new_vrijeme_od" name="vrijeme_od" value="{{ old('vrijeme_od') }}" class="w-full rounded-md border-gray-300 shadow-sm">
                </div>
                <div>
                    <label for="new_vrijeme_do" class="block text-sm font-medium text-gray-700 mb-1">Vrijeme do</label>
                    <input type="time" id="new_vrijeme_do" name="vrijeme_do" value="{{ old('vrijeme_do') }}" class="w-full rounded-md border-gray-300 shadow-sm">
                </div>
                <div>
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700 mt-7">
                        <input type="checkbox" name="cjelodnevno" value="1" @checked(old('cjelodnevno')) class="rounded border-gray-300 text-red-700">
                        Cjelodnevno
                    </label>
                </div>
                <div>
                    <label for="new_location_id" class="block text-sm font-medium text-gray-700 mb-1">Kataloška lokacija</label>
                    <select id="new_location_id" name="location_id" class="w-full rounded-md border-gray-300 shadow-sm">
                        <option value="">— bez / ručni —</option>
                        @foreach($locations as $location)
                            <option value="{{ $location->id }}" @selected((string) old('location_id') === (string) $location->id)>
                                {{ $location->naziv }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="new_location_manual_name" class="block text-sm font-medium text-gray-700 mb-1">Ručni naziv lokacije</label>
                    <input type="text" id="new_location_manual_name" name="location_manual_name" value="{{ old('location_manual_name') }}" class="w-full rounded-md border-gray-300 shadow-sm">
                </div>
                <div class="md:col-span-3">
                    <button type="submit" style="background:#b91c1c; color:#fff; padding:10px 16px; border-radius:8px; font-weight:600; border:0;">
                        Dodaj održavanje
                    </button>
                </div>
            </form>

            @include('cultural-calendar.partials.occurrence-generator-form', [
                'entry' => $entry,
                'locations' => $locations,
                'generateRoute' => route('cultural-event-entries.occurrences.generate', $entry),
            ])
        </div>

        @if($entry->isEditorialPreparationDeletable())
            <div class="bg-white rounded-lg border border-red-200 p-6 max-w-3xl mt-8">
                <h2 class="text-lg font-semibold text-red-900 mb-2">Brisanje događaja</h2>
                <p class="text-sm text-gray-600 mb-4">
                    Trajno uklanja događaj i sva njegova održavanja. Ova radnja se ne može poništiti.
                </p>
                <form method="POST" action="{{ route('cultural-event-entries.destroy', $entry) }}" onsubmit="return confirm('Da li ste sigurni da želite trajno obrisati ovaj događaj? Ova radnja se ne može poništiti.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-3 py-1.5 border border-red-300 rounded-md text-red-700 hover:bg-red-50 font-semibold">
                        Obriši
                    </button>
                </form>
            </div>
        @endif
    @endif
</div>
@endsection
