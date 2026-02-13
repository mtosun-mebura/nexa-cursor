@extends('frontend.layouts.dashboard')

@section('title', 'Matches - NEXA Skillmatching')

@section('content')
<section class="flex flex-wrap items-center justify-between gap-3">
  <div>
    <h1 class="kt-page-title text-gray-900 dark:text-white">Vacature-matching</h1>
    <p class="text-sm text-muted dark:text-muted-dark">Geselecteerd op jouw profiel en voorkeuren.</p>
  </div>
  <div class="flex items-center gap-2">
    <span class="pill"><span class="h-2 w-2 rounded-full bg-brand-500"></span> Nieuwe matches</span>
    <span class="pill">{{ $vacancies->count() }} resultaten</span>
  </div>
</section>

<section class="card overflow-hidden">
  <div class="overflow-x-auto">
    <table class="w-full" id="matches_table" data-default-sort-column="5" data-default-sort-direction="desc">
      <thead class="bg-gray-200 dark:bg-gray-800 border-b border-gray-300 dark:border-gray-700">
        <tr>
          <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:bg-gray-300 dark:hover:bg-gray-700" onclick="sortTable(0)">
            <span class="flex items-center justify-between w-full">
              <span>Vacature</span>
              <span class="kt-table-col-sort inline-flex flex-shrink-0 ml-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.25 15L12 18.75 15.75 15M8.25 9L12 5.25 15.75 9"></path>
                </svg>
              </span>
            </span>
          </th>
          <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:bg-gray-300 dark:hover:bg-gray-700" onclick="sortTable(1)">
            <span class="flex items-center justify-between w-full">
              <span>Bedrijf</span>
              <span class="kt-table-col-sort inline-flex flex-shrink-0 ml-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.25 15L12 18.75 15.75 15M8.25 9L12 5.25 15.75 9"></path>
                </svg>
              </span>
            </span>
          </th>
          <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:bg-gray-300 dark:hover:bg-gray-700" onclick="sortTable(2)">
            <span class="flex items-center justify-between w-full">
              <span>Locatie</span>
              <span class="kt-table-col-sort inline-flex flex-shrink-0 ml-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.25 15L12 18.75 15.75 15M8.25 9L12 5.25 15.75 9"></path>
                </svg>
              </span>
            </span>
          </th>
          <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:bg-gray-300 dark:hover:bg-gray-700" onclick="sortTable(3)">
            <span class="flex items-center justify-between w-full">
              <span>Type</span>
              <span class="kt-table-col-sort inline-flex flex-shrink-0 ml-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.25 15L12 18.75 15.75 15M8.25 9L12 5.25 15.75 9"></path>
                </svg>
              </span>
            </span>
          </th>
          <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:bg-gray-300 dark:hover:bg-gray-700" onclick="sortTable(4)">
            <span class="flex items-center justify-between w-full">
              <span>Salaris</span>
              <span class="kt-table-col-sort inline-flex flex-shrink-0 ml-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.25 15L12 18.75 15.75 15M8.25 9L12 5.25 15.75 9"></path>
                </svg>
              </span>
            </span>
          </th>
          <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:bg-gray-300 dark:hover:bg-gray-700" onclick="sortTable(5)">
            <span class="flex items-center justify-between w-full">
              <span>Match</span>
              <span class="kt-table-col-sort inline-flex flex-shrink-0 ml-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.25 15L12 18.75 15.75 15M8.25 9L12 5.25 15.75 9"></path>
                </svg>
              </span>
            </span>
          </th>
        </tr>
      </thead>
      <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
        @forelse($vacancies as $vacancy)
        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors cursor-pointer" data-href="{{ route('frontend.vacancy-details', array_merge(['companySlug' => $vacancy->company->slug, 'vacancyId' => $vacancy->id], request()->only(['q', 'location', 'distance', 'category', 'employment_type', 'experience_level', 'salary_min', 'salary_max', 'remote_work', 'travel_expenses', 'skills', 'sort']), ['from' => 'matches'])) }}">
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
        </tr>
        @empty
        <tr>
          <td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
            Geen vacatures gevonden die matchen met jouw profiel.
          </td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</section>

<script>
  let sortDirection = {};
  var SORT_ICON_PATHS = {
    up: 'M5 15l7-7 7 7',
    down: 'M19 9l-7 7-7-7',
    upDown: 'M8.25 15L12 18.75 15.75 15M8.25 9L12 5.25 15.75 9'
  };
  function updateSortIcons(table, sortDirection) {
    var headers = table.querySelectorAll('thead th');
    headers.forEach(function(th, i) {
      var path = th.querySelector('.kt-table-col-sort path');
      if (!path) return;
      var state = sortDirection[i] === 'desc' ? 'up' : (sortDirection[i] === 'asc' ? 'down' : 'upDown');
      path.setAttribute('d', SORT_ICON_PATHS[state]);
    });
  }
  function sortTable(columnIndex) {
    const table = document.getElementById('matches_table');
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    
    // Toggle sort direction; only one column is sorted at a time
    const direction = sortDirection[columnIndex] === 'asc' ? 'desc' : 'asc';
    sortDirection = {};
    sortDirection[columnIndex] = direction;
    updateSortIcons(table, sortDirection);
    
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
  
  // Row click: navigate to detail; init sort icons on load
  document.addEventListener('DOMContentLoaded', function() {
    var table = document.getElementById('matches_table');
    if (table) {
      var defaultCol = parseInt(table.dataset.defaultSortColumn, 10) || 5;
      var defaultDir = (table.dataset.defaultSortDirection || 'desc');
      sortDirection[defaultCol] = defaultDir;
      updateSortIcons(table, sortDirection);
      table.addEventListener('click', function(e) {
        const row = e.target.closest('tbody tr[data-href]');
        if (!row) return;
        window.location.href = row.dataset.href;
      });
    }
  });

</script>
@endsection
