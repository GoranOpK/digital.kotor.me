@php
    $isEdit = isset($event) && $event;
    $defaultStartDate = request('datum_od');
    if ($defaultStartDate === null && isset($event->datum_od)) {
        $defaultStartDate = $event->datum_od->format('Y-m-d');
    }
    $timeValue = old('vrijeme');
    if ($timeValue === null && $isEdit && $event->vrijeme) {
        $timeValue = substr((string) $event->vrijeme, 0, 5);
    }
@endphp

@if ($errors->any())
    <div class="mb-4 rounded-md bg-red-50 border border-red-200 text-red-800 px-4 py-3">
        <ul class="list-disc ml-5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="grid grid-cols-1 md:grid-cols-2 gap-5">
    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">Naslov *</label>
        <input type="text" name="naslov" value="{{ old('naslov', $event->naslov ?? '') }}" class="w-full border-gray-300 rounded-md" required>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Kategorija *</label>
        <select name="kategorija" class="w-full border-gray-300 rounded-md" required>
            <option value="">Odaberi kategoriju</option>
            @foreach($categories as $category)
                <option value="{{ $category }}" @selected(old('kategorija', $event->kategorija ?? '') === $category)>{{ $category }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Status *</label>
        <select name="status" class="w-full border-gray-300 rounded-md" required>
            @foreach($statuses as $status)
                <option value="{{ $status }}" @selected(old('status', $event->status ?? 'draft') === $status)>
                    {{ ucfirst($status) }}
                </option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Datum od *</label>
        <input type="date" name="datum_od" value="{{ old('datum_od', $defaultStartDate) }}" class="w-full border-gray-300 rounded-md" required>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Datum do</label>
        <input type="date" name="datum_do" value="{{ old('datum_do', isset($event->datum_do) ? $event->datum_do->format('Y-m-d') : '') }}" class="w-full border-gray-300 rounded-md">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Vrijeme</label>
        <input type="time" name="vrijeme" value="{{ $timeValue }}" class="w-full border-gray-300 rounded-md">
    </div>

    <div>
        <label style="display:block; font-size:14px; font-weight:600; color:#374151; margin-bottom:6px;">Lokacija</label>
        <input
            type="text"
            name="lokacija"
            value="{{ old('lokacija', $event->lokacija ?? '') }}"
            placeholder="Npr. Kulturni centar Kotor"
            style="width:100%; border:1px solid #d1d5db; border-radius:6px; padding:9px 10px;"
        >
    </div>

    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">Opis</label>
        <textarea name="opis" rows="5" class="w-full border-gray-300 rounded-md">{{ old('opis', $event->opis ?? '') }}</textarea>
    </div>

    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">Slika</label>
        <input type="file" name="slika" accept="image/*" class="w-full border-gray-300 rounded-md">
        @if($isEdit && $event->slika)
            <div class="mt-3">
                <div class="text-xs text-gray-500 mb-2">Trenutna slika:</div>
                <img src="{{ asset('storage/' . $event->slika) }}" alt="Slika događaja" class="h-28 rounded-md border border-gray-200">
            </div>
        @endif
    </div>

    <div class="md:col-span-2">
        <label class="inline-flex items-center gap-2 text-sm text-gray-700">
            <input type="checkbox" name="featured" value="1" class="rounded border-gray-300" @checked(old('featured', $event->featured ?? false))>
            Istaknuti događaj
        </label>
    </div>
</div>
