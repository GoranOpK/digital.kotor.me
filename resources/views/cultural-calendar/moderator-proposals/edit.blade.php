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

    @if($proposal->return_reason)
        <div class="mb-4 rounded-md bg-amber-50 border border-amber-200 text-amber-900 px-4 py-3 max-w-3xl">
            <p class="font-semibold mb-1">Razlog vraćanja na doradu</p>
            <p class="mb-0 whitespace-pre-wrap">{{ $proposal->return_reason }}</p>
        </div>
    @endif

    <div class="bg-white rounded-lg border border-gray-200 p-6 max-w-3xl">
        <div class="flex flex-wrap justify-between gap-3 items-start mb-4">
            <h2 class="text-lg font-semibold text-gray-900 mb-0">Predloženi sadržaj</h2>
            <form method="POST" action="{{ route('cultural-moderator-proposals.submit', $proposal) }}">
                @csrf
                <button type="submit" class="px-3 py-1.5 border border-blue-300 rounded-md text-blue-800 hover:bg-blue-50 font-semibold">
                    Pošalji na pregled
                </button>
            </form>
        </div>

        <form method="POST" action="{{ route('cultural-moderator-proposals.update', $proposal) }}" class="space-y-4" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div>
                <label for="proposed_naslov" class="block text-sm font-medium text-gray-700 mb-1">Naslov</label>
                <input type="text" id="proposed_naslov" name="proposed_naslov" value="{{ old('proposed_naslov', $proposal->proposed_naslov) }}" maxlength="255" class="w-full rounded-md border-gray-300 shadow-sm">
                @error('proposed_naslov')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="proposed_opis" class="block text-sm font-medium text-gray-700 mb-1">Opis</label>
                <textarea id="proposed_opis" name="proposed_opis" rows="5" class="w-full rounded-md border-gray-300 shadow-sm">{{ old('proposed_opis', $proposal->proposed_opis) }}</textarea>
                @error('proposed_opis')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="proposed_category_id" class="block text-sm font-medium text-gray-700 mb-1">Kategorija</label>
                <select id="proposed_category_id" name="proposed_category_id" class="w-full rounded-md border-gray-300 shadow-sm">
                    <option value="">— bez kategorije —</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) old('proposed_category_id', $proposal->proposed_category_id) === (string) $category->id)>
                            {{ $category->naziv }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                @include('cultural-calendar.partials.event-cover-field', [
                    'coverMedia' => $proposal->proposedCoverMedia,
                    'liveCoverMedia' => $entry->coverMedia ?? null,
                    'coverLocked' => false,
                    'coverMode' => 'proposal',
                ])
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Oznake</label>
                <div class="space-y-2 max-h-48 overflow-y-auto border border-gray-200 rounded-md p-3">
                    @php
                        $selectedTagIds = collect(old('tag_ids', $proposal->tags->pluck('id')->all()))->map(fn ($id) => (int) $id)->all();
                    @endphp
                    @forelse($tags as $tag)
                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" name="tag_ids[]" value="{{ $tag->id }}" @checked(in_array((int) $tag->id, $selectedTagIds, true)) class="rounded border-gray-300 text-red-700">
                            {{ $tag->naziv }}
                        </label>
                    @empty
                        <p class="text-sm text-gray-500">Nema aktivnih oznaka.</p>
                    @endforelse
                </div>
            </div>

            <div class="pt-2">
                <button type="submit" style="background:#b91c1c; color:#fff; padding:10px 16px; border-radius:8px; font-weight:600; border:0;">
                    Sačuvaj izmjene
                </button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 p-6 max-w-3xl mt-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-2">Održavanja (podaci)</h2>
        <p class="text-sm text-gray-600 mb-4">
            Izmjene datuma, vremena i lokacije idu kroz ovaj prijedlog. Status (Planiran / Odgođen / Otkazan) ostaje van prijedloga.
        </p>

        <h3 class="text-sm font-semibold text-gray-800 mb-2">Kanonska održavanja</h3>
        <div class="space-y-4 mb-6">
            @forelse($entry->occurrences as $occurrence)
                @php
                    $updateOp = $proposal->occurrenceOps->first(
                        fn ($op) => $op->isUpdate() && (int) $op->source_occurrence_id === (int) $occurrence->id
                    );
                @endphp
                <div class="border border-gray-200 rounded-md p-3">
                    <p class="text-xs text-gray-500 mb-2">
                        Kanonski: {{ $occurrence->datum?->format('d.m.Y') }}
                        · {{ $occurrence->location?->naziv ?? $occurrence->location_manual_name ?? 'bez lokacije' }}
                        · {{ $occurrence->statusLabel() }}
                    </p>
                    <form method="POST" action="{{ route('cultural-moderator-proposals.occurrences.update-canonical', [$proposal, $occurrence]) }}" class="grid grid-cols-1 md:grid-cols-3 gap-2">
                        @csrf
                        @method('PUT')
                        @php
                            $datum = old('datum', $updateOp?->proposed_datum?->format('Y-m-d') ?? $occurrence->datum?->format('Y-m-d'));
                            $vrijemeOd = old('vrijeme_od', $updateOp ? ($updateOp->proposed_vrijeme_od ? \Illuminate\Support\Str::substr($updateOp->proposed_vrijeme_od, 0, 5) : '') : ($occurrence->vrijeme_od ? \Illuminate\Support\Str::substr($occurrence->vrijeme_od, 0, 5) : ''));
                            $vrijemeDo = old('vrijeme_do', $updateOp ? ($updateOp->proposed_vrijeme_do ? \Illuminate\Support\Str::substr($updateOp->proposed_vrijeme_do, 0, 5) : '') : ($occurrence->vrijeme_do ? \Illuminate\Support\Str::substr($occurrence->vrijeme_do, 0, 5) : ''));
                            $cjelodnevno = old('cjelodnevno', $updateOp?->proposed_cjelodnevno ?? $occurrence->cjelodnevno);
                            $locationId = old('location_id', $updateOp?->proposed_location_id ?? $occurrence->location_id);
                            $manual = old('location_manual_name', $updateOp?->proposed_location_manual_name ?? $occurrence->location_manual_name);
                        @endphp
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Datum</label>
                            <input type="date" name="datum" value="{{ $datum }}" required class="w-full rounded-md border-gray-300 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Vrijeme od</label>
                            <input type="time" name="vrijeme_od" value="{{ $vrijemeOd }}" class="w-full rounded-md border-gray-300 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Vrijeme do</label>
                            <input type="time" name="vrijeme_do" value="{{ $vrijemeDo }}" class="w-full rounded-md border-gray-300 text-sm">
                        </div>
                        <div class="md:col-span-3 flex items-center gap-2">
                            <input type="checkbox" name="cjelodnevno" value="1" @checked($cjelodnevno) class="rounded border-gray-300">
                            <span class="text-sm text-gray-700">Cjelodnevno</span>
                            @if($updateOp)
                                <span class="text-xs text-amber-700 ml-2">Predložena izmjena aktivna</span>
                            @endif
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Lokacija</label>
                            <select name="location_id" class="w-full rounded-md border-gray-300 text-sm">
                                <option value="">—</option>
                                @foreach($locations as $location)
                                    <option value="{{ $location->id }}" @selected((string) $locationId === (string) $location->id)>{{ $location->naziv }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Ručni naziv lokacije</label>
                            <input type="text" name="location_manual_name" value="{{ $manual }}" class="w-full rounded-md border-gray-300 text-sm">
                        </div>
                        <div class="flex items-end">
                            <button type="submit" class="px-3 py-1.5 border border-gray-300 rounded-md text-sm">Sačuvaj predloženu izmjenu</button>
                        </div>
                    </form>
                </div>
            @empty
                <p class="text-sm text-gray-500">Nema kanonskih održavanja.</p>
            @endforelse
        </div>

        <h3 class="text-sm font-semibold text-gray-800 mb-2">Predložena nova održavanja</h3>
        <div class="space-y-3 mb-6">
            @forelse($proposal->occurrenceOps->where('operation', 'add') as $op)
                <div class="border border-dashed border-gray-300 rounded-md p-3">
                    <form method="POST" action="{{ route('cultural-moderator-proposals.occurrences.update', [$proposal, $op]) }}" class="grid grid-cols-1 md:grid-cols-3 gap-2">
                        @csrf
                        @method('PUT')
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Datum</label>
                            <input type="date" name="datum" value="{{ old('datum', $op->proposed_datum?->format('Y-m-d')) }}" required class="w-full rounded-md border-gray-300 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Vrijeme od</label>
                            <input type="time" name="vrijeme_od" value="{{ old('vrijeme_od', $op->proposed_vrijeme_od ? \Illuminate\Support\Str::substr($op->proposed_vrijeme_od, 0, 5) : '') }}" class="w-full rounded-md border-gray-300 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Vrijeme do</label>
                            <input type="time" name="vrijeme_do" value="{{ old('vrijeme_do', $op->proposed_vrijeme_do ? \Illuminate\Support\Str::substr($op->proposed_vrijeme_do, 0, 5) : '') }}" class="w-full rounded-md border-gray-300 text-sm">
                        </div>
                        <div class="md:col-span-3 flex items-center gap-2">
                            <input type="checkbox" name="cjelodnevno" value="1" @checked(old('cjelodnevno', $op->proposed_cjelodnevno)) class="rounded border-gray-300">
                            <span class="text-sm text-gray-700">Cjelodnevno</span>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Lokacija</label>
                            <select name="location_id" class="w-full rounded-md border-gray-300 text-sm">
                                <option value="">—</option>
                                @foreach($locations as $location)
                                    <option value="{{ $location->id }}" @selected((string) old('location_id', $op->proposed_location_id) === (string) $location->id)>{{ $location->naziv }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Ručni naziv lokacije</label>
                            <input type="text" name="location_manual_name" value="{{ old('location_manual_name', $op->proposed_location_manual_name) }}" class="w-full rounded-md border-gray-300 text-sm">
                        </div>
                        <div class="flex items-end gap-2">
                            <button type="submit" class="px-3 py-1.5 border border-gray-300 rounded-md text-sm">Ažuriraj</button>
                        </div>
                    </form>
                    <form method="POST" action="{{ route('cultural-moderator-proposals.occurrences.destroy', [$proposal, $op]) }}" class="mt-2" onsubmit="return confirm('Ukloniti predloženo dodavanje?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-sm text-red-700">Ukloni iz prijedloga</button>
                    </form>
                </div>
            @empty
                <p class="text-sm text-gray-500">Nema predloženih dodavanja.</p>
            @endforelse
        </div>

        <h3 class="text-sm font-semibold text-gray-800 mb-2">Dodaj novo održavanje u prijedlog</h3>
        <form method="POST" action="{{ route('cultural-moderator-proposals.occurrences.store', $proposal) }}" class="grid grid-cols-1 md:grid-cols-3 gap-3">
            @csrf
            <div>
                <label class="block text-xs text-gray-600 mb-1">Datum</label>
                <input type="date" name="datum" value="{{ old('datum') }}" required class="w-full rounded-md border-gray-300 text-sm">
            </div>
            <div>
                <label class="block text-xs text-gray-600 mb-1">Vrijeme od</label>
                <input type="time" name="vrijeme_od" value="{{ old('vrijeme_od') }}" class="w-full rounded-md border-gray-300 text-sm">
            </div>
            <div>
                <label class="block text-xs text-gray-600 mb-1">Vrijeme do</label>
                <input type="time" name="vrijeme_do" value="{{ old('vrijeme_do') }}" class="w-full rounded-md border-gray-300 text-sm">
            </div>
            <div class="md:col-span-3 flex items-center gap-2">
                <input type="checkbox" name="cjelodnevno" value="1" @checked(old('cjelodnevno')) class="rounded border-gray-300">
                <span class="text-sm text-gray-700">Cjelodnevno</span>
            </div>
            <div>
                <label class="block text-xs text-gray-600 mb-1">Lokacija</label>
                <select name="location_id" class="w-full rounded-md border-gray-300 text-sm">
                    <option value="">—</option>
                    @foreach($locations as $location)
                        <option value="{{ $location->id }}" @selected((string) old('location_id') === (string) $location->id)>{{ $location->naziv }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs text-gray-600 mb-1">Ručni naziv lokacije</label>
                <input type="text" name="location_manual_name" value="{{ old('location_manual_name') }}" class="w-full rounded-md border-gray-300 text-sm">
            </div>
            <div class="flex items-end">
                <button type="submit" class="px-3 py-1.5 border border-blue-300 rounded-md text-blue-800 text-sm">Dodaj u prijedlog</button>
            </div>
        </form>
    </div>
</div>
@endsection
