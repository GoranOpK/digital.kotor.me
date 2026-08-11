@extends('layouts.app')

@section('content')
<div class="kk-shell mx-auto px-4 sm:px-6 lg:px-8 py-6 max-w-3xl">
    <h1 style="font-size:28px; font-weight:700; margin:0 0 16px; color:#111827;">Nova Manifestacija</h1>
    @if($errors->any())
        <div class="mb-4 rounded-md bg-red-50 border border-red-200 text-red-800 px-4 py-3">
            <ul class="list-disc list-inside">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif
    <form method="POST" action="{{ route('cultural-moderator-manifestations.store') }}" class="bg-white rounded-lg border border-gray-200 p-6">
        @csrf
        @include('cultural-calendar.admin.manifestations.partials.form', [
            'contentEditable' => true,
            'showOrganizerPicker' => false,
            'activeOrganizer' => $activeOrganizer,
            'mediaItems' => $mediaItems,
            'organizers' => collect(),
        ])
        <div class="mt-6 flex gap-2">
            <button type="submit" class="px-4 py-2 bg-red-700 text-white rounded-md font-semibold">Sačuvaj nacrt</button>
            <a href="{{ route('cultural-moderator-manifestations.index') }}" class="px-4 py-2 border border-gray-300 rounded-md">Odustani</a>
        </div>
    </form>
</div>
@endsection
