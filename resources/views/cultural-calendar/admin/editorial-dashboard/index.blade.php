@extends('layouts.app')

@section('content')
<div class="kk-shell mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div style="margin-bottom:20px;">
        <h1 style="font-size:28px; font-weight:700; margin:0; color:#111827;">Kontrolna tabla</h1>
        <p class="text-sm text-gray-600 mt-1 mb-0">Radna tabla (TS-010.2). Klik otvara postojeće liste sa filterom — bez poslovnih akcija ovdje.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 max-w-5xl">
        @foreach($cards as $card)
            <a
                href="{{ $card['url'] }}"
                class="block bg-white rounded-lg border border-gray-200 p-5 hover:border-red-300 hover:shadow-sm transition"
            >
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1">{{ $card['id'] }}</p>
                        <h2 class="text-lg font-semibold text-gray-900 mb-1">{{ $card['title'] }}</h2>
                        <p class="text-sm text-gray-600 mb-0">{{ $card['description'] }}</p>
                    </div>
                    <div
                        class="shrink-0 min-w-[3rem] text-center rounded-md px-3 py-2"
                        style="background:#fef2f2; color:#991b1b; font-size:22px; font-weight:700;"
                    >
                        {{ $card['count'] }}
                    </div>
                </div>
            </a>
        @endforeach
    </div>
</div>
@endsection
