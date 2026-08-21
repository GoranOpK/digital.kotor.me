@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8 max-w-2xl">
    <a href="{{ route('payments.index') }}" class="text-gray-600 hover:text-gray-900">← Vrste plaćanja</a>
    <h1 class="text-3xl font-bold mt-2 mb-2">Unos iznosa</h1>
    <p class="text-sm text-gray-600 mb-2">{{ $type->name }}</p>
    <p class="text-sm text-gray-600 mb-6 font-mono">Račun: {{ $account->account_number }}</p>

    @if($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <ul class="list-disc ml-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('payments.amount.store') }}" class="bg-white rounded-lg shadow p-6 space-y-4">
        @csrf
        <div>
            <label class="block text-sm font-medium mb-1">Iznos (EUR)</label>
            <input type="text" name="amount" value="{{ old('amount', $amount) }}" inputmode="decimal" class="w-full border rounded px-3 py-2" required>
            <p class="text-xs text-gray-500 mt-1">Unesite iznos veći od 0, sa najviše dvije decimale. Sistem ne predlaže visinu obaveze.</p>
        </div>
        <div class="flex gap-3">
            <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded">Pregled</button>
            <button type="submit" form="ep-abandon" class="px-4 py-2">Odustani</button>
        </div>
    </form>
    <form id="ep-abandon" method="POST" action="{{ route('payments.abandon') }}" class="hidden">@csrf</form>
</div>
@endsection
