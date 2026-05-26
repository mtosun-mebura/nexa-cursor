@extends('admin.layouts.app')

@section('title', 'Job Configuratie Types Beheer')

@push('styles')
<style>
    /* Ensure dropdown can overflow table cells without stretching them */
    #types_table td:last-child {
        position: relative;
        overflow: visible !important;
        width: 100px !important;
        min-width: 100px !important;
        max-width: 100px !important;
    }

    #types_table td:last-child .kt-menu-item {
        position: static;
    }

    #types_table td:last-child .kt-menu-dropdown {
        position: fixed !important;
        z-index: 99999 !important;
    }
    
    #types_table td:last-child .kt-menu-item.show {
        z-index: 99999 !important;
    }
    
    #types_table td:last-child .kt-menu-item.show .kt-menu-dropdown {
        z-index: 99999 !important;
    }
    
    /* Table row hover styling */
    .type-row {
        cursor: pointer !important;
    }
    .type-row:hover {
        background-color: var(--muted) !important;
    }
    @supports (color: color-mix(in lab, red, red)) {
        .type-row:hover {
            background-color: color-mix(in oklab, var(--muted) 50%, transparent) !important;
        }
    }
</style>
@endpush

@section('content')

<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5">
        <h1 class="text-xl font-medium leading-none text-mono">
            Job Configuratie Types Beheer
        </h1>
        @if(auth()->user()->hasRole('super-admin') || auth()->user()->can('create-job-configurations'))
        <a href="{{ route('admin.job-configuration-types.create') }}" class="kt-btn kt-btn-primary">
            <i class="ki-filled ki-plus me-2"></i>
            Nieuw Type
        </a>
        @endif
    </div>

    <!-- Success/Error Alerts -->
    @if(session('success'))
        <div class="kt-alert kt-alert-success mb-5" id="success-alert" role="alert">
            <i class="ki-filled ki-check-circle me-2"></i>
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="kt-alert kt-alert-danger mb-5" id="error-alert" role="alert">
            <i class="ki-filled ki-cross-circle me-2"></i>
            {{ session('error') }}
        </div>
    @endif

    <!-- Statistics Cards -->
    <div class="kt-card mb-5">
        <div class="kt-card-content">
            <div class="flex lg:px-10 py-1.5 gap-2">
                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                    <span class="text-mono text-2xl lg:text-2xl leading-none font-semibold">
                        {{ $stats['total'] ?? 0 }}
                    </span>
                    <span class="text-secondary-foreground text-sm">
                        Totaal
                    </span>
                </div>
                <span class="not-last:border-e border-e-input my-1"></span>
                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                    <span class="text-mono text-2xl lg:text-2xl leading-none font-semibold">
                        {{ $stats['active'] ?? 0 }}
                    </span>
                    <span class="text-secondary-foreground text-sm">
                        Actief
                    </span>
                </div>
                <span class="not-last:border-e border-e-input my-1"></span>
                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                    <span class="text-mono text-2xl lg:text-2xl leading-none font-semibold">
                        {{ $stats['inactive'] ?? 0 }}
                    </span>
                    <span class="text-secondary-foreground text-sm">
                        Inactief
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="grid gap-5 lg:gap-7.5">
        <div class="kt-card kt-card-grid min-w-full">
            <div class="kt-card-header py-5 flex-wrap gap-2">
                <h3 class="kt-card-title text-sm pb-3 w-full">
                    <span data-kt-datatable-info="true">Toon 1 tot {{ $types->count() }} van {{ $types->count() }} types</span>
                </h3>
                <div class="flex flex-wrap gap-2 lg:gap-5 justify-end w-full">
                    <!-- Search -->
                    <div class="flex">
                        <form method="GET" action="{{ route('admin.job-configuration-types.index') }}" class="flex gap-2" id="search-form">
                            @if(request('is_active'))
                                <input type="hidden" name="is_active" value="{{ request('is_active') }}">
                            @endif
                            @if(request('sort'))
                                <input type="hidden" name="sort" value="{{ request('sort') }}">
                            @endif
                            @if(request('direction'))
                                <input type="hidden" name="direction" value="{{ request('direction') }}">
                            @endif
                            <label class="kt-input" style="position: relative !important;">
                                <i class="ki-filled ki-magnifier"></i>
                                <input placeholder="Zoek types..." 
                                       type="text" 
                                       name="search" 
                                       value="{{ request('search') }}"
                                       id="search-input"
                                       data-kt-datatable-search="#types_table"/>
                            </label>
                        </form>
                    </div>
                    <!-- Filters -->
                    <div class="flex flex-wrap gap-2.5 items-center">
                        <form method="GET" action="{{ route('admin.job-configuration-types.index') }}" id="filters-form" class="flex gap-2.5">
                            @if(request('search'))
                                <input type="hidden" name="search" value="{{ request('search') }}">
                            @endif
                            @if(request('sort'))
                                <input type="hidden" name="sort" value="{{ request('sort') }}">
                            @endif
                            @if(request('direction'))
                                <input type="hidden" name="direction" value="{{ request('direction') }}">
                            @endif
                            
                            <select class="kt-select w-36" 
                                    name="is_active" 
                                    data-kt-select="true" 
                                    data-kt-select-placeholder="Status"
                                    id="status-filter">
                                <option value="">Alle statussen</option>
                                <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Actief</option>
                                <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Inactief</option>
                            </select>
                            
                            @if(request('is_active') || request('search'))
                            <a href="{{ route('admin.job-configuration-types.index') }}" 
                               class="kt-btn kt-btn-outline kt-btn-icon" 
                               title="Filters resetten"
                               id="reset-filter-btn"
                               style="display: inline-flex !important; visibility: visible !important; opacity: 1 !important; min-width: 34px !important; height: 34px !important; align-items: center !important; justify-content: center !important; border: 1px solid var(--input) !important; background-color: var(--background) !important; color: var(--secondary-foreground) !important; position: relative !important; z-index: 1 !important;">
                                <i class="ki-filled ki-arrows-circle text-base" style="display: block !important; visibility: visible !important; opacity: 1 !important; font-size: 1rem !important;"></i>
                            </a>
                            @endif
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="kt-card-content">
                @if($types->count() > 0)
                    <div class="grid" data-kt-datatable="true" data-kt-datatable-page-size="10" id="types_table">
                        <div class="kt-scrollable-x-auto">
                            <table class="kt-table table-auto kt-table-border" data-kt-datatable-table="true">
                            <thead>
                                <tr>
                                    <th class="min-w-[200px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Weergave Naam</span>
                                        </span>
                                    </th>
                                    <th class="min-w-[150px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Naam</span>
                                        </span>
                                    </th>
                                    <th class="min-w-[200px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Beschrijving</span>
                                        </span>
                                    </th>
                                    <th class="min-w-[100px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Configuraties</span>
                                        </span>
                                    </th>
                                    <th class="min-w-[100px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Status</span>
                                        </span>
                                    </th>
                                    <th class="min-w-[100px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Volgorde</span>
                                        </span>
                                    </th>
                                    <th class="w-[100px] text-center">Acties</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($types as $type)
                                <tr class="type-row">
                                    <td class="text-foreground font-medium">
                                        {{ $type->display_name }}
                                    </td>
                                    <td>
                                        <code class="text-sm" data-type-id="{{ $type->id }}">{{ $type->name }}</code>
                                    </td>
                                    <td class="text-secondary-foreground text-sm">
                                        {{ $type->description ?: '-' }}
                                    </td>
                                    <td>
                                        <span class="kt-badge kt-badge-sm kt-badge-info">
                                            {{ $type->job_configurations_count ?? 0 }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($type->is_active)
                                            <span class="kt-badge kt-badge-sm kt-badge-success">Actief</span>
                                        @else
                                            <span class="kt-badge kt-badge-sm kt-badge-danger">Inactief</span>
                                        @endif
                                    </td>
                                    <td class="text-secondary-foreground text-sm">
                                        {{ $type->sort_order }}
                                    </td>
                                    <td class="text-center">
                                        @if(auth()->user()->hasRole('super-admin') || auth()->user()->can('view-job-configurations') || auth()->user()->can('edit-job-configurations') || auth()->user()->can('delete-job-configurations'))
                                        <div class="kt-menu flex justify-center" data-kt-menu="true">
                                            <div class="kt-menu-item"
                                                 data-kt-menu-item-offset="0, 10px"
                                                 data-kt-menu-item-placement="bottom-end"
                                                 data-kt-menu-item-placement-rtl="bottom-start"
                                                 data-kt-menu-item-toggle="dropdown"
                                                 data-kt-menu-item-trigger="click">
                                                <button class="kt-menu-toggle kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" type="button" aria-label="Acties">
                                                    <i class="ki-filled ki-dots-vertical text-lg"></i>
                                                </button>
                                                <div class="kt-menu-dropdown kt-menu-default w-full max-w-[175px]" data-kt-menu-dismiss="true">
                                                    @if(auth()->user()->hasRole('super-admin') || auth()->user()->can('view-job-configurations'))
                                                    <div class="kt-menu-item">
                                                        <a class="kt-menu-link" href="{{ route('admin.job-configuration-types.show', $type) }}">
                                                            <span class="kt-menu-icon">
                                                                <i class="ki-filled ki-eye"></i>
                                                            </span>
                                                            <span class="kt-menu-title">Bekijken</span>
                                                        </a>
                                                    </div>
                                                    @endif
                                                    @if(auth()->user()->hasRole('super-admin') || auth()->user()->can('edit-job-configurations'))
                                                    @if(auth()->user()->hasRole('super-admin') || auth()->user()->can('view-job-configurations'))
                                                    <div class="kt-menu-separator"></div>
                                                    @endif
                                                    <div class="kt-menu-item">
                                                        <a class="kt-menu-link" href="{{ route('admin.job-configuration-types.edit', $type) }}">
                                                            <span class="kt-menu-icon">
                                                                <i class="ki-filled ki-pencil"></i>
                                                            </span>
                                                            <span class="kt-menu-title">Bewerken</span>
                                                        </a>
                                                    </div>
                                                    @endif
                                                    @if(auth()->user()->hasRole('super-admin') || auth()->user()->can('edit-job-configurations'))
                                                    @if(auth()->user()->hasRole('super-admin') || auth()->user()->can('view-job-configurations') || auth()->user()->can('edit-job-configurations'))
                                                    <div class="kt-menu-separator"></div>
                                                    @endif
                                                    @php
                                                        $configurationsCount = $type->job_configurations_count ?? 0;
                                                        $canDeactivate = $configurationsCount == 0;
                                                    @endphp
                                                    @if(!$type->is_active || $canDeactivate)
                                                    <div class="kt-menu-item">
                                                        <form action="{{ route('admin.job-configuration-types.toggle-status', $type) }}"
                                                              method="POST"
                                                              style="display: inline;"
                                                              onsubmit="return confirm('Weet je zeker dat je de status wilt wijzigen?')">
                                                            @csrf
                                                            <button type="submit" class="kt-menu-link w-full text-left">
                                                                <span class="kt-menu-icon">
                                                                    <i class="ki-filled {{ $type->is_active ? 'ki-cross-circle' : 'ki-check-circle' }}"></i>
                                                                </span>
                                                                <span class="kt-menu-title">{{ $type->is_active ? 'Deactiveren' : 'Activeren' }}</span>
                                                            </button>
                                                        </form>
                                                    </div>
                                                    @else
                                                    <div class="kt-menu-item">
                                                        <span class="kt-menu-link w-full text-left opacity-50 cursor-not-allowed" title="Kan niet worden gedeactiveerd omdat het in gebruik is door {{ $configurationsCount }} configuratie(s)">
                                                            <span class="kt-menu-icon">
                                                                <i class="ki-filled ki-cross-circle"></i>
                                                            </span>
                                                            <span class="kt-menu-title">Deactiveren (in gebruik)</span>
                                                        </span>
                                                    </div>
                                                    @endif
                                                    @endif
                                                    @if(auth()->user()->hasRole('super-admin') || auth()->user()->can('delete-job-configurations'))
                                                    @if(auth()->user()->hasRole('super-admin') || auth()->user()->can('view-job-configurations') || auth()->user()->can('edit-job-configurations'))
                                                    <div class="kt-menu-separator"></div>
                                                    @endif
                                                    <div class="kt-menu-item">
                                                        <form action="{{ route('admin.job-configuration-types.destroy', $type) }}"
                                                              method="POST"
                                                              style="display: inline;"
                                                              onsubmit="return confirm('Weet je zeker dat je dit type wilt verwijderen?')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="kt-menu-link w-full text-left">
                                                                <span class="kt-menu-icon">
                                                                    <i class="ki-filled ki-trash"></i>
                                                                </span>
                                                                <span class="kt-menu-title">Verwijderen</span>
                                                            </button>
                                                        </form>
                                                    </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                        </div>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="kt-card-footer justify-center md:justify-between flex-col md:flex-row gap-5 text-secondary-foreground text-sm font-medium">
                        <div class="flex items-center gap-2 order-2 md:order-1">
                            Toon
                            <select class="kt-select w-24" data-kt-datatable-size="true" data-kt-select="" name="perpage">
                                <option value="10" selected>10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
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
                @else
                    <div class="p-5 text-center">
                        <p class="text-muted-foreground">Geen types gevonden.</p>
                        @if(auth()->user()->hasRole('super-admin') || auth()->user()->can('create-job-configurations'))
                        <a href="{{ route('admin.job-configuration-types.create') }}" class="kt-btn kt-btn-primary mt-3">
                            <i class="ki-filled ki-plus me-2"></i>
                            Eerste Type Aanmaken
                        </a>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-submit filters on change
    const statusFilter = document.getElementById('status-filter');
    
    if (statusFilter) {
        statusFilter.addEventListener('change', function() {
            document.getElementById('filters-form').submit();
        });
    }
    
    // Initialize KTMenu for action menus
    function initializeMenus() {
        if (window.KTMenu && typeof window.KTMenu.init === 'function') {
            try {
                window.KTMenu.init();
            } catch (e) {
                console.warn('KTMenu init error:', e);
            }
        } else {
            // Retry if KTMenu not loaded yet
            setTimeout(initializeMenus, 100);
        }
    }
    
    // Initialize immediately and after a short delay
    initializeMenus();
    setTimeout(initializeMenus, 300);
    
    // Also initialize when table content changes (for pagination)
    const typesTable = document.getElementById('types_table');
    if (typesTable) {
        const observer = new MutationObserver(function() {
            setTimeout(initializeMenus, 100);
        });
        observer.observe(typesTable, { childList: true, subtree: true });
    }
    
    // Position dropdown using fixed positioning to avoid stacking context issues
    document.addEventListener('click', function(e) {
        const toggle = e.target.closest('.kt-menu-toggle');
        if (toggle) {
            const menuItem = toggle.closest('.kt-menu-item');
            if (menuItem) {
                const dropdown = menuItem.querySelector('.kt-menu-dropdown');
                if (dropdown) {
                    setTimeout(function() {
                        const buttonRect = toggle.getBoundingClientRect();
                        dropdown.style.position = 'fixed';
                        dropdown.style.left = (buttonRect.right - 175) + 'px';
                        dropdown.style.top = (buttonRect.bottom + 5) + 'px';
                        dropdown.style.right = 'auto';
                        dropdown.style.minWidth = '175px';
                        dropdown.style.width = '175px';
                        dropdown.style.zIndex = '99999';
                    }, 10);
                }
            }
        }
    });
    
    // Replace "of" with "van" in pagination info
    function replaceOfWithVan() {
        const infoSpan = document.querySelector('[data-kt-datatable-info="true"]');
        if (infoSpan && infoSpan.textContent.includes(' of ')) {
            infoSpan.textContent = infoSpan.textContent.replace(' of ', ' van ');
        }
    }
    
    // Run after datatable is initialized
    setTimeout(replaceOfWithVan, 500);
    setTimeout(replaceOfWithVan, 1000);
    
    // Also watch for changes
    if (typesTable) {
        const paginationObserver = new MutationObserver(function() {
            replaceOfWithVan();
        });
        paginationObserver.observe(typesTable, { childList: true, subtree: true });
    }
    
    // Make table rows clickable (except actions column) - robust event delegation
    function makeRowsClickable() {
        if (!typesTable) {
            return;
        }
        
        // Remove existing handler if it exists
        if (typesTable._rowClickHandler) {
            typesTable.removeEventListener('click', typesTable._rowClickHandler, true);
        }
        
        // Create robust click handler
        typesTable._rowClickHandler = function(e) {
            const row = e.target.closest('tr.type-row');
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
            
            // Get type ID - try multiple methods
            let typeId = null;
            
            // Method 1: Try data attribute on row
            typeId = row.getAttribute('data-type-id');
            
            // Method 2: Try name code element
            if (!typeId || typeId === 'null' || typeId === '') {
                const nameCode = row.querySelector('td:nth-child(2) code[data-type-id]');
                if (nameCode) {
                    typeId = nameCode.getAttribute('data-type-id');
                }
            }
            
            // Method 3: Try to extract from any link in the row
            if (!typeId || typeId === 'null' || typeId === '') {
                const viewLink = row.querySelector('a[href*="/admin/job-configuration-types/"]');
                if (viewLink) {
                    const href = viewLink.getAttribute('href');
                    const match = href.match(/\/admin\/job-configuration-types\/(\d+)/);
                    if (match && match[1]) {
                        typeId = match[1];
                    }
                }
            }
            
            if (typeId && typeId !== 'null' && typeId !== '' && typeId !== null && typeId !== undefined) {
                e.stopPropagation();
                e.stopImmediatePropagation();
                e.preventDefault();
                window.location.href = '/admin/job-configuration-types/' + typeId;
            }
        };
        
        // Add event listener with capture phase on container
        typesTable.addEventListener('click', typesTable._rowClickHandler, true);
    }
    
    // Initialize row click handlers
    makeRowsClickable();
    
    // Re-initialize after delays in case datatable initializes later
    setTimeout(makeRowsClickable, 100);
    setTimeout(makeRowsClickable, 500);
    setTimeout(makeRowsClickable, 1000);
    
    // Re-initialize when table content changes (for pagination/search)
    if (typesTable) {
        const rowObserver = new MutationObserver(function() {
            makeRowsClickable();
        });
        rowObserver.observe(typesTable, { childList: true, subtree: true });
    }
});
</script>
<script src="{{ asset('assets/js/search-input-clear.js') }}"></script>
@endpush

@endsection

