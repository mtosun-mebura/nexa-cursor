@extends('admin.layouts.app')

@section('title', 'Rollen Beheer')

@section('content')
<style>
    :root {
        --primary-color: #2196F3;
        --primary-dark: #1976D2;
        --primary-light: #BBDEFB;
        --accent-color: #FF4081;
        --success-color: #4CAF50;
        --warning-color: #FF9800;
        --danger-color: #F44336;
        --info-color: #00BCD4;
        --secondary-color: #757575;
        --light-color: #f8f9fa;
        --dark-color: #212121;
        --border-color: #e9ecef;
        --shadow-light: 0 2px 10px rgba(0, 0, 0, 0.1);
        --shadow-medium: 0 4px 20px rgba(0, 0, 0, 0.15);
        --shadow-heavy: 0 8px 30px rgba(0, 0, 0, 0.2);
        --border-radius: 12px;
        --border-radius-small: 8px;
        --transition: all 0.3s ease;
    }

    /* Dashboard Stats Cards */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 24px;
        margin-bottom: 32px;
    }

    .stat-card {
        background: white;
        border-radius: var(--border-radius);
        box-shadow: var(--shadow-light);
        padding: 0px;
        transition: var(--transition);
        border: none;
        position: relative;
        overflow: hidden;
    }

    .stat-card:hover {
        box-shadow: var(--shadow-medium);
        transform: translateY(-2px);
    }

    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
    }

    .stat-card.blue::before {
        background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
    }

    .stat-card.green::before {
        background: linear-gradient(135deg, #4CAF50 0%, #388E3C 100%);
    }

    .stat-card.orange::before {
        background: linear-gradient(135deg, #FF9800 0%, #F57C00 100%);
    }

    .stat-card.purple::before {
        background: linear-gradient(135deg, #9C27B0 0%, #7B1FA2 100%);
    }

    .stat-card.red::before {
        background: linear-gradient(135deg, #F44336 0%, #D32F2F 100%);
    }

    .stat-card.teal::before {
        background: linear-gradient(135deg, #00BCD4 0%, #0097A7 100%);
    }

    .stat-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 16px;
    }

    .stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        color: white;
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
    }

    .stat-card.blue .stat-icon {
        background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
    }

    .stat-card.green .stat-icon {
        background: linear-gradient(135deg, #4CAF50 0%, #388E3C 100%);
    }

    .stat-card.orange .stat-icon {
        background: linear-gradient(135deg, #FF9800 0%, #F57C00 100%);
    }

    .stat-card.purple .stat-icon {
        background: linear-gradient(135deg, #9C27B0 0%, #7B1FA2 100%);
    }

    .stat-card.red .stat-icon {
        background: linear-gradient(135deg, #F44336 0%, #D32F2F 100%);
    }

    .stat-card.teal .stat-icon {
        background: linear-gradient(135deg, #00BCD4 0%, #0097A7 100%);
    }

    .stat-value {
        font-size: 2.5rem;
        font-weight: 700;
        color: var(--dark-color);
        margin-bottom: 8px;
        line-height: 1;
    }

    .stat-label {
        font-size: 0.9rem;
        color: var(--secondary-color);
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .stat-change {
        font-size: 0.8rem;
        color: var(--success-color);
        font-weight: 500;
    }

    /* Material Design Components */
    .material-card {
        background: white;
        border-radius: var(--border-radius);
        box-shadow: var(--shadow-light);
        border: none;
        margin-bottom: 24px;
        transition: var(--transition);
    }
    
    .material-card:hover {
        box-shadow: var(--shadow-medium);
    }
    
    .material-card .card-header {
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
        color: white;
        border-radius: var(--border-radius) var(--border-radius) 0 0;
        padding: 10px 24px;
        border: none;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .material-card .card-body {
        padding: 0px;
    }
    
    /* Filters Section */
    .filters-section {
        background: var(--light-color);
        padding: 10px 24px;
        border-bottom: 1px solid var(--border-color);
    }

    .filter-group {
        margin-bottom: 16px;
    }

    .filter-label {
        display: block;
        font-size: 12px;
        font-weight: 600;
        color: var(--secondary-color);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 8px;
    }

    .filter-select {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid var(--border-color);
        border-radius: var(--border-radius-small);
        background-color: white;
        font-size: 12px;
        color: var(--dark-color);
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
        color: var(--dark-color);
    }

    .filter-select option:checked {
        background: var(--primary-color);
        color: white;
    }

    /* Custom 16.66%-kolom voor 6 kolommen */
    .col-md-2 {
        flex: 0 0 16.666667%;
        max-width: 16.666667%;
    }
    
    .material-btn {
        border-radius: var(--border-radius-small);
        text-transform: uppercase;
        font-weight: 500;
        letter-spacing: 0.5px;
        padding: 6px 12px;
        border: none;
        transition: var(--transition);
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    
    .material-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
        text-decoration: none;
    }
    
    .material-btn-primary {
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
        color: white;
    }
    
    .material-btn-warning {
        background: linear-gradient(135deg, var(--warning-color) 0%, #F57C00 100%);
        color: white;
    }
    
    .material-btn-danger {
        background: linear-gradient(135deg, var(--danger-color) 0%, #D32F2F 100%);
        color: white;
    }
    
    .material-table {
        border-radius: var(--border-radius-small);
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }
    
    .material-table thead th {
        background: var(--light-color);
        border: none;
        font-weight: 600;
        color: #495057;
        padding: 12px 16px;
        text-transform: uppercase;
        font-size: 0.85rem;
        letter-spacing: 0.5px;
    }
    
    .material-table tbody td {
        padding: 12px 16px;
        border-bottom: 1px solid var(--border-color);
        vertical-align: middle;
    }
    
    .material-table tbody tr:hover {
        background: var(--light-color);
    }
    
    .material-badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .material-badge-info {
        background: linear-gradient(135deg, var(--info-color) 0%, #0097A7 100%);
        color: white;
    }

    .material-badge-success {
        background: linear-gradient(135deg, var(--success-color) 0%, #388E3C 100%);
        color: white;
    }

    .material-badge-warning {
        background: linear-gradient(135deg, var(--warning-color) 0%, #F57C00 100%);
        color: white;
    }

    .material-badge-danger {
        background: linear-gradient(135deg, var(--danger-color) 0%, #D32F2F 100%);
        color: white;
    }

    .material-badge-secondary {
        background: linear-gradient(135deg, var(--secondary-color) 0%, #616161 100%);
        color: white;
    }

    .action-buttons {
        display: flex;
        gap: 6px;
        flex-wrap: wrap;
    }
    
    .action-btn {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: var(--transition);
        box-shadow: var(--shadow-light);
        cursor: pointer;
        position: relative;
        overflow: hidden;
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
        transform: scale(1.1);
        box-shadow: var(--shadow-medium);
    }
    
    .action-btn-info {
        background: linear-gradient(135deg, var(--info-color) 0%, #42a5f5 100%);
        color: white;
    }
    
    .action-btn-warning {
        background: linear-gradient(135deg, var(--warning-color) 0%, #ffb74d 100%);
        color: white;
    }
    
    .action-btn-danger {
        background: linear-gradient(135deg, var(--danger-color) 0%, #ef5350 100%);
        color: white;
    }
    
    .action-btn-success {
        background: linear-gradient(135deg, var(--success-color) 0%, #66bb6a 100%);
        color: white;
    }

    .role-info {
        display: flex;
        flex-direction: column;
    }
    
    .role-name {
        font-weight: 600;
        color: var(--dark-color);
        margin-bottom: 4px;
        font-size: 16px;
    }
    
    .role-description {
        font-size: 12px;
        color: var(--secondary-color);
        display: flex;
        align-items: center;
        gap: 6px;
    }

    /* Pagination Styling */
    .pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 6px;
        margin-top: 24px;
    }

    .page-link {
        border-radius: var(--border-radius-small);
        border: 1px solid var(--border-color);
        padding: 6px 12px;
        color: var(--dark-color);
        text-decoration: none;
        transition: var(--transition);
        background: white;
    }

    .page-link:hover {
        background: var(--primary-color);
        color: white;
        border-color: var(--primary-color);
        text-decoration: none;
    }

    .page-item.active .page-link {
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
        border-color: var(--primary-color);
        color: white;
    }

    .page-item.disabled .page-link {
        background: var(--light-color);
        color: var(--secondary-color);
        border-color: var(--border-color);
    }

    .material-alert {
        border-radius: 8px;
        border: none;
        padding: 16px 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }
    
    .material-alert-success {
        background: linear-gradient(135deg, #E8F5E8 0%, #C8E6C9 100%);
        color: #2E7D32;
        border-left: 4px solid #4CAF50;
    }
    
    .material-alert-danger {
        background: linear-gradient(135deg, #FFEBEE 0%, #FFCDD2 100%);
        color: #C62828;
        border-left: 4px solid #F44336;
    }
    
    .material-icon {
        font-size: 1.2rem;
        margin-right: 8px;
    }
    
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #757575;
    }
    
    .empty-state i {
        font-size: 4rem;
        margin-bottom: 20px;
        opacity: 0.5;
    }
    
    .empty-state p {
        font-size: 1.1rem;
        margin: 0;
    }
</style>

<!-- Dashboard Stats -->
<div class="stats-grid">
    <div class="stat-card blue">
        <div class="stat-header">
            <div>
                <div class="stat-value">{{ $stats['total_roles'] }}</div>
                <div class="stat-label">Totaal Rollen</div>
            </div>
            <div class="stat-icon">
                <i class="fas fa-user-shield"></i>
            </div>
        </div>
    </div>

    <div class="stat-card green">
        <div class="stat-header">
            <div>
                <div class="stat-value">{{ $stats['system_roles'] }}</div>
                <div class="stat-label">Systeem Rollen</div>
            </div>
            <div class="stat-icon">
                <i class="fas fa-cogs"></i>
            </div>
        </div>
    </div>

    <div class="stat-card orange">
        <div class="stat-header">
            <div>
                <div class="stat-value">{{ $stats['custom_roles'] }}</div>
                <div class="stat-label">Aangepaste Rollen</div>
            </div>
            <div class="stat-icon">
                <i class="fas fa-user-edit"></i>
            </div>
        </div>
    </div>

    <div class="stat-card purple">
        <div class="stat-header">
            <div>
                <div class="stat-value">{{ $stats['total_users_with_roles'] }}</div>
                <div class="stat-label">Gebruikers met Rollen</div>
            </div>
            <div class="stat-icon">
                <i class="fas fa-users"></i>
            </div>
        </div>
    </div>
</div>

<!-- Top Rollen per Gebruik -->
@if($stats['roles_by_usage']->count() > 0)
<div class="material-card">
    <div class="card-header">
        <h4 class="mb-0">
            <i class="fas fa-chart-bar me-2"></i> Top Rollen per Gebruik
        </h4>
    </div>
    <div class="card-body">
        <div class="row">
            @foreach($stats['roles_by_usage'] as $role)
                @if($role->name !== 'super-admin' || auth()->user()->hasRole('super-admin'))
                <div class="col-md-6 col-lg-4 mb-3">
                    <div class="d-flex align-items-center p-3 bg-light rounded">
                        <div class="flex-shrink-0">
                            <div class="stat-icon" style="width: 40px; height: 40px; font-size: 16px;">
                                <i class="fas fa-user-shield"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-1">{{ ucfirst($role->name) }}</h6>
                            <small class="text-muted">{{ $role->users_count }} gebruikers</small>
                        </div>
                    </div>
                </div>
                @endif
            @endforeach
        </div>
    </div>
</div>
@endif

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="material-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-user-shield material-icon"></i>
                        Rollen Beheer
                    </h5>
                    <a href="{{ route('admin.roles.create') }}" class="material-btn material-btn-primary">
                        <i class="fas fa-plus me-1"></i>
                        Nieuwe Rol
                    </a>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="material-alert material-alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle material-icon"></i>
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="material-alert material-alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle material-icon"></i>
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <!-- Filters -->
                    <div class="filters-section">
                        <form method="GET" action="{{ route('admin.roles.index') }}" id="filters-form">
                            <div class="row">
                                @if(auth()->user()->hasRole('super-admin'))
                                    <!-- Super-admin: 5 kolommen over gehele breedte -->
                                    <div class="col-md-2">
                                        <div class="filter-group">
                                            <label class="filter-label">Type</label>
                                            <select name="type" class="filter-select" onchange="this.form.submit()">
                                                <option value="">Alle types</option>
                                                <option value="system" {{ request('type') == 'system' ? 'selected' : '' }}>Systeem</option>
                                                <option value="custom" {{ request('type') == 'custom' ? 'selected' : '' }}>Aangepast</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="filter-group">
                                            <label class="filter-label">Gebruikers</label>
                                            <select name="users" class="filter-select" onchange="this.form.submit()">
                                                <option value="">Alle rollen</option>
                                                <option value="with_users" {{ request('users') == 'with_users' ? 'selected' : '' }}>Met gebruikers</option>
                                                <option value="without_users" {{ request('users') == 'without_users' ? 'selected' : '' }}>Zonder gebruikers</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="filter-group">
                                            <label class="filter-label">Permissies</label>
                                            <select name="permissions" class="filter-select" onchange="this.form.submit()">
                                                <option value="">Alle rollen</option>
                                                <option value="with_permissions" {{ request('permissions') == 'with_permissions' ? 'selected' : '' }}>Met permissies</option>
                                                <option value="without_permissions" {{ request('permissions') == 'without_permissions' ? 'selected' : '' }}>Zonder permissies</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="filter-group">
                                            <label class="filter-label">Items per pagina</label>
                                            <select name="per_page" class="filter-select" onchange="this.form.submit()">
                                                <option value="15" {{ request('per_page', 15) == 15 ? 'selected' : '' }}>15</option>
                                                <option value="25" {{ request('per_page', 15) == 25 ? 'selected' : '' }}>25</option>
                                                <option value="50" {{ request('per_page', 15) == 50 ? 'selected' : '' }}>50</option>
                                                <option value="100" {{ request('per_page', 15) == 100 ? 'selected' : '' }}>100</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="filter-group">
                                            <label class="filter-label">&nbsp;</label>
                                            <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-secondary w-100" style="height: 36px; display: flex; align-items: center; justify-content: center; gap: 6px; text-decoration: none;">
                                                <i class="fas fa-times"></i>
                                                Filter wissen
                                            </a>
                                        </div>
                                    </div>
                                @else
                                    <!-- Non-super-admin: 4 kolommen over gehele breedte -->
                                    <div class="col-md-3">
                                        <div class="filter-group">
                                            <label class="filter-label">Type</label>
                                            <select name="type" class="filter-select" onchange="this.form.submit()">
                                                <option value="">Alle types</option>
                                                <option value="system" {{ request('type') == 'system' ? 'selected' : '' }}>Systeem</option>
                                                <option value="custom" {{ request('type') == 'custom' ? 'selected' : '' }}>Aangepast</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="filter-group">
                                            <label class="filter-label">Gebruikers</label>
                                            <select name="users" class="filter-select" onchange="this.form.submit()">
                                                <option value="">Alle rollen</option>
                                                <option value="with_users" {{ request('users') == 'with_users' ? 'selected' : '' }}>Met gebruikers</option>
                                                <option value="without_users" {{ request('users') == 'without_users' ? 'selected' : '' }}>Zonder gebruikers</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="filter-group">
                                            <label class="filter-label">Items per pagina</label>
                                            <select name="per_page" class="filter-select" onchange="this.form.submit()">
                                                <option value="15" {{ request('per_page', 15) == 15 ? 'selected' : '' }}>15</option>
                                                <option value="25" {{ request('per_page', 15) == 25 ? 'selected' : '' }}>25</option>
                                                <option value="50" {{ request('per_page', 15) == 50 ? 'selected' : '' }}>50</option>
                                                <option value="100" {{ request('per_page', 15) == 100 ? 'selected' : '' }}>100</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="filter-group">
                                            <label class="filter-label">&nbsp;</label>
                                            <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-secondary w-100" style="height: 36px; display: flex; align-items: center; justify-content: center; gap: 6px; text-decoration: none;">
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
                        <div class="table-responsive">
                            <table class="table material-table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Rol & Details</th>
                                        <th>Rechten</th>
                                        <th>Gebruikers</th>
                                        <th>Type</th>
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
                                                <span class="material-badge material-badge-info">{{ $role->permissions->count() }}</span>
                                            </td>
                                            <td>
                                                <span class="material-badge material-badge-secondary">{{ $role->users->count() }}</span>
                                            </td>
                                            <td>
                                                @if(in_array($role->name, ['super-admin', 'company-admin', 'staff', 'candidate']))
                                                    <span class="material-badge material-badge-warning">Systeem</span>
                                                @else
                                                    <span class="material-badge material-badge-success">Aangepast</span>
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
                            </table>
                        </div>
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
