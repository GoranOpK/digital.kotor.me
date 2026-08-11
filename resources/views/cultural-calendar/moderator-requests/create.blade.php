@extends('layouts.app')

@section('content')
<div class="kk-shell mx-auto px-4 sm:px-6 lg:px-8 py-6 max-w-3xl">
    <h1 style="font-size:28px; font-weight:700; margin:0 0 8px; color:#111827;">Zahtjev za Moderatora</h1>
    <p class="text-sm text-gray-600 mb-4">Organizator: <strong>{{ $organizer->naziv }}</strong></p>

    @if(session('status'))
        <div class="mb-4 rounded-md bg-green-50 border border-green-200 text-green-800 px-4 py-3">{{ session('status') }}</div>
    @endif
    @if($errors->any())
        <div class="mb-4 rounded-md bg-red-50 border border-red-200 text-red-800 px-4 py-3">
            <ul class="list-disc list-inside">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('cultural-moderator-requests.store', $organizer) }}" class="bg-white rounded-lg border border-gray-200 p-6 space-y-4" id="moderator-request-form">
        @csrf
        <div>
            <label class="block text-sm font-medium mb-1">Tip *</label>
            <select name="type" id="moderator-request-type" required class="w-full border-gray-300 rounded-md">
                <option value="add" @selected(old('type', 'add') === 'add')>Dodjela</option>
                <option value="remove" @selected(old('type') === 'remove')>Uklanjanje</option>
            </select>
        </div>

        <div id="add-fields" class="space-y-4">
            <div>
                <label class="block text-sm font-medium mb-1">Ime i prezime predloženog Moderatora *</label>
                <input type="text" name="proposed_moderator_name" value="{{ old('proposed_moderator_name') }}" class="w-full border-gray-300 rounded-md" autocomplete="name">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">E-mail predloženog Moderatora *</label>
                <input type="email" name="proposed_moderator_email" value="{{ old('proposed_moderator_email') }}" class="w-full border-gray-300 rounded-md" autocomplete="email">
            </div>
        </div>

        <div id="remove-fields" class="space-y-4" hidden>
            <div>
                <label class="block text-sm font-medium mb-1">Aktivni Moderator za uklanjanje *</label>
                <select name="target_user_id" class="w-full border-gray-300 rounded-md">
                    <option value="">— izaberite —</option>
                    @foreach($activeModerators as $authorization)
                        @if($authorization->user)
                            <option value="{{ $authorization->user->id }}" @selected((string) old('target_user_id') === (string) $authorization->user->id)>
                                {{ $authorization->user->name }}
                            </option>
                        @endif
                    @endforeach
                </select>
            </div>
            <p class="text-xs text-gray-500">Prikazani su samo aktivni Moderatori ovog Organizatora.</p>
        </div>

        <button type="submit" style="background:#b91c1c; color:#fff; padding:10px 16px; border-radius:8px; font-weight:600; border:0; cursor:pointer;">
            Podnesi zahtjev
        </button>
    </form>
</div>

<script>
(function () {
    var typeSelect = document.getElementById('moderator-request-type');
    var addFields = document.getElementById('add-fields');
    var removeFields = document.getElementById('remove-fields');
    if (!typeSelect || !addFields || !removeFields) return;

    function sync() {
        var isRemove = typeSelect.value === 'remove';
        addFields.hidden = isRemove;
        removeFields.hidden = !isRemove;
        addFields.querySelectorAll('input').forEach(function (el) {
            el.required = !isRemove;
            el.disabled = isRemove;
        });
        removeFields.querySelectorAll('select').forEach(function (el) {
            el.required = isRemove;
            el.disabled = !isRemove;
        });
    }

    typeSelect.addEventListener('change', sync);
    sync();
})();
</script>
@endsection
