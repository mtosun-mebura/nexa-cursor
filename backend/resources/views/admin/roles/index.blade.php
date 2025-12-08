@extends('admin.layouts.app')

@section('title', 'Rollen Beheer')

@section('content')


<!-- Status Statistieken -->
<div class="stats-cards">
    <div class="stat-card">
        <div class="stat-number" style="background: linear-gradient(135deg, #2196f3 0%, #64b5f6 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">{{ $stats['total_roles'] }}</div>
        <div class="stat-label">Totaal Rollen</div>
    </div>
    <div class="stat-card">
        <div class="stat-number" style="background: linear-gradient(135deg, #4caf50 0%, #81c784 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">{{ $stats['system_roles'] }}</div>
        <div class="stat-label">Systeem Rollen</div>
    </div>
    <div class="stat-card">
        <div class="stat-number" style="background: linear-gradient(135deg, #ff9800 0%, #ffb74d 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">{{ $stats['custom_roles'] }}</div>
        <div class="stat-label">Aangepaste Rollen</div>
    </div>
    <div class="stat-card">
        <div class="stat-number" style="background: linear-gradient(135deg, #9c27b0 0%, #ba68c8 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">{{ $stats['total_users_with_roles'] }}</div>
        <div class="stat-label">Gebruikers met Rollen</div>
    </div>
</div>

<!-- Top Rollen per Gebruik -->
@if($stats['roles_by_usage']->count() > 0)
<div class="kt-card">
    <div class="kt-card-header">
        <h5 class="mb-0">
            <i class="fas fa-chart-bar me-2"></i> Top Rollen per gebruik
        </h5>
    </div>
    <div class="kt-card-content">
        <div class="grid gap-5 lg:gap-7.5">
            @foreach($stats['roles_by_usage'] as $role)
                @if($role->name !== 'super-admin' || auth()->user()->hasRole('super-admin'))
                <div class="col-md-6 col-lg-4 mb-3">
                    <div class="flex items-center p-3" style="background: var(--light-bg); border-radius: var(--border-radius); box-shadow: var(--shadow-light);">
                        <div class="flex-shrink-0">
                            <div class="stat-icon" style="width: 40px; height: 40px; font-size: 16px;">
                                <i class="fas fa-user-shield"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-1" style="font-size: 14px;">{{ ucfirst($role->name) }}</h6>
                            <small class="text-muted" style="font-size: 11px;">{{ $role->users_count }} gebruikers</small>
                        </div>
                    </div>
                </div>
                @endif
            @endforeach
        </div>
    </div>
</div>
@endif

