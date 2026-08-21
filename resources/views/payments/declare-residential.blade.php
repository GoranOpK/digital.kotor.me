@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8 max-w-2xl">
    <h1 class="text-3xl font-bold mb-2">Odaberite status rezidentnosti</h1>
    <p class="text-sm text-gray-600 mb-6">Status je potreban da bi sistem odredio dostupne vrste plaćanja. Ne izvodi se iz ostalih podataka profila.</p>

    @if($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <ul class="list-disc ml-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('payments.declaration.store') }}" class="bg-white rounded-lg shadow p-6 space-y-4">
        @csrf
        <label class="flex items-center gap-2">
            <input type="radio" name="residential_status" value="resident" @checked(old('residential_status') === 'resident')>
            Rezident
        </label>
        <label class="flex items-center gap-2">
            <input type="radio" name="residential_status" value="non-resident" @checked(old('residential_status') === 'non-resident')>
            Nerezident
        </label>
        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded">Sačuvaj i nastavi</button>
    </form>
</div>
@endsection
