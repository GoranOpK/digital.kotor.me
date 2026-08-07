@php
    $user = auth()->user();
    $isKkAdmin = $user && $user->role && $user->role->name === 'kk_admin';
    $isCompetitionAdmin = $user && $user->role && $user->role->name === 'konkurs_admin';
    $isKomisija = $user && $user->role && $user->role->name === 'komisija';
    $isKkSection = request()->routeIs(
        'cultural-calendar.*',
        'cultural-events.*',
        'cultural-event-entries.*',
        'cultural-editorial-dashboard.*',
        'cultural-event-change-proposals.*',
        'cultural-locations.*',
        'cultural-categories.*',
        'cultural-tags.*',
        'cultural-media.*',
        'cultural-organizers.*',
        'cultural-organizer-creation-requests.*',
        'cultural-moderator-requests.*',
        'cultural-moderator-workspace.*',
        'cultural-moderator-events.*',
        'cultural-moderator-proposals.*',
        'cultural-moderator-context.*'
    );
@endphp
<nav
    x-data="{ open: false }"
    @class([
        'bg-white border-b border-gray-100 print:hidden',
        'dark:bg-gray-800 dark:border-gray-700' => ! $isKkSection,
    ])
>
    <!-- Primary Navigation Menu -->
    <div @class([
        'mx-auto px-4 sm:px-6 lg:px-8',
        'kk-shell' => $isKkSection,
        'max-w-7xl' => ! $isKkSection,
    ])>
        <div class="flex justify-between h-16 items-center">
            <div class="flex items-center justify-start min-w-0 flex-1">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ $isKkAdmin ? route('cultural-calendar.index') : ($isCompetitionAdmin ? route('admin.dashboard') : route('dashboard')) }}">
                        <img src="{{ asset('img/logo.png') }}" alt="Digital Kotor" class="block h-10 w-auto">
                    </a>
                </div>

                <!-- Navigation Links -->
                @if($isKkAdmin || $isKkSection)
                    <div
                        class="hidden sm:flex sm:items-center sm:justify-start"
                        style="margin-left: 28px; gap: 36px;"
                    >
                        <a
                            href="{{ route('cultural-calendar.index') }}"
                            style="
                                color: {{ request()->routeIs('cultural-calendar.index') ? '#111827' : '#4b5563' }};
                                font-size: 14px;
                                font-weight: {{ request()->routeIs('cultural-calendar.index') ? '700' : '600' }};
                                text-decoration: none;
                                white-space: nowrap;
                                border-bottom: 2px solid {{ request()->routeIs('cultural-calendar.index') ? '#7a0f17' : 'transparent' }};
                                padding-bottom: 2px;
                            "
                        >Kalendar kulture</a>
                        <a
                            href="{{ route('cultural-calendar.events') }}"
                            style="
                                color: {{ request()->routeIs('cultural-calendar.events') ? '#111827' : '#4b5563' }};
                                font-size: 14px;
                                font-weight: {{ request()->routeIs('cultural-calendar.events') ? '700' : '600' }};
                                text-decoration: none;
                                white-space: nowrap;
                                border-bottom: 2px solid {{ request()->routeIs('cultural-calendar.events') ? '#7a0f17' : 'transparent' }};
                                padding-bottom: 2px;
                            "
                        >Pretraga i pregled</a>
                        <a
                            href="{{ route('cultural-calendar.archive') }}"
                            style="
                                color: {{ request()->routeIs('cultural-calendar.archive') ? '#111827' : '#4b5563' }};
                                font-size: 14px;
                                font-weight: {{ request()->routeIs('cultural-calendar.archive') ? '700' : '600' }};
                                text-decoration: none;
                                white-space: nowrap;
                                border-bottom: 2px solid {{ request()->routeIs('cultural-calendar.archive') ? '#7a0f17' : 'transparent' }};
                                padding-bottom: 2px;
                            "
                        >Arhiva događaja</a>
                        @if($isKkAdmin)
                            <a
                                href="{{ route('cultural-editorial-dashboard.index') }}"
                                style="
                                    display: inline-flex;
                                    align-items: center;
                                    padding: 8px 14px;
                                    border-radius: 8px;
                                    background: {{ request()->routeIs('cultural-editorial-dashboard.*') ? '#5f0c12' : '#7a0f17' }};
                                    color: #ffffff;
                                    font-size: 14px;
                                    font-weight: 600;
                                    text-decoration: none;
                                    white-space: nowrap;
                                "
                            >Urednički rad</a>
                            <a
                                href="{{ route('cultural-events.index') }}"
                                style="
                                    display: inline-flex;
                                    align-items: center;
                                    padding: 8px 14px;
                                    border-radius: 8px;
                                    background: {{ request()->routeIs('cultural-events.*') ? '#5f0c12' : '#7a0f17' }};
                                    color: #ffffff;
                                    font-size: 14px;
                                    font-weight: 600;
                                    text-decoration: none;
                                    white-space: nowrap;
                                "
                            >Događaji</a>
                            <a
                                href="{{ route('cultural-event-entries.index') }}"
                                style="
                                    display: inline-flex;
                                    align-items: center;
                                    padding: 8px 14px;
                                    border-radius: 8px;
                                    background: {{ request()->routeIs('cultural-event-entries.*') ? '#5f0c12' : '#7a0f17' }};
                                    color: #ffffff;
                                    font-size: 14px;
                                    font-weight: 600;
                                    text-decoration: none;
                                    white-space: nowrap;
                                "
                            >Kanonski događaji</a>
                            <a
                                href="{{ route('cultural-locations.index') }}"
                                style="
                                    display: inline-flex;
                                    align-items: center;
                                    padding: 8px 14px;
                                    border-radius: 8px;
                                    background: {{ request()->routeIs('cultural-locations.*') ? '#5f0c12' : '#7a0f17' }};
                                    color: #ffffff;
                                    font-size: 14px;
                                    font-weight: 600;
                                    text-decoration: none;
                                    white-space: nowrap;
                                "
                            >Lokacije</a>
                            <a
                                href="{{ route('cultural-categories.index') }}"
                                style="
                                    display: inline-flex;
                                    align-items: center;
                                    padding: 8px 14px;
                                    border-radius: 8px;
                                    background: {{ request()->routeIs('cultural-categories.*') ? '#5f0c12' : '#7a0f17' }};
                                    color: #ffffff;
                                    font-size: 14px;
                                    font-weight: 600;
                                    text-decoration: none;
                                    white-space: nowrap;
                                "
                            >Kategorije</a>
                            <a
                                href="{{ route('cultural-tags.index') }}"
                                style="
                                    display: inline-flex;
                                    align-items: center;
                                    padding: 8px 14px;
                                    border-radius: 8px;
                                    background: {{ request()->routeIs('cultural-tags.*') ? '#5f0c12' : '#7a0f17' }};
                                    color: #ffffff;
                                    font-size: 14px;
                                    font-weight: 600;
                                    text-decoration: none;
                                    white-space: nowrap;
                                "
                            >Oznake</a>
                            <a
                                href="{{ route('cultural-media.index') }}"
                                style="
                                    display: inline-flex;
                                    align-items: center;
                                    padding: 8px 14px;
                                    border-radius: 8px;
                                    background: {{ request()->routeIs('cultural-media.*') ? '#5f0c12' : '#7a0f17' }};
                                    color: #ffffff;
                                    font-size: 14px;
                                    font-weight: 600;
                                    text-decoration: none;
                                    white-space: nowrap;
                                "
                            >Mediji</a>
                            <a
                                href="{{ route('cultural-organizers.index') }}"
                                style="
                                    display: inline-flex;
                                    align-items: center;
                                    padding: 8px 14px;
                                    border-radius: 8px;
                                    background: {{ request()->routeIs('cultural-organizers.*') ? '#5f0c12' : '#7a0f17' }};
                                    color: #ffffff;
                                    font-size: 14px;
                                    font-weight: 600;
                                    text-decoration: none;
                                    white-space: nowrap;
                                "
                            >Organizatori</a>
                            <a
                                href="{{ route('cultural-organizer-creation-requests.index') }}"
                                style="
                                    display: inline-flex;
                                    align-items: center;
                                    padding: 8px 14px;
                                    border-radius: 8px;
                                    background: {{ request()->routeIs('cultural-organizer-creation-requests.*') ? '#5f0c12' : '#7a0f17' }};
                                    color: #ffffff;
                                    font-size: 14px;
                                    font-weight: 600;
                                    text-decoration: none;
                                    white-space: nowrap;
                                "
                            >Zahtjevi Org</a>
                            <a
                                href="{{ route('cultural-moderator-requests.index') }}"
                                style="
                                    display: inline-flex;
                                    align-items: center;
                                    padding: 8px 14px;
                                    border-radius: 8px;
                                    background: {{ request()->routeIs('cultural-moderator-requests.index', 'cultural-moderator-requests.show') ? '#5f0c12' : '#7a0f17' }};
                                    color: #ffffff;
                                    font-size: 14px;
                                    font-weight: 600;
                                    text-decoration: none;
                                    white-space: nowrap;
                                "
                            >Zahtjevi Mod</a>
                        @endif
                        @auth
                            @if(\App\Support\CulturalPortalAccess::allows(auth()->user()))
                                <a
                                    href="{{ route('cultural-moderator-workspace.index') }}"
                                    style="
                                        display: inline-flex;
                                        align-items: center;
                                        padding: 8px 14px;
                                        border-radius: 8px;
                                        background: {{ request()->routeIs('cultural-moderator-workspace.*') ? '#5f0c12' : '#7a0f17' }};
                                        color: #ffffff;
                                        font-size: 14px;
                                        font-weight: 600;
                                        text-decoration: none;
                                        white-space: nowrap;
                                    "
                                >Mod rad</a>
                            @endif
                        @endauth
                    </div>
                @else
                <div class="hidden sm:ms-8 sm:flex sm:items-center sm:justify-start sm:gap-x-8">
                    @if($isCompetitionAdmin)
                        <x-nav-link :href="route('home')" :active="request()->routeIs('home')" aria-label="Početna stranica">
                            <x-icon-home />
                        </x-nav-link>
                        <x-nav-link :href="route('admin.commissions.index')" :active="request()->routeIs('admin.commissions.*')">
                            Komisije
                        </x-nav-link>
                        <x-nav-link :href="route('competitions.archive')" :active="request()->routeIs('competitions.archive')">
                            Arhiva konkursa
                        </x-nav-link>
                    @elseif($isKomisija)
                        <x-nav-link :href="route('home')" :active="request()->routeIs('home')" aria-label="Početna stranica">
                            <x-icon-home />
                        </x-nav-link>
                        <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                            Moj panel
                        </x-nav-link>
                        <x-nav-link :href="route('evaluation.index')" :active="request()->routeIs('evaluation.*')">
                            Ocjenjivanje
                        </x-nav-link>
                        <x-nav-link :href="route('competitions.archive')" :active="request()->routeIs('competitions.archive')">
                            Arhiva konkursa
                        </x-nav-link>
                    @else
                        <x-nav-link :href="route('home')" :active="request()->routeIs('home')">
                            <x-icon-home class="w-5 h-5 inline-block align-text-bottom" style="margin-right: 4px;" />
                            Home
                        </x-nav-link>
                        <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                            Moj Panel
                        </x-nav-link>
                        @auth
                            @if(auth()->user()->role && (auth()->user()->role->name === 'superadmin' || auth()->user()->role->name === 'admin'))
                                <x-nav-link :href="route('admin.dashboard')" :active="request()->is('admin*')">
                                    Administracija
                                </x-nav-link>
                            @endif
                        @endauth
                    @endif
                </div>
                @endif
            </div>

            <!-- User info + Logout -->
            <div class="sm:flex sm:items-center sm:ms-6" style="display:flex; align-items:center; gap:12px;">
                @auth
                    <span @class(['text-sm text-gray-700', 'dark:text-gray-200' => ! $isKkSection]) style="margin-right: 16px;">
                        @if(auth()->user()->role && auth()->user()->role->name === 'konkurs_admin')
                            Administrator konkursa
                        @else
                        {{ Auth::user()->name }}
                        @endif
                    </span>
                    <form method="POST" action="{{ route('logout') }}" style="margin:0;">
                        @csrf
                        <button
                            type="submit"
                            class="inline-flex items-center justify-center px-4 py-2 text-sm font-semibold"
                            style="
                                min-width: 100px;
                                background: #0d6efd;
                                color: #ffffff;
                                border: 1px solid #0d6efd;
                                border-radius: 8px;
                                padding: 8px 14px;
                                display: inline-flex;
                                align-items: center;
                                justify-content: center;
                                gap: 6px;
                                cursor: pointer;
                                text-decoration: none;
                            "
                        >
                            Odjava
                        </button>
                    </form>
                @endauth
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button
                    @click="open = ! open"
                    @class([
                        'inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out',
                        'dark:text-gray-500 dark:hover:text-gray-400 dark:hover:bg-gray-900 dark:focus:bg-gray-900 dark:focus:text-gray-400' => ! $isKkSection,
                    ])
                >
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            @if($isKkAdmin || $isKkSection)
                <x-responsive-nav-link :href="route('cultural-calendar.index')" :active="request()->routeIs('cultural-calendar.index')">
                    Kalendar kulture
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('cultural-calendar.events')" :active="request()->routeIs('cultural-calendar.events')">
                    Pretraga i pregled
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('cultural-calendar.archive')" :active="request()->routeIs('cultural-calendar.archive')">
                    Arhiva događaja
                </x-responsive-nav-link>
                    @if($isKkAdmin)
                    <a
                        href="{{ route('cultural-editorial-dashboard.index') }}"
                        style="
                            display: block;
                            width: 100%;
                            padding: 10px 16px;
                            background: {{ request()->routeIs('cultural-editorial-dashboard.*') ? '#5f0c12' : '#7a0f17' }};
                            color: #ffffff;
                            font-size: 16px;
                            font-weight: 600;
                            text-decoration: none;
                        "
                    >
                        Urednički rad
                    </a>
                    <a
                        href="{{ route('cultural-events.index') }}"
                        style="
                            display: block;
                            width: 100%;
                            padding: 10px 16px;
                            background: {{ request()->routeIs('cultural-events.*') ? '#5f0c12' : '#7a0f17' }};
                            color: #ffffff;
                            font-size: 16px;
                            font-weight: 600;
                            text-decoration: none;
                        "
                    >
                        Događaji
                    </a>
                    <a
                        href="{{ route('cultural-event-entries.index') }}"
                        style="
                            display: block;
                            width: 100%;
                            padding: 10px 16px;
                            background: {{ request()->routeIs('cultural-event-entries.*') ? '#5f0c12' : '#7a0f17' }};
                            color: #ffffff;
                            font-size: 16px;
                            font-weight: 600;
                            text-decoration: none;
                        "
                    >
                        Kanonski događaji
                    </a>
                    <a
                        href="{{ route('cultural-locations.index') }}"
                        style="
                            display: block;
                            width: 100%;
                            padding: 10px 16px;
                            background: {{ request()->routeIs('cultural-locations.*') ? '#5f0c12' : '#7a0f17' }};
                            color: #ffffff;
                            font-size: 16px;
                            font-weight: 600;
                            text-decoration: none;
                        "
                    >
                        Lokacije
                    </a>
                    <a
                        href="{{ route('cultural-categories.index') }}"
                        style="
                            display: block;
                            width: 100%;
                            padding: 10px 16px;
                            background: {{ request()->routeIs('cultural-categories.*') ? '#5f0c12' : '#7a0f17' }};
                            color: #ffffff;
                            font-size: 16px;
                            font-weight: 600;
                            text-decoration: none;
                        "
                    >
                        Kategorije
                    </a>
                    <a
                        href="{{ route('cultural-tags.index') }}"
                        style="
                            display: block;
                            width: 100%;
                            padding: 10px 16px;
                            background: {{ request()->routeIs('cultural-tags.*') ? '#5f0c12' : '#7a0f17' }};
                            color: #ffffff;
                            font-size: 16px;
                            font-weight: 600;
                            text-decoration: none;
                        "
                    >
                        Oznake
                    </a>
                    <a
                        href="{{ route('cultural-media.index') }}"
                        style="
                            display: block;
                            width: 100%;
                            padding: 10px 16px;
                            background: {{ request()->routeIs('cultural-media.*') ? '#5f0c12' : '#7a0f17' }};
                            color: #ffffff;
                            font-size: 16px;
                            font-weight: 600;
                            text-decoration: none;
                        "
                    >
                        Mediji
                    </a>
                    <a
                        href="{{ route('cultural-organizers.index') }}"
                        style="
                            display: block;
                            width: 100%;
                            padding: 10px 16px;
                            background: {{ request()->routeIs('cultural-organizers.*') ? '#5f0c12' : '#7a0f17' }};
                            color: #ffffff;
                            font-size: 16px;
                            font-weight: 600;
                            text-decoration: none;
                        "
                    >
                        Organizatori
                    </a>
                    <a
                        href="{{ route('cultural-organizer-creation-requests.index') }}"
                        style="
                            display: block;
                            width: 100%;
                            padding: 10px 16px;
                            background: {{ request()->routeIs('cultural-organizer-creation-requests.*') ? '#5f0c12' : '#7a0f17' }};
                            color: #ffffff;
                            font-size: 16px;
                            font-weight: 600;
                            text-decoration: none;
                        "
                    >
                        Zahtjevi Org
                    </a>
                    <a
                        href="{{ route('cultural-moderator-requests.index') }}"
                        style="
                            display: block;
                            width: 100%;
                            padding: 10px 16px;
                            background: {{ request()->routeIs('cultural-moderator-requests.index', 'cultural-moderator-requests.show') ? '#5f0c12' : '#7a0f17' }};
                            color: #ffffff;
                            font-size: 16px;
                            font-weight: 600;
                            text-decoration: none;
                        "
                    >
                        Zahtjevi Mod
                    </a>
                    @endif
                    @auth
                        @if(\App\Support\CulturalPortalAccess::allows(auth()->user()))
                            <a
                                href="{{ route('cultural-moderator-workspace.index') }}"
                                style="
                                    display: block;
                                    width: 100%;
                                    padding: 10px 16px;
                                    background: {{ request()->routeIs('cultural-moderator-workspace.*') ? '#5f0c12' : '#7a0f17' }};
                                    color: #ffffff;
                                    font-size: 16px;
                                    font-weight: 600;
                                    text-decoration: none;
                                "
                            >
                                Mod rad
                            </a>
                        @endif
                    @endauth
                @elseif($isCompetitionAdmin)
                <x-responsive-nav-link :href="route('home')" :active="request()->routeIs('home')">
                    <x-icon-home class="w-5 h-5 inline-block align-text-bottom" style="margin-right: 4px;" />
                    Početna
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.commissions.index')" :active="request()->routeIs('admin.commissions.*')">
                    Komisije
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('competitions.archive')" :active="request()->routeIs('competitions.archive')">
                    Arhiva konkursa
                </x-responsive-nav-link>
            @elseif($isKomisija)
                <x-responsive-nav-link :href="route('home')" :active="request()->routeIs('home')">
                    <x-icon-home class="w-5 h-5 inline-block align-text-bottom" style="margin-right: 4px;" />
                    Početna
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                    Moj panel
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('evaluation.index')" :active="request()->routeIs('evaluation.*')">
                    Ocjenjivanje
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('competitions.archive')" :active="request()->routeIs('competitions.archive')">
                    Arhiva konkursa
                </x-responsive-nav-link>
            @else
                <x-responsive-nav-link :href="route('home')" :active="request()->routeIs('home')">
                    <x-icon-home class="w-5 h-5 inline-block align-text-bottom" style="margin-right: 4px;" />
                    Home
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                    Moj Panel
                </x-responsive-nav-link>
                @auth
                    @if(auth()->user()->role && (auth()->user()->role->name === 'superadmin' || auth()->user()->role->name === 'admin'))
                        <x-responsive-nav-link :href="route('admin.dashboard')" :active="request()->is('admin*')">
                            Administracija
                        </x-responsive-nav-link>
                    @endif
                @endauth
            @endif
        </div>

        <!-- Responsive Settings Options -->
        <div @class(['pt-4 pb-1 border-t border-gray-200', 'dark:border-gray-600' => ! $isKkSection])>
            @auth
                <div class="px-4">
                    <div @class(['font-medium text-base text-gray-800', 'dark:text-gray-200' => ! $isKkSection])>
                        @if(auth()->user()->role && auth()->user()->role->name === 'konkurs_admin')
                            Administrator konkursa
                        @else
                            {{ Auth::user()->name }}
                        @endif
                    </div>
                    <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
                </div>

                <div class="mt-3 space-y-1">
                    <!-- Authentication -->
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf

                        <x-responsive-nav-link :href="route('logout')"
                                onclick="event.preventDefault();
                                            this.closest('form').submit();">
                            {{ __('Log Out') }}
                        </x-responsive-nav-link>
                    </form>
                </div>
            @endauth
        </div>
    </div>
</nav>
