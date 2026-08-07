@extends('layouts.app')

@section('content')
<div class="kk-shell mx-auto px-4 sm:px-6 lg:px-8 py-6 max-w-3xl">
    <h1 style="font-size:28px; font-weight:700; margin:0 0 16px; color:#111827;">Uredi Organizatora</h1>

    @if($errors->any())
        <div class="mb-4 rounded-md bg-red-50 border border-red-200 text-red-800 px-4 py-3">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('cultural-organizers.update', $organizer) }}" class="bg-white rounded-lg border border-gray-200 p-6 space-y-4">
        @csrf
        @method('PUT')
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Naziv *</label>
            <input type="text" name="naziv" value="{{ old('naziv', $organizer->naziv) }}" required class="w-full border-gray-300 rounded-md">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Opis</label>
            <textarea name="opis" rows="3" class="w-full border-gray-300 rounded-md">{{ old('opis', $organizer->opis) }}</textarea>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Kontakt e-mail</label>
            <input type="email" name="contact_email" value="{{ old('contact_email', $organizer->contact_email) }}" class="w-full border-gray-300 rounded-md">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Kontakt telefon</label>
            <input type="text" name="contact_phone" value="{{ old('contact_phone', $organizer->contact_phone) }}" class="w-full border-gray-300 rounded-md">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Web sajt</label>
            <input type="text" name="website" value="{{ old('website', $organizer->website) }}" class="w-full border-gray-300 rounded-md">
        </div>
        <p class="text-sm text-gray-500">Status: {{ $organizer->statusLabel() }} (reaktivacija nije dio Koraka 1)</p>
        <div class="flex gap-3">
            <button type="submit" class="px-4 py-2 bg-red-800 text-white rounded-md font-semibold">Sačuvaj</button>
            <a href="{{ route('cultural-organizers.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700">Nazad</a>
        </div>
    </form>
</div>
@endsection
