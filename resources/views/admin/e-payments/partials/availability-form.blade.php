@if($errors->any())
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
        <ul class="list-disc ml-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif

<form method="POST" action="{{ $action }}" class="bg-white rounded-lg shadow p-6 space-y-4">
    @csrf
    <div>
        <label class="block text-sm font-medium mb-1">Korisnička kategorija</label>
        <select name="user_type" id="availability-user-type" class="w-full border rounded px-3 py-2" required>
            <option value="">— izaberite —</option>
            @foreach($userTypes as $value => $label)
                <option value="{{ $value }}" @selected(old('user_type') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium mb-1">Status prebivališta</label>
        <select name="residential_status" id="availability-residential" class="w-full border rounded px-3 py-2">
            <option value="">Nije primjenjivo (pravna lica)</option>
            <option value="resident" @selected(old('residential_status') === 'resident')>Rezident</option>
            <option value="non-resident" @selected(old('residential_status') === 'non-resident')>Nerezident</option>
        </select>
        <p class="text-xs text-gray-500 mt-1">Obavezno za Fizičko lice i Preduzetnika. Zabranjeno za pravna lica.</p>
    </div>
    <div class="flex gap-3">
        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded">Sačuvaj</button>
        <a href="{{ $cancelUrl }}" class="px-4 py-2">Odustani</a>
    </div>
</form>

<script>
    (function () {
        const natural = @json($naturalPersonTypes);
        const typeSelect = document.getElementById('availability-user-type');
        const residentialSelect = document.getElementById('availability-residential');
        if (!typeSelect || !residentialSelect) return;

        function syncResidential() {
            const needsResidential = natural.indexOf(typeSelect.value) !== -1;
            residentialSelect.required = needsResidential;
        }

        typeSelect.addEventListener('change', syncResidential);
        syncResidential();
    })();
</script>
