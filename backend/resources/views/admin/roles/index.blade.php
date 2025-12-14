@extends('admin.layouts.app')

@section('title', 'Rollen Beheer')

@section('content')

<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5">
        <h1 class="text-xl font-medium leading-none text-mono">
            Rollen Beheer
        </h1>
        @if(auth()->user()->hasRole('super-admin') || auth()->user()->can('create-roles'))
        <a href="{{ route('admin.roles.create') }}" class="kt-btn kt-btn-primary">
            <i class="ki-filled ki-plus me-2"></i>
            Nieuwe Rol
        </a>
        @endif
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
                        {{ $stats['total_roles'] ?? 0 }}
                    </span>
                    <span class="text-secondary-foreground text-sm">
                        Totaal Rollen
                    </span>
                </div>
                <span class="not-last:border-e border-e-input my-1"></span>
                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                    <span class="text-mono text-2xl lg:text-2xl leading-none font-semibold">
                        {{ $stats['system_roles'] ?? 0 }}
                    </span>
                    <span class="text-secondary-foreground text-sm">
                        Systeem Rollen
                    </span>
                </div>
                <span class="not-last:border-e border-e-input my-1"></span>
                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                    <span class="text-mono text-2xl lg:text-2xl leading-none font-semibold">
                        {{ $stats['custom_roles'] ?? 0 }}
                    </span>
                    <span class="text-secondary-foreground text-sm">
                        Aangepaste Rollen
                    </span>
                </div>
                <span class="not-last:border-e border-e-input my-1"></span>
                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                    <span class="text-mono text-2xl lg:text-2xl leading-none font-semibold">
                        {{ $stats['total_users_with_roles'] ?? 0 }}
                    </span>
                    <span class="text-secondary-foreground text-sm">
                        Gebruikers met Rollen
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="grid gap-5 lg:gap-7.5">
        <div class="kt-card kt-card-grid min-w-full">
            <div class="kt-card-header py-5 flex-wrap gap-2">
                <h3 class="kt-card-title text-sm pb-3 w-full">
                    Toon 1 tot {{ $roles->count() }} van {{ $roles->count() }} rollen
                </h3>
                <div class="flex flex-wrap gap-2 lg:gap-5 justify-end w-full">
                    <!-- Search -->
                    <div class="flex">
                        <form method="GET" action="{{ route('admin.roles.index') }}" class="flex gap-2" id="search-form">
                            @if(request('type'))
                                <input type="hidden" name="type" value="{{ request('type') }}">
                            @endif
                            @if(request('users'))
                                <input type="hidden" name="users" value="{{ request('users') }}">
                            @endif
                            @if(request('status'))
                                <input type="hidden" name="status" value="{{ request('status') }}">
                            @endif
                            @if(request('permissions'))
                                <input type="hidden" name="permissions" value="{{ request('permissions') }}">
                            @endif
                            @if(request('sort'))
                                <input type="hidden" name="sort" value="{{ request('sort') }}">
                            @endif
                            @if(request('direction'))
                                <input type="hidden" name="direction" value="{{ request('direction') }}">
                            @endif
                            @if(request('status'))
                                <input type="hidden" name="status" value="{{ request('status') }}">
                            @endif
                            <label class="kt-input w-64" style="position: relative !important;">
                                <i class="ki-filled ki-magnifier"></i>
                                <input placeholder="Zoek rollen..." 
                                       type="text" 
                                       name="search" 
                                       value="{{ request('search') }}"
                                       id="search-input"
                                       data-kt-datatable-search="#roles_table"/>
                            </label>
                        </form>
                    </div>
                    <!-- Filters -->
                    <div class="flex flex-wrap gap-2.5 items-center">
                        <form method="GET" action="{{ route('admin.roles.index') }}" id="filters-form" class="flex gap-2.5">
                            @if(request('search'))
                                <input type="hidden" name="search" value="{{ request('search') }}">
                            @endif
                            
                            <select class="kt-select w-36" 
                                    name="type" 
                                    data-kt-select="true" 
                                    data-kt-select-placeholder="Type"
                                    id="type-filter">
                                <option value="">Alle types</option>
                                <option value="system" {{ request('type') == 'system' ? 'selected' : '' }}>Systeem</option>
                                <option value="custom" {{ request('type') == 'custom' ? 'selected' : '' }}>Aangepast</option>
                            </select>
                            
                            <select class="kt-select w-36" 
                                    name="users" 
                                    data-kt-select="true" 
                                    data-kt-select-placeholder="Gebruikers"
                                    id="users-filter">
                                <option value="">Alle rollen</option>
                                <option value="with_users" {{ request('users') == 'with_users' ? 'selected' : '' }}>Met gebruikers</option>
                                <option value="without_users" {{ request('users') == 'without_users' ? 'selected' : '' }}>Zonder gebruikers</option>
                            </select>
                            
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
                                <option value="name" {{ request('sort') == 'name' ? 'selected' : '' }}>Naam</option>
                                <option value="users_count" {{ request('sort') == 'users_count' ? 'selected' : '' }}>Aantal gebruikers</option>
                                <option value="is_active" {{ request('sort') == 'is_active' ? 'selected' : '' }}>Status</option>
                                <option value="created_at" {{ request('sort') == 'created_at' ? 'selected' : '' }}>Datum</option>
                            </select>
                        </form>
                        @if(request('type') || request('users') || (request('status') && request('status') != '') || (request('sort') && request('sort') != 'name') || request('direction') || request('search'))
                        <a href="{{ route('admin.roles.index') }}" 
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
                @if($roles->count() > 0)
                    <div class="grid" data-kt-datatable="true" data-kt-datatable-page-size="10" id="roles_table">
                        <div class="kt-scrollable-x-auto">
                            <table class="kt-table table-auto kt-table-border" data-kt-datatable-table="true">
                            <thead>
                                <tr>
                                    <th class="min-w-[200px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Rol</span>
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
                                    <th class="min-w-[120px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Type</span>
                                            <span class="kt-table-col-sort"></span>
                                        </span>
                                    </th>
                                    <th class="min-w-[120px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Rechten</span>
                                            <span class="kt-table-col-sort"></span>
                                        </span>
                                    </th>
                                    <th class="min-w-[120px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Gebruikers</span>
                                            <span class="kt-table-col-sort">
                                                @php
                                                    $currentSort = request('sort');
                                                    $currentDirection = request('direction');
                                                    if ($currentSort == 'users_count') {
                                                        $nextDirection = ($currentDirection == 'desc') ? 'asc' : 'desc';
                                                    } else {
                                                        $nextDirection = 'desc';
                                                    }
                                                @endphp
                                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'users_count', 'direction' => $nextDirection]) }}" 
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
                                                    if ($currentSort == 'is_active') {
                                                        $nextDirection = ($currentDirection == 'desc') ? 'asc' : 'desc';
                                                    } else {
                                                        $nextDirection = 'desc';
                                                    }
                                                @endphp
                                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'is_active', 'direction' => $nextDirection]) }}" 
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
                                @foreach($roles as $role)
                                    @if($role->name !== 'super-admin' || auth()->user()->hasRole('super-admin'))
                                    <tr class="role-row cursor-pointer" data-role-id="{{ $role->id }}" onclick="window.location.href='{{ route('admin.roles.show', $role) }}'">
                                        <td>
                                            <div class="flex items-center gap-2.5">
                                                <div class="rounded-full size-9 shrink-0 bg-accent/60 border border-input flex items-center justify-center">
                                                    <i class="ki-filled ki-profile-circle text-lg text-foreground"></i>
                                                </div>
                                                <div class="flex flex-col">
                                                    <a class="text-sm font-medium text-mono hover:text-primary mb-px" href="{{ route('admin.roles.show', $role) }}" data-role-id="{{ $role->id }}" onclick="event.stopPropagation();">
                                                        {{ ucfirst(str_replace('-', ' ', $role->name)) }}
                                                    </a>
                                                    @if($role->description)
                                                        <span class="text-xs text-muted-foreground font-normal mt-0.5">
                                                            {{ Str::limit($role->description, 50) }}
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-foreground font-normal">
                                            @if(in_array($role->name, ['super-admin', 'company-admin', 'staff', 'candidate']))
                                                <span class="kt-badge kt-badge-sm kt-badge-warning">Systeem</span>
                                            @else
                                                <span class="kt-badge kt-badge-sm kt-badge-success">Aangepast</span>
                                            @endif
                                        </td>
                                        <td class="text-foreground font-normal">
                                            <span class="kt-badge kt-badge-sm kt-badge-info">{{ $role->permissions->count() }}</span>
                                        </td>
                                        <td class="text-foreground font-normal">
                                            <span class="kt-badge kt-badge-sm kt-badge-secondary">{{ $role->users_count ?? $role->users->count() }}</span>
                                        </td>
                                        <td class="text-foreground font-normal">
                                            @php
                                                $isActive = $role->is_active ?? true;
                                            @endphp
                                            @if($isActive)
                                                <span class="kt-badge kt-badge-sm kt-badge-success">Actief</span>
                                            @else
                                                <span class="kt-badge kt-badge-sm kt-badge-danger">Inactief</span>
                                            @endif
                                        </td>
                                        <td class="text-foreground font-normal">
                                            <span class="text-sm">{{ $role->created_at->format('d-m-Y') }}</span>
                                        </td>
                                        <td class="w-[60px]" onclick="event.stopPropagation();">
                                            <div class="kt-menu flex justify-center" data-kt-menu="true">
                                                <div class="kt-menu-item" data-kt-menu-item-offset="0, 10px" data-kt-menu-item-placement="bottom-end" data-kt-menu-item-placement-rtl="bottom-start" data-kt-menu-item-toggle="dropdown" data-kt-menu-item-trigger="click">
                                                    <button class="kt-menu-toggle kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost">
                                                        <i class="ki-filled ki-dots-vertical text-lg"></i>
                                                    </button>
                                                    <div class="kt-menu-dropdown kt-menu-default w-full max-w-[175px]" data-kt-menu-dismiss="true">
                                                        @if(auth()->user()->hasRole('super-admin') || auth()->user()->can('view-roles'))
                                                        <div class="kt-menu-item">
                                                            <a class="kt-menu-link" href="{{ route('admin.roles.show', $role) }}">
                                                                <span class="kt-menu-icon">
                                                                    <i class="ki-filled ki-eye"></i>
                                                                </span>
                                                                <span class="kt-menu-title">Bekijken</span>
                                                            </a>
                                                        </div>
                                                        @endif
                                                        @if(auth()->user()->hasRole('super-admin') || auth()->user()->can('edit-roles'))
                                                        <div class="kt-menu-item">
                                                            <a class="kt-menu-link" href="{{ route('admin.roles.edit', $role) }}">
                                                                <span class="kt-menu-icon">
                                                                    <i class="ki-filled ki-pencil"></i>
                                                                </span>
                                                                <span class="kt-menu-title">Bewerken</span>
                                                            </a>
                                                        </div>
                                                        @endif
                                                        @if((auth()->user()->hasRole('super-admin') || auth()->user()->can('view-roles')) || (auth()->user()->hasRole('super-admin') || auth()->user()->can('edit-roles')))
                                                        <div class="kt-menu-separator"></div>
                                                        @endif
                                                        @if(auth()->user()->hasRole('super-admin') || auth()->user()->can('edit-roles'))
                                                        @if(!in_array($role->name, ['super-admin', 'company-admin', 'staff', 'candidate']))
                                                        <div class="kt-menu-item">
                                                            <form action="{{ route('admin.roles.toggle-status', $role) }}" method="POST" class="inline" onsubmit="return confirm('Weet je zeker dat je de status van deze rol wilt wijzigen?')">
                                                                @csrf
                                                                <button type="submit" class="kt-menu-link w-full text-left">
                                                                    <span class="kt-menu-icon">
                                                                        <i class="ki-filled ki-arrows-circle"></i>
                                                                    </span>
                                                                    <span class="kt-menu-title">
                                                                        @php
                                                                            $isActive = $role->is_active ?? true;
                                                                        @endphp
                                                                        {{ $isActive ? 'Deactiveren' : 'Activeren' }}
                                                                    </span>
                                                                </button>
                                                            </form>
                                                        </div>
                                                        @endif
                                                        @endif
                                                        @if((auth()->user()->hasRole('super-admin') || auth()->user()->can('edit-roles')) && !in_array($role->name, ['super-admin', 'company-admin', 'staff', 'candidate']))
                                                        <div class="kt-menu-separator"></div>
                                                        @endif
                                                        @if(auth()->user()->hasRole('super-admin') || auth()->user()->can('delete-roles'))
                                                        @if(!in_array($role->name, ['super-admin', 'company-admin', 'staff', 'candidate']))
                                                        <div class="kt-menu-item">
                                                            <form action="{{ route('admin.roles.destroy', $role) }}" 
                                                                  method="POST" 
                                                                  style="display: inline;"
                                                                  onsubmit="return confirm('Weet je zeker dat je deze rol wilt verwijderen?')">
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
                                                        @endif
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    @endif
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
                        <h4 class="text-lg font-semibold text-mono mb-2">Geen rollen gevonden</h4>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    /* Table row hover styling (same as users) */
    .role-row {
        cursor: pointer !important;
    }
    .role-row:hover {
        background-color: var(--muted) !important;
    }
    @supports (color: color-mix(in lab, red, red)) {
        .role-row:hover {
            background-color: color-mix(in oklab, var(--muted) 50%, transparent) !important;
        }
    }
    
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
    
    /* Volledige tekst weergeven in dropdown opties */
    #filters-form .kt-select-option-text {
        overflow: visible !important;
        white-space: normal !important;
        text-overflow: clip !important;
    }
    
    /* Zorg dat dropdown opties breed genoeg zijn */
    #filters-form .kt-select-options {
        min-width: max-content !important;
    }
    
    #filters-form .kt-select-option {
        white-space: normal !important;
    }
