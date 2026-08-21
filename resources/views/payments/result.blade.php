@extends('layouts.app')

@section('content')
@php
    $status = $transaction->status;
    $title = match ($status) {
        \App\Enums\PaymentStatus::Successful => 'Uspješno plaćanje',
        \App\Enums\PaymentStatus::Failed => 'Plaćanje nije uspjelo.',
        \App\Enums\PaymentStatus::Cancelled => 'Plaćanje je otkazano.',
        default => 'Plaćanje je u obradi',
    };
@endphp
<div class="container mx-auto px-4 py-8 max-w-2xl">
    <h1 class="text-3xl font-bold mb-2">{{ $title }}</h1>
    <p class="text-sm text-gray-600 mb-6">Status: {{ $status->label() }}</p>

    <div class="bg-white rounded-lg shadow p-6 space-y-3">
        <div><span class="text-sm text-gray-500">Iznos</span><div>{{ $snapshot['amount'] ?? $transaction->amount }} {{ $snapshot['currency'] ?? $transaction->currency }}</div></div>
        <div><span class="text-sm text-gray-500">Vrsta</span><div>{{ $snapshot['payment_type_name'] ?? '' }}</div></div>
        <div><span class="text-sm text-gray-500">Račun</span><div class="font-mono">{{ $snapshot['account_number'] ?? '' }}</div></div>
        @if(!empty($snapshot['account_name']))
            <div><span class="text-sm text-gray-500">Naziv računa (snapshot)</span><div>{{ $snapshot['account_name'] }}</div></div>
        @endif
        <div><span class="text-sm text-gray-500">Identifikator transakcije</span><div class="font-mono text-sm">{{ $transaction->merchant_transaction_id }}</div></div>
    </div>

    @if($status === \App\Enums\PaymentStatus::Failed)
        <p class="text-sm text-gray-700 mt-4">Novac nije naplaćen kroz ovaj tok.</p>
    @endif

    <div class="mt-6">
        <a href="{{ route('payments.index') }}" class="bg-indigo-600 text-white px-4 py-2 rounded inline-block">Nazad na plaćanja</a>
    </div>
</div>
@endsection
