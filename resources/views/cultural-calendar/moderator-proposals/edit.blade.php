@extends('layouts.app')

@section('content')
<div class="kk-shell mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:16px; flex-wrap:wrap;">
        <div>
            <h1 style="font-size:28px; font-weight:700; margin:0; color:#111827;">Prijedlog izmjene</h1>
            <p class="text-sm text-gray-600 mt-1 mb-0">
                {{ $entry?->naslov ?: 'Događaj' }} · {{ $proposal->statusLabel() }} · ID {{ $proposal->id }}
            </p>
        </div>
        <a href="{{ route('cultural-moderator-events.edit', $entry) }}" class="px-3 py-1.5 border border-gray-300 rounded-md">Nazad na događaj</a>
    </div>

    @if(session('status'))
        <div class="mb-4 rounded-md bg-green-50 border border-green-200 text-green-800 px-4 py-3">{{ session('status') }}</div>
    @endif

    @if($errors->any())
        <div class="mb-4 rounded-md bg-red-50 border border-red-200 text-red-800 px-4 py-3">
            <ul class="list-disc list-inside">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    @if($proposal->return_reason)
        <div class="mb-4 rounded-md bg-amber-50 border border-amber-200 text-amber-900 px-4 py-3 max-w-3xl">
            <p class="font-semibold mb-1">Razlog vraćanja na doradu</p>
            <p class="mb-0 whitespace-pre-wrap">{{ $proposal->return_reason }}</p>
        </div>
    @endif

    <div class="bg-white rounded-lg border border-gray-200 p-6 max-w-3xl">
        <div class="flex flex-wrap justify-between gap-3 items-start mb-4">
            <h2 class="text-lg font-semibold text-gray-900 mb-0">Predloženi sadržaj</h2>
            <form method="POST" action="{{ route('cultural-moderator-proposals.submit', $proposal) }}">
                @csrf
                <button type="submit" class="px-3 py-1.5 border border-blue-300 rounded-md text-blue-800 hover:bg-blue-50 font-semibold">
                    Pošalji na pregled
                </button>
            </form>
        </div>

        <form method="POST" action="{{ route('cultural-moderator-proposals.update', $proposal) }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label for="proposed_naslov" class="block text-sm font-medium text-gray-700 mb-1">Naslov</label>
                <input type="text" id="proposed_naslov" name="proposed_naslov" value="{{ old('proposed_naslov', $proposal->proposed_naslov) }}" maxlength="255" class="w-full rounded-md border-gray-300 shadow-sm">
                @error('proposed_naslov')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="proposed_opis" class="block text-sm font-medium text-gray-700 mb-1">Opis</label>
                <textarea id="proposed_opis" name="proposed_opis" rows="5" class="w-full rounded-md border-gray-300 shadow-sm">{{ old('proposed_opis', $proposal->proposed_opis) }}</textarea>
                @error('proposed_opis')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="proposed_category_id" class="block text-sm font-medium text-gray-700 mb-1">Kategorija</label>
                <select id="proposed_category_id" name="proposed_category_id" class="w-full rounded-md border-gray-300 shadow-sm">
                    <option value="">— bez kategorije —</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) old('proposed_category_id', $proposal->proposed_category_id) === (string) $category->id)>
                            {{ $category->naziv }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="proposed_cover_media_id" class="block text-sm font-medium text-gray-700 mb-1">Naslovni medij</label>
                <select id="proposed_cover_media_id" name="proposed_cover_media_id" class="w-full rounded-md border-gray-300 shadow-sm">
                    <option value="">— bez naslovne —</option>
                    @foreach($mediaItems as $media)
                        <option value="{{ $media->id }}" @selected((string) old('proposed_cover_media_id', $proposal->proposed_cover_media_id) === (string) $media->id)>
                            {{ $media->naziv }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Oznake</label>
                <div class="space-y-2 max-h-48 overflow-y-auto border border-gray-200 rounded-md p-3">
                    @php
                        $selectedTagIds = collect(old('tag_ids', $proposal->tags->pluck('id')->all()))->map(fn ($id) => (int) $id)->all();
                    @endphp
                    @forelse($tags as $tag)
                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" name="tag_ids[]" value="{{ $tag->id }}" @checked(in_array((int) $tag->id, $selectedTagIds, true)) class="rounded border-gray-300 text-red-700">
                            {{ $tag->naziv }}
                        </label>
                    @empty
                        <p class="text-sm text-gray-500">Nema aktivnih oznaka.</p>
                    @endforelse
                </div>
            </div>

            <div class="pt-2">
                <button type="submit" style="background:#b91c1c; color:#fff; padding:10px 16px; border-radius:8px; font-weight:600; border:0;">
                    Sačuvaj izmjene
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
