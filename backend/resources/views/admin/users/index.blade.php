@extends('admin.layouts.app')

@section('title', 'Gebruikers Beheer')

@section('content')
<style>
    :root {
        --primary-color: #2196f3;
        --primary-light: #64b5f6;
        --primary-dark: #1976d2;
        --secondary-color: #e3f2fd;
        --success-color: #4caf50;
        --warning-color: #ff9800;
        --danger-color: #f44336;
        --info-color: #2196f3;
        --light-bg: #fafafa;
        --dark-text: #212121;
        --medium-text: #757575;
        --border-color: #e0e0e0;
        --shadow-light: 0 2px 4px rgba(0,0,0,0.1);
        --shadow-medium: 0 4px 8px rgba(0,0,0,0.12);
        --shadow-heavy: 0 8px 16px rgba(0,0,0,0.15);
        --border-radius: 8px;
        --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .material-card {
        background: white;
        border-radius: var(--border-radius);
        box-shadow: var(--shadow-light);
        border: none;
        margin-bottom: 24px;
        transition: var(--transition);
        overflow: hidden;
    }
    
    .material-card:hover {
        box-shadow: var(--shadow-medium);
    }
    
    .material-card .card-header {
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
        color: white;
        border-radius: 0;
        padding: 10px 24px;
        border: none;
        position: relative;
        overflow: hidden;
    }
    
    .material-card .card-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(45deg, transparent 30%, rgba(255,255,255,0.1) 50%, transparent 70%);
        transform: translateX(-100%);
        transition: var(--transition);
    }
    
    .material-card .card-header:hover::before {
        transform: translateX(100%);
    }
    
    .material-card .card-body {
        padding: 0px;
    }
    
    .material-btn {
        border-radius: var(--border-radius);
        text-transform: uppercase;
        font-weight: 500;
        letter-spacing: 0.5px;
        padding: 6px 12px;
        border: none;
        transition: var(--transition);
        box-shadow: var(--shadow-light);
        position: relative;
        overflow: hidden;
        cursor: pointer;
        font-size: 12px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
    }
    
    .material-btn::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 0;
        height: 0;
        background: rgba(255,255,255,0.3);
        border-radius: 50%;
        transform: translate(-50%, -50%);
        transition: var(--transition);
    }
    
    .material-btn:hover::before {
        width: 300px;
        height: 300px;
    }
    
    .material-btn:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-medium);
        text-decoration: none;
    }
    
    .material-btn:active {
        transform: translateY(0);
        box-shadow: var(--shadow-light);
    }
    
    .material-btn-primary {
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
        color: white;
    }
    
    .material-btn-secondary {
        background: var(--light-bg);
        color: var(--dark-text);
        border: 1px solid var(--border-color);
    }
    
    .material-btn-secondary:hover {
        background: var(--secondary-color);
        color: var(--primary-color);
        transform: translateY(-2px);
        box-shadow: var(--shadow-medium);
    }
    
    .material-table {
        width: 100%;
        border-collapse: collapse;
        border-radius: var(--border-radius);
        overflow: hidden;
        box-shadow: var(--shadow-light);
        background: white;
    }
    
    .material-table thead th {
        background: var(--light-bg);
        border: none;
        font-weight: 600;
        color: var(--dark-text);
        padding: 12px 16px;
        text-transform: uppercase;
        font-size: 12px;
        letter-spacing: 1px;
        cursor: pointer;
        transition: var(--transition);
        position: relative;
        text-align: left;
    }
    
    .material-table thead th:hover {
        background: var(--secondary-color);
        color: var(--primary-color);
    }
    
    .material-table tbody td {
        padding: 12px 16px;
        border-bottom: 1px solid var(--border-color);
        vertical-align: middle;
        transition: var(--transition);
    }
    
    .material-table tbody tr {
        transition: var(--transition);
        background-color: white;
    }
    
    .material-table tbody tr:hover {
        background-color: var(--secondary-color) !important;
        transition: background-color 0.3s ease;
    }
    
    .material-badge {
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        display: inline-block;
        transition: var(--transition);
    }
    
    .material-badge-success {
        background: linear-gradient(135deg, #e8f5e8 0%, #c8e6c9 100%);
        color: #2e7d32;
        box-shadow: 0 2px 4px rgba(76, 175, 80, 0.2);
    }
    
    .material-badge-warning {
        background: linear-gradient(135deg, #fff3e0 0%, #ffe0b2 100%);
        color: #f57c00;
        box-shadow: 0 2px 4px rgba(255, 152, 0, 0.2);
    }
    
    .material-badge-info {
        background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
        color: #1565c0;
        box-shadow: 0 2px 4px rgba(33, 150, 243, 0.2);
    }
    
    .material-badge-primary {
        background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
        color: #1565c0;
        box-shadow: 0 2px 4px rgba(33, 150, 243, 0.2);
    }
    
    .material-badge-secondary {
        background: linear-gradient(135deg, #f5f5f5 0%, #e0e0e0 100%);
        color: #757575;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    
    .action-buttons {
        display: flex;
        gap: 8px;
        align-items: center;
        min-height: 40px;
    }
    
    .action-btn {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: var(--transition);
        text-decoration: none;
        font-size: 14px;
        position: relative;
        overflow: hidden;
        cursor: pointer;
        box-shadow: var(--shadow-light);
    }
    
    .action-btn::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 0;
        height: 0;
        background: rgba(255,255,255,0.3);
        border-radius: 50%;
        transform: translate(-50%, -50%);
        transition: var(--transition);
    }
    
    .action-btn:hover::before {
        width: 100px;
        height: 100px;
    }
    
    .action-btn:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-medium);
        text-decoration: none;
    }
    
    .action-btn:active {
        transform: translateY(0);
        box-shadow: var(--shadow-light);
    }
    
    .action-btn-info {
        background: linear-gradient(135deg, #2196f3 0%, #42a5f5 100%);
        color: white;
    }
    
    .action-btn-warning {
        background: linear-gradient(135deg, #ff9800 0%, #ffb74d 100%);
        color: white;
    }
    
    .action-btn-danger {
        background: linear-gradient(135deg, #f44336 0%, #ef5350 100%);
        color: white;
    }
    
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #6c757d;
    }
    
    .empty-state i {
        font-size: 4rem;
        margin-bottom: 20px;
        opacity: 0.5;
    }
    
    /* Statistics Cards */
    .stats-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 20px;
        margin-bottom: 32px;
    }
    
    .stat-card {
        background: white;
        border-radius: var(--border-radius);
        padding: 10px;
        box-shadow: var(--shadow-light);
        text-align: center;
        transition: var(--transition);
        position: relative;
        overflow: hidden;
    }
    
    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
    }
    
    .stat-card:hover {
        transform: translateY(-4px);
        box-shadow: var(--shadow-medium);
    }
    
    .stat-number {
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 0px;
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    
    .stat-label {
        font-size: 0.8rem;
        color: var(--medium-text);
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-top: 4px;
    }
    
    /* Filters Section */
    .filters-section {
        background: var(--light-bg);
        padding: 16px 24px;
        border-bottom: 1px solid var(--border-color);
    }
    
    .filter-group {
        margin-bottom: 0;
    }
    
    .filter-label {
        display: block;
        font-size: 0.75rem;
        font-weight: 600;
        color: var(--dark-text);
        margin-bottom: 4px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .filter-select {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid var(--border-color);
        border-radius: var(--border-radius);
        background-color: white;
        font-size: 12px;
        color: var(--dark-text);
        transition: var(--transition);
        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right 12px center;
        background-size: 16px;
        padding-right: 40px;
    }
    
    .filter-select:focus {
        outline: none;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(33, 150, 243, 0.1);
    }
    
    .filter-select option {
        padding: 8px;
        background-color: white;
        color: var(--dark-text);
    }
    
    .filter-select option:checked {
        background: var(--primary-color);
        color: white;
    }
    
    /* Results Info */
    .results-info-wrapper {
        padding: 12px 24px;
        background: var(--light-bg);
        border-top: 1px solid var(--border-color);
        border-bottom: 1px solid var(--border-color);
    }
    
    .results-info {
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .results-text {
        font-size: 0.875rem;
        color: var(--medium-text);
        display: flex;
        align-items: center;
    }
    
    .results-text i {
        color: var(--primary-color);
        font-size: 0.875rem;
    }

    /* Pagination */
    .pagination-wrapper {
        padding: 16px 24px;
        background: var(--light-bg);
        border-top: 1px solid var(--border-color);
    }
    
    .pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 6px;
        margin: 0;
        padding: 0;
        list-style: none;
    }
    
    .page-item {
        margin: 0;
    }
    
    .page-link {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        border: 1px solid var(--border-color);
        border-radius: var(--border-radius);
        background: white;
        color: var(--dark-text);
        text-decoration: none;
        font-weight: 500;
        font-size: 0.85rem;
        transition: var(--transition);
        box-shadow: var(--shadow-light);
    }
    
    .page-link:hover {
        background: var(--secondary-color);
        border-color: var(--primary-color);
        color: var(--primary-color);
        transform: translateY(-2px);
        box-shadow: var(--shadow-medium);
        text-decoration: none;
    }
    
    .page-item.active .page-link {
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
        border-color: var(--primary-color);
        color: white;
        box-shadow: var(--shadow-medium);
    }
    
    .page-item.disabled .page-link {
        background: #f5f5f5;
        color: #ccc;
        cursor: not-allowed;
        border-color: #e0e0e0;
    }
    
    .page-item.disabled .page-link:hover {
        transform: none;
        box-shadow: var(--shadow-light);
    }
    
    .user-info {
        display: flex;
        flex-direction: column;
    }
    
    .user-name {
        font-weight: 600;
        color: #495057;
    }
    
    .user-middle-name {
        font-size: 0.85rem;
        color: #6c757d;
        margin-top: 4px;
    }
    
    .user-email {
        color: #2196f3;
        text-decoration: none;
    }
    
    .user-email:hover {
        color: #1976d2;
        text-decoration: underline;
    }
    
    .user-phone {
        color: #2196f3;
        text-decoration: none;
    }
    
    .user-phone:hover {
        color: #1976d2;
        text-decoration: underline;
    }
    
    .user-company {
        background: #e3f2fd;
        color: #1976d2;
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 0.75rem;
    }
    
    .user-roles {
        display: flex;
        flex-wrap: wrap;
        gap: 4px;
    }
    
    .date-info {
        font-size: 0.85rem;
        color: #6c757d;
    }
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Status Statistieken -->
            <div class="stats-cards">
                <div class="stat-card">
                    <div class="stat-number" style="background: linear-gradient(135deg, #4caf50 0%, #66bb6a 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">{{ $users->where('is_active', true)->count() }}</div>
                    <div class="stat-label">Actief</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" style="background: linear-gradient(135deg, #f44336 0%, #ef5350 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">{{ $users->where('is_active', false)->count() }}</div>
                    <div class="stat-label">Inactief</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" style="background: linear-gradient(135deg, #ff9800 0%, #ffb74d 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">{{ $users->total() }}</div>
                    <div class="stat-label">Totaal</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" style="background: linear-gradient(135deg, #9c27b0 0%, #ba68c8 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">{{ $users->groupBy('role')->count() }}</div>
                    <div class="stat-label">Rollen</div>
                </div>
            </div>
            <div class="material-card">
                <!-- Header -->
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-users me-2"></i> Gebruikers Beheer
                    </h5>
                    <div class="d-flex gap-2">
                        <a href="{{ route('admin.users.create') }}" class="material-btn material-btn-primary">
                            <i class="fas fa-plus me-2"></i> Nieuwe Gebruiker
                        </a>
                    </div>
                </div>
                
                <!-- Filters -->
                <div class="filters-section">
                    <form method="GET" action="{{ route('admin.users.index') }}" id="filters-form">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="filter-group">
                                    <label class="filter-label">Status</label>
                                    <select name="status" class="filter-select" onchange="this.form.submit()">
                                        <option value="">Alle statussen</option>
                                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Actief</option>
                                        <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactief</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="filter-group">
                                    <label class="filter-label">Rol</label>
                                    <select name="role" class="filter-select" onchange="this.form.submit()">
                                        <option value="">Alle rollen</option>
                                        <option value="admin" {{ request('role') == 'admin' ? 'selected' : '' }}>Admin</option>
                                        <option value="user" {{ request('role') == 'user' ? 'selected' : '' }}>Gebruiker</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="filter-group">
                                    <label class="filter-label">Sorteren</label>
                                    <select name="sort" class="filter-select" onchange="this.form.submit()">
                                        <option value="first_name" {{ request('sort') == 'first_name' ? 'selected' : '' }}>Naam</option>
                                        <option value="email" {{ request('sort') == 'email' ? 'selected' : '' }}>E-mail</option>
                                        <option value="created_at" {{ request('sort') == 'created_at' ? 'selected' : '' }}>Aangemaakt</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="filter-group">
                                    <label class="filter-label">Items per pagina</label>
                                    <select name="per_page" class="filter-select" onchange="this.form.submit()">
                                        <option value="5" {{ request('per_page', 5) == 5 ? 'selected' : '' }}>5</option>
                                        <option value="15" {{ request('per_page', 5) == 15 ? 'selected' : '' }}>15</option>
                                        <option value="25" {{ request('per_page', 5) == 25 ? 'selected' : '' }}>25</option>
                                        <option value="50" {{ request('per_page', 5) == 50 ? 'selected' : '' }}>50</option>
                                        <option value="100" {{ request('per_page', 5) == 100 ? 'selected' : '' }}>100</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-1">
                                <div class="filter-group">
                                    <label class="filter-label">&nbsp;</label>
                                    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary w-100" style="height: 36px; display: flex; align-items: center; justify-content: center; gap: 6px; text-decoration: none;">
                                        <i class="fas fa-times"></i>
                                        Filter wissen
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <div class="table-responsive" style="width: 100%;">
                        <table class="material-table" style="width: 100%;">
                            <thead>
                                <tr>
                                    <th>Naam</th>
                                    <th>E-mail</th>
                                    <th>Telefoon</th>
                                    <th>Bedrijf</th>
                                    <th>Rollen</th>
                                    <th>Status</th>
                                    <th>Gemaakt op</th>
                                    <th>Acties</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($users as $user)
                                    <tr>
                                        <td>
                                            <div class="user-info">
                                                <div class="user-name">{{ $user->first_name }} {{ $user->last_name }}</div>
                                                @if($user->middle_name)
                                                    <div class="user-middle-name">{{ $user->middle_name }}</div>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <a href="mailto:{{ $user->email }}" class="user-email">{{ $user->email }}</a>
                                        </td>
                                        <td>
                                            @if($user->phone)
                                                <a href="tel:{{ $user->phone }}" class="user-phone">{{ $user->phone }}</a>
                                            @else
                                                <span class="text-muted">Geen telefoon</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($user->company)
                                                <span class="user-company">{{ $user->company->name }}</span>
                                            @else
                                                <span class="material-badge material-badge-secondary">Geen bedrijf</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="user-roles">
                                                @foreach($user->roles as $role)
                                                    @if($role->name === 'super-admin')
                                                        @if(auth()->user()->hasRole('super-admin'))
                                                            <span class="material-badge material-badge-info">{{ ucfirst(str_replace('-', ' ', $role->name)) }}</span>
                                                        @else
                                                            <span class="material-badge material-badge-secondary">Verborgen</span>
                                                        @endif
                                                    @else
                                                        <span class="material-badge material-badge-info">{{ ucfirst(str_replace('-', ' ', $role->name)) }}</span>
                                                    @endif
                                                @endforeach
                                            </div>
                                        </td>
                                        <td>
                                            @if($user->email_verified_at)
                                                <span class="material-badge material-badge-success">Geverifieerd</span>
                                            @else
                                                <span class="material-badge material-badge-warning">Niet geverifieerd</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="date-info">{{ $user->created_at->format('d-m-Y H:i') }}</div>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="{{ route('admin.users.show', $user) }}" class="action-btn action-btn-info" title="Bekijken">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('admin.users.edit', $user) }}" class="action-btn action-btn-warning" title="Bewerken">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="d-inline" onsubmit="return confirm('Weet je zeker dat je deze gebruiker wilt verwijderen?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="action-btn action-btn-danger" title="Verwijderen">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8">
                                            <div class="empty-state">
                                                <i class="fas fa-users"></i>
                                                <h5>Nog geen gebruikers</h5>
                                                <p>Er zijn nog geen gebruikers aangemaakt.</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Results Info -->
                    <div class="results-info-wrapper">
                        <div class="results-info">
                            <span class="results-text">
                                <i class="fas fa-info-circle me-2"></i>
                                Toon {{ $users->firstItem() ?? 0 }} tot {{ $users->lastItem() ?? 0 }} van {{ $users->total() }} resultaten
                            </span>
                        </div>
                    </div>

                    <!-- Pagination -->
                    @if($users->hasPages())
                        <div class="pagination-wrapper">
                            <nav aria-label="Paginering">
                                <ul class="pagination">
                                    {{-- Previous Page Link --}}
                                    @if ($users->onFirstPage())
                                        <li class="page-item disabled">
                                            <span class="page-link">
                                                <i class="fas fa-chevron-left"></i>
                                            </span>
                                        </li>
                                    @else
                                        <li class="page-item">
                                            <a class="page-link" href="{{ $users->previousPageUrl() }}">
                                                <i class="fas fa-chevron-left"></i>
                                            </a>
                                        </li>
                                    @endif

                                    {{-- Pagination Elements --}}
                                    @foreach ($users->getUrlRange(1, $users->lastPage()) as $page => $url)
                                        @if ($page == $users->currentPage())
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
                                    @if ($users->hasMorePages())
                                        <li class="page-item">
                                            <a class="page-link" href="{{ $users->nextPageUrl() }}">
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
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
