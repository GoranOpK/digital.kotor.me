@php
    $media = $media ?? null;
    $isEdit = $media !== null;
@endphp

<div class="space-y-4">
    <div>
        <label for="naziv" class="block text-sm font-medium text-gray-700 mb-1">Naziv</label>
        <input
            type="text"
            id="naziv"
            name="naziv"
            value="{{ old('naziv', $media->naziv ?? '') }}"
            required
            maxlength="255"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-700 focus:ring-red-700"
        >
        @error('naziv')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="alt_tekst" class="block text-sm font-medium text-gray-700 mb-1">ALT tekst</label>
        <input
            type="text"
            id="alt_tekst"
            name="alt_tekst"
            value="{{ old('alt_tekst', $media->alt_tekst ?? '') }}"
            required
            maxlength="255"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-700 focus:ring-red-700"
        >
        @error('alt_tekst')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="namjena" class="block text-sm font-medium text-gray-700 mb-1">Namjena</label>
        <select
            id="namjena"
            name="namjena"
            required
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-700 focus:ring-red-700"
        >
            @foreach($purposes as $purpose)
                <option value="{{ $purpose }}" @selected(old('namjena', $media->namjena ?? \App\Models\CulturalMedia::PURPOSE_EVENT_COVER) === $purpose)>
                    {{ $purposeLabels[$purpose] ?? $purpose }}
                </option>
            @endforeach
        </select>
        @error('namjena')
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
                <option value="{{ $status }}" @selected(old('status', $media->status ?? \App\Models\CulturalMedia::STATUS_ACTIVE) === $status)>
                    {{ $statusLabels[$status] ?? $status }}
                </option>
            @endforeach
        </select>
        @error('status')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="opis" class="block text-sm font-medium text-gray-700 mb-1">Opis (opciono)</label>
        <textarea
            id="opis"
            name="opis"
            rows="3"
            maxlength="5000"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-700 focus:ring-red-700"
        >{{ old('opis', $media->opis ?? '') }}</textarea>
        @error('opis')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
            <label for="autor" class="block text-sm font-medium text-gray-700 mb-1">Autor (opciono)</label>
            <input type="text" id="autor" name="autor" value="{{ old('autor', $media->autor ?? '') }}" maxlength="255" class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-700 focus:ring-red-700">
            @error('autor')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="izvor" class="block text-sm font-medium text-gray-700 mb-1">Izvor (opciono)</label>
            <input type="text" id="izvor" name="izvor" value="{{ old('izvor', $media->izvor ?? '') }}" maxlength="255" class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-700 focus:ring-red-700">
            @error('izvor')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="licenca" class="block text-sm font-medium text-gray-700 mb-1">Licenca (opciono)</label>
            <input type="text" id="licenca" name="licenca" value="{{ old('licenca', $media->licenca ?? '') }}" maxlength="255" class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-700 focus:ring-red-700">
            @error('licenca')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    @if(! $isEdit)
        <div>
            <label for="fajl" class="block text-sm font-medium text-gray-700 mb-1">Fotografija (JPEG, PNG ili WebP, max 5 MB)</label>
            <input
                type="file"
                id="fajl"
                name="fajl"
                accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                required
                class="w-full text-sm text-gray-700"
            >
            @error('fajl')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    @else
        <div>
            <div class="text-xs text-gray-500 mb-2">Trenutna fotografija (zamjena fajla nije dio Koraka 1):</div>
            <img src="{{ $media->publicUrl() }}" alt="{{ $media->alt_tekst }}" class="h-28 rounded-md border border-gray-200">
            <p class="mt-2 text-xs text-gray-500">
                {{ strtoupper($media->format) }} · {{ $media->sirina }}×{{ $media->visina }} · {{ number_format($media->velicina / 1024, 1) }} KB
            </p>
        </div>
    @endif
</div>
