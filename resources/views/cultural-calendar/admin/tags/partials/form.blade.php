@php
    $tag = $tag ?? null;
@endphp

<div class="space-y-4">
    <div>
        <label for="naziv" class="block text-sm font-medium text-gray-700 mb-1">Naziv</label>
        <input
            type="text"
            id="naziv"
            name="naziv"
            value="{{ old('naziv', $tag->naziv ?? '') }}"
            required
            maxlength="255"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-700 focus:ring-red-700"
        >
        @error('naziv')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="opis" class="block text-sm font-medium text-gray-700 mb-1">Opis (opciono)</label>
        <textarea
            id="opis"
            name="opis"
            rows="4"
            maxlength="5000"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-700 focus:ring-red-700"
        >{{ old('opis', $tag->opis ?? '') }}</textarea>
        @error('opis')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
        <select
            id="status"
            name="status"
            required
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-700 focus:ring-red-700"
        >
            @foreach($statuses as $status)
                <option value="{{ $status }}" @selected(old('status', $tag->status ?? \App\Models\CulturalTag::STATUS_ACTIVE) === $status)>
                    {{ $statusLabels[$status] ?? $status }}
                </option>
            @endforeach
        </select>
        @error('status')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>
</div>
