<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
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
                        {{ Lg::t('Home') }}
                    </x-nav-link>
                </div>

                <div class="sm:hidden space-x-8 -my-px ml-10 flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ Lg::t('PDTs') }}
                    </x-nav-link>
                </div>


                <div class="sm:hidden space-x-8 -my-px ml-10 flex">
                    <x-nav-link :href="route('apidoc')" :active="request()->routeIs('apidoc')">
                        {{ Lg::t('Documentação API') }}
                    </x-nav-link>
                </div>


                <div class="sm:hidden space-x-8 -my-px ml-10 flex">
                    <x-nav-link :href="route('participantes')" :active="request()->routeIs('participantes')">
                        {{ Lg::t('Participantes') }}
                    </x-nav-link>
                </div>

                <div class="sm:hidden space-x-8 -my-px ml-10 flex">
                    <x-nav-link :href="route('knowledge')" :active="request()->routeIs('knowledge')">
                        {{ Lg::t('Publicações') }}
                    </x-nav-link>
                </div>

                <div class="sm:hidden space-x-8 -my-px ml-10 flex">
                    <x-nav-link :href="route('contact.store')" :active="request()->routeIs('contact.store')">
                        {{ Lg::t('Contactos') }}
                    </x-nav-link>
                </div>

                @auth
                @if (Auth::user()->isAdmin == 1)
                <div class="sm:hidden space-x-8 -my-px ml-10 flex">
                    <x-nav-link :href="route('admin')" :active="request()->routeIs('admin')">
                        {{ Lg::t('Admin') }}
                    </x-nav-link>
                </div>
                @endif
                @endauth
                @endif
            </div>


            <!-- Language toggle (PT/EN). Remembered for the session; never changes a record's URI.
                 Colours are inline: the layout's global `a:link { color: black }` outranks a
                 utility class, which would otherwise leave one of the two unreadable. -->
            <div class="flex items-center ml-4">
                <div style="display:inline-flex; border:1px solid #cbd5e1; border-radius:6px; overflow:hidden; font-size:12px; font-weight:700;"
                     role="group" aria-label="{{ Lg::t('Idioma') }}">
                    @foreach (Lg::supported() as $code)
                        @php $on = Lg::current() === $code; @endphp
                        <a href="{{ Lg::toggleUrl($code) }}"
                           hreflang="{{ $code }}"
                           aria-current="{{ $on ? 'true' : 'false' }}"
                           title="{{ $code === 'pt' ? Lg::t('Português') : Lg::t('Inglês') }}"
                           style="padding:6px 12px; text-decoration:none; line-height:1.2;
                                  background-color:{{ $on ? '#334155' : '#ffffff' }};
                                  color:{{ $on ? '#ffffff' : '#334155' }};">
                            {{ strtoupper($code) }}
                        </a>
                    @endforeach
                </div>
            </div>

            <!-- Login/Signup -->
            @guest
            <div class="flex items-center ml-6">
                <div class="grid grid-cols-2">
                    @if (Route::has('login'))
                    <div class="sm:hidden flex">
                        <x-button-primary-pdts link="{{ route('login') }}" :active="request()->routeIs('login')" title="{{ Lg::t('Login') }}">
                        </x-button-primary-pdts>
                    </div>
                    @endif

                    @if (Route::has('register'))
                    <div class="sm:hidden flex">
                        <a href="{{ route('register') }}">
                            <x-secondary-button :active="request()->routeIs('register')">
                                {{ Lg::t('Registo') }}
                            </x-secondary-button>
                        </a>
                    </div>
                    @endif
                </div>
            </div>
            @endguest

            <!-- Settings Dropdown -->
            @auth
            <div class="sm:hidden flex items-center ml-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
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
                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ Lg::t('Perfil') }}
                        </x-dropdown-link>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">
                                {{ Lg::t('Logout') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>
            @endauth

            <!-- Hamburger Menu (responsive) -->
            <div class="-mr-2 flex items-center md:hidden lg:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
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
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('home')" :active="request()->routeIs('home')">
                {{ Lg::t('Home') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ Lg::t('PDTs') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('apidoc')" :active="request()->routeIs('apidoc')">
                {{ Lg::t('Documentação API') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('contact.store')" :active="request()->routeIs('contact.store')">
                {{ Lg::t('Contactos') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('participantes')" :active="request()->routeIs('participantes')">
                {{ Lg::t('Participantes') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('knowledge')" :active="request()->routeIs('knowledge')">
                {{ Lg::t('Publicações') }}
            </x-responsive-nav-link>
            <div class="flex gap-2 px-4 py-2">
                @foreach (Lg::supported() as $code)
                    @php $on = Lg::current() === $code; @endphp
                    <a href="{{ Lg::toggleUrl($code) }}" hreflang="{{ $code }}"
                       aria-current="{{ $on ? 'true' : 'false' }}"
                       style="padding:4px 12px; font-size:12px; font-weight:700; border-radius:6px;
                              text-decoration:none; border:1px solid {{ $on ? '#334155' : '#cbd5e1' }};
                              background-color:{{ $on ? '#334155' : '#ffffff' }};
                              color:{{ $on ? '#ffffff' : '#334155' }};">
                        {{ strtoupper($code) }}
                    </a>
                @endforeach
            </div>
            @guest
            @if (Route::has('login'))
            <x-responsive-nav-link :href="route('login')" :active="request()->routeIs('login')">
                {{ Lg::t('Login') }}
            </x-responsive-nav-link>
            @endif
            @if (Route::has('register'))
            <x-responsive-nav-link :href="route('register')" :active="request()->routeIs('register')">
                {{ Lg::t('Registo') }}
            </x-responsive-nav-link>
            @endif
            @endguest
            @auth
            @if (Auth::user()->isAdmin == 1)
            <x-responsive-nav-link :href="route('admin')" :active="request()->routeIs('admin')">
                {{ Lg::t('Admin') }}
            </x-responsive-nav-link>
            @endif
            @endauth
        </div>

        <!-- Responsive Settings Options -->
        @auth
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
            </div>
            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ Lg::t('Perfil') }}
                </x-responsive-nav-link>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">
                        {{ Lg::t('Logout') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
        @endauth
    </div>
</nav>