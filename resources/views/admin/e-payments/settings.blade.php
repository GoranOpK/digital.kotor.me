@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8 max-w-2xl">
    @include('admin.e-payments.partials.nav')
    <a href="{{ route('admin.e-payments.payment-types.index') }}" class="text-gray-600 hover:text-gray-900">← Katalog</a>
    <h1 class="text-3xl font-bold mt-2 mb-2">e-Plaćanje — nova plaćanja</h1>
    <p class="text-sm text-gray-600 mb-6">Onemogućavanje sprečava nove tokove. Nije production audit. Nije gateway.</p>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">{{ session('success') }}</div>
    @endif

    <form method="POST" action="{{ route('admin.e-payments.settings.update') }}" class="bg-white rounded-lg shadow p-6 space-y-4">
        @csrf
        <p class="font-medium">Nova plaćanja su trenutno: {{ $newPaymentsEnabled ? 'omogućena' : 'onemogućena' }}</p>
        <input type="hidden" name="new_payments_enabled" value="0">
        <label class="flex items-center gap-2">
            <input type="checkbox" name="new_payments_enabled" value="1" @checked($newPaymentsEnabled)>
            Omogući nova plaćanja
        </label>
        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded">Sačuvaj</button>
    </form>
</div>
@endsection
