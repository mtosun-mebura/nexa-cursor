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

    <!-- Statistics Cards -->
    <div class="kt-card mb-5">
        <div class="kt-card-content">
            <div class="flex flex-col sm:flex-row lg:px-10 py-1.5 gap-2">
                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                    <span class="text-mono text-2xl lg:text-2xl leading-none font-semibold">
                        {{ $stats['total_companies'] ?? 0 }}
                    </span>
                    <span class="text-secondary-foreground text-sm">
                        Bedrijven
                    </span>
                </div>
                <span class="hidden sm:block not-last:border-e border-e-input my-1"></span>
                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                    <span class="text-mono text-2xl lg:text-2xl leading-none font-semibold">
                        {{ $stats['active_companies'] ?? 0 }}
                    </span>
                    <span class="text-secondary-foreground text-sm">
                        Actief
                    </span>
                </div>
                <span class="hidden sm:block not-last:border-e border-e-input my-1"></span>
                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                    <span class="text-mono text-2xl lg:text-2xl leading-none font-semibold">
                        {{ $stats['total_users'] ?? 0 }}
                    </span>
                    <span class="text-secondary-foreground text-sm">
                        Gebruikers
                    </span>
                </div>
                <span class="hidden sm:block not-last:border-e border-e-input my-1"></span>
                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                    <span class="text-mono text-2xl lg:text-2xl leading-none font-semibold">
                        {{ $stats['total_vacancies'] ?? 0 }}
                    </span>
                    <span class="text-secondary-foreground text-sm">
                        Vacatures
                    </span>
                </div>
                <span class="hidden sm:block not-last:border-e border-e-input my-1"></span>
                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                    <span class="text-mono text-2xl lg:text-2xl leading-none font-semibold">
                        {{ $stats['intermediaries'] ?? 0 }}
                    </span>
                    <span class="text-secondary-foreground text-sm">
                        Tussenpartijen / Recruiters
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="grid gap-5 lg:gap-7.5">
        <div class="kt-card kt-card-grid min-w-full">
            <div class="kt-card-header px-5 py-5 flex-wrap gap-2 justify-between items-center">
                <h3 class="kt-card-title text-sm mb-0">
                    Toon 1 tot {{ $users->count() }} van {{ $users->count() }} gebruikers
                </h3>
                <div class="flex flex-col sm:flex-row flex-wrap gap-2 lg:gap-5 justify-end items-center w-full sm:w-auto ml-auto">
                    <!-- Search -->
                    <div class="flex w-full sm:w-auto justify-end">
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
                            <label class="kt-input w-full sm:w-64" style="position: relative !important;">
                                <i class="ki-filled ki-magnifier"></i>
                                <input placeholder="Zoek gebruikers..." 
                                       type="text" 
                                       name="search" 
                                       value="{{ request('search') }}"
                                       id="search-input"
