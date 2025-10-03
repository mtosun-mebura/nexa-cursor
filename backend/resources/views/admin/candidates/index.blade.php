@extends('admin.layouts.app')

@section('title', 'Kandidaten')

@section('content')
<style>
    :root {
        --primary-color: #1976d2;
        --primary-light: #42a5f5;
        --primary-dark: #1565c0;
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

    .stats-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 24px;
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
        font-size: 0.875rem;
        font-weight: 500;
        letter-spacing: 1px;
        text-transform: uppercase;
        color: var(--medium-text);
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
        display: inline-block;
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
    
    .material-btn-primary {
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
        color: white;
    }
    
    .material-btn-primary:hover {
        background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary-color) 100%);
        color: white;
        transform: translateY(-2px);
        box-shadow: var(--shadow-medium);
    }

    .filters-section {
        margin-bottom: 32px;
        padding: 28px;
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        border-radius: var(--border-radius);
        border: 1px solid var(--border-color);
        box-shadow: var(--shadow-light);
        position: relative;
        overflow: hidden;
    }

    .filters-section::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
    }

    .filter-group {
        margin-bottom: 20px;
        position: relative;
    }

    .filter-label {
        display: block;
        font-size: 0.875rem;
        font-weight: 600;
        color: var(--dark-text);
        margin-bottom: 10px;
        text-transform: uppercase;
        letter-spacing: 0.8px;
    }

    .filter-select {
        width: 100%;
        padding: 8px 12px;
        border: 2px solid var(--border-color);
        border-radius: var(--border-radius);
        background: white;
        font-size: 0.875rem;
        font-weight: 500;
        color: var(--dark-text);
        transition: var(--transition);
        cursor: pointer;
        appearance: none;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right 12px center;
        background-size: 16px;
        padding-right: 40px;
        box-shadow: var(--shadow-light);
    }

    .filter-select:focus {
        outline: none;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(25, 118, 210, 0.1), var(--shadow-medium);
        transform: translateY(-1px);
    }

    .filter-select:hover {
        border-color: var(--primary-light);
        box-shadow: var(--shadow-medium);
        transform: translateY(-1px);
    }

    .filter-select option {
        padding: 8px 12px;
        background: white;
        color: var(--dark-text);
        font-size: 0.875rem;
        font-weight: 500;
    }

    .filter-select option:hover {
        background: var(--light-bg);
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

    .material-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }

    .material-table th {
        background: var(--light-bg);
        color: var(--dark-text);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-size: 0.75rem;
        padding: 12px 16px;
        border-bottom: 2px solid var(--border-color);
        position: relative;
        cursor: pointer;
        transition: var(--transition);
    }

    .material-table th:hover {
        background: var(--secondary-color);
    }

    .material-table th.sortable::after {
        content: '↕';
        position: absolute;
        right: 12px;
        color: var(--medium-text);
        font-size: 0.75rem;
    }

    .material-table th.sort-asc::after {
        content: '↑';
        color: var(--primary-color);
    }

    .material-table th.sort-desc::after {
        content: '↓';
        color: var(--primary-color);
    }

    .material-table td {
        padding: 12px 16px;
        border-bottom: 1px solid var(--border-color);
        vertical-align: middle;
    }

    .material-table tbody tr:hover {
        background: var(--light-bg);
    }

    .candidate-info {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .candidate-name {
        font-weight: 600;
        color: var(--dark-text);
    }

    .candidate-location {
        font-size: 0.875rem;
        color: var(--medium-text);
    }

    .experience-badge {
        background: linear-gradient(135deg, var(--success-color) 0%, #66bb6a 100%);
        color: white;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .education-badge {
        background: linear-gradient(135deg, var(--warning-color) 0%, #ffb74d 100%);
        color: white;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .status-badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        text-align: center;
        min-width: 80px;
    }

    .status-active {
        background: linear-gradient(135deg, var(--success-color) 0%, #66bb6a 100%);
        color: white;
    }

    .status-pending {
        background: linear-gradient(135deg, var(--warning-color) 0%, #ffb74d 100%);
        color: white;
    }

    .status-rejected {
        background: linear-gradient(135deg, var(--danger-color) 0%, #ef5350 100%);
        color: white;
    }

    .status-hired {
        background: linear-gradient(135deg, var(--info-color) 0%, #64b5f6 100%);
        color: white;
    }

    .candidate-type {
        background: var(--secondary-color);
        color: var(--primary-color);
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .date-info {
        display: flex;
        flex-direction: column;
        gap: 2px;
    }

    .date {
        font-weight: 600;
        color: var(--dark-text);
        font-size: 0.875rem;
    }

    .time {
        font-size: 0.75rem;
        color: var(--medium-text);
    }

    .action-buttons {
        display: flex;
        gap: 6px;
        flex-wrap: wrap;
    }

    .action-btn {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        text-decoration: none;
        transition: var(--transition);
        cursor: pointer;
        font-size: 0.875rem;
    }

    .action-btn:hover {
        transform: scale(1.1);
        box-shadow: var(--shadow-medium);
    }

    .action-btn-info {
        background: var(--info-color);
    }

    .action-btn-warning {
        background: var(--warning-color);
    }

    .action-btn-success {
        background: var(--success-color);
    }

    .action-btn-secondary {
        background: var(--medium-text);
    }

    .action-btn-danger {
        background: var(--danger-color);
    }

    .results-info-wrapper {
        display: flex;
        justify-content: center;
        align-items: center;
        margin: 24px 0;
        padding: 10px 24px;
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        border-radius: var(--border-radius);
        border: 1px solid var(--border-color);
        box-shadow: var(--shadow-light);
    }

    .results-info {
        display: flex;
        align-items: center;
    }

    .results-text {
        font-size: 0.875rem;
        font-weight: 500;
        color: var(--dark-text);
        display: flex;
        align-items: center;
    }

    .results-text i {
        color: var(--primary-color);
        font-size: 1rem;
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

    .per-page-selector {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .per-page-label {
        font-size: 0.875rem;
        font-weight: 500;
        color: var(--dark-text);
        white-space: nowrap;
    }

    .per-page-select {
        padding: 8px 12px;
        border: 2px solid var(--border-color);
        border-radius: var(--border-radius);
        background: white;
        font-size: 0.875rem;
        font-weight: 500;
        color: var(--dark-text);
        transition: var(--transition);
        cursor: pointer;
        appearance: none;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right 8px center;
        background-size: 14px;
        padding-right: 32px;
        min-width: 80px;
        box-shadow: var(--shadow-light);
    }

    .per-page-select:focus {
        outline: none;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(25, 118, 210, 0.1), var(--shadow-medium);
        transform: translateY(-1px);
    }

    .per-page-select:hover {
        border-color: var(--primary-light);
        box-shadow: var(--shadow-medium);
        transform: translateY(-1px);
    }

    .pagination-wrapper {
        margin-top: 0;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .pagination {
        display: flex;
        align-items: center;
        gap: 6px;
        list-style: none;
        margin: 0;
        padding: 0;
    }

    .page-item {
        margin: 0;
    }

    .page-link {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        border: none;
        background: white;
        color: var(--dark-text);
        text-decoration: none;
        font-weight: 600;
        font-size: 0.875rem;
        transition: var(--transition);
        box-shadow: var(--shadow-light);
        position: relative;
        overflow: hidden;
    }

    .page-link:hover {
        background: var(--light-bg);
        color: var(--primary-color);
        transform: translateY(-2px);
        box-shadow: var(--shadow-medium);
        text-decoration: none;
    }

    .page-item.active .page-link {
        background: linear-gradient(135deg, #ff9800 0%, #ffb74d 100%);
        color: white;
        box-shadow: var(--shadow-medium);
        transform: translateY(-2px);
    }

    .page-item.disabled .page-link {
        background: #f5f5f5;
        color: #bdbdbd;
        cursor: not-allowed;
        transform: none;
        box-shadow: none;
    }

    .page-item.disabled .page-link:hover {
        background: #f5f5f5;
        color: #bdbdbd;
        transform: none;
        box-shadow: none;
    }

    .page-link:focus {
        outline: none;
        box-shadow: 0 0 0 3px rgba(255, 152, 0, 0.3);
    }

    .empty-state {
        text-align: center;
        padding: 64px 32px;
        color: var(--medium-text);
    }

    .empty-state i {
        font-size: 4rem;
        margin-bottom: 24px;
        color: var(--border-color);
    }

    .empty-state h4 {
        margin-bottom: 16px;
        color: var(--dark-text);
    }

    @media (max-width: 768px) {
        .stats-cards {
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
        }
        
        .material-card .card-body {
            padding: 24px 16px;
        }
        
        .filters-section {
            padding: 10px;
        }
        
        .action-buttons {
            gap: 4px;
        }
        
        .action-btn {
            width: 32px;
            height: 36px;
            font-size: 0.75rem;
        }
    }
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Dashboard Statistieken -->
            <div class="stats-cards">
                <div class="stat-card">
                    <div class="stat-number" style="background: linear-gradient(135deg, #4caf50 0%, #66bb6a 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">{{ $stats['active_candidates'] }}</div>
                    <div class="stat-label">ACTIEF</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" style="background: linear-gradient(135deg, #f44336 0%, #ef5350 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">{{ $stats['rejected_candidates'] }}</div>
                    <div class="stat-label">INACTIEF</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" style="background: linear-gradient(135deg, #ff9800 0%, #ffb74d 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">{{ $stats['pending_candidates'] }}</div>
                    <div class="stat-label">TEST MODUS</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" style="background: linear-gradient(135deg, #1976d2 0%, #42a5f5 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">{{ $stats['total_candidates'] }}</div>
                    <div class="stat-label">TOTAAL</div>
                </div>
            </div>

            <div class="material-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-user-graduate me-2"></i> Kandidaten Overzicht
                    </h5>
                    <a href="{{ route('admin.candidates.create') }}" class="material-btn material-btn-primary">
                        <i class="fas fa-plus me-2"></i> NIEUWE KANDIDAAT
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
                        <form method="GET" action="{{ route('admin.candidates.index') }}" id="filters-form">
                            <div class="row">
                                @if(auth()->user()->hasRole('super-admin'))
                                    <!-- Super-admin: 5 kolommen over gehele breedte -->
                                    <div class="col-md-2">
                                        <div class="filter-group">
                                            <label class="filter-label">Status</label>
                                            <select name="status" class="filter-select" onchange="this.form.submit()">
                                                <option value="">Alle statussen</option>
                                                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Actief</option>
                                                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>In Afwachting</option>
                                                <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Afgewezen</option>
                                                <option value="hired" {{ request('status') == 'hired' ? 'selected' : '' }}>Aangenomen</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="filter-group">
                                            <label class="filter-label">Ervaring</label>
                                            <select name="experience" class="filter-select" onchange="this.form.submit()">
                                                <option value="">Alle niveaus</option>
                                                <option value="1" {{ request('experience') == '1' ? 'selected' : '' }}>1+ jaar</option>
                                                <option value="3" {{ request('experience') == '3' ? 'selected' : '' }}>3+ jaar</option>
                                                <option value="5" {{ request('experience') == '5' ? 'selected' : '' }}>5+ jaar</option>
                                                <option value="7" {{ request('experience') == '7' ? 'selected' : '' }}>7+ jaar</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="filter-group">
                                            <label class="filter-label">Opleiding</label>
                                            <select name="education" class="filter-select" onchange="this.form.submit()">
                                                <option value="">Alle niveaus</option>
                                                <option value="high_school" {{ request('education') == 'high_school' ? 'selected' : '' }}>Middelbare School</option>
                                                <option value="vocational" {{ request('education') == 'vocational' ? 'selected' : '' }}>MBO</option>
                                                <option value="bachelor" {{ request('education') == 'bachelor' ? 'selected' : '' }}>HBO/Bachelor</option>
                                                <option value="master" {{ request('education') == 'master' ? 'selected' : '' }}>WO/Master</option>
                                                <option value="phd" {{ request('education') == 'phd' ? 'selected' : '' }}>PhD/Doctoraat</option>
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
                                            <a href="{{ route('admin.candidates.index') }}" class="btn btn-outline-secondary w-100" style="height: 36px; display: flex; align-items: center; justify-content: center; gap: 6px; text-decoration: none;">
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
                                                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>In Afwachting</option>
                                                <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Afgewezen</option>
                                                <option value="hired" {{ request('status') == 'hired' ? 'selected' : '' }}>Aangenomen</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="filter-group">
                                            <label class="filter-label">Ervaring</label>
                                            <select name="experience" class="filter-select" onchange="this.form.submit()">
                                                <option value="">Alle niveaus</option>
                                                <option value="1" {{ request('experience') == '1' ? 'selected' : '' }}>1+ jaar</option>
                                                <option value="3" {{ request('experience') == '3' ? 'selected' : '' }}>3+ jaar</option>
                                                <option value="5" {{ request('experience') == '5' ? 'selected' : '' }}>5+ jaar</option>
                                                <option value="7" {{ request('experience') == '7' ? 'selected' : '' }}>7+ jaar</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="filter-group">
                                            <label class="filter-label">Opleiding</label>
                                            <select name="education" class="filter-select" onchange="this.form.submit()">
                                                <option value="">Alle niveaus</option>
                                                <option value="high_school" {{ request('education') == 'high_school' ? 'selected' : '' }}>Middelbare School</option>
                                                <option value="vocational" {{ request('education') == 'vocational' ? 'selected' : '' }}>MBO</option>
                                                <option value="bachelor" {{ request('education') == 'bachelor' ? 'selected' : '' }}>HBO/Bachelor</option>
                                                <option value="master" {{ request('education') == 'master' ? 'selected' : '' }}>WO/Master</option>
                                                <option value="phd" {{ request('education') == 'phd' ? 'selected' : '' }}>PhD/Doctoraat</option>
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
                                @endif
                            </div>
                        </form>
                    </div>

                    @if($candidates->count() > 0)
                    <div class="table-responsive">
                        <table class="table material-table table-hover">
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
                                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'first_name', 'order' => request('sort') == 'first_name' && request('order') == 'asc' ? 'desc' : 'asc']) }}" class="sortable-header">
                                            NAAM & BESCHRIJVING
                                            @if(request('sort') == 'first_name')
                                                <i class="fas fa-sort-{{ request('order') == 'asc' ? 'up' : 'down' }}"></i>
                                            @else
                                                <i class="fas fa-sort"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th>
                                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'experience_years', 'order' => request('sort') == 'experience_years' && request('order') == 'asc' ? 'desc' : 'asc']) }}" class="sortable-header">
                                            ERVARING
                                            @if(request('sort') == 'experience_years')
                                                <i class="fas fa-sort-{{ request('order') == 'asc' ? 'up' : 'down' }}"></i>
                                            @else
                                                <i class="fas fa-sort"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th>
                                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'education_level', 'order' => request('sort') == 'education_level' && request('order') == 'asc' ? 'desc' : 'asc']) }}" class="sortable-header">
                                            OPLEIDING
                                            @if(request('sort') == 'education_level')
                                                <i class="fas fa-sort-{{ request('order') == 'asc' ? 'up' : 'down' }}"></i>
                                            @else
                                                <i class="fas fa-sort"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th>
                                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'status', 'order' => request('sort') == 'status' && request('order') == 'asc' ? 'desc' : 'asc']) }}" class="sortable-header">
                                            STATUS
                                            @if(request('sort') == 'status')
                                                <i class="fas fa-sort-{{ request('order') == 'asc' ? 'up' : 'down' }}"></i>
                                            @else
                                                <i class="fas fa-sort"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th>FOTO</th>
                                    <th>TYPE</th>
                                    <th>
                                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'created_at', 'order' => request('sort') == 'created_at' && request('order') == 'asc' ? 'desc' : 'asc']) }}" class="sortable-header">
                                            AANGEMAAKT
                                            @if(request('sort') == 'created_at')
                                                <i class="fas fa-sort-{{ request('order') == 'asc' ? 'up' : 'down' }}"></i>
                                            @else
                                                <i class="fas fa-sort"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th>ACTIES</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($candidates as $candidate)
                                    <tr>
                                        <td>
                                            <strong>{{ $candidate->id }}</strong>
                                        </td>
                                        <td>
                                            <div class="candidate-info">
                                                <div class="candidate-name">
                                                    <strong>{{ $candidate->full_name }}</strong>
                                                </div>
                                                <div class="candidate-location">
                                                    <i class="fas fa-map-marker-alt me-1"></i>
                                                    {{ $candidate->city }}, {{ $candidate->country }}
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="experience-info">
                                                <span class="experience-badge">{{ $candidate->experience_years }}+ jaar</span>
                                            </div>
                                        </td>
                                        <td>
                                            @if($candidate->education_level)
                                                <span class="education-badge">{{ $candidate->education_level_display }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="status-badge status-{{ $candidate->status }}">
                                                @if($candidate->status == 'active')
                                                    ACTIEF
                                                @elseif($candidate->status == 'pending')
                                                    TEST MODUS
                                                @elseif($candidate->status == 'rejected')
                                                    INACTIEF
                                                @elseif($candidate->status == 'hired')
                                                    AANGENOMEN
                                                @else
                                                    {{ ucfirst($candidate->status) }}
                                                @endif
                                            </span>
                                        </td>
                                        <td>
                                            @if($candidate->photo_blob)
                                                <div class="candidate-photo">
                                                    <img src="{{ route('candidate.photo', ['token' => $candidate->getCompanyPhotoToken(1)]) }}" 
                                                         alt="Kandidaat foto" 
                                                         class="candidate-photo-img"
                                                         style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid #e0e0e0;">
                                                </div>
                                            @else
                                                <div class="no-photo">
                                                    <i class="fas fa-user-circle" style="font-size: 24px; color: #ccc;"></i>
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="candidate-type">
                                                {{ $candidate->preferred_work_type ? ucfirst($candidate->preferred_work_type) : 'Fulltime' }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="date-info">
                                                <div class="date">{{ $candidate->created_at->format('d-m-Y') }}</div>
                                                <div class="time">{{ $candidate->created_at->format('H:i') }}</div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="{{ route('admin.candidates.show', $candidate) }}" 
                                                   class="action-btn action-btn-info" title="Bekijken">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('admin.candidates.edit', $candidate) }}" 
                                                   class="action-btn action-btn-warning" title="Bewerken">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="{{ route('admin.candidates.download-cv', $candidate) }}" 
                                                   class="action-btn action-btn-success" title="Download CV">
                                                    <i class="fas fa-play"></i>
                                                </a>
                                                <form action="{{ route('admin.candidates.toggle-status', $candidate) }}" 
                                                      method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="action-btn action-btn-secondary" title="Status wijzigen">
                                                        <i class="fas fa-square"></i>
                                                    </button>
                                                </form>
                                                <form action="{{ route('admin.candidates.destroy', $candidate) }}" 
                                                      method="POST" class="d-inline" 
                                                      onsubmit="return confirm('Weet je zeker dat je deze kandidaat wilt verwijderen?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="action-btn action-btn-danger" title="Verwijderen">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
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
                                Toon {{ $candidates->firstItem() ?? 0 }} tot {{ $candidates->lastItem() ?? 0 }} van {{ $candidates->total() }} resultaten
                            </span>
                        </div>
                    </div>

                    <!-- Pagination -->
                    @if($candidates->hasPages())
                        <div class="pagination-wrapper">
                            <nav aria-label="Paginering">
                                <ul class="pagination">
                                    {{-- Previous Page Link --}}
                                    @if ($candidates->onFirstPage())
                                        <li class="page-item disabled">
                                            <span class="page-link">
                                                <i class="fas fa-chevron-left"></i>
                                            </span>
                                        </li>
                                    @else
                                        <li class="page-item">
                                            <a class="page-link" href="{{ $candidates->previousPageUrl() }}">
                                                <i class="fas fa-chevron-left"></i>
                                            </a>
                                        </li>
                                    @endif

                                    {{-- Pagination Elements --}}
                                    @foreach ($candidates->getUrlRange(1, $candidates->lastPage()) as $page => $url)
                                        @if ($page == $candidates->currentPage())
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
                                    @if ($candidates->hasMorePages())
                                        <li class="page-item">
                                            <a class="page-link" href="{{ $candidates->nextPageUrl() }}">
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
                        <i class="fas fa-users"></i>
                        <h4>Geen kandidaten gevonden</h4>
                        <p>Er zijn momenteel geen kandidaten beschikbaar.</p>
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
            const sortField = this.dataset.sort;
            const currentUrl = new URL(window.location);
            const currentSort = currentUrl.searchParams.get('sort');
            const currentOrder = currentUrl.searchParams.get('order');
            
            let newOrder = 'asc';
            if (currentSort === sortField && currentOrder === 'asc') {
                newOrder = 'desc';
            }
            
            currentUrl.searchParams.set('sort', sortField);
            currentUrl.searchParams.set('order', newOrder);
            window.location.href = currentUrl.toString();
        });
    });
    
    // Huidige sortering markeren
    const currentSort = '{{ request("sort", "created_at") }}';
    const currentOrder = '{{ request("order", "desc") }}';
    
    sortableHeaders.forEach(header => {
        if (header.dataset.sort === currentSort) {
            header.classList.remove('sort-asc', 'sort-desc');
            header.classList.add(`sort-${currentOrder}`);
        }
    });
});
</script>
@endsection
