@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8 max-w-3xl">
    @include('admin.e-payments.partials.nav')
    <p class="text-sm mb-4"><a href="{{ route('admin.e-payments.transactions.index') }}" class="text-indigo-700 hover:underline">← Transakcije</a></p>
    <h1 class="text-3xl font-bold mb-2">Transakcija</h1>
    <p class="text-sm text-gray-600 mb-6">Read-only operativni detalj. Status: {{ $transaction->status->label() }}</p>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">{{ session('error') }}</div>
    @endif

    <div class="bg-white rounded-lg shadow p-6 space-y-3 mb-4">
        <h2 class="font-semibold">Identitet</h2>
        <div><span class="text-sm text-gray-500">UUID</span><div class="font-mono text-sm">{{ $transaction->uuid }}</div></div>
        <div><span class="text-sm text-gray-500">Identifikator transakcije</span><div class="font-mono text-sm">{{ $transaction->merchant_transaction_id }}</div></div>
        @if(is_string($transaction->gateway_reference) && $transaction->gateway_reference !== '')
            <div><span class="text-sm text-gray-500">Referenca provajdera</span><div class="font-mono text-sm">{{ $transaction->gateway_reference }}</div></div>
        @endif
        <div><span class="text-sm text-gray-500">Status</span><div>{{ $transaction->status->label() }}</div></div>
        @if($configuredProvider !== '')
            <div><span class="text-sm text-gray-500">Trenutno konfigurisani provajder</span><div>{{ $configuredProvider }}</div></div>
        @endif
    </div>

    <div class="bg-white rounded-lg shadow p-6 space-y-3 mb-4">
        <h2 class="font-semibold">Plaćanje (snapshot)</h2>
        <div><span class="text-sm text-gray-500">Datum pokretanja</span><div>{{ $transaction->created_at?->timezone(config('app.timezone'))->format('d.m.Y. H:i') }}</div></div>
        @if($successfulAtLabel)
            <div><span class="text-sm text-gray-500">Datum uspješne transakcije</span><div>{{ $successfulAtLabel }}</div></div>
        @endif
        <div><span class="text-sm text-gray-500">Iznos</span><div>{{ $snapshot->amountWithCurrency() }}</div></div>
        <div><span class="text-sm text-gray-500">Vrsta plaćanja</span><div>{{ $snapshot->paymentTypeName !== '' ? $snapshot->paymentTypeName : '—' }}</div></div>
        <div><span class="text-sm text-gray-500">Račun primaoca</span><div class="font-mono">{{ $snapshot->accountNumber !== '' ? $snapshot->accountNumber : '—' }}</div></div>
        @if($snapshot->accountName !== '')
            <div><span class="text-sm text-gray-500">Naziv računa</span><div>{{ $snapshot->accountName }}</div></div>
        @endif
    </div>

    <div class="bg-white rounded-lg shadow p-6 space-y-3 mb-4">
        <h2 class="font-semibold">Uplatilac</h2>
        @if($snapshot->payerLabel !== '')
            <div><span class="text-sm text-gray-500">Uplatilac (snapshot)</span><div>{{ $snapshot->payerLabel }}</div></div>
        @endif
        @if($snapshot->userTypeLabel !== '')
            <div><span class="text-sm text-gray-500">Korisnička kategorija (snapshot)</span><div>{{ $snapshot->userTypeLabel }}</div></div>
        @endif
        @if($transaction->user)
            <div><span class="text-sm text-gray-500">Korisnik (tekući zapis)</span><div>{{ $transaction->user->name }}</div></div>
            <div><span class="text-sm text-gray-500">Email (tekući zapis)</span><div>{{ $transaction->user->email }}</div></div>
        @endif
    </div>

    <div class="bg-white rounded-lg shadow p-6 mb-4">
        <h2 class="font-semibold mb-3">Tok događaja</h2>
        <ol class="space-y-3 text-sm">
            @foreach($timeline as $step)
                <li>
                    <span class="text-gray-500">{{ $step['occurred_at'] }}</span>
                    <div>{{ $step['label'] }}</div>
                    @if(!empty($step['metadata']))
                        <div class="text-xs text-gray-600">
                            @foreach($step['metadata'] as $key => $value)
                                <div>{{ $key }}: {{ $value }}</div>
                            @endforeach
                        </div>
                    @endif
                </li>
            @endforeach
        </ol>
    </div>

    <div class="bg-white rounded-lg shadow p-6 mb-4">
        <h2 class="font-semibold mb-3">Potvrda (dostava)</h2>
        @forelse($transaction->confirmationDeliveries as $delivery)
            <div class="text-sm space-y-1 mb-3">
                <div>Kanal: {{ $delivery->channel }}</div>
                <div>Status: {{ $delivery->status->value }}</div>
                <div>Primalac: {{ $delivery->recipient_email }}</div>
                @if($delivery->sent_at)
                    <div>Poslato: {{ $delivery->sent_at->timezone(config('app.timezone'))->format('d.m.Y. H:i') }}</div>
                @endif
                @if($delivery->failed_at)
                    <div>Neuspjeh: {{ $delivery->failed_at->timezone(config('app.timezone'))->format('d.m.Y. H:i') }}</div>
                @endif
                @if(is_string($delivery->error_class) && $delivery->error_class !== '')
                    <div>Greška: {{ $delivery->error_class }}</div>
                @endif
            </div>
        @empty
            <p class="text-sm text-gray-600">Nema zapisa o dostavi potvrde.</p>
        @endforelse
    </div>

    @if($canInquire)
        <form method="POST" action="{{ route('admin.e-payments.transactions.check-status', $transaction) }}" class="mb-6">
            @csrf
            <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded">Provjeri status</button>
        </form>
    @endif
</div>
@endsection
