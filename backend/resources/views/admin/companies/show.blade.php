@extends('admin.layouts.app')

@section('title', 'Bedrijf Details - ' . $company->name)

@section('content')
<style>
    :root {
        --primary-color: #4caf50;
        --primary-light: #66bb6a;
        --primary-dark: #388e3c;
        --primary-hover: #43a047;
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

    .company-header {
        background: linear-gradient(135deg, #f8f9fa, #e9ecef);
        border-radius: var(--border-radius);
        padding: 24px;
        margin-bottom: 24px;
        border-left: 4px solid var(--primary-color);
    }

    .company-title {
        font-size: 2rem;
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: 12px;
        line-height: 1.2;
    }

    .company-meta {
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

    .company-status {
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

    .company-status:hover {
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
                <i class="fas fa-building"></i>
                Bedrijf Details: {{ $company->name }}
            </h5>
            <div class="material-header-actions">
                <a href="{{ route('admin.companies.edit', $company) }}" class="material-btn material-btn-warning me-2">
                    <i class="fas fa-edit"></i> Bewerken
                </a>
                <a href="{{ route('admin.companies.index') }}" class="material-btn material-btn-secondary">
                    <i class="fas fa-arrow-left"></i> Terug naar Overzicht
                </a>
            </div>
        </div>
        <div class="card-body">
            <!-- Company Header Section -->
            <div class="company-header">
                <h1 class="company-title">{{ $company->name }}</h1>
                <div class="company-meta">
                    <div class="meta-item">
                        <i class="fas fa-building"></i>
                        <span>{{ $company->industry ?? 'Geen branche' }}</span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <span>{{ $company->city ?? 'Locatie onbekend' }}</span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-users"></i>
                        <span>{{ $company->users->count() }} gebruikers</span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-briefcase"></i>
                        <span>{{ $company->vacancies->count() }} vacatures</span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-calendar"></i>
                        <span>Aangemaakt: {{ $company->created_at->format('d-m-Y') }}</span>
                    </div>
                </div>
                <div class="company-status status-active">
                    <i class="fas fa-circle"></i>
                    Actief
                </div>
            </div>

            <div class="info-grid">
                <div class="info-section">
                    <h6 class="section-title">
                        <i class="fas fa-info-circle"></i>
                        Bedrijfsinformatie
                    </h6>
                    <table class="info-table">
                        <tr>
                            <td>ID</td>
                            <td>{{ $company->id }}</td>
                        </tr>
                        <tr>
                            <td>Naam</td>
                            <td>{{ $company->name }}</td>
                        </tr>
                        <tr>
                            <td>KVK Nummer</td>
                            <td>{{ $company->kvk_number ?? 'Niet opgegeven' }}</td>
                        </tr>
                        <tr>
                            <td>Branche</td>
                            <td>{{ $company->industry ?? 'Niet opgegeven' }}</td>
                        </tr>
                        <tr>
                            <td>Website</td>
                            <td>
                                @if($company->website)
                                    <a href="{{ $company->website }}" target="_blank" class="material-link">{{ $company->website }}</a>
                                @else
                                    <span class="material-text-muted">Niet opgegeven</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td>Beschrijving</td>
                            <td>{{ $company->description ?? 'Geen beschrijving' }}</td>
                        </tr>
                    </table>
                </div>
                
                <div class="info-section">
                    <h6 class="section-title">
                        <i class="fas fa-map-marker-alt"></i>
                        Contact Informatie
                    </h6>
                    <table class="info-table">
                        <tr>
                            <td>E-mail</td>
                            <td>{{ $company->email }}</td>
                        </tr>
                        <tr>
                            <td>Telefoon</td>
                            <td>{{ $company->phone ?? 'Niet opgegeven' }}</td>
                        </tr>
                        <tr>
                            <td>Straat</td>
                            <td>{{ $company->street ?? 'Niet opgegeven' }}</td>
                        </tr>
                        <tr>
                            <td>Huisnummer</td>
                            <td>{{ $company->house_number ?? 'Niet opgegeven' }}</td>
                        </tr>
                        <tr>
                            <td>Postcode</td>
                            <td>{{ $company->postal_code ?? 'Niet opgegeven' }}</td>
                        </tr>
                        <tr>
                            <td>Plaats</td>
                            <td>{{ $company->city ?? 'Niet opgegeven' }}</td>
                        </tr>
                        <tr>
                            <td>Land</td>
                            <td>{{ $company->country ?? 'Niet opgegeven' }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="info-grid">
                <div class="info-section">
                    <h6 class="section-title">
                        <i class="fas fa-cog"></i>
                        Systeem Informatie
                    </h6>
                    <table class="info-table">
                        <tr>
                            <td>Aangemaakt op</td>
                            <td>{{ $company->created_at->format('d-m-Y H:i') }}</td>
                        </tr>
                        <tr>
                            <td>Laatst bijgewerkt</td>
                            <td>{{ $company->updated_at->format('d-m-Y H:i') }}</td>
                        </tr>
                    </table>
                </div>
                
                <div class="info-section">
                    <h6 class="section-title">
                        <i class="fas fa-users"></i>
                        Gebruikers & Vacatures
                    </h6>
                    <table class="info-table">
                        <tr>
                            <td>Aantal gebruikers</td>
                            <td>{{ $company->users->count() }}</td>
                        </tr>
                        <tr>
                            <td>Aantal vacatures</td>
                            <td>{{ $company->vacancies->count() }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
