<!doctype html>
<html lang="nl">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="auth-check" content="{{ auth()->check() ? 'true' : 'false' }}">
  <title>@yield('title', 'NEXA Skillmatching – Dashboard')</title>
  @vite(['resources/css/app.css', 'resources/js/frontend-app.js'])
  <!-- Inter (optioneel) -->
  <link rel="preconnect" href="https://rsms.me/" />
  <link href="https://rsms.me/inter/inter.css" rel="stylesheet" />
  <!-- Keenicons for icons -->
  <link href="{{ asset('assets/vendors/keenicons/styles.bundle.css') }}" rel="stylesheet" />
  
  <!-- Custom styles for KT dropdown menu only -->
  <style>
    /* Hide user dropdown if not authenticated */
    @if(!auth()->check())
    .user-dropdown-container,
    .user-dropdown-container * {
      display: none !important;
      visibility: hidden !important;
    }
    @endif
    
    /* KT Dropdown Menu Styles - isolated to prevent conflicts */
    .kt-dropdown-menu {
      border-radius: calc(0.5rem - 2px);
      border: 1px solid rgb(229, 231, 235);
      background-color: rgb(255, 255, 255);
      padding: 0.5rem;
      color: rgb(17, 24, 39);
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1);
    }
    .dark .kt-dropdown-menu {
      border-color: rgb(55, 65, 81);
      background-color: rgb(31, 41, 55);
      color: rgb(243, 244, 246);
    }
    .kt-dropdown-menu:not(.open) {
      display: none;
    }
    .kt-dropdown-menu-link {
      display: flex;
      width: 100%;
      cursor: pointer;
      align-items: center;
      column-gap: 0.625rem;
      border-radius: calc(0.5rem - 2px);
      padding: 0.5rem 0.625rem;
      text-align: start;
      font-size: 0.875rem;
      font-weight: 500;
      color: rgb(17, 24, 39);
      transition: background-color 0.15s ease-in-out;
    }
    .dark .kt-dropdown-menu-link {
      color: rgb(243, 244, 246);
    }
    .kt-dropdown-menu-link:hover {
      background-color: rgb(249, 250, 251);
    }
    .dark .kt-dropdown-menu-link:hover {
      background-color: rgb(55, 65, 81);
    }
    .kt-dropdown-menu-link i {
      flex-shrink: 0;
      font-size: 1rem;
      color: rgb(107, 114, 128);
    }
    .dark .kt-dropdown-menu-link i {
      color: rgb(156, 163, 175);
    }
    .kt-dropdown-menu-separator {
      height: 1px;
      background-color: rgb(229, 231, 235);
      margin: 0.25rem 0;
    }
    .dark .kt-dropdown-menu-separator {
      background-color: rgb(55, 65, 81);
    }
    .kt-dropdown-menu-sub {
      width: 100%;
      list-style: none;
      padding: 0;
      margin: 0;
    }
    .kt-dropdown-menu-sub li {
      margin: 0;
    }
  </style>
  
  <!-- Dark Mode Initial State (FOUC-vrij) - MUST RUN FIRST -->
  <script>
  (() => {
    const el = document.documentElement
    const saved = localStorage.getItem('theme')
    
    // Remove dark class first to ensure clean state
    el.classList.remove('dark')
    
    if (saved === 'dark') {
      // Use saved dark preference
      el.classList.add('dark')
    } else if (saved === 'light') {
      // Use saved light preference (already removed)
      el.classList.remove('dark')
    } else {
      // No saved preference - use system preference
      const prefersDark = matchMedia('(prefers-color-scheme: dark)').matches
      if (prefersDark) {
        el.classList.add('dark')
        localStorage.setItem('theme', 'dark')
      } else {
        localStorage.setItem('theme', 'light')
      }
    }
  })()
  </script>
