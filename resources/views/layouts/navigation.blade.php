@php
    $user = auth()->user();
    $isKkAdmin = $user && $user->role && $user->role->name === 'kk_admin';
    $isCompetitionAdmin = $user && $user->role && $user->role->name === 'konkurs_admin';
    $isKomisija = $user && $user->role && $user->role->name === 'komisija';
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

    // Public portal (`cultural-calendar.*`) vs editorial management: public and editorial
    // entrypoints use distinct labels and must not render together in one nav context.
    $isKkPublicPortalContext = request()->routeIs('cultural-calendar.*');
    $isKkEditorialPortalContext = $isKkAdmin && ! $isKkPublicPortalContext;
    $showPublicEventsNav = ! $isKkAdmin || $isKkPublicPortalContext;
    $showEditorialEventsNav = $isKkEditorialPortalContext;
    $showPublicManifestationsNav = ! $isKkAdmin || $isKkPublicPortalContext;
    $showEditorialManifestationsNav = $isKkEditorialPortalContext;

    // Shared KK nav link styles: red text, transparent fill, red tape on active/hover.
    $kkNavBtn = static function (bool $active): string {
        $tape = $active ? '#7a0f17' : 'transparent';

        return 'display:inline-flex;align-items:center;justify-content:flex-start;padding:8px 14px;border-radius:0;'
            .'background:transparent;color:#7a0f17;font-size:12px;font-weight:600;text-decoration:none;white-space:nowrap;'
            ."border-bottom:3px solid {$tape};";
    };
    // Compact desktop sizing for moderator entrypoints (Kontrolna tabla + Moderiranje) — both <a>, shared style.
    $kkModeratorEntryBtn = static function (bool $active) use ($kkNavBtn): string {
        $tape = $active ? '#7a0f17' : 'transparent';

        return 'display:inline-flex;align-items:center;justify-content:center;padding:6px 10px;border-radius:0;'
            .'background:transparent;color:#7a0f17;font-size:11px;font-weight:600;text-decoration:none;white-space:nowrap;'
            ."border-bottom:3px solid {$tape};"
            .'box-sizing:border-box;line-height:1.2;min-height:32px;height:32px;';
    };
    $kkNavBtnMobile = static function (bool $active): string {
        $tape = $active ? '#7a0f17' : 'transparent';

        return 'display:block;width:100%;box-sizing:border-box;padding:10px 16px;border-radius:0;'
            .'background:transparent;color:#7a0f17;font-size:14px;font-weight:600;text-decoration:none;'
            ."border-bottom:3px solid {$tape};";
    };
    $kkModeratorGuideBtn = 'display:inline-flex;align-items:center;justify-content:center;padding:6px 10px;'
        .'background:#7a0f17;color:#ffffff;border:0;border-radius:0;cursor:pointer;'
        .'text-decoration:none;font-size:11px;font-weight:600;white-space:nowrap;min-height:32px;height:32px;box-sizing:border-box;';
    $kkLogoutBtn = 'display:inline-flex;align-items:center;justify-content:center;padding:6px 10px;'
        .'background:#374151;color:#ffffff;border:0;border-radius:0;cursor:pointer;'
        .'text-decoration:none;font-size:11px;font-weight:600;white-space:nowrap;min-height:32px;height:32px;box-sizing:border-box;';
    $kkLogoutBtnMobile = 'display:block;width:100%;box-sizing:border-box;padding:10px 16px;'
        .'background:#374151;color:#ffffff;border:0;border-radius:0;font-size:14px;'
        .'font-weight:600;text-decoration:none;text-align:left;cursor:pointer;';
    $kkModeratorGuideBtnMobile = 'display:block;width:100%;box-sizing:border-box;padding:10px 16px;'
        .'background:#7a0f17;color:#ffffff;border:0;border-radius:0;font-size:14px;'
        .'font-weight:600;text-decoration:none;text-align:left;';
    $moderatorGuideUrl = asset('pdf/'.rawurlencode('Moderator uputstvo.pdf'));

    // Moderator UX block (grant-based; not a platform role). Labels only — access stays in middleware.
    $isActiveModeratorUser = $user && \App\Support\CulturalModeratorEventAccess::isActiveModerator($user);
    $moderatorActiveOrganizer = null;
    $moderatorAvailableOrganizerCount = 0;
    if ($isActiveModeratorUser) {
        $moderatorActiveOrganizer = \App\Support\CulturalOrganizerContext::get($user);
        $moderatorAvailableOrganizerCount = \App\Support\CulturalOrganizerContext::availableOrganizers($user)->count();
    }
    $isModeratorContentNav = request()->routeIs(
        'cultural-moderator-events.*',
        'cultural-moderator-manifestations.*',
        'cultural-moderator-proposals.*'
    );
    $isModeratorHubNav = $isModeratorContentNav
        || request()->routeIs('cultural-moderator-workspace.*');
