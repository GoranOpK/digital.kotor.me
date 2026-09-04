@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8 max-w-2xl">
    <h1 class="text-3xl font-bold mb-2">Pregled naloga</h1>
    <p class="text-sm text-gray-600 mb-6">Provjerite podatke. Transakcija se kreira tek kada izričito pokrenete plaćanje.</p>

    <div class="bg-white rounded-lg shadow p-6 space-y-3">
        <div><span class="text-sm text-gray-500">Uplatilac</span><div>{{ $payer }}</div></div>
        <div><span class="text-sm text-gray-500">Korisnička kategorija</span><div>{{ $userTypeLabel }}</div></div>
        <div><span class="text-sm text-gray-500">Vrsta plaćanja</span><div>{{ $type->name }}</div></div>
        <div><span class="text-sm text-gray-500">Račun</span><div class="font-mono">{{ $account->account_number }}</div></div>
        <div><span class="text-sm text-gray-500">Iznos</span><div>{{ $amount }} {{ $currency }}</div></div>
        <div><span class="text-sm text-gray-500">Dodatna provizija korisniku</span><div>0,00 EUR</div></div>
    </div>

    <div class="bg-yellow-50 border border-yellow-200 text-yellow-900 px-4 py-3 rounded mt-4 text-sm">
        Pregled nije potpuna uplatnica. Svrha, poziv na broj, model i šifra plaćanja nijesu još konfigurisani i zato nijesu prikazani. Lokalni simulator nije stvarni payment gateway.
    </div>

    <div class="flex flex-wrap gap-3 mt-6">
        <form method="POST" action="{{ route('payments.launch') }}">
            @csrf
            <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded">Pokreni plaćanje</button>
        </form>
        <a href="{{ route('payments.amount.edit') }}" class="bg-gray-200 px-4 py-2 rounded">Nazad / Izmijeni</a>
        <form method="POST" action="{{ route('payments.abandon') }}">
            @csrf
            <button type="submit" class="px-4 py-2">Odustani</button>
        </form>
    </div>
</div>
@endsection
