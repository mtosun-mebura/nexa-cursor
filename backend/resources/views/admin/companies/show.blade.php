@extends('admin.layouts.app')

@section('title', 'Bedrijf Details')

@section('content')
<style>
    .material-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        overflow: hidden;
        margin-bottom: 24px;
    }

    .material-card .card-header {
        background: linear-gradient(135deg, #4caf50 0%, #66bb6a 100%);
        color: white;
        padding: 20px 24px;
        border: none;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .material-card .card-header h5 {
        margin: 0;
        font-weight: 600;
        font-size: 1.25rem;
    }

    .material-card .card-body {
        padding: 24px;
    }

    .material-btn {
        border: none;
        border-radius: 8px;
        padding: 12px 24px;
        font-weight: 500;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s ease;
        cursor: pointer;
        font-size: 0.875rem;
    }

    .material-btn-warning {
        background: linear-gradient(135deg, #ff9800 0%, #ffb74d 100%);
        color: white;
    }

    .material-btn-warning:hover {
        background: linear-gradient(135deg, #f57c00 0%, #ff8f00 100%);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(255, 152, 0, 0.3);
    }

    .material-btn-secondary {
        background: #f5f5f5;
        color: #333;
    }

    .material-btn-secondary:hover {
        background: #e0e0e0;
        transform: translateY(-1px);
    }

    .material-section-title {
        color: #666;
        font-size: 1rem;
        font-weight: 600;
        margin: 24px 0 16px 0;
        padding-bottom: 8px;
        border-bottom: 2px solid #f0f0f0;
    }

    .material-info-table {
        width: 100%;
        border-collapse: collapse;
    }

    .material-info-table tr {
        border-bottom: 1px solid #f0f0f0;
    }

    .material-info-table tr:last-child {
        border-bottom: none;
    }

    .material-info-table td {
        padding: 12px 0;
        vertical-align: top;
    }

    .material-info-table td:first-child {
        width: 150px;
        font-weight: 600;
        color: #333;
    }

    .material-info-table td:last-child {
        color: #666;
    }

    .material-divider {
        height: 1px;
        background: #f0f0f0;
        margin: 32px 0;
        border: none;
    }

    .material-table {
        width: 100%;
        border-collapse: collapse;
        background: white;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .material-table thead {
        background: linear-gradient(135deg, #4caf50 0%, #66bb6a 100%);
        color: white;
    }

    .material-table th {
        padding: 16px;
        text-align: left;
        font-weight: 600;
        font-size: 0.875rem;
    }

    .material-table td {
        padding: 16px;
        border-bottom: 1px solid #f0f0f0;
    }

    .material-table tbody tr:hover {
        background: #f8f9fa;
    }

    .material-badge {
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: 500;
        text-transform: uppercase;
    }

    .material-badge-primary {
        background: #e3f2fd;
        color: #1976d2;
    }

    .material-badge-success {
        background: #e8f5e8;
        color: #2e7d32;
    }

    .material-badge-secondary {
        background: #f5f5f5;
        color: #666;
    }

    .material-badge-warning {
        background: #fff3e0;
        color: #f57c00;
    }

    .material-header-actions {
        display: flex;
        gap: 12px;
    }

    .material-link {
        color: #4caf50;
        text-decoration: none;
        transition: color 0.2s ease;
    }

    .material-link:hover {
        color: #43a047;
        text-decoration: underline;
    }

    .material-text-muted {
        color: #999;
    }
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="material-card">
                <div class="card-header">
                    <h5>
                        <i class="fas fa-building me-2"></i> Bedrijf Details
                    </h5>
                    <div class="material-header-actions">
                        <a href="{{ route('admin.companies.edit', $company) }}" class="material-btn material-btn-warning">
                            <i class="fas fa-edit"></i> Bewerken
                        </a>
                        <a href="{{ route('admin.companies.index') }}" class="material-btn material-btn-secondary">
                            <i class="fas fa-arrow-left"></i> Terug naar Overzicht
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="material-section-title">Bedrijfsinformatie</h6>
                            <table class="material-info-table">
                                <tr>
                                    <td><strong>ID:</strong></td>
                                    <td>{{ $company->id }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Naam:</strong></td>
                                    <td>{{ $company->name }}</td>
                                </tr>
                                <tr>
                                    <td><strong>KVK Nummer:</strong></td>
                                    <td>{{ $company->kvk_number ?? 'Niet opgegeven' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Branche:</strong></td>
                                    <td>{{ $company->industry ?? 'Niet opgegeven' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Website:</strong></td>
                                    <td>
                                        @if($company->website)
                                            <a href="{{ $company->website }}" target="_blank" class="material-link">{{ $company->website }}</a>
                                        @else
                                            <span class="material-text-muted">Niet opgegeven</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Beschrijving:</strong></td>
                                    <td>{{ $company->description ?? 'Geen beschrijving' }}</td>
                                </tr>
                            </table>
                        </div>
                        
                        <div class="col-md-6">
                            <h6 class="material-section-title">Contact Informatie</h6>
                            <table class="material-info-table">
                                <tr>
                                    <td><strong>E-mail:</strong></td>
                                    <td>{{ $company->email }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Telefoon:</strong></td>
                                    <td>{{ $company->phone ?? 'Niet opgegeven' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Straat:</strong></td>
                                    <td>{{ $company->street ?? 'Niet opgegeven' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Huisnummer:</strong></td>
                                    <td>{{ $company->house_number ?? 'Niet opgegeven' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Postcode:</strong></td>
                                    <td>{{ $company->postal_code ?? 'Niet opgegeven' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Plaats:</strong></td>
                                    <td>{{ $company->city ?? 'Niet opgegeven' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Land:</strong></td>
                                    <td>{{ $company->country ?? 'Niet opgegeven' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <hr class="material-divider">

                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="material-section-title">Systeem Informatie</h6>
                            <table class="material-info-table">
                                <tr>
                                    <td><strong>Aangemaakt op:</strong></td>
                                    <td>{{ $company->created_at->format('d-m-Y H:i') }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Laatst bijgewerkt:</strong></td>
                                    <td>{{ $company->updated_at->format('d-m-Y H:i') }}</td>
                                </tr>
                            </table>
                        </div>
                        
                        <div class="col-md-6">
                            <h6 class="material-section-title">Statistieken</h6>
                            <table class="material-info-table">
                                <tr>
                                    <td><strong>Aantal gebruikers:</strong></td>
                                    <td>{{ $company->users->count() }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Aantal vacatures:</strong></td>
                                    <td>{{ $company->vacancies->count() }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    @if($company->users->isNotEmpty())
                        <hr class="material-divider">
                        <div class="row">
                            <div class="col-12">
                                <h6 class="material-section-title">Gebruikers van dit bedrijf</h6>
                                <div class="table-responsive">
                                    <table class="material-table">
                                        <thead>
                                            <tr>
                                                <th>Naam</th>
                                                <th>E-mail</th>
                                                <th>Rollen</th>
                                                <th>Aangemaakt</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($company->users as $user)
                                                <tr>
                                                    <td>{{ $user->first_name }} {{ $user->last_name }}</td>
                                                    <td>{{ $user->email }}</td>
                                                    <td>
                                                        @foreach($user->roles as $role)
                                                            <span class="material-badge material-badge-primary me-1">{{ $role->name }}</span>
                                                        @endforeach
                                                    </td>
                                                    <td>{{ $user->created_at->format('d-m-Y') }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if($company->vacancies->isNotEmpty())
                        <hr class="material-divider">
                        <div class="row">
                            <div class="col-12">
                                <h6 class="material-section-title">Vacatures van dit bedrijf</h6>
                                <div class="table-responsive">
                                    <table class="material-table">
                                        <thead>
                                            <tr>
                                                <th>Titel</th>
                                                <th>Categorie</th>
                                                <th>Status</th>
                                                <th>Aangemaakt</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($company->vacancies as $vacancy)
                                                <tr>
                                                    <td>{{ $vacancy->title }}</td>
                                                    <td>{{ $vacancy->category->name ?? 'Geen categorie' }}</td>
                                                    <td>
                                                        @if($vacancy->status == 'active')
                                                            <span class="material-badge material-badge-success">Actief</span>
                                                        @elseif($vacancy->status == 'inactive')
                                                            <span class="material-badge material-badge-secondary">Inactief</span>
                                                        @else
                                                            <span class="material-badge material-badge-warning">{{ $vacancy->status }}</span>
                                                        @endif
                                                    </td>
                                                    <td>{{ $vacancy->created_at->format('d-m-Y') }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
