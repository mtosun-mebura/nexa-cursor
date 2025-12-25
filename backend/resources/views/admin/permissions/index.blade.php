@extends('admin.layouts.app')

@section('title', 'Permissies Beheer')

@push('scripts')
<script>
// Prevent sidebar accordion from closing when clicking Bulk Bewerken button
(function() {
    // Initialize global flag
    window.isBulkEditClick = false;
    
    // Track when Bulk Bewerken button is clicked - only handle Bulk Bewerken specifically
    // Don't interfere with any other clicks
    document.addEventListener('DOMContentLoaded', function() {
        // Only add handler to Bulk Bewerken button specifically, not all clicks
        const bulkEditButton = document.querySelector('button.kt-btn-primary');
        if (bulkEditButton && bulkEditButton.textContent.includes('Bulk Bewerken')) {
            bulkEditButton.addEventListener('click', function(e) {
                window.isBulkEditClick = true;
                setTimeout(function() {
                    window.isBulkEditClick = false;
                }, 300);
                
                // Prevent sidebar accordion from closing
                const sidebarAccordion = document.querySelector('#sidebar_menu .kt-menu-item[data-kt-menu-item-toggle="accordion"]');
                if (sidebarAccordion && sidebarAccordion.classList.contains('show')) {
                    // Use MutationObserver to restore show class if removed
                    const observer = new MutationObserver(function(mutations) {
                        mutations.forEach(function(mutation) {
                            if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                                if (!sidebarAccordion.classList.contains('show') && mutation.oldValue && mutation.oldValue.includes('show')) {
                                    sidebarAccordion.classList.add('show');
                                    const accordionContent = sidebarAccordion.querySelector('.kt-menu-accordion');
                                    if (accordionContent) {
                                        accordionContent.classList.add('show');
                                    }
                                }
                            }
                        });
                    });
                    
                    observer.observe(sidebarAccordion, {
                        attributes: true,
                        attributeFilter: ['class'],
                        attributeOldValue: true
                    });
                    
                    // Stop observing after a short delay
                    setTimeout(function() {
                        observer.disconnect();
                    }, 500);
                }
            });
        }
    });
})();
</script>
@endpush

@push('styles')
<style>
    /* Badge styling wordt nu globaal gedefinieerd in admin/layouts/app.blade.php */

    /* Table column sorting - align arrows to the right */
    .kt-table-col {
        display: flex !important;
        justify-content: space-between !important;
        align-items: center !important;
        width: 100% !important;
    }
    .kt-table-col-sort {
        margin-left: auto !important;
    }

    /* Ensure dropdown can overflow table cells without stretching them */
    #permissions_table td:last-child {
        position: relative;
        overflow: visible !important;
        width: 60px !important;
        min-width: 60px !important;
        max-width: 60px !important;
    }

    #permissions_table td:last-child .kt-menu-item {
        position: static;
    }

    #permissions_table td:last-child .kt-menu-dropdown {
        position: fixed !important;
        z-index: 99999 !important;
    }
    
    #permissions_table td:last-child .kt-menu-item.show {
        z-index: 99999 !important;
    }
    
    #permissions_table td:last-child .kt-menu-item.show .kt-menu-dropdown {
        z-index: 99999 !important;
    }

    /* Prevent table rows from stretching when dropdown is open */
    #permissions_table tbody tr {
        height: auto !important;
        min-height: auto !important;
    }

    /* Checkbox styling voor permissions tabel */
    .permission-checkbox,
    #select-all-permissions {
        width: 20px !important;
        height: 20px !important;
        min-width: 20px !important;
        min-height: 20px !important;
        border-width: 1px !important;
        border-color: #555555;
        color: #555555;
        padding-right: 0 !important;
    }

    .permission-checkbox:hover,
    #select-all-permissions:hover {
        border-color: #555555;
        background-color: rgba(85, 85, 85, 0.1);
    }

    .permission-checkbox:checked,
    #select-all-permissions:checked {
        border-color: #10b981 !important;
        background-color: transparent !important;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='20' height='20' viewBox='0 0 20 20' fill='none'%3E%3Cpath fill-rule='evenodd' d='M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z' clip-rule='evenodd' fill='%2310b981'/%3E%3C/svg%3E") !important;
        background-position: center !important;
        background-repeat: no-repeat !important;
        background-size: 20px 20px !important;
        border-width: 1px !important;
        color: #10b981 !important;
    }

    .permission-checkbox:focus-visible,
    #select-all-permissions:focus-visible {
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
</style>
@endpush

