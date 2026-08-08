{{-- PO-N-TR-02-04 — generator Održavanja (samo Nacrt). --}}
@php
    /** @var \App\Models\CulturalEventEntry $entry */
    /** @var \Illuminate\Support\Collection|\Illuminate\Database\Eloquent\Collection $locations */
    /** @var string $generateRoute */
@endphp

@if($entry->isDraft())
    <div class="mt-8 pt-6 border-t border-gray-200">
        <h3 class="text-base font-semibold text-gray-900 mb-3">Generiši Održavanja</h3>
        <p class="text-sm text-gray-600 mb-4">Jednokratno kreiranje više nezavisnih Održavanja (dnevno / sedmično / mjesečno). Najviše 100 po operaciji.</p>

        @error('generator')
            <p class="text-sm text-red-600 mb-3">{{ $message }}</p>
        @enderror
        @error('count')
            <p class="text-sm text-red-600 mb-3">{{ $message }}</p>
        @enderror
        @error('end_date')
            <p class="text-sm text-red-600 mb-3">{{ $message }}</p>
        @enderror

        <form method="POST" action="{{ $generateRoute }}" class="grid grid-cols-1 md:grid-cols-3 gap-3" id="occurrence-generator-form">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tip ponavljanja</label>
                <select name="recurrence_type" required class="w-full rounded-md border-gray-300 shadow-sm">
                    <option value="daily" @selected(old('recurrence_type') === 'daily')>Dnevno</option>
                    <option value="weekly" @selected(old('recurrence_type', 'weekly') === 'weekly')>Sedmično</option>
                    <option value="monthly" @selected(old('recurrence_type') === 'monthly')>Mjesečno</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Početni datum</label>
                <input type="date" name="start_date" value="{{ old('start_date') }}" required class="w-full rounded-md border-gray-300 shadow-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Završetak</label>
                <select name="end_mode" id="gen_end_mode" class="w-full rounded-md border-gray-300 shadow-sm">
                    <option value="count" @selected(old('end_mode', old('count') !== null ? 'count' : (old('end_date') ? 'end_date' : 'count')) === 'count')>Broj Održavanja</option>
                    <option value="end_date" @selected(old('end_mode') === 'end_date' || (old('end_date') && old('count') === null))>Krajnji datum</option>
                </select>
            </div>
            <div id="gen_count_wrap">
                <label class="block text-sm font-medium text-gray-700 mb-1">Broj</label>
                <input type="number" name="count" id="gen_count" min="1" max="100" value="{{ old('count') }}" class="w-full rounded-md border-gray-300 shadow-sm">
            </div>
            <div id="gen_end_date_wrap" class="hidden">
                <label class="block text-sm font-medium text-gray-700 mb-1">Krajnji datum</label>
                <input type="date" name="end_date" id="gen_end_date" value="{{ old('end_date') }}" class="w-full rounded-md border-gray-300 shadow-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Vrijeme od</label>
                <input type="time" name="vrijeme_od" value="{{ old('vrijeme_od') }}" class="w-full rounded-md border-gray-300 shadow-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Vrijeme do</label>
                <input type="time" name="vrijeme_do" value="{{ old('vrijeme_do') }}" class="w-full rounded-md border-gray-300 shadow-sm">
            </div>
            <div>
                <label class="inline-flex items-center gap-2 text-sm text-gray-700 mt-7">
                    <input type="checkbox" name="cjelodnevno" value="1" @checked(old('cjelodnevno')) class="rounded border-gray-300">
                    Cjelodnevno
                </label>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Kataloška lokacija</label>
                <select name="location_id" class="w-full rounded-md border-gray-300 shadow-sm">
                    <option value="">—</option>
                    @foreach($locations as $location)
                        <option value="{{ $location->id }}" @selected((string) old('location_id') === (string) $location->id)>{{ $location->naziv }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Ručni naziv lokacije</label>
                <input type="text" name="location_manual_name" value="{{ old('location_manual_name') }}" class="w-full rounded-md border-gray-300 shadow-sm">
            </div>
            <div class="md:col-span-3 flex justify-end">
                <button type="submit" class="px-4 py-2 bg-red-700 text-white rounded-md hover:bg-red-800">Generiši</button>
            </div>
        </form>
    </div>
    <script>
        (function () {
            var mode = document.getElementById('gen_end_mode');
            var countWrap = document.getElementById('gen_count_wrap');
            var endWrap = document.getElementById('gen_end_date_wrap');
            var countInput = document.getElementById('gen_count');
            var endInput = document.getElementById('gen_end_date');
            if (!mode) return;
            function sync() {
                var isCount = mode.value === 'count';
                countWrap.classList.toggle('hidden', !isCount);
                endWrap.classList.toggle('hidden', isCount);
                if (isCount) {
                    endInput.value = '';
                    endInput.removeAttribute('required');
                    countInput.setAttribute('required', 'required');
                } else {
                    countInput.value = '';
                    countInput.removeAttribute('required');
                    endInput.setAttribute('required', 'required');
                }
            }
            mode.addEventListener('change', sync);
            sync();
        })();
    </script>
@endif
