@extends('layouts.app')

@section('content')
<div class="kk-shell mx-auto px-4 sm:px-6 lg:px-8 py-6 max-w-3xl">
    <h1 style="font-size:28px; font-weight:700; margin:0 0 8px; color:#111827;">Zahtjev za kreiranje Organizatora</h1>
    <p class="text-sm text-gray-600 mb-4">Podnošenje zahtjeva ne kreira Organizatora. Entitet nastaje tek nakon odobrenja Urednika.</p>

    @if(session('status'))
        <div class="mb-4 rounded-md bg-green-50 border border-green-200 text-green-800 px-4 py-3">{{ session('status') }}</div>
    @endif
    @if($errors->any())
        <div class="mb-4 rounded-md bg-red-50 border border-red-200 text-red-800 px-4 py-3">
            <ul class="list-disc list-inside">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('cultural-organizer-creation-requests.store') }}" class="bg-white rounded-lg border border-gray-200 p-6 space-y-4">
        @csrf
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Naziv Organizatora *</label>
            <input type="text" name="naziv" value="{{ old('naziv') }}" required class="w-full border-gray-300 rounded-md">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Opis</label>
            <textarea name="opis" rows="3" class="w-full border-gray-300 rounded-md">{{ old('opis') }}</textarea>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Kontakt e-mail</label>
            <input type="email" name="contact_email" value="{{ old('contact_email') }}" class="w-full border-gray-300 rounded-md">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Kontakt telefon</label>
            <input type="text" name="contact_phone" value="{{ old('contact_phone') }}" class="w-full border-gray-300 rounded-md">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Web sajt</label>
            <input type="text" name="website" value="{{ old('website') }}" class="w-full border-gray-300 rounded-md">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Predloženi Moderator (postojeći nalog) *</label>
            <select name="proposed_moderator_user_id" required class="w-full border-gray-300 rounded-md">
                <option value="">— izaberite korisnika —</option>
                @foreach($candidateModerators as $candidate)
                    <option value="{{ $candidate->id }}" @selected((string) old('proposed_moderator_user_id') === (string) $candidate->id)>
                        {{ $candidate->name }} ({{ $candidate->email }})
                    </option>
                @endforeach
            </select>
        </div>
        {{-- Inline styles: same visibility guarantee as Event / Manifestation create (Tailwind preflight + utility CSS). --}}
        <button type="submit" style="background:#b91c1c; color:#fff; padding:10px 16px; border-radius:8px; font-weight:600; border:0; cursor:pointer;">
            Podnesi zahtjev
        </button>
    </form>
</div>
@endsection
