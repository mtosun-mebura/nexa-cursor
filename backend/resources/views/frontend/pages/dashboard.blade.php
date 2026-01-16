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
      <table class="w-full">
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
          <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
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
            <td class="px-4 py-4">
              <div class="flex items-center gap-2">
                <a href="{{ route('frontend.vacancy-details', array_merge(['company' => $vacancy->company->slug, 'vacancy' => $vacancy->id], request()->only(['q', 'location', 'distance', 'category', 'employment_type', 'experience_level', 'salary_min', 'salary_max', 'remote_work', 'travel_expenses', 'skills', 'sort']))) }}" 
                   class="inline-flex items-center px-3 py-1.5 border border-gray-300 dark:border-gray-600 text-xs font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                  Details
                </a>
                <button class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 transition-colors">
                  Solliciteer
                </button>
              </div>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="7" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
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

function sortTable(columnIndex) {
  const table = document.querySelector('table');
  if (!table) return; // Table doesn't exist, exit early
  
  const tbody = table.querySelector('tbody');
  if (!tbody) return; // Tbody doesn't exist, exit early
  
  const rows = Array.from(tbody.querySelectorAll('tr'));
  
  // Toggle sort direction
  sortDirection[columnIndex] = sortDirection[columnIndex] === 'asc' ? 'desc' : 'asc';
  const direction = sortDirection[columnIndex];
  
  // Remove existing sort indicators
  if (table) {
    table.querySelectorAll('th svg').forEach(svg => {
      svg.style.display = 'none';
    });
  }
  
  // Add sort indicator to current column
  const currentHeader = table.querySelectorAll('th')[columnIndex];
  if (!currentHeader) return; // Header doesn't exist, exit early
  
  const currentSvg = currentHeader.querySelector('svg');
  if (!currentSvg) return; // SVG doesn't exist, exit early
  
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

// Initialize table
document.addEventListener('DOMContentLoaded', function() {
  // Hide all sort arrows initially
  const table = document.querySelector('table');
  if (table) {
    table.querySelectorAll('th svg').forEach(svg => {
      svg.style.display = 'none';
    });
  }
});
</script>
@endsection