</style>
@endpush

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
        
        // Initial replace
        replaceOfWithVan();
        
        // Watch for changes in pagination info
        const observer = new MutationObserver(replaceOfWithVan);
        const infoSpan = document.querySelector('[data-kt-datatable-info="true"]');
        if (infoSpan) {
            observer.observe(infoSpan, { childList: true, subtree: true });
        }
        
        // Auto-submit filters on change
        const filterSelects = document.querySelectorAll('#filters-form select');
        filterSelects.forEach(select => {
            select.addEventListener('change', function() {
                document.getElementById('filters-form').submit();
            });
        });
        
        // Search form submit on Enter
        const searchInput = document.getElementById('search-input');
        if (searchInput) {
            searchInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    document.getElementById('search-form').submit();
                }
            });
        }
        
        // Make table rows clickable (except actions column)
        document.querySelectorAll('tbody tr.role-row').forEach(function(row) {
            row.addEventListener('click', function(e) {
                // Don't navigate if clicking on actions column or menu
                if (e.target.closest('td:last-child') || e.target.closest('.kt-menu') || e.target.closest('button') || e.target.closest('a')) {
                    return;
                }
                
                // Get role ID from the name link
                const nameLink = this.querySelector('td:first-child a[data-role-id]');
                if (nameLink) {
                    const roleId = nameLink.getAttribute('data-role-id');
                    if (roleId) {
                        window.location.href = '/admin/roles/' + roleId;
                    }
                }
            });
        });
    });
</script>
@endpush

@endsection
