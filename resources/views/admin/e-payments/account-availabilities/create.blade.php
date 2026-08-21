@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8 max-w-2xl">
    <a href="{{ route('admin.e-payments.payment-types.accounts.availabilities.index', [$type, $account]) }}" class="text-gray-600 hover:text-gray-900">← Dostupnost računa</a>
    <h1 class="text-3xl font-bold mt-2 mb-2">Novo pravilo dostupnosti računa</h1>
    <p class="text-sm text-gray-600 mb-6">Samo kanonskih 8 kategorija. Za pravna lica status prebivališta nije dozvoljen.</p>

    @include('admin.e-payments.partials.availability-form', [
        'action' => route('admin.e-payments.payment-types.accounts.availabilities.store', [$type, $account]),
        'cancelUrl' => route('admin.e-payments.payment-types.accounts.availabilities.index', [$type, $account]),
        'userTypes' => $userTypes,
        'naturalPersonTypes' => $naturalPersonTypes,
    ])
</div>
@endsection
