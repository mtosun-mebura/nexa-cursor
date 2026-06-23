@php
    $branding = $branding ?? app(\App\Services\WebsiteBuilderService::class)->getSiteBranding(
        request()->routeIs('taxi.portal.*') || (($frontendResolvedModuleName ?? null) === 'taxi') ? 'taxi' : null
    );
    $dashboardLinkLabel = $dashboardLinkLabel ?? ($branding['dashboard_link_label'] ?? \App\Models\GeneralSetting::get('dashboard_link_label', 'Mijn Nexa'));
    $dashboardLinkVisible = $dashboardLinkVisible ?? (bool) ($branding['dashboard_link_visible'] ?? (\App\Models\GeneralSetting::get('dashboard_link_visible', '1') === '1'));
    $dashboardLinkUrl = $dashboardLinkUrl ?? ($branding['dashboard_link_url'] ?? route('dashboard'));
    $showSkillmatchingNav = ($showSkillmatchingAppLinks ?? false) && ! request()->routeIs('taxi.portal.*');
    $showGuestSkillmatchingLinks = $showGuestSkillmatchingLinks ?? ($frontendResolvedModuleName ?? null) !== 'taxi';
    $hideGuestLoginButton = request()->routeIs('login', 'frontend.set-password') && ! $showGuestSkillmatchingLinks;
    $isFrontendAppPage = request()->routeIs('dashboard', 'profile', 'matches', 'agenda', 'applications', 'applications.*', 'settings', 'taxi.portal.*');
    $isPublicWebsitePage = ! $isFrontendAppPage;
    $isTaxiPortalPage = request()->routeIs('taxi.portal.*');
    $isTaxiContext = $isTaxiPortalPage || (($frontendResolvedModuleName ?? null) === 'taxi');
    $profileMenuLabel = $isTaxiContext ? 'Mijn gegevens' : 'Mijn Profiel';
    $profileMenuUrl = $isTaxiContext
        ? route('taxi.portal.dashboard', ['tab' => 'profile'])
        : route('profile');
    $mobileMenuHiddenClass = $isTaxiPortalPage ? 'lg:hidden' : 'md:hidden';
    $desktopNavVisibleClass = $isTaxiPortalPage ? 'hidden lg:flex' : 'hidden md:flex';
@endphp
<!-- Header -->
<header
    @class([
        'relative bg-white dark:bg-gray-900 border-b border-gray-200 sticky top-0 z-50',
        'dark:border-gray-800' => $isTaxiPortalPage,
        'dark:border-gray-600' => ! $isTaxiPortalPage,
        'shadow-none' => $isTaxiPortalPage,
        'shadow-sm' => ! $isTaxiPortalPage,
    ])
    x-data="{ mobileMenuOpen: false }"
