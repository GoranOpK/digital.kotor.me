@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-md mx-auto bg-white dark:bg-gray-800 shadow sm:rounded-lg px-6 py-8">
        <h2 class="text-2xl font-semibold text-gray-800 dark:text-gray-100 mb-6 text-center">Prijava</h2>
        <form method="POST" action="{{ route('login') }}" class="space-y-5">
            @csrf

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email adresa</label>
                <input
                    id="email"
                    type="email"
                    name="email"
                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500"
                    required
                    autofocus
                >
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Šifra</label>
                <input
                    id="password"
                    type="password"
                    name="password"
                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500"
                    required
                >
            </div>

            <div class="flex items-center justify-between">
                <button
                    type="submit"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                >
                    Prijavi se
                </button>
                <a href="{{ route('register') }}" class="text-sm text-indigo-600 hover:text-indigo-500">
                    Nemate nalog? Registrujte se
                </a>
            </div>
        </form>
    </div>
</div>
@endsection