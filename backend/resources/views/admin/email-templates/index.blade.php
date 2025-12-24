@extends('admin.layouts.app')

@section('title', 'E-mail Templates Beheer')

@section('content')

<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5">
        <h1 class="text-xl font-medium leading-none text-mono">
            E-mail Templates Beheer
        </h1>
        @can('create-email-templates')
        <a href="{{ route('admin.email-templates.create') }}" class="kt-btn kt-btn-primary">
            <i class="ki-filled ki-plus me-2"></i>
            Nieuw Template
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
            <div class="flex lg:px-10 py-1.5 gap-2">
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
                <span class="not-last:border-e border-e-input my-1"></span>
                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                    <span class="text-mono text-2xl lg:text-2xl leading-none font-semibold">
                        {{ $stats['total_templates'] ?? 0 }}
                    </span>
                    <span class="text-secondary-foreground text-sm">
                        Totaal Templates
                    </span>
                </div>
                <span class="not-last:border-e border-e-input my-1"></span>
                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                    <span class="text-mono text-2xl lg:text-2xl leading-none font-semibold">
                        {{ $stats['unique_types'] ?? 0 }}
                    </span>
                    <span class="text-secondary-foreground text-sm">
                        Types
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="grid gap-5 lg:gap-7.5">
        <div class="kt-card kt-card-grid min-w-full">
            <div class="kt-card-header py-5 flex-wrap gap-2">
                <h3 class="kt-card-title text-sm pb-3 w-full">
                    Toon 1 tot {{ $emailTemplates->count() }} van {{ $emailTemplates->count() }} templates
                </h3>
                <div class="flex flex-wrap gap-2 lg:gap-5 justify-end w-full">
                    <!-- Search -->
                    <div class="flex">
                        <form method="GET" action="{{ route('admin.email-templates.index') }}" class="flex gap-2" id="search-form">
                            @if(request('type'))
                                <input type="hidden" name="type" value="{{ request('type') }}">
                            @endif
                            @if(request('status'))
                                <input type="hidden" name="status" value="{{ request('status') }}">
                            @endif
                            @if(request('company'))
                                <input type="hidden" name="company" value="{{ request('company') }}">
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
                            <label class="kt-input w-64" style="position: relative !important;">
                                <i class="ki-filled ki-magnifier"></i>
                                <input placeholder="Zoek templates..." 
                                       type="text" 
                                       name="search" 
                                       value="{{ request('search') }}"
                                       id="search-input"
                                       data-kt-datatable-search="#email_templates_table"/>
                            </label>
                        </form>
                    </div>
                    <!-- Filters -->
                    <div class="flex flex-wrap gap-2.5 items-center">
                        <form method="GET" action="{{ route('admin.email-templates.index') }}" id="filters-form" class="flex gap-2.5">
                            @if(request('search'))
                                <input type="hidden" name="search" value="{{ request('search') }}">
                            @endif
                            
                            <select class="kt-select w-36" 
                                    name="type" 
                                    data-kt-select="true" 
                                    data-kt-select-placeholder="Type"
                                    id="type-filter">
                                <option value="">Alle types</option>
                                <option value="welcome" {{ request('type') == 'welcome' ? 'selected' : '' }}>Welkom</option>
                                <option value="notification" {{ request('type') == 'notification' ? 'selected' : '' }}>Notificatie</option>
                                <option value="reminder" {{ request('type') == 'reminder' ? 'selected' : '' }}>Herinnering</option>
                                <option value="confirmation" {{ request('type') == 'confirmation' ? 'selected' : '' }}>Bevestiging</option>
                            </select>
                            
                            @if(auth()->user()->hasRole('super-admin') && $companies->count() > 0)
                            <select class="kt-select w-36" 
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
                            
                            <select class="kt-select w-36" 
                                    name="status" 
                                    data-kt-select="true" 
                                    data-kt-select-placeholder="Status"
                                    id="status-filter">
                                <option value="">Alle statussen</option>
                                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Actief</option>
                                <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactief</option>
                            </select>
                            
                            <select class="kt-select w-36" 
                                    name="sort" 
                                    data-kt-select="true" 
                                    data-kt-select-placeholder="Sortering"
                                    id="sort-filter">
                                <option value="" {{ !request('sort') ? 'selected' : '' }}>Geen sortering</option>
                                <option value="created_at" {{ request('sort') == 'created_at' ? 'selected' : '' }}>Datum</option>
                                <option value="name" {{ request('sort') == 'name' ? 'selected' : '' }}>Naam</option>
                                <option value="type" {{ request('sort') == 'type' ? 'selected' : '' }}>Type</option>
                            </select>
                        </form>
                        @if(request('type') || request('company') || request('status') || (request('sort') && request('sort') != 'created_at') || request('direction') || request('search'))
                        <a href="{{ route('admin.email-templates.index') }}" 
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
                @if($emailTemplates->count() > 0)
                    <div class="grid" data-kt-datatable="true" data-kt-datatable-page-size="10" id="email_templates_table">
                        <div class="kt-scrollable-x-auto">
                            <table class="kt-table table-auto kt-table-border" data-kt-datatable-table="true">
                            <thead>
                                <tr>
                                    <th class="min-w-[300px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Template & Details</span>
                                            <span class="kt-table-col-sort">
                                                @php
                                                    $currentSort = request('sort');
                                                    $currentDirection = request('direction');
                                                    if ($currentSort == 'name') {
                                                        $nextDirection = ($currentDirection == 'asc') ? 'desc' : 'asc';
                                                    } else {
                                                        $nextDirection = 'asc';
                                                    }
                                                @endphp
                                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'name', 'direction' => $nextDirection]) }}" 
                                                   class="kt-table-col-sort-btn"></a>
                                            </span>
                                        </span>
                                    </th>
                                    <th class="min-w-[150px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Type</span>
                                            <span class="kt-table-col-sort">
                                                @php
                                                    $currentSort = request('sort');
                                                    $currentDirection = request('direction');
                                                    if ($currentSort == 'type') {
                                                        $nextDirection = ($currentDirection == 'asc') ? 'desc' : 'asc';
                                                    } else {
                                                        $nextDirection = 'asc';
                                                    }
                                                @endphp
                                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'type', 'direction' => $nextDirection]) }}" 
                                                   class="kt-table-col-sort-btn"></a>
                                            </span>
                                        </span>
                                    </th>
                                    <th class="min-w-[150px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Bedrijf</span>
                                            <span class="kt-table-col-sort"></span>
                                        </span>
                                    </th>
                                    <th class="min-w-[120px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Status</span>
                                            <span class="kt-table-col-sort"></span>
                                        </span>
                                    </th>
                                    <th class="min-w-[150px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Gemaakt op</span>
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
                                @foreach($emailTemplates as $template)
                                    <tr class="template-row" data-template-id="{{ $template->id }}">
                                        <td>
                                            <div class="flex flex-col">
                                                <a class="text-sm font-medium text-mono hover:text-primary mb-px" href="{{ route('admin.email-templates.show', $template) }}">
                                                    {{ $template->name }}
                                                </a>
                                                @if($template->description)
                                                    <span class="text-xs text-muted-foreground">{{ Str::limit($template->description, 60) }}</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="text-foreground font-normal">
                                            <span class="text-sm">{{ ucfirst($template->type) }}</span>
                                        </td>
                                        <td class="text-foreground font-normal">
                                            @if($template->company)
                                                <span class="text-sm">{{ $template->company->name }}</span>
                                            @else
                                                <span class="text-sm text-muted-foreground">Algemeen</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($template->is_active)
                                                <span class="kt-badge kt-badge-sm kt-badge-success">Actief</span>
                                            @else
                                                <span class="kt-badge kt-badge-sm kt-badge-danger">Inactief</span>
                                            @endif
                                        </td>
                                        <td class="text-foreground font-normal">
                                            <span class="text-sm">{{ $template->created_at->format('d-m-Y') }}</span>
                                        </td>
                                        <td class="w-[60px]" onclick="event.stopPropagation();">
                                            <div class="kt-menu flex justify-center" data-kt-menu="true">
                                                <div class="kt-menu-item" data-kt-menu-item-offset="0, 10px" data-kt-menu-item-placement="bottom-end" data-kt-menu-item-placement-rtl="bottom-start" data-kt-menu-item-toggle="dropdown" data-kt-menu-item-trigger="click">
                                                    <button class="kt-menu-toggle kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost">
                                                        <i class="ki-filled ki-dots-vertical text-lg"></i>
                                                    </button>
                                                    <div class="kt-menu-dropdown kt-menu-default w-full max-w-[175px]" data-kt-menu-dismiss="true">
                                                        @can('view-email-templates')
                                                        <div class="kt-menu-item">
                                                            <a class="kt-menu-link" href="{{ route('admin.email-templates.show', $template) }}">
                                                                <span class="kt-menu-icon">
                                                                    <i class="ki-filled ki-eye"></i>
                                                                </span>
                                                                <span class="kt-menu-title">Bekijken</span>
                                                            </a>
                                                        </div>
                                                        @endcan
                                                        @can('edit-email-templates')
                                                        <div class="kt-menu-item">
                                                            <a class="kt-menu-link" href="{{ route('admin.email-templates.edit', $template) }}">
                                                                <span class="kt-menu-icon">
                                                                    <i class="ki-filled ki-pencil"></i>
                                                                </span>
                                                                <span class="kt-menu-title">Bewerken</span>
                                                            </a>
                                                        </div>
                                                        @endcan
                                                        @if(auth()->user()->can('view-email-templates') || auth()->user()->can('edit-email-templates'))
                                                        <div class="kt-menu-separator"></div>
                                                        @endif
                                                        @can('delete-email-templates')
                                                        <div class="kt-menu-item">
                                                            <form action="{{ route('admin.email-templates.destroy', $template) }}" 
                                                                  method="POST" 
                                                                  style="display: inline;"
                                                                  onsubmit="return confirm('Weet je zeker dat je dit template wilt verwijderen?')">
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
                        <h4 class="text-lg font-semibold text-mono mb-2">Geen e-mail templates gevonden</h4>
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
        const typeFilter = document.getElementById('type-filter');
        const companyFilter = document.getElementById('company-filter');
        const statusFilter = document.getElementById('status-filter');
        const sortFilter = document.getElementById('sort-filter');
        
        if (typeFilter && filterForm) {
            typeFilter.addEventListener('change', function() {
                filterForm.submit();
            });
        }
        
        if (companyFilter && filterForm) {
            companyFilter.addEventListener('change', function() {
                filterForm.submit();
            });
        }
        
        if (statusFilter && filterForm) {
            statusFilter.addEventListener('change', function() {
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
        
        // Make table rows clickable (except actions column)
        document.querySelectorAll('tbody tr.template-row').forEach(function(row) {
            row.addEventListener('click', function(e) {
                // Don't navigate if clicking on actions column or menu
                if (e.target.closest('td:last-child') || e.target.closest('.kt-menu') || e.target.closest('button') || e.target.closest('a')) {
                    return;
                }
                
                // Get template ID
                const templateId = this.getAttribute('data-template-id');
                if (templateId) {
                    window.location.href = '/admin/email-templates/' + templateId;
                }
            });
        });
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
    .template-row {
        cursor: pointer !important;
    }
    .template-row:hover {
        background-color: var(--muted) !important;
    }
    @supports (color: color-mix(in lab, red, red)) {
        .template-row:hover {
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
