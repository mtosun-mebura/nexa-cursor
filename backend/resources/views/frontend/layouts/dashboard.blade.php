<!doctype html>
<html lang="nl">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'NEXA Skillmatching – Dashboard')</title>
  @vite(['resources/css/app.css', 'resources/js/app.js'])
  <!-- Inter (optioneel) -->
  <link rel="preconnect" href="https://rsms.me/" />
  <link href="https://rsms.me/inter/inter.css" rel="stylesheet" />
  
  <!-- Dark Mode Initial State (FOUC-vrij) -->
  <script>
  (() => {
    const el = document.documentElement
    const saved = localStorage.getItem('theme')
    const prefersDark = matchMedia('(prefers-color-scheme: dark)').matches
    el.classList.toggle('dark', saved ? saved === 'dark' : prefersDark)
  })()
  </script>
</head>
<body class="bg-surface dark:bg-surface-dark text-text dark:text-text-dark antialiased min-h-screen flex flex-col">
  <!-- Header -->
  @include('frontend.layouts.partials.header')
  
  <div class="w-full py-6 flex-1">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 container-custom">
      <aside class="lg:col-span-2 card p-4 self-start">
      <nav class="space-y-1">
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

      @if(request()->routeIs('jobs.*') || request()->routeIs('frontend.vacancy-details'))
      <div class="my-4 h-px bg-border dark:bg-border-dark"></div>
      <form method="GET" action="{{ route('jobs.index') }}" class="space-y-3">
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
        
        <h3 class="text-sm font-semibold">Filters</h3>
        
        <!-- Locatie -->
        <div>
          <label class="text-sm text-muted dark:text-muted-dark">Locatie</label>
          <input name="location" class="input mt-1" placeholder="Plaats of remote" value="{{ request('location') }}">
        </div>
        
        <!-- Afstand -->
        <div>
          <label class="text-sm text-muted dark:text-muted-dark">Afstand</label>
          <select name="distance" class="select mt-1">
            <option value="">Alle afstanden</option>
            <option value="5" {{ request('distance') == '5' ? 'selected' : '' }}>Binnen 5 km</option>
            <option value="10" {{ request('distance') == '10' ? 'selected' : '' }}>Binnen 10 km</option>
            <option value="25" {{ request('distance') == '25' ? 'selected' : '' }}>Binnen 25 km</option>
            <option value="50" {{ request('distance') == '50' ? 'selected' : '' }}>Binnen 50 km</option>
            <option value="100" {{ request('distance') == '100' ? 'selected' : '' }}>Binnen 100 km</option>
          </select>
        </div>
        
        <!-- Werktype -->
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="text-sm text-muted dark:text-muted-dark">Werktype</label>
            <select name="employment_type" class="select mt-1">
              <option value="">Alle</option>
              <option value="Fulltime" {{ request('employment_type') == 'Fulltime' ? 'selected' : '' }}>Fulltime</option>
              <option value="Parttime" {{ request('employment_type') == 'Parttime' ? 'selected' : '' }}>Parttime</option>
              <option value="Freelance" {{ request('employment_type') == 'Freelance' ? 'selected' : '' }}>Freelance</option>
              <option value="ZZP" {{ request('employment_type') == 'ZZP' ? 'selected' : '' }}>ZZP</option>
              <option value="Stage" {{ request('employment_type') == 'Stage' ? 'selected' : '' }}>Stage</option>
              <option value="Traineeship" {{ request('employment_type') == 'Traineeship' ? 'selected' : '' }}>Traineeship</option>
            </select>
          </div>
          <div>
            <label class="text-sm text-muted dark:text-muted-dark">Ervaring</label>
            <select name="experience_level" class="select mt-1">
              <option value="">Alle niveaus</option>
              <option value="Junior" {{ request('experience_level') == 'Junior' ? 'selected' : '' }}>Junior</option>
              <option value="Medior" {{ request('experience_level') == 'Medior' ? 'selected' : '' }}>Medior</option>
              <option value="Senior" {{ request('experience_level') == 'Senior' ? 'selected' : '' }}>Senior</option>
              <option value="Lead" {{ request('experience_level') == 'Lead' ? 'selected' : '' }}>Lead</option>
            </select>
          </div>
        </div>
        
        <!-- Salaris range -->
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="text-sm text-muted dark:text-muted-dark">Min. salaris</label>
            <input name="salary_min" type="number" class="input mt-1" placeholder="€ 2500" value="{{ request('salary_min') }}">
          </div>
          <div>
            <label class="text-sm text-muted dark:text-muted-dark">Max. salaris</label>
            <input name="salary_max" type="number" class="input mt-1" placeholder="€ 8000" value="{{ request('salary_max') }}">
          </div>
        </div>
        
        <!-- Remote werk -->
        <div>
          <label class="flex items-center">
            <input name="remote_work" type="checkbox" class="form-checkbox" {{ request('remote_work') ? 'checked' : '' }}>
            <span class="ml-2 text-sm text-muted dark:text-muted-dark">Remote werk mogelijk</span>
          </label>
        </div>
        
        <!-- Reiskosten -->
        <div>
          <label class="flex items-center">
            <input name="travel_expenses" type="checkbox" class="form-checkbox" {{ request('travel_expenses') ? 'checked' : '' }}>
            <span class="ml-2 text-sm text-muted dark:text-muted-dark">Reiskosten vergoed</span>
          </label>
        </div>
        
        <!-- Vaardigheden -->
        <div>
          <label class="text-sm text-muted dark:text-muted-dark">Vaardigheden</label>
          <input name="skills" class="input mt-1" placeholder="bv. Laravel, React" value="{{ request('skills') }}">
        </div>
        
        <button class="btn btn-primary w-full" type="submit">Toon resultaten</button>
        
        @if(request()->hasAny(['location', 'distance', 'employment_type', 'experience_level', 'salary_min', 'salary_max', 'remote_work', 'travel_expenses', 'skills']))
          <a href="{{ route('jobs.index', request()->only(['q', 'sort', 'per_page'])) }}" class="btn btn-outline w-full">Reset filters</a>
        @endif
      </form>
      @endif
      
      <script>
        // Auto-submit form when dropdown values change
        document.addEventListener('DOMContentLoaded', function() {
          const form = document.querySelector('form[action="{{ route('jobs.index') }}"]');
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

      <main class="lg:col-span-10 space-y-6">
        @yield('content')
      </main>
    </div>
  </div>

  <!-- Footer -->
  @include('frontend.layouts.partials.footer')
</body>
</html>
