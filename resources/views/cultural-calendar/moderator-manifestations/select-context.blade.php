@extends('layouts.app')

@section('content')
<div class="kk-shell mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <h1 style="font-size:28px; font-weight:700; margin:0 0 8px; color:#111827;">Manifestacije</h1>
    <p class="text-sm text-gray-600 mb-4">Izaberite aktivni Organizator kontekst.</p>
    <form method="POST" action="{{ route('cultural-moderator-context.update') }}" class="bg-white rounded-lg border border-gray-200 p-4 max-w-xl flex flex-wrap gap-2 items-end">
        @csrf
        <div class="flex-1">
            <label for="organizer_id" class="block text-xs text-gray-500 mb-1">Organizator</label>
            <select id="organizer_id" name="organizer_id" required class="w-full rounded-md border-gray-300 text-sm">
                <option value="">— izaberi —</option>
                @foreach($organizers as $organizer)
                    <option value="{{ $organizer->id }}">{{ $organizer->naziv }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="px-3 py-1.5 border border-gray-300 rounded-md">Postavi kontekst</button>
    </form>
</div>
@endsection
