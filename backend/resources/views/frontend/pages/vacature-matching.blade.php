<!doctype html>
<html lang="nl">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>NEXA Skillmatching – Portal</title>
  @vite(['resources/css/app.css'])
  <!-- Inter (optioneel) -->
  <link rel="preconnect" href="https://rsms.me/" />
  <link href="https://rsms.me/inter/inter.css" rel="stylesheet" />
  
  <!-- Dark Mode Toggle (FOUC-vrij) -->
  <script>
  (() => {
    const el = document.documentElement
    const saved = localStorage.getItem('theme')
    const prefersDark = matchMedia('(prefers-color-scheme: dark)').matches
    el.classList.toggle('dark', saved ? saved === 'dark' : prefersDark)
    window.toggleTheme = () => {
      const dark = el.classList.toggle('dark')
      localStorage.setItem('theme', dark ? 'dark' : 'light')
    }
  })()
  </script>
</head>
<body class="bg-surface dark:bg-surface-dark text-text dark:text-text-dark antialiased">
  <header class="sticky top-0 z-40 border-b border-border dark:border-border-dark bg-surface/90 dark:bg-surface-dark/90 backdrop-blur">
    <div class="w-full px-4 py-3 flex items-center gap-4">
      <a href="/" class="flex items-center gap-3">
        <img src="{{ asset('images/nexa-skillmatching-logo.png') }}" alt="NEXA" class="h-7 w-auto">
        <span class="hidden sm:block font-semibold">NEXA Skillmatching</span>
      </a>

      <div class="flex-1">
        <label class="sr-only" for="q">Zoeken</label>
        <input id="q" type="search" class="input"
               placeholder="Zoek vacatures, bedrijven of skills…">
      </div>

      <nav class="flex items-center gap-2">
        <button class="btn btn-outline" onclick="toggleTheme()">Thema</button>
        <a class="btn btn-primary" href="#">Plaats vacature</a>
      </nav>
    </div>
  </header>

  <div class="w-full px-4 py-6 grid grid-cols-1 lg:grid-cols-12 gap-6">
    <aside class="lg:col-span-2 card p-4 self-start">
      <nav class="space-y-1">
        @foreach (['Dashboard','Vacatures','Matches','Sollicitaties','Profiel','Instellingen'] as $item)
        <a href="#" class="flex items-center justify-between rounded-xl px-3 py-2
                          text-sm hover:bg-card dark:hover:bg-card-dark border border-transparent
                          hover:border-border dark:hover:border-border-dark">
          <span>{{ $item }}</span>
          <svg class="h-4 w-4 text-muted dark:text-muted-dark" viewBox="0 0 24 24" fill="currentColor"><path d="M9 18l6-6-6-6"/></svg>
        </a>
        @endforeach
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
      <section class="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h1 class="text-2xl font-semibold leading-tight">Vacature-matching</h1>
          <p class="text-sm text-muted dark:text-muted-dark">Geselecteerd op jouw profiel en voorkeuren.</p>
        </div>
        <div class="flex items-center gap-2">
          <span class="pill"><span class="h-2 w-2 rounded-full bg-brand-500"></span> Nieuwe matches</span>
          <span class="pill">42 resultaten</span>
        </div>
      </section>

      <section class="grid sm:grid-cols-2 xl:grid-cols-3 gap-5">
        @foreach (range(1,6) as $i)
        <article class="card p-4 flex flex-col gap-3">
          <header class="flex items-start justify-between gap-3">
            <div>
              <h3 class="font-semibold leading-tight">Senior Laravel Developer</h3>
              <p class="text-sm text-muted dark:text-muted-dark">NEXA · Amsterdam · Hybride</p>
            </div>
            <span class="badge">€ 5.000–6.000</span>
          </header>

          <p class="text-sm text-muted dark:text-muted-dark line-clamp-3">
            Bouw aan een schaalbaar matching-platform met queues en event-driven architectuur.
          </p>

          <div class="space-y-2">
            <div class="flex items-center justify-between text-sm">
              <span class="text-muted dark:text-muted-dark">Matchscore</span>
              <strong>86%</strong>
            </div>
            <div class="match"><span style="width:86%"></span></div>
            <div class="flex flex-wrap gap-2">
              <span class="pill">Laravel</span><span class="pill">MySQL</span>
              <span class="pill">Docker</span><span class="pill">Tailwind</span>
            </div>
          </div>

          <div class="mt-auto flex items-center gap-2 pt-2">
            <a href="#" class="btn btn-outline">Details</a>
            <button class="btn btn-primary">Solliciteer</button>
          </div>
        </article>
        @endforeach
      </section>
    </main>
  </div>

  <footer class="mt-10 border-t border-border dark:border-border-dark py-6 text-center text-sm text-muted dark:text-muted-dark">
    © {{ date('Y') }} NEXA Skillmatching
  </footer>
</body>
</html>
