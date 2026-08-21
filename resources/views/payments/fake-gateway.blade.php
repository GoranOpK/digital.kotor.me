@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8 max-w-2xl">
    <div class="bg-amber-50 border border-amber-300 text-amber-950 px-4 py-3 rounded mb-6">
        <p class="font-bold">LOKALNI SIMULATOR PLAĆANJA</p>
        <p class="text-sm mt-1">Ovo nije stvarni payment gateway.</p>
    </div>

    <div class="bg-white rounded-lg shadow p-6 space-y-2 mb-6">
        <div><span class="text-sm text-gray-500">Iznos</span><div>{{ $transaction->amount }} {{ $transaction->currency }}</div></div>
        <div><span class="text-sm text-gray-500">Referenca</span><div class="font-mono text-sm">{{ $transaction->merchant_transaction_id }}</div></div>
        <div><span class="text-sm text-gray-500">Status</span><div>{{ $transaction->status->label() }}</div></div>
    </div>

    <div class="space-y-3">
        <form method="POST" action="{{ $successUrl }}">
            @csrf
            <button type="submit" class="w-full bg-green-700 text-white px-4 py-2 rounded">Simuliraj uspješno plaćanje</button>
        </form>
        <form method="POST" action="{{ $failedUrl }}">
            @csrf
            <button type="submit" class="w-full bg-red-700 text-white px-4 py-2 rounded">Simuliraj neuspješno plaćanje</button>
        </form>
        <form method="POST" action="{{ $cancelledUrl }}">
            @csrf
            <button type="submit" class="w-full bg-indigo-600 text-white px-4 py-2 rounded">Simuliraj otkazivanje</button>
        </form>
    </div>
</div>
@endsection
