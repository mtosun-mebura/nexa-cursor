<header class="bg-white dark:bg-gray-900 shadow-sm border-b border-gray-200 dark:border-gray-700 sticky top-0 z-50">
    <div class="container-custom"
         x-data="{ 
                mobileMenuOpen: false,
                userMenuOpen: false,
                languageMenuOpen: false,
                currentLanguage: 'nl',
                isDark: document.documentElement.classList.contains('dark'),
                toggleTheme() {
                    this.isDark = !this.isDark;
                    document.documentElement.classList.toggle('dark', this.isDark);
                    localStorage.setItem('theme', this.isDark ? 'dark' : 'light');
                },
                switchLanguage(lang) {
                    this.currentLanguage = lang;
                    localStorage.setItem('language', lang);
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
             }"
             x-init="
                const saved = localStorage.getItem('theme');
                const prefersDark = matchMedia('(prefers-color-scheme: dark)').matches;
                this.isDark = saved ? saved === 'dark' : prefersDark;
                document.documentElement.classList.toggle('dark', this.isDark);
                const savedLanguage = localStorage.getItem('language');
                const serverLanguage = '{{ app()->getLocale() }}';
                this.currentLanguage = serverLanguage || savedLanguage || 'nl';
             ">
        <div class="flex justify-between items-center h-16 md:h-20">
            <!-- Logo -->
            <div class="flex-shrink-0 ml-2 md:ml-8 py-1">
                <a href="{{ route('home') }}" class="flex items-center" aria-label="Nexa Skillmatching">
                    <img src="{{ asset('images/nexa-skillmatching-logo.png') }}" alt="Nexa Skillmatching" class="h-12 md:h-16 w-auto">
                </a>
            </div>
            
            <!-- Desktop Navigation -->
            <nav class="hidden md:flex space-x-1" role="navigation" aria-label="Hoofdnavigatie">
                @auth
                    <a href="{{ route('dashboard') }}" class="text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 px-3 py-2 rounded-md text-base font-medium transition-colors duration-200 {{ request()->routeIs('dashboard') ? 'text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20' : '' }}">
                        Dashboard
                    </a>
                    <a href="{{ route('jobs.index') }}" class="text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 px-3 py-2 rounded-md text-base font-medium transition-colors duration-200 {{ request()->routeIs('jobs.*') ? 'text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20' : '' }}">
                        Vacatures
                    </a>
                    <a href="{{ route('matches') }}" class="text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 px-3 py-2 rounded-md text-base font-medium transition-colors duration-200 {{ request()->routeIs('matches') ? 'text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20' : '' }}">
                        Matches
                    </a>
                    @if(auth()->user() && auth()->user()->can('view-agenda'))
                    <a href="{{ route('agenda') }}" class="text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 px-3 py-2 rounded-md text-base font-medium transition-colors duration-200 {{ request()->routeIs('agenda') ? 'text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20' : '' }}">
                        Agenda
                    </a>
                    @endif
                @else
                    <a href="{{ route('home') }}" class="text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 px-3 py-2 rounded-md text-base font-medium transition-colors duration-200 {{ request()->routeIs('home') ? 'text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20' : '' }}">
                        Home
                    </a>
                    <a href="{{ route('jobs.index') }}" class="text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 px-3 py-2 rounded-md text-base font-medium transition-colors duration-200 {{ request()->routeIs('jobs.*') ? 'text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20' : '' }}">
                        Vacatures
                    </a>
                    <a href="{{ route('about') }}" class="text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 px-3 py-2 rounded-md text-base font-medium transition-colors duration-200 {{ request()->routeIs('about') ? 'text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20' : '' }}">
                        Over Ons
                    </a>
                @endauth
            </nav>
            
            <!-- Right side actions -->
            <div class="hidden md:flex items-center space-x-4">
                <!-- Dark mode toggle -->
                <button @click="toggleTheme()" 
                        class="p-2 rounded-lg text-gray-600 hover:text-blue-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:text-blue-400 dark:hover:bg-gray-800 transition-colors duration-200"
                        aria-label="Toggle dark mode">
                    <!-- Moon icon for light mode -->
                    <svg x-show="!isDark" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                    </svg>
                    <!-- Sun icon for dark mode -->
                    <svg x-show="isDark" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                </button>
                
                <!-- Language selector -->
                <div class="relative">
                    <button @click="languageMenuOpen = !languageMenuOpen" 
                            class="p-2 rounded-lg text-gray-600 hover:text-blue-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:text-blue-400 dark:hover:bg-gray-800 transition-colors duration-200"
                            aria-label="Select language">
                        <!-- Dutch flag -->
                        <div x-show="currentLanguage === 'nl'" class="flex items-center">
                            <span class="fi fi-nl text-lg"></span>
                        </div>
                        <!-- English flag -->
                        <div x-show="currentLanguage === 'en'" class="flex items-center">
                            <span class="fi fi-gb text-lg"></span>
                        </div>
                    </button>
                    
                    <div x-show="languageMenuOpen" 
                         @click.away="languageMenuOpen = false"
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
                
                <!-- User menu -->
                @auth
                    <div class="relative">
                        <button @click="userMenuOpen = !userMenuOpen" 
                                class="flex items-center space-x-2 text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 px-3 py-2 rounded-md text-base font-medium transition-colors duration-200"
                                aria-expanded="false" 
                                aria-haspopup="true">
                            <span>{{ Auth::user()->first_name }}</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        
                        <div x-show="userMenuOpen" 
                             @click.away="userMenuOpen = false"
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="transform opacity-0 scale-95 translate-y-1"
                             x-transition:enter-end="transform opacity-100 scale-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-150"
                             x-transition:leave-start="transform opacity-100 scale-100 translate-y-0"
                             x-transition:leave-end="transform opacity-0 scale-95 translate-y-1"
                             class="absolute right-0 mt-3 w-56 bg-white dark:bg-gray-800 rounded-2xl shadow-2xl py-2 z-50 border border-gray-200/50 dark:border-gray-700/50 backdrop-blur-sm"
                             role="menu">
                            <a href="{{ route('dashboard') }}" class="flex items-center px-4 py-3 text-base text-gray-700 dark:text-gray-300 hover:bg-blue-50 dark:hover:bg-blue-900/20 hover:text-blue-600 dark:hover:text-blue-400 transition-all duration-200 group" role="menuitem">
                                <svg class="w-4 h-4 mr-3 text-gray-400 group-hover:text-blue-500 dark:group-hover:text-blue-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v2H8V5z"></path>
                                </svg>
                                Dashboard
                            </a>
                            <a href="{{ route('profile') }}" class="flex items-center px-4 py-3 text-base text-gray-700 dark:text-gray-300 hover:bg-blue-50 dark:hover:bg-blue-900/20 hover:text-blue-600 dark:hover:text-blue-400 transition-all duration-200 group" role="menuitem">
                                <svg class="w-4 h-4 mr-3 text-gray-400 group-hover:text-blue-500 dark:group-hover:text-blue-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                                Mijn Profiel
                            </a>
                            <a href="{{ route('applications') }}" class="flex items-center px-4 py-3 text-base text-gray-700 dark:text-gray-300 hover:bg-blue-50 dark:hover:bg-blue-900/20 hover:text-blue-600 dark:hover:text-blue-400 transition-all duration-200 group" role="menuitem">
                                <svg class="w-4 h-4 mr-3 text-gray-400 group-hover:text-blue-500 dark:group-hover:text-blue-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                Mijn Sollicitaties
                            </a>
                            <a href="{{ route('settings') }}" class="flex items-center px-4 py-3 text-base text-gray-700 dark:text-gray-300 hover:bg-blue-50 dark:hover:bg-blue-900/20 hover:text-blue-600 dark:hover:text-blue-400 transition-all duration-200 group" role="menuitem">
                                <svg class="w-4 h-4 mr-3 text-gray-400 group-hover:text-blue-500 dark:group-hover:text-blue-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                Instellingen
                            </a>
                            <div class="border-t border-gray-200/50 dark:border-gray-700/50 my-2"></div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="flex items-center w-full px-4 py-3 text-base text-gray-700 dark:text-gray-300 hover:bg-red-50 dark:hover:bg-red-900/20 hover:text-red-600 dark:hover:text-red-400 transition-all duration-200 group" role="menuitem">
                                    <svg class="w-4 h-4 mr-3 text-gray-400 group-hover:text-red-500 dark:group-hover:text-red-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                    </svg>
                                    Uitloggen
                                </button>
                            </form>
                        </div>
                    </div>
                @else
                    <div class="flex items-center space-x-3">
                        <a href="{{ route('login') }}" class="text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 px-3 py-2 rounded-md text-base font-medium transition-colors duration-200">
                            Inloggen
                        </a>
                        <a href="{{ route('register') }}" class="btn btn-primary">
                            Registreren
                        </a>
                    </div>
                @endauth
            </div>
            
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
        
        <!-- Mobile Navigation -->
        <div x-show="mobileMenuOpen" x-cloak
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
                    <a href="{{ route('vacancies.index') }}" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-gray-100 dark:hover:bg-gray-800">
                        Vacatures
                    </a>
                    <a href="{{ route('about') }}" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-gray-100 dark:hover:bg-gray-800">
                        Over Ons
                    </a>
                @endauth
            </div>
        </div>
    </div>
</header>