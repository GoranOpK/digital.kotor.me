@extends('layouts.app')

@section('content')
<div class="kk-shell mx-auto px-4 sm:px-6 lg:px-8 py-6 max-w-xl">
    <h1 style="font-size:28px; font-weight:700; margin:0 0 8px; color:#111827;">Izbor organizatora</h1>
    <p class="text-sm text-gray-600 mb-4">Za rad sa događajima potreban je aktivni kontekst Organizatora.</p>

    <form method="POST" action="{{ route('cultural-moderator-context.update') }}" class="bg-white rounded-lg border border-gray-200 p-6 space-y-4">
        @csrf
        <div>
            <label for="organizer_id" class="block text-sm font-medium text-gray-700 mb-1">Organizator</label>
            <select id="organizer_id" name="organizer_id" required class="w-full rounded-md border-gray-300 shadow-sm">
                <option value="">— izaberi —</option>
                @foreach($organizers as $organizer)
                    <option value="{{ $organizer->id }}">{{ $organizer->naziv }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" style="background:#b91c1c; color:#fff; padding:10px 16px; border-radius:8px; font-weight:600; border:0;">
            Nastavi
        </button>
    </form>
</div>
@endsection
