@extends('frontend.layouts.dashboard')

@section('title', 'Vacatures - NEXA Skillmatching')

@section('content')
<style>
  @media (min-width: 768px) {
    .jobs-desktop-table {
      display: block !important;
    }
  }
  @media (max-width: 767px) {
    .jobs-desktop-table {
      display: none !important;
    }
  }
</style>
<section class="flex flex-wrap items-center justify-between gap-3 mb-6">
  <div>
    <h1 class="text-2xl font-semibold leading-tight">Vacatures</h1>
    <p class="text-sm text-muted dark:text-muted-dark">Ontdek de nieuwste vacatures en vind de perfecte baan die bij jou past.</p>
  </div>
  <div class="flex items-center gap-2">
    <span class="pill">{{ $jobs->total() ?? 0 }} resultaten</span>
    @if(request('location'))
      <span class="pill pill-outline">Locatie: {{ request('location') }}</span>
    @endif
    @if(request('distance'))
      <span class="pill pill-outline">Afstand: {{ request('distance') }} km</span>
    @endif
    <div class="flex items-center gap-2">
      <label class="text-sm text-muted dark:text-muted-dark">Items per pagina:</label>
      <select class="select w-auto" onchange="changeItemsPerPage(this.value)">
        <option value="5" {{ request('per_page', 15) == 5 ? 'selected' : '' }}>5</option>
        <option value="15" {{ request('per_page', 15) == 15 ? 'selected' : '' }}>15</option>
        <option value="25" {{ request('per_page', 15) == 25 ? 'selected' : '' }}>25</option>
        <option value="50" {{ request('per_page', 15) == 50 ? 'selected' : '' }}>50</option>
        <option value="100" {{ request('per_page', 15) == 100 ? 'selected' : '' }}>100</option>
      </select>
    </div>
  </div>
</section>

<!-- Search and Filters -->
<div class="card p-4 mb-6">
  <form method="GET" action="{{ route('jobs.index') }}" id="searchForm">
    <div class="flex flex-col md:flex-row gap-4">
      <div class="flex-1">
        <input type="search" name="q" class="input" placeholder="Zoek vacatures, bedrijven of skills…" value="{{ request('q') }}">
      </div>
      <div class="flex gap-2">
        <select name="sort" class="select w-auto">
          <option value="published_at" {{ request('sort', 'published_at') == 'published_at' ? 'selected' : '' }}>Nieuwste eerst</option>
          <option value="title" {{ request('sort') == 'title' ? 'selected' : '' }}>Titel A-Z</option>
          <option value="salary_min" {{ request('sort') == 'salary_min' ? 'selected' : '' }}>Salaris (hoog-laag)</option>
          <option value="created_at" {{ request('sort') == 'created_at' ? 'selected' : '' }}>Oudste eerst</option>
        </select>
        <button type="submit" class="btn btn-primary">Zoeken</button>
        @if(request()->hasAny(['q', 'sort']))
          <a href="{{ route('jobs.index', request()->only(['location', 'distance', 'category', 'employment_type', 'experience_level', 'salary_min', 'salary_max', 'remote_work', 'travel_expenses', 'skills', 'per_page'])) }}" class="btn btn-outline">Reset</a>
        @endif
      </div>
    </div>
    
    <!-- Hidden fields to preserve other parameters -->
    @if(request('per_page'))
      <input type="hidden" name="per_page" value="{{ request('per_page') }}">
    @endif
    @if(request('location'))
      <input type="hidden" name="location" value="{{ request('location') }}">
    @endif
    @if(request('distance'))
      <input type="hidden" name="distance" value="{{ request('distance') }}">
    @endif
    @if(request('category'))
      <input type="hidden" name="category" value="{{ request('category') }}">
    @endif
    @if(request('employment_type'))
      <input type="hidden" name="employment_type" value="{{ request('employment_type') }}">
    @endif
    @if(request('experience_level'))
      <input type="hidden" name="experience_level" value="{{ request('experience_level') }}">
    @endif
    @if(request('salary_min'))
      <input type="hidden" name="salary_min" value="{{ request('salary_min') }}">
    @endif
    @if(request('salary_max'))
      <input type="hidden" name="salary_max" value="{{ request('salary_max') }}">
    @endif
    @if(request('remote_work'))
      <input type="hidden" name="remote_work" value="{{ request('remote_work') }}">
    @endif
    @if(request('travel_expenses'))
      <input type="hidden" name="travel_expenses" value="{{ request('travel_expenses') }}">
    @endif
    @if(request('skills'))
      <input type="hidden" name="skills" value="{{ request('skills') }}">
    @endif
  </form>