</head>
<body class="bg-white dark:bg-surface-dark text-text dark:text-text-dark antialiased min-h-screen flex flex-col">
  <!-- Header -->
  @include('frontend.layouts.partials.header')
  
  <div class="w-full py-6 flex-1">
    <div class="grid grid-cols-1 {{ (auth()->check() || request()->routeIs('jobs.*') || request()->routeIs('frontend.vacancy-details')) ? 'lg:grid-cols-12' : 'lg:grid-cols-1' }} gap-6 container-custom">
      @if(auth()->check() || request()->routeIs('jobs.*') || request()->routeIs('frontend.vacancy-details'))
      <aside class="w-full min-w-0 lg:col-span-2 card p-3 sm:p-4 self-start lg:sticky lg:top-6">
      @auth
      <nav class="space-y-1 mb-6">
        <a href="{{ route('dashboard') }}" class="flex items-center justify-between rounded-xl px-3 py-2
                          text-sm hover:bg-card dark:hover:bg-card-dark border border-transparent
                          hover:border-border dark:hover:border-border-dark {{ request()->routeIs('dashboard') ? 'bg-card dark:bg-card-dark border-border dark:border-border-dark' : '' }}">
          <span>Dashboard</span>
          <svg class="h-4 w-4 text-muted dark:text-muted-dark" viewBox="0 0 24 24" fill="currentColor"><path d="M9 18l6-6-6-6"/></svg>
        </a>
        <a href="{{ route('jobs.index') }}" class="flex items-center justify-between rounded-xl px-3 py-2
                          text-sm hover:bg-card dark:hover:bg-card-dark border border-transparent
                          hover:border-border dark:hover:border-border-dark {{ request()->routeIs('jobs.*') || request()->routeIs('frontend.vacancy-details') ? 'bg-card dark:bg-card-dark border-border dark:border-border-dark' : '' }}">
          <span>Vacatures</span>
          <svg class="h-4 w-4 text-muted dark:text-muted-dark" viewBox="0 0 24 24" fill="currentColor"><path d="M9 18l6-6-6-6"/></svg>
        </a>
        <a href="{{ route('matches') }}" class="flex items-center justify-between rounded-xl px-3 py-2
                          text-sm hover:bg-card dark:hover:bg-card-dark border border-transparent
                          hover:border-border dark:hover:border-border-dark {{ request()->routeIs('matches') ? 'bg-card dark:bg-card-dark border-border dark:border-border-dark' : '' }}">
          <span>Matches</span>
          <svg class="h-4 w-4 text-muted dark:text-muted-dark" viewBox="0 0 24 24" fill="currentColor"><path d="M9 18l6-6-6-6"/></svg>
        </a>
        @if(auth()->user() && auth()->user()->can('view-agenda'))
        <a href="{{ route('agenda') }}" class="flex items-center justify-between rounded-xl px-3 py-2
                          text-sm hover:bg-card dark:hover:bg-card-dark border border-transparent
                          hover:border-border dark:hover:border-border-dark {{ request()->routeIs('agenda') ? 'bg-card dark:bg-card-dark border-border dark:border-border-dark' : '' }}">
          <span>Agenda</span>
          <svg class="h-4 w-4 text-muted dark:text-muted-dark" viewBox="0 0 24 24" fill="currentColor"><path d="M9 18l6-6-6-6"/></svg>
        </a>
        @endif
        <a href="{{ route('applications') }}" class="flex items-center justify-between rounded-xl px-3 py-2
                          text-sm hover:bg-card dark:hover:bg-card-dark border border-transparent
                          hover:border-border dark:hover:border-border-dark {{ request()->routeIs('applications') ? 'bg-card dark:bg-card-dark border-border dark:border-border-dark' : '' }}">
          <span>Sollicitaties</span>
          <svg class="h-4 w-4 text-muted dark:text-muted-dark" viewBox="0 0 24 24" fill="currentColor"><path d="M9 18l6-6-6-6"/></svg>
        </a>
        <a href="{{ route('favorites.index') }}" class="flex items-center justify-between rounded-xl px-3 py-2
                          text-sm hover:bg-card dark:hover:bg-card-dark border border-transparent
                          hover:border-border dark:hover:border-border-dark {{ request()->routeIs('favorites.*') ? 'bg-card dark:bg-card-dark border-border dark:border-border-dark' : '' }}">
          <span>Favorieten</span>
          <svg class="h-4 w-4 text-muted dark:text-muted-dark" viewBox="0 0 24 24" fill="currentColor"><path d="M9 18l6-6-6-6"/></svg>
        </a>
        <a href="{{ route('profile') }}" class="flex items-center justify-between rounded-xl px-3 py-2
                          text-sm hover:bg-card dark:hover:bg-card-dark border border-transparent
                          hover:border-border dark:hover:border-border-dark {{ request()->routeIs('profile') ? 'bg-card dark:bg-card-dark border-border dark:border-border-dark' : '' }}">
          <span>Profiel</span>
          <svg class="h-4 w-4 text-muted dark:text-muted-dark" viewBox="0 0 24 24" fill="currentColor"><path d="M9 18l6-6-6-6"/></svg>
        </a>
        <a href="{{ route('settings') }}" class="flex items-center justify-between rounded-xl px-3 py-2
                          text-sm hover:bg-card dark:hover:bg-card-dark border border-transparent
                          hover:border-border dark:hover:border-border-dark {{ request()->routeIs('settings') ? 'bg-card dark:bg-card-dark border-border dark:border-border-dark' : '' }}">
          <span>Instellingen</span>
          <svg class="h-4 w-4 text-muted dark:text-muted-dark" viewBox="0 0 24 24" fill="currentColor"><path d="M9 18l6-6-6-6"/></svg>
        </a>
      </nav>
      @endauth

      @if(request()->routeIs('jobs.*') || request()->routeIs('frontend.vacancy-details'))
      @auth
      <div class="my-4 h-px bg-border dark:bg-border-dark"></div>
      @endauth
      <div x-data="{ 
             filtersOpen: false, 
             isDesktop: window.innerWidth >= 1024,
             init() {
               const check = () => {
                 this.isDesktop = window.innerWidth >= 1024;
                 if (this.isDesktop) this.filtersOpen = true;
                 else this.filtersOpen = false;
               };
               check();
               window.addEventListener('resize', check);
             }
           }"
           x-init="init()">
        <!-- Filters Header - klikbaar op mobiel -->
        <button @click="filtersOpen = !filtersOpen" 
                type="button"
                class="flex items-center justify-between w-full lg:hidden mb-3 p-3 min-h-[44px] rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors touch-manipulation">
          <h3 class="text-lg font-semibold">Filters</h3>
          <svg x-show="!filtersOpen" 
               x-cloak
               class="w-5 h-5 transition-transform" 
               fill="none" 
               stroke="currentColor" 
               viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
          </svg>
          <svg x-show="filtersOpen" 
               x-cloak
               class="w-5 h-5 transition-transform" 
               fill="none" 
               stroke="currentColor" 
               viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
          </svg>
        </button>
        
        <!-- Desktop Header -->
        <h3 class="text-lg font-semibold hidden lg:block mb-3">Filters</h3>
        
        <!-- Filters Form - verborgen op mobiel standaard, altijd zichtbaar op desktop -->
        <div x-show="filtersOpen || isDesktop"
             x-cloak
             class="max-lg:max-h-[70vh] max-lg:overflow-y-auto max-lg:pb-2"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 transform scale-95"
             x-transition:enter-end="opacity-100 transform scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 transform scale-100"
             x-transition:leave-end="opacity-0 transform scale-95">
          <form method="GET" 
                action="{{ route('jobs.index') }}" 
                class="space-y-3">
          <!-- Hidden fields to preserve search query and sort -->
          @if(request('q'))
            <input type="hidden" name="q" value="{{ request('q') }}">
          @endif
          @if(request('sort'))
            <input type="hidden" name="sort" value="{{ request('sort') }}">
          @endif
          @if(request('per_page'))
            <input type="hidden" name="per_page" value="{{ request('per_page') }}">
          @endif
        
        <!-- Locatie -->
        <div class="min-w-0">
          <label class="text-sm text-muted dark:text-muted-dark">Locatie</label>
          <input name="location" class="input mt-1 w-full min-w-0" placeholder="Plaats of remote" value="{{ request('location') }}">
        </div>
        
        <!-- Afstand -->
        <div class="min-w-0">
          <label class="text-sm text-muted dark:text-muted-dark">Afstand</label>
          <select name="distance" class="select mt-1 w-full min-w-0">
            <option value="">Alle afstanden</option>
            <option value="5" {{ request('distance') == '5' ? 'selected' : '' }}>Binnen 5 km</option>
            <option value="10" {{ request('distance') == '10' ? 'selected' : '' }}>Binnen 10 km</option>
            <option value="25" {{ request('distance') == '25' ? 'selected' : '' }}>Binnen 25 km</option>
            <option value="50" {{ request('distance') == '50' ? 'selected' : '' }}>Binnen 50 km</option>
            <option value="100" {{ request('distance') == '100' ? 'selected' : '' }}>Binnen 100 km</option>
          </select>
        </div>
        
        <!-- Werktype -->
        <div class="space-y-3 min-w-0">
          <div class="min-w-0">
            <label class="text-sm text-muted dark:text-muted-dark">Werktype</label>
            <select name="employment_type" class="select mt-1 w-full min-w-0">
              <option value="">Alle</option>
              <option value="Fulltime" {{ request('employment_type') == 'Fulltime' ? 'selected' : '' }}>Fulltime</option>
              <option value="Parttime" {{ request('employment_type') == 'Parttime' ? 'selected' : '' }}>Parttime</option>
              <option value="Freelance" {{ request('employment_type') == 'Freelance' ? 'selected' : '' }}>Freelance</option>
              <option value="ZZP" {{ request('employment_type') == 'ZZP' ? 'selected' : '' }}>ZZP</option>
              <option value="Stage" {{ request('employment_type') == 'Stage' ? 'selected' : '' }}>Stage</option>
              <option value="Traineeship" {{ request('employment_type') == 'Traineeship' ? 'selected' : '' }}>Traineeship</option>
            </select>
          </div>
          <div class="min-w-0">
            <label class="text-sm text-muted dark:text-muted-dark">Ervaring</label>
            <select name="experience_level" class="select mt-1 w-full min-w-0">
              <option value="">Alle niveaus</option>
              <option value="Junior" {{ request('experience_level') == 'Junior' ? 'selected' : '' }}>Junior</option>
              <option value="Medior" {{ request('experience_level') == 'Medior' ? 'selected' : '' }}>Medior</option>
              <option value="Senior" {{ request('experience_level') == 'Senior' ? 'selected' : '' }}>Senior</option>
              <option value="Lead" {{ request('experience_level') == 'Lead' ? 'selected' : '' }}>Lead</option>
            </select>
          </div>
        </div>
        
        <!-- Salaris range -->
        <div class="space-y-3 min-w-0">
          <div class="min-w-0">
            <label class="text-sm text-muted dark:text-muted-dark">Min. salaris</label>
            <input name="salary_min" type="number" class="input mt-1 w-full min-w-0" placeholder="€ 2500" value="{{ request('salary_min') }}">
          </div>
          <div class="min-w-0">
            <label class="text-sm text-muted dark:text-muted-dark">Max. salaris</label>
            <input name="salary_max" type="number" class="input mt-1 w-full min-w-0" placeholder="€ 8000" value="{{ request('salary_max') }}">
          </div>
        </div>
        
        <!-- Remote werk -->
        <div>
          <label class="flex items-center min-h-[44px] cursor-pointer touch-manipulation">
            <input name="remote_work" type="checkbox" class="form-checkbox w-4 h-4" {{ request('remote_work') ? 'checked' : '' }}>
            <span class="ml-2 text-sm text-muted dark:text-muted-dark">Remote werk mogelijk</span>
          </label>
        </div>
        
        <!-- Reiskosten -->
        <div>
          <label class="flex items-center min-h-[44px] cursor-pointer touch-manipulation">
            <input name="travel_expenses" type="checkbox" class="form-checkbox w-4 h-4" {{ request('travel_expenses') ? 'checked' : '' }}>
            <span class="ml-2 text-sm text-muted dark:text-muted-dark">Reiskosten vergoed</span>
          </label>
        </div>
        
        <!-- Vaardigheden -->
        <div class="min-w-0">
          <label class="text-sm text-muted dark:text-muted-dark">Vaardigheden</label>
          <input name="skills" class="input mt-1 w-full min-w-0" placeholder="bv. Laravel, React" value="{{ request('skills') }}">
        </div>
        
        <button class="btn btn-primary w-full min-h-[44px] touch-manipulation" type="submit">Toon resultaten</button>
        
        @if(request()->hasAny(['location', 'distance', 'employment_type', 'experience_level', 'salary_min', 'salary_max', 'remote_work', 'travel_expenses', 'skills']))
          <a href="{{ route('jobs.index', request()->only(['q', 'sort', 'per_page'])) }}" class="btn btn-outline w-full min-h-[44px] touch-manipulation inline-flex items-center justify-center">Reset filters</a>
        @endif
          </form>
        </div>
      </div>
      @endif
      
      <script>
        // Auto-submit form when dropdown values change
        document.addEventListener('DOMContentLoaded', function() {
          const form = document.querySelector('form[action="{{ route('jobs.index') }}"]');
          if (!form) {
            return; // Form doesn't exist on this page, skip initialization
          }
          
          const selects = form.querySelectorAll('select');
          const inputs = form.querySelectorAll('input[name="location"]');
          
          // Auto-submit on select change
          selects.forEach(select => {
            select.addEventListener('change', function() {
              console.log('Select changed:', select.name, select.value);
              form.submit();
            });
          });
          
          // Auto-submit on location input change (with debounce)
          inputs.forEach(input => {
            let timeout;
            input.addEventListener('input', function() {
              clearTimeout(timeout);
              timeout = setTimeout(() => {
                console.log('Location changed:', input.value);
                form.submit();
              }, 1000); // 1 second delay
            });
          });
        });
      </script>
    </aside>
      @endif

      <main class="{{ (auth()->check() || request()->routeIs('jobs.*') || request()->routeIs('frontend.vacancy-details')) ? 'lg:col-span-10' : 'lg:col-span-12' }} space-y-6">
        @yield('content')
      </main>
    </div>
  </div>

  <!-- Footer -->
  @include('frontend.layouts.partials.footer')
  
  <!-- KT UI Scripts for dropdowns and drawers -->
  <script src="{{ asset('assets/js/core.bundle.js') }}" data-navigate-once></script>
  <script src="{{ asset('assets/vendors/ktui/ktui.min.js') }}" data-navigate-once></script>
  
  <!-- Initialize KT UI dropdowns -->
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Ensure dropdown menus are hidden by default
      document.querySelectorAll('[data-kt-dropdown-menu="true"]').forEach(function(menu) {
        if (!menu.classList.contains('open')) {
          menu.style.display = 'none';
        }
      });
      
      // Initialize all dropdowns
      if (typeof window.KTDropdown !== 'undefined') {
        document.querySelectorAll('[data-kt-dropdown="true"]').forEach(function(element) {
          try {
            if (!window.KTDropdown.getInstance(element)) {
              new window.KTDropdown(element);
            }
          } catch(e) {
            console.error('Error initializing dropdown:', e);
          }
        });
      }
    });
  </script>
  
      <!-- Frontend Header Badges -->
      <script src="{{ asset('js/frontend-header-badges.js') }}"></script>
      <script src="{{ asset('js/notifications-drawer.js') }}"></script>
      
      <!-- Frontend Chat -->
      <script src="{{ asset('js/frontend-chat.js') }}"></script>
  
  <!-- Hide user dropdown if not authenticated -->
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const authMeta = document.querySelector('meta[name="auth-check"]');
      const isAuthenticated = authMeta && authMeta.getAttribute('content') === 'true';
      
      if (!isAuthenticated) {
        // Hide user dropdown container
        const userDropdown = document.querySelector('.user-dropdown-container');
        if (userDropdown) {
          userDropdown.style.display = 'none';
          userDropdown.style.visibility = 'hidden';
          userDropdown.style.opacity = '0';
        }
        
        // Hide all user dropdown menus
        const userDropdownMenus = document.querySelectorAll('[data-kt-dropdown-toggle="true"]');
        userDropdownMenus.forEach(menu => {
          const container = menu.closest('.user-dropdown-container');
          if (container) {
            container.style.display = 'none';
            container.style.visibility = 'hidden';
          }
        });
      }
    });
  </script>
  
  <!-- Dark Mode Toggle Script -->
  <script>
    function toggleDarkMode() {
      const html = document.documentElement;
      const isDark = html.classList.contains('dark');
      
      if (isDark) {
        // Switch to light mode
        html.classList.remove('dark');
        localStorage.setItem('theme', 'light');
        console.log('🌞 Switched to light mode, saved to localStorage');
      } else {
        // Switch to dark mode
        html.classList.add('dark');
        localStorage.setItem('theme', 'dark');
        console.log('🌙 Switched to dark mode, saved to localStorage');
      }
      
      // Update switch state if KT UI switch exists
      const switchElement = document.querySelector('[data-kt-theme-switch-toggle="true"]');
      if (switchElement) {
        switchElement.checked = !isDark;
      }
    }
    
    // Ensure dark mode is applied on page load (run after initial state script)
    document.addEventListener('DOMContentLoaded', function() {
      const html = document.documentElement;
      const saved = localStorage.getItem('theme');
      
      // Re-apply saved preference to ensure it's not overridden
      if (saved === 'dark') {
        html.classList.add('dark');
      } else {
        html.classList.remove('dark');
      }
      
      // Update switch state if KT UI switch exists
      const isDark = html.classList.contains('dark');
      const switchElement = document.querySelector('[data-kt-theme-switch-toggle="true"]');
      if (switchElement) {
        switchElement.checked = isDark;
      }
      
      console.log('📱 Dark mode initialized on DOMContentLoaded:', { saved, isDark: html.classList.contains('dark') });
    });
  </script>
</body>
</html>
