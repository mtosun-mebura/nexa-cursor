@extends('admin.layouts.app')

@section('title', 'Dashboard')

@section('content')
<style>
    .dashboard-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 2rem;
        border-radius: 12px;
        margin-bottom: 2rem;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    }
    
    .dashboard-header h1 {
        margin: 0;
        font-weight: 300;
        font-size: 2.5rem;
    }
    
    .dashboard-header p {
        margin: 0.5rem 0 0 0;
        opacity: 0.9;
        font-size: 1.1rem;
    }
    
    .material-stat-card {
        background: white;
        border-radius: 16px;
        padding: 2rem;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
        border: none;
        position: relative;
        overflow: hidden;
    }
    
    .material-stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, var(--card-color), var(--card-color-light));
    }
    
    .material-stat-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
    }
    
    .material-stat-card.users {
        --card-color: #2196f3;
        --card-color-light: #64b5f6;
    }
    
    .material-stat-card.companies {
        --card-color: #4caf50;
        --card-color-light: #81c784;
    }
    
    .material-stat-card.vacancies {
        --card-color: #ff9800;
        --card-color-light: #ffb74d;
    }
    
    .material-stat-card.matches {
        --card-color: #9c27b0;
        --card-color-light: #ba68c8;
    }
    
    .material-stat-card.interviews {
        --card-color: #f44336;
        --card-color-light: #ef5350;
    }
    
    .material-stat-card.notifications {
        --card-color: #00bcd4;
        --card-color-light: #4dd0e1;
    }
    
    .stat-content {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    
    .stat-info {
        flex: 1;
    }
    
    .stat-number {
        font-size: 2.5rem;
        font-weight: 700;
        color: #333;
        margin: 0;
        line-height: 1;
    }
    
    .stat-label {
        color: #666;
        font-size: 0.9rem;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin: 0.5rem 0 0 0;
    }
    
    .stat-icon {
        font-size: 3rem;
        color: var(--card-color);
        opacity: 0.8;
    }
    
    .material-table-card {
        background: white;
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        border: none;
        overflow: hidden;
    }
    
    .material-table-card .card-header {
        background: linear-gradient(135deg, var(--header-color), var(--header-color-light));
        color: white;
        border: none;
        padding: 1.5rem;
    }
    
    .material-table-card.users .card-header {
        --header-color: #2196f3;
        --header-color-light: #64b5f6;
    }
    
    .material-table-card.companies .card-header {
        --header-color: #4caf50;
        --header-color-light: #81c784;
    }
    
    .material-table-card .card-header h5 {
        margin: 0;
        font-weight: 500;
        font-size: 1.1rem;
    }
    
    .material-table-card .card-body {
        padding: 0;
    }
    
    .material-table {
        margin: 0;
        border: none;
    }
    
    .material-table thead th {
        background: #f8f9fa;
        border: none;
        font-weight: 600;
        color: #495057;
        padding: 1rem;
        text-transform: uppercase;
        font-size: 0.8rem;
        letter-spacing: 0.5px;
    }
    
    .material-table tbody td {
        padding: 1rem;
        border-bottom: 1px solid #e9ecef;
        vertical-align: middle;
    }
    
    .material-table tbody tr:hover {
        background: #f8f9fa;
    }
    
    .material-table tbody tr:last-child td {
        border-bottom: none;
    }
    
    .user-name {
        font-weight: 600;
        color: #495057;
    }
    
    .user-email {
        font-size: 0.85rem;
        color: #6c757d;
        margin-top: 0.25rem;
    }
    
    .company-name {
        font-weight: 600;
        color: #495057;
    }
    
    .date-info {
        font-size: 0.85rem;
        color: #6c757d;
    }
    
    @media (max-width: 768px) {
        .dashboard-header h1 {
            font-size: 2rem;
        }
        
        .stat-content {
            flex-direction: column;
            text-align: center;
        }
        
        .stat-icon {
            margin-bottom: 1rem;
        }
    }
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="dashboard-header">
                <h1>Dashboard Overzicht</h1>
                <p>Welkom terug! Hier is een overzicht van je platform.</p>
            </div>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                    <div class="material-stat-card users">
                        <div class="stat-content">
                            <div class="stat-info">
                                <div class="stat-number">{{ $stats['total_users'] }}</div>
                                <div class="stat-label">Gebruikers</div>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                    <div class="material-stat-card companies">
                        <div class="stat-content">
                            <div class="stat-info">
                                <div class="stat-number">{{ $stats['total_companies'] }}</div>
                                <div class="stat-label">Bedrijven</div>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-building"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                    <div class="material-stat-card vacancies">
                        <div class="stat-content">
                            <div class="stat-info">
                                <div class="stat-number">{{ $stats['total_vacancies'] }}</div>
                                <div class="stat-label">Vacatures</div>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-briefcase"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                    <div class="material-stat-card matches">
                        <div class="stat-content">
                            <div class="stat-info">
                                <div class="stat-number">{{ $stats['total_matches'] }}</div>
                                <div class="stat-label">Matches</div>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-handshake"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                    <div class="material-stat-card interviews">
                        <div class="stat-content">
                            <div class="stat-info">
                                <div class="stat-number">{{ $stats['total_interviews'] ?? 0 }}</div>
                                <div class="stat-label">Interviews</div>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                    <div class="material-stat-card notifications">
                        <div class="stat-content">
                            <div class="stat-info">
                                <div class="stat-number">{{ $stats['total_notifications'] ?? 0 }}</div>
                                <div class="stat-label">Notificaties</div>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-bell"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Data -->
            <div class="row">
                <div class="col-lg-6 mb-4">
                    <div class="material-table-card users">
                        <div class="card-header">
                            <h5><i class="fas fa-users me-2"></i> Recente Gebruikers</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table material-table">
                                    <thead>
                                        <tr>
                                            <th>Naam</th>
                                            <th>E-mail</th>
                                            <th>Datum</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($recent_users as $user)
                                        <tr>
                                            <td>
                                                <div class="user-name">{{ $user->first_name }} {{ $user->last_name }}</div>
                                            </td>
                                            <td>
                                                <div class="user-email">{{ $user->email }}</div>
                                            </td>
                                            <td>
                                                <div class="date-info">{{ $user->created_at->format('d-m-Y') }}</div>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6 mb-4">
                    <div class="material-table-card companies">
                        <div class="card-header">
                            <h5><i class="fas fa-building me-2"></i> Recente Bedrijven</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table material-table">
                                    <thead>
                                        <tr>
                                            <th>Naam</th>
                                            <th>Datum</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($recent_companies as $company)
                                        <tr>
                                            <td>
                                                <div class="company-name">{{ $company->name }}</div>
                                            </td>
                                            <td>
                                                <div class="date-info">{{ $company->created_at->format('d-m-Y') }}</div>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
