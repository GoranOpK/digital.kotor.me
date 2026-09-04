@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8 max-w-4xl">
    <h1 class="text-3xl font-bold mb-2">Moja e-Plaćanja</h1>
    <p class="text-sm text-gray-600 mb-6">Istorija sopstvenih transakcija kroz e-Plaćanje.</p>

    <div class="flex flex-wrap gap-3 mb-6">
        @if($newPaymentsEnabled)
            <a href="{{ route('payments.index') }}" class="bg-indigo-600 text-white px-4 py-2 rounded inline-block">Novo e-Plaćanje</a>
        @else
            <p class="text-sm text-gray-700">Nova e-Plaćanja su trenutno nedostupna. Istorija ostaje dostupna.</p>
        @endif
    </div>

    <form method="GET" action="{{ route('payments.history') }}" class="mb-6">
        <label for="status" class="block text-sm text-gray-600 mb-1">Status</label>
        <div class="flex flex-wrap gap-2">
            <select id="status" name="status" class="border rounded px-3 py-2">
                <option value="all" @selected($statusFilter === null)>Svi</option>
                @foreach(\App\Enums\PaymentStatus::casesInBusinessOrder() as $status)
                    <option value="{{ $status->value }}" @selected($statusFilter === $status)>{{ $status->label() }}</option>
                @endforeach
            </select>
            <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded">Prikaži</button>
        </div>
    </form>

    @if($transactions->isEmpty())
        <div class="bg-white rounded-lg shadow p-6">
            <p>Još nemate e-Plaćanja.</p>
            @if($newPaymentsEnabled)
                <a href="{{ route('payments.index') }}" class="inline-block mt-4 bg-indigo-600 text-white px-4 py-2 rounded">Novo e-Plaćanje</a>
            @endif
        </div>
    @else
        <div class="overflow-x-auto bg-white rounded-lg shadow">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-left">
                    <tr>
                        <th class="px-4 py-3">Datum pokretanja</th>
                        <th class="px-4 py-3">Vrsta plaćanja</th>
                        <th class="px-4 py-3">Iznos</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Identifikator</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($transactions as $transaction)
                        @php $row = \App\Services\Payments\PaymentTransactionSnapshotView::from($transaction); @endphp
                        <tr class="border-t">
                            <td class="px-4 py-3 whitespace-nowrap">{{ $transaction->created_at?->timezone(config('app.timezone'))->format('d.m.Y. H:i') }}</td>
                            <td class="px-4 py-3">{{ $row->paymentTypeName !== '' ? $row->paymentTypeName : '—' }}</td>
                            <td class="px-4 py-3 whitespace-nowrap">{{ $row->amountWithCurrency() }}</td>
                            <td class="px-4 py-3">{{ $transaction->status->label() }}</td>
                            <td class="px-4 py-3 font-mono text-xs break-all">{{ $transaction->merchant_transaction_id }}</td>
                            <td class="px-4 py-3"><a href="{{ route('payments.result', $transaction) }}" class="text-indigo-700 hover:underline">Detalji</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">
            {{ $transactions->links() }}
        </div>
    @endif
</div>
@endsection
