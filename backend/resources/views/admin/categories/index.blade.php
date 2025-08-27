@extends('admin.layouts.app')

@section('title', 'Categorieën Beheer')

@section('content')
@include('admin.material-design-template')

<style>
    /* Smart Table Styles */
    .smart-table-container {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }
    
    .smart-table-header {
        background: linear-gradient(135deg, #ff9800 0%, #ffb74d 100%);
        color: white;
        padding: 20px 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 16px;
    }
    
    .smart-table-filters {
        background: #f8f9fa;
        padding: 20px 24px;
        border-bottom: 1px solid #e9ecef;
    }
    
    .filters-row {
        display: flex;
        gap: 16px;
        align-items: center;
        flex-wrap: wrap;
        margin-bottom: 16px;
    }
    
    .filter-group {
        display: flex;
        flex-direction: column;
        gap: 8px;
        min-width: 200px;
    }
    
    .filter-group label {
        font-weight: 600;
        color: #495057;
        font-size: 0.875rem;
        margin-bottom: 4px;
    }
    
    .filter-input {
        border: 1px solid #ced4da;
        border-radius: 8px;
        padding: 10px 12px;
        font-size: 0.875rem;
        transition: all 0.3s ease;
        background: white;
    }
    
    .filter-input:focus {
        border-color: #ff9800;
        outline: none;
        box-shadow: 0 0 0 3px rgba(255, 152, 0, 0.1);
        background-color: #fff8e1;
    }
    
    .filter-input:hover {
        border-color: #ff9800;
        background-color: #fff8e1;
    }
    
    .filter-select {
        border: 1px solid #ced4da;
        border-radius: 8px;
        padding: 8px 12px;
        font-size: 0.875rem;
        background: white;
        transition: all 0.3s ease;
        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;
        background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23666' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6,9 12,15 18,9'%3e%3c/polyline%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right 8px center;
        background-size: 16px;
        padding-right: 32px;
        cursor: pointer;
    }
    
    .filter-select:focus {
        border-color: #ff9800;
        outline: none;
        box-shadow: 0 0 0 3px rgba(255, 152, 0, 0.1);
        background-color: #fff8e1;
    }
    
    .filter-select:hover {
        border-color: #ff9800;
        background-color: #fff8e1;
    }
    
    .filter-actions {
        display: flex;
        gap: 16px;
        align-items: end;
        margin-left: auto;
    }
    
    .btn-filter {
        background: linear-gradient(135deg, #2196F3 0%, #42A5F5 100%);
        color: white;
        border: none;
        border-radius: 12px;
        padding: 12px 24px;
        font-size: 0.875rem;
        font-weight: 600;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        cursor: pointer;
        box-shadow: 0 3px 12px rgba(33, 150, 243, 0.3);
        text-transform: uppercase;
        letter-spacing: 1px;
        min-width: 120px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }
    
    .btn-filter:hover {
        background: linear-gradient(135deg, #1976D2 0%, #2196F3 100%);
        transform: translateY(-3px);
        box-shadow: 0 6px 20px rgba(33, 150, 243, 0.4);
    }
    
    .btn-filter:active {
        transform: translateY(-1px);
        box-shadow: 0 3px 8px rgba(33, 150, 243, 0.3);
    }
    
    .btn-clear {
        background: linear-gradient(135deg, #FF5722 0%, #FF7043 100%);
        color: white;
        border: none;
        border-radius: 12px;
        padding: 12px 24px;
        font-size: 0.875rem;
        font-weight: 600;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        cursor: pointer;
        box-shadow: 0 3px 12px rgba(255, 87, 34, 0.3);
        text-transform: uppercase;
        letter-spacing: 1px;
        min-width: 120px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }
    
    .btn-clear:hover {
        background: linear-gradient(135deg, #E64A19 0%, #FF5722 100%);
        transform: translateY(-3px);
        box-shadow: 0 6px 20px rgba(255, 87, 34, 0.4);
    }
    
    .btn-clear:active {
        transform: translateY(-1px);
        box-shadow: 0 3px 8px rgba(255, 87, 34, 0.3);
    }
    
    /* Ripple Effect */
    .btn-filter, .btn-clear {
        position: relative;
        overflow: hidden;
    }
    
    .pagination-controls .page-link {
        position: relative;
        overflow: hidden;
    }
    
    .ripple {
        position: absolute;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.3);
        transform: scale(0);
        animation: ripple-animation 0.6s linear;
        pointer-events: none;
    }
    
    @keyframes ripple-animation {
        to {
            transform: scale(4);
            opacity: 0;
        }
    }
    
    .smart-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .smart-table thead th {
        background: #f8f9fa;
        padding: 16px 12px;
        text-align: left;
        font-weight: 600;
        color: #495057;
        border-bottom: 2px solid #e9ecef;
        cursor: pointer;
        transition: background-color 0.3s ease;
        position: relative;
    }
    
    .smart-table thead th:hover {
        background: #e9ecef;
    }
    
    .smart-table thead th.sortable::after {
        content: '↕';
        position: absolute;
        right: 8px;
        opacity: 0.5;
    }
    
    .smart-table thead th.sort-asc::after {
        content: '↑';
        opacity: 1;
        color: #ff9800;
    }
    
    .smart-table thead th.sort-desc::after {
        content: '↓';
        opacity: 1;
        color: #ff9800;
    }
    
    .smart-table tbody td {
        padding: 16px 12px;
        border-bottom: 1px solid #e9ecef;
        vertical-align: middle;
    }
    
    .smart-table tbody tr:hover {
        background: #f8f9fa;
    }
    
    .table-info {
        background: #e3f2fd;
        padding: 16px 24px;
        border-bottom: 1px solid #e9ecef;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 16px;
    }
    
    .table-stats {
        font-size: 0.875rem;
        color: #495057;
    }
    
    .per-page-selector {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .per-page-selector label {
        font-size: 0.875rem;
        color: #495057;
        font-weight: 500;
    }
    
    .per-page-selector select {
        border: 1px solid #ced4da;
        border-radius: 6px;
        padding: 4px 8px;
        font-size: 0.875rem;
        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;
        background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23666' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6,9 12,15 18,9'%3e%3c/polyline%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right 4px center;
        background-size: 12px;
        padding-right: 20px;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .per-page-selector select:focus {
        border-color: #ff9800;
        outline: none;
        box-shadow: 0 0 0 2px rgba(255, 152, 0, 0.1);
        background-color: #fff8e1;
    }
    
    .per-page-selector select:hover {
        border-color: #ff9800;
        background-color: #fff8e1;
    }
    
    /* Material Design Pagination */
    .material-pagination {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 20px;
        padding: 24px;
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-radius: 16px;
        margin: 24px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    }
    
    .pagination-controls {
        display: flex;
        justify-content: center;
        align-items: center;
    }
    
    .pagination-controls nav {
        display: flex;
        justify-content: center;
    }
    
    .pagination-controls .pagination {
        margin: 0;
        display: flex;
        gap: 8px;
        align-items: center;
        list-style: none;
        padding: 0;
    }
    
    .pagination-controls .page-item {
        margin: 0;
        list-style: none;
    }
    
    .pagination-controls .page-link {
        border: none;
        padding: 12px 16px;
        color: #495057;
        background: white;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        font-weight: 600;
        min-width: 48px;
        height: 48px;
        text-align: center;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        position: relative;
        overflow: hidden;
        text-decoration: none;
        font-size: 0.875rem;
    }
    
    .pagination-controls .page-link:hover {
        background: linear-gradient(135deg, #E3F2FD 0%, #BBDEFB 100%);
        color: #1976D2;
        transform: translateY(-2px);
        box-shadow: 0 4px 16px rgba(33, 150, 243, 0.3);
        text-decoration: none;
    }
    
    .pagination-controls .page-link:focus {
        background: linear-gradient(135deg, #E3F2FD 0%, #BBDEFB 100%);
        color: #1976D2;
        box-shadow: 0 0 0 3px rgba(33, 150, 243, 0.2);
        outline: none;
        text-decoration: none;
    }
    
    .pagination-controls .page-item.active .page-link {
        background: linear-gradient(135deg, #2196F3 0%, #42A5F5 100%);
        color: white;
        border: none;
        box-shadow: 0 4px 16px rgba(33, 150, 243, 0.4);
        transform: translateY(-2px);
    }
    
    .pagination-controls .page-item.disabled .page-link {
        background: #f8f9fa;
        color: #adb5bd;
        cursor: not-allowed;
        opacity: 0.7;
        box-shadow: none;
    }
    
    .pagination-controls .page-item.disabled .page-link:hover {
        background: #f8f9fa;
        color: #adb5bd;
        transform: none;
        box-shadow: none;
    }
    
    /* Previous/Next Buttons */
    .pagination-controls .page-item:first-child .page-link,
    .pagination-controls .page-item:last-child .page-link {
        background: linear-gradient(135deg, #FF5722 0%, #FF7043 100%);
        color: white;
        min-width: 56px;
        font-weight: 600;
    }
    
    .pagination-controls .page-item:first-child .page-link:hover,
    .pagination-controls .page-item:last-child .page-link:hover {
        background: linear-gradient(135deg, #E64A19 0%, #FF5722 100%);
        transform: translateY(-2px);
        box-shadow: 0 4px 16px rgba(255, 87, 34, 0.4);
    }
    
    .pagination-controls .page-item:first-child .page-link:focus,
    .pagination-controls .page-item:last-child .page-link:focus {
        background: linear-gradient(135deg, #E64A19 0%, #FF5722 100%);
        box-shadow: 0 0 0 3px rgba(255, 87, 34, 0.2);
    }
    
    /* Pagination Info */
    .pagination-info {
        display: flex;
        align-items: center;
        gap: 16px;
        font-size: 0.875rem;
        color: #495057;
        background: white;
        padding: 12px 20px;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }
    
    .pagination-info .pagination-text {
        font-weight: 600;
        color: #1976D2;
    }
    
    .pagination-info .pagination-stats {
        color: #6c757d;
        font-weight: 500;
    }
    
    .pagination-info i {
        color: #2196F3;
        margin-right: 8px;
    }
    
    /* Override any default Bootstrap pagination styles */
    .pagination {
        margin: 0 !important;
        padding: 0 !important;
        list-style: none !important;
    }
    
    .page-item {
        margin: 0 !important;
        list-style: none !important;
    }
    
    .page-link {
        text-decoration: none !important;
        border: none !important;
    }
    
    .category-icon {
        width: 32px;
        height: 32px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 12px;
        font-size: 1rem;
    }
    
    .category-name-cell {
        display: flex;
        align-items: center;
    }
    
    .category-details {
        display: flex;
        flex-direction: column;
    }
    
    .category-name {
        font-weight: 600;
        color: #495057;
    }
    
    .category-slug {
        font-size: 0.75rem;
        color: #6c757d;
        margin-top: 2px;
    }
    
    .status-badge {
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 500;
        text-transform: uppercase;
    }
    
    .status-active {
        background: #d4edda;
        color: #155724;
    }
    
    .status-inactive {
        background: #f8d7da;
        color: #721c24;
    }
    
    .color-preview {
        width: 24px;
        height: 24px;
        border-radius: 6px;
        border: 2px solid #e9ecef;
        margin-right: 8px;
    }
    
    .color-info {
        display: flex;
        align-items: center;
        font-size: 0.875rem;
    }
    
    .sort-order-badge {
        background: #fff3e0;
        color: #e65100;
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: 500;
    }
    
    .action-buttons {
        display: flex;
        gap: 8px;
    }
    
    .action-btn {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
        cursor: pointer;
    }
    
    .action-btn:hover {
        transform: scale(1.1);
    }
    
    .action-btn-view {
        background: #17a2b8;
        color: white;
    }
    
    .action-btn-edit {
        background: #ffc107;
        color: #212529;
    }
    
    .action-btn-delete {
        background: #dc3545;
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
    
    @media (max-width: 768px) {
        .smart-table-header {
            flex-direction: column;
            align-items: stretch;
        }
        
        .filters-row {
            flex-direction: column;
            align-items: stretch;
        }
        
        .filter-group {
            min-width: auto;
        }
        
        .filter-actions {
            justify-content: center;
        }
        
        .table-info {
            flex-direction: column;
            text-align: center;
        }
        
        .smart-table {
            font-size: 0.875rem;
        }
        
        .smart-table thead th,
        .smart-table tbody td {
            padding: 8px 6px;
        }
        
        .category-name-cell {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .category-icon {
            margin-bottom: 8px;
            margin-right: 0;
        }
        
        .action-buttons {
            flex-direction: column;
            gap: 4px;
        }
        
        .action-btn {
            width: 32px;
            height: 32px;
        }
    }
    
    @media (max-width: 480px) {
        .smart-table-header {
            padding: 16px;
        }
        
        .smart-table-filters {
            padding: 16px;
        }
        
        .table-info {
            padding: 12px 16px;
        }
        
        .smart-table thead th,
        .smart-table tbody td {
            padding: 6px 4px;
            font-size: 0.8rem;
        }
        
        .category-name {
            font-size: 0.9rem;
        }
        
        .category-slug {
            font-size: 0.7rem;
        }
        
        .material-pagination {
            gap: 16px;
            padding: 20px 16px;
            margin: 16px;
        }
        
        .pagination-info {
            flex-direction: column;
            text-align: center;
            gap: 8px;
            padding: 10px 16px;
        }
        
        .pagination-controls .page-link {
            padding: 10px 12px;
            min-width: 40px;
            height: 40px;
            font-size: 0.875rem;
        }
        
        .filter-actions {
            margin-left: 0;
            justify-content: center;
            width: 100%;
        }
        
        .btn-filter, .btn-clear {
            min-width: 100px;
            padding: 10px 16px;
            font-size: 0.8rem;
        }
    }
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="smart-table-container">
                <!-- Header -->
                <div class="smart-table-header">
                    <h5 class="mb-0">
                        <i class="fas fa-tags me-2"></i> Categorieën Beheer
                    </h5>
                    <a href="{{ route('admin.categories.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i> Nieuwe Categorie
                    </a>
                </div>

                <!-- Filters -->
                <div class="smart-table-filters">
                    <form method="GET" action="{{ route('admin.categories.index') }}" id="filterForm">
                        <div class="filters-row">
                            <div class="filter-group">
                                <label for="search">Zoeken</label>
                                <input type="text" id="search" name="search" class="filter-input" 
                                       placeholder="Zoek op naam, beschrijving..." 
                                       value="{{ request('search') }}">
                            </div>
                            
                            <div class="filter-group">
                                <label for="status">Status</label>
                                <select id="status" name="status" class="filter-select">
                                    <option value="">Alle statussen</option>
                                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Actief</option>
                                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactief</option>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label for="sort_by">Sorteren op</label>
                                <select id="sort_by" name="sort_by" class="filter-select">
                                    <option value="sort_order" {{ request('sort_by') === 'sort_order' ? 'selected' : '' }}>Volgorde</option>
                                    <option value="name" {{ request('sort_by') === 'name' ? 'selected' : '' }}>Naam</option>
                                    <option value="created_at" {{ request('sort_by') === 'created_at' ? 'selected' : '' }}>Datum</option>
                                    <option value="is_active" {{ request('sort_by') === 'is_active' ? 'selected' : '' }}>Status</option>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label for="sort_order">Richting</label>
                                <select id="sort_order" name="sort_order" class="filter-select">
                                    <option value="asc" {{ request('sort_order') === 'asc' ? 'selected' : '' }}>Oplopend</option>
                                    <option value="desc" {{ request('sort_order') === 'desc' ? 'selected' : '' }}>Aflopend</option>
                                </select>
                            </div>
                            
                            <div class="filter-actions">
                                <button type="submit" class="btn-filter">
                                    <i class="fas fa-search"></i> FILTEREN
                                </button>
                                <a href="{{ route('admin.categories.index') }}" class="btn-clear">
                                    <i class="fas fa-times"></i> WISSEN
                                </a>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Table Info -->
                <div class="table-info">
                    <div class="table-stats">
                        <i class="fas fa-info-circle me-1"></i>
                        Toon {{ $categories->firstItem() ?? 0 }} tot {{ $categories->lastItem() ?? 0 }} van {{ $categories->total() }} categorieën
                    </div>
                    
                    <div class="per-page-selector">
                        <label for="per_page">Per pagina:</label>
                        <select id="per_page" name="per_page" onchange="changePerPage(this.value)">
                            <option value="5" {{ request('per_page') == 5 ? 'selected' : '' }}>5</option>
                            <option value="10" {{ request('per_page') == 10 || !request('per_page') ? 'selected' : '' }}>10</option>
                            <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25</option>
                            <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                            <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100</option>
                        </select>
                    </div>
                </div>

                <!-- Success Message -->
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show m-3" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <!-- Table -->
                <div class="table-responsive">
                    <table class="smart-table">
                        <thead>
                            <tr>
                                <th class="sortable {{ request('sort_by') === 'name' ? (request('sort_order') === 'asc' ? 'sort-asc' : 'sort-desc') : '' }}" 
                                    onclick="sortTable('name')">
                                    <i class="fas fa-tag me-1"></i> Naam
                                </th>
                                <th>Beschrijving</th>
                                <th>Kleur & Icoon</th>
                                <th class="sortable {{ request('sort_by') === 'is_active' ? (request('sort_order') === 'asc' ? 'sort-asc' : 'sort-desc') : '' }}" 
                                    onclick="sortTable('is_active')">
                                    <i class="fas fa-toggle-on me-1"></i> Status
                                </th>
                                <th class="sortable {{ request('sort_by') === 'sort_order' ? (request('sort_order') === 'asc' ? 'sort-asc' : 'sort-desc') : '' }}" 
                                    onclick="sortTable('sort_order')">
                                    <i class="fas fa-sort-numeric-up me-1"></i> Volgorde
                                </th>
                                <th class="sortable {{ request('sort_by') === 'created_at' ? (request('sort_order') === 'asc' ? 'sort-asc' : 'sort-desc') : '' }}" 
                                    onclick="sortTable('created_at')">
                                    <i class="fas fa-calendar me-1"></i> Gemaakt op
                                </th>
                                <th>Acties</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($categories as $category)
                                <tr>
                                    <td>
                                        <div class="category-name-cell">
                                            @if($category->icon)
                                                <div class="category-icon" style="background-color: {{ $category->color ?? '#6c757d' }}; color: white;">
                                                    <i class="{{ $category->icon }}"></i>
                                                </div>
                                            @endif
                                            <div class="category-details">
                                                <div class="category-name">{{ $category->name }}</div>
                                                @if($category->slug)
                                                    <div class="category-slug">{{ $category->slug }}</div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div title="{{ $category->description }}">
                                            @if($category->description)
                                                {{ Str::limit($category->description, 60) }}
                                            @else
                                                <span class="text-muted">Geen beschrijving</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        @if($category->color)
                                            <div class="color-info">
                                                <div class="color-preview" style="background-color: {{ $category->color }};"></div>
                                                {{ $category->color }}
                                            </div>
                                        @else
                                            <span class="text-muted">Geen kleur</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($category->is_active)
                                            <span class="status-badge status-active">Actief</span>
                                        @else
                                            <span class="status-badge status-inactive">Inactief</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($category->sort_order)
                                            <span class="sort-order-badge">{{ $category->sort_order }}</span>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="text-muted">{{ $category->created_at->format('d-m-Y H:i') }}</div>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="{{ route('admin.categories.show', $category) }}" class="action-btn action-btn-view" title="Bekijken">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('admin.categories.edit', $category) }}" class="action-btn action-btn-edit" title="Bewerken">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="{{ route('admin.categories.destroy', $category) }}" method="POST" class="d-inline" onsubmit="return confirm('Weet je zeker dat je deze categorie wilt verwijderen?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="action-btn action-btn-delete" title="Verwijderen">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7">
                                        <div class="empty-state">
                                            <i class="fas fa-tags"></i>
                                            <h5>Geen categorieën gevonden</h5>
                                            <p>Er zijn geen categorieën die voldoen aan je zoekcriteria.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if($categories->hasPages())
                    <div class="material-pagination">
                        <div class="pagination-info">
                            <span class="pagination-text">
                                <i class="fas fa-info-circle"></i>
                                Pagina {{ $categories->currentPage() }} van {{ $categories->lastPage() }}
                            </span>
                            <span class="pagination-stats">
                                ({{ $categories->total() }} categorieën)
                            </span>
                        </div>
                        <div class="pagination-controls">
                            <nav>
                                <ul class="pagination">
                                    {{-- Previous Page Link --}}
                                    @if ($categories->onFirstPage())
                                        <li class="page-item disabled" aria-disabled="true" aria-label="Previous">
                                            <span class="page-link" aria-hidden="true">
                                                <i class="fas fa-chevron-left"></i>
                                            </span>
                                        </li>
                                    @else
                                        @php
                                            $prevUrl = $categories->previousPageUrl();
                                            if (request()->has('search')) $prevUrl .= '&search=' . request('search');
                                            if (request()->has('status')) $prevUrl .= '&status=' . request('status');
                                            if (request()->has('sort_by')) $prevUrl .= '&sort_by=' . request('sort_by');
                                            if (request()->has('sort_order')) $prevUrl .= '&sort_order=' . request('sort_order');
                                            if (request()->has('per_page')) $prevUrl .= '&per_page=' . request('per_page');
                                        @endphp
                                        <li class="page-item">
                                            <a class="page-link" href="{{ $prevUrl }}" rel="prev" aria-label="Previous">
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
                                    @endphp
                                    
                                    @for ($page = $startPage; $page <= $endPage; $page++)
                                        @php
                                            $url = $categories->url($page);
                                            if (request()->has('search')) $url .= '&search=' . request('search');
                                            if (request()->has('status')) $url .= '&status=' . request('status');
                                            if (request()->has('sort_by')) $url .= '&sort_by=' . request('sort_by');
                                            if (request()->has('sort_order')) $url .= '&sort_order=' . request('sort_order');
                                            if (request()->has('per_page')) $url .= '&per_page=' . request('per_page');
                                        @endphp
                                        
                                        @if ($page == $currentPage)
                                            <li class="page-item active" aria-current="page">
                                                <span class="page-link">{{ $page }}</span>
                                            </li>
                                        @else
                                            <li class="page-item">
                                                <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                                            </li>
                                        @endif
                                    @endfor

                                    {{-- Next Page Link --}}
                                    @if ($categories->hasMorePages())
                                        @php
                                            $nextUrl = $categories->nextPageUrl();
                                            if (request()->has('search')) $nextUrl .= '&search=' . request('search');
                                            if (request()->has('status')) $nextUrl .= '&status=' . request('status');
                                            if (request()->has('sort_by')) $nextUrl .= '&sort_by=' . request('sort_by');
                                            if (request()->has('sort_order')) $nextUrl .= '&sort_order=' . request('sort_order');
                                            if (request()->has('per_page')) $nextUrl .= '&per_page=' . request('per_page');
                                        @endphp
                                        <li class="page-item">
                                            <a class="page-link" href="{{ $nextUrl }}" rel="next" aria-label="Next">
                                                <i class="fas fa-chevron-right"></i>
                                            </a>
                                        </li>
                                    @else
                                        <li class="page-item disabled" aria-disabled="true" aria-label="Next">
                                            <span class="page-link" aria-hidden="true">
                                                <i class="fas fa-chevron-right"></i>
                                            </span>
                                        </li>
                                    @endif
                                </ul>
                            </nav>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
function changePerPage(value) {
    const url = new URL(window.location);
    url.searchParams.set('per_page', value);
    window.location.href = url.toString();
}

function sortTable(column) {
    const url = new URL(window.location);
    const currentSortBy = url.searchParams.get('sort_by');
    const currentSortOrder = url.searchParams.get('sort_order');
    
    if (currentSortBy === column) {
        // Toggle sort order
        url.searchParams.set('sort_order', currentSortOrder === 'asc' ? 'desc' : 'asc');
    } else {
        // Set new column and default to ascending
        url.searchParams.set('sort_by', column);
        url.searchParams.set('sort_order', 'asc');
    }
    
    window.location.href = url.toString();
}

// Auto-submit form on filter changes
document.addEventListener('DOMContentLoaded', function() {
    const filterInputs = document.querySelectorAll('#filterForm input, #filterForm select');
    filterInputs.forEach(input => {
        if (input.type !== 'submit' && input.id !== 'per_page') {
            input.addEventListener('change', function() {
                document.getElementById('filterForm').submit();
            });
        }
    });
    
    // Add loading state to filter button
    const filterForm = document.getElementById('filterForm');
    const filterButton = filterForm.querySelector('.btn-filter');
    
    filterForm.addEventListener('submit', function() {
        filterButton.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Filteren...';
        filterButton.disabled = true;
        filterButton.style.transform = 'translateY(0)';
        filterButton.style.boxShadow = '0 2px 4px rgba(255, 152, 0, 0.3)';
    });
    
    // Add ripple effect to buttons
    const buttons = document.querySelectorAll('.btn-filter, .btn-clear, .pagination-controls .page-link');
    buttons.forEach(button => {
        button.addEventListener('click', function(e) {
            // Remove existing ripples
            const existingRipples = this.querySelectorAll('.ripple');
            existingRipples.forEach(ripple => ripple.remove());
            
            const ripple = document.createElement('span');
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = e.clientX - rect.left - size / 2;
            const y = e.clientY - rect.top - size / 2;
            
            ripple.style.width = ripple.style.height = size + 'px';
            ripple.style.left = x + 'px';
            ripple.style.top = y + 'px';
            ripple.classList.add('ripple');
            
            this.appendChild(ripple);
            
            setTimeout(() => {
                ripple.remove();
            }, 600);
        });
    });
    
    // Add search input debouncing
    const searchInput = document.getElementById('search');
    let searchTimeout;
    
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            if (this.value.length >= 2 || this.value.length === 0) {
                document.getElementById('filterForm').submit();
            }
        }, 500);
    });
});
</script>
@endsection
