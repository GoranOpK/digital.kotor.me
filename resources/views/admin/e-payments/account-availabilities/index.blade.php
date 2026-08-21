@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <div>
            <a href="{{ route('admin.e-payments.payment-types.accounts.index', $type) }}" class="text-gray-600 hover:text-gray-900">← Računi</a>
            <h1 class="text-3xl font-bold mt-2">Dostupnost računa — {{ $account->account_number }}</h1>
            <p class="text-sm text-gray-600 mt-1">Račun je dostupan samo ako su i vrsta i račun aktivni i imaju poklapanje. Nije 17/41 mapiranje.</p>
        </div>
        <a href="{{ route('admin.e-payments.payment-types.accounts.availabilities.create', [$type, $account]) }}" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">Novo pravilo</a>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">{{ session('success') }}</div>
    @endif

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Korisnička kategorija</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Prebivalište</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Akcije</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($rules as $rule)
                    <tr>
                        <td class="px-6 py-4">{{ \App\Support\UserType::displayLabel($rule->user_type) }}</td>
                        <td class="px-6 py-4">
                            @if($rule->residential_status === 'resident')
                                Rezident
                            @elseif($rule->residential_status === 'non-resident')
                                Nerezident
                            @else
                                Nije primjenjivo
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 text-xs rounded {{ $rule->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700' }}">
                                {{ $rule->is_active ? 'Aktivno' : 'Neaktivno' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm">
                            @if($rule->is_active)
                                <form method="POST" action="{{ route('admin.e-payments.payment-types.accounts.availabilities.deactivate', [$type, $account, $rule]) }}" class="inline" onsubmit="return confirm('Deaktivirati pravilo? Red se ne briše.');">
                                    @csrf
                                    <button type="submit" class="text-red-600 hover:text-red-800">Deaktiviraj</button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('admin.e-payments.payment-types.accounts.availabilities.activate', [$type, $account, $rule]) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="text-green-700 hover:text-green-900">Aktiviraj</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-4 text-center text-gray-500">Nema pravila dostupnosti za ovaj račun.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
