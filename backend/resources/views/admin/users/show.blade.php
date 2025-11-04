@extends('admin.layouts.app')

@section('title', 'Gebruiker Details - ' . $user->first_name . ' ' . $user->last_name)

@section('content')
<style>
    :root {
        --primary-color: #2196f3;
        --primary-light: #64b5f6;
        --primary-dark: #1976d2;
        --primary-hover: #42a5f5;
        --success-color: #4caf50;
        --warning-color: #ff9800;
        --danger-color: #f44336;
        --info-color: #2196f3;
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

    .user-header {
        background: linear-gradient(135deg, #f8f9fa, #e9ecef);
        border-radius: var(--border-radius);
        padding: 24px;
        margin-bottom: 24px;
        border-left: 4px solid var(--primary-color);
    }
    
    /* Ensure user-header works in both modes */
    [data-theme="dark"] .user-header,
    .dark .user-header {
        background: linear-gradient(135deg, #374151 0%, #4b5563 100%) !important;
        background-image: none !important;
        border-left-color: #60a5fa !important;
    }

    .user-title {
        font-size: 2rem;
        font-weight: 700;
        color: #212121 !important; /* Force dark text in light mode */
        margin-bottom: 12px;
        line-height: 1.2;
    }
    
    /* Ensure user-title is white in dark mode */
    [data-theme="dark"] .user-title,
    [data-theme="dark"] .user-title[style],
    .dark .user-title,
    .dark .user-title[style] {
        color: #ffffff !important; /* Pure white for maximum contrast */
    }

    .user-meta {
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
        color: #424242 !important; /* Force darker text in light mode */
        font-size: 14px;
    }
    
    /* Ensure meta-items are white in dark mode */
    [data-theme="dark"] .meta-item,
    [data-theme="dark"] .meta-item span,
    [data-theme="dark"] .meta-item[style],
    .dark .meta-item,
    .dark .meta-item span,
    .dark .meta-item[style] {
        color: #ffffff !important; /* Pure white for maximum contrast */
    }

    .meta-item span {
        color: #424242 !important; /* Force darker text in light mode */
    }

    .meta-item i {
        color: var(--primary-color);
        width: 16px;
    }

    .user-status {
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

    .user-status:hover {
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

    .material-text-muted {
        color: var(--text-secondary);
        font-style: italic;
    }

    .material-link {
        color: var(--primary-color);
        text-decoration: none;
        transition: color 0.2s ease;
    }

    .material-link:hover {
        color: var(--primary-hover);
        text-decoration: underline;
    }
</style>

<div class="container-fluid">
    <div class="material-card">
        <div class="card-header">
            <h5>
                <i class="fas fa-user"></i>
                Gebruiker Details: {{ $user->first_name }} {{ $user->last_name }}
            </h5>
            <div class="material-header-actions">
                <a href="{{ route('admin.users.edit', $user) }}" class="material-btn material-btn-warning me-2">
                    <i class="fas fa-edit"></i> Bewerken
                </a>
                <a href="{{ route('admin.users.index') }}" class="material-btn material-btn-secondary">
                    <i class="fas fa-arrow-left"></i> Terug naar Overzicht
                </a>
            </div>
        </div>
        <div class="card-body">
            <!-- User Header Section -->
            <div class="user-header">
                <h1 class="user-title">{{ $user->first_name }} {{ $user->last_name }}</h1>
                <div class="user-meta">
                    <div class="meta-item">
                        <i class="fas fa-envelope"></i>
                        <span>{{ $user->email }}</span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-building"></i>
                        <span>{{ $user->company->name ?? 'Geen bedrijf' }}</span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-user-shield"></i>
                        <span>{{ $user->roles->count() }} rollen</span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-calendar"></i>
                        <span>Aangemaakt: {{ $user->created_at->format('d-m-Y') }}</span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-phone"></i>
                        <span>{{ $user->phone ?? 'Geen telefoon' }}</span>
                    </div>
                </div>
                <div class="user-status status-active">
                    <i class="fas fa-circle"></i>
                    @if($user->email_verified_at)
                        Actief
                    @else
                        Niet geverifieerd
                    @endif
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
                            <td>{{ $user->id }}</td>
                        </tr>
                        <tr>
                            <td>Naam</td>
                            <td>{{ $user->first_name }} {{ $user->last_name }}</td>
                        </tr>
                        <tr>
                            <td>E-mail</td>
                            <td>{{ $user->email }}</td>
                        </tr>
                        <tr>
                            <td>Telefoon</td>
                            <td>{{ $user->phone ?? 'Niet opgegeven' }}</td>
                        </tr>
                        <tr>
                            <td>Geboortedatum</td>
                            <td>{{ $user->date_of_birth ? \Carbon\Carbon::parse($user->date_of_birth)->format('d-m-Y') : 'Niet opgegeven' }}</td>
                        </tr>
                    </table>
                </div>
                
                <div class="info-section">
                    <h6 class="section-title">
                        <i class="fas fa-user-shield"></i>
                        Account & Status
                    </h6>
                    <table class="info-table">
                        <tr>
                            <td>Status</td>
                            <td>
                                @if($user->email_verified_at)
                                    <span class="material-badge material-badge-success">Actief</span>
                                @else
                                    <span class="material-badge material-badge-warning">Niet geverifieerd</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td>Bedrijf</td>
                            <td>
                                @if($user->company)
                                    <span class="material-badge material-badge-primary">{{ $user->company->name }}</span>
                                @else
                                    <span class="material-text-muted">Geen bedrijf toegewezen</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td>Rollen</td>
                            <td>
                                @foreach($user->roles as $role)
                                    @if($role->name === 'super-admin')
                                        @if(auth()->user()->hasRole('super-admin'))
                                            <span class="material-badge material-badge-primary me-1">{{ $role->name }}</span>
                                        @else
                                            <span class="material-badge material-badge-secondary me-1">Verborgen</span>
                                        @endif
                                    @else
                                        <span class="material-badge material-badge-primary me-1">{{ $role->name }}</span>
                                    @endif
                                @endforeach
                                @if($user->roles->isEmpty())
                                    <span class="material-text-muted">Geen rollen toegewezen</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td>E-mail geverifieerd</td>
                            <td>
                                @if($user->email_verified_at)
                                    <span class="material-badge material-badge-success">Ja ({{ \Carbon\Carbon::parse($user->email_verified_at)->format('d-m-Y H:i') }})</span>
                                @else
                                    <span class="material-badge material-badge-warning">Nee</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td>Telefoon geverifieerd</td>
                            <td>
                                @if($user->phone_verified_at)
                                    <span class="material-badge material-badge-success">Ja ({{ \Carbon\Carbon::parse($user->phone_verified_at)->format('d-m-Y H:i') }})</span>
                                @else
                                    <span class="material-badge material-badge-warning">Nee</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td>Aangemaakt op</td>
                            <td>{{ $user->created_at->format('d-m-Y H:i') }}</td>
                        </tr>
                        <tr>
                            <td>Laatst bijgewerkt</td>
                            <td>{{ $user->updated_at->format('d-m-Y H:i') }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
