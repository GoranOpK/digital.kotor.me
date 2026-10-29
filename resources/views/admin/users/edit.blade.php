@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Izmjena korisnika</h1>
        <a href="{{ route('admin.users.show', $user) }}" class="text-gray-600 hover:text-gray-900">← Nazad</a>
    </div>

    @if($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

            @if($user->isSuperadmin())
                <div class="bg-white rounded-lg shadow p-6">
                    <p class="text-sm text-gray-800 mb-4">
                        Superadmin nalog se ne uređuje kroz Users administraciju. Uloga i status aktivacije ostaju provisioning-controlled.
                    </p>
                    <p class="text-sm text-gray-600 mb-1"><strong>Uloga:</strong> {{ $user->role->display_name ?? 'Super administrator' }}</p>
                    <p class="text-sm text-gray-600 mb-4"><strong>Status:</strong> {{ $user->activation_status === 'active' ? 'Aktivan' : 'Deaktiviran' }}</p>
                    <a href="{{ route('admin.users.show', $user) }}" class="bg-gray-300 text-gray-700 px-6 py-2 rounded hover:bg-gray-400">
                        Nazad
                    </a>
                </div>
            @else
    <div class="bg-white rounded-lg shadow p-6">
        <form method="POST" action="{{ route('admin.users.update', $user) }}">
            @csrf
            @method('PUT')

            @php
                $adminUsesAccountContact = $subjectIdentity->access === \App\Identity\Runtime\IdentityAccess::ACCOUNT_ONLY
                    || ! config('identity.canonical_read');
                $adminFirstName = $adminUsesAccountContact
                    ? ($subjectIdentity->firstName ?? $user->first_name)
                    : $subjectIdentity->firstName;
                $adminLastName = $adminUsesAccountContact
                    ? ($subjectIdentity->lastName ?? $user->last_name)
                    : $subjectIdentity->lastName;
                $adminPhone = $adminUsesAccountContact
                    ? ($subjectIdentity->phone ?? $user->phone)
                    : $subjectIdentity->phone;
            @endphp
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="first_name" class="block text-sm font-medium text-gray-700 mb-2">Ime</label>
                    <input type="text" name="first_name" id="first_name" value="{{ old('first_name', $adminFirstName) }}" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                </div>

                <div>
                    <label for="last_name" class="block text-sm font-medium text-gray-700 mb-2">Prezime</label>
                    <input type="text" name="last_name" id="last_name" value="{{ old('last_name', $adminLastName) }}" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                    <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                </div>

                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">Telefon</label>
                    <input type="text" name="phone" id="phone" value="{{ old('phone', $adminPhone) }}" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <div>
                    <label for="role_id" class="block text-sm font-medium text-gray-700 mb-2">Uloga</label>
                    <select name="role_id" id="role_id" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}" {{ $user->role_id == $role->id ? 'selected' : '' }}>
                                {{ $role->display_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="activation_status" class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <select name="activation_status" id="activation_status" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                        <option value="active" {{ $user->activation_status === 'active' ? 'selected' : '' }}>Aktivan</option>
                        <option value="deactivated" {{ $user->activation_status === 'deactivated' ? 'selected' : '' }}>Deaktiviran</option>
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Nova lozinka (ostavite prazno ako ne želite da je mijenjate)</label>
                    <input type="password" name="password" id="password" autocomplete="new-password"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <div class="md:col-span-2">
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">Potvrda lozinke</label>
                    <input type="password" name="password_confirmation" id="password_confirmation" autocomplete="new-password"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>

            @if(($user->role->name ?? null) === 'korisnik')
            <div class="mt-6 rounded-md border border-amber-300 bg-amber-50 p-4">
                <h2 class="text-base font-semibold text-gray-900 mb-2">MMSP testni pristup</h2>
                <input type="hidden" name="mmsp_test_access" value="0">
                <label class="flex items-start gap-2 text-sm text-gray-800">
                    <input
                        type="checkbox"
                        name="mmsp_test_access"
                        value="1"
                        class="mt-1"
                        {{ old('mmsp_test_access', ! empty($mmspTestAccessAllowed) ? '1' : '0') == '1' ? 'checked' : '' }}
                    >
                    <span>Dozvoli pristup MMSP testnom režimu</span>
                </label>
                @if(! empty($mmspTestAccessLatest))
                    <p class="mt-2 text-xs text-gray-600">
                        Posljednja promjena:
                        {{ $mmspTestAccessLatest->action === \App\Models\MmspTestAccessGrant::ACTION_GRANTED ? 'odobreno' : 'ukinuto' }}
                        ({{ $mmspTestAccessLatest->reason === \App\Models\MmspTestAccessGrant::REASON_ROLE_CHANGED ? 'promjena uloge' : 'ručno' }}),
                        {{ $mmspTestAccessLatest->created_at?->timezone(config('app.timezone'))->format('d.m.Y H:i') }}
                        @if($mmspTestAccessLatest->actor)
                            — {{ $mmspTestAccessLatest->actor->name }}
                        @endif
                    </p>
                @endif
            </div>
            @endif

            <div class="mt-6 flex gap-4">
                <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded hover:bg-indigo-700">
                    Sačuvaj izmjene
                </button>
                <a href="{{ route('admin.users.show', $user) }}" class="bg-gray-300 text-gray-700 px-6 py-2 rounded hover:bg-gray-400">
                    Otkaži
                </a>
            </div>
        </form>
    </div>
            @endif
</div>
@endsection

