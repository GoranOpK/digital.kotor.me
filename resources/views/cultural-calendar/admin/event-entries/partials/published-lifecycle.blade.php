{{-- Lifecycle / management akcije za Objavljen Događaj (ne ordinary content). --}}
@if($entry->organizer_id === null)
    <div class="bg-white rounded-lg border border-gray-200 p-6 max-w-3xl mb-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-2">Poveži sa Organizatorom</h2>
        <p class="text-sm text-gray-600 mb-4">
            Povezivanje je jednokratno. Nakon uspjeha Organizator se ne može ukloniti niti zamijeniti kroz ovu akciju.
        </p>
        <form method="POST" action="{{ route('cultural-event-entries.link-organizer', $entry) }}" class="space-y-3">
            @csrf
            <div>
                <label for="link_organizer_id" class="block text-sm font-medium text-gray-700 mb-1">Organizator</label>
                <select id="link_organizer_id" name="organizer_id" required class="w-full rounded-md border-gray-300 shadow-sm">
                    <option value="">— izaberite Aktivnog Organizatora —</option>
                    @foreach($activeOrganizers as $organizer)
                        <option value="{{ $organizer->id }}" @selected((string) old('organizer_id') === (string) $organizer->id)>
                            {{ $organizer->naziv }}
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="px-4 py-2 border border-indigo-400 rounded-md text-indigo-900 hover:bg-indigo-50 font-semibold">
                Poveži sa Organizatorom
            </button>
        </form>
    </div>
@endif

<div class="bg-white rounded-lg border border-gray-200 p-6 max-w-3xl mb-6">
    <h2 class="text-lg font-semibold text-gray-900 mb-4">Akcije događaja</h2>
    <div class="flex flex-wrap gap-3 mb-6">
        @if($entry->featured)
            <form method="POST" action="{{ route('cultural-event-entries.featured', $entry) }}">
                @csrf
                <input type="hidden" name="featured" value="0">
                <button type="submit" class="px-4 py-2 border border-gray-400 rounded-md text-gray-800 hover:bg-gray-50 font-semibold">
                    Ukloni isticanje
                </button>
            </form>
        @else
            <form method="POST" action="{{ route('cultural-event-entries.featured', $entry) }}">
                @csrf
                <input type="hidden" name="featured" value="1">
                <button type="submit" class="px-4 py-2 border border-indigo-400 rounded-md text-indigo-900 hover:bg-indigo-50 font-semibold">
                    Istakni
                </button>
            </form>
        @endif
    </div>

    <form method="POST" action="{{ route('cultural-event-entries.cancel', $entry) }}" class="space-y-3">
        @csrf
        <div>
            <label for="cancellation_reason" class="block text-sm font-medium text-gray-700 mb-1">Razlog otkazivanja (opciono)</label>
            <textarea id="cancellation_reason" name="cancellation_reason" rows="3" class="w-full rounded-md border-gray-300 shadow-sm">{{ old('cancellation_reason') }}</textarea>
        </div>
        <button type="submit" class="px-4 py-2 border border-red-600 rounded-md text-red-800 hover:bg-red-50 font-semibold">
            Otkaži događaj
        </button>
    </form>
</div>

