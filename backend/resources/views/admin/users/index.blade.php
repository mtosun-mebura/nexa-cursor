@extends('admin.layouts.app')

@section('title', 'Gebruikers Beheer')

@section('content')

<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5">
        <h1 class="text-xl font-medium leading-none text-mono">
            Gebruikers Beheer
        </h1>
        @can('create-users')
        <a href="{{ route('admin.users.create') }}" class="kt-btn kt-btn-primary">
            <i class="ki-filled ki-plus me-2"></i>
            Nieuwe Gebruiker
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
                        {{ $stats['total_companies'] ?? 0 }}
                    </span>
                    <span class="text-secondary-foreground text-sm">
                        Bedrijven
                    </span>
                </div>
                <span class="not-last:border-e border-e-input my-1"></span>
                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                    <span class="text-mono text-2xl lg:text-2xl leading-none font-semibold">
                        {{ $stats['active_companies'] ?? 0 }}
                    </span>
                    <span class="text-secondary-foreground text-sm">
                        Actief
                    </span>
                </div>
                <span class="not-last:border-e border-e-input my-1"></span>
                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                    <span class="text-mono text-2xl lg:text-2xl leading-none font-semibold">
                        {{ $stats['total_users'] ?? 0 }}
                    </span>
                    <span class="text-secondary-foreground text-sm">
                        Gebruikers
                    </span>
                </div>
                <span class="not-last:border-e border-e-input my-1"></span>
                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                    <span class="text-mono text-2xl lg:text-2xl leading-none font-semibold">
                        {{ $stats['total_vacancies'] ?? 0 }}
                    </span>
                    <span class="text-secondary-foreground text-sm">
                        Vacatures
                    </span>
                </div>
                <span class="not-last:border-e border-e-input my-1"></span>
                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                    <span class="text-mono text-2xl lg:text-2xl leading-none font-semibold">
                        {{ $stats['intermediaries'] ?? 0 }}
                    </span>
                    <span class="text-secondary-foreground text-sm">
                        Tussenpartijen
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="grid gap-5 lg:gap-7.5">
        <div class="kt-card kt-card-grid min-w-full">
            <div class="kt-card-header py-5 flex-wrap gap-2">
                <h3 class="kt-card-title text-sm pb-3 w-full">
                    Toon 1 tot {{ $users->count() }} van {{ $users->count() }} gebruikers
                </h3>
                <div class="flex flex-wrap gap-2 lg:gap-5 justify-end w-full">
                    <!-- Search -->
                    <div class="flex">
                        <form method="GET" action="{{ route('admin.users.index') }}" class="flex gap-2" id="search-form">
                            @if(request('status'))
                                <input type="hidden" name="status" value="{{ request('status') }}">
                            @endif
                            @if(request('role'))
                                <input type="hidden" name="role" value="{{ request('role') }}">
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
                                <input placeholder="Zoek gebruikers..." 
                                       type="text" 
                                       name="search" 
                                       value="{{ request('search') }}"
                                       id="search-input"
                                       data-kt-datatable-search="#users_table"/>
                            </label>
                        </form>
                    </div>
                    <!-- Filters -->
                    <div class="flex flex-wrap gap-2.5 items-center">
                        <form method="GET" action="{{ route('admin.users.index') }}" id="filters-form" class="flex gap-2.5">
                            @if(request('search'))
                                <input type="hidden" name="search" value="{{ request('search') }}">
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
                            
                            @if($roles->count() > 0)
                            <select class="kt-select w-36" 
                                    name="role" 
                                    data-kt-select="true" 
                                    data-kt-select-placeholder="Rol"
                                    id="role-filter">
                                <option value="">Alle rollen</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role }}" {{ request('role') == $role ? 'selected' : '' }}>
                                        {{ ucfirst(str_replace('-', ' ', $role)) }}
                                    </option>
                                @endforeach
                            </select>
                            @endif
                            
                            @if($companies->count() > 0)
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
                                    name="sort" 
                                    data-kt-select="true" 
                                    data-kt-select-placeholder="Sortering"
                                    id="sort-filter">
                                <option value="" {{ !request('sort') ? 'selected' : '' }}>Geen sortering</option>
                                <option value="created_at" {{ request('sort') == 'created_at' ? 'selected' : '' }}>Datum</option>
                                <option value="first_name" {{ request('sort') == 'first_name' ? 'selected' : '' }}>Voornaam</option>
                                <option value="last_name" {{ request('sort') == 'last_name' ? 'selected' : '' }}>Achternaam</option>
                                <option value="email" {{ request('sort') == 'email' ? 'selected' : '' }}>E-mail</option>
                            </select>
                        </form>
                        @if(request('status') || request('role') || request('company') || (request('sort') && request('sort') != 'created_at') || request('direction') || request('search'))
                        <a href="{{ route('admin.users.index') }}" 
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
                @if($users->count() > 0)
                    <div class="grid" data-kt-datatable="true" data-kt-datatable-page-size="10" id="users_table">
                        <div class="kt-scrollable-x-auto">
                            <table class="kt-table table-auto kt-table-border" data-kt-datatable-table="true">
                            <thead>
                                <tr>
                                    <th class="min-w-[250px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Gebruiker</span>
                                            <span class="kt-table-col-sort">
                                                @php
                                                    $currentSort = request('sort');
                                                    $currentDirection = request('direction');
                                                    // Default voor tekst is 'asc' (alfabetisch)
                                                    if ($currentSort == 'first_name') {
                                                        // Als direction 'asc' is, toggle naar 'desc'
                                                        // Als direction 'desc' is of null, gebruik 'asc' (default)
                                                        $nextDirection = ($currentDirection == 'asc') ? 'desc' : 'asc';
                                                    } else {
                                                        $nextDirection = 'asc';
                                                    }
                                                @endphp
                                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'first_name', 'direction' => $nextDirection]) }}" 
                                                   class="kt-table-col-sort-btn"></a>
                                            </span>
                                        </span>
                                    </th>
                                    <th class="min-w-[150px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Rol</span>
                                            <span class="kt-table-col-sort"></span>
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
                                            <span class="kt-table-col-sort">
                                                @php
                                                    $currentSort = request('sort');
                                                    $currentDirection = request('direction');
                                                    // Default voor datums is 'desc'
                                                    if ($currentSort == 'email_verified_at') {
                                                        $nextDirection = ($currentDirection == 'desc') ? 'asc' : 'desc';
                                                    } else {
                                                        $nextDirection = 'desc';
                                                    }
                                                @endphp
                                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'email_verified_at', 'direction' => $nextDirection]) }}" 
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
                                                    // Default voor datums is 'desc' (nieuwste eerst)
                                                    // Als we op created_at sorteren
                                                    if ($currentSort == 'created_at') {
                                                        // Als direction 'desc' is, toggle naar 'asc'
                                                        // Als direction 'asc' is of null, gebruik 'desc' (default)
                                                        $nextDirection = ($currentDirection == 'desc') ? 'asc' : 'desc';
                                                    } else {
                                                        // Als we op een andere kolom sorteren, start met 'desc' (default voor datums)
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
                                @foreach($users as $user)
                                    <tr class="user-row" data-user-id="{{ $user->id }}">
                                        <td>
                                            <div class="flex items-center gap-2.5">
                                                @if($user->photo_blob)
                                                    <img alt="{{ $user->first_name }} {{ $user->last_name }}" class="rounded-full size-9 shrink-0" src="{{ route('admin.users.photo', $user) }}"/>
                                                @else
                                                    <div class="rounded-full size-9 shrink-0 bg-accent/60 border border-input flex items-center justify-center">
                                                        <span class="text-xs font-semibold text-secondary-foreground">
                                                            {{ strtoupper(substr($user->first_name ?? 'U', 0, 1) . substr($user->last_name ?? '', 0, 1)) }}
                                                        </span>
                                                    </div>
                                                @endif
                                                <div class="flex flex-col">
                                                    <a class="text-sm font-medium text-mono hover:text-primary mb-px" href="{{ route('admin.users.show', $user) }}" data-user-id="{{ $user->id }}">
                                                        {{ $user->first_name }} {{ $user->last_name }}
                                                    </a>
                                                    <a class="text-sm text-secondary-foreground font-normal hover:text-primary" href="mailto:{{ $user->email }}">
                                                        {{ $user->email }}
                                                    </a>
                                                    @if($user->function)
                                                        <span class="text-xs text-muted-foreground font-normal mt-0.5">
                                                            {{ $user->function }}
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-foreground font-normal">
                                            @if($user->roles->count() > 0)
                                                @foreach($user->roles as $role)
                                                    <span class="kt-badge kt-badge-info">{{ ucfirst(str_replace('-', ' ', $role->name)) }}</span>
                                                @endforeach
                                            @else
                                                <span class="text-sm text-muted-foreground">Geen rol</span>
                                            @endif
                                        </td>
                                        <td class="text-foreground font-normal">
                                            @if($user->company)
                                                <span class="text-sm">{{ $user->company->name }}</span>
                                            @else
                                                <span class="text-sm text-muted-foreground">Geen bedrijf</span>
                                            @endif
                                        </td>
                                        <td class="user-status-cell">
                                            @php
                                                $isActive = isset($user->is_active) ? $user->is_active : ($user->email_verified_at !== null);
                                            @endphp
                                            @if($isActive)
                                                <span class="kt-badge kt-badge-sm kt-badge-success">Actief</span>
                                            @else
                                                <span class="kt-badge kt-badge-sm kt-badge-danger">Inactief</span>
                                            @endif
                                        </td>
                                        <td class="text-foreground font-normal">
                                            <span class="text-sm">{{ $user->created_at->format('d-m-Y') }}</span>
                                        </td>
                                        <td class="w-[60px]" onclick="event.stopPropagation();">
                                            <div class="kt-menu flex justify-center" data-kt-menu="true">
                                                <div class="kt-menu-item" data-kt-menu-item-offset="0, 10px" data-kt-menu-item-placement="bottom-end" data-kt-menu-item-placement-rtl="bottom-start" data-kt-menu-item-toggle="dropdown" data-kt-menu-item-trigger="click">
                                                    <button class="kt-menu-toggle kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost">
                                                        <i class="ki-filled ki-dots-vertical text-lg"></i>
                                                    </button>
                                                    <div class="kt-menu-dropdown kt-menu-default w-full max-w-[175px]" data-kt-menu-dismiss="true">
                                                        @can('view-users')
                                                        <div class="kt-menu-item">
                                                            <a class="kt-menu-link" href="{{ route('admin.users.show', $user) }}">
                                                                <span class="kt-menu-icon">
                                                                    <i class="ki-filled ki-eye"></i>
                                                                </span>
                                                                <span class="kt-menu-title">Bekijken</span>
                                                            </a>
                                                        </div>
                                                        @endcan
                                                        @can('edit-users')
                                                        <div class="kt-menu-item">
                                                            <a class="kt-menu-link" href="{{ route('admin.users.edit', $user) }}">
                                                                <span class="kt-menu-icon">
                                                                    <i class="ki-filled ki-pencil"></i>
                                                                </span>
                                                                <span class="kt-menu-title">Bewerken</span>
                                                            </a>
                                                        </div>
                                                        @endcan
                                                        @if(auth()->user()->can('view-users') || auth()->user()->can('edit-users'))
                                                        <div class="kt-menu-separator"></div>
                                                        @endif
                                                        @can('edit-users')
                                                        <div class="kt-menu-item">
                                                            <form action="{{ route('admin.users.toggle-status', $user) }}" 
                                                                  method="POST" 
                                                                  style="display: inline;"
                                                                  class="toggle-status-form"
                                                                  data-user-id="{{ $user->id }}">
                                                                @csrf
                                                                <button type="submit" class="kt-menu-link w-full text-left">
                                                                    <span class="kt-menu-icon">
                                                                        @php
                                                                            $isActive = isset($user->is_active) ? $user->is_active : ($user->email_verified_at !== null);
                                                                        @endphp
                                                                        <i class="ki-filled toggle-status-icon {{ $isActive ? 'ki-pause' : 'ki-play' }}"></i>
                                                                    </span>
                                                                    <span class="kt-menu-title toggle-status-text">{{ $isActive ? 'Deactiveren' : 'Activeren' }}</span>
                                                                </button>
                                                            </form>
                                                        </div>
                                                        @endcan
                                                        @can('delete-users')
                                                        <div class="kt-menu-separator"></div>
                                                        @endcan
                                                        @can('delete-users')
                                                        <div class="kt-menu-item">
                                                            <form action="{{ route('admin.users.destroy', $user) }}" 
                                                                  method="POST" 
                                                                  style="display: inline;"
                                                                  onsubmit="return confirm('Weet je zeker dat je deze gebruiker wilt verwijderen?')">
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
                        <h4 class="text-lg font-semibold text-mono mb-2">Geen gebruikers gevonden</h4>
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
        const roleFilter = document.getElementById('role-filter');
        const companyFilter = document.getElementById('company-filter');
        const sortFilter = document.getElementById('sort-filter');
        
        if (statusFilter && filterForm) {
            statusFilter.addEventListener('change', function() {
                filterForm.submit();
            });
        }
        
        if (roleFilter && filterForm) {
            roleFilter.addEventListener('change', function() {
                filterForm.submit();
            });
        }
        
        if (companyFilter && filterForm) {
            companyFilter.addEventListener('change', function() {
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
                }, 300); // Wait for fade-out animation
            }, 3000); // 3 seconds
        }
        
        // Handle toggle status form submission via AJAX - using event delegation with capture
        document.addEventListener('submit', function(e) {
            // Check if this is a toggle-status form
            let form = e.target;
            while (form && form.tagName !== 'FORM') {
                form = form.parentElement;
            }
            
            if (!form || !form.classList.contains('toggle-status-form')) {
                return;
            }
            
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            
            const formData = new FormData(form);
            const url = form.action;
            const button = form.querySelector('button[type="submit"]');
            const userId = form.getAttribute('data-user-id');
            
            if (!userId) {
                console.error('Toggle status: No user ID found');
                return false;
            }
            
            if (!button) {
                console.error('Toggle status: No button found');
                return false;
            }
            
            const titleElement = button.querySelector('.kt-menu-title');
            const originalButtonText = titleElement ? titleElement.textContent.trim() : '';
            
            // Disable button
            button.disabled = true;
            if (titleElement) {
                titleElement.textContent = 'Bezig...';
            }
            
            // Make AJAX request
            fetch(url, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(data => {
                        throw new Error(data.message || 'Server error');
                    }).catch(err => {
                        // If JSON parsing fails, try to get text response
                        return response.text().then(text => {
                            throw new Error('Network error: ' + response.status + ' - ' + text);
                        });
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success && data.is_active !== undefined) {
                    // Find user row
                    const userRow = document.querySelector(`tr.user-row[data-user-id="${userId}"]`);
                    
                    if (!userRow) {
                        console.error('Toggle status: User row not found for ID:', userId);
                        // Reload page as fallback
                        window.location.reload();
                        return;
                    }
                    
                    // Update status badge
                    const statusCell = userRow.querySelector('.user-status-cell');
                    if (statusCell) {
                        statusCell.innerHTML = data.is_active 
                            ? '<span class="kt-badge kt-badge-sm kt-badge-success">Actief</span>'
                            : '<span class="kt-badge kt-badge-sm kt-badge-danger">Inactief</span>';
                    }
                    
                    // Update all toggle buttons for this user
                    document.querySelectorAll(`.toggle-status-form[data-user-id="${userId}"]`).forEach(function(f) {
                        const btn = f.querySelector('button[type="submit"]');
                        if (!btn) return;
                        
                        const icon = btn.querySelector('.toggle-status-icon');
                        const text = btn.querySelector('.toggle-status-text');
                        
                        if (icon) {
                            icon.className = 'ki-filled toggle-status-icon ' + (data.is_active ? 'ki-pause' : 'ki-play');
                        }
                        
                        if (text) {
                            text.textContent = data.is_active ? 'Deactiveren' : 'Activeren';
                        }
                    });
                    
                    // Re-enable button
                    button.disabled = false;
                    if (titleElement) {
                        titleElement.textContent = originalButtonText;
                    }
                    
                    // Close dropdown
                    setTimeout(() => {
                        const menu = form.closest('.kt-menu');
                        if (menu) {
                            const toggle = menu.querySelector('.kt-menu-toggle');
                            if (toggle && (toggle.getAttribute('aria-expanded') === 'true' || toggle.classList.contains('active'))) {
                                toggle.click();
                            }
                        }
                    }, 150);
                } else {
                    throw new Error(data.message || 'Unknown error');
                }
            })
            .catch(error => {
                console.error('Toggle status error:', error);
                alert('Fout: ' + error.message);
                button.disabled = false;
                if (titleElement) {
                    titleElement.textContent = originalButtonText;
                }
            });
            
            return false;
        }, true);
        
        // Make table rows clickable (except actions column) - using event delegation
        // This works even after filtering/searching because we listen on tbody
        const tbody = document.querySelector('#users_table table tbody');
        if (tbody) {
            tbody.addEventListener('click', function(e) {
                // Find the closest row
                const row = e.target.closest('tr.user-row');
                if (!row) {
                    return;
                }
                
                // Don't navigate if clicking on actions column or menu
                if (e.target.closest('td:last-child') || e.target.closest('.kt-menu') || e.target.closest('button') || e.target.closest('a')) {
                    return;
                }
                
                // Get user ID from the name link
                const nameLink = row.querySelector('td:first-child a[data-user-id]');
                if (nameLink) {
                    const userId = nameLink.getAttribute('data-user-id');
                    if (userId) {
                        window.location.href = '/admin/users/' + userId;
                    }
                }
            });
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
    
    /* Table row hover styling (same as demo) */
    .user-row {
        cursor: pointer !important;
    }
    .user-row:hover {
        background-color: var(--muted) !important;
    }
    @supports (color: color-mix(in lab, red, red)) {
        .user-row:hover {
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
            // Clear any pending close operations
            if (closingTimeout) {
                clearTimeout(closingTimeout);
                closingTimeout = null;
            }
            
            // Vind alle kt-select displays
            const displays = document.querySelectorAll('.kt-select-display');
            
            displays.forEach(function(display) {
                if (display === exceptElement) return;
                
                // Check of deze dropdown open is
                if (display.getAttribute('aria-expanded') === 'true') {
                    // Probeer eerst via KTUI API
                    const select = display.parentElement?.querySelector('select.kt-select[data-kt-select="true"]');
                    if (select && typeof window.KTSelect !== 'undefined') {
                        try {
                            const instance = window.KTSelect.getInstance(select);
                            if (instance && instance.hide && typeof instance.hide === 'function') {
                                instance.hide();
                            }
                        } catch (e) {
                            // Fallback naar DOM manipulatie
                        }
                    }
                    
                    // Fallback: direct DOM manipulatie
                    display.setAttribute('aria-expanded', 'false');
                    
                    // Zoek en sluit alle dropdown menu's
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
            // Luister alleen naar nieuwe dropdown menu's die verschijnen (meer betrouwbaar)
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    // Check voor nieuwe dropdown menu's die verschijnen (dropdown is daadwerkelijk open)
                    if (mutation.addedNodes.length > 0) {
                        mutation.addedNodes.forEach(function(node) {
                            if (node.nodeType === 1) {
                                const isDropdown = node.classList && (
                                    node.classList.contains('kt-menu-dropdown') ||
                                    node.classList.contains('kt-select-dropdown') ||
                                    node.hasAttribute('data-kt-select-dropdown')
                                );
                                
                                // Check of dropdown daadwerkelijk zichtbaar is
                                if (isDropdown) {
                                    // Wacht even om te zien of dropdown zichtbaar wordt
                                    setTimeout(function() {
                                        const computedStyle = window.getComputedStyle(node);
                                        const isVisible = computedStyle.display !== 'none' && 
                                                         computedStyle.visibility !== 'hidden' && 
                                                         computedStyle.opacity !== '0';
                                        
                                        if (isVisible) {
                                            // Een nieuwe dropdown is daadwerkelijk verschenen, sluit alle andere
                                            const allDisplays = document.querySelectorAll('.kt-select-display[aria-expanded="true"]');
                                            allDisplays.forEach(function(display) {
                                                // Vind de bijbehorende dropdown
                                                const parent = display.closest('.kt-select-wrapper, [data-kt-select-wrapper]') || display.parentElement;
                                                const relatedDropdown = parent && parent.querySelector('.kt-menu-dropdown, .kt-select-dropdown, [data-kt-select-dropdown]');
                                                
                                                // Als dit niet de dropdown is die net verscheen, sluit hem
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
            
            // Luister naar clicks buiten dropdowns om ze te sluiten
            document.addEventListener('click', function(e) {
                const clickedSelect = e.target.closest('select.kt-select[data-kt-select="true"]');
                const clickedDisplay = e.target.closest('.kt-select-display');
                const clickedDropdown = e.target.closest('.kt-menu-dropdown, .kt-select-dropdown, [data-kt-select-dropdown]');
                const clickedOption = e.target.closest('.kt-menu-item, [data-kt-select-option]');
                
                // Als de click binnen een dropdown is, doe niets
                if (clickedSelect || clickedDisplay || clickedDropdown || clickedOption) {
                    return;
                }
                
                // Click was buiten alle dropdowns, sluit ze allemaal
                closeAllDropdowns(null);
                openDropdown = null;
            });
        }
        
        // Initialiseer
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

@endsection