</div>

@if(!isset($jobs) || $jobs->isEmpty())
  <div class="card p-10 text-center">
    <div class="flex flex-col items-center">
      <div class="w-16 h-16 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center mx-auto mb-4">
        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m8 0V6a2 2 0 012 2v6a2 2 0 01-2 2H6a2 2 0 01-2-2V8a2 2 0 012-2V6"></path>
        </svg>
      </div>
      <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Geen vacatures gevonden</h3>
      <p class="text-gray-500 dark:text-gray-400">Probeer je zoekcriteria aan te passen of kom later terug voor nieuwe kansen!</p>
    </div>
  </div>
@else
  <!-- Mobiele kaarten - alleen op mobiele apparaten (< 768px) -->
  <section class="block md:hidden grid grid-cols-1 gap-6">
    @foreach($jobs as $job)
      @php
        $companyName = $job->company->name ?? 'Directe werkgever';
        $companyInitial = Str::upper(Str::substr($companyName, 0, 1));
        $publishedLabel = $job->publication_date ? $job->publication_date->diffForHumans() : 'Nog niet gepubliceerd';
      @endphp

      <article class="card p-6 flex flex-col h-full hover:shadow-lg transition-all duration-300 hover:-translate-y-1">
        <div class="flex items-start justify-between mb-4">
          <div class="flex items-center gap-3">
            <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg flex items-center justify-center">
              <span class="text-white font-bold text-lg">{{ $companyInitial }}</span>
            </div>
            <div>
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white leading-tight">
                {{ $job->title }}
              </h3>
              <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ $companyName }}
              </p>
            </div>
          </div>

          <span class="badge bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-200">
            @if($job->salary_min && $job->salary_max)
              €{{ number_format($job->salary_min, 0, ',', '.') }} - {{ number_format($job->salary_max, 0, ',', '.') }}
            @else
              Niet opgegeven
            @endif
          </span>
        </div>

        <p class="text-gray-600 dark:text-gray-300 text-sm mb-4 overflow-hidden" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">
          {{ Str::limit(strip_tags($job->description), 150) }}
        </p>

        <div class="flex flex-wrap gap-2 mb-4">
          @if($job->category)
            <span class="px-3 py-1 bg-blue-100 dark:bg-blue-900/20 text-blue-800 dark:text-blue-200 text-xs rounded-full">
              {{ $job->category->name }}
            </span>
          @endif
          @if($job->remote_work)
            <span class="px-3 py-1 bg-green-100 dark:bg-green-800 text-green-800 dark:text-green-100 text-xs rounded-full">
              Remote
            </span>
          @endif
          @if($job->travel_expenses)
            <span class="px-3 py-1 bg-purple-100 dark:bg-purple-900/20 text-purple-800 dark:text-purple-200 text-xs rounded-full">
              Reiskostenvergoeding
            </span>
          @endif
        </div>

        <div class="mt-auto space-y-3">
          <div class="flex items-center justify-between text-sm text-gray-500 dark:text-gray-400">
            <div class="flex items-center">
              <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
              </svg>
              {{ $job->location ?? 'Onbekende locatie' }}
            </div>
            <div class="flex items-center">
              <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
              {{ $publishedLabel }}
            </div>
          </div>

          <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-500 dark:text-gray-400">
                {{ $job->employment_type ?? 'Niet opgegeven' }}
              </span>
              <div class="flex items-center space-x-2">
                <a href="{{ route('jobs.show', array_merge([$job], request()->only(['q', 'location', 'distance', 'category', 'employment_type', 'experience_level', 'salary_min', 'salary_max', 'remote_work', 'travel_expenses', 'skills', 'sort']))) }}"
                   class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 text-sm font-medium">
                  Details
                </a>
                @auth
                  <a href="{{ route('jobs.show', array_merge([$job], request()->only(['q', 'location', 'distance', 'category', 'employment_type', 'experience_level', 'salary_min', 'salary_max', 'remote_work', 'travel_expenses', 'skills', 'sort']))) }}#solliciteer"
                     class="btn btn-primary text-sm px-4 py-2">
                    Solliciteer
                  </a>
                @else
                  <a href="{{ route('login') }}" class="btn btn-primary text-sm px-4 py-2">
                    Log in om te solliciteren
                  </a>
                @endauth
              </div>
            </div>
          </div>
        </div>
      </article>
    @endforeach
  </section>

  <!-- Desktop tabel - alleen op tablets en desktop (>= 768px) -->
  <section class="jobs-desktop-table hidden md:block card overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full" data-jobs-table>
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
            Publicatiedatum
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
        @foreach($jobs as $job)
        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
          <td class="px-4 py-4">
            <div class="flex flex-col">
              <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                {{ $job->title }}
              </div>
              <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                {{ Str::limit(strip_tags($job->description), 80) }}
              </div>
              <div class="flex flex-wrap gap-1 mt-2">
                @if($job->category)
                  <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                    {{ $job->category->name }}
                  </span>
                @endif
                @if($job->remote_work)
                  <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                    Remote
                  </span>
                @endif
                @if($job->travel_expenses)
                  <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-800 dark:text-purple-100">
                    Reiskosten
                  </span>
                @endif
              </div>
            </div>
          </td>
          <td class="px-4 py-4">
            <div class="text-sm text-gray-900 dark:text-gray-100">
                @if($job->company && $job->company->is_intermediary)
                  {{ $job->company->name }}
                @else
                  <span class="text-gray-500 dark:text-gray-400">Directe werkgever</span>
                @endif
            </div>
          </td>
          <td class="px-4 py-4">
            <div class="text-sm text-gray-900 dark:text-gray-100">
              {{ $job->location }}
            </div>
          </td>
          <td class="px-4 py-4">
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
              {{ $job->employment_type ?? 'Niet opgegeven' }}
            </span>
          </td>
          <td class="px-4 py-4">
            <div class="text-sm text-gray-900 dark:text-gray-100">
              @if($job->salary_min && $job->salary_max)
                €{{ number_format($job->salary_min, 0, ',', '.') }} - €{{ number_format($job->salary_max, 0, ',', '.') }}
              @else
                Niet opgegeven
              @endif
            </div>
          </td>
          <td class="px-4 py-4">
            <div class="text-sm text-gray-900 dark:text-gray-100">
              {{ $job->publication_date ? $job->publication_date->format('d-m-Y') : 'Niet gepubliceerd' }}
            </div>
          </td>
          <td class="px-4 py-4">
            <div class="flex items-center gap-2">
              <a href="{{ route('jobs.show', array_merge([$job], request()->only(['q', 'location', 'distance', 'category', 'employment_type', 'experience_level', 'salary_min', 'salary_max', 'remote_work', 'travel_expenses', 'skills', 'sort']))) }}" 
                 class="inline-flex items-center px-3 py-1.5 border border-gray-300 dark:border-gray-600 text-xs font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                Details
              </a>
              @auth
              <a href="{{ route('jobs.show', array_merge([$job], request()->only(['q', 'location', 'distance', 'category', 'employment_type', 'experience_level', 'salary_min', 'salary_max', 'remote_work', 'travel_expenses', 'skills', 'sort']))) }}#solliciteer"
                 class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 transition-colors">
                Solliciteer
              </a>
              @else
              <a href="{{ route('login') }}" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 transition-colors">
                Log in
              </a>
              @endauth
            </div>
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</section>
@endif

<!-- Pagination -->
@if($jobs->hasPages())
  <div class="mt-8">
    {{ $jobs->links() }}
  </div>
@endif

<script>
let sortDirection = {};

function sortTable(columnIndex) {
  const table = document.querySelector('table[data-jobs-table]');
  if (!table) return;
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
        // Extract numeric value from salary range (take the minimum salary)
        aValue = aValue.replace(/[€,\s]/g, '').split('-')[0] || '0';
        bValue = bValue.replace(/[€,\s]/g, '').split('-')[0] || '0';
        aValue = parseInt(aValue) || 0;
        bValue = parseInt(bValue) || 0;
        break;
      case 5: // Publicatiedatum
        aValue = a.cells[5].textContent.trim();
        bValue = b.cells[5].textContent.trim();
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
  const table = document.querySelector('table[data-jobs-table]');
  if (table) {
    table.querySelectorAll('th svg').forEach(svg => {
      svg.style.display = 'none';
    });
  }
});

// Change items per page
function changeItemsPerPage(perPage) {
  const url = new URL(window.location);
  url.searchParams.set('per_page', perPage);
  url.searchParams.delete('page'); // Reset to first page
  window.location.href = url.toString();
}

// Auto-submit form on sort change
document.addEventListener('DOMContentLoaded', function() {
  const sortSelect = document.querySelector('select[name="sort"]');
  if (sortSelect) {
    sortSelect.addEventListener('change', function() {
      document.getElementById('searchForm').submit();
    });
  }
});
</script>
@endsection