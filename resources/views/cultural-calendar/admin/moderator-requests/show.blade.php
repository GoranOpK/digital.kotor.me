@extends('layouts.app')

@section('content')
<div class="kk-shell mx-auto px-4 sm:px-6 lg:px-8 py-6 max-w-3xl">
    <h1 style="font-size:28px; font-weight:700; margin:0 0 16px; color:#111827;">Zahtjev Moderatora #{{ $requestItem->id }}</h1>

    @if($errors->any())
        <div class="mb-4 rounded-md bg-red-50 border border-red-200 text-red-800 px-4 py-3">
            <ul class="list-disc list-inside">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="bg-white rounded-lg border border-gray-200 p-6 space-y-2 mb-6 text-sm">
        <p><strong>Organizator:</strong> {{ $requestItem->organizer?->naziv }}</p>
        <p><strong>Tip:</strong> {{ $requestItem->typeLabel() }}</p>
        <p><strong>Podnosilac:</strong> {{ $requestItem->submitter?->name }}</p>
        <p><strong>Ciljni korisnik:</strong> {{ $requestItem->targetUser?->name }} (ID {{ $requestItem->target_user_id }})</p>
        <p><strong>Status:</strong> {{ $requestItem->statusLabel() }}</p>
        @if($requestItem->decision_at)
            <p><strong>Odluka:</strong> {{ $requestItem->decisionUser?->name }} — {{ $requestItem->decision_at }}</p>
            <p><strong>Napomena:</strong> {{ $requestItem->decision_note ?: '—' }}</p>
        @endif
    </div>

    @if($requestItem->isSubmitted())
        <div class="bg-white rounded-lg border border-gray-200 p-6 space-y-4">
            <div>
                <label class="block text-sm font-medium mb-1">Napomena odluke (opciono)</label>
                <textarea form="approve-mod-form" name="decision_note" rows="2" class="w-full border-gray-300 rounded-md">{{ old('decision_note') }}</textarea>
            </div>
            <div class="flex gap-3 flex-wrap">
                <form id="approve-mod-form" method="POST" action="{{ route('cultural-moderator-requests.approve', $requestItem) }}">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-green-700 text-white rounded-md font-semibold">Odobri</button>
                </form>
                <form method="POST" action="{{ route('cultural-moderator-requests.reject', $requestItem) }}">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-amber-700 text-white rounded-md font-semibold">Odbij</button>
                </form>
            </div>
        </div>
    @endif

    <div class="mt-4">
        <a href="{{ route('cultural-moderator-requests.index') }}" class="text-sm text-gray-600 underline">Nazad</a>
    </div>
</div>
@endsection
