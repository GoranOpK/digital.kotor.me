@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Administratorski Dashboard</h1>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600" style="background:#ef4444; color:#fff; padding:8px 16px; border-radius:6px; border:none; cursor:pointer;">
                Odjava
            </button>
        </form>
    </div>

    <!-- Statistike -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-gray-500 text-sm font-medium mb-2">Ukupno korisnika</h3>
            <p class="text-3xl font-bold text-gray-900">{{ $stats['total_users'] }}</p>
            <p class="text-sm text-gray-500 mt-1">Aktivnih: {{ $stats['active_users'] }}</p>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-gray-500 text-sm font-medium mb-2">Konkursi</h3>
            <p class="text-3xl font-bold text-gray-900">{{ $stats['total_competitions'] }}</p>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-gray-500 text-sm font-medium mb-2">Prijave</h3>
            <p class="text-3xl font-bold text-gray-900">{{ $stats['total_applications'] }}</p>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-gray-500 text-sm font-medium mb-2">Tenderi</h3>
            <p class="text-3xl font-bold text-gray-900">{{ $stats['total_tenders'] }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Najnoviji korisnici -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-6 border-b">
                <h2 class="text-xl font-semibold">Najnoviji korisnici</h2>
            </div>
            <div class="p-6">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ime</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Akcije</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($recent_users as $user)
                                <tr>
                                    <td class="px-4 py-3 whitespace-nowrap">{{ $user->name }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap">{{ $user->email }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs rounded {{ $user->activation_status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                            {{ $user->activation_status === 'active' ? 'Aktivan' : 'Deaktiviran' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <a href="{{ route('admin.users.show', $user) }}" class="text-indigo-600 hover:text-indigo-900">Pregled</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-3 text-center text-gray-500">Nema korisnika</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">
                    <a href="{{ route('admin.users.index') }}" class="text-indigo-600 hover:text-indigo-900">Prikaži sve korisnike →</a>
                </div>
            </div>
        </div>

        <!-- Najnovije prijave -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-6 border-b">
                <h2 class="text-xl font-semibold">Najnovije prijave na konkurse</h2>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    @forelse($recent_applications as $application)
                        <div class="border-b pb-4 last:border-b-0">
                            <p class="font-medium">{{ $application->user->name ?? 'N/A' }}</p>
                            <p class="text-sm text-gray-600">{{ $application->competition->title ?? 'N/A' }}</p>
                            <p class="text-xs text-gray-500 mt-1">{{ $application->created_at->format('d.m.Y H:i') }}</p>
                        </div>
                    @empty
                        <p class="text-gray-500">Nema prijava</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

