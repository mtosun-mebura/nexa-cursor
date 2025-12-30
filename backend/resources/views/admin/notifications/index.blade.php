@extends('admin.layouts.app')

@section('title', 'Notificaties Beheer')

@section('content')

<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5">
        <h1 class="text-xl font-medium leading-none text-mono">
            Notificaties Beheer
        </h1>
        @can('create-notifications')
        <a href="{{ route('admin.notifications.create') }}" class="kt-btn kt-btn-primary">
            <i class="ki-filled ki-plus me-2"></i>
            Nieuwe Notificatie
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
                        {{ $stats['read'] ?? 0 }}
                    </span>
                    <span class="text-secondary-foreground text-sm">
                        Gelezen
                    </span>
                </div>
                <span class="hidden sm:block not-last:border-e border-e-input my-1"></span>
                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                    <span class="text-mono text-2xl lg:text-2xl leading-none font-semibold">
                        {{ $stats['unread'] ?? 0 }}
                    </span>
                    <span class="text-secondary-foreground text-sm">
                        Ongelezen
                    </span>
                </div>
                <span class="hidden sm:block not-last:border-e border-e-input my-1"></span>
                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                    <span class="text-mono text-2xl lg:text-2xl leading-none font-semibold">
                        {{ $stats['total_notifications'] ?? 0 }}
                    </span>
                    <span class="text-secondary-foreground text-sm">
                        Totaal Notificaties
                    </span>
                </div>
                <span class="hidden sm:block not-last:border-e border-e-input my-1"></span>
                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                    <span class="text-mono text-2xl lg:text-2xl leading-none font-semibold">
                        {{ $stats['unique_users'] ?? 0 }}
                    </span>
                    <span class="text-secondary-foreground text-sm">
                        Gebruikers
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="grid gap-5 lg:gap-7.5">
        <div class="kt-card kt-card-grid min-w-full">
            <div class="kt-card-header py-5 flex-wrap gap-2">
                <h3 class="kt-card-title text-sm pb-3 w-full">
                    Toon 1 tot {{ $notifications->count() }} van {{ $notifications->count() }} notificaties
                </h3>
                <div class="flex flex-col sm:flex-row flex-wrap gap-2 lg:gap-5 justify-center sm:justify-end items-center w-full">
                    <!-- Search -->
                    <div class="flex w-full sm:w-auto justify-center sm:justify-start">
                        <form method="GET" action="{{ route('admin.notifications.index') }}" class="flex gap-2" id="search-form">
                            @if(request('status'))
                                <input type="hidden" name="status" value="{{ request('status') }}">
                            @endif
                            @if(request('type'))
                                <input type="hidden" name="type" value="{{ request('type') }}">
                            @endif
                            @if(request('priority'))
                                <input type="hidden" name="priority" value="{{ request('priority') }}">
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
                                <input placeholder="Zoek notificaties..." 
                                       type="text" 
                                       name="search" 
                                       value="{{ request('search') }}"
                                       id="search-input"
                                       data-kt-datatable-search="#notifications_table"/>
                            </label>
                        </form>
                    </div>
                    <!-- Filters -->
                    <div class="flex flex-col sm:flex-row flex-wrap gap-2.5 items-center justify-center sm:justify-start w-full sm:w-auto">
                        <form method="GET" action="{{ route('admin.notifications.index') }}" id="filters-form" class="flex flex-col sm:flex-row gap-2.5 w-full sm:w-auto items-center sm:items-stretch">
                            @if(request('search'))
                                <input type="hidden" name="search" value="{{ request('search') }}">
                            @endif
                            
                            <select class="kt-select w-full sm:w-36" 
                                    name="status" 
                                    data-kt-select="true" 
                                    data-kt-select-placeholder="Status"
                                    id="status-filter">
                                <option value="">Alle statussen</option>
                                <option value="unread" {{ request('status') == 'unread' ? 'selected' : '' }}>Ongelezen</option>
                                <option value="read" {{ request('status') == 'read' ? 'selected' : '' }}>Gelezen</option>
                            </select>
                            
                            <select class="kt-select w-full sm:w-36" 
                                    name="type" 
                                    data-kt-select="true" 
                                    data-kt-select-placeholder="Type"
                                    id="type-filter">
                                <option value="">Alle types</option>
                                <option value="info" {{ request('type') == 'info' ? 'selected' : '' }}>Info</option>
                                <option value="warning" {{ request('type') == 'warning' ? 'selected' : '' }}>Waarschuwing</option>
                                <option value="error" {{ request('type') == 'error' ? 'selected' : '' }}>Fout</option>
                                <option value="success" {{ request('type') == 'success' ? 'selected' : '' }}>Succes</option>
                            </select>
                            
                            @if(auth()->user()->hasRole('super-admin'))
                            <select class="kt-select w-full sm:w-36" 
                                    name="priority" 
                                    data-kt-select="true" 
                                    data-kt-select-placeholder="Prioriteit"
                                    id="priority-filter">
                                <option value="">Alle prioriteiten</option>
                                <option value="low" {{ request('priority') == 'low' ? 'selected' : '' }}>Laag</option>
                                <option value="medium" {{ request('priority') == 'medium' ? 'selected' : '' }}>Gemiddeld</option>
                                <option value="high" {{ request('priority') == 'high' ? 'selected' : '' }}>Hoog</option>
                            </select>
                            @endif
                            
                            <select class="kt-select w-full sm:w-36" 
                                    name="sort" 
                                    data-kt-select="true" 
                                    data-kt-select-placeholder="Sortering"
                                    id="sort-filter">
                                <option value="" {{ !request('sort') ? 'selected' : '' }}>Geen sortering</option>
                                <option value="created_at" {{ request('sort') == 'created_at' ? 'selected' : '' }}>Datum</option>
                                <option value="type" {{ request('sort') == 'type' ? 'selected' : '' }}>Type</option>
                                <option value="status" {{ request('sort') == 'status' ? 'selected' : '' }}>Status</option>
                            </select>
                        </form>
                        @if(request('status') || request('type') || request('priority') || (request('sort') && request('sort') != 'created_at') || request('direction') || request('search'))
                        <a href="{{ route('admin.notifications.index') }}" 
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
                @if($notifications->count() > 0)
                    <div class="grid" data-kt-datatable="true" data-kt-datatable-page-size="10" id="notifications_table">
                        <div class="kt-scrollable-x-auto">
                            <table class="kt-table table-auto kt-table-border" data-kt-datatable-table="true">
                            <thead>
                                <tr>
                                    <th class="min-w-[250px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Gebruiker</span>
                                            <span class="kt-table-col-sort"></span>
                                        </span>
                                    </th>
                                    <th class="min-w-[300px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Inhoud</span>
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
                                @foreach($notifications as $notification)
                                    <tr class="notification-row" data-notification-id="{{ $notification->id }}">
                                        <td>
                                            @if($notification->user)
                                                <div class="flex flex-col">
                                                    <span class="text-sm font-medium text-mono mb-px">
                                                        {{ $notification->user->first_name }} {{ $notification->user->last_name }}
                                                    </span>
                                                    <a class="text-sm text-secondary-foreground font-normal hover:text-primary" href="mailto:{{ $notification->user->email }}">
                                                        {{ $notification->user->email }}
                                                    </a>
                                                </div>
                                            @else
                                                <span class="text-sm text-muted-foreground">Gebruiker niet gevonden</span>
                                            @endif
                                        </td>
                                        <td class="text-foreground font-normal">
                                            <div class="flex flex-col">
                                                @if($notification->title)
                                                    <span class="text-sm font-medium mb-1">{{ $notification->title }}</span>
                                                @endif
                                                <span class="text-sm text-secondary-foreground">{{ Str::limit($notification->message, 80) }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            @if($notification->read_at)
                                                <span class="kt-badge kt-badge-sm kt-badge-success">Gelezen</span>
                                            @else
                                                <span class="kt-badge kt-badge-sm kt-badge-warning">Ongelezen</span>
                                            @endif
                                        </td>
                                        <td class="text-foreground font-normal">
                                            <span class="text-sm">{{ $notification->created_at->format('d-m-Y H:i') }}</span>
                                        </td>
                                        <td class="w-[60px]" onclick="event.stopPropagation();">
                                            <div class="kt-menu flex justify-center" data-kt-menu="true">
                                                <div class="kt-menu-item" data-kt-menu-item-offset="0, 10px" data-kt-menu-item-placement="bottom-end" data-kt-menu-item-placement-rtl="bottom-start" data-kt-menu-item-toggle="dropdown" data-kt-menu-item-trigger="click">
                                                    <button class="kt-menu-toggle kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost">
                                                        <i class="ki-filled ki-dots-vertical text-lg"></i>
                                                    </button>
                                                    <div class="kt-menu-dropdown kt-menu-default w-full max-w-[175px]" data-kt-menu-dismiss="true">
                                                        @can('view-notifications')
                                                        <div class="kt-menu-item">
                                                            <a class="kt-menu-link" href="{{ route('admin.notifications.show', $notification) }}">
                                                                <span class="kt-menu-icon">
                                                                    <i class="ki-filled ki-eye"></i>
                                                                </span>
                                                                <span class="kt-menu-title">Bekijken</span>
                                                            </a>
                                                        </div>
                                                        @endcan
                                                        @can('edit-notifications')
                                                        <div class="kt-menu-item">
                                                            <a class="kt-menu-link" href="{{ route('admin.notifications.edit', $notification) }}">
                                                                <span class="kt-menu-icon">
                                                                    <i class="ki-filled ki-pencil"></i>
                                                                </span>
                                                                <span class="kt-menu-title">Bewerken</span>
                                                            </a>
                                                        </div>
                                                        @endcan
                                                        @if(auth()->user()->can('view-notifications') || auth()->user()->can('edit-notifications'))
                                                        <div class="kt-menu-separator"></div>
                                                        @endif
                                                        @can('delete-notifications')
                                                        <div class="kt-menu-item">
                                                            <form action="{{ route('admin.notifications.destroy', $notification) }}" 
                                                                  method="POST" 
                                                                  style="display: inline;"
                                                                  onsubmit="return confirm('Weet je zeker dat je deze notificatie wilt verwijderen?')">
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
                        <h4 class="text-lg font-semibold text-mono mb-2">Geen notificaties gevonden</h4>
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
        const typeFilter = document.getElementById('type-filter');
        const priorityFilter = document.getElementById('priority-filter');
        const sortFilter = document.getElementById('sort-filter');
        
        if (statusFilter && filterForm) {
            statusFilter.addEventListener('change', function() {
                filterForm.submit();
            });
        }
        
        if (typeFilter && filterForm) {
            typeFilter.addEventListener('change', function() {
                filterForm.submit();
            });
        }
        
        if (priorityFilter && filterForm) {
            priorityFilter.addEventListener('change', function() {
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
        document.querySelectorAll('tbody tr.notification-row').forEach(function(row) {
            row.addEventListener('click', function(e) {
                // Don't navigate if clicking on actions column or menu
                if (e.target.closest('td:last-child') || e.target.closest('.kt-menu') || e.target.closest('button') || e.target.closest('a')) {
                    return;
                }
                
                // Get notification ID
                const notificationId = this.getAttribute('data-notification-id');
                if (notificationId) {
                    window.location.href = '/admin/notifications/' + notificationId;
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
    .notification-row {
        cursor: pointer !important;
    }
    .notification-row:hover {
        background-color: var(--muted) !important;
    }
    @supports (color: color-mix(in lab, red, red)) {
        .notification-row:hover {
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
