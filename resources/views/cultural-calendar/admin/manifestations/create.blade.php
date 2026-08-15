@extends('layouts.app')

@section('content')
<div class="kk-shell mx-auto px-4 sm:px-6 lg:px-8 py-6 max-w-3xl">
    <h1 style="font-size:28px; font-weight:700; margin:0 0 16px; color:#111827;">Nova Manifestacija</h1>

    @if($errors->any())
        <div class="mb-4 rounded-md bg-red-50 border border-red-200 text-red-800 px-4 py-3">
            <ul class="list-disc list-inside">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('cultural-manifestations.store') }}" enctype="multipart/form-data" class="bg-white rounded-lg border border-gray-200 p-6">
        @csrf
        @include('cultural-calendar.admin.manifestations.partials.form', [
            'contentEditable' => true,
            'showOrganizerPicker' => true,
            'organizers' => $organizers,
        ])
        <div class="mt-6 flex gap-2">
            {{-- Inline styles: Tailwind preflight leaves submit buttons transparent; missing utility CSS → white-on-white. Match Event create CTA pattern. --}}
            <button type="submit" style="background:#b91c1c; color:#fff; padding:10px 16px; border-radius:8px; font-weight:600; border:0; cursor:pointer;">
                Sačuvaj nacrt
            </button>
            <a href="{{ route('cultural-manifestations.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700">Odustani</a>
        </div>
    </form>
</div>
@endsection
