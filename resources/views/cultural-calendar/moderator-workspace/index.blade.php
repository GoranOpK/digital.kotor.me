@extends('layouts.app')

@section('content')
<div class="kk-shell mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <h1 style="font-size:28px; font-weight:700; margin:0 0 8px; color:#111827;">Moderatorski workspace</h1>
    <p class="text-sm text-gray-600 mb-4">Pristup na osnovu aktivnog moderatorskog ovlašćenja (PO-ORG-04). Nije nova platformska uloga.</p>

    @if(session('status'))
        <div class="mb-4 rounded-md bg-green-50 border border-green-200 text-green-800 px-4 py-3">{{ session('status') }}</div>
    @endif

    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50">
                <tr class="text-left text-gray-600">
                    <th class="px-4 py-3">Organizator</th>
                    <th class="px-4 py-3 text-right">Akcije</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($authorizations as $auth)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $auth->organizer->naziv }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('cultural-moderator-requests.create', $auth->organizer) }}" class="px-3 py-1.5 border border-gray-300 rounded-md">Zahtjev za Moderatora</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2" class="px-4 py-8 text-center text-gray-500">
                            @if($isEditor)
                                Nemate aktivno moderatorsko ovlašćenje; pristupate kao Urednik.
                            @else
                                Nemate aktivno ovlašćenje nad aktivnim Organizatorom.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