@section('content')

<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5">
        <h1 class="text-xl font-medium leading-none text-mono">
            Permissies Beheer
        </h1>
        <div class="flex items-center gap-2.5">
            @if(auth()->user()->hasRole('super-admin') || auth()->user()->can('create-permissions'))
            <a href="{{ route('admin.permissions.create') }}" class="kt-btn kt-btn-primary">
                <i class="ki-filled ki-plus me-2"></i>
                Permissie toevoegen
            </a>
            <a href="{{ route('admin.permissions.bulk-create') }}" class="kt-btn kt-btn-success">
                <i class="ki-filled ki-plus me-2"></i>
                Bulk Aanmaken
            </a>
            @endif
            @if(auth()->user()->hasRole('super-admin') || auth()->user()->can('edit-permissions'))
            <div class="kt-menu flex" data-kt-menu="true">
                <div class="kt-menu-item" data-kt-menu-item-toggle="dropdown" data-kt-menu-item-trigger="click">
                    <button type="button" class="kt-btn kt-btn-primary">
                        <i class="ki-filled ki-pencil me-2"></i>
                        Bulk Bewerken
                        <i class="ki-filled ki-down ms-2 text-xs"></i>
                    </button>
                    <div class="kt-menu-dropdown kt-menu-default w-full max-w-[250px]">
                        <div class="kt-menu-item">
                            <a href="{{ route('admin.permissions.bulk-edit') }}" class="kt-menu-link">
                                <span class="kt-menu-title">Alle Permissies</span>
                            </a>
                        </div>
                        <div class="kt-menu-separator"></div>
                        @php
                            // Get unique modules for dropdown
                            $modulesFromGroup = \Spatie\Permission\Models\Permission::where('guard_name', 'web')
                                ->whereNotNull('group')
                                ->distinct()
                                ->pluck('group')
                                ->unique()
                                ->sort()
                                ->values();

                            // Also get modules from permission names
                            $permissionsForModules = \Spatie\Permission\Models\Permission::where('guard_name', 'web')
                                ->pluck('name');

                            $modulesFromName = collect();
                            foreach ($permissionsForModules as $permissionName) {
                                $parts = explode('-', $permissionName);
                                if (count($parts) > 1) {
                                    array_shift($parts);
                                    $module = implode('-', $parts);
                                    if ($module) {
                                        $modulesFromName->push($module);
                                    }
                                }
                            }

                            $allModulesForDropdown = $modulesFromGroup->merge($modulesFromName)
                                ->unique()
                                ->sort()
                                ->values();

                            // Base module display names mapping
                            $baseModuleNames = [
                                'users' => 'Gebruikers',
                                'vacancies' => 'Vacatures',
                                'matches' => 'Matches',
                                'interviews' => 'Interviews',
                                'notifications' => 'Notificaties',
                                'email-templates' => 'E-mail Templates',
                                'tenant-dashboard' => 'Tenant Dashboard',
                                'agenda' => 'Agenda',
                                'companies' => 'Bedrijven',
                                'branches' => 'Branches',
                                'roles' => 'Rollen',
                                'permissions' => 'Permissies',
                                'dashboard' => 'Dashboard',
                                'settings' => 'Configuraties',
                                'instellingen' => 'Configuraties',
                                'configuraties' => 'Configuraties',
                                'job-configurations' => 'Job Configuraties',
                                'job_configurations' => 'Job Configuraties',
                            ];
                        @endphp
                        @foreach($allModulesForDropdown as $module)
                        <div class="kt-menu-item">
                            <a href="{{ route('admin.permissions.bulk-edit', ['module' => $module]) }}" class="kt-menu-link">
                                <span class="kt-menu-title">{{ $baseModuleNames[$module] ?? ucfirst(str_replace(['-', '_'], ' ', $module)) }}</span>
                            </a>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>

    <!-- Success Alert -->
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
                        {{ $stats['total_permissions'] ?? 0 }}
                    </span>
                    <span class="text-secondary-foreground text-sm">
                        Totaal Permissies
                    </span>
                </div>
                <span class="not-last:border-e border-e-input my-1"></span>
                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                    <span class="text-mono text-2xl lg:text-2xl leading-none font-semibold">
                        {{ $stats['assigned_permissions'] ?? 0 }}
                    </span>
                    <span class="text-secondary-foreground text-sm">
                        Toegewezen
                    </span>
                </div>
                <span class="not-last:border-e border-e-input my-1"></span>
                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                    <span class="text-mono text-2xl lg:text-2xl leading-none font-semibold">
                        {{ $stats['unassigned_permissions'] ?? 0 }}
                    </span>
                    <span class="text-secondary-foreground text-sm">
                        Niet Toegewezen
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="grid gap-5 lg:gap-7.5">
        <div class="kt-card kt-card-grid min-w-full">
            <div class="kt-card-header py-5 flex-wrap gap-2">
                <h3 class="kt-card-title text-sm pb-3 w-full">
                    Toon 1 tot {{ $allPermissions->count() }} van {{ $allPermissions->count() }} permissie(s)
                </h3>
                <div class="flex flex-wrap gap-2 lg:gap-5 w-full items-center">
                    <!-- Bulk Delete Button (hidden by default, shown when items are selected) - Links uitgelijnd -->
                    @if(auth()->user()->hasRole('super-admin') || auth()->user()->can('delete-permissions'))
                    <form method="POST"
                          action="{{ route('admin.permissions.bulk-delete') }}"
                          id="bulk-delete-form"
                          style="display: none;">
                        @csrf
                        @method('DELETE')
                        <div id="selected-permissions-container"></div>
                        <button type="button"
                                class="kt-btn kt-btn-icon"
                                id="bulk-delete-btn"
                                title="Verwijder geselecteerde permissies"
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
                        <form method="GET" action="{{ route('admin.permissions.index') }}" class="flex gap-2" id="search-form">
                            @if(request('module'))
                                <input type="hidden" name="module" value="{{ request('module') }}">
                            @endif
                            @if(request('assigned'))
                                <input type="hidden" name="assigned" value="{{ request('assigned') }}">
                            @endif
                            @if(request('sort'))
                                <input type="hidden" name="sort" value="{{ request('sort') }}">
                            @endif
                            @if(request('direction'))
                                <input type="hidden" name="direction" value="{{ request('direction') }}">
                            @endif
                            <label class="kt-input w-64" style="position: relative !important;">
                                <i class="ki-filled ki-magnifier"></i>
                                <input placeholder="Zoek permissies..."
                                       type="text"
                                       name="search"
                                       value="{{ request('search') }}"
                                       id="search-input"
                                       data-kt-datatable-search="#permissions_table"/>
                            </label>
                        </form>
                    </div>
                    <!-- Filters -->
                    <div class="flex flex-wrap gap-2.5 items-center">
                        <form method="GET" action="{{ route('admin.permissions.index') }}" id="filters-form" class="flex gap-2.5">
                            @if(request('search'))
                                <input type="hidden" name="search" value="{{ request('search') }}">
                            @endif

                            @php
                                $moduleNames = [
                                    'users' => 'Gebruikers',
                                    'vacancies' => 'Vacatures',
                                    'matches' => 'Matches',
                                    'interviews' => 'Interviews',
                                    'notifications' => 'Notificaties',
                                    'email-templates' => 'E-mail Templates',
                                    'email_templates' => 'E-mail Templates',
                                    'tenant-dashboard' => 'Tenant Dashboard',
                                    'tenant_dashboard' => 'Tenant Dashboard',
                                    'agenda' => 'Agenda',
                                    'companies' => 'Bedrijven',
                                    'branches' => 'Branches',
                                    'categories' => 'Categorieën',
                                'roles' => 'Rollen en Permissies',
                                'permissions' => 'Permissies',
                                'dashboard' => 'Dashboard',
                                'settings' => 'Configuraties',
                                'instellingen' => 'Configuraties',
                                'configuraties' => 'Configuraties',
                                'job-configurations' => 'Job Configuraties',
                                'job_configurations' => 'Job Configuraties',
                            ];
                            @endphp

                            <select class="kt-select w-36"
                                    name="module"
                                    data-kt-select="true"
                                    data-kt-select-placeholder="Module"
                                    id="module-filter">
                                <option value="">Alle modules</option>
                                @foreach($modules as $module)
                                    <option value="{{ $module }}" {{ request('module') == $module ? 'selected' : '' }}>
                                        {{ $moduleNames[$module] ?? ucfirst(str_replace(['-', '_'], ' ', $module)) }}
                                    </option>
                                @endforeach
                            </select>

                            <select class="kt-select w-36"
                                    name="assigned"
                                    data-kt-select="true"
                                    data-kt-select-placeholder="Toegewezen"
                                    id="assigned-filter">
                                <option value="">Alle permissies</option>
                                <option value="yes" {{ request('assigned') == 'yes' ? 'selected' : '' }}>Toegewezen</option>
                                <option value="no" {{ request('assigned') == 'no' ? 'selected' : '' }}>Niet toegewezen</option>
                            </select>
                        </form>
                        @if(request('module') || request('assigned') || request('search'))
                        <a href="{{ route('admin.permissions.index') }}"
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
            </div>

            <div class="kt-card-content">
                @if($allPermissions->count() > 0)
                    <div class="grid" data-kt-datatable="true" data-kt-datatable-page-size="10" id="permissions_table" data-permissions-table="true">
                        <div class="kt-scrollable-x-auto">
                            <table class="kt-table table-auto kt-table-border" data-kt-datatable-table="true">
                            <thead>
                                <tr>
                                    @if(auth()->user()->hasRole('super-admin') || auth()->user()->can('delete-permissions'))
                                    <th class="w-[50px] text-center">
                                        <span class="kt-table-col">
                                            <label class="kt-label flex items-center justify-center cursor-pointer mt-1">
                                                <input type="checkbox"
                                                       class="kt-checkbox"
                                                       id="select-all-permissions"
                                                       title="Selecteer alle verwijderbare permissies">
                                            </label>
                                        </span>
                                    </th>
                                    @endif
                                    <th class="min-w-[250px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Permissie</span>
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
                                            <span class="kt-table-col-label">Module</span>
                                            <span class="kt-table-col-sort"></span>
                                        </span>
                                    </th>
                                    <th class="min-w-[200px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Toegewezen aan</span>
                                            <span class="kt-table-col-sort"></span>
                                        </span>
                                    </th>
                                    <th class="min-w-[120px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Status</span>
                                            <span class="kt-table-col-sort"></span>
                                        </span>
                                    </th>
                                    @if(auth()->user()->hasRole('super-admin') || auth()->user()->can('edit-permissions') || auth()->user()->can('delete-permissions'))
                                    <th class="w-[60px] text-center">Acties</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    function getModuleFromPermission($permission) {
                                        $parts = explode('-', $permission->name);
                                        if (count($parts) > 1) {
                                            array_shift($parts); // Remove action
                                            return implode('-', $parts);
                                        }
                                        return $permission->group ?? 'other';
                                    }
                                @endphp
                                @foreach($allPermissions as $permission)
                                    @php
                                        $module = getModuleFromPermission($permission);
                                        $moduleDisplay = $moduleNames[$module] ?? ucfirst(str_replace(['-', '_'], ' ', $module));
                                        $canEdit = auth()->user()->hasRole('super-admin') || auth()->user()->can('edit-permissions');
                                        $canDelete = auth()->user()->hasRole('super-admin') || auth()->user()->can('delete-permissions');
                                        $hasRoles = $permission->roles_count > 0;
                                        $canDeletePermission = $canDelete && !$hasRoles;
                                    @endphp
                                    <tr>
                                        @if(auth()->user()->hasRole('super-admin') || auth()->user()->can('delete-permissions'))
                                        <td class="text-center">
                                            @if(!$hasRoles)
                                            <label class="kt-label flex items-center justify-center cursor-pointer">
                                                <input type="checkbox"
                                                       class="kt-checkbox permission-checkbox"
                                                       name="permission_ids[]"
                                                       value="{{ $permission->id }}"
                                                       data-permission-id="{{ $permission->id }}"
                                                       data-has-roles="false"
                                                       data-permission-name="{{ $permission->name }}">
                                            </label>
                                            @else
                                            <span class="text-muted-foreground text-xs" title="Niet verwijderbaar: toegewezen aan rollen">
                                                <i class="ki-filled ki-lock text-lg"></i>
                                            </span>
                                            @endif
                                        </td>
                                        @endif
                                        <td>
                                            <div class="flex flex-col">
                                                <span class="text-sm font-medium text-foreground">
                                                    {{ $permission->name }}
                                                </span>
                                                @if($permission->description)
                                                    <span class="text-xs text-muted-foreground mt-0.5">
                                                        {{ $permission->description }}
                                                    </span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="text-foreground font-normal">
                                            <span class="kt-badge kt-badge-sm kt-badge-info">
                                                {{ $moduleDisplay }}
                                            </span>
                                        </td>
                                        <td class="text-foreground font-normal">
                                            @if($hasRoles)
                                                <div class="flex flex-wrap gap-1">
                                                    @foreach($permission->roles as $role)
                                                        <span class="kt-badge kt-badge-sm kt-badge-success">
                                                            {{ ucfirst(str_replace('-', ' ', $role->name)) }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            @else
                                                <span class="kt-badge kt-badge-sm kt-badge-secondary">Geen rollen</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($hasRoles)
                                                <span class="kt-badge kt-badge-sm kt-badge-success">Toegewezen</span>
                                            @else
                                                <span class="kt-badge kt-badge-sm kt-badge-warning">Niet toegewezen</span>
                                            @endif
                                        </td>
                                        @if(auth()->user()->hasRole('super-admin') || auth()->user()->can('edit-permissions') || auth()->user()->can('delete-permissions'))
                                        <td class="w-[60px]" onclick="event.stopPropagation();">
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
                                                        @php
                                                            $canView = auth()->user()->hasRole('super-admin') || auth()->user()->can('view-permissions');
                                                            $canEditPermission = $canEdit && !$hasRoles;
                                                        @endphp
                                                        @if($canView)
                                                        <div class="kt-menu-item">
                                                            <a class="kt-menu-link" href="{{ route('admin.permissions.show', $permission) }}">
                                                                <span class="kt-menu-icon">
                                                                    <i class="ki-filled ki-eye"></i>
                                                                </span>
                                                                <span class="kt-menu-title">Bekijken</span>
                                                            </a>
                                                        </div>
                                                        @endif
                                                        @if($canEditPermission)
                                                        @if($canView)
                                                        <div class="kt-menu-separator"></div>
                                                        @endif
                                                        <div class="kt-menu-item">
                                                            <a class="kt-menu-link" href="{{ route('admin.permissions.edit', $permission) }}">
                                                                <span class="kt-menu-icon">
                                                                    <i class="ki-filled ki-pencil"></i>
                                                                </span>
                                                                <span class="kt-menu-title">Bewerken</span>
                                                            </a>
                                                        </div>
                                                        @endif
                                                        @if($canDeletePermission)
                                                        @if($canView || $canEditPermission)
                                                        <div class="kt-menu-separator"></div>
                                                        @endif
                                                        <div class="kt-menu-item">
                                                            <form action="{{ route('admin.permissions.destroy', $permission) }}" 
                                                                  method="POST" 
                                                                  style="display: inline;"
                                                                  onsubmit="return confirm('Weet je zeker dat je deze permissie wilt verwijderen?')">
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
                                        </td>
                                        @endif
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
                        <h4 class="text-lg font-semibold text-mono mb-2">Geen permissies gevonden</h4>
                        @can('create-permissions')
                        <a href="{{ route('admin.permissions.bulk-create') }}" class="kt-btn kt-btn-primary mt-3">
                            <i class="ki-filled ki-plus me-2"></i>
                            Permissies Bulk Aanmaken
                        </a>
                        @endcan
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
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

        // Auto-submit filters when changed
        const moduleFilter = document.getElementById('module-filter');
        const assignedFilter = document.getElementById('assigned-filter');
        const filtersForm = document.getElementById('filters-form');

        if (moduleFilter && filtersForm) {
            moduleFilter.addEventListener('change', function() {
                filtersForm.submit();
            });
        }

        if (assignedFilter && filtersForm) {
            assignedFilter.addEventListener('change', function() {
                filtersForm.submit();
            });
        }

        // Search form submit on Enter only (same as roles page)
        const searchInput = document.getElementById('search-input');
        if (searchInput) {
            searchInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    document.getElementById('search-form').submit();
                }
            });
        }

        // Replace "of" with "van" in pagination info
        function replaceOfWithVan() {
            const infoSpan = document.querySelector('[data-kt-datatable-info="true"]');
            if (infoSpan && infoSpan.textContent.includes(' of ')) {
                infoSpan.textContent = infoSpan.textContent.replace(' of ', ' van ');
            }
        }

        // Call on load and after a delay to catch dynamically updated content
        replaceOfWithVan();
        setTimeout(replaceOfWithVan, 100);
        setTimeout(replaceOfWithVan, 500);
        setTimeout(replaceOfWithVan, 1000);

        // Also observe changes to the pagination info element
        const infoSpan = document.querySelector('[data-kt-datatable-info="true"]');
        if (infoSpan) {
            const observer = new MutationObserver(function(mutations) {
                replaceOfWithVan();
            });
            observer.observe(infoSpan, { childList: true, characterData: true, subtree: true });
        }

        // Initialize KTComponents first (if available)
        if (window.KTComponents && window.KTComponents.init) {
            try {
                window.KTComponents.init();
            } catch (error) {
                console.warn('KTComponents initialization failed:', error);
            }
        }
        
        // Add click listeners to all menu toggles
        function initializeMenuClicks() {
            const menuToggles = document.querySelectorAll('#permissions_table .kt-menu-toggle');
            
            menuToggles.forEach(function(toggle) {
                // Remove any existing listeners by cloning
                const newToggle = toggle.cloneNode(true);
                toggle.parentNode.replaceChild(newToggle, toggle);
                
                // Add click listener that manually toggles the menu
                newToggle.addEventListener('click', function(e) {
                    e.stopPropagation();
                    
                    // Find menu item and dropdown
                    const menuItem = newToggle.closest('.kt-menu-item');
                    const dropdown = menuItem ? menuItem.querySelector('.kt-menu-dropdown') : null;
                    
                    if (!menuItem || !dropdown) {
                        return;
                    }
                    
                    const isOpen = menuItem.classList.contains('show');
                    
                    // Close all other menus first
                    document.querySelectorAll('#permissions_table .kt-menu-item.show').forEach(function(item) {
                        if (item !== menuItem) {
                            item.classList.remove('show');
                            const otherDropdown = item.querySelector('.kt-menu-dropdown');
                            if (otherDropdown) {
                                otherDropdown.classList.remove('show');
                            }
                        }
                    });
                    
                    // Toggle this menu
                    if (isOpen) {
                        menuItem.classList.remove('show');
                        dropdown.classList.remove('show');
                    } else {
                        menuItem.classList.add('show');
                        dropdown.classList.add('show');
                        
                        // Position dropdown using fixed positioning to avoid stacking context issues
                        const buttonRect = newToggle.getBoundingClientRect();
                        
                        dropdown.style.position = 'fixed';
                        dropdown.style.left = (buttonRect.right - 175) + 'px'; // Align right edge with button
                        dropdown.style.top = (buttonRect.bottom + 5) + 'px';
                        dropdown.style.right = 'auto';
                        dropdown.style.minWidth = '175px';
                        dropdown.style.width = '175px';
                        dropdown.style.zIndex = '99999';
                    }
                }, true); // Use capture phase to catch early
            });
        }
        
        // Initialize menu listeners
        initializeMenuClicks();
        setTimeout(initializeMenuClicks, 100);
        setTimeout(initializeMenuClicks, 500);
        setTimeout(initializeMenuClicks, 1000);
        
        // Initialize KTMenu for all menus (including table action menus)
        // This should be called after DOM is ready
        function initKTMenu() {
            if (window.KTMenu && window.KTMenu.init) {
                try {
                    window.KTMenu.init();
                } catch (error) {
                    console.warn('KTMenu initialization failed:', error);
                }
            }
        }
        
        // Initialize immediately
        initKTMenu();
        
        // Also try after delays in case menus are added dynamically
        setTimeout(initKTMenu, 100);
        setTimeout(initKTMenu, 500);
        setTimeout(initKTMenu, 1000);


        // Select All functionality - Wait for elements to be available
        function initSelectAll() {
            // Try multiple ways to find the checkbox
            let selectAllCheckbox = document.getElementById('select-all-permissions');

            // If not found by ID, try finding it in the table header
            if (!selectAllCheckbox) {
                const tableHeader = document.querySelector('#permissions_table thead th');
                if (tableHeader) {
                    selectAllCheckbox = tableHeader.querySelector('input#select-all-permissions');
                }
            }

            // If still not found, try querySelector on the table
            if (!selectAllCheckbox) {
                selectAllCheckbox = document.querySelector('#permissions_table input#select-all-permissions');
            }

            const bulkDeleteForm = document.getElementById('bulk-delete-form');
            const bulkDeleteBtn = document.getElementById('bulk-delete-btn');
            const selectedPermissionsContainer = document.getElementById('selected-permissions-container');

            if (!selectAllCheckbox || !bulkDeleteBtn || !bulkDeleteForm || !selectedPermissionsContainer) {
                // Retry if elements not found yet - but limit retries
                if (typeof initSelectAll.retryCount === 'undefined') {
                    initSelectAll.retryCount = 0;
                }
                initSelectAll.retryCount++;
                if (initSelectAll.retryCount < 20) { // Max 2 seconds
                    setTimeout(initSelectAll, 100);
                }
                return;
            }

            function getPermissionCheckboxes() {
                return document.querySelectorAll('#permissions_table input.permission-checkbox');
            }

            function updateBulkDeleteButton() {
                const checkboxes = getPermissionCheckboxes();
                const selected = Array.from(checkboxes).filter(function(cb) { return cb.checked; });
                const selectedIds = selected.map(function(cb) { return cb.value; });

                // Show/hide button
                if (selectedIds.length > 0) {
                    bulkDeleteBtn.style.display = 'inline-flex';
                    bulkDeleteForm.style.display = 'inline-block';
                } else {
                    bulkDeleteBtn.style.display = 'none';
                    bulkDeleteForm.style.display = 'none';
                }

                // Update form inputs
                selectedPermissionsContainer.innerHTML = '';
                selectedIds.forEach(function(id) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'permissions[]';
                    input.value = id;
                    selectedPermissionsContainer.appendChild(input);
                });

                // Update select all state
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
                const checkboxes = getPermissionCheckboxes();
                const selected = Array.from(checkboxes).filter(cb => cb.checked);

                if (selected.length === 0) {
                    alert('Selecteer minimaal één permissie om te verwijderen.');
                    return;
                }

                const withRoles = [];
                selected.forEach(checkbox => {
                    const row = checkbox.closest('tr');
                    if (row && row.querySelector('i.ki-filled.ki-lock')) {
                        withRoles.push(checkbox.getAttribute('data-permission-name') || 'Onbekend');
                    }
                });

                if (withRoles.length > 0) {
                    alert('De volgende permissies kunnen niet worden verwijderd omdat ze toegewezen zijn aan rollen:\n\n' +
                          withRoles.join('\n') +
                          '\n\nSelecteer alleen permissies die niet toegewezen zijn aan rollen.');
                    return;
                }

                if (confirm('Weet je zeker dat je ' + selected.length + ' permissie' +
                           (selected.length > 1 ? 's' : '') +
                           ' wilt verwijderen? Dit kan niet ongedaan worden gemaakt.')) {
                    bulkDeleteForm.submit();
                }
            };

            // Select all handler - use event delegation to ensure it works even if checkbox is replaced
            const selectAllHandler = function(e) {
                // Find checkbox fresh each time in case it was replaced
                let checkbox = document.getElementById('select-all-permissions');
                if (!checkbox) {
                    checkbox = document.querySelector('#permissions_table input#select-all-permissions');
                }

                if (checkbox && (e.target === checkbox || e.target.id === 'select-all-permissions')) {
                    e.stopPropagation();
                    const isChecked = checkbox.checked;
                    const checkboxes = getPermissionCheckboxes();

                    checkboxes.forEach(cb => {
                        if (!cb.disabled) {
                            cb.checked = isChecked;
                        }
                    });

                    updateBulkDeleteButton();
                }
            };

            // Add listener to checkbox directly
            if (selectAllCheckbox) {
                selectAllCheckbox.addEventListener('change', selectAllHandler);
            }

            // Also add listener to document as fallback (event delegation)
            document.addEventListener('change', selectAllHandler);

            // Also listen for click events - but only for select-all checkbox
            document.addEventListener('click', function(e) {
                // Only handle clicks on the select-all checkbox, ignore everything else
                if (e.target && e.target.id === 'select-all-permissions') {
                    e.stopPropagation(); // Prevent event from bubbling to other handlers
                    setTimeout(function() {
                        selectAllHandler(e);
                    }, 10);
                }
            }, true); // Use capture phase to handle before other listeners

            // Individual checkbox handlers
            document.addEventListener('change', function(e) {
                if (e.target && e.target.classList && e.target.classList.contains('permission-checkbox')) {
                    updateBulkDeleteButton();
                }
            });

            // Watch table for changes
            const permissionsTable = document.getElementById('permissions_table');
            if (permissionsTable) {
                const observer = new MutationObserver(function() {
                    setTimeout(updateBulkDeleteButton, 50);
                });
                observer.observe(permissionsTable, { childList: true, subtree: true });
            }

            // Initial update
            updateBulkDeleteButton();
            setTimeout(updateBulkDeleteButton, 100);
            setTimeout(updateBulkDeleteButton, 500);
        }

        // Initialize with retry mechanism - wait a bit longer for datatable to initialize
        setTimeout(function() {
            initSelectAll();
        }, 300);

        // Also try after datatable might be initialized
        setTimeout(function() {
            initSelectAll();
        }, 1000);
        
        // Prevent sidebar accordion from closing when clicking anywhere on the page
        // Monitor all accordion items and restore show class if removed unintentionally
        const sidebarMenu = document.getElementById('sidebar_menu');
        if (sidebarMenu) {
            const accordionItems = sidebarMenu.querySelectorAll('.kt-menu-item-accordion');
            const accordionObservers = new Map();
            
            function setupAccordionProtection(accordion) {
                // Only protect if accordion is open and has 'here' class (active page)
                const menuLink = accordion.querySelector('.kt-menu-link');
                const shouldBeOpen = accordion.classList.contains('show') || (menuLink && menuLink.classList.contains('here'));
                
                if (shouldBeOpen && !accordionObservers.has(accordion)) {
                    const observer = new MutationObserver(function(mutations) {
                        mutations.forEach(function(mutation) {
                            if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                                const wasOpen = mutation.oldValue && mutation.oldValue.includes('show');
                                const isNowClosed = !accordion.classList.contains('show');
                                
                                // If accordion was open and is now closed, but should stay open
                                if (wasOpen && isNowClosed) {
                                    const menuLink = accordion.querySelector('.kt-menu-link');
                                    // Only restore if this accordion has the 'here' class (active page)
                                    if (menuLink && menuLink.classList.contains('here')) {
                                        // Restore show class after a short delay
                                        setTimeout(function() {
                                            accordion.classList.add('show');
                                            const accordionContent = accordion.querySelector('.kt-menu-accordion');
                                            if (accordionContent) {
                                                accordionContent.classList.add('show');
                                            }
                                        }, 10);
                                    }
                                }
                            }
                        });
                    });
                    
                    observer.observe(accordion, {
                        attributes: true,
                        attributeFilter: ['class'],
                        attributeOldValue: true
                    });
                    
                    accordionObservers.set(accordion, observer);
                }
            }
            
            // Setup protection for all accordions
            accordionItems.forEach(setupAccordionProtection);
            
            // Re-setup when accordions are toggled
            accordionItems.forEach(function(accordion) {
                const menuLink = accordion.querySelector('.kt-menu-link');
                if (menuLink) {
                    menuLink.addEventListener('click', function() {
                        setTimeout(function() {
                            const observer = accordionObservers.get(accordion);
                            if (observer) {
                                observer.disconnect();
                                accordionObservers.delete(accordion);
                            }
                            setupAccordionProtection(accordion);
                        }, 100);
                    });
                }
            });
        }
    });
</script>
@endpush

@endsection
