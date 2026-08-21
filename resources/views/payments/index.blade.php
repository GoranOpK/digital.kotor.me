@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8 max-w-3xl">
    <h1 class="text-3xl font-bold mb-2">e-Plaćanje</h1>
    <p class="text-sm text-gray-600 mb-6">Dostupne vrste plaćanja za vaš korisnički profil.</p>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">{{ session('error') }}</div>
    @endif

    @if($types->isEmpty())
        <div class="bg-white rounded-lg shadow p-6">
            <p>Trenutno nema dostupnih vrsta plaćanja za vaš korisnički profil.</p>
        </div>
    @else
        <div class="space-y-4">
            @foreach($types as $type)
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-xl font-semibold">{{ $type->name }}</h2>
                    @if($type->description)
                        <p class="text-gray-600 mt-1">{{ $type->description }}</p>
                    @endif
                    <a href="{{ route('payments.start', $type) }}" class="inline-block mt-4 bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">Nastavi</a>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
