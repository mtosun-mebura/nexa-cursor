@extends('admin.layouts.app')

@section('title', 'Vacatures Beheer')

@section('content')
<style>
    :root {
        --primary-color: #9c27b0;
        --primary-light: #ba68c8;
        --primary-dark: #7b1fa2;
        --secondary-color: #f3e5f5;
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
        padding: 24px 32px;
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
        padding: 32px;
    }
    
    .material-btn {
        border-radius: var(--border-radius);
        text-transform: uppercase;
        font-weight: 500;
        letter-spacing: 0.5px;
        padding: 12px 24px;
        border: none;
        transition: var(--transition);
        box-shadow: var(--shadow-light);
        position: relative;
        overflow: hidden;
        cursor: pointer;
        font-size: 14px;
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
    }
    
    .material-btn:active {
        transform: translateY(0);
        box-shadow: var(--shadow-light);
    }
    
    .material-btn-primary {
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
        color: white;
    }
    
    .material-table {
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
        padding: 20px 16px;
        text-transform: uppercase;
        font-size: 12px;
        letter-spacing: 1px;
        cursor: pointer;
        transition: var(--transition);
        position: relative;
    }
    
    .material-table thead th:hover {
        background: var(--secondary-color);
        color: var(--primary-color);
    }
    
    .material-table thead th.sortable::after {
        content: '↕';
        margin-left: 8px;
        opacity: 0.5;
        transition: var(--transition);
    }
    
    .material-table thead th.sort-asc::after {
        content: '↑';
        opacity: 1;
        color: var(--primary-color);
    }
    
    .material-table thead th.sort-desc::after {
        content: '↓';
        opacity: 1;
        color: var(--primary-color);
    }
    
    .material-table tbody td {
        padding: 20px 16px;
        border-bottom: 1px solid var(--border-color);
        vertical-align: middle;
        transition: var(--transition);
    }
    
    .material-table tbody tr {
        transition: var(--transition);
        background-color: white;
    }
    
    /* Hover effect voor tabel rijen */
    .material-table tbody tr:hover,
    .table tbody tr:hover,
    .material-table .table tbody tr:hover,
    table tbody tr:hover,
    .table-hover tbody tr:hover,
    .material-table.table-hover tbody tr:hover {
        background-color: #e3f2fd !important;
        background: #e3f2fd !important;
        transform: scale(1.01);
        transition: background-color 0.3s ease;
    }
    
    /* Specifieke override voor Bootstrap table-hover */
    .table.table-hover tbody tr:hover {
        background-color: #e3f2fd !important;
        background: #e3f2fd !important;
    }
    
    /* Nog specifiekere override */
    .table.material-table.table-hover tbody tr:hover {
        background-color: #e3f2fd !important;
        background: #e3f2fd !important;
    }
    
    /* Ultieme override voor Bootstrap */
    .table.table-hover > tbody > tr:hover > td,
    .table.table-hover > tbody > tr:hover > th {
        background-color: #e3f2fd !important;
        background: #e3f2fd !important;
    }
    
    .table.material-table.table-hover > tbody > tr:hover > td,
    .table.material-table.table-hover > tbody > tr:hover > th {
        background-color: #e3f2fd !important;
        background: #e3f2fd !important;
    }
    
    .status-badge {
        padding: 8px 16px;
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
    
    .status-open {
        background: linear-gradient(135deg, #f1f8e9 0%, #81c784 100%);
        color: #388e3c;
        border: 2px solid #81c784;
    }
    
    .status-closed {
        background: linear-gradient(135deg, #ffcdd2 0%, #e57373 100%);
        color: #d32f2f;
        border: 2px solid #e57373;
    }
    
    .status-processing {
        background: linear-gradient(135deg, #fff8e1 0%, #ffb74d 100%);
        color: #f57c00;
        border: 2px solid #ffb74d;
    }
    
    .action-buttons {
        display: flex;
        gap: 8px;
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
    
    .vacancy-info {
        display: flex;
        flex-direction: column;
    }
    
    .vacancy-title {
        font-weight: 600;
        color: var(--dark-text);
        margin-bottom: 4px;
        font-size: 16px;
    }
    
    .vacancy-location {
        font-size: 14px;
        color: var(--medium-text);
        display: flex;
        align-items: center;
        gap: 6px;
    }
    
    .vacancy-company {
        background: linear-gradient(135deg, #e8f5e8 0%, #c8e6c9 100%);
        color: #2e7d32;
        padding: 6px 12px;
        border-radius: 16px;
        font-size: 12px;
        font-weight: 600;
        display: inline-block;
    }
    
    .vacancy-category {
        background: linear-gradient(135deg, #fff3e0 0%, #ffe0b2 100%);
        color: #f57c00;
        padding: 6px 12px;
        border-radius: 16px;
        font-size: 12px;
        font-weight: 600;
        display: inline-block;
    }
    
    .vacancy-type {
        background: linear-gradient(135deg, #f3e5f5 0%, #e1bee7 100%);
        color: #7b1fa2;
        padding: 6px 12px;
        border-radius: 16px;
        font-size: 12px;
        font-weight: 600;
        display: inline-block;
    }
    
    .date-info {
        font-size: 14px;
        color: var(--medium-text);
    }
    
    .filters-section {
        background: var(--light-bg);
        border-radius: var(--border-radius);
        padding: 24px;
        margin-bottom: 24px;
        box-shadow: var(--shadow-light);
    }
    
    .filter-group {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 16px;
    }
    
    .filter-label {
        font-weight: 600;
        color: var(--dark-text);
        min-width: 100px;
        font-size: 14px;
    }
    
    .filter-select {
        border: 2px solid var(--border-color);
        border-radius: var(--border-radius);
        padding: 10px 14px;
        background: white;
        min-width: 160px;
        font-size: 14px;
        transition: var(--transition);
        cursor: pointer;
        appearance: none;
        background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6,9 12,15 18,9'%3e%3c/polyline%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right 10px center;
        background-size: 14px;
        padding-right: 35px;
    }
    
    .filter-select:focus {
        outline: none;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(156, 39, 176, 0.1);
    }
    
    .filter-select:hover {
        border-color: var(--primary-light);
    }
    
    .filter-select option {
        padding: 10px 16px;
        background-color: white;
        color: var(--dark-text);
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
        padding: 24px;
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
        font-size: 2.5rem;
        font-weight: 700;
        margin-bottom: 8px;
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
    
    .seo-indicator {
        display: inline-block;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        margin-right: 8px;
        box-shadow: var(--shadow-light);
    }
    
    .seo-good {
        background: linear-gradient(135deg, var(--success-color) 0%, #66bb6a 100%);
    }
    
    .seo-warning {
        background: linear-gradient(135deg, var(--warning-color) 0%, #ffb74d 100%);
    }
    
    .seo-bad {
        background: linear-gradient(135deg, var(--danger-color) 0%, #ef5350 100%);
    }
    
    .pagination-wrapper {
        display: flex;
        justify-content: center;
        margin-top: 32px;
    }
    
    .page-link {
        color: var(--primary-color);
        border: 1px solid var(--border-color);
        padding: 12px 16px;
        margin: 0 4px;
        border-radius: var(--border-radius);
        text-decoration: none;
        transition: var(--transition);
        font-weight: 500;
    }
    
    .page-link:hover {
        background: var(--primary-color);
        color: white;
        border-color: var(--primary-color);
        transform: translateY(-2px);
        box-shadow: var(--shadow-light);
    }
    
    .page-item.active .page-link {
        background: var(--primary-color);
        border-color: var(--primary-color);
        color: white;
        box-shadow: var(--shadow-medium);
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
    
    @media (max-width: 768px) {
        .stats-cards {
            grid-template-columns: repeat(2, 1fr);
        }
        
        .filter-group {
            flex-direction: column;
            align-items: stretch;
        }
        
        .filter-label {
            min-width: auto;
        }
        
        .filter-select {
            min-width: auto;
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
                    <div class="stat-number" style="background: linear-gradient(135deg, #4caf50 0%, #66bb6a 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">{{ $statusStats['Open'] ?? 0 }}</div>
                    <div class="stat-label">Open</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" style="background: linear-gradient(135deg, #f44336 0%, #ef5350 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">{{ $statusStats['Gesloten'] ?? 0 }}</div>
                    <div class="stat-label">Gesloten</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" style="background: linear-gradient(135deg, #ff9800 0%, #ffb74d 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">{{ $statusStats['In behandeling'] ?? 0 }}</div>
                    <div class="stat-label">In behandeling</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" style="background: linear-gradient(135deg, #9c27b0 0%, #ba68c8 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">{{ $vacancies->total() }}</div>
                    <div class="stat-label">Totaal</div>
                </div>
            </div>

            <div class="material-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-briefcase me-2"></i> Vacatures Overzicht
                    </h5>
                    <a href="{{ route('admin.vacancies.create') }}" class="material-btn material-btn-primary">
                        <i class="fas fa-plus me-2"></i> Nieuwe Vacature
                    </a>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <!-- Filters -->
                    <div class="filters-section">
                        <form method="GET" action="{{ route('admin.vacancies.index') }}" id="filters-form">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="filter-group">
                                        <label class="filter-label">Status</label>
                                        <select name="status" class="filter-select" onchange="this.form.submit()">
                                            <option value="">Alle statussen</option>
                                            <option value="Open" {{ request('status') == 'Open' ? 'selected' : '' }}>Open</option>
                                            <option value="Gesloten" {{ request('status') == 'Gesloten' ? 'selected' : '' }}>Gesloten</option>
                                            <option value="In behandeling" {{ request('status') == 'In behandeling' ? 'selected' : '' }}>In behandeling</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="filter-group">
                                        <label class="filter-label">Categorie</label>
                                        <select name="category_id" class="filter-select" onchange="this.form.submit()">
                                            <option value="">Alle categorieën</option>
                                            @foreach($categories ?? [] as $category)
                                                <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                                    {{ $category->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="filter-group">
                                        <label class="filter-label">Bedrijf</label>
                                        <select name="company_id" class="filter-select" onchange="this.form.submit()">
                                            <option value="">Alle bedrijven</option>
                                            @foreach($companies ?? [] as $company)
                                                <option value="{{ $company->id }}" {{ request('company_id') == $company->id ? 'selected' : '' }}>
                                                    {{ $company->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="filter-group">
                                        <label class="filter-label">Sortering</label>
                                        <select name="sort_by" class="filter-select" onchange="this.form.submit()">
                                            <option value="publication_date" {{ request('sort_by') == 'publication_date' ? 'selected' : '' }}>Publicatiedatum</option>
                                            <option value="title" {{ request('sort_by') == 'title' ? 'selected' : '' }}>Titel</option>
                                            <option value="location" {{ request('sort_by') == 'location' ? 'selected' : '' }}>Locatie</option>
                                            <option value="company_id" {{ request('sort_by') == 'company_id' ? 'selected' : '' }}>Bedrijf</option>
                                            <option value="category_id" {{ request('sort_by') == 'category_id' ? 'selected' : '' }}>Categorie</option>
                                            <option value="status" {{ request('sort_by') == 'status' ? 'selected' : '' }}>Status</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="table-responsive">
                        <table class="table material-table table-hover">
                            <thead>
                                <tr>
                                    <th class="sortable" data-sort="id">ID</th>
                                    <th class="sortable" data-sort="title">Titel & Locatie</th>
                                    <th class="sortable" data-sort="company_id">Bedrijf</th>
                                    <th class="sortable" data-sort="category_id">Categorie</th>
                                    <th class="sortable" data-sort="status">Status</th>
                                    <th>Type</th>
                                    <th class="sortable" data-sort="publication_date">Publicatiedatum</th>
                                    <th>SEO</th>
                                    <th>Acties</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($vacancies as $vacancy)
                                    <tr>
                                        <td>{{ $vacancy->id }}</td>
                                        <td>
                                            <div class="vacancy-info">
                                                <div class="vacancy-title">{{ $vacancy->title }}</div>
                                                @if($vacancy->location)
                                                    <div class="vacancy-location">
                                                        <i class="fas fa-map-marker-alt"></i>{{ $vacancy->location }}
                                                    </div>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            @if($vacancy->company)
                                                <span class="vacancy-company">{{ $vacancy->company->name }}</span>
                                            @else
                                                <span class="text-muted">Geen bedrijf</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($vacancy->category)
                                                <span class="vacancy-category">{{ $vacancy->category->name }}</span>
                                            @else
                                                <span class="text-muted">Geen categorie</span>
                                            @endif
                                        </td>
                                        <td>
                                            @switch($vacancy->status)
                                                @case('Open')
                                                    <span class="status-badge status-open">Open</span>
                                                    @break
                                                @case('Gesloten')
                                                    <span class="status-badge status-closed">Gesloten</span>
                                                    @break
                                                @case('In behandeling')
                                                    <span class="status-badge status-processing">In behandeling</span>
                                                    @break
                                                @default
                                                    <span class="status-badge status-open">{{ $vacancy->status }}</span>
                                            @endswitch
                                        </td>
                                        <td>
                                            <span class="vacancy-type">{{ $vacancy->employment_type ?? 'Volledig' }}</span>
                                        </td>
                                        <td>
                                            <div class="date-info">
                                                <div>{{ $vacancy->publication_date?->format('d-m-Y') ?? 'Niet gepubliceerd' }}</div>
                                                <small>{{ $vacancy->publication_date?->format('H:i') ?? '' }}</small>
                                            </div>
                                        </td>
                                        <td>
                                            @php
                                                $seoScore = 0;
                                                if ($vacancy->meta_title) $seoScore++;
                                                if ($vacancy->meta_description) $seoScore++;
                                                if ($vacancy->meta_keywords) $seoScore++;
                                                if ($vacancy->description && strlen($vacancy->description) > 100) $seoScore++;
                                                
                                                $seoClass = $seoScore >= 3 ? 'seo-good' : ($seoScore >= 2 ? 'seo-warning' : 'seo-bad');
                                                $seoText = $seoScore >= 3 ? 'Goed' : ($seoScore >= 2 ? 'Gemiddeld' : 'Slecht');
                                            @endphp
                                            <span class="seo-indicator {{ $seoClass }}"></span>
                                            <small>{{ $seoText }}</small>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="{{ route('admin.vacancies.show', $vacancy) }}" class="action-btn action-btn-info" title="Bekijken">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('admin.vacancies.edit', $vacancy) }}" class="action-btn action-btn-warning" title="Bewerken">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                @if($vacancy->status !== 'Open' && $vacancy->status !== 'In behandeling')
                                                    <form action="{{ route('admin.vacancies.update', $vacancy) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        @method('PUT')
                                                        <input type="hidden" name="status" value="Open">
                                                        <input type="hidden" name="title" value="{{ $vacancy->title }}">
                                                        <input type="hidden" name="company_id" value="{{ $vacancy->company_id }}">
                                                        <input type="hidden" name="description" value="{{ $vacancy->description }}">
                                                        <button type="submit" class="action-btn action-btn-success" title="Openen">
                                                            <i class="fas fa-play"></i>
                                                        </button>
                                                    </form>
                                                @elseif($vacancy->status === 'In behandeling')
                                                    <form action="{{ route('admin.vacancies.update', $vacancy) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        @method('PUT')
                                                        <input type="hidden" name="status" value="Open">
                                                        <input type="hidden" name="title" value="{{ $vacancy->title }}">
                                                        <input type="hidden" name="company_id" value="{{ $vacancy->company_id }}">
                                                        <input type="hidden" name="description" value="{{ $vacancy->description }}">
                                                        <button type="submit" class="action-btn action-btn-success" title="Openen">
                                                            <i class="fas fa-play"></i>
                                                        </button>
                                                    </form>
                                                    <form action="{{ route('admin.vacancies.update', $vacancy) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        @method('PUT')
                                                        <input type="hidden" name="status" value="Gesloten">
                                                        <input type="hidden" name="title" value="{{ $vacancy->title }}">
                                                        <input type="hidden" name="company_id" value="{{ $vacancy->company_id }}">
                                                        <input type="hidden" name="description" value="{{ $vacancy->description }}">
                                                        <button type="submit" class="action-btn action-btn-danger" title="Sluiten">
                                                            <i class="fas fa-stop"></i>
                                                        </button>
                                                    </form>
                                                @else
                                                    <form action="{{ route('admin.vacancies.update', $vacancy) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        @method('PUT')
                                                        <input type="hidden" name="status" value="In behandeling">
                                                        <input type="hidden" name="title" value="{{ $vacancy->title }}">
                                                        <input type="hidden" name="company_id" value="{{ $vacancy->company_id }}">
                                                        <input type="hidden" name="description" value="{{ $vacancy->description }}">
                                                        <button type="submit" class="action-btn action-btn-warning" title="In behandeling">
                                                            <i class="fas fa-clock"></i>
                                                        </button>
                                                    </form>
                                                    <form action="{{ route('admin.vacancies.update', $vacancy) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        @method('PUT')
                                                        <input type="hidden" name="status" value="Gesloten">
                                                        <input type="hidden" name="title" value="{{ $vacancy->title }}">
                                                        <input type="hidden" name="company_id" value="{{ $vacancy->company_id }}">
                                                        <input type="hidden" name="description" value="{{ $vacancy->description }}">
                                                        <button type="submit" class="action-btn action-btn-danger" title="Sluiten">
                                                            <i class="fas fa-stop"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                                <form action="{{ route('admin.vacancies.destroy', $vacancy) }}" method="POST" class="d-inline" onsubmit="return confirm('Weet je zeker dat je deze vacature wilt verwijderen?')">
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
                                        <td colspan="9">
                                            <div class="empty-state">
                                                <i class="fas fa-briefcase"></i>
                                                <h5>Nog geen vacatures</h5>
                                                <p>Er zijn nog geen vacatures aangemaakt.</p>
                                                <a href="{{ route('admin.vacancies.create') }}" class="material-btn material-btn-primary">
                                                    <i class="fas fa-plus me-2"></i> Eerste Vacature Aanmaken
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($vacancies->hasPages())
                        <div class="pagination-wrapper">
                            {{ $vacancies->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Sortering functionaliteit
    const sortableHeaders = document.querySelectorAll('.sortable');
    
    sortableHeaders.forEach(header => {
        header.addEventListener('click', function() {
            const sortBy = this.dataset.sort;
            const currentSortBy = new URLSearchParams(window.location.search).get('sort_by');
            const currentSortOrder = new URLSearchParams(window.location.search).get('sort_order');
            
            let newSortOrder = 'desc';
            if (currentSortBy === sortBy && currentSortOrder === 'desc') {
                newSortOrder = 'asc';
            }
            
            const url = new URL(window.location);
            url.searchParams.set('sort_by', sortBy);
            url.searchParams.set('sort_order', newSortOrder);
            
            window.location.href = url.toString();
        });
    });
    
    // Huidige sortering markeren
    const currentSortBy = new URLSearchParams(window.location.search).get('sort_by');
    const currentSortOrder = new URLSearchParams(window.location.search).get('sort_order');
    
    if (currentSortBy) {
        const header = document.querySelector(`[data-sort="${currentSortBy}"]`);
        if (header) {
            header.classList.add(currentSortOrder === 'asc' ? 'sort-asc' : 'sort-desc');
        }
    }
    
    // Material Design ripple effect voor buttons
    const buttons = document.querySelectorAll('.material-btn, .action-btn');
    buttons.forEach(button => {
        button.addEventListener('click', function(e) {
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
});
</script>

<style>
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
</style>
@endsection