/>
                            </label>
                        </form>
                    </div>
                    <!-- Filters -->
                    <div class="flex flex-col sm:flex-row flex-wrap gap-2.5 items-stretch sm:items-center w-full sm:w-auto">
                        <form method="GET" action="{{ route('admin.users.index') }}" id="filters-form" class="flex flex-col sm:flex-row gap-2.5 w-full sm:w-auto">
                            @if(request('search'))
                                <input type="hidden" name="search" value="{{ request('search') }}">
                            @endif
                            
                            <select class="kt-select w-full sm:w-36" 
                                    name="status" 
                                    data-kt-select="true" 
                                    data-kt-select-placeholder="Status"
                                    id="status-filter">
                                <option value="">Alle statussen</option>
                                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Actief</option>
                                <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactief</option>
                            </select>
                            
                            @if($roles->count() > 0)
                            <select class="kt-select w-full sm:w-36" 
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
                    <div class="grid" data-admin-datatable="true" data-admin-datatable-page-size="10" id="users_table" data-admin-datatable-label="gebruikers" data-admin-datatable-on-page="syncUsersBulkSelection">
                        <div class="kt-scrollable-x-auto admin-table-scroll-wrap users-table-wrap">
                            <table class="kt-table kt-table-border admin-fluid-table w-full @can('delete-users') has-user-check @endcan">
                            <thead>
                                <tr>
                                    @can('delete-users')
                                    <th class="admin-table__check-col text-center" data-no-row-link data-label="">
                                        <div class="users-check-col-head">
                                            <button type="button"
                                                    id="users-bulk-delete"
                                                    class="kt-btn kt-btn-sm kt-btn-ghost kt-btn-destructive hidden"
                                                    hidden
                                                    aria-label="Geselecteerde gebruikers verwijderen"
                                                    title="Verwijderen">
                                                <i class="ki-filled ki-trash" aria-hidden="true"></i>
                                                <span class="users-bulk-delete-count">(<span data-users-selected-count>0</span>)</span>
                                            </button>
                                            <label class="kt-label mb-0 inline-flex items-center justify-center cursor-pointer">
                                                <input type="checkbox" class="kt-checkbox" id="users-select-all" aria-label="Alles selecteren">
                                            </label>
                                        </div>
                                    </th>
                                    @endcan
                                    <th data-label="Gebruiker">
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
                                    <th data-label="Rol">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Rol</span>
                                            <span class="kt-table-col-sort"></span>
                                        </span>
                                    </th>
                                    <th data-label="Bedrijf">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Bedrijf</span>
                                            <span class="kt-table-col-sort"></span>
                                        </span>
                                    </th>
                                    <th data-label="Status">
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
                                    <th data-label="Aangemaakt">
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
                                    <th class="text-center" data-label="Acties">Acties</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($users as $user)
                                    <tr class="user-row" data-user-id="{{ $user->id }}">
                                        @can('delete-users')
                                        <td class="admin-table__check-col text-center" data-no-row-link data-label="">
                                            @if($user->id !== auth()->id())
                                            <label class="kt-label mb-0 inline-flex items-center justify-center cursor-pointer">
                                                <input type="checkbox"
                                                       class="kt-checkbox user-row-checkbox"
                                                       value="{{ $user->id }}"
                                                       aria-label="Selecteer {{ $user->first_name }} {{ $user->last_name }}">
                                            </label>
                                            @endif
                                        </td>
                                        @endcan
                                        <td>
                                            <div class="flex items-center gap-2.5">
                                                @if($user->photo_blob)
                                                    <img alt="{{ $user->first_name }} {{ $user->last_name }}" class="rounded-full size-9 shrink-0" src="{{ $user->photo_blob ? route('secure.photo', ['token' => $user->getPhotoToken()]) : asset('assets/media/avatars/300-2.png') }}"/>
                                                @else
                                                    <div class="rounded-full size-9 shrink-0 bg-accent/60 border border-input flex items-center justify-center">
                                                        <span class="text-xs font-semibold text-secondary-foreground">
                                                            {{ strtoupper(substr($user->first_name ?? 'U', 0, 1) . substr($user->last_name ?? '', 0, 1)) }}
                                                        </span>
                                                    </div>
                                                @endif
                                                <div class="flex flex-col">
                                                    <div class="flex flex-wrap items-center gap-1.5 min-w-0">
                                                        <a class="text-sm font-medium text-mono hover:text-primary mb-px" href="{{ route('admin.users.show', $user) }}" data-user-id="{{ $user->id }}">
                                                            {{ $user->first_name }} {{ $user->last_name }}
                                                        </a>
                                                        @if($user->id === auth()->id())
                                                            <span class="kt-badge kt-badge-sm kt-badge-secondary shrink-0">Jij</span>
                                                        @endif
                                                    </div>
                                                    <div class="flex items-center gap-1 min-w-0">
                                                        <span class="user-email-text text-sm text-secondary-foreground font-normal truncate">{{ $user->email }}</span>
                                                        <button type="button"
                                                                class="user-email-copy shrink-0 inline-flex items-center justify-center size-6 rounded text-muted-foreground hover:text-primary"
                                                                data-copy-text="{{ $user->email }}"
                                                                title="E-mailadres kopiëren"
                                                                aria-label="E-mailadres kopiëren">
                                                            <i class="ki-filled ki-copy text-xs pointer-events-none" aria-hidden="true"></i>
                                                        </button>
                                                    </div>
                                                    @if($user->function && $user->company?->hasSkillmatchingModule())
                                                        <span class="text-xs text-muted-foreground font-normal mt-0.5">
                                                            {{ $user->function }}
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-foreground font-normal">
                                            @php
                                                $userRoleNames = $displayRoleNames[$user->id] ?? $user->assignedRoleNames();
                                            @endphp
                                            @if(count($userRoleNames) > 0)
                                                <div class="flex flex-wrap gap-1">
                                                    @foreach($userRoleNames as $roleName)
                                                        <span class="kt-badge kt-badge-info">{{ ucfirst(str_replace('-', ' ', $roleName)) }}</span>
                                                    @endforeach
                                                </div>
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
                                                $presence = ($appPresence[$user->id] ?? null) ?: ['chauffeur' => ['applicable' => false, 'online' => false], 'contract' => ['applicable' => false, 'online' => false]];
                                            @endphp
                                            <div class="flex flex-col items-start gap-1">
                                                <span class="user-account-status">
                                                    @if($isActive)
                                                        <span class="kt-badge kt-badge-sm kt-badge-success">Actief</span>
                                                    @else
                                                        <span class="kt-badge kt-badge-sm kt-badge-danger">Inactief</span>
                                                    @endif
                                                </span>
                                                @if(!empty($presence['chauffeur']['applicable']))
                                                    <span class="kt-badge kt-badge-sm {{ !empty($presence['chauffeur']['online']) ? 'kt-badge-success' : 'kt-badge-secondary' }}" title="Status in de chauffeur-app">
                                                        Chauffeur · {{ !empty($presence['chauffeur']['online']) ? 'Online' : 'Offline' }}
                                                    </span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="text-foreground font-normal">
                                            <span class="text-sm">{{ $user->created_at->format('d-m-Y') }}</span>
                                        </td>
                                        <td class="users-table__actions-col" onclick="event.stopPropagation();" data-no-row-link>
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
                                                        @if($user->id !== auth()->id())
                                                        @if(auth()->user()->can('view-users') || auth()->user()->can('edit-users'))
                                                        <div class="kt-menu-separator"></div>
                                                        @endif
                                                        @can('edit-users')
                                                        <div class="kt-menu-item">
                                                            <form action="{{ route('admin.users.force-logout', $user) }}"
                                                                  method="POST"
                                                                  style="display: inline;"
                                                                  data-admin-confirm="Deze gebruiker wordt op alle apparaten uitgelogd, inclusief de chauffeur-app."
                                                                  data-admin-confirm-title="Op afstand uitloggen"
                                                                  data-admin-confirm-label="Uitloggen">
                                                                @csrf
                                                                <button type="submit" class="kt-menu-link w-full text-left">
                                                                    <span class="kt-menu-icon">
                                                                        <i class="ki-filled ki-exit-right-corner"></i>
                                                                    </span>
                                                                    <span class="kt-menu-title">Uitloggen</span>
                                                                </button>
                                                            </form>
                                                        </div>
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
                                                        @endif
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
                    <div class="kt-card-footer admin-datatable-footer text-secondary-foreground text-sm font-medium">
                        <div class="admin-datatable-footer__perpage flex items-center gap-2">
                            Toon
                            <select class="kt-select w-24" data-admin-datatable-size="true" data-kt-select="" name="perpage">
                            </select>
                            per pagina
                        </div>
                        <div class="admin-datatable-footer__pagination">
                            <div class="kt-datatable-pagination" data-admin-datatable-pagination="true"></div>
                        </div>
                        <span class="admin-datatable-footer__info" data-admin-datatable-info="true"></span>
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

