@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8 max-w-3xl">
    <a href="{{ route('payments.index') }}" class="text-gray-600 hover:text-gray-900">← Vrste plaćanja</a>
    <h1 class="text-3xl font-bold mt-2 mb-2">Izbor računa</h1>
    <p class="text-sm text-gray-600 mb-6">{{ $type->name }} — izaberite jedan od dostupnih računa.</p>

    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">{{ session('error') }}</div>
    @endif

    <form method="POST" action="{{ route('payments.account.store', $type) }}" class="bg-white rounded-lg shadow p-6 space-y-4">
        @csrf
        @foreach($accounts as $account)
            <label class="flex items-start gap-3 border rounded p-3">
                <input type="radio" name="payment_account_id" value="{{ $account->id }}" required>
                <span>
                    <span class="font-mono">{{ $account->account_number }}</span>
                    @if($account->name)
                        <span class="block text-sm text-gray-600">{{ $account->name }}</span>
                    @endif
                </span>
            </label>
        @endforeach
        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded">Nastavi</button>
    </form>
</div>
@endsection