<div class="bg-white rounded-lg border border-gray-200 p-6 max-w-3xl">
    <h2 class="text-lg font-semibold text-gray-900 mb-3">Održavanja</h2>
    <ul class="space-y-4 text-sm">
        @forelse($entry->occurrences as $occurrence)
            <li class="border border-gray-100 rounded-md p-3">
                <div class="text-gray-900 mb-2">
                    {{ $occurrence->datum?->format('d.m.Y') }}
                    @if($occurrence->cjelodnevno)
                        · cjelodnevno
                    @elseif($occurrence->vrijeme_od)
                        · {{ \Illuminate\Support\Str::substr($occurrence->vrijeme_od, 0, 5) }}
                        @if($occurrence->vrijeme_do)
                            – {{ \Illuminate\Support\Str::substr($occurrence->vrijeme_do, 0, 5) }}
                        @endif
                    @endif
                    · {{ $occurrence->location?->naziv ?? $occurrence->location_manual_name ?? 'bez lokacije' }}
                    · <strong>{{ $occurrence->statusLabel() }}</strong>
                </div>
                <div class="flex flex-wrap gap-3 items-start">
                    @if($occurrence->isPlanned())
                        <form method="POST" action="{{ route('cultural-event-entries.occurrences.postpone', [$entry, $occurrence]) }}"
                              class="space-y-2 border border-amber-100 rounded-md p-2 bg-amber-50/40 min-w-[14rem]">
                            @csrf
                            <div>
                                <label for="postponement_reason-{{ $occurrence->id }}" class="block text-xs font-medium text-gray-700 mb-1">
                                    Razlog odgađanja (opciono)
                                </label>
                                <textarea id="postponement_reason-{{ $occurrence->id }}" name="postponement_reason" rows="2"
                                          class="w-full rounded-md border-gray-300 shadow-sm text-sm">{{ old('postponement_reason') }}</textarea>
                            </div>
                            <button type="submit" class="px-3 py-1.5 border border-amber-300 rounded-md text-amber-800 hover:bg-amber-50">
                                Odgodi
                            </button>
                        </form>
                        <form method="POST" action="{{ route('cultural-event-entries.occurrences.cancel', [$entry, $occurrence]) }}"
                              class="space-y-2 border border-red-100 rounded-md p-2 bg-red-50/40 min-w-[14rem]">
                            @csrf
                            <div>
                                <label for="occ-cancellation_reason-{{ $occurrence->id }}" class="block text-xs font-medium text-gray-700 mb-1">
                                    Razlog otkazivanja (opciono)
                                </label>
                                <textarea id="occ-cancellation_reason-{{ $occurrence->id }}" name="cancellation_reason" rows="2"
                                          class="w-full rounded-md border-gray-300 shadow-sm text-sm">{{ old('cancellation_reason') }}</textarea>
                            </div>
                            <button type="submit" class="px-3 py-1.5 border border-red-300 rounded-md text-red-700 hover:bg-red-50">
                                Otkaži održavanje
                            </button>
                        </form>
                    @elseif($occurrence->isPostponed())
                        <form method="POST" action="{{ route('cultural-event-entries.occurrences.resume', [$entry, $occurrence]) }}"
                              class="w-full border border-gray-200 rounded-md p-3 space-y-2 bg-gray-50">
                            @csrf
                            <p class="text-xs font-semibold text-gray-700 mb-1">Novi termin → Planirano</p>
                            <div class="flex flex-wrap gap-2 items-end">
                                <div>
                                    <label class="block text-xs text-gray-600" for="datum-{{ $occurrence->id }}">Datum</label>
                                    <input id="datum-{{ $occurrence->id }}" type="date" name="datum" required
                                           value="{{ old('datum', $occurrence->datum?->toDateString()) }}"
                                           class="rounded-md border-gray-300 text-sm">
                                </div>
                                <div>
                                    <label class="inline-flex items-center gap-1 text-xs text-gray-600 mt-5">
                                        <input type="checkbox" name="cjelodnevno" value="1"
                                               @checked(old('cjelodnevno', $occurrence->cjelodnevno))>
                                        Cjelodnevno
                                    </label>
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600" for="vrijeme_od-{{ $occurrence->id }}">Od</label>
                                    <input id="vrijeme_od-{{ $occurrence->id }}" type="time" name="vrijeme_od"
                                           value="{{ old('vrijeme_od', $occurrence->vrijeme_od ? \Illuminate\Support\Str::substr($occurrence->vrijeme_od, 0, 5) : '') }}"
                                           class="rounded-md border-gray-300 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600" for="vrijeme_do-{{ $occurrence->id }}">Do</label>
                                    <input id="vrijeme_do-{{ $occurrence->id }}" type="time" name="vrijeme_do"
                                           value="{{ old('vrijeme_do', $occurrence->vrijeme_do ? \Illuminate\Support\Str::substr($occurrence->vrijeme_do, 0, 5) : '') }}"
                                           class="rounded-md border-gray-300 text-sm">
                                </div>
                                <button type="submit" class="px-3 py-1.5 border border-green-600 rounded-md text-green-800 hover:bg-green-50">
                                    Vrati u Planirano
                                </button>
                            </div>
                        </form>
                        <form method="POST" action="{{ route('cultural-event-entries.occurrences.cancel', [$entry, $occurrence]) }}"
                              class="space-y-2 border border-red-100 rounded-md p-2 bg-red-50/40 min-w-[14rem]">
                            @csrf
                            <div>
                                <label for="occ-cancellation_reason-postponed-{{ $occurrence->id }}" class="block text-xs font-medium text-gray-700 mb-1">
                                    Razlog otkazivanja (opciono)
                                </label>
                                <textarea id="occ-cancellation_reason-postponed-{{ $occurrence->id }}" name="cancellation_reason" rows="2"
                                          class="w-full rounded-md border-gray-300 shadow-sm text-sm">{{ old('cancellation_reason') }}</textarea>
                            </div>
                            <button type="submit" class="px-3 py-1.5 border border-red-300 rounded-md text-red-700 hover:bg-red-50">
                                Otkaži održavanje
                            </button>
                        </form>
                    @endif
                </div>
            </li>
        @empty
            <li class="text-gray-500">Nema održavanja.</li>
        @endforelse
    </ul>
</div>