@endphp
<style>
    .kk-logout-link:hover,
    .kk-logout-link:focus {
        background: #1f2937 !important;
        color: #ffffff !important;
    }
    .kk-logout-link {
        border-radius: 0 !important;
    }
    .kk-moderator-guide-link {
        border-radius: 0 !important;
    }
    .kk-moderator-guide-link:hover,
    .kk-moderator-guide-link:focus {
        background: #5c0b11 !important;
        color: #ffffff !important;
        border-bottom-color: transparent !important;
        text-decoration: none !important;
    }
</style>
@if($isKkAdmin || $isKkSection)
{{-- Inline CSS: Tailwind purge often omits sm:flex-col, which collapsed both rows into one horizontal flex. --}}
<style>
    .kk-admin-nav-desktop { display: none; }
    .kk-admin-nav-row {
        display: flex;
        flex-direction: row;
        flex-wrap: nowrap;
        align-items: stretch;
        justify-content: flex-start;
        gap: 4px;
        width: auto;
        max-width: 100%;
        margin: 0;
        box-sizing: border-box;
        text-align: left;
    }
    .kk-admin-nav-desktop a,
    .kk-section-links a {
        border-radius: 0 !important;
        align-self: stretch;
        text-align: left;
    }
    .kk-logout-link {
        align-self: stretch;
        text-align: left;
    }
    .kk-admin-nav-row > a:first-child,
    .kk-section-links > a:first-child {
        padding-left: 0 !important;
    }
    .kk-section-links {
        justify-content: flex-start !important;
        margin-left: 0 !important;
    }
    .kk-admin-nav-desktop[data-kk-nav-context="editorial"] a,
    nav[data-kk-nav-context="editorial"] .kk-logout-link,
    nav[data-kk-nav-context="editorial"] .kk-moderator-guide-link {
        font-size: 10px !important;
        padding: 8px 8px !important;
    }
    .kk-admin-nav-desktop[data-kk-nav-context="editorial"] .kk-admin-nav-row > a:first-child {
        padding-left: 0 !important;
    }
    .kk-admin-nav-desktop a:hover,
    .kk-admin-nav-desktop a:focus,
    .kk-section-links a:hover,
    .kk-section-links a:focus,
    #kk-mobile-nav-menu a:hover:not(.kk-moderator-guide-link),
    #kk-mobile-nav-menu a:focus:not(.kk-moderator-guide-link) {
        border-bottom-color: #7a0f17 !important;
    }
    @media (min-width: 640px) {
        .kk-admin-nav-desktop {
            display: flex;
            flex-direction: row;
            align-items: stretch;
            justify-content: flex-start;
            gap: 4px;
            margin-left: 0;
            flex: 0 1 auto;
            min-width: 0;
            max-width: 100%;
            box-sizing: border-box;
        }
    }
</style>
@endif
<nav
    data-kk-mobile-nav-root
    @if($isKkEditorialPortalContext) data-kk-nav-context="editorial" @elseif($isKkSection) data-kk-nav-context="public" @endif
    @class([
        'bg-white border-b border-gray-100 print:hidden',
        'dark:bg-gray-800 dark:border-gray-700' => ! $isKkSection,
    ])
    @if($isKkAdmin) style="overflow-x: hidden;" @endif
