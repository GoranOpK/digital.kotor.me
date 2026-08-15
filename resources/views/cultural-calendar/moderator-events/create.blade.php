@extends('layouts.app')

@section('content')
<div class="kk-shell mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:16px; flex-wrap:wrap;">
        <div>
            <h1 style="font-size:28px; font-weight:700; margin:0; color:#111827;">Novi nacrt</h1>
            <p class="text-sm text-gray-600 mt-1 mb-0">Organizator: {{ $activeOrganizer->naziv }}</p>
        </div>
        <a href="{{ route('cultural-moderator-events.index') }}" class="px-3 py-1.5 border border-gray-300 rounded-md">Nazad</a>
    </div>

    @if($errors->any())
        <div class="mb-4 rounded-md bg-red-50 border border-red-200 text-red-800 px-4 py-3">
            <ul class="list-disc list-inside">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="bg-white rounded-lg border border-gray-200 p-6 max-w-3xl">
        <form method="POST" action="{{ route('cultural-moderator-events.store') }}" enctype="multipart/form-data">
            @csrf
            @include('cultural-calendar.moderator-events.partials.form')
            <div class="mt-6">
                <button type="submit" style="background:#b91c1c; color:#fff; padding:10px 16px; border-radius:8px; font-weight:600; border:0;">
                    Sačuvaj nacrt
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