>
    <div class="container-custom">
        <div class="flex justify-between items-center h-16 md:h-20">
            <!-- Hamburger (links) + logo -->
            <div class="flex items-center gap-2 flex-shrink-0">
                <div class="{{ $mobileMenuHiddenClass }} flex-shrink-0">
                    <button @click="mobileMenuOpen = !mobileMenuOpen"
                            class="frontend-header-mobile-menu-toggle p-2 rounded-lg text-gray-700 dark:text-white hover:text-gray-900 dark:hover:text-white/90 hover:bg-gray-100 dark:hover:bg-white/10 transition-colors duration-200"
                            :aria-expanded="mobileMenuOpen"
                            :aria-label="mobileMenuOpen ? 'Menu sluiten' : 'Menu openen'">
                        <svg x-show="!mobileMenuOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-cloak>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                        <svg x-show="mobileMenuOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-cloak>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div class="ml-2 md:ml-8 py-1">
                    @include('frontend.layouts.partials.brand-logo', [
                        'branding' => $branding,
                        'logoHref' => route('home'),
                    ])
                </div>
            </div>

            <!-- Navigatie (desktop) -->
            <nav class="{{ $desktopNavVisibleClass }} items-center justify-center flex-1 px-4 gap-4 lg:gap-6" role="navigation" aria-label="Hoofdnavigatie">
                @auth
                    <a href="{{ route('home') }}" class="text-gray-900 dark:text-gray-100 hover:text-blue-600 dark:hover:text-blue-400 px-3 py-2 rounded-md text-base font-medium transition-colors duration-200 {{ request()->routeIs('home') ? 'text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/30 font-semibold' : '' }}">
                        Home
                    </a>
                    @if($showSkillmatchingNav)
                    <a href="{{ route('dashboard') }}" class="text-gray-900 dark:text-gray-100 hover:text-blue-600 dark:hover:text-blue-400 px-3 py-2 rounded-md text-base font-medium transition-colors duration-200 {{ request()->routeIs('dashboard') ? 'text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/30 font-semibold' : '' }}">
                        Dashboard
                    </a>
                    <a href="{{ route('jobs.index') }}" class="text-gray-900 dark:text-gray-100 hover:text-blue-600 dark:hover:text-blue-400 px-3 py-2 rounded-md text-base font-medium transition-colors duration-200 {{ request()->routeIs('jobs.*') ? 'text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/30 font-semibold' : '' }}">
                        Vacatures
                    </a>
                    <a href="{{ route('matches') }}" class="text-gray-900 dark:text-gray-100 hover:text-blue-600 dark:hover:text-blue-400 px-3 py-2 rounded-md text-base font-medium transition-colors duration-200 {{ request()->routeIs('matches') ? 'text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/30 font-semibold' : '' }}">
                        Matches
                    </a>
                    @if(auth()->user() && auth()->user()->can('view-agenda'))
                    <a href="{{ route('agenda') }}" class="text-gray-900 dark:text-gray-100 hover:text-blue-600 dark:hover:text-blue-400 px-3 py-2 rounded-md text-base font-medium transition-colors duration-200 {{ request()->routeIs('agenda') ? 'text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/30 font-semibold' : '' }}">
                        Agenda
                    </a>
                    @endif
                    @endif
                @else
                    <a href="{{ route('home') }}" class="text-gray-900 dark:text-gray-100 hover:text-blue-600 dark:hover:text-blue-400 px-3 py-2 rounded-md text-base font-medium transition-colors duration-200 {{ request()->routeIs('home') ? 'text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/30 font-semibold' : '' }}">
                        Home
                    </a>
                    @if($showGuestSkillmatchingLinks)
                    <a href="{{ route('jobs.index') }}" class="text-gray-900 dark:text-gray-100 hover:text-blue-600 dark:hover:text-blue-400 px-3 py-2 rounded-md text-base font-medium transition-colors duration-200 {{ request()->routeIs('jobs.*') ? 'text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/30 font-semibold' : '' }}">
                        Vacatures
                    </a>
                    <a href="{{ route('about') }}" class="text-gray-900 dark:text-gray-100 hover:text-blue-600 dark:hover:text-blue-400 px-3 py-2 rounded-md text-base font-medium transition-colors duration-200 {{ request()->routeIs('about') ? 'text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/30 font-semibold' : '' }}">
                        Over Ons
                    </a>
                    <a href="{{ route('contact') }}" class="text-gray-900 dark:text-gray-100 hover:text-blue-600 dark:hover:text-blue-400 px-3 py-2 rounded-md text-base font-medium transition-colors duration-200 {{ request()->routeIs('contact') ? 'text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/30 font-semibold' : '' }}">
                        Contact
                    </a>
                    @endif
                @endauth
            </nav>
            
            <!-- Right side: theme-toggle, Inloggen/Dashboard -->
            <div class="relative flex items-center gap-2 md:gap-2.5 flex-shrink-0">
                <!-- Light/Dark mode toggle – altijd zichtbaar -->
                <div class="flex items-center gap-0.5">
                    <button onclick="toggleDarkMode()" 
                            class="p-2 rounded-lg text-gray-700 dark:text-white hover:text-blue-600 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors duration-200"
                            aria-label="Schakel tussen licht en donker thema" title="Licht / Donker">
                        <svg id="dark-mode-icon-moon" class="w-5 h-5 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                        </svg>
                        <svg id="dark-mode-icon-sun" class="w-5 h-5 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                    </button>
                    @if(\App\Models\GeneralSetting::get('ai_chat_enabled', '0') === '1')
                    @include('frontend.components.ai-chatbot-trigger')
                    @endif
                </div>

                @auth
                    <!-- Op ingelogde app-pagina's (dashboard, profiel, agenda, etc.): notificaties, chat, user dropdown; op website-pagina's (home, over ons, vacatures, etc.): alleen "Mijn Nexa" (als ingeschakeld) -->
                    @if(auth()->check() && auth()->user())
                        @if($isFrontendAppPage)
                            @if($showSkillmatchingNav)
                            @include('frontend.partials.topbar-notification-dropdown')
                            @include('frontend.partials.topbar-chat')
                            @endif
                            @include('frontend.partials.topbar-user-dropdown', [
                                'profileMenuLabel' => $profileMenuLabel,
                                'profileMenuUrl' => $profileMenuUrl,
                            ])
                        @elseif($dashboardLinkVisible && $isPublicWebsitePage)
                            <a href="{{ $dashboardLinkUrl }}" class="btn btn-primary text-sm font-medium px-4 py-2 rounded-lg">
                                {{ $dashboardLinkLabel }}
                            </a>
                        @endif
                    @endif
                @else
                    <!-- Inloggen rechtsboven (niet op Mijn Taxi-login/wachtwoord-pagina) -->
                    <div class="flex items-center gap-2 md:gap-3">
                        @unless($hideGuestLoginButton)
                        <a href="{{ route('login') }}" class="btn btn-primary text-sm font-medium px-3 py-2 md:px-4 md:py-2 rounded-lg shrink-0">
                            Inloggen
                        </a>
                        @endunless
                        @if($showGuestSkillmatchingLinks)
                        <a href="{{ route('register') }}" class="hidden sm:inline-flex items-center px-3 py-2 md:px-4 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-200 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                            Registreren
                        </a>
                        @endif
                    </div>
                @endauth
            </div>
        </div>
    </div>

    <!-- Mobiel menu: overlay over content (niet meeschuiven) -->
    <div x-show="mobileMenuOpen"
         x-cloak
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="frontend-header-mobile-menu {{ $mobileMenuHiddenClass }} absolute top-full left-0 right-0 z-50 shadow-lg max-h-[calc(100dvh-4rem)] overflow-y-auto border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900">
        <div class="container-custom py-4 space-y-1">
                @if($isTaxiPortalPage)
                    @php
                        $taxiPortalMobileNav = [
                            'dashboard' => 'Dashboard',
                            'rides' => 'Ritten',
                            'invoices' => 'Facturen',
                            'profile' => 'Mijn gegevens',
                        ];
                        $taxiPortalMobileLinkClass = 'block px-4 py-3 rounded-lg text-base text-gray-900 dark:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-800';
                    @endphp
                    @foreach($taxiPortalMobileNav as $tabKey => $tabLabel)
                        <a href="{{ route('taxi.portal.dashboard', ['tab' => $tabKey]) }}"
                           @click="mobileMenuOpen = false"
                           class="{{ $taxiPortalMobileLinkClass }}">
                            {{ $tabLabel }}
                        </a>
                    @endforeach
                @elseif(auth()->check())
                    <a href="{{ route('home') }}" class="block px-4 py-3 rounded-lg text-base text-gray-900 dark:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-800">
                        Home
                    </a>
                    @if($showSkillmatchingNav)
                    <a href="{{ route('dashboard') }}" class="block px-4 py-3 rounded-lg text-base text-gray-900 dark:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-800">
                        Dashboard
                    </a>
                    <a href="{{ route('jobs.index') }}" class="block px-4 py-3 rounded-lg text-base text-gray-900 dark:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-800">
                        Vacatures
                    </a>
                    <a href="{{ route('matches') }}" class="block px-4 py-3 rounded-lg text-base text-gray-900 dark:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-800">
                        Matches
                    </a>
                    @if(auth()->user() && auth()->user()->can('view-agenda'))
                    <a href="{{ route('agenda') }}" class="block px-4 py-3 rounded-lg text-base text-gray-900 dark:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-800">
                        Agenda
                    </a>
                    @endif
                    @endif
                @else
                    <a href="{{ route('home') }}" class="block px-4 py-3 rounded-lg text-base text-gray-900 dark:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-800">
                        Home
                    </a>
                    @if($showGuestSkillmatchingLinks)
                    <a href="{{ route('jobs.index') }}" class="block px-4 py-3 rounded-lg text-base text-gray-900 dark:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-800">
                        Vacatures
                    </a>
                    <a href="{{ route('about') }}" class="block px-4 py-3 rounded-lg text-base text-gray-900 dark:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-800">
                        Over Ons
                    </a>
                    <a href="{{ route('contact') }}" class="block px-4 py-3 rounded-lg text-base text-gray-900 dark:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-800">
                        Contact
                    </a>
                    @endif
                    @unless($hideGuestLoginButton)
                    <div class="pt-2 mt-2 border-t border-gray-200 dark:border-gray-600 space-y-2">
                        <a href="{{ route('login') }}" class="block px-4 py-3 rounded-lg text-base font-medium text-center btn btn-primary">
                            Inloggen
                        </a>
                        @if($showGuestSkillmatchingLinks)
                        <a href="{{ route('register') }}" class="block px-4 py-3 rounded-lg text-base font-medium text-center text-gray-900 dark:text-gray-100 border border-gray-300 dark:border-gray-600 hover:bg-gray-200 dark:hover:bg-gray-700">
                            Registreren
                        </a>
                        @endif
                    </div>
                    @endunless
                @endif
        </div>
    </div>
</header>
<!-- End of Header -->
