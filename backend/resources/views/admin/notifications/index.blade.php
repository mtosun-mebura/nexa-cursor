@extends('admin.layouts.app')

@section('title', 'Notificaties Beheer')

@section('content')
<style>
    :root {
        --primary-color: #ff6b6b;
        --primary-light: #ee5a24;
        --primary-dark: #e74c3c;
        --secondary-color: #ffeaea;
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
        table-layout: fixed;
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
    
    .material-table thead th:nth-child(1) { width: 80px; min-width: 80px; text-align: center; }   /* ID */
    .material-table thead th:nth-child(2) { width: 25%; }  /* Gebruiker */
    .material-table thead th:nth-child(3) { width: 35%; }  /* Inhoud */
    .material-table thead th:nth-child(4) { width: 12%; }  /* Status */
    .material-table thead th:nth-child(5) { width: 12%; }  /* Gemaakt op */
    .material-table thead th:nth-child(6) { width: 8%; }   /* Acties */

    .material-table thead th:hover {
        background: var(--secondary-color);
        color: var(--primary-color);
    }
    
    .material-table thead th.sortable {
        cursor: pointer;
        position: relative;
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
    
    .material-table thead th.sortable:hover::after {
        opacity: 1;
        color: var(--primary-color);
    }

    .material-table tbody td {
        padding: 12px 16px;
        border-bottom: 1px solid var(--border-color);
        vertical-align: middle;
        transition: var(--transition);
        word-wrap: break-word;
        overflow-wrap: break-word;
    }
    
    .material-table tbody td:nth-child(1) { width: 80px; min-width: 80px; text-align: center; }   /* ID */
    .material-table tbody td:nth-child(2) { width: 25%; }  /* Gebruiker */
    .material-table tbody td:nth-child(3) { width: 35%; }  /* Inhoud */
    .material-table tbody td:nth-child(4) { width: 12%; }  /* Status */
    .material-table tbody td:nth-child(5) { width: 12%; }  /* Gemaakt op */
    .material-table tbody td:nth-child(6) { width: 8%; }   /* Acties */

    .material-table tbody tr {
        transition: var(--transition);
        background-color: white;
    }

    .material-table tbody tr:hover {
        background-color: #ffeaea !important;
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

    .status-read {
        background: linear-gradient(135deg, #e8f5e8 0%, #81c784 100%);
        color: #388e3c;
        border: 2px solid #81c784;
    }

    .status-unread {
        background: linear-gradient(135deg, #fff3e0 0%, #ffb74d 100%);
        color: #f57c00;
        border: 2px solid #ffb74d;
    }

    .action-buttons {
        display: flex;
        gap: 6px;
        flex-wrap: wrap;
        justify-content: flex-start;
        min-width: 120px;
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
        box-shadow: var(--shadow-light);
        cursor: pointer;
        position: relative;
        overflow: hidden;
        text-decoration: none;
        font-size: 14px;
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

    .user-info {
        display: flex;
        flex-direction: column;
    }

    .user-name {
        font-weight: 600;
        color: var(--dark-text);
        margin-bottom: 4px;
        font-size: 16px;
    }

    .user-email {
        font-size: 12px;
        color: var(--medium-text);
        margin-top: 4px;
    }

    .notification-content {
        max-width: 300px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        font-size: 12px;
        color: var(--dark-text);
        line-height: 1.4;
    }

    .notification-content:hover {
        white-space: normal;
        word-wrap: break-word;
    }

    .notification-date {
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
        box-shadow: 0 0 0 0.2rem rgba(255, 107, 107, 0.25);
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
        height: 36Ípx;
        border-radius: var(--border-radius);
        border: 1px solid var(--border-color);
        background: white;
        color: var(--dark-text);
        text-decoration: none;
        transition: var(--transition);
        box-shadow: var(--shadow-light);
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
        transform: translateY(-2px);
        box-shadow: var(--shadow-medium);
    }

    .page-item.disabled .page-link {
        background: var(--light-bg);
        border-color: var(--border-color);
        color: var(--medium-text);
        cursor: not-allowed;
        opacity: 0.5;
    }

    .page-item.disabled .page-link:hover {
        transform: none;
        box-shadow: var(--shadow-light);
        border-color: var(--border-color);
    }

    .results-info-wrapper {
        padding: 12px 24px;
        background: var(--light-bg);
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

    .pagination-wrapper {
        padding: 12px 24px;
        background: var(--light-bg);
        border-top: 1px solid var(--border-color);
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
                    <div class="stat-number" style="background: linear-gradient(135deg, #4caf50 0%, #81c784 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">{{ $notifications->where('read_at', '!=', null)->count() }}</div>
                    <div class="stat-label">Gelezen</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" style="background: linear-gradient(135deg, #ff9800 0%, #ffb74d 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">{{ $notifications->where('read_at', null)->count() }}</div>
                    <div class="stat-label">Ongelezen</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" style="background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">{{ $notifications->count() }}</div>
                    <div class="stat-label">Totaal</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" style="background: linear-gradient(135deg, #9c27b0 0%, #ba68c8 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">{{ $notifications->unique('user_id')->count() }}</div>
                    <div class="stat-label">Gebruikers</div>
                </div>
            </div>

            <div class="material-card">
                <!-- Header -->
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-bell me-2"></i> Notificaties Beheer
                    </h5>
                    <div class="d-flex gap-2">
                        @can('create-notifications')
                            <a href="{{ route('admin.notifications.create') }}" class="material-btn material-btn-primary">
                                <i class="fas fa-plus me-2"></i> Nieuwe Notificatie
                            </a>
                        @endcan
                    </div>
                </div>

                <!-- Success Message -->
                @if(session('success'))
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i>
                        {{ session('success') }}
                    </div>
                @endif

                <!-- Filters -->
                <div class="filters-section">
                    <form method="GET" action="{{ route('admin.notifications.index') }}" id="filters-form">
                        <div class="row">
                            @if(auth()->user()->hasRole('super-admin'))
                                <!-- Super-admin: 5 kolommen over gehele breedte -->
                                <div class="col-md-2">
                                    <div class="filter-group">
                                        <label class="filter-label">Status</label>
                                        <select name="status" class="filter-select" onchange="this.form.submit()">
                                            <option value="">Alle statussen</option>
                                            <option value="unread" {{ request('status') == 'unread' ? 'selected' : '' }}>Ongelezen</option>
                                            <option value="read" {{ request('status') == 'read' ? 'selected' : '' }}>Gelezen</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="filter-group">
                                        <label class="filter-label">Type</label>
                                        <select name="type" class="filter-select" onchange="this.form.submit()">
                                            <option value="">Alle types</option>
                                            <option value="info" {{ request('type') == 'info' ? 'selected' : '' }}>Info</option>
                                            <option value="warning" {{ request('type') == 'warning' ? 'selected' : '' }}>Waarschuwing</option>
                                            <option value="error" {{ request('type') == 'error' ? 'selected' : '' }}>Fout</option>
                                            <option value="success" {{ request('type') == 'success' ? 'selected' : '' }}>Succes</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="filter-group">
                                        <label class="filter-label">Prioriteit</label>
                                        <select name="priority" class="filter-select" onchange="this.form.submit()">
                                            <option value="">Alle prioriteiten</option>
                                            <option value="low" {{ request('priority') == 'low' ? 'selected' : '' }}>Laag</option>
                                            <option value="medium" {{ request('priority') == 'medium' ? 'selected' : '' }}>Gemiddeld</option>
                                            <option value="high" {{ request('priority') == 'high' ? 'selected' : '' }}>Hoog</option>
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
                                        <a href="{{ route('admin.notifications.index') }}" class="btn btn-outline-secondary w-100" style="height: 36px; display: flex; align-items: center; justify-content: center; gap: 6px; text-decoration: none;">
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
                                            <option value="unread" {{ request('status') == 'unread' ? 'selected' : '' }}>Ongelezen</option>
                                            <option value="read" {{ request('status') == 'read' ? 'selected' : '' }}>Gelezen</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="filter-group">
                                        <label class="filter-label">Type</label>
                                        <select name="type" class="filter-select" onchange="this.form.submit()">
                                            <option value="">Alle types</option>
                                            <option value="info" {{ request('type') == 'info' ? 'selected' : '' }}>Info</option>
                                            <option value="warning" {{ request('type') == 'warning' ? 'selected' : '' }}>Waarschuwing</option>
                                            <option value="error" {{ request('type') == 'error' ? 'selected' : '' }}>Fout</option>
                                            <option value="success" {{ request('type') == 'success' ? 'selected' : '' }}>Succes</option>
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
                                        <a href="{{ route('admin.notifications.index') }}" class="btn btn-outline-secondary w-100" style="height: 36px; display: flex; align-items: center; justify-content: center; gap: 6px; text-decoration: none;">
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
                    @if($notifications->count() > 0)
                        <div class="table-responsive">
                            <table class="material-table">
                                <thead>
                                    <tr>
                                        <th class="sortable {{ request('sort') == 'id' ? (request('order') == 'asc' ? 'sort-asc' : 'sort-desc') : '' }}" data-sort="id">
                                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'id', 'order' => request('sort') == 'id' && request('order') == 'asc' ? 'desc' : 'asc']) }}" style="text-decoration: none; color: inherit;">
                                                ID
                                            </a>
                                        </th>
                                        <th class="sortable {{ request('sort') == 'user_id' ? (request('order') == 'asc' ? 'sort-asc' : 'sort-desc') : '' }}" data-sort="user_id">
                                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'user_id', 'order' => request('sort') == 'user_id' && request('order') == 'asc' ? 'desc' : 'asc']) }}" style="text-decoration: none; color: inherit;">
                                                Gebruiker
                                            </a>
                                        </th>
                                        <th class="sortable {{ request('sort') == 'type' ? (request('order') == 'asc' ? 'sort-asc' : 'sort-desc') : '' }}" data-sort="type">
                                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'type', 'order' => request('sort') == 'type' && request('order') == 'asc' ? 'desc' : 'asc']) }}" style="text-decoration: none; color: inherit;">
                                                Inhoud
                                            </a>
                                        </th>
                                        <th class="sortable {{ request('sort') == 'status' ? (request('order') == 'asc' ? 'sort-asc' : 'sort-desc') : '' }}" data-sort="status">
                                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'status', 'order' => request('sort') == 'status' && request('order') == 'asc' ? 'desc' : 'asc']) }}" style="text-decoration: none; color: inherit;">
                                                Status
                                            </a>
                                        </th>
                                        <th class="sortable {{ request('sort') == 'created_at' ? (request('order') == 'asc' ? 'sort-asc' : 'sort-desc') : '' }}" data-sort="created_at">
                                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'created_at', 'order' => request('sort') == 'created_at' && request('order') == 'asc' ? 'desc' : 'asc']) }}" style="text-decoration: none; color: inherit;">
                                                Gemaakt op
                                            </a>
                                        </th>
                                        <th>Acties</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($notifications as $notification)
                                        <tr>
                                            <td>
                                                <strong>{{ $notification->id }}</strong>
                                            </td>
                                            <td>
                                                <div class="user-info">
                                                    @if($notification->user)
                                                        <div class="user-name">
                                                            {{ $notification->user->first_name }} {{ $notification->user->last_name }}
                                                        </div>
                                                        <div class="user-email">
                                                            {{ $notification->user->email }}
                                                        </div>
                                                    @else
                                                        <span class="text-muted">Gebruiker niet gevonden</span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                <div class="notification-content" title="{{ $notification->message }}">
                                                    {{ $notification->message }}
                                                </div>
                                            </td>
                                            <td>
                                                <span class="status-badge {{ $notification->read_at ? 'status-read' : 'status-unread' }}">
                                                    {{ $notification->read_at ? 'Gelezen' : 'Ongelezen' }}
                                                </span>
                                            </td>
                                            <td>
                                                <div class="notification-date">
                                                    {{ $notification->created_at->format('d-m-Y H:i') }}
                                                </div>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <a href="{{ route('admin.notifications.show', $notification) }}"
                                                       class="action-btn action-btn-info"
                                                       title="Bekijken">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    @can('edit-notifications')
                                                        <a href="{{ route('admin.notifications.edit', $notification) }}"
                                                           class="action-btn action-btn-warning"
                                                           title="Bewerken">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                    @endcan
                                                    <form action="{{ route('admin.notifications.destroy', $notification) }}"
                                                          method="POST"
                                                          style="display: inline;"
                                                          onsubmit="return confirm('Weet je zeker dat je deze notificatie wilt verwijderen?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit"
                                                                class="action-btn action-btn-danger"
                                                                title="Verwijderen">
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
                                <div class="results-text">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Toon {{ $notifications->firstItem() ?? 0 }} tot {{ $notifications->lastItem() ?? 0 }} van {{ $notifications->total() }} resultaten
                                </div>
                            </div>
                        </div>

                        <!-- Pagination -->
                        @if($notifications->hasPages())
                            <div class="pagination-wrapper">
                                <nav aria-label="Paginering">
                                    <ul class="pagination">
                                        {{-- Previous Page Link --}}
                                        @if ($notifications->onFirstPage())
                                            <li class="page-item disabled">
                                                <span class="page-link">
                                                    <i class="fas fa-chevron-left"></i>
                                                </span>
                                            </li>
                                        @else
                                            <li class="page-item">
                                                <a class="page-link" href="{{ $notifications->previousPageUrl() }}">
                                                    <i class="fas fa-chevron-left"></i>
                                                </a>
                                            </li>
                                        @endif

                                        {{-- Pagination Elements --}}
                                        @foreach ($notifications->getUrlRange(1, $notifications->lastPage()) as $page => $url)
                                            @if ($page == $notifications->currentPage())
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
                                        @if ($notifications->hasMorePages())
                                            <li class="page-item">
                                                <a class="page-link" href="{{ $notifications->nextPageUrl() }}">
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
                            <i class="fas fa-bell"></i>
                            <h4>Geen notificaties gevonden</h4>
                            <p>Er zijn nog geen notificaties aangemaakt. Maak je eerste notificatie aan om te beginnen.</p>
                            @can('create-notifications')
                                <a href="{{ route('admin.notifications.create') }}" class="material-btn material-btn-primary">
                                    <i class="fas fa-plus me-2"></i> Nieuwe Notificatie
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
