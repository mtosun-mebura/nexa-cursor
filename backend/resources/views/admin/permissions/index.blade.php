@extends('admin.layouts.app')

@section('title', 'Rechten Beheer')

@section('content')


<!-- Dashboard Stats -->
<div class="stats-cards">
    <div class="stat-card">
        <div class="stat-number" style="background: linear-gradient(135deg, #2196F3 0%, #42a5f5 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">{{ $stats['total_permissions'] }}</div>
        <div class="stat-label">Totaal Rechten</div>
    </div>

    <div class="stat-card">
        <div class="stat-number" style="background: linear-gradient(135deg, #4caf50 0%, #66bb6a 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">{{ $stats['assigned_permissions'] }}</div>
        <div class="stat-label">Toegewezen</div>
    </div>

    <div class="stat-card">
        <div class="stat-number" style="background: linear-gradient(135deg, #f44336 0%, #ef5350 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">{{ $stats['unassigned_permissions'] }}</div>
        <div class="stat-label">Niet Toegewezen</div>
    </div>

    <div class="stat-card">
        <div class="stat-number" style="background: linear-gradient(135deg, #9c27b0 0%, #ba68c8 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">{{ $stats['permissions_by_group']->count() }}</div>
        <div class="stat-label">Groepen</div>
    </div>
</div>

<!-- Permissions by Type -->
<div class="kt-card">
    <div class="kt-card-header flex justify-between items-center">
        <h6 class="mb-0">
            <i class="fas fa-chart-pie me-2"></i> Rechten per Type
        </h6>
    </div>
    <div class="kt-card-content">
        <div class="grid gap-5 lg:gap-7.5">
            <div class="col-md-3 mb-3">
                <div class="flex items-center p-3" style="background: var(--light-bg); border-radius: var(--border-radius); box-shadow: var(--shadow-light); margin: 10px 10px 0 10px;">
                    <div class="flex-shrink-0">
                        <div class="stat-icon" style="width: 40px; height: 40px; font-size: 16px;">
                            <i class="fas fa-eye"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="mb-1" style="font-size: 14px;">View</h6>
                        <small style="font-size: 11px; color: var(--medium-text);">{{ $stats['permissions_by_type']['view'] ?? 0 }} rechten</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="flex items-center p-3" style="background: var(--light-bg); border-radius: var(--border-radius); box-shadow: var(--shadow-light); margin: 10px 10px 0 10px;">
                    <div class="flex-shrink-0">
                        <div class="stat-icon" style="width: 40px; height: 40px; font-size: 16px;">
                            <i class="fas fa-plus"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="mb-1" style="font-size: 14px;">Create</h6>
                        <small style="font-size: 11px; color: var(--medium-text);">{{ $stats['permissions_by_type']['create'] ?? 0 }} rechten</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="flex items-center p-3" style="background: var(--light-bg); border-radius: var(--border-radius); box-shadow: var(--shadow-light); margin: 10px 10px 0 10px;">
                    <div class="flex-shrink-0">
                        <div class="stat-icon" style="width: 40px; height: 40px; font-size: 16px;">
                            <i class="fas fa-edit"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="mb-1" style="font-size: 14px;">Edit</h6>
                        <small style="font-size: 11px; color: var(--medium-text);">{{ $stats['permissions_by_type']['edit'] ?? 0 }} rechten</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="flex items-center p-3" style="background: var(--light-bg); border-radius: var(--border-radius); box-shadow: var(--shadow-light); margin: 10px 10px 0 10px;">
                    <div class="flex-shrink-0">
                        <div class="stat-icon" style="width: 40px; height: 40px; font-size: 16px;">
                            <i class="fas fa-trash"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="mb-1" style="font-size: 14px;">Delete</h6>
                        <small style="font-size: 11px; color: var(--medium-text);">{{ $stats['permissions_by_type']['delete'] ?? 0 }} rechten</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Top Permissions by Usage -->
