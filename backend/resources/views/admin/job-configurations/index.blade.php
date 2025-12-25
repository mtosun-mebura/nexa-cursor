@extends('admin.layouts.app')

@section('title', 'Job Configuraties Beheer')

@section('content')

<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5">
        <h1 class="text-xl font-medium leading-none text-mono">
            Job Configuraties Beheer
        </h1>
        @if(auth()->user()->hasRole('super-admin') || auth()->user()->can('create-job-configurations'))
        <div class="flex gap-2.5">
            <a href="{{ route('admin.job-configuration-types.import') }}" class="kt-btn" style="background-color: #f97316; color: white; border-color: #f97316;">
                <x-heroicon-o-arrow-down-on-square-stack class="w-4 h-4 me-2" />
                JSON Importeren
            </a>
            <a href="{{ route('admin.job-configurations.create') }}" class="kt-btn kt-btn-primary">
                <i class="ki-filled ki-plus me-2"></i>
                Nieuwe Configuratie
            </a>
        </div>
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
                        {{ $stats['global'] ?? 0 }}
                    </span>
                    <span class="text-secondary-foreground text-sm">
                        Globaal
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="grid gap-5 lg:gap-7.5">
        <div class="kt-card kt-card-grid min-w-full">
            <div class="kt-card-header py-5 flex-wrap gap-2">
                <div class="flex flex-wrap gap-2 lg:gap-5 w-full items-center">
                    <!-- Bulk Delete Button (hidden by default, shown when items are selected) - Links uitgelijnd -->
                    @if(auth()->user()->hasRole('super-admin') || auth()->user()->can('delete-job-configurations'))
                    <form method="POST"
                          action="{{ route('admin.job-configurations.bulk-delete') }}"
                          id="bulk-delete-form"
                          style="display: none;">
                        @csrf
                        @method('DELETE')
                        <div id="selected-configurations-container"></div>
                        <button type="button"
                                class="kt-btn kt-btn-icon"
                                id="bulk-delete-btn"
                                title="Verwijder geselecteerde configuraties"
                                onclick="handleBulkDelete()"
                                style="display: none; background: transparent; border: none; color: #ef4444;">
                            <i class="ki-filled ki-trash" style="color: #ef4444;"></i>
                        </button>
                    </form>
                    @endif
                    <!-- Search and Filters - Rechts uitgelijnd -->
                    <div class="flex flex-wrap gap-2 lg:gap-5 items-center ml-auto">
                    <!-- Search -->
                    <div class="flex">
                        <form method="GET" action="{{ route('admin.job-configurations.index') }}" class="flex gap-2" id="search-form">
                            @if(request('type'))
                                <input type="hidden" name="type" value="{{ request('type') }}">
                            @endif
                            @if(request('sort'))
                                <input type="hidden" name="sort" value="{{ request('sort') }}">
                            @endif
                            @if(request('direction'))
                                <input type="hidden" name="direction" value="{{ request('direction') }}">
                            @endif
                            <label class="kt-input" style="position: relative !important;">
                                <i class="ki-filled ki-magnifier"></i>
                                <input placeholder="Zoek configuraties..." 
                                       type="text" 
                                       name="search" 
                                       value="{{ request('search') }}"
                                       id="search-input"
                                       data-kt-datatable-search="#configurations_table"/>
                            </label>
                        </form>
                    </div>
                    <!-- Filters -->
                    <div class="flex flex-wrap gap-2.5 items-center">
                        <form method="GET" action="{{ route('admin.job-configurations.index') }}" id="filters-form" class="flex gap-2.5">
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
                                    name="type" 
                                    data-kt-select="true" 
                                    data-kt-select-placeholder="Type"
                                    id="type-filter">
                                <option value="">Alle types</option>
                                @foreach($types ?? [] as $type)
                                    <option value="{{ $type->id }}" {{ request('type') == $type->id ? 'selected' : '' }}>
                                        {{ $type->display_name }}
                                    </option>
                                @endforeach
                            </select>
                            
                            @if(request('type') || request('search'))
                            <a href="{{ route('admin.job-configurations.index') }}" 
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
            </div>
            
            <div class="kt-card-content p-0">
                @if($configurations->count() > 0)
                    <div class="grid" data-kt-datatable="true" data-kt-datatable-page-size="10" id="configurations_table">
                        <div class="kt-scrollable-x-auto" style="overflow-x: auto; overflow-y: visible;">
                            <table class="kt-table table-auto kt-table-border" data-kt-datatable-table="true">
                            <thead>
                                <tr>
                                    @if(auth()->user()->hasRole('super-admin') || auth()->user()->can('delete-job-configurations'))
                                    <th class="w-[50px] text-center">
                                        <span class="kt-table-col">
                                            <label class="kt-label flex items-center justify-center cursor-pointer mt-1">
                                                <input type="checkbox"
                                                       class="kt-checkbox"
                                                       id="select-all-configurations"
                                                       title="Selecteer alle verwijderbare configuraties">
                                            </label>
                                        </span>
                                    </th>
                                    @endif
                                    <th class="min-w-[150px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Type</span>
                                        </span>
                                    </th>
                                    <th class="min-w-[200px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Waarde</span>
                                        </span>
                                    </th>
                                    <th class="min-w-[200px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Aangemaakt</span>
                                        </span>
                                    </th>
                                    <th class="w-[80px] text-center">Acties</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($configurations as $config)
                                <tr class="config-row">
                                    @if(auth()->user()->hasRole('super-admin') || auth()->user()->can('delete-job-configurations'))
                                    <td class="text-center">
                                        @if(!($config->in_use ?? false))
                                        <label class="kt-label flex items-center justify-center cursor-pointer">
                                            <input type="checkbox"
                                                   class="kt-checkbox config-checkbox"
                                                   name="config_ids[]"
                                                   value="{{ $config->id }}"
                                                   data-config-id="{{ $config->id }}"
                                                   data-in-use="false"
                                                   data-config-value="{{ $config->value }}">
                                        </label>
                                        @else
                                        <span class="text-muted-foreground text-xs" title="Niet verwijderbaar: in gebruik door {{ $config->usage_count ?? 0 }} vacature(s)">
                                            <i class="ki-filled ki-lock text-lg"></i>
                                        </span>
                                        @endif
                                    </td>
                                    @endif
                                    <td>
                                        <span class="kt-badge kt-badge-sm kt-badge-info">
                                            {{ $config->type_display }}
                                        </span>
                                    </td>
                                    <td class="text-foreground font-medium" data-config-id="{{ $config->id }}">
                                        {{ $config->value }}
                                    </td>
                                    <td class="text-secondary-foreground text-sm whitespace-nowrap">
                                        {{ $config->created_at->format('d-m-Y H:i') }}
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
                                                        <a class="kt-menu-link" href="{{ route('admin.job-configurations.show', $config) }}">
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
                                                        <a class="kt-menu-link" href="{{ route('admin.job-configurations.edit', $config) }}">
                                                            <span class="kt-menu-icon">
                                                                <i class="ki-filled ki-pencil"></i>
                                                            </span>
                                                            <span class="kt-menu-title">Bewerken</span>
                                                        </a>
                                                    </div>
                                                    @endif
                                                    @if(auth()->user()->hasRole('super-admin') || auth()->user()->can('delete-job-configurations'))
                                                    @if(auth()->user()->hasRole('super-admin') || auth()->user()->can('view-job-configurations') || auth()->user()->can('edit-job-configurations'))
                                                    <div class="kt-menu-separator"></div>
                                                    @endif
                                                    <div class="kt-menu-item">
                                                        <form action="{{ route('admin.job-configurations.destroy', $config) }}"
                                                              method="POST"
                                                              style="display: inline;"
                                                              onsubmit="return confirm('Weet je zeker dat je deze configuratie wilt verwijderen?')">
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
                    </div>
                @else
                    <div class="p-5 text-center">
                        <p class="text-muted-foreground">Geen configuraties gevonden.</p>
                        @if(auth()->user()->hasRole('super-admin') || auth()->user()->can('create-job-configurations'))
                        <a href="{{ route('admin.job-configurations.create') }}" class="kt-btn kt-btn-primary mt-3">
                            <i class="ki-filled ki-plus me-2"></i>
                            Eerste Configuratie Aanmaken
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
    const typeFilter = document.getElementById('type-filter');
    
    if (typeFilter) {
        typeFilter.addEventListener('change', function() {
            document.getElementById('filters-form').submit();
        });
    }
    
    // Search form submit on Enter (only if not using KT Datatable search)
    const searchInput = document.getElementById('search-input');
    if (searchInput && !searchInput.hasAttribute('data-kt-datatable-search')) {
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                document.getElementById('search-form').submit();
            }
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
    const configurationsTable = document.getElementById('configurations_table');
    if (configurationsTable) {
        const observer = new MutationObserver(function() {
            setTimeout(initializeMenus, 100);
        });
        observer.observe(configurationsTable, { childList: true, subtree: true });
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
    
    // Make table rows clickable (except actions column) - use event delegation
    const configurationsTableBody = configurationsTable ? configurationsTable.querySelector('tbody') : null;
    if (configurationsTableBody) {
        // Use event delegation on the tbody to handle dynamically added rows
        configurationsTableBody.addEventListener('click', function(e) {
            const row = e.target.closest('tr.config-row');
            if (!row) return;
            
            // Don't navigate if clicking on actions column or menu
            if (e.target.closest('td:last-child') || e.target.closest('.kt-menu') || e.target.closest('button') || e.target.closest('a')) {
                return;
            }
            
            // Don't navigate if clicking on checkbox or checkbox column
            if (e.target.closest('input.config-checkbox') || e.target.closest('label.kt-label') || e.target.closest('i.ki-filled.ki-lock')) {
                return;
            }
            
            // Get config ID from the value cell (adjust column index if checkbox column exists)
            const checkboxColumn = row.querySelector('td:first-child input.config-checkbox, td:first-child i.ki-filled.ki-lock');
            const valueCell = checkboxColumn 
                ? row.querySelector('td:nth-child(3)[data-config-id]') 
                : row.querySelector('td:nth-child(2)[data-config-id]');
            if (valueCell) {
                const configId = valueCell.getAttribute('data-config-id');
                if (configId) {
                    window.location.href = '/admin/job-configurations/' + configId;
                }
            }
        });
    }
    
    // Select All functionality - Wait for elements to be available
    function initSelectAll() {
        let selectAllCheckbox = document.getElementById('select-all-configurations');
        if (!selectAllCheckbox) {
            const tableHeader = document.querySelector('#configurations_table thead th');
            if (tableHeader) {
                selectAllCheckbox = tableHeader.querySelector('input#select-all-configurations');
            }
        }
        if (!selectAllCheckbox) {
            selectAllCheckbox = document.querySelector('#configurations_table input#select-all-configurations');
        }

        const bulkDeleteForm = document.getElementById('bulk-delete-form');
        const bulkDeleteBtn = document.getElementById('bulk-delete-btn');
        const selectedConfigurationsContainer = document.getElementById('selected-configurations-container');

        if (!selectAllCheckbox || !bulkDeleteBtn || !bulkDeleteForm || !selectedConfigurationsContainer) {
            if (typeof initSelectAll.retryCount === 'undefined') {
                initSelectAll.retryCount = 0;
            }
            initSelectAll.retryCount++;
            if (initSelectAll.retryCount < 20) {
                setTimeout(initSelectAll, 100);
            }
            return;
        }

        function getConfigCheckboxes() {
            return document.querySelectorAll('#configurations_table input.config-checkbox');
        }

        function updateBulkDeleteButton() {
            const checkboxes = getConfigCheckboxes();
            const selected = Array.from(checkboxes).filter(function(cb) { return cb.checked; });
            const selectedIds = selected.map(function(cb) { return cb.value; });

            if (selectedIds.length > 0) {
                bulkDeleteBtn.classList.add('show');
                bulkDeleteForm.classList.add('show');
                bulkDeleteBtn.style.display = 'inline-flex';
                bulkDeleteForm.style.display = 'block';
                bulkDeleteBtn.style.visibility = 'visible';
                bulkDeleteForm.style.visibility = 'visible';
                console.log('[Bulk Delete] Showing button');
            } else {
                bulkDeleteBtn.classList.remove('show');
                bulkDeleteForm.classList.remove('show');
                bulkDeleteBtn.style.display = 'none';
                bulkDeleteForm.style.display = 'none';
                bulkDeleteBtn.style.visibility = 'hidden';
                bulkDeleteForm.style.visibility = 'hidden';
                console.log('[Bulk Delete] Hiding button');
            }

            selectedConfigurationsContainer.innerHTML = '';
            selectedIds.forEach(function(id) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'configurations[]';
                input.value = id;
                selectedConfigurationsContainer.appendChild(input);
            });

            const visibleCheckboxes = Array.from(checkboxes).filter(cb => {
                const row = cb.closest('tr');
                return row && window.getComputedStyle(row).display !== 'none' && !cb.disabled;
            });

            if (visibleCheckboxes.length > 0) {
                const allChecked = visibleCheckboxes.every(cb => cb.checked);
                const someChecked = visibleCheckboxes.some(cb => cb.checked);
                selectAllCheckbox.checked = allChecked;
                selectAllCheckbox.indeterminate = someChecked && !allChecked;
            }
        }

        window.handleBulkDelete = function() {
            const checkboxes = getConfigCheckboxes();
            const selected = Array.from(checkboxes).filter(cb => cb.checked);

            if (selected.length === 0) {
                alert('Selecteer minimaal één configuratie om te verwijderen.');
                return;
            }

            const inUse = [];
            selected.forEach(checkbox => {
                const row = checkbox.closest('tr');
                if (row && row.querySelector('i.ki-filled.ki-lock')) {
                    inUse.push(checkbox.getAttribute('data-config-value') || 'Onbekend');
                }
            });

            if (inUse.length > 0) {
                alert('De volgende configuraties kunnen niet worden verwijderd omdat ze in gebruik zijn:\n\n' +
                      inUse.join('\n') +
                      '\n\nSelecteer alleen configuraties die niet in gebruik zijn.');
                return;
            }

            if (confirm('Weet je zeker dat je ' + selected.length + ' configuratie' +
                       (selected.length > 1 ? 's' : '') +
                       ' wilt verwijderen? Dit kan niet ongedaan worden gemaakt.')) {
                bulkDeleteForm.submit();
            }
        };

        const selectAllHandler = function(e) {
            let checkbox = document.getElementById('select-all-configurations');
            if (!checkbox) {
                checkbox = document.querySelector('#configurations_table input#select-all-configurations');
            }

            if (checkbox && (e.target === checkbox || e.target.id === 'select-all-configurations')) {
                e.stopPropagation();
                const isChecked = checkbox.checked;
                const checkboxes = getConfigCheckboxes();

                checkboxes.forEach(cb => {
                    if (!cb.disabled) {
                        cb.checked = isChecked;
                    }
                });

                updateBulkDeleteButton();
            }
        };

        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', selectAllHandler);
        }

        document.addEventListener('change', selectAllHandler);
        document.addEventListener('click', function(e) {
            if (e.target && e.target.id === 'select-all-configurations') {
                setTimeout(function() {
                    selectAllHandler(e);
                }, 10);
            }
        });

        document.addEventListener('change', function(e) {
            if (e.target && e.target.classList && e.target.classList.contains('config-checkbox')) {
                updateBulkDeleteButton();
            }
        });

        const configurationsTable = document.getElementById('configurations_table');
        if (configurationsTable) {
            const observer = new MutationObserver(function() {
                setTimeout(updateBulkDeleteButton, 50);
            });
            observer.observe(configurationsTable, { childList: true, subtree: true });
        }

        updateBulkDeleteButton();
    }

    setTimeout(initSelectAll, 500);
});
</script>
<script src="{{ asset('assets/js/search-input-clear.js') }}"></script>
@endpush

