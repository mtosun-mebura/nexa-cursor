<!doctype html>
<html lang="nl">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
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
<body class="bg-surface dark:bg-surface-dark text-text dark:text-text-dark antialiased">
  <!-- Header -->
  @include('frontend.layouts.partials.header')
  
  <div class="w-full py-6">
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
                          hover:border-border dark:hover:border-border-dark {{ request()->routeIs('jobs.*') ? 'bg-card dark:bg-card-dark border-border dark:border-border-dark' : '' }}">
          <span>Vacatures</span>
          <svg class="h-4 w-4 text-muted dark:text-muted-dark" viewBox="0 0 24 24" fill="currentColor"><path d="M9 18l6-6-6-6"/></svg>
        </a>
        <a href="{{ route('matches') }}" class="flex items-center justify-between rounded-xl px-3 py-2
                          text-sm hover:bg-card dark:hover:bg-card-dark border border-transparent
                          hover:border-border dark:hover:border-border-dark {{ request()->routeIs('matches') ? 'bg-card dark:bg-card-dark border-border dark:border-border-dark' : '' }}">
          <span>Matches</span>
          <svg class="h-4 w-4 text-muted dark:text-muted-dark" viewBox="0 0 24 24" fill="currentColor"><path d="M9 18l6-6-6-6"/></svg>
        </a>
        <a href="{{ route('applications') }}" class="flex items-center justify-between rounded-xl px-3 py-2
                          text-sm hover:bg-card dark:hover:bg-card-dark border border-transparent
                          hover:border-border dark:hover:border-border-dark {{ request()->routeIs('applications') ? 'bg-card dark:bg-card-dark border-border dark:border-border-dark' : '' }}">
          <span>Sollicitaties</span>
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

      <div class="my-4 h-px bg-border dark:bg-border-dark"></div>

      <form class="space-y-3">
        <h3 class="text-sm font-semibold">Filters</h3>
        <div>
          <label class="text-sm text-muted dark:text-muted-dark">Locatie</label>
          <input class="input mt-1" placeholder="Plaats of remote">
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="text-sm text-muted dark:text-muted-dark">Uren</label>
            <select class="select mt-1">
              <option>Alle</option><option>Fulltime</option><option>Parttime</option>
            </select>
          </div>
          <div>
            <label class="text-sm text-muted dark:text-muted-dark">Contract</label>
            <select class="select mt-1">
              <option>Alle</option><option>Vast</option><option>Tijdelijk</option><option>ZZP</option>
            </select>
          </div>
        </div>
        <div>
          <label class="text-sm text-muted dark:text-muted-dark">Vaardigheden</label>
          <input class="input mt-1" placeholder="bv. Laravel, React">
        </div>
        <button class="btn btn-primary w-full" type="submit">Toon resultaten</button>
      </form>
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
