@extends('admin.layouts.app')

@section('title', 'Interviews Beheer')

@section('content')

<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5">
        <h1 class="text-xl font-medium leading-none text-mono">
            Interviews Beheer
        </h1>
        @can('create-interviews')
        <a href="{{ route('admin.interviews.create') }}" class="kt-btn kt-btn-primary">
            <i class="ki-filled ki-plus me-2"></i>
            Nieuw Interview
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
                        {{ $stats['scheduled'] ?? 0 }}
                    </span>
                    <span class="text-secondary-foreground text-sm">
                        Gepland
                    </span>
                </div>
                <span class="hidden sm:block not-last:border-e border-e-input my-1"></span>
                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                    <span class="text-mono text-2xl lg:text-2xl leading-none font-semibold">
                        {{ $stats['past'] ?? 0 }}
                    </span>
                    <span class="text-secondary-foreground text-sm">
                        Afgelopen
                    </span>
                </div>
                <span class="hidden sm:block not-last:border-e border-e-input my-1"></span>
                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                    <span class="text-mono text-2xl lg:text-2xl leading-none font-semibold">
                        {{ $stats['not_scheduled'] ?? 0 }}
                    </span>
                    <span class="text-secondary-foreground text-sm">
                        Niet Gepland
                    </span>
                </div>
                <span class="hidden sm:block not-last:border-e border-e-input my-1"></span>
                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                    <span class="text-mono text-2xl lg:text-2xl leading-none font-semibold">
                        {{ $stats['total_interviews'] ?? 0 }}
                    </span>
                    <span class="text-secondary-foreground text-sm">
                        Totaal Interviews
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="grid gap-5 lg:gap-7.5">
        <div class="kt-card kt-card-grid min-w-full">
            <div class="kt-card-header py-5 flex-wrap gap-2">
                <h3 class="kt-card-title text-sm pb-3 w-full">
                    Toon 1 tot {{ $interviews->count() }} van {{ $interviews->count() }} interviews
                </h3>
                <div class="flex flex-col sm:flex-row flex-wrap gap-2 lg:gap-5 justify-center sm:justify-end items-center w-full">
                    <!-- Search -->
                    <div class="flex w-full sm:w-auto justify-center sm:justify-start">
                        <form method="GET" action="{{ route('admin.interviews.index') }}" class="flex gap-2" id="search-form">
                            @if(request('status'))
                                <input type="hidden" name="status" value="{{ request('status') }}">
                            @endif
                            @if(request('company'))
                                <input type="hidden" name="company" value="{{ request('company') }}">
                            @endif
                            @if(request('type'))
                                <input type="hidden" name="type" value="{{ request('type') }}">
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
                                <input placeholder="Zoek interviews..." 
                                       type="text" 
                                       name="search" 
                                       value="{{ request('search') }}"
                                       id="search-input"
                                       data-kt-datatable-search="#interviews_table"/>
                            </label>
                        </form>
                    </div>
                    <!-- Filters -->
                    <div class="flex flex-col sm:flex-row flex-wrap gap-2.5 items-center justify-center sm:justify-start w-full sm:w-auto">
                        <form method="GET" action="{{ route('admin.interviews.index') }}" id="filters-form" class="flex flex-col sm:flex-row gap-2.5 w-full sm:w-auto items-center sm:items-stretch">
                            @if(request('search'))
                                <input type="hidden" name="search" value="{{ request('search') }}">
                            @endif
                            
                            <select class="kt-select w-full sm:w-36" 
                                    name="status" 
                                    data-kt-select="true" 
                                    data-kt-select-placeholder="Status"
                                    id="status-filter">
                                <option value="">Alle statussen</option>
                                <option value="scheduled" {{ request('status') == 'scheduled' ? 'selected' : '' }}>Ingepland</option>
                                <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Voltooid</option>
                                <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Geannuleerd</option>
                            </select>
                            
                            @if(auth()->user()->hasRole('super-admin') && $companies->count() > 0)
                            <select class="kt-select w-full sm:w-36" 
                                    name="company" 
                                    data-kt-select="true" 
                                    data-kt-select-placeholder="Bedrijf"
                                    id="company-filter">
                                <option value="">Alle bedrijven</option>
                                @foreach($companies as $company)
                                    <option value="{{ $company->id }}" {{ request('company') == $company->id ? 'selected' : '' }}>
                                        {{ $company->name }}
                                    </option>
                                @endforeach
                            </select>
                            @endif
                            
                            <select class="kt-select w-full sm:w-36" 
                                    name="type" 
                                    data-kt-select="true" 
                                    data-kt-select-placeholder="Type"
                                    id="type-filter">
                                <option value="">Alle types</option>
                                <option value="phone" {{ request('type') == 'phone' ? 'selected' : '' }}>Telefoon</option>
                                <option value="video" {{ request('type') == 'video' ? 'selected' : '' }}>Video</option>
                                <option value="in_person" {{ request('type') == 'in_person' ? 'selected' : '' }}>Persoonlijk</option>
                            </select>
                            
                            <select class="kt-select w-full sm:w-36" 
                                    name="sort" 
                                    data-kt-select="true" 
                                    data-kt-select-placeholder="Sortering"
                                    id="sort-filter">
                                <option value="" {{ !request('sort') ? 'selected' : '' }}>Geen sortering</option>
                                <option value="created_at" {{ request('sort') == 'created_at' ? 'selected' : '' }}>Datum</option>
                                <option value="scheduled_at" {{ request('sort') == 'scheduled_at' ? 'selected' : '' }}>Gepland op</option>
                                <option value="status" {{ request('sort') == 'status' ? 'selected' : '' }}>Status</option>
                            </select>
                        </form>
                        @if(request('status') || request('company') || request('type') || (request('sort') && request('sort') != 'created_at') || request('direction') || request('search'))
                        <a href="{{ route('admin.interviews.index') }}" 
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
                @if($interviews->count() > 0)
                    <div class="grid" data-kt-datatable="true" data-kt-datatable-page-size="10" id="interviews_table">
                        <div class="kt-scrollable-x-auto">
                            <table class="kt-table table-auto kt-table-border" data-kt-datatable-table="true">
                            <thead>
                                <tr>
                                    <th class="min-w-[250px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Kandidaat</span>
                                            <span class="kt-table-col-sort"></span>
                                        </span>
                                    </th>
                                    <th class="min-w-[200px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Vacature</span>
                                            <span class="kt-table-col-sort"></span>
                                        </span>
                                    </th>
                                    <th class="min-w-[150px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Bedrijf</span>
                                            <span class="kt-table-col-sort"></span>
                                        </span>
                                    </th>
                                    <th class="min-w-[150px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Gepland op</span>
                                            <span class="kt-table-col-sort">
                                                @php
                                                    $currentSort = request('sort');
                                                    $currentDirection = request('direction');
                                                    if ($currentSort == 'scheduled_at') {
                                                        $nextDirection = ($currentDirection == 'desc') ? 'asc' : 'desc';
                                                    } else {
                                                        $nextDirection = 'desc';
                                                    }
                                                @endphp
                                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'scheduled_at', 'direction' => $nextDirection]) }}" 
                                                   class="kt-table-col-sort-btn"></a>
                                            </span>
                                        </span>
                                    </th>
                                    <th class="min-w-[150px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Locatie</span>
                                            <span class="kt-table-col-sort"></span>
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
                                    <th class="w-[60px] text-center">Acties</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($interviews as $interview)
                                    <tr class="interview-row" data-interview-id="{{ $interview->id }}">
                                        <td>
                                            @if($interview->match && $interview->match->candidate)
                                                <div class="flex flex-col">
                                                    <span class="text-sm font-medium text-mono mb-px">
                                                        {{ $interview->match->candidate->first_name }} {{ $interview->match->candidate->last_name }} (K)
                                                    </span>
                                                    <span class="text-sm text-secondary-foreground font-normal">
                                                        {{ $interview->match->candidate->email }}
                                                    </span>
                                                </div>
                                            @else
                                                <span class="text-sm text-muted-foreground">Kandidaat niet gevonden</span>
                                            @endif
                                        </td>
                                        <td class="text-foreground font-normal">
                                            @if($interview->match && $interview->match->vacancy)
                                                <span class="text-sm font-medium">{{ $interview->match->vacancy->title }}</span>
                                            @else
                                                <span class="text-sm text-muted-foreground">Vacature niet gevonden</span>
                                            @endif
                                        </td>
                                        <td class="text-foreground font-normal">
                                            @if($interview->company)
                                                <span class="text-sm">{{ $interview->company->name }}</span>
                                            @else
                                                <span class="text-sm text-muted-foreground">Geen bedrijf</span>
                                            @endif
                                        </td>
                                        <td class="text-foreground font-normal">
                                            @if($interview->scheduled_at)
                                                <div class="flex flex-col">
                                                    <span class="text-sm font-medium">{{ \Carbon\Carbon::parse($interview->scheduled_at)->format('d-m-Y') }}</span>
                                                    <span class="text-xs text-muted-foreground">{{ \Carbon\Carbon::parse($interview->scheduled_at)->format('H:i') }}</span>
                                                </div>
                                            @else
                                                <span class="text-sm text-muted-foreground">Niet gepland</span>
                                            @endif
                                        </td>
                                        <td class="text-foreground font-normal">
                                            @if($interview->location)
                                                <span class="text-sm">{{ $interview->location }}</span>
                                            @else
                                                <span class="text-sm text-muted-foreground">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @php
                                                $statusMap = [
                                                    'scheduled' => 'Gepland',
                                                    'confirmed' => 'Bevestigd',
                                                    'completed' => 'Voltooid',
                                                    'cancelled' => 'Geannuleerd',
                                                    'rescheduled' => 'Herpland',
                                                ];
                                                $statusLabel = $statusMap[$interview->status] ?? 'Onbekend';
                                                $badgeClass = 'warning';
                                                if ($interview->status == 'scheduled') {
                                                    $badgeClass = 'info';
                                                } elseif ($interview->status == 'confirmed') {
                                                    $badgeClass = 'warning';
                                                } elseif ($interview->status == 'completed') {
                                                    $badgeClass = 'success';
                                                } elseif ($interview->status == 'cancelled') {
                                                    $badgeClass = 'danger';
                                                } elseif ($interview->status == 'rescheduled') {
                                                    $badgeClass = 'info';
                                                }
                                            @endphp
                                            <span class="kt-badge kt-badge-sm kt-badge-{{ $badgeClass }}">
                                                {{ $statusLabel }}
                                            </span>
                                        </td>
                                        <td class="w-[60px]" onclick="event.stopPropagation();">
                                            <div class="kt-menu flex justify-center" data-kt-menu="true">
                                                <div class="kt-menu-item" data-kt-menu-item-offset="0, 10px" data-kt-menu-item-placement="bottom-end" data-kt-menu-item-placement-rtl="bottom-start" data-kt-menu-item-toggle="dropdown" data-kt-menu-item-trigger="click">
                                                    <button class="kt-menu-toggle kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost">
                                                        <i class="ki-filled ki-dots-vertical text-lg"></i>
                                                    </button>
                                                    <div class="kt-menu-dropdown kt-menu-default w-full max-w-[175px]" data-kt-menu-dismiss="true">
                                                        @can('view-interviews')
                                                        <div class="kt-menu-item">
                                                            <a class="kt-menu-link" href="{{ route('admin.interviews.show', $interview) }}">
                                                                <span class="kt-menu-icon">
                                                                    <i class="ki-filled ki-eye"></i>
                                                                </span>
                                                                <span class="kt-menu-title">Bekijken</span>
                                                            </a>
                                                        </div>
                                                        @endcan
                                                        @can('edit-interviews')
                                                        <div class="kt-menu-item">
                                                            <a class="kt-menu-link" href="{{ route('admin.interviews.edit', $interview) }}">
                                                                <span class="kt-menu-icon">
                                                                    <i class="ki-filled ki-pencil"></i>
                                                                </span>
                                                                <span class="kt-menu-title">Bewerken</span>
                                                            </a>
                                                        </div>
                                                        @endcan
                                                        @if(auth()->user()->can('view-interviews') || auth()->user()->can('edit-interviews'))
                                                        <div class="kt-menu-separator"></div>
                                                        @endif
                                                        @can('delete-interviews')
                                                        <div class="kt-menu-item">
                                                            <form action="{{ route('admin.interviews.destroy', $interview) }}" 
                                                                  method="POST" 
                                                                  style="display: inline;"
                                                                  onsubmit="return confirm('Weet je zeker dat je dit interview wilt verwijderen?')">
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
                        <h4 class="text-lg font-semibold text-mono mb-2">Geen interviews gevonden</h4>
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
        const typeFilter = document.getElementById('type-filter');
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
        
        if (typeFilter && filterForm) {
            typeFilter.addEventListener('change', function() {
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
        
        // Make table rows clickable - use both event delegation and direct handlers
        let rowClickHandler = null;
        const attachedRows = new WeakSet();
        
        function setupRowClicks() {
            const interviewsTable = document.getElementById('interviews_table');
            if (!interviewsTable) {
                return;
            }
            
            // Try to find tbody - check multiple possible locations
            let tbody = interviewsTable.querySelector('table tbody');
            if (!tbody) {
                tbody = interviewsTable.querySelector('tbody');
            }
            if (!tbody) {
                tbody = document.querySelector('[data-kt-datatable-table="true"] tbody');
            }
            
            if (!tbody) {
                return;
            }
            
            // Remove existing handler if it exists (for event delegation)
            if (rowClickHandler) {
                tbody.removeEventListener('click', rowClickHandler, true);
            }
            
            // Create event delegation handler
            rowClickHandler = function(e) {
                const row = e.target.closest('tr.interview-row');
                if (!row) {
                    return;
                }
                
                // Don't navigate if clicking on actions column or menu
                const clickedElement = e.target;
                const actionsTd = row.querySelector('td:last-child');
                const isInActionsColumn = actionsTd && (actionsTd.contains(clickedElement) || clickedElement === actionsTd);
                const isInMenu = clickedElement.closest('.kt-menu') || clickedElement.closest('[data-kt-menu]');
                const isButton = clickedElement.tagName === 'BUTTON' || clickedElement.closest('button');
                const isLink = clickedElement.tagName === 'A' || clickedElement.closest('a');
                
                if (isInActionsColumn || isInMenu || isButton || isLink) {
                    return;
                }
                
                // Get interview ID - try multiple methods since datatable might remove data attributes
                let interviewId = row.getAttribute('data-interview-id');
                
                // If not found, try to get it from the "Bekijken" link in the actions menu
                if (!interviewId || interviewId === 'null' || interviewId === '') {
                    const viewLink = row.querySelector('a[href*="/admin/interviews/"]');
                    if (viewLink) {
                        const href = viewLink.getAttribute('href');
                        const match = href.match(/\/admin\/interviews\/(\d+)/);
                        if (match && match[1]) {
                            interviewId = match[1];
                        }
                    }
                }
                
                if (interviewId && interviewId !== 'null' && interviewId !== '' && interviewId !== null && interviewId !== undefined) {
                    e.stopPropagation();
                    e.stopImmediatePropagation();
                    e.preventDefault();
                    window.location.href = '/admin/interviews/' + interviewId;
                }
            };
            
            // Add event delegation listener
            tbody.addEventListener('click', rowClickHandler, true);
            
            // Also attach direct handlers to each row (backup method)
            const rows = tbody.querySelectorAll('tr.interview-row');
            
            rows.forEach(function(row, index) {
                // Skip if already attached
                if (attachedRows.has(row)) {
                    return;
                }
                
                // Get interview ID - try multiple methods
                let interviewId = row.getAttribute('data-interview-id');
                
                // If not found, try to get it from the "Bekijken" link
                if (!interviewId || interviewId === 'null' || interviewId === '') {
                    const viewLink = row.querySelector('a[href*="/admin/interviews/"]');
                    if (viewLink) {
                        const href = viewLink.getAttribute('href');
                        const match = href.match(/\/admin\/interviews\/(\d+)/);
                        if (match && match[1]) {
                            interviewId = match[1];
                            // Store it in the row for future use
                            row.setAttribute('data-interview-id', interviewId);
                        }
                    }
                }
                
                row.style.cursor = 'pointer';
                attachedRows.add(row);
                
                // Store interview ID in closure for this row
                const rowInterviewId = interviewId;
                
                // Add direct click handler as backup
                row.addEventListener('click', function(e) {
                    // Don't navigate if clicking on actions column or menu
                    const clickedElement = e.target;
                    const actionsTd = this.querySelector('td:last-child');
                    const isInActionsColumn = actionsTd && (actionsTd.contains(clickedElement) || clickedElement === actionsTd);
                    const isInMenu = clickedElement.closest('.kt-menu') || clickedElement.closest('[data-kt-menu]');
                    const isButton = clickedElement.tagName === 'BUTTON' || clickedElement.closest('button');
                    const isLink = clickedElement.tagName === 'A' || clickedElement.closest('a');
                    
                    if (isInActionsColumn || isInMenu || isButton || isLink) {
                        return;
                    }
                    
                    // Use stored ID or try to get it again
                    let interviewId = rowInterviewId || this.getAttribute('data-interview-id');
                    if (!interviewId || interviewId === 'null' || interviewId === '') {
                        const viewLink = this.querySelector('a[href*="/admin/interviews/"]');
                        if (viewLink) {
                            const href = viewLink.getAttribute('href');
                            const match = href.match(/\/admin\/interviews\/(\d+)/);
                            if (match && match[1]) {
                                interviewId = match[1];
                            }
                        }
                    }
                    
                    if (interviewId && interviewId !== 'null' && interviewId !== '' && interviewId !== null && interviewId !== undefined) {
                        e.stopPropagation();
                        e.stopImmediatePropagation();
                        e.preventDefault();
                        window.location.href = '/admin/interviews/' + interviewId;
                    }
                }, true);
            });
        }
        
        // Try immediately
        setupRowClicks();
        
        // Also try after delays in case datatable initializes later
        setTimeout(setupRowClicks, 100);
        setTimeout(setupRowClicks, 500);
        setTimeout(setupRowClicks, 1000);
        
        // Watch for table changes
        const interviewsTable = document.getElementById('interviews_table');
        if (interviewsTable) {
            const observer = new MutationObserver(function() {
                setupRowClicks();
            });
            observer.observe(interviewsTable, { childList: true, subtree: true });
        }
    });
</script>
@endpush

@push('styles')
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
    .interview-row {
        cursor: pointer !important;
    }
    .interview-row:hover {
        background-color: var(--muted) !important;
    }
    @supports (color: color-mix(in lab, red, red)) {
        .interview-row:hover {
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
