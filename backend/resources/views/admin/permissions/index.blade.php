@extends('admin.layouts.app')

@section('title', 'Rechten Beheer')

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
        --secondary-color: #E3F2FD;
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

    /* Dashboard Stats Cards */
    .stats-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 32px;
    }

    .stat-card {
        background: white;
        border-radius: var(--border-radius);
        box-shadow: var(--shadow-light);
        padding: 10px;
        text-align: center;
        transition: var(--transition);
        position: relative;
        overflow: hidden;
    }

    .stat-card:hover {
        transform: translateY(-4px);
        box-shadow: var(--shadow-medium);
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
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
        color: white;
        border-radius: var(--border-radius) var(--border-radius) 0 0;
        padding: 8px 24px;
        border: none;
        position: relative;
        overflow: hidden;
    }

    .material-card .card-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
        transition: var(--transition);
    }

    .material-card .card-header:hover::before {
        transform: translateX(100%);
    }

    .material-card .card-body {
        padding: 0px;
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
        font-size: 11px;
        font-weight: 600;
        color: var(--dark-text);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 4px;
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

    /* Custom 16.66%-kolom voor 6 kolommen */
    .col-md-2 {
        flex: 0 0 16.666667%;
        max-width: 16.666667%;
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

    .material-btn-success {
        background: linear-gradient(135deg, var(--success-color) 0%, #388E3C 100%);
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

    .material-btn-secondary {
        background: linear-gradient(135deg, var(--secondary-color) 0%, #616161 100%);
        color: white;
    }

    .material-badge {
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        box-shadow: var(--shadow-light);
        transition: var(--transition);
    }

    .material-badge:hover {
        transform: translateY(-1px);
        box-shadow: var(--shadow-medium);
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
        gap: 4px;
        flex-wrap: wrap;
        align-items: center;
        justify-content: flex-end;
    }

    .action-btn {
        width: 32px;
        height: 32px;
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
        font-size: 12px;
        text-decoration: none;
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

    /* Permission Items */
    .permission-item {
        background: white;
        border-radius: var(--border-radius);
        padding: 16px;
        margin-bottom: 16px;
        border: 1px solid var(--border-color);
        transition: var(--transition);
        box-shadow: var(--shadow-light);
        position: relative;
        overflow: hidden;
        height: 100%;
        display: flex;
        flex-direction: column;
    }

    .permission-item::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
    }

    .permission-item:hover {
        box-shadow: var(--shadow-medium);
        transform: translateY(-3px);
        border-color: var(--primary-color);
    }

    .permission-name {
        font-weight: 600;
        color: var(--dark-text);
        margin-bottom: 8px;
        font-size: 14px;
        display: flex;
        align-items: center;
        flex-wrap: wrap;
    }

    .permission-description {
        font-size: 12px;
        color: var(--dark-text);
        margin-bottom: 5px;
        line-height: 1.4;
        opacity: 0.8;
        flex-grow: 1;
    }

    .permission-meta {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        margin-top: auto;
        padding-top: 12px;
        border-top: 1px solid var(--border-color);
    }

    .module-section {
        margin-bottom: 0px;
        padding: 10px 10px;
    }

    .permissions-container {
        padding: 0 24px;
    }

    .module-title {
        font-size: 1rem;
        font-weight: 600;
        color: var(--dark-text);
        margin-bottom: 24px;
        padding: 12px 16px;
        background: linear-gradient(135deg, var(--light-bg) 0%, #f0f0f0 100%);
        border-radius: var(--border-radius);
        border-left: 4px solid var(--primary-color);
        display: flex;
        align-items: center;
        gap: 8px;
        box-shadow: var(--shadow-light);
    }

    .material-alert {
        border-radius: var(--border-radius);
        border: none;
        padding: 16px 20px;
        margin-bottom: 20px;
        box-shadow: var(--shadow-light);
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
<div class="material-card">
    <div class="card-header">
        <h6 class="mb-0">
            <i class="fas fa-chart-pie me-2"></i> Rechten per Type
        </h6>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3 mb-3">
                <div class="d-flex align-items-center p-3" style="background: var(--light-bg); border-radius: var(--border-radius); box-shadow: var(--shadow-light); margin: 10px 10px 0 10px;">
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
                <div class="d-flex align-items-center p-3" style="background: var(--light-bg); border-radius: var(--border-radius); box-shadow: var(--shadow-light); margin: 10px 10px 0 10px;">
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
                <div class="d-flex align-items-center p-3" style="background: var(--light-bg); border-radius: var(--border-radius); box-shadow: var(--shadow-light); margin: 10px 10px 0 10px;">
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
                <div class="d-flex align-items-center p-3" style="background: var(--light-bg); border-radius: var(--border-radius); box-shadow: var(--shadow-light); margin: 10px 10px 0 10px;">
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
<div class="material-card">
    <div class="card-header">
        <h6 class="mb-0">
            <i class="fas fa-chart-bar me-2"></i> Meest Gebruikte Rechten
        </h6>
    </div>
    <div class="card-body">
        <div class="row">
            @foreach($stats['most_used_permissions'] as $permission)
            <div class="col-md-4 col-lg-2 mb-3">
                <div class="d-flex align-items-center p-3" style="background: var(--light-bg); border-radius: var(--border-radius); box-shadow: var(--shadow-light); margin: 10px 10px 0 10px;">
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
<div class="material-card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0">
            <i class="fas fa-key me-2"></i> Rechten Beheer
        </h6>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.permissions.bulk-create') }}" class="material-btn material-btn-success">
                <i class="fas fa-plus me-1"></i>
                Bulk Aanmaken
            </a>
            <a href="{{ route('admin.permissions.create') }}" class="material-btn material-btn-primary">
                <i class="fas fa-plus me-1"></i>
                Nieuw Recht
            </a>
        </div>
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
            <form method="GET" action="{{ route('admin.permissions.index') }}" id="filters-form">
                <div class="row">
                    @if(auth()->user()->hasRole('super-admin'))
                        <!-- Super-admin: 5 kolommen over gehele breedte -->
                        <div class="col-md-2">
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
                        <div class="col-md-2">
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
                        <div class="col-md-2">
                            <div class="filter-group">
                                <label class="filter-label">Gebruik</label>
                                <select name="usage" class="filter-select" onchange="this.form.submit()">
                                    <option value="">Alle rechten</option>
                                    <option value="used" {{ request('usage') == 'used' ? 'selected' : '' }}>Gebruikt</option>
                                    <option value="unused" {{ request('usage') == 'unused' ? 'selected' : '' }}>Ongebruikt</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
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
                        <div class="col-md-2">
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
                        <div class="col-md-3">
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
                        <div class="col-md-3">
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
                        <div class="col-md-3">
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
                        <div class="col-md-3">
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

                    <div class="row">
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
                                            <span class="material-badge material-badge-info">{{ $permission->roles->count() }} rollen</span>
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
                <a href="{{ route('admin.permissions.create') }}" class="material-btn material-btn-primary mt-3">
                    <i class="fas fa-plus me-1"></i>
                    Eerste Recht Aanmaken
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
