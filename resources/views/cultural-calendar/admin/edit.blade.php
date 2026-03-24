@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto p-6">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Uredi događaj</h1>
        <a href="{{ route('cultural-events.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
            Nazad na listu
        </a>
    </div>

    <form method="POST" action="{{ route('cultural-events.update', $event) }}" enctype="multipart/form-data" class="bg-white rounded-lg border border-gray-200 p-6">
        @csrf
        @method('PUT')
        @include('cultural-calendar.admin.partials.form', ['event' => $event])
        <div class="mt-6">
            <button type="submit" class="px-5 py-2.5 bg-red-700 text-white rounded-md hover:bg-red-800">
                Sačuvaj izmjene
            </button>
        </div>
    </form>
</div>
@endsection
