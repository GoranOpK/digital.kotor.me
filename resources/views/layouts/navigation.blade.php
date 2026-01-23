<nav x-data="{ open: false }" class="bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}">
                        <img src="{{ asset('img/logo.png') }}" alt="Digital Kotor" class="block h-10 w-auto">
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('home')" :active="request()->routeIs('home')">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 inline-block align-text-bottom" style="margin-right: 4px;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125h9.75a1.125 1.125 0 0 0 1.125-1.125V9.75M8.25 21h8.25" />
                        </svg>
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
                        @if(auth()->user()->role && auth()->user()->role->name === 'komisija')
                            <x-nav-link :href="route('competitions.archive')" :active="request()->routeIs('competitions.archive')">
                                Arhiva konkursa
                            </x-nav-link>
                        @endif
                    @endauth
                </div>
            </div>

            <!-- User info + Logout -->
            <div class="sm:flex sm:items-center sm:ms-6" style="display:flex; align-items:center; gap:12px;">
                @auth
                    <span class="text-sm text-gray-700 dark:text-gray-200" style="margin-right: 16px;">
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
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-900 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-900 focus:text-gray-500 dark:focus:text-gray-400 transition duration-150 ease-in-out">
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
            <x-responsive-nav-link :href="route('home')" :active="request()->routeIs('home')">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 inline-block align-text-bottom" style="margin-right: 4px;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125h9.75a1.125 1.125 0 0 0 1.125-1.125V9.75M8.25 21h8.25" />
                </svg>
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
                @if(auth()->user()->role && auth()->user()->role->name === 'komisija')
                    <x-responsive-nav-link :href="route('competitions.archive')" :active="request()->routeIs('competitions.archive')">
                        Arhiva konkursa
                    </x-responsive-nav-link>
                @endif
            @endauth
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200 dark:border-gray-600">
            @auth
                <div class="px-4">
                    <div class="font-medium text-base text-gray-800 dark:text-gray-200">
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