<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono mb-3">
                Rollen Beheer
            </h1>
        </div>
        <div class="flex items-center gap-2.5">
            <a href="{{ route(\'admin.\' . str_replace(\'admin.\', \'\', request()->route()->getName()) . \'.create\') }}" class="kt-btn kt-btn-primary">
                <i class="ki-filled ki-plus me-2"></i>
                Nieuw
            </a>
        </div>
    </div>
    <div class="grid gap-5 lg:gap-7.5">
        <div class="w-full">
            <div class="kt-card">
                <div class="kt-card-header flex justify-between items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-user-shield material-icon"></i>
                        Rollen Beheer
                    </h5>
                    <a href="{{ route('admin.roles.create') }}" class="kt-btn kt-btn-primary">
                        <i class="fas fa-plus me-1"></i>
                        Nieuwe Rol
                    </a>
                </div>
                <div class="kt-card-content">
                    @if(session('success'))
                        <div class="kt-alert kt-alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle material-icon"></i>
                            {{ session('success') }}
                            <button type="button" class="kt-btn kt-btn-sm kt-btn-icon" data-kt-dismiss="alert"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="kt-alert kt-alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle material-icon"></i>
                            {{ session('error') }}
                            <button type="button" class="kt-btn kt-btn-sm kt-btn-icon" data-kt-dismiss="alert"></button>
                        </div>
                    @endif

                    <!-- Filters -->
                    <div class="filters-section">
                        <form method="GET" action="{{ route('admin.roles.index') }}" id="filters-form">
                            <div class="grid gap-5 lg:gap-7.5">
                                @if(auth()->user()->hasRole('super-admin'))
                                    <!-- Super-admin: 5 kolommen over gehele breedte -->
                                    <div class="lg:col-span-2">
                                        <div class="filter-group">
                                            <label class="filter-label">Type</label>
                                            <select name="type" class="filter-select" onchange="this.form.submit()">
                                                <option value="">Alle types</option>
                                                <option value="system" {{ request('type') == 'system' ? 'selected' : '' }}>Systeem</option>
                                                <option value="custom" {{ request('type') == 'custom' ? 'selected' : '' }}>Aangepast</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="lg:col-span-2">
                                        <div class="filter-group">
                                            <label class="filter-label">Gebruikers</label>
                                            <select name="users" class="filter-select" onchange="this.form.submit()">
                                                <option value="">Alle rollen</option>
                                                <option value="with_users" {{ request('users') == 'with_users' ? 'selected' : '' }}>Met gebruikers</option>
                                                <option value="without_users" {{ request('users') == 'without_users' ? 'selected' : '' }}>Zonder gebruikers</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="lg:col-span-2">
                                        <div class="filter-group">
                                            <label class="filter-label">Permissies</label>
                                            <select name="permissions" class="filter-select" onchange="this.form.submit()">
                                                <option value="">Alle rollen</option>
                                                <option value="with_permissions" {{ request('permissions') == 'with_permissions' ? 'selected' : '' }}>Met permissies</option>
                                                <option value="without_permissions" {{ request('permissions') == 'without_permissions' ? 'selected' : '' }}>Zonder permissies</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="lg:col-span-2">
                                        <div class="filter-group">
                                            <label class="filter-label">Items per pagina</label>
                                            <select name="per_page" class="filter-select" onchange="this.form.submit()">
                                                <option value="5" {{ request('per_page', 15) == 5 ? 'selected' : '' }}>5</option>
                                                <option value="15" {{ request('per_page', 15) == 15 ? 'selected' : '' }}>15</option>
                                                <option value="25" {{ request('per_page', 15) == 25 ? 'selected' : '' }}>25</option>
                                                <option value="50" {{ request('per_page', 15) == 50 ? 'selected' : '' }}>50</option>
                                                <option value="100" {{ request('per_page', 15) == 100 ? 'selected' : '' }}>100</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="lg:col-span-2">
                                        <div class="filter-group">
                                            <label class="filter-label">&nbsp;</label>
                                            <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-secondary w-100" style="height: 36px; display: flex; align-items: center; justify-content: center; gap: 6px; text-decoration: none; border-radius: var(--border-radius);">
                                                <i class="fas fa-times"></i>
                                                Filter wissen
                                            </a>
                                        </div>
                                    </div>
                                @else
                                    <!-- Non-super-admin: 4 kolommen over gehele breedte -->
                                    <div class="lg:col-span-3">
                                        <div class="filter-group">
                                            <label class="filter-label">Type</label>
                                            <select name="type" class="filter-select" onchange="this.form.submit()">
                                                <option value="">Alle types</option>
                                                <option value="system" {{ request('type') == 'system' ? 'selected' : '' }}>Systeem</option>
                                                <option value="custom" {{ request('type') == 'custom' ? 'selected' : '' }}>Aangepast</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="lg:col-span-3">
                                        <div class="filter-group">
                                            <label class="filter-label">Gebruikers</label>
                                            <select name="users" class="filter-select" onchange="this.form.submit()">
                                                <option value="">Alle rollen</option>
                                                <option value="with_users" {{ request('users') == 'with_users' ? 'selected' : '' }}>Met gebruikers</option>
                                                <option value="without_users" {{ request('users') == 'without_users' ? 'selected' : '' }}>Zonder gebruikers</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="lg:col-span-3">
                                        <div class="filter-group">
                                            <label class="filter-label">Items per pagina</label>
                                            <select name="per_page" class="filter-select" onchange="this.form.submit()">
                                                <option value="5" {{ request('per_page', 15) == 5 ? 'selected' : '' }}>5</option>
                                                <option value="15" {{ request('per_page', 15) == 15 ? 'selected' : '' }}>15</option>
                                                <option value="25" {{ request('per_page', 15) == 25 ? 'selected' : '' }}>25</option>
                                                <option value="50" {{ request('per_page', 15) == 50 ? 'selected' : '' }}>50</option>
                                                <option value="100" {{ request('per_page', 15) == 100 ? 'selected' : '' }}>100</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="lg:col-span-3">
                                        <div class="filter-group">
                                            <label class="filter-label">&nbsp;</label>
                                            <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-secondary w-100" style="height: 36px; display: flex; align-items: center; justify-content: center; gap: 6px; text-decoration: none; border-radius: var(--border-radius);">
                                                <i class="fas fa-times"></i>
                                                Filter wissen
                                            </a>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </form>
                    </div>

                    @if($roles->count() > 0)
                        <div class="kt-table-responsive">
                            <kt-table class="material-kt-table">
                                <thead>
                                    <tr>
                                        <th class="sorkt-table {{ request('sort') == 'id' ? (request('order') == 'asc' ? 'sort-asc' : 'sort-desc') : '' }}" data-sort="id">
                                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'id', 'order' => request('sort') == 'id' && request('order') == 'asc' ? 'desc' : 'asc']) }}" style="text-decoration: none; color: inherit;">
                                                ID
                                            </a>
                                        </th>
                                        <th class="sorkt-table {{ request('sort') == 'name' ? (request('order') == 'asc' ? 'sort-asc' : 'sort-desc') : '' }}" data-sort="name">
                                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'name', 'order' => request('sort') == 'name' && request('order') == 'asc' ? 'desc' : 'asc']) }}" style="text-decoration: none; color: inherit;">
                                                Rol & Details
                                            </a>
                                        </th>
                                        <th>Rechten</th>
                                        <th class="sorkt-table {{ request('sort') == 'users_count' ? (request('order') == 'asc' ? 'sort-asc' : 'sort-desc') : '' }}" data-sort="users_count">
                                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'users_count', 'order' => request('sort') == 'users_count' && request('order') == 'asc' ? 'desc' : 'asc']) }}" style="text-decoration: none; color: inherit;">
                                                Gebruikers
                                            </a>
                                        </th>
                                        <th class="sorkt-table {{ request('sort') == 'guard_name' ? (request('order') == 'asc' ? 'sort-asc' : 'sort-desc') : '' }}" data-sort="guard_name">
                                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'guard_name', 'order' => request('sort') == 'guard_name' && request('order') == 'asc' ? 'desc' : 'asc']) }}" style="text-decoration: none; color: inherit;">
                                                Type
                                            </a>
                                        </th>
                                        <th>Acties</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($roles as $role)
                                        @if($role->name !== 'super-admin' || auth()->user()->hasRole('super-admin'))
                                        <tr>
                                            <td>{{ $role->id }}</td>
                                            <td>
                                                <div class="role-info">
                                                    <div class="role-name">{{ ucfirst($role->name) }}</div>
                                                    @if($role->description)
                                                        <div class="role-description">
                                                            <i class="fas fa-info-circle"></i>{{ $role->description }}
                                                        </div>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                <span class="kt-badge kt-badge-info">{{ $role->permissions->count() }}</span>
                                            </td>
                                            <td>
                                                <span class="kt-badge kt-badge-secondary">{{ $role->users->count() }}</span>
                                            </td>
                                            <td>
                                                @if(in_array($role->name, ['super-admin', 'company-admin', 'staff', 'candidate']))
                                                    <span class="kt-badge kt-badge-warning">Systeem</span>
                                                @else
                                                    <span class="kt-badge kt-badge-success">Aangepast</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <a href="{{ route('admin.roles.show', $role) }}"
                                                       class="action-btn action-btn-info"
                                                       title="Bekijken">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="{{ route('admin.roles.edit', $role) }}"
                                                       class="action-btn action-btn-warning"
                                                       title="Bewerken">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    @if(!in_array($role->name, ['super-admin', 'company-admin', 'staff', 'candidate']))
                                                        <form action="{{ route('admin.roles.destroy', $role) }}"
                                                              method="POST"
                                                              style="display: inline;"
                                                              onsubmit="return confirm('Weet je zeker dat je deze rol wilt verwijderen?')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit"
                                                                    class="action-btn action-btn-danger"
                                                                    title="Verwijderen">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                        @endif
                                    @endforeach
                                </tbody>
                            </kt-table>
                        </div>

                        <!-- Results Info -->
                        <div class="results-info-wrapper">
                            <div class="results-info">
                                <span class="results-text">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Toon {{ $roles->firstItem() ?? 0 }} tot {{ $roles->lastItem() ?? 0 }} van {{ $roles->total() }} resultaten
                                </span>
                            </div>
                        </div>

                        <!-- Pagination -->
                        @if($roles->hasPages())
                            <div class="pagination-wrapper">
                                <nav aria-label="Paginering">
                                    <ul class="pagination">
                                        {{-- Previous Page Link --}}
                                        @if ($roles->onFirstPage())
                                            <li class="page-item disabled">
                                                <span class="page-link">
                                                    <i class="fas fa-chevron-left"></i>
                                                </span>
                                            </li>
                                        @else
                                            <li class="page-item">
                                                <a class="page-link" href="{{ $roles->previousPageUrl() }}">
                                                    <i class="fas fa-chevron-left"></i>
                                                </a>
                                            </li>
                                        @endif

                                        {{-- Pagination Elements --}}
                                        @foreach ($roles->getUrlRange(1, $roles->lastPage()) as $page => $url)
                                            @if ($page == $roles->currentPage())
                                                <li class="page-item active">
                                                    <span class="page-link">{{ $page }}</span>
                                                </li>
                                            @else
                                                <li class="page-item">
                                                    <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                                                </li>
                                            @endif
                                        @endforeach

                                        {{-- Next Page Link --}}
                                        @if ($roles->hasMorePages())
                                            <li class="page-item">
                                                <a class="page-link" href="{{ $roles->nextPageUrl() }}">
                                                    <i class="fas fa-chevron-right"></i>
                                                </a>
                                            </li>
                                        @else
                                            <li class="page-item disabled">
                                                <span class="page-link">
                                                    <i class="fas fa-chevron-right"></i>
                                                </span>
                                            </li>
                                        @endif
                                    </ul>
                                </nav>
                            </div>
                        @endif
                    @else
                        <div class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <p>Geen rollen gevonden</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
