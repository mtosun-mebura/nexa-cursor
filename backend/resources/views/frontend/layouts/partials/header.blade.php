<!-- Header -->
<header class="bg-white dark:bg-gray-900 shadow-sm border-b border-gray-200 dark:border-gray-700 sticky top-0 z-50" 
        x-data="{ 
            mobileMenuOpen: false,
            languageMenuOpen: false,
            currentLanguage: '{{ app()->getLocale() }}',
            switchLanguage(lang) {
                this.currentLanguage = lang;
                fetch('{{ route("language.switch") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ language: lang })
                }).then(() => {
                    window.location.reload();
                });
                this.languageMenuOpen = false;
            }
        }">
    <div class="container-custom">
        <div class="flex justify-between items-center h-16 md:h-20">
            <!-- Logo and Navigation -->
            <div class="flex items-center gap-4">
                <!-- Logo -->
                <div class="flex-shrink-0 ml-2 md:ml-8 py-1">
                    <a href="{{ route('home') }}" class="flex items-center" aria-label="Nexa Skillmatching">
                        <img src="{{ asset('images/nexa-skillmatching-logo.png') }}" alt="Nexa Skillmatching" class="h-12 md:h-16 w-auto">
                    </a>
                </div>
                
                <!-- Desktop Navigation -->
                <nav class="hidden md:flex items-center space-x-1" role="navigation" aria-label="Hoofdnavigatie">
                    @auth
                        <a href="{{ route('dashboard') }}" class="text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200 {{ request()->routeIs('dashboard') ? 'text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20' : '' }}">
                            Dashboard
                        </a>
                        <a href="{{ route('jobs.index') }}" class="text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200 {{ request()->routeIs('jobs.*') ? 'text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20' : '' }}">
                            Vacatures
                        </a>
                        <a href="{{ route('matches') }}" class="text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200 {{ request()->routeIs('matches') ? 'text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20' : '' }}">
                            Matches
                        </a>
                        @if(auth()->user() && auth()->user()->can('view-agenda'))
                        <a href="{{ route('agenda') }}" class="text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200 {{ request()->routeIs('agenda') ? 'text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20' : '' }}">
                            Agenda
                        </a>
                        @endif
                    @else
                        <a href="{{ route('home') }}" class="text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200 {{ request()->routeIs('home') ? 'text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20' : '' }}">
                            Home
                        </a>
                        <a href="{{ route('jobs.index') }}" class="text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200 {{ request()->routeIs('jobs.*') ? 'text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20' : '' }}">
                            Vacatures
                        </a>
                        <a href="{{ route('about') }}" class="text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200 {{ request()->routeIs('about') ? 'text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20' : '' }}">
                            Over Ons
                        </a>
                        <a href="{{ route('contact') }}" class="text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200 {{ request()->routeIs('contact') ? 'text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20' : '' }}">
                            Contact
                        </a>
                    @endauth
                </nav>
            </div>
            
            <!-- Right side actions -->
            <div class="flex items-center gap-2.5">
                @auth
                    <!-- Dark mode toggle -->
                    <div class="flex items-center">
                        <button onclick="toggleDarkMode()" 
                                class="p-2 rounded-lg text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors duration-200"
                                aria-label="Toggle dark mode">
                            <!-- Moon icon for light mode (to switch to dark) -->
                            <svg id="dark-mode-icon-moon" class="w-5 h-5 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                            </svg>
                            <!-- Sun icon for dark mode (to switch to light) -->
                            <svg id="dark-mode-icon-sun" class="w-5 h-5 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                        </button>
                    </div>
                    
                    <!-- Language selector -->
                    <div class="relative">
                        <button @click="languageMenuOpen = !languageMenuOpen" 
                                class="p-2 rounded-lg text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors duration-200"
                                aria-label="Select language">
                            <!-- Dutch flag -->
                            <span x-show="currentLanguage === 'nl'" class="fi fi-nl text-lg"></span>
                            <!-- English flag -->
                            <span x-show="currentLanguage === 'en'" class="fi fi-gb text-lg"></span>
                        </button>
                        
                        <div x-show="languageMenuOpen" 
                             @click.away="languageMenuOpen = false"
                             x-cloak
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="transform opacity-0 scale-95 translate-y-1"
                             x-transition:enter-end="transform opacity-100 scale-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-150"
                             x-transition:leave-start="transform opacity-100 scale-100 translate-y-0"
                             x-transition:leave-end="transform opacity-0 scale-95 translate-y-1"
                             class="absolute right-0 mt-2 w-24 bg-white dark:bg-gray-800 rounded-lg shadow-sm py-1 z-50 border border-gray-200 dark:border-gray-600"
                             role="menu">
                            <button @click="switchLanguage('nl')" 
                                    class="flex items-center justify-center w-full px-3 py-2 text-base text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200"
                                    role="menuitem">
                                <span class="fi fi-nl text-base"></span>
                            </button>
                            <button @click="switchLanguage('en')" 
                                    class="flex items-center justify-center w-full px-3 py-2 text-base text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200"
                                    role="menuitem">
                                <span class="fi fi-gb text-base"></span>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Mobile: Show icons, Desktop: Show icons -->
                    @if(auth()->check() && auth()->user())
                        @include('frontend.partials.topbar-notification-dropdown')
                        @include('frontend.partials.topbar-chat')
                        @include('frontend.partials.topbar-user-dropdown')
                    @endif
                @else
                    <div class="hidden md:flex items-center space-x-3">
                        <a href="{{ route('login') }}" class="text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200">
                            Inloggen
                        </a>
                        <a href="{{ route('register') }}" class="btn btn-primary">
                            Registreren
                        </a>
                    </div>
                @endauth
                
                <!-- Mobile menu button -->
                <div class="md:hidden">
                    <button @click="mobileMenuOpen = !mobileMenuOpen" 
                            class="p-2 rounded-lg text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors duration-200"
                            :aria-expanded="mobileMenuOpen"
                            aria-label="Menu openen">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Mobile Navigation -->
        <div x-show="mobileMenuOpen" 
             x-cloak
             x-transition:enter="transition ease-out duration-100"
             x-transition:enter-start="transform opacity-0 scale-95"
             x-transition:enter-end="transform opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-75"
             x-transition:leave-start="transform opacity-100 scale-100"
             x-transition:leave-end="transform opacity-0 scale-95"
             class="md:hidden">
            <div class="px-2 pt-2 pb-3 space-y-1 border-t border-gray-200 dark:border-gray-700">
                @auth
                    <a href="{{ route('dashboard') }}" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-gray-100 dark:hover:bg-gray-800">
                        Dashboard
                    </a>
                    <a href="{{ route('jobs.index') }}" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-gray-100 dark:hover:bg-gray-800">
                        Vacatures
                    </a>
                    <a href="{{ route('matches') }}" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-gray-100 dark:hover:bg-gray-800">
                        Matches
                    </a>
                    @if(auth()->user() && auth()->user()->can('view-agenda'))
                    <a href="{{ route('agenda') }}" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-gray-100 dark:hover:bg-gray-800">
                        Agenda
                    </a>
                    @endif
                @else
                    <a href="{{ route('home') }}" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-gray-100 dark:hover:bg-gray-800">
                        Home
                    </a>
                    <a href="{{ route('jobs.index') }}" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-gray-100 dark:hover:bg-gray-800">
                        Vacatures
                    </a>
                    <a href="{{ route('about') }}" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-gray-100 dark:hover:bg-gray-800">
                        Over Ons
                    </a>
                    <a href="{{ route('contact') }}" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-gray-100 dark:hover:bg-gray-800">
                        Contact
                    </a>
                @endauth
            </div>
        </div>
    </div>
</header>
<!-- End of Header -->
