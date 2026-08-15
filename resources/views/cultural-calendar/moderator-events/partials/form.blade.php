@php
    $entry = $entry ?? null;
@endphp

<div class="space-y-4">
    <div class="rounded-md bg-gray-50 border border-gray-200 px-3 py-2 text-sm text-gray-700">
        Organizator: <strong>{{ $activeOrganizer->naziv }}</strong>
        <span class="text-gray-500">(postavlja se server-side iz aktivnog konteksta)</span>
    </div>

    <div>
        <label for="naslov" class="block text-sm font-medium text-gray-700 mb-1">Naslov</label>
        <input type="text" id="naslov" name="naslov" value="{{ old('naslov', $entry->naslov ?? '') }}" maxlength="255" class="w-full rounded-md border-gray-300 shadow-sm">
        @error('naslov')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="opis" class="block text-sm font-medium text-gray-700 mb-1">Opis</label>
        <textarea id="opis" name="opis" rows="5" class="w-full rounded-md border-gray-300 shadow-sm">{{ old('opis', $entry->opis ?? '') }}</textarea>
        @error('opis')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="category_id" class="block text-sm font-medium text-gray-700 mb-1">Kategorija</label>
        <select id="category_id" name="category_id" class="w-full rounded-md border-gray-300 shadow-sm">
            <option value="">— bez kategorije —</option>
            @foreach($categories as $category)
                <option value="{{ $category->id }}" @selected((string) old('category_id', $entry->category_id ?? '') === (string) $category->id)>
                    {{ $category->naziv }}
                </option>
            @endforeach
        </select>
    </div>

    <div>
        @include('cultural-calendar.partials.event-cover-field', [
            'coverMedia' => $entry->coverMedia ?? null,
            'coverLocked' => false,
            'coverMode' => 'event',
        ])
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Oznake</label>
        <div class="space-y-2 max-h-48 overflow-y-auto border border-gray-200 rounded-md p-3">
            @php
                $selectedTagIds = collect(old('tag_ids', $entry?->tags?->pluck('id')->all() ?? []))->map(fn ($id) => (int) $id)->all();
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
</div>
