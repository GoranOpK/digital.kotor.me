@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8 max-w-3xl">
    <h1 class="text-3xl font-bold mb-2">e-Plaćanje</h1>
    <div class="bg-white rounded-lg shadow p-6">
        <p>Nova e-Plaćanja su privremeno nedostupna.</p>
        <p class="mt-4">
            <a href="{{ route('payments.history') }}" class="text-indigo-700 hover:underline">Moja e-Plaćanja</a>
        </p>
    </div>
</div>
@endsection