>
    <!-- Primary Navigation Menu -->
    <div @class([
        'mx-auto px-4 sm:px-6 lg:px-8',
        'kk-shell' => $isKkAdmin || $isKkSection,
        'max-w-7xl' => ! ($isKkAdmin || $isKkSection),
    ])>
        <div @class([
            'flex justify-between min-h-16',
            'items-stretch' => $isKkAdmin,
            'items-center flex-wrap gap-y-2 py-2' => ! $isKkAdmin,
        ])>
            <div @class([
                'flex justify-start min-w-0 flex-1',
                'items-stretch' => $isKkAdmin,
                'items-center flex-wrap gap-y-2' => ! $isKkAdmin,
            ])>
                @unless($isKkAdmin || $isKkSection)
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ $isCompetitionAdmin ? route('admin.dashboard') : route('dashboard') }}">
                        <img src="{{ asset('img/logo.png') }}" alt="Digital Kotor" class="block h-10 w-auto">
                    </a>
                </div>
                @endunless

                <!-- Navigation Links -->
                @if($isKkAdmin)
                    {{-- Single-row desktop layout; editorial uses a smaller font so all items fit. --}}
                    <div
                        class="kk-admin-nav-desktop"
                        data-kk-nav-layout="one-row"
                        data-kk-nav-context="{{ $isKkPublicPortalContext ? 'public' : 'editorial' }}"
                    >
                        <div class="kk-admin-nav-row" data-kk-nav-row="1">
                            @if($isKkPublicPortalContext)
                                <a
                                    href="{{ route('cultural-calendar.index') }}"
                                    style="{{ $kkNavBtn(request()->routeIs('cultural-calendar.index')) }}"
                                >Kalendar kulture</a>
                                <a
                                    href="{{ route('cultural-calendar.events') }}"
                                    data-kk-nav="events-public"
                                    style="{{ $kkNavBtn(request()->routeIs('cultural-calendar.events')) }}"
                                >Događaji</a>
                                <a
                                    href="{{ route('cultural-calendar.archive') }}"
                                    style="{{ $kkNavBtn(request()->routeIs('cultural-calendar.archive')) }}"
                                >Arhiva događaja</a>
                                <a
                                    href="{{ route('cultural-calendar.manifestations') }}"
                                    data-kk-nav="mf-public"
                                    style="{{ $kkNavBtn(request()->routeIs('cultural-calendar.manifestations', 'cultural-calendar.manifestation')) }}"
                                >Manifestacije</a>
                                <a
                                    href="{{ route('cultural-editorial-dashboard.index') }}"
                                    data-kk-nav="bridge-editorial"
                                    style="{{ $kkNavBtn(request()->routeIs('cultural-editorial-dashboard.*')) }}"
                                >Urednički portal</a>
                            @else
                                <a
                                    href="{{ route('cultural-calendar.index') }}"
                                    data-kk-nav="bridge-public"
                                    style="{{ $kkNavBtn(request()->routeIs('cultural-calendar.*')) }}"
                                >Kalendar kulture</a>
                                <a
                                    href="{{ route('cultural-editorial-dashboard.index') }}"
                                    data-kk-nav="kontrolna-tabla"
                                    style="{{ $kkNavBtn(request()->routeIs('cultural-editorial-dashboard.*')) }}"
                                >Kontrolna tabla</a>
                                <a
                                    href="{{ route('cultural-event-entries.index') }}"
                                    data-kk-nav="events-editorial"
                                    style="{{ $kkNavBtn(request()->routeIs('cultural-event-entries.*')) }}"
                                >Upravljanje događajima</a>
                                <a
                                    href="{{ route('cultural-manifestations.index') }}"
                                    data-kk-nav="mf-editorial"
                                    style="{{ $kkNavBtn(request()->routeIs('cultural-manifestations.*')) }}"
                                >Upravljanje manifestacijama</a>
                                <a
                                    href="{{ route('cultural-locations.index') }}"
                                    style="{{ $kkNavBtn(request()->routeIs('cultural-locations.*')) }}"
                                >Lokacije</a>
                                <a
                                    href="{{ route('cultural-categories.index') }}"
                                    style="{{ $kkNavBtn(request()->routeIs('cultural-categories.*')) }}"
                                >Kategorije</a>
                                <a
                                    href="{{ route('cultural-tags.index') }}"
                                    style="{{ $kkNavBtn(request()->routeIs('cultural-tags.*')) }}"
                                >Oznake</a>
                                <a
                                    href="{{ route('cultural-organizers.index') }}"
                                    style="{{ $kkNavBtn(request()->routeIs('cultural-organizers.*')) }}"
                                >Organizatori</a>
                                <a
                                    href="{{ route('cultural-editorial-requests.index') }}"
                                    data-kk-nav="zahtjevi"
                                    style="{{ $kkNavBtn(request()->routeIs('cultural-editorial-requests.*', 'cultural-organizer-creation-requests.index', 'cultural-organizer-creation-requests.show', 'cultural-organizer-creation-requests.approve', 'cultural-organizer-creation-requests.reject', 'cultural-moderator-requests.index', 'cultural-moderator-requests.show', 'cultural-moderator-requests.approve', 'cultural-moderator-requests.reject')) }}"
                                >Zahtjevi</a>
                            @endif
                        </div>
                    </div>
                @elseif($isKkSection)
                    <div
                        class="hidden sm:flex sm:items-center sm:justify-start sm:flex-wrap kk-section-links"
                        style="margin-left: 0; gap: 8px; flex: 0 1 auto; min-width: 0;"
                    >
                        <a
                            href="{{ route('cultural-calendar.index') }}"
                            style="{{ $kkNavBtn(request()->routeIs('cultural-calendar.index')) }}"
                        >Kalendar kulture</a>
                        <a
                            href="{{ route('cultural-calendar.events') }}"
                            style="{{ $kkNavBtn(request()->routeIs('cultural-calendar.events')) }}"
                        >Događaji</a>
                        <a
                            href="{{ route('cultural-calendar.archive') }}"
                            style="{{ $kkNavBtn(request()->routeIs('cultural-calendar.archive')) }}"
                        >Arhiva događaja</a>
                        <a
                            href="{{ route('cultural-calendar.manifestations') }}"
                            style="{{ $kkNavBtn(request()->routeIs('cultural-calendar.manifestations', 'cultural-calendar.manifestation')) }}"
                        >Manifestacije</a>
                        @auth
                            @if(\App\Support\CulturalPortalAccess::isPlatformUserActive(auth()->user()))
                                <a
                                    href="{{ route('cultural-organizer-creation-requests.create') }}"
                                    style="{{ $kkNavBtn(request()->routeIs('cultural-organizer-creation-requests.create', 'cultural-organizer-creation-requests.store')) }}"
                                >Zahtjev za Organizatora</a>
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

            <!-- Right cluster: moderator tools (compact) + guide + Odjava. No personal name for moderators/kk_admin. -->
            <div class="hidden sm:flex sm:items-center sm:ms-6 shrink-0" style="align-items:center; margin-left:auto; gap:8px;">
                @auth
                    @if($isActiveModeratorUser)
                        <span
                            data-kk-nav-moderator-block="1"
                            style="display:inline-flex;align-items:center;flex-wrap:nowrap;gap:4px;padding-right:8px;border-right:2px solid #e5e7eb;"
                        >
                            <a
                                href="{{ route('cultural-moderator-dashboard.index') }}"
                                data-kk-nav="kontrolna-tabla-moderator"
                                style="{{ $kkModeratorEntryBtn(request()->routeIs('cultural-moderator-dashboard.*')) }}"
                            >Kontrolna tabla</a>
                            <a
                                href="{{ route('cultural-moderator-workspace.index') }}"
                                data-kk-nav="moderiranje"
                                style="{{ $kkModeratorEntryBtn($isModeratorHubNav) }}"
                            >Moderiranje</a>
                            @if($moderatorActiveOrganizer)
                                <span
                                    data-kk-nav="active-organizer"
                                    style="display:inline-flex;align-items:center;padding:4px 8px;border-radius:0;background:#f3f4f6;color:#111827;font-size:10px;font-weight:600;white-space:nowrap;max-width:11rem;overflow:hidden;text-overflow:ellipsis;"
                                    title="Organizator: {{ $moderatorActiveOrganizer->naziv }}"
                                >{{ $moderatorActiveOrganizer->naziv }}</span>
                            @endif
                            @if($moderatorAvailableOrganizerCount > 1)
                                <a
                                    href="{{ route('cultural-moderator-workspace.index') }}"
                                    data-kk-nav="promijeni-organizatora"
                                    style="{{ $kkModeratorEntryBtn(request()->routeIs('cultural-moderator-workspace.*')) }}"
                                >Promijeni</a>
                            @endif
                        </span>
                        <a
                            href="{{ $moderatorGuideUrl }}"
                            class="kk-moderator-guide-link"
                            data-kk-nav="moderator-guide"
                            target="_blank"
                            rel="noopener noreferrer"
                            style="{{ $kkModeratorGuideBtn }}"
                        >Moderator uputstvo</a>
                    @elseif(! $isKkAdmin)
                        <span @class(['text-sm text-gray-700', 'dark:text-gray-200' => ! $isKkSection]) style="display:inline-flex; align-items:center;">
                            @if(auth()->user()->role && auth()->user()->role->name === 'konkurs_admin')
                                Administrator konkursa
                            @else
                                {{ Auth::user()->name }}
                            @endif
                        </span>
                    @endif
                    <form method="POST" action="{{ route('logout') }}" style="margin:0; display:flex; align-items:stretch;">
                        @csrf
                        <button
                            type="submit"
                            class="kk-logout-link"
                            style="{{ $kkLogoutBtn }}"
                        >
                            Odjava
                        </button>
                    </form>
                @endauth
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button
                    type="button"
                    data-kk-mobile-nav-toggle
                    aria-expanded="false"
                    aria-controls="kk-mobile-nav-menu"
                    aria-label="Navigacija"
                    @class([
                        'inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out',
                        'dark:text-gray-500 dark:hover:text-gray-400 dark:hover:bg-gray-900 dark:focus:bg-gray-900 dark:focus:text-gray-400' => ! $isKkSection,
                    ])
                >
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                        <path data-kk-mobile-nav-icon="closed" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path data-kk-mobile-nav-icon="open" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div id="kk-mobile-nav-menu" data-kk-mobile-nav-menu class="hidden sm:hidden">
        <div class="pt-2 pb-3 px-2 space-y-2">
            @if($isKkAdmin || $isKkSection)
                @if(! $isKkAdmin || $isKkPublicPortalContext)
                    <a
                        href="{{ route('cultural-calendar.index') }}"
                        style="{{ $kkNavBtnMobile(request()->routeIs('cultural-calendar.index')) }}"
                    >Kalendar kulture</a>
                @endif
                @if($showPublicEventsNav)
                    <a
                        href="{{ route('cultural-calendar.events') }}"
                        data-kk-nav="events-public"
                        style="{{ $kkNavBtnMobile(request()->routeIs('cultural-calendar.events')) }}"
                    >Događaji</a>
                @endif
                @if(! $isKkAdmin || $isKkPublicPortalContext)
                    <a
                        href="{{ route('cultural-calendar.archive') }}"
                        style="{{ $kkNavBtnMobile(request()->routeIs('cultural-calendar.archive')) }}"
                    >Arhiva događaja</a>
                @endif
                @if($showPublicManifestationsNav)
                    <a
                        href="{{ route('cultural-calendar.manifestations') }}"
                        data-kk-nav="mf-public"
                        style="{{ $kkNavBtnMobile(request()->routeIs('cultural-calendar.manifestations', 'cultural-calendar.manifestation')) }}"
                    >Manifestacije</a>
                @endif
                    @if(! $isKkAdmin)
                        @auth
                            @if(\App\Support\CulturalPortalAccess::isPlatformUserActive(auth()->user()))
                                <a
                                    href="{{ route('cultural-organizer-creation-requests.create') }}"
                                    style="{{ $kkNavBtnMobile(request()->routeIs('cultural-organizer-creation-requests.create', 'cultural-organizer-creation-requests.store')) }}"
                                >Zahtjev za Organizatora</a>
                            @endif
                        @endauth
                    @endif
                    @if($isKkAdmin && $isKkPublicPortalContext)
                        <a
                            href="{{ route('cultural-editorial-dashboard.index') }}"
                            data-kk-nav="bridge-editorial"
                            style="{{ $kkNavBtnMobile(request()->routeIs('cultural-editorial-dashboard.*')) }}"
                        >Urednički portal</a>
                    @endif
                    @if($isKkAdmin && $isKkEditorialPortalContext)
                        <a
                            href="{{ route('cultural-calendar.index') }}"
                            data-kk-nav="bridge-public"
                            style="{{ $kkNavBtnMobile(request()->routeIs('cultural-calendar.*')) }}"
                        >Kalendar kulture</a>
                        <a
                            href="{{ route('cultural-editorial-dashboard.index') }}"
                            data-kk-nav="kontrolna-tabla"
                            style="{{ $kkNavBtnMobile(request()->routeIs('cultural-editorial-dashboard.*')) }}"
                        >Kontrolna tabla</a>
                        <a
                            href="{{ route('cultural-event-entries.index') }}"
                            data-kk-nav="events-editorial"
                            style="{{ $kkNavBtnMobile(request()->routeIs('cultural-event-entries.*')) }}"
                        >Upravljanje događajima</a>
                        <a
                            href="{{ route('cultural-manifestations.index') }}"
                            data-kk-nav="mf-editorial"
                            style="{{ $kkNavBtnMobile(request()->routeIs('cultural-manifestations.*')) }}"
                        >Upravljanje manifestacijama</a>
                        <a
                            href="{{ route('cultural-locations.index') }}"
                            style="{{ $kkNavBtnMobile(request()->routeIs('cultural-locations.*')) }}"
                        >Lokacije</a>
                        <a
                            href="{{ route('cultural-categories.index') }}"
                            style="{{ $kkNavBtnMobile(request()->routeIs('cultural-categories.*')) }}"
                        >Kategorije</a>
                        <a
                            href="{{ route('cultural-tags.index') }}"
                            style="{{ $kkNavBtnMobile(request()->routeIs('cultural-tags.*')) }}"
                        >Oznake</a>
                        <a
                            href="{{ route('cultural-organizers.index') }}"
                            style="{{ $kkNavBtnMobile(request()->routeIs('cultural-organizers.*')) }}"
                        >Organizatori</a>
                        <a
                            href="{{ route('cultural-editorial-requests.index') }}"
                            data-kk-nav="zahtjevi"
                            style="{{ $kkNavBtnMobile(request()->routeIs('cultural-editorial-requests.*', 'cultural-organizer-creation-requests.index', 'cultural-organizer-creation-requests.show', 'cultural-organizer-creation-requests.approve', 'cultural-organizer-creation-requests.reject', 'cultural-moderator-requests.index', 'cultural-moderator-requests.show', 'cultural-moderator-requests.approve', 'cultural-moderator-requests.reject')) }}"
                        >Zahtjevi</a>
                    @endif
                    @auth
                        @if($isActiveModeratorUser)
                            <div data-kk-nav-moderator-block="1" class="space-y-2">
                                <a
                                    href="{{ route('cultural-moderator-dashboard.index') }}"
                                    data-kk-nav="kontrolna-tabla-moderator"
                                    style="{{ $kkNavBtnMobile(request()->routeIs('cultural-moderator-dashboard.*')) }}"
                                >Kontrolna tabla</a>
                                <a
                                    href="{{ route('cultural-moderator-workspace.index') }}"
                                    data-kk-nav="moderiranje"
                                    style="{{ $kkNavBtnMobile($isModeratorHubNav) }}"
                                >Moderiranje</a>
                                @if($moderatorActiveOrganizer)
                                    <span
                                        data-kk-nav="active-organizer"
                                        style="display:block;width:100%;box-sizing:border-box;padding:10px 16px;border-radius:8px;background:#f3f4f6;color:#111827;font-size:12px;font-weight:600;"
                                    >Organizator: {{ $moderatorActiveOrganizer->naziv }}</span>
                                @endif
                                @if($moderatorAvailableOrganizerCount > 1)
                                    <a
                                        href="{{ route('cultural-moderator-workspace.index') }}"
                                        data-kk-nav="promijeni-organizatora"
                                        style="{{ $kkNavBtnMobile(request()->routeIs('cultural-moderator-workspace.*')) }}"
                                    >Promijeni organizatora</a>
                                @endif
                            </div>
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
                @unless($isActiveModeratorUser)
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
                @endunless

                <div class="mt-3 px-2 space-y-2">
                    @if($isActiveModeratorUser)
                        <a
                            href="{{ $moderatorGuideUrl }}"
                            class="kk-moderator-guide-link"
                            data-kk-nav="moderator-guide"
                            target="_blank"
                            rel="noopener noreferrer"
                            style="{{ $kkModeratorGuideBtnMobile }}"
                        >Moderator uputstvo</a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="kk-logout-link" style="{{ $kkLogoutBtnMobile }}">
                            Odjava
                        </button>
                    </form>
                </div>
            @endauth
        </div>
    </div>
</nav>
<script>
(function () {
    var root = document.querySelector('[data-kk-mobile-nav-root]');
    if (!root) {
        return;
    }

    var toggle = root.querySelector('[data-kk-mobile-nav-toggle]');
    var menu = root.querySelector('[data-kk-mobile-nav-menu]');
    if (!toggle || !menu) {
        return;
    }

    var iconClosed = root.querySelector('[data-kk-mobile-nav-icon="closed"]');
    var iconOpen = root.querySelector('[data-kk-mobile-nav-icon="open"]');

    function setOpen(open) {
        menu.classList.toggle('hidden', !open);
        menu.classList.toggle('block', open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');

        if (iconClosed) {
            iconClosed.classList.toggle('hidden', open);
            iconClosed.classList.toggle('inline-flex', !open);
        }

        if (iconOpen) {
            iconOpen.classList.toggle('hidden', !open);
            iconOpen.classList.toggle('inline-flex', open);
        }
    }

    setOpen(false);

    toggle.addEventListener('click', function (event) {
        event.preventDefault();
        setOpen(menu.classList.contains('hidden'));
    });
})();
</script>
