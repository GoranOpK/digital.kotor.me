@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    @include('admin.e-payments.partials.nav')
    <h1 class="text-3xl font-bold mb-2">e-Plaćanje — transakcije</h1>
    <p class="text-sm text-gray-600 mb-6">Operativni pregled. Read-only. Nije production-ready.</p>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">{{ $errors->first() }}</div>
    @endif

    <form method="GET" action="{{ route('admin.e-payments.transactions.index') }}" class="bg-white rounded-lg shadow p-4 mb-6 grid grid-cols-1 md:grid-cols-3 gap-3">
        <div>
            <label for="status" class="block text-sm text-gray-600 mb-1">Status</label>
            <select id="status" name="status" class="border rounded px-3 py-2 w-full">
                <option value="all" @selected(($filters['status'] ?? 'all') === 'all' || ($filters['status'] ?? '') === '')>Svi</option>
                @foreach(\App\Enums\PaymentStatus::casesInBusinessOrder() as $status)
                    <option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="payment_type_id" class="block text-sm text-gray-600 mb-1">Vrsta plaćanja</label>
            <select id="payment_type_id" name="payment_type_id" class="border rounded px-3 py-2 w-full">
                <option value="">Sve</option>
                @foreach($types as $type)
                    <option value="{{ $type->id }}" @selected((string) ($filters['payment_type_id'] ?? '') === (string) $type->id)>{{ $type->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="q" class="block text-sm text-gray-600 mb-1">Merchant ID / UUID</label>
            <input id="q" name="q" value="{{ $filters['q'] ?? '' }}" class="border rounded px-3 py-2 w-full" maxlength="64">
        </div>
        <div>
            <label for="from" class="block text-sm text-gray-600 mb-1">Od datuma</label>
            <input id="from" type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="border rounded px-3 py-2 w-full">
        </div>
        <div>
            <label for="to" class="block text-sm text-gray-600 mb-1">Do datuma</label>
            <input id="to" type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="border rounded px-3 py-2 w-full">
        </div>
        <div>
            <label for="user" class="block text-sm text-gray-600 mb-1">Korisnik (ime ili email)</label>
            <input id="user" name="user" value="{{ $filters['user'] ?? '' }}" class="border rounded px-3 py-2 w-full" maxlength="100">
        </div>
        <div class="md:col-span-3">
            <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded">Prikaži</button>
        </div>
    </form>

    <div class="overflow-x-auto bg-white rounded-lg shadow">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-left">
                <tr>
                    <th class="px-4 py-3">Datum pokretanja</th>
                    <th class="px-4 py-3">Uplatilac</th>
                    <th class="px-4 py-3">Vrsta</th>
                    <th class="px-4 py-3">Iznos</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Identifikator</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $transaction)
                    @php $row = \App\Services\Payments\PaymentTransactionSnapshotView::from($transaction); @endphp
                    <tr class="border-t">
                        <td class="px-4 py-3 whitespace-nowrap">{{ $transaction->created_at?->timezone(config('app.timezone'))->format('d.m.Y. H:i') }}</td>
                        <td class="px-4 py-3">{{ $row->payerLabel !== '' ? $row->payerLabel : ($transaction->user->name ?? '—') }}</td>
                        <td class="px-4 py-3">{{ $row->paymentTypeName !== '' ? $row->paymentTypeName : '—' }}</td>
                        <td class="px-4 py-3 whitespace-nowrap">{{ $row->amountWithCurrency() }}</td>
                        <td class="px-4 py-3">{{ $transaction->status->label() }}</td>
                        <td class="px-4 py-3 font-mono text-xs break-all">{{ $transaction->merchant_transaction_id }}</td>
                        <td class="px-4 py-3"><a href="{{ route('admin.e-payments.transactions.show', $transaction) }}" class="text-indigo-700 hover:underline">Detalji</a></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-6 text-center text-gray-500">Nema transakcija za zadate filtere.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $transactions->links() }}</div>
</div>
@endsection
