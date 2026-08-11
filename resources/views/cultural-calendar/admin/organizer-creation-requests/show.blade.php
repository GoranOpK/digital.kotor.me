@extends('layouts.app')

@section('content')
<div class="kk-shell mx-auto px-4 sm:px-6 lg:px-8 py-6 max-w-3xl">
    <h1 style="font-size:28px; font-weight:700; margin:0 0 16px; color:#111827;">Zahtjev #{{ $requestItem->id }}</h1>

    @if($errors->any())
        <div class="mb-4 rounded-md bg-red-50 border border-red-200 text-red-800 px-4 py-3">
            <ul class="list-disc list-inside">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="bg-white rounded-lg border border-gray-200 p-6 space-y-2 mb-6 text-sm">
        <p><strong>Status:</strong> {{ $requestItem->statusLabel() }}</p>
        <p><strong>Naziv:</strong> {{ $requestItem->proposed_naziv }}</p>
        <p><strong>Opis:</strong> {{ $requestItem->proposed_opis ?: '—' }}</p>
        <p><strong>E-mail:</strong> {{ $requestItem->proposed_contact_email ?: '—' }}</p>
        <p><strong>Telefon:</strong> {{ $requestItem->proposed_contact_phone ?: '—' }}</p>
        <p><strong>Web:</strong> {{ $requestItem->proposed_website ?: '—' }}</p>
        <p><strong>Podnosilac:</strong> {{ $requestItem->submitter?->name }}</p>
        <p><strong>Predloženi Moderator:</strong> {{ $requestItem->proposedModerator?->name }} (ID {{ $requestItem->proposed_moderator_user_id }})</p>
        @if($requestItem->decision_at)
            <p><strong>Odluka:</strong> {{ $requestItem->decisionUser?->name }} — {{ $requestItem->decision_at }}</p>
            <p><strong>Napomena:</strong> {{ $requestItem->decision_note ?: '—' }}</p>
        @endif
    </div>

    @if($requestItem->isSubmitted())
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            {{-- One form + formaction: decision_note reaches both approve and reject (PO-ORG-05). --}}
            <form method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium mb-1">Napomena Urednika</label>
                    <p class="text-xs text-gray-500 mb-1">Obavezna pri odbijanju; opciona pri odobravanju.</p>
                    <textarea name="decision_note" rows="2" class="w-full border-gray-300 rounded-md">{{ old('decision_note') }}</textarea>
                </div>
                <div class="flex gap-3 flex-wrap">
                    <button
                        type="submit"
                        formaction="{{ route('cultural-organizer-creation-requests.approve', $requestItem) }}"
                        style="background:#15803d; color:#fff; padding:10px 16px; border-radius:8px; font-weight:600; border:0; cursor:pointer;"
                    >
                        Odobri
                    </button>
                    <button
                        type="submit"
                        formaction="{{ route('cultural-organizer-creation-requests.reject', $requestItem) }}"
                        style="background:#b45309; color:#fff; padding:10px 16px; border-radius:8px; font-weight:600; border:0; cursor:pointer;"
                    >
                        Odbij
                    </button>
                </div>
            </form>
        </div>
    @endif

    <div class="mt-4">
        <a href="{{ route('cultural-organizer-creation-requests.index') }}" class="text-sm text-gray-600 underline">Nazad na listu</a>
    </div>
</div>
@endsection
