@extends('admin.layouts.app')

@section('title', 'Betalingsproviders')

@section('content')
<style>
    :root {
        --primary-color: #00897b;
        --primary-light: #4db6ac;
        --primary-dark: #00695c;
        --secondary-color: #e0f2f1;
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
    .material-table thead th:nth-child(2) { width: 25%; }  /* Naam & Beschrijving */
    .material-table thead th:nth-child(3) { width: 15%; }  /* Provider Type */
    .material-table thead th:nth-child(4) { width: 12%; }  /* Status */
    .material-table thead th:nth-child(5) { width: 12%; }  /* Modus */
    .material-table thead th:nth-child(6) { width: 12%; }  /* Aangemaakt */
    .material-table thead th:nth-child(7) { width: 12%; }   /* Acties */
    
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
    .material-table tbody td:nth-child(2) { width: 25%; }  /* Naam & Beschrijving */
    .material-table tbody td:nth-child(3) { width: 15%; }  /* Provider Type */
    .material-table tbody td:nth-child(4) { width: 12%; }  /* Status */
    .material-table tbody td:nth-child(5) { width: 12%; }  /* Modus */
    .material-table tbody td:nth-child(6) { width: 12%; }  /* Aangemaakt */
    .material-table tbody td:nth-child(7) { width: 12%; }   /* Acties */
    
    .material-table tbody tr {
        transition: var(--transition);
        background-color: white;
    }
    
    .material-table tbody tr:hover {
        background-color: #f3f4f6 !important;
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
    
    .action-btn-success {
        background: linear-gradient(135deg, var(--success-color) 0%, #66bb6a 100%);
        color: white;
    }
    
    .provider-info {
        display: flex;
        flex-direction: column;
    }
    
    .provider-name {
        font-weight: 600;
        color: var(--dark-text);
        margin-bottom: 4px;
        font-size: 16px;
    }
    
    .provider-description {
        font-size: 12px;
        color: var(--medium-text);
        display: flex;
        align-items: center;
        gap: 6px;
    }
    
    .provider-type {
        background: linear-gradient(135deg, #e0f2f1 0%, #b2dfdb 100%);
        color: #00695c;
        padding: 6px 12px;
        border-radius: 16px;
        font-size: 12px;
        font-weight: 600;
        display: inline-block;
    }
    
    .provider-mode {
        background: linear-gradient(135deg, #fff3e0 0%, #ffe0b2 100%);
        color: #f57c00;
        padding: 6px 12px;
        border-radius: 16px;
        font-size: 12px;
        font-weight: 600;
        display: inline-block;
    }
    
    /* Dark Mode Styling for Payment Providers */
    [data-theme="dark"] .provider-type,
    [data-theme="dark"] .provider-type[style],
    .dark .provider-type,
    .dark .provider-type[style] {
        background: linear-gradient(135deg, #0d9488 0%, #14b8a6 100%) !important;
        background-image: none !important;
        color: #ffffff !important;
    }
    
    [data-theme="dark"] .provider-mode,
    [data-theme="dark"] .provider-mode[style],
    .dark .provider-mode,
    .dark .provider-mode[style] {
        background: linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%) !important;
        background-image: none !important;
        color: #ffffff !important;
    }
    
    [data-theme="dark"] .status-badge.status-active,
    [data-theme="dark"] .status-badge.status-active[style],
    .dark .status-badge.status-active,
    .dark .status-badge.status-active[style] {
        background: linear-gradient(135deg, #16a34a 0%, #22c55e 100%) !important;
        background-image: none !important;
        color: #ffffff !important;
        border-color: #22c55e !important;
    }
    
    [data-theme="dark"] .status-badge.status-inactive,
    [data-theme="dark"] .status-badge.status-inactive[style],
    .dark .status-badge.status-inactive,
    .dark .status-badge.status-inactive[style] {
        background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%) !important;
        background-image: none !important;
        color: #ffffff !important;
        border-color: #ef4444 !important;
    }
    
    .date-info {
        font-size: 12px;
        color: var(--medium-text);
    }
    
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
    
    .filter-select:hover {
        border-color: var(--primary-light);
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
    
    .auto-dismiss {
        animation: slideDown 0.3s ease-out;
    }
    
    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .auto-dismiss.fade-out {
        animation: slideUp 0.3s ease-in forwards;
    }
    
    @keyframes slideUp {
        from {
            opacity: 1;
            transform: translateY(0);
        }
        to {
            opacity: 0;
            transform: translateY(-20px);
        }
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
    
    .pagination-wrapper {
        padding: 12px 24px;
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
        background: none;
        border-radius: 0;
        box-shadow: none;
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
        color: var(--primary-color);
        transform: translateY(-2px);
        box-shadow: var(--shadow-medium);
        text-decoration: none;
        border-color: var(--primary-color);
    }
    
    .page-item.active .page-link {
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
        color: white;
        transform: translateY(-2px);
        box-shadow: var(--shadow-medium);
        border-color: var(--primary-color);
    }
    
    .page-item.disabled .page-link {
        background: var(--light-bg);
        color: var(--medium-text);
        cursor: not-allowed;
        opacity: 0.5;
    }
    
    .page-item.disabled .page-link:hover {
        transform: none;
        box-shadow: var(--shadow-light);
        border-color: var(--border-color);
    }
</style>

<div class="container-fluid">
    <!-- Success Alert -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show auto-dismiss" role="alert" id="success-alert">
            <i class="fas fa-check-circle me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    
    <div class="row">
        <div class="col-12">
            <!-- Status Statistieken -->
            <div class="stats-cards">
                <div class="stat-card">
                    <div class="stat-number" style="background: linear-gradient(135deg, #4caf50 0%, #66bb6a 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">{{ $providers->where('is_active', true)->count() }}</div>
                    <div class="stat-label">Actief</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" style="background: linear-gradient(135deg, #f44336 0%, #ef5350 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">{{ $providers->where('is_active', false)->count() }}</div>
                    <div class="stat-label">Inactief</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" style="background: linear-gradient(135deg, #ff9800 0%, #ffb74d 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">{{ $providers->where('config.test_mode', true)->count() }}</div>
                    <div class="stat-label">Test Modus</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" style="background: linear-gradient(135deg, #00897b 0%, #4db6ac 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">{{ $providers->count() }}</div>
                    <div class="stat-label">Totaal</div>
                </div>
            </div>

            <div class="material-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-credit-card me-2"></i> Betalingsproviders Overzicht
                    </h5>
                    <div class="d-flex gap-2">
                        <a href="{{ route('admin.payment-providers.create') }}" class="material-btn material-btn-primary">
                            <i class="fas fa-plus me-2"></i> Nieuwe Provider
                        </a>
                    </div>
                </div>
                <div class="card-body">

                    <!-- Filters -->
                    <div class="filters-section">
                        <form method="GET" action="{{ route('admin.payment-providers.index') }}" id="filters-form">
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
                                        <label class="filter-label">Provider Type</label>
                                        <select name="provider_type" class="filter-select" onchange="this.form.submit()">
                                            <option value="">Alle types</option>
                                            <option value="mollie" {{ request('provider_type') == 'mollie' ? 'selected' : '' }}>Mollie</option>
                                            <option value="stripe" {{ request('provider_type') == 'stripe' ? 'selected' : '' }}>Stripe</option>
                                            <option value="paypal" {{ request('provider_type') == 'paypal' ? 'selected' : '' }}>PayPal</option>
                                            <option value="adyen" {{ request('provider_type') == 'adyen' ? 'selected' : '' }}>Adyen</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="filter-group">
                                        <label class="filter-label">Modus</label>
                                        <select name="mode" class="filter-select" onchange="this.form.submit()">
                                            <option value="">Alle modi</option>
                                            <option value="test" {{ request('mode') == 'test' ? 'selected' : '' }}>Test</option>
                                            <option value="live" {{ request('mode') == 'live' ? 'selected' : '' }}>Live</option>
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
                                        <a href="{{ route('admin.payment-providers.index') }}" class="btn btn-outline-secondary w-100" style="height: 36px; display: flex; align-items: center; justify-content: center; gap: 6px; text-decoration: none;">
                                            <i class="fas fa-times"></i>
                                            Filter wissen
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>

                    @if($providers->count() > 0)
                        <div class="table-responsive">
                            <table class="material-table">
                                <thead>
                                    <tr>
                                        <th class="sortable {{ request('sort') == 'id' ? (request('order') == 'asc' ? 'sort-asc' : 'sort-desc') : '' }}" data-sort="id">
                                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'id', 'order' => request('sort') == 'id' && request('order') == 'asc' ? 'desc' : 'asc']) }}" style="text-decoration: none; color: inherit;">
                                                ID
                                            </a>
                                        </th>
                                        <th class="sortable {{ request('sort') == 'name' ? (request('order') == 'asc' ? 'sort-asc' : 'sort-desc') : '' }}" data-sort="name">
                                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'name', 'order' => request('sort') == 'name' && request('order') == 'asc' ? 'desc' : 'asc']) }}" style="text-decoration: none; color: inherit;">
                                                Naam & Beschrijving
                                            </a>
                                        </th>
                                        <th class="sortable {{ request('sort') == 'provider_type' ? (request('order') == 'asc' ? 'sort-asc' : 'sort-desc') : '' }}" data-sort="provider_type">
                                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'provider_type', 'order' => request('sort') == 'provider_type' && request('order') == 'asc' ? 'desc' : 'asc']) }}" style="text-decoration: none; color: inherit;">
                                                Provider Type
                                            </a>
                                        </th>
                                        <th class="sortable {{ request('sort') == 'status' ? (request('order') == 'asc' ? 'sort-asc' : 'sort-desc') : '' }}" data-sort="status">
                                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'status', 'order' => request('sort') == 'status' && request('order') == 'asc' ? 'desc' : 'asc']) }}" style="text-decoration: none; color: inherit;">
                                                Status
                                            </a>
                                        </th>
                                        <th class="sortable {{ request('sort') == 'mode' ? (request('order') == 'asc' ? 'sort-asc' : 'sort-desc') : '' }}" data-sort="mode">
                                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'mode', 'order' => request('sort') == 'mode' && request('order') == 'asc' ? 'desc' : 'asc']) }}" style="text-decoration: none; color: inherit;">
                                                Modus
                                            </a>
                                        </th>
                                        <th class="sortable {{ request('sort') == 'created_at' ? (request('order') == 'asc' ? 'sort-asc' : 'sort-desc') : '' }}" data-sort="created_at">
                                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'created_at', 'order' => request('sort') == 'created_at' && request('order') == 'asc' ? 'desc' : 'asc']) }}" style="text-decoration: none; color: inherit;">
                                                Aangemaakt
                                            </a>
                                        </th>
                                        <th>Acties</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($providers as $provider)
                                        <tr>
                                            <td>{{ $provider->id }}</td>
                                            <td>
                                                <div class="provider-info">
                                                    <div class="provider-name">{{ $provider->name }}</div>
                                                    @if($provider->getConfigValue('description'))
                                                        <div class="provider-description">
                                                            <i class="fas fa-info-circle"></i>{{ $provider->getConfigValue('description') }}
                                                        </div>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                <span class="provider-type">{{ ucfirst($provider->provider_type) }}</span>
                                            </td>
                                            <td>
                                                @if($provider->is_active)
                                                    <span class="status-badge status-active">Actief</span>
                                                @else
                                                    <span class="status-badge status-inactive">Inactief</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($provider->getConfigValue('test_mode'))
                                                    <span class="provider-mode">Test</span>
                                                @else
                                                    <span class="provider-mode">Live</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="date-info">
                                                    <div>{{ $provider->created_at->format('d-m-Y') }}</div>
                                                    <small>{{ $provider->created_at->format('H:i') }}</small>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <a href="{{ route('admin.payment-providers.show', $provider) }}" class="action-btn action-btn-info" title="Bekijken">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="{{ route('admin.payment-providers.edit', $provider) }}" class="action-btn action-btn-warning" title="Bewerken">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button type="button" 
                                                            class="action-btn action-btn-info test-connection-btn" 
                                                            data-provider-id="{{ $provider->id }}"
                                                            title="Test Verbinding">
                                                        <i class="fas fa-plug"></i>
                                                    </button>
                                                    <form action="{{ route('admin.payment-providers.toggle-status', $provider) }}" 
                                                          method="POST" 
                                                          style="display: inline;">
                                                        @csrf
                                                        <button type="submit" 
                                                                class="action-btn {{ $provider->is_active ? 'action-btn-danger' : 'action-btn-success' }}" 
                                                                title="{{ $provider->is_active ? 'Deactiveren' : 'Activeren' }}">
                                                            <i class="fas {{ $provider->is_active ? 'fa-pause' : 'fa-play' }}"></i>
                                                        </button>
                                                    </form>
                                                    <form action="{{ route('admin.payment-providers.destroy', $provider) }}" 
                                                          method="POST" 
                                                          style="display: inline;"
                                                          onsubmit="return confirm('Weet je zeker dat je deze betalingsprovider wilt verwijderen?')">
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
                                <span class="results-text">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Toon {{ $providers->firstItem() ?? 0 }} tot {{ $providers->lastItem() ?? 0 }} van {{ $providers->total() }} resultaten
                                </span>
                            </div>
                        </div>

                        <!-- Pagination -->
                        @if($providers->hasPages())
                            <div class="pagination-wrapper">
                                <nav aria-label="Paginering">
                                    <ul class="pagination">
                                        {{-- Previous Page Link --}}
                                        @if ($providers->onFirstPage())
                                            <li class="page-item disabled">
                                                <span class="page-link">
                                                    <i class="fas fa-chevron-left"></i>
                                                </span>
                                            </li>
                                        @else
                                            <li class="page-item">
                                                <a class="page-link" href="{{ $providers->previousPageUrl() }}">
                                                    <i class="fas fa-chevron-left"></i>
                                                </a>
                                            </li>
                                        @endif

                                        {{-- Pagination Elements --}}
                                        @foreach ($providers->getUrlRange(1, $providers->lastPage()) as $page => $url)
                                            @if ($page == $providers->currentPage())
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
                                        @if ($providers->hasMorePages())
                                            <li class="page-item">
                                                <a class="page-link" href="{{ $providers->nextPageUrl() }}">
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
                            <i class="fas fa-credit-card"></i>
                            <h5>Geen betalingsproviders gevonden</h5>
                            <p class="text-muted">Maak je eerste betalingsprovider aan om te beginnen.</p>
                            <a href="{{ route('admin.payment-providers.create') }}" class="material-btn material-btn-primary">
                                <i class="fas fa-plus"></i>
                                Eerste Provider Aanmaken
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Test Connection Modal -->
<div class="modal fade" id="testConnectionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plug me-2"></i>Test Verbinding</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="testResult"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Sluiten</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Test connection functionality
    document.querySelectorAll('.test-connection-btn').forEach(button => {
        button.addEventListener('click', function() {
            const providerId = this.dataset.providerId;
            const modal = new bootstrap.Modal(document.getElementById('testConnectionModal'));
            const resultDiv = document.getElementById('testResult');
            
            resultDiv.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Verbinding testen...</div>';
            modal.show();
            
            fetch(`/admin/payment-providers/${providerId}/test-connection`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    resultDiv.innerHTML = `<div class="alert alert-success"><i class="fas fa-check-circle"></i> ${data.message}</div>`;
                } else {
                    resultDiv.innerHTML = `<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> ${data.message}</div>`;
                }
            })
            .catch(error => {
                resultDiv.innerHTML = `<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> Fout bij testen van verbinding: ${error.message}</div>`;
            });
        });
    });
    });
    
    // Auto-dismiss success alert after 5 seconds
    document.addEventListener('DOMContentLoaded', function() {
        const successAlert = document.getElementById('success-alert');
        if (successAlert) {
            setTimeout(function() {
                successAlert.classList.add('fade-out');
                setTimeout(function() {
                    successAlert.remove();
                }, 300); // Match the CSS animation duration
            }, 5000); // 5 seconds
        }
    });
</script>
@endsection