@can('delete-users')
<form method="POST"
      action="{{ route('admin.users.bulk-destroy') }}"
      id="users-bulk-delete-form"
      class="hidden">
    @csrf
    @method('DELETE')
    <div id="users-bulk-delete-ids"></div>
</form>
@endcan

@push('scripts')
<script>
(function () {
    function copyWithSelection(text) {
        var span = document.createElement('span');
        span.textContent = text;
        span.style.cssText = 'position:fixed;top:0;left:0;white-space:pre;';
        document.body.appendChild(span);
        var selection = window.getSelection();
        var range = document.createRange();
        range.selectNodeContents(span);
        selection.removeAllRanges();
        selection.addRange(range);
        var copied = false;
        try {
            copied = document.execCommand('copy');
        } catch (err) {
            copied = false;
        }
        selection.removeAllRanges();
        span.remove();
        return copied;
    }

    function copyFromVisibleEmail(button) {
        var label = button.parentElement ? button.parentElement.querySelector('.user-email-text') : null;
        if (!label) {
            return false;
        }
        var selection = window.getSelection();
        var range = document.createRange();
        range.selectNodeContents(label);
        selection.removeAllRanges();
        selection.addRange(range);
        var copied = false;
        try {
            copied = document.execCommand('copy');
        } catch (err) {
            copied = false;
        }
        selection.removeAllRanges();
        return copied;
    }

    function markEmailCopied(button) {
        var icon = button.querySelector('i');
        button.setAttribute('title', 'Gekopieerd');
        button.setAttribute('aria-label', 'Gekopieerd');
        if (icon) {
            icon.classList.remove('ki-copy');
            icon.classList.add('ki-check');
        }
        window.setTimeout(function () {
            button.setAttribute('title', 'E-mailadres kopiëren');
            button.setAttribute('aria-label', 'E-mailadres kopiëren');
            if (icon) {
                icon.classList.remove('ki-check');
                icon.classList.add('ki-copy');
            }
        }, 1500);
    }

    document.addEventListener('click', function (e) {
        var button = e.target && e.target.closest ? e.target.closest('.user-email-copy') : null;
        if (!button) {
            return;
        }
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();
        var text = button.getAttribute('data-copy-text') || '';
        if (!text) {
            return;
        }

        try {
            window.focus();
            button.focus();
        } catch (err) {}

        var clipboardPromise = null;
        if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
            clipboardPromise = navigator.clipboard.writeText(text);
        }

        var copiedNow = copyFromVisibleEmail(button) || copyWithSelection(text);
        if (copiedNow) {
            markEmailCopied(button);
        }

        if (clipboardPromise) {
            clipboardPromise.then(function () {
                markEmailCopied(button);
            }).catch(function () {
                if (!copiedNow) {
                    window.prompt('Kopieer dit e-mailadres:', text);
                }
            });
        } else if (!copiedNow) {
            window.prompt('Kopieer dit e-mailadres:', text);
        }
    }, true);
})();
</script>
<script>
(function () {
    const tableRoot = document.getElementById('users_table');
    const selectAll = document.getElementById('users-select-all');
    const bulkBtn = document.getElementById('users-bulk-delete');
    const bulkForm = document.getElementById('users-bulk-delete-form');
    const bulkIds = document.getElementById('users-bulk-delete-ids');
    const countEl = document.querySelector('[data-users-selected-count]');
    if (!tableRoot || !selectAll || !bulkBtn || !bulkForm) {
        return;
    }

    function rowCheckboxes() {
        return Array.from(tableRoot.querySelectorAll('.user-row-checkbox'));
    }

    function visibleRowCheckboxes() {
        return rowCheckboxes().filter(function (cb) {
            const row = cb.closest('tr');
            if (!row || row.hidden) {
                return false;
            }
            const style = window.getComputedStyle(row);
            return style.display !== 'none' && style.visibility !== 'hidden';
        });
    }

    function selectedCheckboxes() {
        return rowCheckboxes().filter(function (cb) { return cb.checked; });
    }

    function syncUsersBulkSelection() {
        const visible = visibleRowCheckboxes();
        const selectedVisible = visible.filter(function (cb) { return cb.checked; });
        const selected = selectedCheckboxes();
        selectAll.checked = visible.length > 0 && selectedVisible.length === visible.length;
        selectAll.indeterminate = selectedVisible.length > 0 && selectedVisible.length < visible.length;
        if (countEl) {
            countEl.textContent = String(selected.length);
        }
        const show = selected.length > 0;
        bulkBtn.hidden = !show;
        bulkBtn.classList.toggle('hidden', !show);
    }

    window.syncUsersBulkSelection = syncUsersBulkSelection;

    function fillBulkForm() {
        if (!bulkIds) {
            return;
        }
        bulkIds.innerHTML = '';
        selectedCheckboxes().forEach(function (cb) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'user_ids[]';
            input.value = cb.value;
            bulkIds.appendChild(input);
        });
    }

    selectAll.addEventListener('change', function () {
        const checked = selectAll.checked;
        visibleRowCheckboxes().forEach(function (cb) {
            cb.checked = checked;
        });
        syncUsersBulkSelection();
    });

    tableRoot.addEventListener('change', function (e) {
        if (e.target && e.target.classList.contains('user-row-checkbox')) {
            syncUsersBulkSelection();
        }
    });

    bulkBtn.addEventListener('click', function (e) {
        e.preventDefault();
        const selected = selectedCheckboxes();
        if (!selected.length) {
            return;
        }
        const count = selected.length;
        const message = count === 1
            ? 'Weet je zeker dat je deze gebruiker wilt verwijderen? Dit kan niet ongedaan worden gemaakt.'
            : 'Weet je zeker dat je ' + count + ' gebruikers wilt verwijderen? Dit kan niet ongedaan worden gemaakt.';
        if (!window.confirm(message)) {
            return;
        }
        fillBulkForm();
        bulkForm.submit();
    });

    syncUsersBulkSelection();
})();
</script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Replace "of" with "van" in pagination info
        function replaceOfWithVan() {
            const infoSpan = document.querySelector('[data-admin-datatable-info="true"]');
            if (infoSpan && infoSpan.textContent.includes(' of ')) {
                infoSpan.textContent = infoSpan.textContent.replace(' of ', ' van ');
            }
        }
        
        // Initial replacement
        replaceOfWithVan();
        
        // Watch for changes in the info span
        const infoSpan = document.querySelector('[data-admin-datatable-info="true"]');
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
                    
                    // Update account status badge; laat chauffeur-/contract-app status staan
                    const accountStatus = userRow.querySelector('.user-account-status');
                    if (accountStatus) {
                        accountStatus.innerHTML = data.is_active
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
        
        // Make table rows clickable (except actions column) - robust event delegation
        // Use container-level delegation to survive datatable updates
        function setupUserRowClicks() {
            const usersTable = document.getElementById('users_table');
            if (!usersTable) {
                return;
            }
            
            // Remove existing handler if it exists
            if (usersTable._rowClickHandler) {
                usersTable.removeEventListener('click', usersTable._rowClickHandler, true);
            }
            
            // Create robust click handler
            usersTable._rowClickHandler = function(e) {
                const row = e.target.closest('tr.user-row');
                if (!row) {
                    return;
                }
                
                // Don't navigate if clicking on actions column or menu
                const clickedElement = e.target;
                const actionsTd = row.querySelector('td:last-child');
                const isInActionsColumn = actionsTd && (actionsTd.contains(clickedElement) || clickedElement === actionsTd);
                const isInMenu = clickedElement.closest('.kt-menu') || clickedElement.closest('[data-kt-menu]');
                const isCopyEmail = !!clickedElement.closest('.user-email-copy');
                const isCheckbox = !!clickedElement.closest('[data-no-row-link], .admin-table__check-col, .user-row-checkbox, input[type="checkbox"]');
                const isButton = clickedElement.tagName === 'BUTTON' || clickedElement.closest('button');
                const isLink = clickedElement.tagName === 'A' || clickedElement.closest('a');
                
                if (isCopyEmail || isCheckbox || isInActionsColumn || isInMenu || isButton || isLink) {
                    return;
                }
                
                // Get user ID - try multiple methods
                let userId = null;
                
                // Method 1: Try data attribute on row
                userId = row.getAttribute('data-user-id');
                
                // Method 2: Try name link with data attribute
                if (!userId || userId === 'null' || userId === '') {
                    const nameLink = row.querySelector('a[data-user-id]');
                    if (nameLink) {
                        userId = nameLink.getAttribute('data-user-id');
                    }
                }
                
                // Method 3: Try to extract from any link in the row
                if (!userId || userId === 'null' || userId === '') {
                    const viewLink = row.querySelector('a[href*="/admin/users/"]');
                    if (viewLink) {
                        const href = viewLink.getAttribute('href');
                        const match = href.match(/\/admin\/users\/(\d+)/);
                        if (match && match[1]) {
                            userId = match[1];
                        }
                    }
                }
                
                if (userId && userId !== 'null' && userId !== '' && userId !== null && userId !== undefined) {
                    e.stopPropagation();
                    e.stopImmediatePropagation();
                    e.preventDefault();
                    window.location.href = '/admin/users/' + userId;
                }
            };
            
            // Add event listener with capture phase on container
            usersTable.addEventListener('click', usersTable._rowClickHandler, true);
        }
        
        // Initialize immediately
        setupUserRowClicks();
        
        // Re-initialize after delays in case datatable initializes later
        setTimeout(setupUserRowClicks, 100);
        setTimeout(setupUserRowClicks, 500);
        setTimeout(setupUserRowClicks, 1000);
        
        // Watch for table changes
        const usersTable = document.getElementById('users_table');
        if (usersTable) {
            const observer = new MutationObserver(function() {
                setupUserRowClicks();
            });
            observer.observe(usersTable, { childList: true, subtree: true });
        }
    });
