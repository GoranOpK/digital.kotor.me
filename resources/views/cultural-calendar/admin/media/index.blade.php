@extends('layouts.app')

@section('content')
<div class="kk-shell mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:16px; flex-wrap:wrap;">
        <h1 style="font-size:28px; font-weight:700; margin:0; color:#111827;">Katalog medija</h1>
        <a href="{{ route('cultural-media.create') }}" style="display:inline-block; background:#b91c1c; color:#fff; text-decoration:none; padding:10px 14px; border-radius:8px; font-weight:600;">
            + Novi medij
        </a>
    </div>

    @if(session('status'))
        <div class="mb-4 rounded-md bg-green-50 border border-green-200 text-green-800 px-4 py-3">
            {{ session('status') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-4 rounded-md bg-red-50 border border-red-200 text-red-800 px-4 py-3">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50">
                    <tr class="text-left text-gray-600">
                        <th class="px-4 py-3">Pregled</th>
                        <th class="px-4 py-3">Naziv</th>
                        <th class="px-4 py-3">Namjena</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Akcije</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($mediaItems as $item)
                        <tr>
                            <td class="px-4 py-3">
                                <img src="{{ $item->publicUrl() }}" alt="{{ $item->alt_tekst }}" class="h-14 w-20 object-cover rounded border border-gray-200">
                            </td>
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $item->naziv }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $item->purposeLabel() }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $item->statusLabel() }}</td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2 flex-wrap">
                                    <a href="{{ route('cultural-media.edit', $item) }}" class="px-3 py-1.5 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                                        Uredi
                                    </a>
                                    @if($item->isActive())
                                        <form method="POST" action="{{ route('cultural-media.deactivate', $item) }}">
                                            @csrf
                                            <button type="submit" class="px-3 py-1.5 border border-amber-300 rounded-md text-amber-800 hover:bg-amber-50">
                                                Deaktiviraj
                                            </button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('cultural-media.activate', $item) }}">
                                            @csrf
                                            <button type="submit" class="px-3 py-1.5 border border-green-300 rounded-md text-green-800 hover:bg-green-50">
                                                Aktiviraj
                                            </button>
                                        </form>
                                    @endif
                                    <form method="POST" action="{{ route('cultural-media.destroy', $item) }}" onsubmit="return confirm('Trajno obrisati medij i fajl?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="px-3 py-1.5 border border-red-300 rounded-md text-red-800 hover:bg-red-50">
                                            Obriši
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                                Nema unesenih medija u katalogu.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-gray-100">
            {{ $mediaItems->links() }}
        </div>
    </div>
</div>
@endsection
