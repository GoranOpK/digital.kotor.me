@extends('layouts.app')

@section('content')
<div class="kk-shell mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div class="flex items-center justify-between mb-6 gap-3 flex-wrap">
        <h1 class="text-2xl font-bold text-gray-900">Manifestacije</h1>
        <a href="{{ route('cultural-calendar.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
            Nazad na Kalendar kulture
        </a>
    </div>

    @if($manifestations->isEmpty())
        <div class="bg-white border border-gray-200 rounded-lg p-8 text-center text-gray-500">
            Trenutno nema objavljenih manifestacija.
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach($manifestations as $manifestation)
                @include('cultural-calendar.manifestations.partials.card', [
                    'manifestation' => $manifestation,
                    'manifestationQuery' => $manifestationQuery,
                ])
            @endforeach
        </div>

        <div class="mt-6">
            {{ $manifestations->links() }}
        </div>
    @endif
</div>
@endsection
