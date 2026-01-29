@extends('frontend.layouts.dashboard')

@section('title', 'Dashboard - NEXA Skillmatching')

@section('content')
<section class="flex flex-wrap items-center justify-between gap-3">
  <div>
    <h1 class="text-2xl font-semibold leading-tight">Dashboard</h1>
    <p class="text-sm text-muted dark:text-muted-dark">Welkom terug, {{ Auth::user()->first_name }}!</p>
  </div>
  <div class="flex items-center gap-2">
    <span class="pill"><span class="h-2 w-2 rounded-full bg-brand-500"></span> Nieuwe matches</span>
    <span class="pill">{{ $vacancies->count() }} resultaten</span>
  </div>
</section>

<!-- Stats Cards -->
<section class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
  <div class="card p-4">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-sm text-muted dark:text-muted-dark">Totaal Matches</p>
        <p class="text-2xl font-semibold">{{ $stats['total_matches'] }}</p>
      </div>
      <div class="h-8 w-8 bg-brand-100 dark:bg-brand-900/20 rounded-lg flex items-center justify-center">
        <svg class="h-4 w-4 text-brand-600 dark:text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
        </svg>
      </div>
    </div>
  </div>
  
  <div class="card p-4">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-sm text-muted dark:text-muted-dark">Actieve Sollicitaties</p>
        <p class="text-2xl font-semibold">{{ $stats['active_applications'] }}</p>
      </div>
      <div class="h-8 w-8 bg-green-100 dark:bg-green-900/20 rounded-lg flex items-center justify-center">
        <svg class="h-4 w-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
      </div>
    </div>
  </div>
  
  <div class="card p-4">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-sm text-muted dark:text-muted-dark">Interviews</p>
        <p class="text-2xl font-semibold">{{ $stats['interviews'] }}</p>
      </div>
      <div class="h-8 w-8 bg-blue-100 dark:bg-blue-900/20 rounded-lg flex items-center justify-center">
        <svg class="h-4 w-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
        </svg>
      </div>
    </div>
  </div>
  
  <div class="card p-4">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-sm text-muted dark:text-muted-dark">Profiel Compleet</p>
        <p class="text-2xl font-semibold">{{ $stats['profile_complete'] }}%</p>
      </div>
      <div class="h-8 w-8 bg-purple-100 dark:bg-purple-900/20 rounded-lg flex items-center justify-center">
        <svg class="h-4 w-4 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
        </svg>
      </div>
    </div>
  </div>
</section>

<!-- Recent Matches -->
<section>
  <div class="flex items-center justify-between mb-4">
    <h2 class="text-lg font-semibold">Recente Matches</h2>
    <a href="{{ route('matches') }}" class="text-sm text-brand-600 dark:text-brand-400 hover:underline">Bekijk alle</a>
  </div>
  
  <div class="card overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full" id="dashboard_matches_table" data-default-sort-column="5" data-default-sort-direction="desc">
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
          <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors cursor-pointer" data-href="{{ route('frontend.vacancy-details', array_merge(['companySlug' => $vacancy->company->slug, 'vacancyId' => $vacancy->id], request()->only(['q', 'location', 'distance', 'category', 'employment_type', 'experience_level', 'salary_min', 'salary_max', 'remote_work', 'travel_expenses', 'skills', 'sort']), ['from' => 'dashboard'])) }}">
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
  const table = document.querySelector('#dashboard_matches_table');
  if (!table) return; // Table doesn't exist, exit early
  
  const tbody = table.querySelector('tbody');
  if (!tbody) return; // Tbody doesn't exist, exit early
  
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
        const aTitleEl = a.cells[0]?.querySelector('.text-sm.font-medium');
        const bTitleEl = b.cells[0]?.querySelector('.text-sm.font-medium');
        aValue = aTitleEl ? aTitleEl.textContent.trim() : '';
        bValue = bTitleEl ? bTitleEl.textContent.trim() : '';
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
        break;
      case 5: // Match score
        const aScoreEl = a.cells[5]?.querySelector('span');
        const bScoreEl = b.cells[5]?.querySelector('span');
        aValue = aScoreEl ? parseInt(aScoreEl.textContent.replace('%', '')) : 0;
        bValue = bScoreEl ? parseInt(bScoreEl.textContent.replace('%', '')) : 0;
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

// Row click: ga naar vacature-detail
document.addEventListener('DOMContentLoaded', function() {
  const table = document.getElementById('dashboard_matches_table');
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
