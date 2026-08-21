@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8 max-w-2xl">
    <h1 class="text-3xl font-bold mb-2">Nova vrsta plaćanja</h1>
    <p class="text-sm text-gray-600 mb-6">Koristite sintetički naziv i kod. Vrsta se kreira neaktivna.</p>

    @if($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <ul class="list-disc ml-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.e-payments.payment-types.store') }}" class="bg-white rounded-lg shadow p-6 space-y-4">
        @csrf
        <div>
            <label class="block text-sm font-medium mb-1">Naziv</label>
            <input type="text" name="name" value="{{ old('name') }}" class="w-full border rounded px-3 py-2" required>
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Interni kod</label>
            <input type="text" name="code" value="{{ old('code') }}" class="w-full border rounded px-3 py-2 font-mono" required>
            <p class="text-xs text-gray-500 mt-1">Samo mala slova, brojevi i crtice. Nakon snimanja se ne mijenja.</p>
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Opis (opciono)</label>
            <textarea name="description" class="w-full border rounded px-3 py-2" rows="3">{{ old('description') }}</textarea>
        </div>
        <div class="flex gap-3">
            <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded">Sačuvaj</button>
            <a href="{{ route('admin.e-payments.payment-types.index') }}" class="px-4 py-2">Odustani</a>
        </div>
    </form>
</div>
@endsection
