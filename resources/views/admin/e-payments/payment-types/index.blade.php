@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    @include('admin.e-payments.partials.nav')
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold">Katalog e-Plaćanja — vrste</h1>
            <p class="text-sm text-gray-600 mt-1">Lokalna administracija. Korisnički tok do pregleda je lokalni. Nije production-ready.</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('admin.e-payments.transactions.index') }}" class="px-4 py-2 border rounded">Transakcije</a>
            <a href="{{ route('admin.e-payments.settings.edit') }}" class="px-4 py-2 border rounded">Nova plaćanja</a>
            <a href="{{ route('admin.e-payments.payment-types.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">Nova vrsta</a>
        </div>
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
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Naziv</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kod</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Računi</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Dostupnost</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Akcije</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($types as $type)
                    <tr>
                        <td class="px-6 py-4">{{ $type->name }}</td>
                        <td class="px-6 py-4 font-mono text-sm">{{ $type->code }}</td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 text-xs rounded {{ $type->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700' }}">
                                {{ $type->is_active ? 'Aktivna' : 'Neaktivna' }}
                            </span>
                        </td>
                        <td class="px-6 py-4">{{ $type->active_accounts_count }} / {{ $type->accounts_count }}</td>
                        <td class="px-6 py-4">{{ $type->availabilities_count }}</td>
                        <td class="px-6 py-4 text-sm">
                            <a href="{{ route('admin.e-payments.payment-types.accounts.index', $type) }}" class="text-indigo-600 hover:text-indigo-900 mr-3">Računi</a>
                            <a href="{{ route('admin.e-payments.payment-types.availabilities.index', $type) }}" class="text-indigo-600 hover:text-indigo-900 mr-3">Dostupnost</a>
                            <a href="{{ route('admin.e-payments.payment-types.edit', $type) }}" class="text-yellow-600 hover:text-yellow-900 mr-3">Izmijeni</a>
                            @if($type->is_active)
                                <form method="POST" action="{{ route('admin.e-payments.payment-types.deactivate', $type) }}" class="inline" onsubmit="return confirm('Deaktivirati vrstu? Računi se ne brišu.');">
                                    @csrf
                                    <button type="submit" class="text-red-600 hover:text-red-800">Deaktiviraj</button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('admin.e-payments.payment-types.activate', $type) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="text-green-700 hover:text-green-900">Aktiviraj</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">Nema vrsta plaćanja. Unesite samo sintetičke test podatke.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $types->links() }}</div>
</div>
@endsection
