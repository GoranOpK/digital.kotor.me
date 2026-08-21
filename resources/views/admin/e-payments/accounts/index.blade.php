@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <div>
            <a href="{{ route('admin.e-payments.payment-types.index') }}" class="text-gray-600 hover:text-gray-900">← Vrste</a>
            <h1 class="text-3xl font-bold mt-2">Računi — {{ $type->name }}</h1>
            <p class="text-sm text-gray-600 mt-1">Broj računa je nepromjenjiv nakon kreiranja. Koristite samo sintetičke brojeve.</p>
        </div>
        <a href="{{ route('admin.e-payments.payment-types.accounts.create', $type) }}" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">Novi račun</a>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">{{ session('error') }}</div>
    @endif

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Broj računa</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Naziv</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Akcije</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($accounts as $account)
                    <tr>
                        <td class="px-6 py-4 font-mono text-sm">{{ $account->account_number }}</td>
                        <td class="px-6 py-4">{{ $account->name ?: '—' }}</td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 text-xs rounded {{ $account->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700' }}">
                                {{ $account->is_active ? 'Aktivan' : 'Neaktivan' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm">
                            <a href="{{ route('admin.e-payments.payment-types.accounts.edit', [$type, $account]) }}" class="text-yellow-600 hover:text-yellow-900 mr-3">Izmijeni</a>
                            <a href="{{ route('admin.e-payments.payment-types.accounts.availabilities.index', [$type, $account]) }}" class="text-indigo-600 hover:text-indigo-900 mr-3">Dostupnost</a>
                            @if($account->is_active)
                                <form method="POST" action="{{ route('admin.e-payments.payment-types.accounts.deactivate', [$type, $account]) }}" class="inline" onsubmit="return confirm('Deaktivirati račun? Red se ne briše.');">
                                    @csrf
                                    <button type="submit" class="text-red-600 hover:text-red-800">Deaktiviraj</button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('admin.e-payments.payment-types.accounts.activate', [$type, $account]) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="text-green-700 hover:text-green-900">Aktiviraj</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-4 text-center text-gray-500">Nema računa za ovu vrstu.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $accounts->links() }}</div>
</div>
@endsection
