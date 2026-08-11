@php
    $routeLink = $routeLink ?? null;
    $routeUnlink = $routeUnlink ?? null;
    $routeMove = $routeMove ?? null;
    $linksEditable = $linksEditable ?? false;
@endphp

<div class="mt-8 space-y-6">
    <div>
        <h2 class="text-lg font-semibold text-gray-900 mb-2">Povezani Događaji</h2>
        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50">
                    <tr class="text-left text-gray-600">
                        <th class="px-4 py-2">Naslov</th>
                        <th class="px-4 py-2">Status</th>
                        <th class="px-4 py-2">Organizator</th>
                        @if($linksEditable)<th class="px-4 py-2 text-right">Akcija</th>@endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($manifestation->events as $event)
                        <tr>
                            <td class="px-4 py-2">{{ $event->naslov ?: '— bez naslova —' }}</td>
                            <td class="px-4 py-2">{{ $event->statusLabel() }}</td>
                            <td class="px-4 py-2">{{ $event->organizer?->naziv ?? '—' }}</td>
                            @if($linksEditable)
                                <td class="px-4 py-2 text-right">
                                    <form method="POST" action="{{ $routeUnlink }}" class="inline">
                                        @csrf
                                        <input type="hidden" name="event_entry_id" value="{{ $event->id }}">
                                        <button type="submit" class="px-2 py-1 border border-red-300 rounded text-red-700 hover:bg-red-50">Ukloni</button>
                                    </form>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-4 text-gray-500">Nema povezanih Događaja.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($linksEditable)
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <h3 class="text-sm font-semibold text-gray-900 mb-2">Poveži Događaj (bez Manifestacije)</h3>
            <form method="POST" action="{{ $routeLink }}" class="flex flex-wrap gap-2 items-end">
                @csrf
                <div class="flex-1 min-w-[220px]">
                    <label for="link_event_entry_id" class="block text-xs text-gray-500 mb-1">Događaj</label>
                    <select id="link_event_entry_id" name="event_entry_id" required class="w-full rounded-md border-gray-300 text-sm">
                        <option value="">— izaberi —</option>
                        @foreach($linkableEvents as $event)
                            <option value="{{ $event->id }}">
                                {{ $event->naslov ?: '— bez naslova —' }} · {{ $event->statusLabel() }} · {{ $event->organizer?->naziv ?? 'bez Org.' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="px-3 py-1.5 border border-blue-300 rounded-md text-blue-800 hover:bg-blue-50">Poveži</button>
            </form>
        </div>

        <div class="bg-white rounded-lg border border-amber-200 p-4">
            <h3 class="text-sm font-semibold text-gray-900 mb-1">Premjesti Događaj iz druge Manifestacije</h3>
            <p class="text-xs text-amber-800 mb-2">Eksplicitna akcija (BR-201). Event trenutno pripada drugoj MF.</p>
            <form method="POST" action="{{ $routeMove }}" class="flex flex-wrap gap-2 items-end">
                @csrf
                <div class="flex-1 min-w-[220px]">
                    <label for="move_event_entry_id" class="block text-xs text-gray-500 mb-1">Događaj</label>
                    <select id="move_event_entry_id" name="event_entry_id" required class="w-full rounded-md border-gray-300 text-sm">
                        <option value="">— izaberi —</option>
                        @foreach($moveCandidates as $event)
                            <option value="{{ $event->id }}">
                                {{ $event->naslov ?: '— bez naslova —' }} · {{ $event->statusLabel() }} · MF: {{ $event->manifestation?->naziv ?? '—' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="px-3 py-1.5 border border-amber-400 rounded-md text-amber-900 hover:bg-amber-50">Premjesti u ovu Manifestaciju</button>
            </form>
        </div>
    @endif
</div>
