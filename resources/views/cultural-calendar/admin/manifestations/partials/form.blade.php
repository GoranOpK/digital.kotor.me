@php
    $manifestation = $manifestation ?? null;
    $contentEditable = $contentEditable ?? true;
    $showOrganizerPicker = $showOrganizerPicker ?? true;
    $activeOrganizer = $activeOrganizer ?? null;
@endphp

<div class="space-y-6 kk-padded-fields" data-kk-padded-fields>
    <div>
        <label for="naziv" class="block text-sm font-medium text-gray-700 mb-2">Naziv *</label>
        <input type="text" id="naziv" name="naziv" value="{{ old('naziv', $manifestation->naziv ?? '') }}" maxlength="255"
               @disabled(! $contentEditable)
               class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-700 focus:ring-red-700">
        @error('naziv')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="opis" class="block text-sm font-medium text-gray-700 mb-2">Opis</label>
        <textarea id="opis" name="opis" rows="5" @disabled(! $contentEditable)
                  class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-700 focus:ring-red-700">{{ old('opis', $manifestation->opis ?? '') }}</textarea>
        @error('opis')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    @if($showOrganizerPicker)
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6" data-kk-mf-form-row="organizer-website">
            <div>
                <label for="organizer_id" class="block text-sm font-medium text-gray-700 mb-2">Organizator</label>
                <select id="organizer_id" name="organizer_id" @disabled(! $contentEditable)
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-700 focus:ring-red-700">
                    <option value="">— platformska (bez Organizatora) —</option>
                    @foreach($organizers as $organizer)
                        <option value="{{ $organizer->id }}" @selected((string) old('organizer_id', $manifestation->organizer_id ?? '') === (string) $organizer->id)>
                            {{ $organizer->naziv }}{{ $organizer->isActive() ? '' : ' (neaktivan)' }}
                        </option>
                    @endforeach
                </select>
                @error('organizer_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="web_stranica" class="block text-sm font-medium text-gray-700 mb-2">Web stranica / Više informacija</label>
                <input type="url" id="web_stranica" name="web_stranica" value="{{ old('web_stranica', $manifestation->web_stranica ?? '') }}" maxlength="2048"
                       @disabled(! $contentEditable)
                       placeholder="https://"
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-700 focus:ring-red-700">
                @error('web_stranica')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>
    @else
        @if($activeOrganizer)
            <div class="rounded-md bg-blue-50 border border-blue-200 px-4 py-3 text-sm text-blue-900">
                Organizator: <strong>{{ $activeOrganizer->naziv }}</strong>
            </div>
        @endif

        <div>
            <label for="web_stranica" class="block text-sm font-medium text-gray-700 mb-2">Web stranica / Više informacija</label>
            <input type="url" id="web_stranica" name="web_stranica" value="{{ old('web_stranica', $manifestation->web_stranica ?? '') }}" maxlength="2048"
                   @disabled(! $contentEditable)
                   placeholder="https://"
                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-700 focus:ring-red-700">
            @error('web_stranica')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
    @endif

    <div>
        @include('cultural-calendar.partials.event-cover-field', [
            'coverMedia' => $manifestation->coverMedia ?? null,
            'coverLocked' => ! $contentEditable,
            'coverMode' => 'event',
            'coverLockMessage' => 'Manifestacija je zaključana; naslovna fotografija se ne može mijenjati.',
        ])
    </div>
</div>