</script>
@endpush

@push('styles')
<style>
    #content #users_table .admin-fluid-table th.admin-table__check-col,
    #content #users_table .admin-fluid-table td.admin-table__check-col {
        width: 2.75rem !important;
        min-width: 2.75rem !important;
        max-width: 2.75rem !important;
        padding-inline: 0.375rem !important;
        text-align: center !important;
        vertical-align: middle !important;
    }
    #users_table .admin-table__check-col .kt-label {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        min-height: 2rem;
        margin: 0;
    }
    #users_table .admin-table__check-col .kt-checkbox {
        margin: 0;
    }
    #users_table .users-check-col-head {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 0.125rem;
        width: 100%;
    }
    #users_table #users-bulk-delete {
        border: 0 !important;
        box-shadow: none !important;
        background-color: transparent !important;
        width: 100%;
        min-width: 0;
        max-width: 100%;
        height: auto;
        min-height: 0;
        padding: 0 !important;
        margin: 0;
        gap: 0.05rem;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        font-variant-numeric: tabular-nums;
        color: var(--destructive, #dc2626) !important;
    }
    #users_table #users-bulk-delete:not(.hidden):not([hidden]) {
        display: inline-flex !important;
    }
    #users_table #users-bulk-delete:hover,
    #users_table #users-bulk-delete:focus,
    #users_table #users-bulk-delete:focus-visible,
    #users_table #users-bulk-delete:active {
        background-color: transparent !important;
        color: var(--destructive, #dc2626) !important;
    }
    #users_table #users-bulk-delete i,
    #users_table #users-bulk-delete:hover i,
    #users_table #users-bulk-delete:focus i,
    #users_table #users-bulk-delete:active i {
        font-size: 1.25rem !important;
        line-height: 1 !important;
        color: inherit !important;
    }
    #users_table #users-bulk-delete .users-bulk-delete-count {
        font-size: 0.625rem;
        line-height: 1;
    }
    #users_table #users-bulk-delete.hidden {
        display: none !important;
    }

    #content #users_table .admin-fluid-table.has-user-check th:nth-child(2),
    #content #users_table .admin-fluid-table.has-user-check td:nth-child(2),
    #content #users_table .admin-fluid-table:not(.has-user-check) th:nth-child(1),
    #content #users_table .admin-fluid-table:not(.has-user-check) td:nth-child(1) {
        width: 32%;
    }

    #content #users_table .admin-fluid-table.has-user-check th:nth-child(3),
    #content #users_table .admin-fluid-table.has-user-check td:nth-child(3),
    #content #users_table .admin-fluid-table:not(.has-user-check) th:nth-child(2),
    #content #users_table .admin-fluid-table:not(.has-user-check) td:nth-child(2) {
        width: 16%;
    }

    #content #users_table .admin-fluid-table.has-user-check th:nth-child(4),
    #content #users_table .admin-fluid-table.has-user-check td:nth-child(4),
    #content #users_table .admin-fluid-table:not(.has-user-check) th:nth-child(3),
    #content #users_table .admin-fluid-table:not(.has-user-check) td:nth-child(3) {
        width: 16%;
    }

    #content #users_table .admin-fluid-table.has-user-check th:nth-child(5),
    #content #users_table .admin-fluid-table.has-user-check td:nth-child(5),
    #content #users_table .admin-fluid-table:not(.has-user-check) th:nth-child(4),
    #content #users_table .admin-fluid-table:not(.has-user-check) td:nth-child(4) {
        width: 16%;
    }

    #content #users_table .admin-fluid-table.has-user-check th:nth-child(6),
    #content #users_table .admin-fluid-table.has-user-check td:nth-child(6),
    #content #users_table .admin-fluid-table:not(.has-user-check) th:nth-child(5),
    #content #users_table .admin-fluid-table:not(.has-user-check) td:nth-child(5) {
        width: 12%;
    }

    #content #users_table .admin-fluid-table th:last-child,
    #content #users_table .admin-fluid-table td:last-child,
    #content #users_table .users-table__actions-col {
        width: 4.5rem !important;
        min-width: 4.5rem !important;
        max-width: 4.5rem !important;
        padding-inline: 0.375rem !important;
        text-align: center !important;
        vertical-align: middle !important;
        white-space: nowrap;
        overflow: visible !important;
    }

    #users_table .users-table-wrap {
        overflow-x: auto !important;
        overflow-y: visible !important;
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
    
    /* Table row hover styling (same as demo) */
    .user-row {
        cursor: pointer !important;
    }
    .user-email-copy {
        cursor: pointer;
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
            }, true);
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
