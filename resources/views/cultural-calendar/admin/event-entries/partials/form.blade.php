@php
    $entry = $entry ?? null;
@endphp

<div class="space-y-6">
    <div>
        <label for="naslov" class="block text-sm font-medium text-gray-700 mb-2">Naslov</label>
        <input
            type="text"
            id="naslov"
            name="naslov"
            value="{{ old('naslov', $entry->naslov ?? '') }}"
            maxlength="255"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-700 focus:ring-red-700"
        >
        @error('naslov')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="opis" class="block text-sm font-medium text-gray-700 mb-2">Opis</label>
        <textarea
            id="opis"
            name="opis"
            rows="5"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-700 focus:ring-red-700"
        >{{ old('opis', $entry->opis ?? '') }}</textarea>
        @error('opis')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6" data-kk-event-form-row="category-organizer">
        <div>
            <label for="category_id" class="block text-sm font-medium text-gray-700 mb-2">Kategorija</label>
            <select
                id="category_id"
                name="category_id"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-700 focus:ring-red-700"
            >
                <option value="">— bez kategorije —</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) old('category_id', $entry->category_id ?? '') === (string) $category->id)>
                        {{ $category->naziv }}
                    </option>
                @endforeach
            </select>
            @error('category_id')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="organizer_manual_name" class="block text-sm font-medium text-gray-700 mb-2">Organizator</label>
            <input
                type="text"
                id="organizer_manual_name"
                name="organizer_manual_name"
                value="{{ old('organizer_manual_name', $entry->organizer_manual_name ?? '') }}"
                maxlength="255"
                placeholder="Opciono — unesite naziv organizatora"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-700 focus:ring-red-700"
            >
            @error('organizer_manual_name')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
            @error('organizer_id')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div>
        @include('cultural-calendar.partials.event-cover-field', [
            'coverMedia' => $entry->coverMedia ?? null,
            'coverLocked' => false,
            'coverMode' => 'event',
        ])
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Oznake</label>
        <div class="flex flex-wrap gap-x-5 gap-y-2 border border-gray-200 rounded-md p-3" data-kk-event-tags="wrap">
            @php
                $selectedTagIds = collect(old('tag_ids', $entry?->tags?->pluck('id')->all() ?? []))->map(fn ($id) => (int) $id)->all();
                $sortedTags = $tags->sortBy(fn ($tag) => mb_strtolower((string) $tag->naziv), SORT_NATURAL)->values();
            @endphp
            @forelse($sortedTags as $tag)
                <label class="inline-flex items-center gap-2 text-sm text-gray-700 whitespace-nowrap">
                    <input
                        type="checkbox"
                        name="tag_ids[]"
                        value="{{ $tag->id }}"
                        @checked(in_array((int) $tag->id, $selectedTagIds, true))
                        class="rounded border-gray-300 text-red-700 focus:ring-red-700"
                    >
                    {{ $tag->naziv }}
                </label>
            @empty
                <p class="text-sm text-gray-500">Nema aktivnih oznaka.</p>
            @endforelse
        </div>
        @error('tag_ids')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    @unless($entry?->isPublished())
        <div class="rounded-md bg-gray-50 border border-gray-200 px-3 py-2 text-sm text-gray-600">
            Isticanje događaja biće dostupno nakon objave.
        </div>
    @endunless
</div>
