@extends('admin.layouts.app')

@section('title', 'Recht Details - ' . $permission->name)

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
        --light-bg: #f5f5f5;
        --border-color: #e0e0e0;
        --text-primary: #212121;
        --text-secondary: #757575;
        --shadow: 0 2px 4px rgba(0,0,0,0.1);
        --shadow-hover: 0 4px 8px rgba(0,0,0,0.15);
        --border-radius: 8px;
        --transition: all 0.3s ease;
    }

    .material-card {
        background: white;
        border-radius: var(--border-radius);
        box-shadow: var(--shadow);
        margin-bottom: 24px;
        overflow: hidden;
        transition: var(--transition);
    }

    .material-card:hover {
        box-shadow: var(--shadow-hover);
    }

    .card-header {
        background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
        color: white;
        padding: 20px 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 16px;
    }

    .card-header h5 {
        margin: 0;
        font-size: 1.25rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .material-header-actions {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
    }

    .material-btn {
        padding: 10px 20px;
        border: none;
        border-radius: var(--border-radius);
        font-weight: 500;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: var(--transition);
        cursor: pointer;
        font-size: 14px;
        height: 44px;
        min-height: 44px;
    }

    .material-btn-warning {
        background: var(--warning-color);
        color: white;
    }

    .material-btn-warning:hover {
        background: #f57c00;
        color: white;
        transform: translateY(-2px);
    }

    .material-btn-secondary {
        background: var(--light-bg);
        color: var(--text-primary);
    }

    .material-btn-secondary:hover {
        background: #e0e0e0;
        color: var(--text-primary);
        transform: translateY(-2px);
    }

    .card-body {
        padding: 24px;
    }

    .permission-header {
        background: linear-gradient(135deg, #f8f9fa, #e9ecef);
        border-radius: var(--border-radius);
        padding: 24px;
        margin-bottom: 24px;
        border-left: 4px solid var(--primary-color);
    }

    .permission-title {
        font-size: 2rem;
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: 12px;
        line-height: 1.2;
    }

    .permission-meta {
        display: flex;
        align-items: center;
        gap: 24px;
        flex-wrap: wrap;
        margin-bottom: 16px;
    }

    .meta-item {
        display: flex;
        align-items: center;
        gap: 8px;
        color: var(--text-secondary);
        font-size: 14px;
    }

    .meta-item i {
        color: var(--primary-color);
        width: 16px;
    }

    .permission-status {
        padding: 8px 16px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
    }

    .permission-status:hover {
        transform: scale(1.05);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }

    .status-active {
        background: linear-gradient(135deg, #f1f8e9 0%, #81c784 100%);
        color: #388e3c;
        border: 2px solid #81c784;
    }

    .status-inactive {
        background: linear-gradient(135deg, #ffcdd2 0%, #e57373 100%);
        color: #d32f2f;
        border: 2px solid #e57373;
    }

    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 24px;
        margin-bottom: 24px;
    }

    .info-section {
        background: white;
        border-radius: var(--border-radius);
        padding: 20px;
        box-shadow: var(--shadow);
        border: 1px solid var(--border-color);
    }

    .section-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: 16px;
        padding-bottom: 8px;
        border-bottom: 2px solid var(--primary-color);
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .info-table {
        width: 100%;
        border-collapse: collapse;
    }

    .info-table tr {
        border-bottom: 1px solid var(--border-color);
    }

    .info-table tr:last-child {
        border-bottom: none;
    }

    .info-table td {
        padding: 12px 0;
        vertical-align: top;
    }

    .info-table td:first-child {
        font-weight: 600;
        color: var(--text-primary);
        width: 140px;
        min-width: 140px;
    }

    .info-table td:last-child {
        color: var(--text-secondary);
    }

    .material-badge {
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
        text-transform: uppercase;
    }

    .material-badge-primary {
        background: var(--primary-color);
        color: white;
    }

    .material-badge-secondary {
        background: var(--secondary-color);
        color: white;
    }

    .material-badge-success {
        background: var(--success-color);
        color: white;
    }

    .material-badge-warning {
        background: var(--warning-color);
        color: white;
    }

    .material-badge-danger {
        background: var(--danger-color);
        color: white;
    }

    .material-badge-info {
        background: var(--info-color);
        color: white;
    }

    .material-text-muted {
        color: var(--text-secondary);
        font-style: italic;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }

    .stat-card {
        background: white;
        border-radius: var(--border-radius);
        padding: 20px;
        text-align: center;
        box-shadow: var(--shadow);
        border: 1px solid var(--border-color);
        transition: var(--transition);
    }

    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-hover);
    }

    .stat-value {
        font-size: 2rem;
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: 8px;
    }

    .stat-label {
        font-size: 0.9rem;
        color: var(--text-secondary);
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .material-table {
        border-radius: var(--border-radius);
        overflow: hidden;
        box-shadow: var(--shadow);
        border: 1px solid var(--border-color);
    }

    .material-table thead th {
        background: var(--light-bg);
        border: none;
        font-weight: 600;
        color: var(--text-primary);
        padding: 16px 12px;
        text-transform: uppercase;
        font-size: 0.85rem;
        letter-spacing: 0.5px;
    }

    .material-table tbody td {
        padding: 16px 12px;
        border-bottom: 1px solid var(--border-color);
        vertical-align: middle;
    }

    .material-table tbody tr:hover {
        background: var(--light-bg);
    }

    .material-alert {
        border-radius: var(--border-radius);
        border: none;
        padding: 16px 20px;
        margin-bottom: 20px;
        box-shadow: var(--shadow);
    }

    .material-alert-warning {
        background: linear-gradient(135deg, #fff3e0 0%, #ffe0b2 100%);
        color: #e65100;
        border-left: 4px solid var(--warning-color);
    }

    code {
        background: var(--light-bg);
        color: var(--text-primary);
        padding: 4px 8px;
        border-radius: 4px;
        font-family: 'Courier New', monospace;
        font-size: 12px;
    }
</style>

<div class="container-fluid">
    <div class="material-card">
        <div class="card-header">
            <h5>
                <i class="fas fa-key"></i>
                Recht Details: {{ $permission->name }}
            </h5>
            <div class="material-header-actions">
                <a href="{{ route('admin.permissions.edit', $permission) }}" class="material-btn material-btn-warning me-2">
                    <i class="fas fa-edit"></i> Bewerken
                </a>
                <a href="{{ route('admin.permissions.index') }}" class="material-btn material-btn-secondary">
                    <i class="fas fa-arrow-left"></i> Terug naar Overzicht
                </a>
            </div>
        </div>
        <div class="card-body">
            <!-- Permission Header Section -->
            <div class="permission-header">
                <h1 class="permission-title">{{ ucfirst(str_replace('-', ' ', $permission->name)) }}</h1>
                <div class="permission-meta">
                    <div class="meta-item">
                        <i class="fas fa-key"></i>
                        <span>{{ $permission->name }}</span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-folder"></i>
                        <span>{{ $permission->group ?? 'Geen groep' }}</span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-shield-alt"></i>
                        <span>{{ $permission->guard_name }}</span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-calendar"></i>
                        <span>Aangemaakt: {{ $permission->created_at->format('d-m-Y') }}</span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-clock"></i>
                        <span>Bijgewerkt: {{ $permission->updated_at->format('d-m-Y') }}</span>
                    </div>
                </div>
                <div class="permission-status status-active">
                    <i class="fas fa-circle"></i>
                    Actief
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value">{{ $permission->roles->count() }}</div>
                    <div class="stat-label">Rollen</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">{{ $permission->users->count() }}</div>
                    <div class="stat-label">Gebruikers</div>
                </div>
            </div>

            <div class="info-grid">
                <div class="info-section">
                    <h6 class="section-title">
                        <i class="fas fa-info-circle"></i>
                        Basis Informatie
                    </h6>
                    <table class="info-table">
                        <tr>
                            <td>ID</td>
                            <td>{{ $permission->id }}</td>
                        </tr>
                        <tr>
                            <td>Naam</td>
                            <td><code>{{ $permission->name }}</code></td>
                        </tr>
                        <tr>
                            <td>Groep</td>
                            <td>
                                @if($permission->group)
                                    <span class="material-badge material-badge-primary">{{ $permission->group }}</span>
                                @else
                                    <span class="material-text-muted">Geen groep</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td>Guard</td>
                            <td>{{ $permission->guard_name }}</td>
                        </tr>
                        <tr>
                            <td>Beschrijving</td>
                            <td>{{ $permission->description ?? 'Geen beschrijving' }}</td>
                        </tr>
                    </table>
                </div>
                
                <div class="info-section">
                    <h6 class="section-title">
                        <i class="fas fa-cog"></i>
                        Systeem Informatie
                    </h6>
                    <table class="info-table">
                        <tr>
                            <td>Aangemaakt op</td>
                            <td>{{ $permission->created_at->format('d-m-Y H:i') }}</td>
                        </tr>
                        <tr>
                            <td>Laatst bijgewerkt</td>
                            <td>{{ $permission->updated_at->format('d-m-Y H:i') }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Roles with this Permission -->
            <div class="info-section">
                <h6 class="section-title">
                    <i class="fas fa-user-shield"></i>
                    Rollen met dit Recht ({{ $permission->roles->count() }})
                </h6>
                
                @if($permission->roles->count() > 0)
                    <div class="table-responsive">
                        <table class="table material-table">
                            <thead>
                                <tr>
                                    <th>Rol Naam</th>
                                    <th>Beschrijving</th>
                                    <th>Type</th>
                                    <th>Aantal Gebruikers</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($permission->roles as $role)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-user-shield me-2 text-primary"></i>
                                                <strong>{{ $role->name }}</strong>
                                            </div>
                                        </td>
                                        <td>{{ $role->description ?? 'Geen beschrijving' }}</td>
                                        <td>
                                            @if(in_array($role->name, ['super-admin', 'company-admin', 'staff', 'candidate']))
                                                <span class="material-badge material-badge-warning">Systeem</span>
                                            @else
                                                <span class="material-badge material-badge-success">Aangepast</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="material-badge material-badge-secondary">{{ $role->users->count() }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="material-alert material-alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Dit recht is niet toegewezen aan rollen.
                    </div>
                @endif
            </div>

            <!-- Users with this Permission -->
            @if($permission->users->count() > 0)
                <div class="info-section">
                    <h6 class="section-title">
                        <i class="fas fa-users"></i>
                        Gebruikers met dit Recht ({{ $permission->users->count() }})
                    </h6>
                    
                    <div class="table-responsive">
                        <table class="table material-table">
                            <thead>
                                <tr>
                                    <th>Naam</th>
                                    <th>E-mail</th>
                                    <th>Bedrijf</th>
                                    <th>Rollen</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($permission->users as $user)
                                    <tr>
                                        <td>{{ $user->first_name }} {{ $user->last_name }}</td>
                                        <td>{{ $user->email }}</td>
                                        <td>{{ $user->company->name ?? 'Geen bedrijf' }}</td>
                                        <td>
                                            <div class="d-flex flex-wrap gap-1">
                                                @foreach($user->roles as $role)
                                                    <span class="material-badge material-badge-info">{{ $role->name }}</span>
                                                @endforeach
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
