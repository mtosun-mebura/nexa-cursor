@extends('admin.layouts.app')

@section('title', 'Matches Beheer')

@section('content')

<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5">
        <h1 class="text-xl font-medium leading-none text-mono">
            Matches Beheer
        </h1>
        @can('create-matches')
        <a href="{{ route('admin.matches.create') }}" class="kt-btn kt-btn-primary">
            <i class="ki-filled ki-plus me-2"></i>
            Nieuwe Match
        </a>
        @endcan
    </div>

    <!-- Success Alert -->
    @if(session('success'))
        <div class="kt-alert kt-alert-success mb-5" id="success-alert" role="alert">
            <i class="ki-filled ki-check-circle me-2"></i>
            {{ session('success') }}
        </div>
    @endif

    <!-- Statistics Cards -->
    <div class="kt-card mb-5">
        <div class="kt-card-content">
            <div class="flex flex-col sm:flex-row lg:px-10 py-1.5 gap-2">
                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                    <span class="text-mono text-2xl lg:text-2xl leading-none font-semibold">
                        {{ $stats['pending'] ?? 0 }}
                    </span>
                    <span class="text-secondary-foreground text-sm">
                        In Afwachting
                    </span>
                </div>
                <span class="hidden sm:block not-last:border-e border-e-input my-1"></span>
                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                    <span class="text-mono text-2xl lg:text-2xl leading-none font-semibold">
                        {{ $stats['accepted'] ?? 0 }}
                    </span>
                    <span class="text-secondary-foreground text-sm">
                        Geaccepteerd
                    </span>
                </div>
                <span class="hidden sm:block not-last:border-e border-e-input my-1"></span>
                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                    <span class="text-mono text-2xl lg:text-2xl leading-none font-semibold">
                        {{ $stats['interview'] ?? 0 }}
                    </span>
                    <span class="text-secondary-foreground text-sm">
                        Interview
                    </span>
                </div>
                <span class="hidden sm:block not-last:border-e border-e-input my-1"></span>
                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                    <span class="text-mono text-2xl lg:text-2xl leading-none font-semibold">
                        {{ $stats['total_matches'] ?? 0 }}
                    </span>
                    <span class="text-secondary-foreground text-sm">
                        Totaal Matches
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="grid gap-5 lg:gap-7.5">
        <div class="kt-card kt-card-grid min-w-full">
            <div class="kt-card-header py-5 flex-wrap gap-2">
                <h3 class="kt-card-title text-sm pb-3 w-full">
                    Toon 1 tot {{ $matches->count() }} van {{ $matches->count() }} matches
                </h3>
                <div class="flex flex-col sm:flex-row flex-wrap gap-2 lg:gap-5 justify-center sm:justify-end items-center w-full">
                    <!-- Search -->
                    <div class="flex w-full sm:w-auto justify-center sm:justify-start">
                        <form method="GET" action="{{ route('admin.matches.index') }}" class="flex gap-2" id="search-form">
                            @if(request('status'))
                                <input type="hidden" name="status" value="{{ request('status') }}">
                            @endif
                            @if(request('company'))
                                <input type="hidden" name="company" value="{{ request('company') }}">
                            @endif
                            @if(request('score'))
                                <input type="hidden" name="score" value="{{ request('score') }}">
                            @endif
                            @if(request('vacancy'))
                                <input type="hidden" name="vacancy" value="{{ request('vacancy') }}">
                            @endif
                            @if(request('age_range'))
                                <input type="hidden" name="age_range" value="{{ request('age_range') }}">
                            @endif
                            @if(request('sort'))
                                <input type="hidden" name="sort" value="{{ request('sort') }}">
                            @endif
                            @if(request('direction'))
                                <input type="hidden" name="direction" value="{{ request('direction') }}">
                            @endif
                            @if(request('per_page'))
                                <input type="hidden" name="per_page" value="{{ request('per_page') }}">
                            @endif
                            <label class="kt-input w-full sm:w-64" style="position: relative !important;">
                                <i class="ki-filled ki-magnifier"></i>
                                <input placeholder="Zoek matches..." 
                                       type="text" 
                                       name="search" 
                                       value="{{ request('search') }}"
                                       id="search-input"
                                       data-kt-datatable-search="#matches_table"/>
                            </label>
                        </form>
                    </div>
                    <!-- Filters -->
                    <div class="flex flex-col sm:flex-row flex-wrap gap-2.5 items-center justify-center sm:justify-start w-full sm:w-auto">
                        <form method="GET" action="{{ route('admin.matches.index') }}" id="filters-form" class="flex flex-col sm:flex-row gap-2.5 w-full sm:w-auto items-center sm:items-stretch">
                            @if(request('search'))
                                <input type="hidden" name="search" value="{{ request('search') }}">
                            @endif
                            
                            <select class="kt-select w-full sm:w-36" 
                                    name="status" 
                                    data-kt-select="true" 
                                    data-kt-select-placeholder="Status"
                                    id="status-filter">
                                <option value="">Alle statussen</option>
                                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>In Afwachting</option>
                                <option value="accepted" {{ request('status') == 'accepted' ? 'selected' : '' }}>Geaccepteerd</option>
                                <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Afgewezen</option>
                                <option value="interview" {{ request('status') == 'interview' ? 'selected' : '' }}>Interview</option>
                            </select>
                            
                            @if($vacancies->count() > 0)
                            <select class="kt-select w-full sm:w-36" 
                                    name="vacancy" 
                                    data-kt-select="true" 
                                    data-kt-select-placeholder="Vacature"
                                    id="vacancy-filter">
                                <option value="">Alle vacatures</option>
                                @foreach($vacancies as $vacancy)
                                    <option value="{{ $vacancy->id }}" {{ request('vacancy') == $vacancy->id ? 'selected' : '' }}>
                                        {{ $vacancy->title }}
                                    </option>
                                @endforeach
                            </select>
                            @endif
                            
                            <select class="kt-select w-full sm:w-36" 
                                    name="score" 
                                    data-kt-select="true" 
                                    data-kt-select-placeholder="Score"
                                    id="score-filter">
                                <option value="">Alle scores</option>
                                <option value="high" {{ request('score') == 'high' ? 'selected' : '' }}>Hoog (80%+)</option>
                                <option value="medium" {{ request('score') == 'medium' ? 'selected' : '' }}>Gemiddeld (60-79%)</option>
                                <option value="low" {{ request('score') == 'low' ? 'selected' : '' }}>Laag (<60%)</option>
                            </select>
                            
                            <select class="kt-select w-full sm:w-36" 
                                    name="age_range" 
                                    data-kt-select="true" 
                                    data-kt-select-placeholder="Leeftijd"
                                    id="age-range-filter">
                                <option value="">Alle leeftijden</option>
                                <option value="18-25" {{ request('age_range') == '18-25' ? 'selected' : '' }}>18-25 jaar</option>
                                <option value="26-30" {{ request('age_range') == '26-30' ? 'selected' : '' }}>26-30 jaar</option>
                                <option value="31-35" {{ request('age_range') == '31-35' ? 'selected' : '' }}>31-35 jaar</option>
                                <option value="36-40" {{ request('age_range') == '36-40' ? 'selected' : '' }}>36-40 jaar</option>
                                <option value="41-50" {{ request('age_range') == '41-50' ? 'selected' : '' }}>41-50 jaar</option>
                                <option value="50+" {{ request('age_range') == '50+' ? 'selected' : '' }}>50+ jaar</option>
                            </select>
                            
                            <select class="kt-select w-full sm:w-36" 
                                    name="sort" 
                                    data-kt-select="true" 
                                    data-kt-select-placeholder="Sortering"
                                    id="sort-filter">
                                <option value="" {{ !request('sort') ? 'selected' : '' }}>Geen sortering</option>
                                <option value="created_at" {{ request('sort') == 'created_at' ? 'selected' : '' }}>Datum</option>
                                <option value="match_score" {{ request('sort') == 'match_score' ? 'selected' : '' }}>Score</option>
                                <option value="status" {{ request('sort') == 'status' ? 'selected' : '' }}>Status</option>
                            </select>
                        </form>
                        @if(request('status') || request('company') || request('vacancy') || request('score') || request('age_range') || (request('sort') && request('sort') != 'created_at') || request('direction') || request('search'))
                        <a href="{{ route('admin.matches.index') }}" 
                           class="kt-btn kt-btn-outline kt-btn-icon" 
                           title="Filters resetten"
                           id="reset-filter-btn"
                           style="display: inline-flex !important; visibility: visible !important; opacity: 1 !important; min-width: 34px !important; height: 34px !important; align-items: center !important; justify-content: center !important; border: 1px solid var(--input) !important; background-color: var(--background) !important; color: var(--secondary-foreground) !important; position: relative !important; z-index: 1 !important;">
                            <i class="ki-filled ki-arrows-circle text-base" style="display: block !important; visibility: visible !important; opacity: 1 !important; font-size: 1rem !important;"></i>
                        </a>
                        @endif
                    </div>
                </div>
            </div>
            
            <div class="kt-card-content">
                @if($matches->count() > 0)
                    <div class="grid" data-kt-datatable="true" data-kt-datatable-page-size="10" id="matches_table">
                        <div class="kt-scrollable-x-auto">
                            <table class="kt-table table-auto kt-table-border" data-kt-datatable-table="true">
                            <thead>
                                <tr>
                                    <th class="min-w-[200px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Vacature</span>
                                            <span class="kt-table-col-sort"></span>
                                        </span>
                                    </th>
                                    <th class="min-w-[250px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Kandidaat</span>
                                            <span class="kt-table-col-sort"></span>
                                        </span>
                                    </th>
                                    <th class="min-w-[100px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Leeftijd</span>
                                            <span class="kt-table-col-sort"></span>
                                        </span>
                                    </th>
                                    <th class="min-w-[120px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Match Score</span>
                                            <span class="kt-table-col-sort">
                                                @php
                                                    $currentSort = request('sort');
                                                    $currentDirection = request('direction');
                                                    if ($currentSort == 'match_score') {
                                                        $nextDirection = ($currentDirection == 'desc') ? 'asc' : 'desc';
                                                    } else {
                                                        $nextDirection = 'desc';
                                                    }
                                                @endphp
                                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'match_score', 'direction' => $nextDirection]) }}" 
                                                   class="kt-table-col-sort-btn"></a>
                                            </span>
                                        </span>
                                    </th>
                                    <th class="min-w-[120px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Status</span>
                                            <span class="kt-table-col-sort">
                                                @php
                                                    $currentSort = request('sort');
                                                    $currentDirection = request('direction');
                                                    if ($currentSort == 'status') {
                                                        $nextDirection = ($currentDirection == 'asc') ? 'desc' : 'asc';
                                                    } else {
                                                        $nextDirection = 'asc';
                                                    }
                                                @endphp
                                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'status', 'direction' => $nextDirection]) }}" 
                                                   class="kt-table-col-sort-btn"></a>
                                            </span>
                                        </span>
                                    </th>
                                    <th class="min-w-[150px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Aangemaakt</span>
                                            <span class="kt-table-col-sort">
                                                @php
                                                    $currentSort = request('sort');
                                                    $currentDirection = request('direction');
                                                    if ($currentSort == 'created_at') {
                                                        $nextDirection = ($currentDirection == 'desc') ? 'asc' : 'desc';
                                                    } else {
                                                        $nextDirection = 'desc';
                                                    }
                                                @endphp
                                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'created_at', 'direction' => $nextDirection]) }}" 
                                                   class="kt-table-col-sort-btn"></a>
                                            </span>
                                        </span>
                                    </th>
                                    <th class="w-[60px] text-center">Acties</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($matches as $match)
                                    <tr class="match-row" data-match-id="{{ $match->id }}" data-candidate-id="{{ $match->candidate_id ?? '' }}">
                                        <td class="text-foreground font-normal">
                                            @if($match->vacancy)
                                                <div class="flex flex-col">
                                                    <span class="text-sm font-medium">{{ $match->vacancy->title }}</span>
                                                    @if($match->vacancy->location)
                                                        <span class="text-xs text-muted-foreground">{{ $match->vacancy->location }}</span>
                                                    @endif
                                                </div>
                                            @else
                                                <span class="text-sm text-muted-foreground">Vacature niet gevonden</span>
                                            @endif
                                        </td>
                                        <td class="text-foreground font-normal">
                                            @if($match->candidate)
                                                <div class="flex flex-col">
                                                    <span class="text-sm font-medium text-mono mb-px">
                                                        {{ $match->candidate->first_name }} {{ $match->candidate->last_name }} (K)
                                                    </span>
                                                    <span class="text-xs text-muted-foreground">
                                                        {{ $match->candidate->email }}
                                                    </span>
                                                </div>
                                            @else
                                                <span class="text-sm text-muted-foreground">Kandidaat niet gevonden</span>
                                            @endif
                                        </td>
                                        <td class="text-foreground font-normal">
                                            @if($match->candidate && $match->candidate->date_of_birth)
                                                @php
                                                    $age = \Carbon\Carbon::parse($match->candidate->date_of_birth)->age;
                                                @endphp
                                                <span class="text-sm">{{ $age }} jaar</span>
                                            @else
                                                <span class="text-sm text-muted-foreground">-</span>
                                            @endif
                                        </td>
                                        <td class="text-foreground font-normal">
                                            @if($match->match_score)
                                                <span class="kt-badge kt-badge-sm {{ $match->match_score >= 80 ? 'kt-badge-success' : ($match->match_score >= 60 ? 'kt-badge-warning' : 'kt-badge-danger') }}">
                                                    {{ $match->match_score }}%
                                                </span>
                                            @else
                                                <span class="text-sm text-muted-foreground">N/A</span>
                                            @endif
                                        </td>
                                        <td>
                                            @switch($match->status)
                                                @case('pending')
                                                    <span class="kt-badge kt-badge-sm kt-badge-warning">In afwachting</span>
                                                    @break
                                                @case('accepted')
                                                    <span class="kt-badge kt-badge-sm kt-badge-success">Geaccepteerd</span>
                                                    @break
                                                @case('rejected')
                                                    <span class="kt-badge kt-badge-sm kt-badge-danger">Afgewezen</span>
                                                    @break
                                                @case('interview')
                                                    <span class="kt-badge kt-badge-sm kt-badge-info">Interview</span>
                                                    @break
                                                @default
                                                    <span class="kt-badge kt-badge-sm kt-badge-secondary">{{ ucfirst($match->status) }}</span>
                                            @endswitch
                                        </td>
                                        <td class="text-foreground font-normal">
                                            <span class="text-sm">{{ $match->created_at->format('d-m-Y') }}</span>
                                        </td>
                                        <td class="w-[60px]" onclick="event.stopPropagation();">
                                            <div class="kt-menu flex justify-center" data-kt-menu="true">
                                                <div class="kt-menu-item" data-kt-menu-item-offset="0, 10px" data-kt-menu-item-placement="bottom-end" data-kt-menu-item-placement-rtl="bottom-start" data-kt-menu-item-toggle="dropdown" data-kt-menu-item-trigger="click">
                                                    <button class="kt-menu-toggle kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost">
                                                        <i class="ki-filled ki-dots-vertical text-lg"></i>
                                                    </button>
                                                    <div class="kt-menu-dropdown kt-menu-default w-full max-w-[175px]" data-kt-menu-dismiss="true">
                                                        @can('view-matches')
                                                        <div class="kt-menu-item">
                                                            <a class="kt-menu-link" href="{{ route('admin.matches.show', $match) }}">
                                                                <span class="kt-menu-icon">
                                                                    <i class="ki-filled ki-eye"></i>
                                                                </span>
                                                                <span class="kt-menu-title">Bekijken</span>
                                                            </a>
                                                        </div>
                                                        @endcan
                                                        @if(auth()->user()->can('view-matches') || auth()->user()->can('edit-matches'))
                                                        <div class="kt-menu-separator"></div>
                                                        @endif
                                                        @can('delete-matches')
                                                        <div class="kt-menu-item">
                                                            <form action="{{ route('admin.matches.destroy', $match) }}" 
                                                                  method="POST" 
                                                                  style="display: inline;"
                                                                  onsubmit="return confirm('Weet je zeker dat je deze match wilt verwijderen?')">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="kt-menu-link w-full text-left text-danger">
                                                                    <span class="kt-menu-icon">
                                                                        <i class="ki-filled ki-trash"></i>
                                                                    </span>
                                                                    <span class="kt-menu-title">Verwijderen</span>
                                                                </button>
                                                            </form>
                                                        </div>
                                                        @endcan
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        </div>
                    
                    <!-- Pagination -->
                    <div class="kt-card-footer justify-center md:justify-between flex-col md:flex-row gap-5 text-secondary-foreground text-sm font-medium">
                        <div class="flex items-center gap-2 order-2 md:order-1">
                            Toon
                            <select class="kt-select w-24" data-kt-datatable-size="true" data-kt-select="" name="perpage">
                            </select>
                            per pagina
                        </div>
                        <div class="flex items-center gap-4 order-1 md:order-2">
                            <span data-kt-datatable-info="true">
                            </span>
                            <div class="kt-datatable-pagination" data-kt-datatable-pagination="true">
                            </div>
                        </div>
                    </div>
                    </div>
                @else
                    <div class="flex flex-col items-center justify-center py-16">
                        <i class="ki-filled ki-information-5 text-4xl text-muted-foreground mb-4"></i>
                        <h4 class="text-lg font-semibold text-mono mb-2">Geen matches gevonden</h4>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Replace "of" with "van" in pagination info
        function replaceOfWithVan() {
            const infoSpan = document.querySelector('[data-kt-datatable-info="true"]');
            if (infoSpan && infoSpan.textContent.includes(' of ')) {
                infoSpan.textContent = infoSpan.textContent.replace(' of ', ' van ');
            }
        }
        
        // Initial replacement
        replaceOfWithVan();
        
        // Watch for changes in the info span
        const infoSpan = document.querySelector('[data-kt-datatable-info="true"]');
        if (infoSpan) {
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'childList' || mutation.type === 'characterData') {
                        replaceOfWithVan();
                    }
                });
            });
            
            observer.observe(infoSpan, {
                childList: true,
                characterData: true,
                subtree: true
            });
        }
        
        // Filter form submission (server-side filters)
        const filterForm = document.getElementById('filters-form');
        const statusFilter = document.getElementById('status-filter');
        const companyFilter = document.getElementById('company-filter');
        // Zet data-attribuut voor fallback CSS voor vacature en score dropdowns
        function setFilterAttribute(filterId, attributeName) {
            const filter = document.getElementById(filterId);
            if (filter) {
                const wrapper = filter.closest('.kt-select-wrapper') || filter.parentElement;
                if (wrapper) {
                    wrapper.setAttribute(attributeName, 'true');
                }
            }
        }
        
        function setFilterAttributes() {
            setFilterAttribute('vacancy-filter', 'data-vacancy-filter');
            setFilterAttribute('score-filter', 'data-score-filter');
        }
        
        // Probeer direct
        setFilterAttributes();
        
        // Probeer ook na DOMContentLoaded en na een korte delay (voor KTComponents initialisatie)
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', setFilterAttributes);
        }
        setTimeout(setFilterAttributes, 100);
        setTimeout(setFilterAttributes, 500);
        const vacancyFilter = document.getElementById('vacancy-filter');
        const scoreFilter = document.getElementById('score-filter');
        const ageRangeFilter = document.getElementById('age-range-filter');
        const sortFilter = document.getElementById('sort-filter');
        
        if (statusFilter && filterForm) {
            statusFilter.addEventListener('change', function() {
                filterForm.submit();
            });
        }
        
        if (companyFilter && filterForm) {
            companyFilter.addEventListener('change', function() {
                filterForm.submit();
            });
        }
        
        if (vacancyFilter && filterForm) {
            vacancyFilter.addEventListener('change', function() {
                filterForm.submit();
            });
        }
        
        if (scoreFilter && filterForm) {
            scoreFilter.addEventListener('change', function() {
                filterForm.submit();
            });
        }
        
        if (ageRangeFilter && filterForm) {
            ageRangeFilter.addEventListener('change', function() {
                filterForm.submit();
            });
        }
        
        if (sortFilter && filterForm) {
            sortFilter.addEventListener('change', function() {
                filterForm.submit();
            });
        }
        
        // Auto-dismiss success alert after 3 seconds
        const successAlert = document.getElementById('success-alert');
        if (successAlert) {
            setTimeout(function() {
                successAlert.style.transition = 'opacity 0.3s ease-out';
                successAlert.style.opacity = '0';
                setTimeout(function() {
                    successAlert.remove();
                }, 300);
            }, 3000);
        }
        
        // Make table rows clickable using event delegation on tbody
        function setupRowClicks() {
            const matchesTable = document.getElementById('matches_table');
            if (!matchesTable) {
                return;
            }
            
            const tbody = matchesTable.querySelector('tbody');
            if (!tbody) {
                return;
            }
            
            // Use event delegation - listen on tbody, handle clicks on rows
            // Use capture phase to ensure we get the event first
            tbody.addEventListener('click', function(e) {
                // Find the closest match-row
                const row = e.target.closest('tr.match-row');
                if (!row) {
                    return;
                }
                
                // Don't navigate if clicking on actions column (last td) or menu
                const clickedElement = e.target;
                const actionsTd = row.querySelector('td:last-child');
                const isInActionsColumn = actionsTd && (actionsTd.contains(clickedElement) || clickedElement === actionsTd);
                const isInMenu = clickedElement.closest('.kt-menu') || clickedElement.closest('[data-kt-menu]');
                const isButton = clickedElement.tagName === 'BUTTON' || clickedElement.closest('button');
                const isLink = clickedElement.tagName === 'A' || clickedElement.closest('a');
                
                if (isInActionsColumn || isInMenu || isButton || isLink) {
                    return;
                }
                
                // Get match ID and navigate to match details
                // First try to get it from the "Bekijken" link in the actions menu
                let matchId = null;
                const viewLink = row.querySelector('a[href*="/admin/matches/"]');
                if (viewLink) {
                    const href = viewLink.getAttribute('href');
                    const match = href.match(/\/admin\/matches\/(\d+)/);
                    if (match && match[1]) {
                        matchId = match[1];
                    }
                }
                
                // Fallback to data attribute
                if (!matchId) {
                    matchId = row.getAttribute('data-match-id');
                }
                
                // Fallback to dataset
                if (!matchId || matchId === 'null') {
                    matchId = row.dataset.matchId;
                }
                
                if (matchId && matchId !== 'null' && matchId !== '' && matchId !== null && matchId !== undefined) {
                    // Stop propagation to prevent other handlers
                    e.stopPropagation();
                    e.stopImmediatePropagation();
                    e.preventDefault();
                    
                    // Navigate to match details
                    window.location.href = '/admin/matches/' + matchId;
                }
            }, true); // Use capture phase
            
            // Set cursor pointer on all rows
            const rows = tbody.querySelectorAll('tr.match-row');
            rows.forEach(function(row) {
                row.style.cursor = 'pointer';
            });
        }
        
        // Try immediately
        setupRowClicks();
        
        // Also try after delays
        setTimeout(setupRowClicks, 100);
        setTimeout(setupRowClicks, 500);
        setTimeout(setupRowClicks, 1000);
    });
</script>
@endpush

@push('styles')
<style>
    /* Zorg dat vacature en score dropdown opties volledig zichtbaar zijn */
    #vacancy-filter + .kt-select-wrapper .kt-select-dropdown,
    #vacancy-filter + .kt-select-wrapper [data-kt-select-dropdown],
    .kt-select-wrapper:has(#vacancy-filter) .kt-select-dropdown,
    .kt-select-wrapper:has(#vacancy-filter) [data-kt-select-dropdown],
    #score-filter + .kt-select-wrapper .kt-select-dropdown,
    #score-filter + .kt-select-wrapper [data-kt-select-dropdown],
    .kt-select-wrapper:has(#score-filter) .kt-select-dropdown,
    .kt-select-wrapper:has(#score-filter) [data-kt-select-dropdown] {
        min-width: max-content !important;
        width: auto !important;
        max-width: 500px !important;
    }
    
    /* Zorg dat de opties zelf volledig zichtbaar zijn */
    #vacancy-filter + .kt-select-wrapper .kt-select-options,
    .kt-select-wrapper:has(#vacancy-filter) .kt-select-options,
    #score-filter + .kt-select-wrapper .kt-select-options,
    .kt-select-wrapper:has(#score-filter) .kt-select-options {
        min-width: max-content !important;
    }
    
    /* Zorg dat de optie tekst volledig zichtbaar is (geen ellipsis) */
    #vacancy-filter + .kt-select-wrapper .kt-select-option-text,
    .kt-select-wrapper:has(#vacancy-filter) .kt-select-option-text,
    #score-filter + .kt-select-wrapper .kt-select-option-text,
    .kt-select-wrapper:has(#score-filter) .kt-select-option-text {
        overflow: visible !important;
        white-space: normal !important;
        text-overflow: clip !important;
        word-wrap: break-word !important;
    }
    
    /* Fallback voor browsers die :has() niet ondersteunen */
    .kt-select-wrapper[data-vacancy-filter] .kt-select-dropdown,
    .kt-select-wrapper[data-vacancy-filter] [data-kt-select-dropdown],
    .kt-select-wrapper[data-score-filter] .kt-select-dropdown,
    .kt-select-wrapper[data-score-filter] [data-kt-select-dropdown] {
        min-width: max-content !important;
        width: auto !important;
        max-width: 500px !important;
    }
    
    .kt-select-wrapper[data-vacancy-filter] .kt-select-options,
    .kt-select-wrapper[data-score-filter] .kt-select-options {
        min-width: max-content !important;
    }
    
    .kt-select-wrapper[data-vacancy-filter] .kt-select-option-text,
    .kt-select-wrapper[data-score-filter] .kt-select-option-text {
        overflow: visible !important;
        white-space: normal !important;
        text-overflow: clip !important;
        word-wrap: break-word !important;
    }
</style>
<style>
    /* Table column sorting */
    .kt-table-col {
        display: flex !important;
        justify-content: space-between !important;
        align-items: center !important;
        width: 100% !important;
    }
    .kt-table-col-sort {
        margin-left: auto !important;
    }
    
    /* Reset button visibility */
    a[title="Filters resetten"] {
        display: inline-flex !important;
        visibility: visible !important;
        opacity: 1 !important;
        min-width: 34px !important;
        height: 34px !important;
        align-items: center !important;
        justify-content: center !important;
        border: 1px solid var(--input) !important;
        background-color: var(--background) !important;
        color: var(--secondary-foreground) !important;
    }
    a[title="Filters resetten"]:hover {
        background-color: var(--accent) !important;
        color: var(--accent-foreground) !important;
    }
    a[title="Filters resetten"] i {
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
    }
    
    /* Table row hover styling */
    .match-row {
        cursor: pointer !important;
    }
    .match-row td:not(:last-child) {
        cursor: pointer !important;
        pointer-events: auto !important;
    }
    .match-row:hover {
        background-color: var(--muted) !important;
    }
    @supports (color: color-mix(in lab, red, red)) {
        .match-row:hover {
            background-color: color-mix(in oklab, var(--muted) 50%, transparent) !important;
        }
    }
</style>
@endpush

@push('scripts')
<script src="{{ asset('assets/js/search-input-clear.js') }}"></script>
<script>
    (function() {
        'use strict';
        
        let openDropdown = null;
        let closingTimeout = null;
        
        function closeAllDropdowns(exceptElement) {
            if (closingTimeout) {
                clearTimeout(closingTimeout);
                closingTimeout = null;
            }
            
            const displays = document.querySelectorAll('.kt-select-display');
            
            displays.forEach(function(display) {
                if (display === exceptElement) return;
                
                if (display.getAttribute('aria-expanded') === 'true') {
                    const select = display.parentElement?.querySelector('select.kt-select[data-kt-select="true"]');
                    if (select && typeof window.KTSelect !== 'undefined') {
                        try {
                            const instance = window.KTSelect.getInstance(select);
                            if (instance && instance.hide && typeof instance.hide === 'function') {
                                instance.hide();
                            }
                        } catch (e) {
                        }
                    }
                    
                    display.setAttribute('aria-expanded', 'false');
                    
                    const parent = display.closest('.kt-select-wrapper, [data-kt-select-wrapper]') || display.parentElement;
                    if (parent) {
                        const dropdowns = parent.querySelectorAll('.kt-menu-dropdown, .kt-select-dropdown, [data-kt-select-dropdown], [data-kt-menu-dropdown]');
                        dropdowns.forEach(function(dropdown) {
                            dropdown.style.display = 'none';
                            dropdown.style.visibility = 'hidden';
                            dropdown.style.opacity = '0';
                            dropdown.classList.remove('show', 'active', 'kt-menu-show');
                        });
                    }
                }
            });
        }
        
        function initSelectExclusive() {
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.addedNodes.length > 0) {
                        mutation.addedNodes.forEach(function(node) {
                            if (node.nodeType === 1) {
                                const isDropdown = node.classList && (
                                    node.classList.contains('kt-menu-dropdown') ||
                                    node.classList.contains('kt-select-dropdown') ||
                                    node.hasAttribute('data-kt-select-dropdown')
                                );
                                
                                if (isDropdown) {
                                    setTimeout(function() {
                                        const computedStyle = window.getComputedStyle(node);
                                        const isVisible = computedStyle.display !== 'none' && 
                                                         computedStyle.visibility !== 'hidden' && 
                                                         computedStyle.opacity !== '0';
                                        
                                        if (isVisible) {
                                            const allDisplays = document.querySelectorAll('.kt-select-display[aria-expanded="true"]');
                                            allDisplays.forEach(function(display) {
                                                const parent = display.closest('.kt-select-wrapper, [data-kt-select-wrapper]') || display.parentElement;
                                                const relatedDropdown = parent && parent.querySelector('.kt-menu-dropdown, .kt-select-dropdown, [data-kt-select-dropdown]');
                                                
                                                if (relatedDropdown !== node) {
                                                    closeAllDropdowns(display);
                                                } else {
                                                    openDropdown = display;
                                                }
                                            });
                                        }
                                    }, 50);
                                }
                            }
                        });
                    }
                });
            });
            
            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
            
            document.addEventListener('click', function(e) {
                const clickedSelect = e.target.closest('select.kt-select[data-kt-select="true"]');
                const clickedDisplay = e.target.closest('.kt-select-display');
                const clickedDropdown = e.target.closest('.kt-menu-dropdown, .kt-select-dropdown, [data-kt-select-dropdown]');
                const clickedOption = e.target.closest('.kt-menu-item, [data-kt-select-option]');
                
                if (clickedSelect || clickedDisplay || clickedDropdown || clickedOption) {
                    return;
                }
                
                closeAllDropdowns(null);
                openDropdown = null;
            });
        }
        
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                setTimeout(initSelectExclusive, 200);
            });
        } else {
            setTimeout(initSelectExclusive, 200);
        }
    })();
</script>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Voorkom dat de sidebar drawer sluit wanneer je binnen de content area klikt
    const contentArea = document.getElementById('content');
    const sidebar = document.getElementById('sidebar');
    
    if (contentArea && sidebar) {
        const observer = new MutationObserver(function(mutations) {
            const backdrop = document.querySelector('.kt-drawer-backdrop');
            if (backdrop) {
                backdrop.removeEventListener('click', preventBackdropClose);
                backdrop.addEventListener('click', preventBackdropClose, true);
            }
        });
        
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
        
        function preventBackdropClose(e) {
            if (contentArea.contains(e.target)) {
                e.preventDefault();
                e.stopImmediatePropagation();
                return false;
            }
        }
        
        setTimeout(function() {
            const backdrop = document.querySelector('.kt-drawer-backdrop');
            if (backdrop) {
                backdrop.addEventListener('click', preventBackdropClose, true);
            }
        }, 100);
    }
});
</script>
@endpush

@endsection
