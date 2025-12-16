@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Pregled korisnika</h1>
        <div>
            <a href="{{ route('admin.users.index') }}" class="text-gray-600 hover:text-gray-900 mr-4">← Nazad</a>
            <a href="{{ route('admin.users.edit', $user) }}" class="bg-yellow-500 text-white px-4 py-2 rounded hover:bg-yellow-600">Izmeni</a>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white rounded-lg shadow p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <h3 class="text-lg font-semibold mb-4">Osnovni podaci</h3>
                <dl class="space-y-2">
                    <dt class="text-sm font-medium text-gray-500">Ime i prezime</dt>
                    <dd class="text-sm text-gray-900">{{ $user->name }}</dd>

                    <dt class="text-sm font-medium text-gray-500">Email</dt>
                    <dd class="text-sm text-gray-900">{{ $user->email }}</dd>

                    <dt class="text-sm font-medium text-gray-500">Telefon</dt>
                    <dd class="text-sm text-gray-900">{{ $user->phone ?? 'N/A' }}</dd>

                    <dt class="text-sm font-medium text-gray-500">Uloga</dt>
                    <dd class="text-sm text-gray-900">
                        <span class="px-2 py-1 text-xs rounded bg-blue-100 text-blue-800">
                            {{ $user->role->display_name ?? 'N/A' }}
                        </span>
                    </dd>

                    <dt class="text-sm font-medium text-gray-500">Status</dt>
                    <dd class="text-sm text-gray-900">
                        <span class="px-2 py-1 text-xs rounded {{ $user->activation_status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $user->activation_status === 'active' ? 'Aktivan' : 'Deaktiviran' }}
                        </span>
                    </dd>
                </dl>
            </div>

            <div>
                <h3 class="text-lg font-semibold mb-4">Dodatni podaci</h3>
                <dl class="space-y-2">
                    <dt class="text-sm font-medium text-gray-500">Tip korisnika</dt>
                    <dd class="text-sm text-gray-900">{{ $user->user_type ?? 'N/A' }}</dd>

                    <dt class="text-sm font-medium text-gray-500">Status prebivališta</dt>
                    <dd class="text-sm text-gray-900">{{ $user->residential_status ?? 'N/A' }}</dd>

                    <dt class="text-sm font-medium text-gray-500">Email verifikovan</dt>
                    <dd class="text-sm text-gray-900">
                        @if($user->email_verified_at)
                            <span class="text-green-600">Da ({{ $user->email_verified_at->format('d.m.Y H:i') }})</span>
                        @else
                            <span class="text-red-600">Ne</span>
                        @endif
                    </dd>

                    <dt class="text-sm font-medium text-gray-500">Registrovan</dt>
                    <dd class="text-sm text-gray-900">{{ $user->created_at->format('d.m.Y H:i') }}</dd>

                    <dt class="text-sm font-medium text-gray-500">Poslednja izmena</dt>
                    <dd class="text-sm text-gray-900">{{ $user->updated_at->format('d.m.Y H:i') }}</dd>
                </dl>
            </div>
        </div>

        <div class="mt-6 pt-6 border-t">
            <h3 class="text-lg font-semibold mb-4">Akcije</h3>
            <div class="flex gap-4">
                @if($user->activation_status === 'active')
                    <form method="POST" action="{{ route('admin.users.deactivate', $user) }}">
                        @csrf
                        <button type="submit" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">
                            Deaktiviraj korisnika
                        </button>
                    </form>
                @else
                    <form method="POST" action="{{ route('admin.users.activate', $user) }}">
                        @csrf
                        <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
                            Aktiviraj korisnika
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