@push('styles')
<style>
    /* Checkbox styling */
    .config-checkbox,
    #select-all-configurations {
        width: 20px !important;
        height: 20px !important;
        min-width: 20px !important;
        min-height: 20px !important;
        border-width: 1px !important;
        border-color: #d1d5db !important;
        border-radius: 0.25rem !important;
        cursor: pointer !important;
        appearance: none !important;
        -webkit-appearance: none !important;
        -moz-appearance: none !important;
        background-color: transparent !important;
        transition: all 0.2s !important;
    }

    .config-checkbox:hover,
    #select-all-configurations:hover {
        border-color: #555555;
        background-color: rgba(85, 85, 85, 0.1);
    }

    .config-checkbox:checked,
    #select-all-configurations:checked {
        border-color: #10b981 !important;
        background-color: transparent !important;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='20' height='20' viewBox='0 0 20 20' fill='none'%3E%3Cpath fill-rule='evenodd' d='M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z' clip-rule='evenodd' fill='%2310b981'/%3E%3C/svg%3E") !important;
        background-position: center !important;
        background-repeat: no-repeat !important;
        background-size: contain !important;
    }

    .config-checkbox:focus-visible,
    #select-all-configurations:focus-visible {
        --tw-ring-color: #555555;
        --tw-ring-offset-width: 2px;
    }

    /* Bulk delete button - alleen rode prullenbak zonder achtergrond */
    #bulk-delete-btn {
        background: transparent !important;
        border: none !important;
        color: #ef4444 !important;
        padding: 0.5rem !important;
        margin-left: -0.5rem !important;
        display: none !important;
        visibility: hidden !important;
    }

    #bulk-delete-btn.show {
        display: inline-flex !important;
        visibility: visible !important;
    }

    #bulk-delete-form {
        display: none !important;
        visibility: hidden !important;
        overflow: visible !important;
    }

    #bulk-delete-form.show {
        display: block !important;
        visibility: visible !important;
        overflow: visible !important;
    }

    /* Ensure thead and th allow overflow */
    #configurations_table thead th:first-child {
        overflow: visible !important;
    }

    #configurations_table thead {
        overflow: visible !important;
    }

    #configurations_table .kt-scrollable-x-auto {
        overflow-x: auto !important;
        overflow-y: visible !important;
    }


    #bulk-delete-btn:hover {
        background: rgba(239, 68, 68, 0.1) !important;
        border-radius: 0.375rem !important;
    }

    #bulk-delete-btn i {
        color: #ef4444 !important;
        width: 24px !important;
        height: 24px !important;
        font-size: 24px !important;
    }

    /* Ensure dropdown can overflow table cells without stretching them */
    #configurations_table td:last-child {
        position: relative;
        overflow: visible !important;
        width: 100px !important;
        min-width: 100px !important;
        max-width: 100px !important;
    }

    #configurations_table td:last-child .kt-menu-item {
        position: static;
    }

    #configurations_table td:last-child .kt-menu-dropdown {
        position: fixed !important;
        z-index: 99999 !important;
    }
    
    #configurations_table td:last-child .kt-menu-item.show {
        z-index: 99999 !important;
    }
    
    #configurations_table td:last-child .kt-menu-item.show .kt-menu-dropdown {
        z-index: 99999 !important;
    }
    
    /* Table row hover styling */
    .config-row {
        cursor: pointer !important;
    }
    .config-row:hover {
        background-color: var(--muted) !important;
    }
    @supports (color: color-mix(in lab, red, red)) {
        .config-row:hover {
            background-color: color-mix(in oklab, var(--muted) 50%, transparent) !important;
        }
    }
</style>
@endpush

@endsection

