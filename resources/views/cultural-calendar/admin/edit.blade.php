@extends('layouts.app')

@section('content')
<div class="kk-shell mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Uredi događaj</h1>
        <a href="{{ route('cultural-events.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
            Nazad na listu
        </a>
    </div>

    @php($eventId = $event->getKey() ?? request()->route('dogadjaji'))
    <form method="POST" action="{{ route('cultural-events.update', ['dogadjaji' => $eventId]) }}" enctype="multipart/form-data" class="bg-white rounded-lg border border-gray-200 p-6">
        @csrf
        @method('PUT')
        @include('cultural-calendar.admin.partials.form', ['event' => $event, 'maxEventDate' => $maxEventDate])
        <div style="margin-top:24px; padding-top:12px; border-top:1px solid #e5e7eb;">
            <button type="submit" style="display:inline-block; background:#b91c1c; color:#fff; border:none; border-radius:8px; padding:10px 16px; font-weight:600; cursor:pointer;">
                Sačuvaj izmjene
            </button>
        </div>
    </form>
</div>
@endsection
