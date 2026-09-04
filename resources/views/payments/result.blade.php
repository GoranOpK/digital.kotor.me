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
    <p class="text-sm mb-4">
        <a href="{{ route('payments.history') }}" class="text-indigo-700 hover:underline">← Moja e-Plaćanja</a>
    </p>
    <h1 class="text-3xl font-bold mb-2">{{ $title }}</h1>
    <p class="text-sm text-gray-600 mb-6">Status: {{ $status->label() }}</p>

    @if($status === \App\Enums\PaymentStatus::Processing)
        <p class="text-sm text-gray-700 mb-4">Status plaćanja još nije konačno potvrđen.</p>
    @endif

    <div class="bg-white rounded-lg shadow p-6 space-y-3">
        <div><span class="text-sm text-gray-500">Datum pokretanja</span><div>{{ $transaction->created_at?->timezone(config('app.timezone'))->format('d.m.Y. H:i') }}</div></div>
        @if($successfulAtLabel)
            <div><span class="text-sm text-gray-500">Datum uspješne transakcije</span><div>{{ $successfulAtLabel }}</div></div>
        @endif
        @if($snapshot->payerLabel !== '')
            <div><span class="text-sm text-gray-500">Uplatilac</span><div>{{ $snapshot->payerLabel }}</div></div>
        @endif
        @if($snapshot->userTypeLabel !== '')
            <div><span class="text-sm text-gray-500">Korisnička kategorija</span><div>{{ $snapshot->userTypeLabel }}</div></div>
        @endif
        <div><span class="text-sm text-gray-500">Vrsta plaćanja</span><div>{{ $snapshot->paymentTypeName !== '' ? $snapshot->paymentTypeName : '—' }}</div></div>
        <div><span class="text-sm text-gray-500">Račun primaoca</span><div class="font-mono">{{ $snapshot->accountNumber !== '' ? $snapshot->accountNumber : '—' }}</div></div>
        @if($snapshot->accountName !== '')
            <div><span class="text-sm text-gray-500">Naziv računa</span><div>{{ $snapshot->accountName }}</div></div>
        @endif
        <div><span class="text-sm text-gray-500">Iznos</span><div>{{ $snapshot->amountWithCurrency() }}</div></div>
        <div><span class="text-sm text-gray-500">Identifikator transakcije</span><div class="font-mono text-sm">{{ $transaction->merchant_transaction_id }}</div></div>
        @if(is_string($transaction->gateway_reference) && $transaction->gateway_reference !== '')
            <div><span class="text-sm text-gray-500">Referenca provajdera</span><div class="font-mono text-sm">{{ $transaction->gateway_reference }}</div></div>
        @endif
    </div>

    @if(!empty($timeline))
        <div class="bg-white rounded-lg shadow p-6 mt-4">
            <h2 class="font-semibold mb-3">Tok plaćanja</h2>
            <ol class="space-y-2 text-sm">
                @foreach($timeline as $step)
                    <li>
                        <span class="text-gray-500">{{ $step['occurred_at'] }}</span>
                        <div>{{ $step['label'] }}</div>
                    </li>
                @endforeach
            </ol>
        </div>
    @endif

    @if($status === \App\Enums\PaymentStatus::Successful)
        <div class="mt-4 space-y-2">
            <a href="{{ route('payments.confirmation.pdf', $transaction) }}" class="bg-indigo-600 text-white px-4 py-2 rounded inline-block">Preuzmi potvrdu (PDF)</a>
            @if(!empty($confirmationEmailSent))
                <p class="text-sm text-gray-700">Potvrda je poslata na email.</p>
            @endif
        </div>
    @endif

    @if($status === \App\Enums\PaymentStatus::Failed)
        <p class="text-sm text-gray-700 mt-4">Novac nije naplaćen kroz ovaj tok.</p>
    @endif

    @if($status === \App\Enums\PaymentStatus::Cancelled)
        <p class="text-sm text-gray-700 mt-4">Plaćanje je otkazano. Novac nije naplaćen kroz ovaj tok.</p>
    @endif

    <div class="mt-6 flex flex-wrap gap-3">
        <a href="{{ route('payments.history') }}" class="bg-gray-800 text-white px-4 py-2 rounded inline-block">Moja e-Plaćanja</a>
        @if(!empty($newPaymentsEnabled))
            <a href="{{ route('payments.index') }}" class="bg-indigo-600 text-white px-4 py-2 rounded inline-block">Novo e-Plaćanje</a>
        @endif
    </div>
</div>
@endsection
