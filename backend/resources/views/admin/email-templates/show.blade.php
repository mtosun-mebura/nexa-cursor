@extends('admin.layouts.app')

@section('title', 'E-mail Sjabloon Details - ' . $emailTemplate->name)

@section('content')
<style>
    :root {
        --primary-color: #00897b;
        --primary-light: #4db6ac;
        --primary-dark: #00695c;
        --primary-hover: #26a69a;
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

    .email-template-header {
        background: linear-gradient(135deg, #f8f9fa, #e9ecef);
        border-radius: var(--border-radius);
        padding: 24px;
        margin-bottom: 24px;
        border-left: 4px solid var(--primary-color);
    }

    .email-template-title {
        font-size: 2rem;
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: 12px;
        line-height: 1.2;
    }

    .email-template-meta {
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

    .email-template-status {
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

    .email-template-status:hover {
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

    .email-content {
        background: var(--light-bg);
        border-radius: var(--border-radius);
        padding: 20px;
        margin-top: 16px;
        border-left: 4px solid var(--primary-color);
        white-space: pre-line;
    }
</style>

<div class="container-fluid">
    <div class="material-card">
        <div class="card-header">
            <h5>
                <i class="fas fa-envelope"></i>
                E-mail Sjabloon Details: {{ $emailTemplate->name }}
            </h5>
            <div class="material-header-actions">
                <a href="{{ route('admin.email-templates.edit', $emailTemplate) }}" class="material-btn material-btn-warning me-2">
                    <i class="fas fa-edit"></i> Bewerken
                </a>
                <a href="{{ route('admin.email-templates.index') }}" class="material-btn material-btn-secondary">
                    <i class="fas fa-arrow-left"></i> Terug naar Overzicht
                </a>
            </div>
        </div>
        <div class="card-body">
            <!-- Email Template Header Section -->
            <div class="email-template-header">
                <h1 class="email-template-title">{{ $emailTemplate->name }}</h1>
                <div class="email-template-meta">
                    <div class="meta-item">
                        <i class="fas fa-tag"></i>
                        <span>{{ ucfirst(str_replace('_', ' ', $emailTemplate->type)) }}</span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-building"></i>
                        <span>{{ $emailTemplate->company->name ?? 'Globaal' }}</span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-calendar"></i>
                        <span>Aangemaakt: {{ $emailTemplate->created_at->format('d-m-Y') }}</span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-clock"></i>
                        <span>Bijgewerkt: {{ $emailTemplate->updated_at->format('d-m-Y') }}</span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-envelope"></i>
                        <span>{{ strlen($emailTemplate->content) }} karakters</span>
                    </div>
                </div>
                <div class="email-template-status status-{{ $emailTemplate->is_active ? 'active' : 'inactive' }}">
                    <i class="fas fa-circle"></i>
                    {{ $emailTemplate->is_active ? 'Actief' : 'Inactief' }}
                </div>
            </div>

            <div class="info-grid">
                <div class="info-section">
                    <h6 class="section-title">
                        <i class="fas fa-info-circle"></i>
                        Sjabloon Informatie
                    </h6>
                    <table class="info-table">
                        <tr>
                            <td>ID</td>
                            <td>{{ $emailTemplate->id }}</td>
                        </tr>
                        <tr>
                            <td>Naam</td>
                            <td>{{ $emailTemplate->name }}</td>
                        </tr>
                        <tr>
                            <td>Type</td>
                            <td>{{ ucfirst(str_replace('_', ' ', $emailTemplate->type)) }}</td>
                        </tr>
                        <tr>
                            <td>Onderwerp</td>
                            <td>{{ $emailTemplate->subject }}</td>
                        </tr>
                        <tr>
                            <td>Status</td>
                            <td>
                                <span class="material-badge material-badge-{{ $emailTemplate->is_active ? 'success' : 'secondary' }}">
                                    {{ $emailTemplate->is_active ? 'Actief' : 'Inactief' }}
                                </span>
                            </td>
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
                            <td>Bedrijf</td>
                            <td>{{ $emailTemplate->company->name ?? 'Globaal' }}</td>
                        </tr>
                        <tr>
                            <td>Aangemaakt op</td>
                            <td>{{ $emailTemplate->created_at->format('d-m-Y H:i') }}</td>
                        </tr>
                        <tr>
                            <td>Laatst bijgewerkt</td>
                            <td>{{ $emailTemplate->updated_at->format('d-m-Y H:i') }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="info-section">
                <h6 class="section-title">
                    <i class="fas fa-envelope-open"></i>
                    E-mail Inhoud
                </h6>
                <div class="email-content">
                    {{ $emailTemplate->content }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
