@php
    $isKkSection = request()->routeIs(
        'cultural-calendar.*',
        'cultural-event-entries.*',
        'cultural-manifestations.*',
        'cultural-editorial-dashboard.*',
        'cultural-editorial-requests.*',
        'cultural-event-change-proposals.*',
        'cultural-locations.*',
        'cultural-categories.*',
        'cultural-tags.*',
        'cultural-organizers.*',
        'cultural-organizer-creation-requests.*',
        'cultural-moderator-requests.*',
        'cultural-moderator-workspace.*',
        'cultural-moderator-dashboard.*',
        'cultural-moderator-events.*',
        'cultural-moderator-manifestations.*',
        'cultural-moderator-proposals.*',
        'cultural-moderator-context.*'
    );
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['kk-section' => $isKkSection]) @if($isKkSection) style="color-scheme: light;" @endif>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Digital Kotor') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>
            @media print {
                nav { display: none !important; }
            }
            @if($isKkSection)
            /* Kalendar kulture: always light UI, content width matches main banner (1120px). */
            html.kk-section { color-scheme: light; }
            .kk-shell {
                max-width: 1120px;
                margin-left: auto;
                margin-right: auto;
                width: 100%;
                box-sizing: border-box;
            }
            /* Text/select padding: Tailwind form utilities are not in compiled KK CSS. */
            .kk-padded-fields input[type="text"],
            .kk-padded-fields input[type="url"],
            .kk-padded-fields textarea,
            .kk-padded-fields select {
                box-sizing: border-box;
                padding: 10px 14px;
                line-height: 1.45;
            }
            .kk-padded-fields select {
                min-height: 2.75rem;
            }
            /* Desktop-only scroll-following bottom background (not tied to a page footer). */
            .kk-scroll-bg {
                display: none;
            }
            @media (min-width: 992px) {
                .kk-section-wrap {
                    position: relative;
                    isolation: isolate;
                    background-color: #f3f4f6;
                }
                .kk-scroll-bg {
                    display: block;
                    position: fixed;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    width: 100%;
                    height: auto;
                    z-index: 0;
                    pointer-events: none;
                    opacity: 0.3;
                }
                .kk-section-wrap > nav,
                .kk-section-wrap > header,
                .kk-section-wrap > main {
                    position: relative;
                    z-index: 1;
                }
                .kk-section-wrap > main {
                    background: transparent;
                }
            }
            @endif
        </style>
    </head>
    <body class="font-sans antialiased">
        <div @class([
            'min-h-screen bg-gray-100',
            'dark:bg-gray-900' => ! $isKkSection,
            'kk-section-wrap' => $isKkSection,
        ])>
            @if($isKkSection)
                <img
                    class="kk-scroll-bg"
                    src="{{ asset('img/KKBG.jpg') }}?v={{ @filemtime(public_path('img/KKBG.jpg')) ?: time() }}"
                    alt=""
                    aria-hidden="true"
                >
            @endif
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header @class([
                    'bg-white shadow',
                    'dark:bg-gray-800' => ! $isKkSection,
                ])>
                    <div @class([
                        'mx-auto py-6 px-4 sm:px-6 lg:px-8',
                        'kk-shell' => $isKkSection,
                        'max-w-7xl' => ! $isKkSection,
                    ])>
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                @if (isset($slot))
                {{ $slot }}
                @else
                    @yield('content')
                @endif
            </main>
        </div>
    </body>
</html>
