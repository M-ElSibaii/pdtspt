<nav x-data="{ open: false }" class="bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-row justify-between h-16">
            <div class="grow flex flex-row">
                <!-- Logo -->
                <div class="space-x-8 -my-px ml-10 ">
                    <a href="{{ route('dashboard') }}">
                        <img src="{{ asset ('img/logoUminhoPdts.svg')}}" alt="Application logo" width="170 px" style="padding-top: 5px; min-width: 170px;" />
                    </a>
                </div>

                <!-- Navigation Links -->

                @if (Route::has('login'))
                <div class="sm:hidden space-x-8 -my-px ml-10 flex">
                    <x-nav-link :href="route('home')" :active="request()->routeIs('home')">
                        {{ __('Home') }}
                    </x-nav-link>
                </div>

                <div class="sm:hidden space-x-8 -my-px ml-10 flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('PDTs') }}
                    </x-nav-link>
                </div>
                @auth
                <div class="sm:hidden space-x-8 -my-px ml-10 flex">
                    <x-nav-link :href="route('apidoc')" :active="request()->routeIs('apidoc')">
                        {{ __('Documentação API') }}
                    </x-nav-link>
                </div>

                @endauth
                <div class="sm:hidden space-x-8 -my-px ml-10 flex">
                    <x-nav-link :href="route('participantes')" :active="request()->routeIs('participantes')">
                        {{ __('Participantes') }}
                    </x-nav-link>
                </div>
                <div class="sm:hidden space-x-8 -my-px ml-10 flex">
                    <x-nav-link :href="route('contact.store')" :active="request()->routeIs('contact.store')">
                        {{ __('Contactos') }}
                    </x-nav-link>
                </div>
                @auth
                @if (Auth::user()->isAdmin == 1)
                <div class="sm:hidden space-x-8 -my-px ml-10 flex">
                    <x-nav-link :href="route('admin')" :active="request()->routeIs('admin')">
                        {{ __('Admin') }}
                    </x-nav-link>
                </div>
                @endif
                @endauth
                @auth
                @if (Auth::user()->isAdmin == 1)
                <div class="sm:hidden space-x-8 -my-px ml-10 flex">
                    <x-nav-link :href="route('pdtinput')" :active="request()->routeIs('pdtinput')">
                        {{ __('PDTcreate') }}
                    </x-nav-link>
                </div>
                @endif
                @endauth
                @endif

            </div>
            <!-- Login signup -->
            {{-- :class="'inline-flex items-center px-4 py-2 bg-slate-700 dark:bg-slate-200 rounded-md font-semibold text-xs text-white dark:text-gray-900 uppercase tracking-widest hover:bg-slate-900 dark:hover:bg-white focus:bg-slate-900 dark:focus:bg-white active:bg-slate-900 dark:active:bg-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150'"  --}}
            @if (Auth::user())
            @else
            <div class="flex items-center ml-6">
                <div class="grid grid-cols-2">
                    @if (Route::has('login'))
                    <div class="sm:hidden flex">
                        <x-button-primary-pdts link="{{route('login')}}" :active="request()->routeIs('login')" title="{{ __('Login') }}">
                        </x-button-primary-pdts>
                    </div>
                    @if (Route::has('register'))
                    <div class="sm:hidden flex">
                        <a href="{{route('register')}}">
                            <x-secondary-button :active="request()->routeIs('register')">
                                {{ __('Registo') }}
                            </x-secondary-button>
                        </a>
                    </div>
                    @endif
                    @endif
                </div>
            </div>
            @endif
            <!-- Settings Dropdown -->
            <div class="sm:hidden flex items-center ml-6">

                @auth
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white dark:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none transition ease-in-out duration-150">
                            @if (Auth::user()->photo)
                            <img class="rounded-circle shadow-1-strong me-3" src="{{ asset(Auth::user()->photo) }}" alt="{{ Auth::user()->name }}" style="width: 40px; height: 40px; border-radius: 50%;">
                            @else
                            <img class="rounded-circle shadow-1-strong me-3" src="{{ asset('/img/users/default.png') }}" alt="{{ Auth::user()->name }}" style="width: 40px; height: 40px; border-radius: 50%;">
                            @endif
                            <div class="ml-2">{{ Auth::user()->name }}</div>
                            <div class="ml-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>

                        </button>
                    </x-slot>
                    @endauth
                    <x-slot name="content">
                        @auth
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Perfil') }}
                        </x-dropdown-link>
                        <x-dropdown-link :href="route('privacypolicy')">
                            {{ __('Política de privacidade') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')" onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Logout') }}
                            </x-dropdown-link>
                        </form>
                        @endauth
                    </x-slot>
                </x-dropdown>

            </div>

            <!-- Hamburger -->
            <div class="-mr-2 flex items-center md:hidden lg:hidden">
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
    <div :class="{'block': open, 'hidden': ! open}" class="hidden">
        @if (Route::has('login'))
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('home')" :active="request()->routeIs('home')">
                {{ __('Home') }}
            </x-responsive-nav-link>
        </div>
        @auth
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('PDTs') }}
            </x-responsive-nav-link>
        </div>
        @endif
        @endauth
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('contact.store')" :active="request()->routeIs('contact.store')">
                {{ __('Contactos') }}
            </x-responsive-nav-link>
        </div>
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('participantes')" :active="request()->routeIs('participantes')">
                {{ __('Participantes') }}
            </x-responsive-nav-link>
        </div>
        @if (Route::has('login'))
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('login')" :active="request()->routeIs('login')">
                {{ __('Login') }}
            </x-responsive-nav-link>
        </div>
        @if (Route::has('register'))
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('register')" :active="request()->routeIs('register')">
                {{ __('Registo') }}
            </x-responsive-nav-link>
        </div>
        @endif
        @endif

        <!-- Responsive Settings Options -->
        @auth
        <div class="pt-4 pb-1 border-t border-gray-200 dark:border-gray-600">

            <div class="px-4">
                <div class="font-medium text-base text-gray-800 dark:text-gray-200">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">

                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Perfil') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('privacypolicy')">
                    {{ __('Política de privacidade') }}
                </x-responsive-nav-link>
                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')" onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>

            </div>
        </div>
        @endauth
    </div>
</nav>