@if($stats['most_used_permissions']->count() > 0)
<div class="kt-card">
    <div class="kt-card-header flex justify-between items-center">
        <h6 class="mb-0">
            <i class="fas fa-chart-bar me-2"></i> Meest Gebruikte Rechten
        </h6>
    </div>
    <div class="kt-card-content">
        <div class="grid gap-5 lg:gap-7.5">
            @foreach($stats['most_used_permissions'] as $permission)
            <div class="col-md-4 col-lg-2 mb-3">
                <div class="flex items-center p-3" style="background: var(--light-bg); border-radius: var(--border-radius); box-shadow: var(--shadow-light); margin: 10px 10px 0 10px;">
                    <div class="flex-shrink-0">
                        <div class="stat-icon" style="width: 40px; height: 40px; font-size: 16px;">
                            <i class="fas fa-key"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="mb-1" style="font-size: 14px;">{{ ucfirst(str_replace('-', ' ', $permission->name)) }}</h6>
                        <small class="text-muted" style="font-size: 11px;">{{ $permission->roles_count }} rollen</small>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endif

<!-- Permissions List -->
<div class="kt-card">
    <div class="kt-card-header flex justify-between items-center">
        <h6 class="mb-0">
            <i class="fas fa-key me-2"></i> Rechten Beheer
        </h6>
        <div class="flex gap-2">
            <a href="{{ route('admin.permissions.bulk-create') }}" class="kt-btn kt-btn-success">
                <i class="fas fa-plus me-1"></i>
                Bulk Aanmaken
            </a>
            <a href="{{ route('admin.permissions.create') }}" class="kt-btn kt-btn-primary">
                <i class="fas fa-plus me-1"></i>
                Nieuw Recht
            </a>
        </div>
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
            <form method="GET" action="{{ route('admin.permissions.index') }}" id="filters-form">
                <div class="grid gap-5 lg:gap-7.5">
                    @if(auth()->user()->hasRole('super-admin'))
                        <!-- Super-admin: 5 kolommen over gehele breedte -->
                        <div class="lg:col-span-2">
                            <div class="filter-group">
                                <label class="filter-label">Type</label>
                                <select name="type" class="filter-select" onchange="this.form.submit()">
                                    <option value="">Alle types</option>
                                    <option value="view" {{ request('type') == 'view' ? 'selected' : '' }}>View</option>
                                    <option value="create" {{ request('type') == 'create' ? 'selected' : '' }}>Create</option>
                                    <option value="edit" {{ request('type') == 'edit' ? 'selected' : '' }}>Edit</option>
                                    <option value="delete" {{ request('type') == 'delete' ? 'selected' : '' }}>Delete</option>
                                </select>
                            </div>
                        </div>
                        <div class="lg:col-span-2">
                            <div class="filter-group">
                                <label class="filter-label">Module</label>
                                <select name="module" class="filter-select" onchange="this.form.submit()">
                                    <option value="">Alle modules</option>
                                    <option value="users" {{ request('module') == 'users' ? 'selected' : '' }}>Gebruikers</option>
                                    <option value="companies" {{ request('module') == 'companies' ? 'selected' : '' }}>Bedrijven</option>
                                    <option value="vacancies" {{ request('module') == 'vacancies' ? 'selected' : '' }}>Vacatures</option>
                                    <option value="categories" {{ request('module') == 'categories' ? 'selected' : '' }}>Categorieën</option>
                                    <option value="notifications" {{ request('module') == 'notifications' ? 'selected' : '' }}>Notificaties</option>
                                    <option value="matches" {{ request('module') == 'matches' ? 'selected' : '' }}>Matches</option>
                                    <option value="interviews" {{ request('module') == 'interviews' ? 'selected' : '' }}>Interviews</option>
                                </select>
                            </div>
                        </div>
                        <div class="lg:col-span-2">
                            <div class="filter-group">
                                <label class="filter-label">Gebruik</label>
                                <select name="usage" class="filter-select" onchange="this.form.submit()">
                                    <option value="">Alle rechten</option>
                                    <option value="used" {{ request('usage') == 'used' ? 'selected' : '' }}>Gebruikt</option>
                                    <option value="unused" {{ request('usage') == 'unused' ? 'selected' : '' }}>Ongebruikt</option>
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
                                <a href="{{ route('admin.permissions.index') }}" class="btn btn-outline-secondary w-100" style="height: 36px; display: flex; align-items: center; justify-content: center; gap: 6px; text-decoration: none; border-radius: var(--border-radius);">
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
                                    <option value="view" {{ request('type') == 'view' ? 'selected' : '' }}>View</option>
                                    <option value="create" {{ request('type') == 'create' ? 'selected' : '' }}>Create</option>
                                    <option value="edit" {{ request('type') == 'edit' ? 'selected' : '' }}>Edit</option>
                                    <option value="delete" {{ request('type') == 'delete' ? 'selected' : '' }}>Delete</option>
                                </select>
                            </div>
                        </div>
                        <div class="lg:col-span-3">
                            <div class="filter-group">
                                <label class="filter-label">Module</label>
                                <select name="module" class="filter-select" onchange="this.form.submit()">
                                    <option value="">Alle modules</option>
                                    <option value="users" {{ request('module') == 'users' ? 'selected' : '' }}>Gebruikers</option>
                                    <option value="companies" {{ request('module') == 'companies' ? 'selected' : '' }}>Bedrijven</option>
                                    <option value="vacancies" {{ request('module') == 'vacancies' ? 'selected' : '' }}>Vacatures</option>
                                    <option value="categories" {{ request('module') == 'categories' ? 'selected' : '' }}>Categorieën</option>
                                    <option value="notifications" {{ request('module') == 'notifications' ? 'selected' : '' }}>Notificaties</option>
                                    <option value="matches" {{ request('module') == 'matches' ? 'selected' : '' }}>Matches</option>
                                    <option value="interviews" {{ request('module') == 'interviews' ? 'selected' : '' }}>Interviews</option>
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
                                <a href="{{ route('admin.permissions.index') }}" class="btn btn-outline-secondary w-100" style="height: 36px; display: flex; align-items: center; justify-content: center; gap: 6px; text-decoration: none; border-radius: var(--border-radius);">
                                    <i class="fas fa-times"></i>
                                    Filter wissen
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            </form>
        </div>

        @if($permissions->count() > 0)
            @foreach($permissions as $group => $groupPermissions)
                <div class="module-section">
                    <h6 class="module-title">
                        <i class="fas fa-folder material-icon"></i>
                        {{ ucfirst($group) }} ({{ $groupPermissions->count() }})
                    </h6>

                    <div class="grid gap-5 lg:gap-7.5">
                        @foreach($groupPermissions as $permission)
                            <div class="col-md-4 col-lg-2 mb-3">
                                <div class="permission-item">
                                    <div class="permission-name">
                                        <i class="fas fa-shield-alt me-2 text-primary"></i>
                                        {{ ucfirst(str_replace('-', ' ', $permission->name)) }}
                                    </div>
                                    <div class="permission-description">
                                        {{ $permission->description ?? 'Geen beschrijving' }}
                                    </div>
                                    <div class="permission-meta">
                                        <div>
                                            <span class="kt-badge kt-badge-info">{{ $permission->roles->count() }} rollen</span>
                                        </div>
                                        <div class="action-buttons">
                                            <a href="{{ route('admin.permissions.show', $permission) }}"
                                               class="action-btn action-btn-info"
                                               title="Bekijken">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('admin.permissions.edit', $permission) }}"
                                               class="action-btn action-btn-warning"
                                               title="Bewerken">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            @if($permission->roles->count() === 0)
                                                <form action="{{ route('admin.permissions.destroy', $permission) }}"
                                                      method="POST"
                                                      style="display: inline;"
                                                      onsubmit="return confirm('Weet je zeker dat je dit recht wilt verwijderen?')">
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
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        @else
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <p>Geen rechten gevonden</p>
                <a href="{{ route('admin.permissions.create') }}" class="kt-btn kt-btn-primary mt-3">
                    <i class="fas fa-plus me-1"></i>
                    Eerste Recht Aanmaken
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
