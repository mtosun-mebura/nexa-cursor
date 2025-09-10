@extends('admin.layouts.app')

@section('title', 'Categorieën Beheer')

@section('content')
<style>
    :root {
        --primary-color: #ff9800;
        --primary-light: #ffb74d;
        --primary-dark: #f57c00;
        --secondary-color: #fff3e0;
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
        background-color: #fff3e0 !important;
        transition: background-color 0.3s ease;
    }
    
    .status-badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        display: inline-block;
        min-width: 100px;
        text-align: center;
        box-shadow: var(--shadow-light);
        transition: var(--transition);
    }
    
    .status-badge:hover {
        transform: scale(1.05);
        box-shadow: var(--shadow-medium);
    }
    
    .status-active {
        background: linear-gradient(135deg, #e8f5e8 0%, #81c784 100%);
        color: #388e3c;
        border: 2px solid #81c784;
    }
    
    .status-inactive {
        background: linear-gradient(135deg, #ffcdd2 0%, #e57373 100%);
        color: #d32f2f;
        border: 2px solid #e57373;
    }
    
    .action-buttons {
        display: flex;
        gap: 6px;
        flex-wrap: wrap;
        justify-content: flex-start;
        min-width: 120px;
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
        transform: scale(1.05);
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
    
    .category-info {
        display: flex;
        flex-direction: column;
    }
    
    .category-name {
        font-weight: 600;
        color: var(--dark-text);
        margin-bottom: 4px;
        font-size: 16px;
    }
    
    .category-description {
        font-size: 12px;
        color: var(--medium-text);
        display: flex;
        align-items: center;
        gap: 6px;
    }
    
    .category-group {
        background: linear-gradient(135deg, #fff3e0 0%, #ffe0b2 100%);
        color: #f57c00;
        padding: 6px 12px;
        border-radius: 16px;
        font-size: 12px;
        font-weight: 600;
        display: inline-block;
    }
    
    .date-info {
        font-size: 12px;
        color: var(--medium-text);
    }
    
    .form-control, .form-select {
        border-radius: var(--border-radius);
        border: 1px solid var(--border-color);
        padding: 8px 12px;
        transition: var(--transition);
        background-color: white;
    }
    
    .form-control:focus, .form-select:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 0.2rem rgba(255, 152, 0, 0.25);
        outline: none;
    }
    
    .form-label {
        font-weight: 600;
        color: var(--dark-text);
        margin-bottom: 0px;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
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
        font-size: 12px;
        color: var(--medium-text);
        text-transform: uppercase;
        letter-spacing: 1px;
        font-weight: 600;
    }
    
    .empty-state {
        text-align: center;
        padding: 80px 20px;
        color: var(--medium-text);
    }
    
    .empty-state i {
        font-size: 5rem;
        margin-bottom: 24px;
        opacity: 0.3;
        color: var(--primary-color);
    }
    
    .alert {
        border-radius: var(--border-radius);
        border: none;
        padding: 16px 20px;
        margin-bottom: 24px;
        box-shadow: var(--shadow-light);
    }
    
    .alert-success {
        background: linear-gradient(135deg, #e8f5e8 0%, #c8e6c9 100%);
        color: #2e7d32;
        border-left: 4px solid var(--success-color);
    }
    
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

    /* Sortable headers */
    .sortable-header {
        color: inherit;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 6px;
        transition: var(--transition);
    }

    .sortable-header:hover {
        color: var(--primary-color);
        text-decoration: none;
    }

    .sortable-header i {
        font-size: 12px;
        opacity: 0.6;
        transition: var(--transition);
    }

    .sortable-header:hover i {
        opacity: 1;
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
        list-style: none;
    }
    
    .page-link {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        border-radius: var(--border-radius);
        border: 1px solid var(--border-color);
        background: white;
        color: var(--dark-text);
        text-decoration: none;
        transition: var(--transition);
        font-weight: 500;
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
    
    /* Filters Section */
    .filters-section {
        background: var(--light-bg);
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
        color: var(--medium-text);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 0px;
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
        box-shadow: 0 0 0 3px rgba(255, 152, 0, 0.1);
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
    @media (min-width: 768px) {
      .col-md-20 {
        flex: 0 0 16.666667%;
        max-width: 16.666667%;
      }
    }

    @media (max-width: 768px) {
        .stats-cards {
            grid-template-columns: repeat(2, 1fr);
        }
        
        .action-buttons {
            justify-content: center;
        }
        
        .material-table thead th,
        .material-table tbody td {
            padding: 12px 8px;
            font-size: 12px;
        }
    }
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Status Statistieken -->
            <div class="stats-cards">
                <div class="stat-card">
                    <div class="stat-number" style="background: linear-gradient(135deg, #4caf50 0%, #81c784 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">{{ $categories->where('is_active', true)->count() }}</div>
                    <div class="stat-label">Actief</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" style="background: linear-gradient(135deg, #f44336 0%, #ef5350 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">{{ $categories->where('is_active', false)->count() }}</div>
                    <div class="stat-label">Inactief</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" style="background: linear-gradient(135deg, #ff9800 0%, #ffb74d 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">{{ $categories->count() }}</div>
                    <div class="stat-label">Totaal</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" style="background: linear-gradient(135deg, #ff9800 0%, #ffb74d 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">{{ $categories->unique('group')->count() }}</div>
                    <div class="stat-label">Groepen</div>
                </div>
            </div>

            <div class="material-card">
                <!-- Header -->
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-tags me-2"></i> Categorieën Beheer
                    </h5>
                    <div class="d-flex gap-2">
                        @can('create-categories')
                        <a href="{{ route('admin.categories.create') }}" class="material-btn material-btn-primary">
                            <i class="fas fa-plus me-2"></i> Nieuwe Categorie
                        </a>
                        @endcan
                    </div>
                </div>

                <!-- Filters -->
                <div class="filters-section">
                    <form method="GET" action="{{ route('admin.categories.index') }}" id="filters-form">
                        <div class="row">
                            @if(auth()->user()->hasRole('super-admin'))
                                <!-- Super-admin: 5 kolommen over gehele breedte -->
                                <div class="col-md-2">
                                    <div class="filter-group">
                                        <label class="filter-label">Status</label>
                                        <select name="status" class="filter-select" onchange="this.form.submit()">
                                            <option value="">Alle statussen</option>
                                            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Actief</option>
                                            <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactief</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="filter-group">
                                        <label class="filter-label">Groep</label>
                                        <select name="group" class="filter-select" onchange="this.form.submit()">
                                            <option value="">Alle groepen</option>
                                            @foreach($categories->unique('group')->pluck('group')->filter() as $group)
                                                <option value="{{ $group }}" {{ request('group') == $group ? 'selected' : '' }}>
                                                    {{ $group }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="filter-group">
                                        <label class="filter-label">Sorteren op</label>
                                        <select name="sort_by" class="filter-select" onchange="this.form.submit()">
                                            <option value="sort_order" {{ request('sort_by') === 'sort_order' ? 'selected' : '' }}>Volgorde</option>
                                            <option value="name" {{ request('sort_by') === 'name' ? 'selected' : '' }}>Naam</option>
                                            <option value="created_at" {{ request('sort_by') === 'created_at' ? 'selected' : '' }}>Datum</option>
                                            <option value="is_active" {{ request('sort_by') === 'is_active' ? 'selected' : '' }}>Status</option>
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
                                <div class="col-md-2">
                                    <div class="filter-group">
                                        <label class="filter-label">&nbsp;</label>
                                        <a href="{{ route('admin.categories.index') }}" class="btn btn-outline-secondary w-100" style="height: 36px; display: flex; align-items: center; justify-content: center; gap: 6px; text-decoration: none;">
                                            <i class="fas fa-times"></i>
                                            Filter wissen
                                        </a>
                                    </div>
                                </div>
                            @else
                                <!-- Non-super-admin: 4 kolommen over gehele breedte -->
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
                                        <label class="filter-label">Groep</label>
                                        <select name="group" class="filter-select" onchange="this.form.submit()">
                                            <option value="">Alle groepen</option>
                                            @foreach($categories->unique('group')->pluck('group')->filter() as $group)
                                                <option value="{{ $group }}" {{ request('group') == $group ? 'selected' : '' }}>
                                                    {{ $group }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
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
                                <div class="col-md-3">
                                    <div class="filter-group">
                                        <label class="filter-label">&nbsp;</label>
                                        <a href="{{ route('admin.categories.index') }}" class="btn btn-outline-secondary w-100" style="height: 36px; display: flex; align-items: center; justify-content: center; gap: 6px; text-decoration: none;">
                                            <i class="fas fa-times"></i>
                                            Filter wissen
                                        </a>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </form>
                </div>

                <!-- Table -->
                <div class="card-body">
                    @if($categories->count() > 0)
                        <div class="table-responsive">
                            <table class="material-table">
                                <thead>
                                    <tr>
                                        <th>
                                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'id', 'order' => request('sort') == 'id' && request('order') == 'asc' ? 'desc' : 'asc']) }}" class="sortable-header">
                                                ID
                                                @if(request('sort') == 'id')
                                                    <i class="fas fa-sort-{{ request('order') == 'asc' ? 'up' : 'down' }}"></i>
                                                @else
                                                    <i class="fas fa-sort"></i>
                                                @endif
                                            </a>
                                        </th>
                                        <th>
                                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'name', 'order' => request('sort') == 'name' && request('order') == 'asc' ? 'desc' : 'asc']) }}" class="sortable-header">
                                                Categorie & Details
                                                @if(request('sort') == 'name')
                                                    <i class="fas fa-sort-{{ request('order') == 'asc' ? 'up' : 'down' }}"></i>
                                                @else
                                                    <i class="fas fa-sort"></i>
                                                @endif
                                            </a>
                                        </th>
                                        <th>
                                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'group', 'order' => request('sort') == 'group' && request('order') == 'asc' ? 'desc' : 'asc']) }}" class="sortable-header">
                                                Groep
                                                @if(request('sort') == 'group')
                                                    <i class="fas fa-sort-{{ request('order') == 'asc' ? 'up' : 'down' }}"></i>
                                                @else
                                                    <i class="fas fa-sort"></i>
                                                @endif
                                            </a>
                                        </th>
                                        <th>
                                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'is_active', 'order' => request('sort') == 'is_active' && request('order') == 'asc' ? 'desc' : 'asc']) }}" class="sortable-header">
                                                Status
                                                @if(request('sort') == 'is_active')
                                                    <i class="fas fa-sort-{{ request('order') == 'asc' ? 'up' : 'down' }}"></i>
                                                @else
                                                    <i class="fas fa-sort"></i>
                                                @endif
                                            </a>
                                        </th>
                                        <th>
                                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'sort_order', 'order' => request('sort') == 'sort_order' && request('order') == 'asc' ? 'desc' : 'asc']) }}" class="sortable-header">
                                                Volgorde
                                                @if(request('sort') == 'sort_order')
                                                    <i class="fas fa-sort-{{ request('order') == 'asc' ? 'up' : 'down' }}"></i>
                                                @else
                                                    <i class="fas fa-sort"></i>
                                                @endif
                                            </a>
                                        </th>
                                        <th>
                                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'created_at', 'order' => request('sort') == 'created_at' && request('order') == 'asc' ? 'desc' : 'asc']) }}" class="sortable-header">
                                                Aangemaakt
                                                @if(request('sort') == 'created_at')
                                                    <i class="fas fa-sort-{{ request('order') == 'asc' ? 'up' : 'down' }}"></i>
                                                @else
                                                    <i class="fas fa-sort"></i>
                                                @endif
                                            </a>
                                        </th>
                                        <th>Acties</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($categories as $category)
                                        <tr>
                                            <td>
                                                <strong>{{ $category->id }}</strong>
                                            </td>
                                            <td>
                                                <div class="category-info">
                                                    <div class="category-name">
                                                        @if($category->icon)
                                                            <i class="{{ $category->icon }} me-2" style="color: {{ $category->color }};"></i>
                                                        @endif
                                                        {{ $category->name }}
                                                    </div>
                                                    @if($category->description)
                                                        <div class="category-description">
                                                            <i class="fas fa-info-circle"></i>
                                                            {{ Str::limit($category->description, 50) }}
                                                        </div>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                @if($category->group)
                                                    <span class="category-group">{{ $category->group }}</span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="status-badge {{ $category->is_active ? 'status-active' : 'status-inactive' }}">
                                                    {{ $category->is_active ? 'Actief' : 'Inactief' }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark">{{ $category->sort_order }}</span>
                                            </td>
                                            <td>
                                                <div class="date-info">
                                                    {{ $category->created_at->format('d-m-Y H:i') }}
                                                </div>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    @can('view-categories')
                                                    <a href="{{ route('admin.categories.show', $category) }}" 
                                                       class="action-btn action-btn-info" 
                                                       title="Bekijken">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    @endcan
                                                    @can('edit-categories')
                                                    <a href="{{ route('admin.categories.edit', $category) }}" 
                                                       class="action-btn action-btn-warning" 
                                                       title="Bewerken">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    @endcan
                                                    @can('delete-categories')
                                                    <form action="{{ route('admin.categories.destroy', $category) }}" 
                                                          method="POST" 
                                                          style="display: inline;"
                                                          onsubmit="return confirm('Weet je zeker dat je deze categorie wilt verwijderen?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" 
                                                                class="action-btn action-btn-danger" 
                                                                title="Verwijderen">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                    @endcan
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Results Info -->
                        <div class="results-info-wrapper">
                            <div class="results-info">
                                <span class="results-text">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Toon {{ $categories->firstItem() ?? 0 }} tot {{ $categories->lastItem() ?? 0 }} van {{ $categories->total() }} resultaten
                                </span>
                            </div>
                        </div>

                        <!-- Pagination -->
                        @if($categories->hasPages())
                            <div class="pagination-wrapper">
                                <nav aria-label="Paginering">
                                    <ul class="pagination">
                                {{-- Previous Page Link --}}
                                @if ($categories->onFirstPage())
                                    <li class="page-item disabled">
                                        <span class="page-link">
                                            <i class="fas fa-chevron-left"></i>
                                        </span>
                                    </li>
                                @else
                                    <li class="page-item">
                                        <a class="page-link" href="{{ $categories->previousPageUrl() }}">
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                    </li>
                                @endif

                                {{-- Pagination Elements --}}
                                @php
                                    $currentPage = $categories->currentPage();
                                    $lastPage = $categories->lastPage();
                                    $startPage = max(1, $currentPage - 2);
                                    $endPage = min($lastPage, $currentPage + 2);
                                    
                                    // Adjust range if we're near the beginning or end
                                    if ($endPage - $startPage < 4) {
                                        if ($startPage == 1) {
                                            $endPage = min($lastPage, $startPage + 4);
                                        } else {
                                            $startPage = max(1, $endPage - 4);
                                        }
                                    }
                                @endphp
                                
                                {{-- First page if not in range --}}
                                @if($startPage > 1)
                                    <li class="page-item">
                                        <a class="page-link" href="{{ $categories->url(1) }}">1</a>
                                    </li>
                                    @if($startPage > 2)
                                        <li class="page-item disabled">
                                            <span class="page-link">...</span>
                                        </li>
                                    @endif
                                @endif
                                
                                {{-- Page range --}}
                                @for($page = $startPage; $page <= $endPage; $page++)
                                    @if ($page == $currentPage)
                                        <li class="page-item active">
                                            <span class="page-link">{{ $page }}</span>
                                        </li>
                                    @else
                                        <li class="page-item">
                                            <a class="page-link" href="{{ $categories->url($page) }}">{{ $page }}</a>
                                        </li>
                                    @endif
                                @endfor
                                
                                {{-- Last page if not in range --}}
                                @if($endPage < $lastPage)
                                    @if($endPage < $lastPage - 1)
                                        <li class="page-item disabled">
                                            <span class="page-link">...</span>
                                        </li>
                                    @endif
                                    <li class="page-item">
                                        <a class="page-link" href="{{ $categories->url($lastPage) }}">{{ $lastPage }}</a>
                                    </li>
                                @endif

                                {{-- Next Page Link --}}
                                @if ($categories->hasMorePages())
                                    <li class="page-item">
                                        <a class="page-link" href="{{ $categories->nextPageUrl() }}">
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
                            <i class="fas fa-tags"></i>
                            <h4>Geen categorieën gevonden</h4>
                            <p>Er zijn nog geen categorieën aangemaakt. Maak je eerste categorie aan om te beginnen.</p>
                            @can('create-categories')
                            <a href="{{ route('admin.categories.create') }}" class="material-btn material-btn-primary">
                                <i class="fas fa-plus me-2"></i> Nieuwe Categorie
                            </a>
                            @endcan
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection