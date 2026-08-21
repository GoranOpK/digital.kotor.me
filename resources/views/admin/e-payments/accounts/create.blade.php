@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8 max-w-2xl">
    <a href="{{ route('admin.e-payments.payment-types.accounts.index', $type) }}" class="text-gray-600 hover:text-gray-900">← Računi</a>
    <h1 class="text-3xl font-bold mt-2 mb-2">Novi račun</h1>
    <p class="text-sm text-gray-600 mb-6">Unesite sintetički broj (npr. SYN-…). Nakon snimanja broj se ne može mijenjati.</p>

    @if($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <ul class="list-disc ml-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.e-payments.payment-types.accounts.store', $type) }}" class="bg-white rounded-lg shadow p-6 space-y-4">
        @csrf
        <div>
            <label class="block text-sm font-medium mb-1">Broj računa</label>
            <input type="text" name="account_number" value="{{ old('account_number') }}" class="w-full border rounded px-3 py-2 font-mono" maxlength="64" required>
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Naziv / label (opciono)</label>
            <input type="text" name="name" value="{{ old('name') }}" class="w-full border rounded px-3 py-2">
        </div>
        <div class="flex gap-3">
            <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded">Sačuvaj</button>
            <a href="{{ route('admin.e-payments.payment-types.accounts.index', $type) }}" class="px-4 py-2">Odustani</a>
        </div>
    </form>
</div>
@endsection
