@extends('layouts.app')

@section('content')
<div class="kk-shell mx-auto px-4 sm:px-6 lg:px-8 py-6 max-w-3xl">
    <h1 style="font-size:28px; font-weight:700; margin:0 0 8px; color:#111827;">Zahtjev za Moderatora</h1>
    <p class="text-sm text-gray-600 mb-4">Organizator: <strong>{{ $organizer->naziv }}</strong></p>

    @if($errors->any())
        <div class="mb-4 rounded-md bg-red-50 border border-red-200 text-red-800 px-4 py-3">
            <ul class="list-disc list-inside">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('cultural-moderator-requests.store', $organizer) }}" class="bg-white rounded-lg border border-gray-200 p-6 space-y-4">
        @csrf
        <div>
            <label class="block text-sm font-medium mb-1">Tip *</label>
            <select name="type" required class="w-full border-gray-300 rounded-md">
                <option value="add" @selected(old('type') === 'add')>Dodjela</option>
                <option value="remove" @selected(old('type') === 'remove')>Uklanjanje</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Ciljni korisnik (user_id) *</label>
            <select name="target_user_id" required class="w-full border-gray-300 rounded-md">
                <option value="">— izaberite —</option>
                @foreach($candidateUsers as $candidate)
                    <option value="{{ $candidate->id }}" @selected((string) old('target_user_id') === (string) $candidate->id)>
                        {{ $candidate->name }} ({{ $candidate->email }})
                    </option>
                @endforeach
            </select>
        </div>
        <p class="text-xs text-gray-500">Aktivni moderatori: {{ $activeModerators->pluck('user.name')->filter()->join(', ') ?: '—' }}</p>
        <button type="submit" class="px-4 py-2 bg-red-800 text-white rounded-md font-semibold">Podnesi</button>
    </form>
</div>
@endsection
