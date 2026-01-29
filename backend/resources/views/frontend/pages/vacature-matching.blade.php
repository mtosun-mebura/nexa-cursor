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
<body class="bg-white dark:bg-surface-dark text-text dark:text-text-dark antialiased">
  <header class="sticky top-0 z-40 border-b border-border dark:border-border-dark bg-white/90 dark:bg-surface-dark/90 backdrop-blur">
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
              <option>Alle</option><option>Vast</option><option>Tijdelijk</option><option>ZZP</option><option>Stage</option><option>Traineeship</option>
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
          <span class="pill">{{ $vacancies->count() }} resultaten</span>
        </div>
      </section>

      <section class="card overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full" id="vacature_matching_table">
            <thead class="bg-gray-200 dark:bg-gray-800 border-b border-gray-300 dark:border-gray-700">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:bg-gray-300 dark:hover:bg-gray-700" onclick="sortTable(0)">
                  Vacature
                  <svg class="inline-block w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                  </svg>
                </th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:bg-gray-300 dark:hover:bg-gray-700" onclick="sortTable(1)">
                  Bedrijf
                  <svg class="inline-block w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                  </svg>
                </th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:bg-gray-300 dark:hover:bg-gray-700" onclick="sortTable(2)">
                  Locatie
                  <svg class="inline-block w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                  </svg>
                </th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:bg-gray-300 dark:hover:bg-gray-700" onclick="sortTable(3)">
                  Type
                  <svg class="inline-block w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                  </svg>
                </th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:bg-gray-300 dark:hover:bg-gray-700" onclick="sortTable(4)">
                  Salaris
                  <svg class="inline-block w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                  </svg>
                </th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:bg-gray-300 dark:hover:bg-gray-700" onclick="sortTable(5)">
                  Match
                  <svg class="inline-block w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                  </svg>
                </th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                  Acties
                </th>
              </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
              @forelse($vacancies as $vacancy)
              <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors cursor-pointer" data-href="{{ route('frontend.vacancy-details', ['companySlug' => $vacancy->company->slug, 'vacancyId' => $vacancy->id]) }}">
                <td class="px-4 py-4">
                  <div class="flex flex-col">
                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                      {{ $vacancy->title }}
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                      {{ Str::limit(strip_tags($vacancy->description), 80) }}
                    </div>
                    <div class="flex flex-wrap gap-1 mt-2">
                  @if($vacancy->category)
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                      {{ $vacancy->category->name }}
                    </span>
                  @endif
                      @if($vacancy->remote_work)
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                          Remote
                        </span>
                      @endif
                      @if($vacancy->travel_expenses)
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-800 dark:text-purple-100">
                          Reiskosten
                        </span>
                      @endif
                    </div>
                  </div>
                </td>
                <td class="px-4 py-4">
                  <div class="text-sm text-gray-900 dark:text-gray-100">
                @if($vacancy->company && $vacancy->company->is_intermediary)
                  {{ $vacancy->company->name }}
                @else
                  <span class="text-gray-500 dark:text-gray-400">Directe werkgever</span>
                @endif
                  </div>
                </td>
                <td class="px-4 py-4">
                  <div class="text-sm text-gray-900 dark:text-gray-100">
                    {{ $vacancy->location }}
                  </div>
                </td>
                <td class="px-4 py-4">
                  <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                    {{ $vacancy->employment_type }}
                  </span>
                </td>
                <td class="px-4 py-4">
                  <div class="text-sm text-gray-900 dark:text-gray-100">
                    {{ $vacancy->salary_range ?? 'Niet opgegeven' }}
                  </div>
                </td>
                <td class="px-4 py-4">
                  <div class="flex items-center">
                    <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-2 mr-2">
                      <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $vacancy->match_score }}%"></div>
                    </div>
                    <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                      {{ $vacancy->match_score }}%
                    </span>
                  </div>
                </td>
                <td class="px-4 py-4" data-actions-cell>
                  <div class="flex items-center gap-2">
                    <a href="{{ route('frontend.vacancy-details', ['companySlug' => $vacancy->company->slug, 'vacancyId' => $vacancy->id]) }}" 
                       class="inline-flex items-center px-3 py-1.5 border border-gray-300 dark:border-gray-600 text-xs font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                      Details
                    </a>
                    <button class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-white bg-orange-600 hover:bg-orange-700 transition-colors">
                      Solliciteer
                    </button>
                  </div>
                </td>
              </tr>
              @empty
              <tr>
                <td colspan="7" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                  Geen vacatures gevonden.
                </td>
              </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </section>
    </main>
  </div>

  <footer class="mt-10 border-t border-border dark:border-border-dark py-6 text-center text-sm text-muted dark:text-muted-dark">
    © {{ date('Y') }} NEXA Skillmatching
  </footer>

  <script>
    let sortDirection = {};
    
    function sortTable(columnIndex) {
      const table = document.querySelector('table');
      const tbody = table.querySelector('tbody');
      const rows = Array.from(tbody.querySelectorAll('tr'));
      
      // Toggle sort direction
      sortDirection[columnIndex] = sortDirection[columnIndex] === 'asc' ? 'desc' : 'asc';
      const direction = sortDirection[columnIndex];
      
      // Remove existing sort indicators
      table.querySelectorAll('th svg').forEach(svg => {
        svg.style.display = 'none';
      });
      
      // Add sort indicator to current column
      const currentHeader = table.querySelectorAll('th')[columnIndex];
      const currentSvg = currentHeader.querySelector('svg');
      currentSvg.style.display = 'inline-block';
      
      // Rotate arrow based on direction
      if (direction === 'asc') {
        currentSvg.style.transform = 'rotate(180deg)';
      } else {
        currentSvg.style.transform = 'rotate(0deg)';
      }
      
      // Sort rows
      rows.sort((a, b) => {
        let aValue, bValue;
        
        switch(columnIndex) {
          case 0: // Vacature title
            aValue = a.cells[0].querySelector('.text-sm.font-medium').textContent.trim();
            bValue = b.cells[0].querySelector('.text-sm.font-medium').textContent.trim();
            break;
          case 1: // Bedrijf
            aValue = a.cells[1].textContent.trim();
            bValue = b.cells[1].textContent.trim();
            break;
          case 2: // Locatie
            aValue = a.cells[2].textContent.trim();
            bValue = b.cells[2].textContent.trim();
            break;
          case 3: // Type
            aValue = a.cells[3].textContent.trim();
            bValue = b.cells[3].textContent.trim();
            break;
          case 4: // Salaris
            aValue = a.cells[4].textContent.trim();
            bValue = b.cells[4].textContent.trim();
            // Handle "Niet opgegeven" as lowest value
            if (aValue === 'Niet opgegeven') aValue = '0';
            if (bValue === 'Niet opgegeven') bValue = '0';
            break;
          case 5: // Match score
            aValue = parseInt(a.cells[5].querySelector('span').textContent.replace('%', ''));
            bValue = parseInt(b.cells[5].querySelector('span').textContent.replace('%', ''));
            break;
          default:
            return 0;
        }
        
        if (typeof aValue === 'string' && typeof bValue === 'string') {
          return direction === 'asc' ? aValue.localeCompare(bValue) : bValue.localeCompare(aValue);
        } else {
          return direction === 'asc' ? aValue - bValue : bValue - aValue;
        }
      });
      
      // Re-append sorted rows
      rows.forEach(row => tbody.appendChild(row));
    }
    
    // Row click: navigate to detail (except when clicking Acties column)
    document.getElementById('vacature_matching_table').addEventListener('click', function(e) {
      const row = e.target.closest('tbody tr[data-href]');
      if (!row) return;
      if (e.target.closest('[data-actions-cell]')) return;
      window.location.href = row.dataset.href;
    });

    // Initialize table
    document.addEventListener('DOMContentLoaded', function() {
      // Hide all sort arrows initially
      document.querySelectorAll('th svg').forEach(svg => {
        svg.style.display = 'none';
      });
    });
  </script>
</body>
</html>
