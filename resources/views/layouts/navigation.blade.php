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

    // Shared KK nav button styles (existing red palette).
    $kkNavBtn = static function (bool $active): string {
        $bg = $active ? '#5f0c12' : '#7a0f17';

        return 'display:inline-flex;align-items:center;justify-content:center;padding:8px 14px;border-radius:8px;'
            ."background:{$bg};color:#ffffff;font-size:14px;font-weight:600;text-decoration:none;white-space:nowrap;";
    };
    // Shared desktop sizing for moderator entrypoints (Kontrolna tabla + Moderiranje) — both <a>.
    // Explicit equal width+height (content-width alone would make Moderiranje narrower).
    $kkModeratorEntryBtn = static function (bool $active) use ($kkNavBtn): string {
        return $kkNavBtn($active)
            .'box-sizing:border-box;line-height:1.25;min-height:38px;height:38px;width:128px;min-width:128px;';
    };
    $kkNavBtnMobile = static function (bool $active): string {
        $bg = $active ? '#5f0c12' : '#7a0f17';

        return 'display:block;width:100%;box-sizing:border-box;padding:10px 16px;border-radius:8px;'
            ."background:{$bg};color:#ffffff;font-size:16px;font-weight:600;text-decoration:none;";
    };
    $kkLogoutBtn = 'min-width:100px;background:#0d6efd;color:#ffffff;border:1px solid #0d6efd;border-radius:8px;'
        .'padding:8px 14px;display:inline-flex;align-items:center;justify-content:center;gap:6px;cursor:pointer;'
        .'text-decoration:none;font-size:14px;font-weight:600;';
    $kkLogoutBtnMobile = 'display:block;width:100%;box-sizing:border-box;padding:10px 16px;border-radius:8px;'
        .'background:#0d6efd;color:#ffffff;border:1px solid #0d6efd;font-size:16px;font-weight:600;'
        .'text-decoration:none;text-align:center;cursor:pointer;';

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
@if($isKkAdmin)
{{-- Inline CSS: Tailwind purge often omits sm:flex-col, which collapsed both rows into one horizontal flex. --}}
<style>
    .kk-admin-nav-desktop { display: none; }
    .kk-admin-nav-row {
        display: flex;
        flex-direction: row;
        flex-wrap: wrap;
        align-items: center;
        justify-content: center;
        gap: 8px;
        width: 100%;
        max-width: 100%;
        box-sizing: border-box;
    }
    @media (min-width: 640px) {
        .kk-admin-nav-desktop {
            display: flex;
            flex-direction: column;
            align-items: stretch;
            gap: 8px;
            margin-left: 16px;
            flex: 1 1 auto;
            min-width: 0;
            max-width: 100%;
            box-sizing: border-box;
        }
        .kk-admin-nav-desktop[data-kk-nav-context="public"] {
            flex-direction: row;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .kk-admin-nav-desktop[data-kk-nav-context="public"] .kk-admin-nav-row {
            width: auto;
            max-width: none;
            flex-wrap: nowrap;
        }
    }
</style>
@endif
<nav
    data-kk-mobile-nav-root
    @class([
        'bg-white border-b border-gray-100 print:hidden',
        'dark:bg-gray-800 dark:border-gray-700' => ! $isKkSection,
    ])
    @if($isKkAdmin) style="overflow-x: hidden;" @endif
>
    <!-- Primary Navigation Menu -->
    <div @class([
        'mx-auto px-4 sm:px-6 lg:px-8',
        'kk-shell' => $isKkSection,
        'max-w-7xl' => ! $isKkSection,
    ])>
        <div @class([
            'flex justify-between min-h-16 py-2',
            'items-start' => $isKkAdmin,
            'items-center flex-wrap gap-y-2' => ! $isKkAdmin,
        ]) style="{{ $isKkAdmin ? 'width:100%;max-width:100%;box-sizing:border-box;' : '' }}">
            <div @class([
                'flex justify-start min-w-0 flex-1',
                'items-start' => $isKkAdmin,
                'items-center flex-wrap gap-y-2' => ! $isKkAdmin,
            ]) style="{{ $isKkAdmin ? 'width:100%;max-width:100%;box-sizing:border-box;' : '' }}">
                <!-- Logo -->
                <div class="shrink-0 flex items-center" style="{{ $isKkAdmin ? 'min-height: 38px;' : '' }}">
                    <a href="{{ $isKkAdmin ? route('cultural-calendar.index') : ($isCompetitionAdmin ? route('admin.dashboard') : route('dashboard')) }}">
                        <img src="{{ asset('img/logo.png') }}" alt="Digital Kotor" class="block h-10 w-auto">
                    </a>
                </div>

                <!-- Navigation Links -->
                @if($isKkAdmin)
                    {{-- Explicit two-row desktop layout (column via scoped CSS, not Tailwind flex-col). --}}
                    <div
                        class="kk-admin-nav-desktop"
                        data-kk-nav-layout="two-row"
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
                            @endif
                        </div>
                        <div class="kk-admin-nav-row" data-kk-nav-row="2">
                            @if($isKkEditorialPortalContext)
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
                            <form method="POST" action="{{ route('logout') }}" style="margin:0;">
                                @csrf
                                <button
                                    type="submit"
                                    class="inline-flex items-center justify-center text-sm font-semibold"
                                    style="{{ $kkLogoutBtn }}"
                                >
                                    Odjava
                                </button>
                            </form>
                        </div>
                    </div>
                @elseif($isKkSection)
                    <div
                        class="hidden sm:flex sm:items-center sm:justify-start sm:flex-wrap"
                        style="margin-left: 16px; gap: 8px; flex: 1 1 auto; min-width: 0;"
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
                            @if($isActiveModeratorUser)
                                <span
                                    data-kk-nav-moderator-block="1"
                                    style="display:inline-flex;align-items:center;flex-wrap:wrap;gap:8px;margin-left:4px;padding-left:8px;border-left:2px solid #e5e7eb;"
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
                                            style="display:inline-flex;align-items:center;padding:8px 12px;border-radius:8px;background:#f3f4f6;color:#111827;font-size:13px;font-weight:600;white-space:nowrap;"
                                        >Organizator: {{ $moderatorActiveOrganizer->naziv }}</span>
                                    @endif
                                    @if($moderatorAvailableOrganizerCount > 1)
                                        <a
                                            href="{{ route('cultural-moderator-workspace.index') }}"
                                            data-kk-nav="promijeni-organizatora"
                                            style="{{ $kkNavBtn(request()->routeIs('cultural-moderator-workspace.*')) }}"
                                        >Promijeni organizatora</a>
                                    @endif
                                </span>
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

            <!-- User info + Logout (non-kk_admin only; kk_admin must not place name among nav buttons) -->
            @unless($isKkAdmin)
            <div class="hidden sm:flex sm:items-center sm:ms-6 shrink-0" style="align-items:center; gap:12px;">
                @auth
                    <span @class(['text-sm text-gray-700', 'dark:text-gray-200' => ! $isKkSection]) style="margin-right: 8px;">
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
                            class="inline-flex items-center justify-center text-sm font-semibold"
                            style="{{ $kkLogoutBtn }}"
                        >
                            Odjava
                        </button>
                    </form>
                @endauth
            </div>
            @endunless

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
                                        style="display:block;width:100%;box-sizing:border-box;padding:10px 16px;border-radius:8px;background:#f3f4f6;color:#111827;font-size:14px;font-weight:600;"
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

                <div class="mt-3 px-2 space-y-2">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" style="{{ $kkLogoutBtnMobile }}">
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
