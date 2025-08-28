@extends('admin.layouts.app')

@section('title', 'Vacature Details - ' . $vacancy->title)

@section('content')
<style>
    :root {
        --primary-color: #9c27b0;
        --primary-light: #ba68c8;
        --primary-dark: #7b1fa2;
        --primary-hover: #ab47bc;
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
    }

    .material-btn-primary {
        background: var(--primary-color);
        color: white;
    }

    .material-btn-primary:hover {
        background: var(--primary-hover);
        color: white;
        transform: translateY(-2px);
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
        background: var(--secondary-color);
        color: white;
    }

    .material-btn-secondary:hover {
        background: #616161;
        color: white;
        transform: translateY(-2px);
    }

    .material-btn-danger {
        background: var(--danger-color);
        color: white;
    }

    .material-btn-danger:hover {
        background: #d32f2f;
        color: white;
        transform: translateY(-2px);
    }

    .material-btn-success {
        background: var(--success-color);
        color: white;
    }

    .material-btn-success:hover {
        background: #388e3c;
        color: white;
        transform: translateY(-2px);
    }

    .card-body {
        padding: 24px;
    }

    .vacancy-header {
        background: linear-gradient(135deg, #f8f9fa, #e9ecef);
        border-radius: var(--border-radius);
        padding: 24px;
        margin-bottom: 24px;
        border-left: 4px solid var(--primary-color);
    }

    .vacancy-title {
        font-size: 2rem;
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: 12px;
        line-height: 1.2;
    }

    .vacancy-meta {
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

    .status-badge {
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

    .status-badge:hover {
        transform: scale(1.05);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
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

    .content-section {
        background: white;
        border-radius: var(--border-radius);
        padding: 24px;
        box-shadow: var(--shadow);
        border: 1px solid var(--border-color);
        margin-bottom: 24px;
    }

    .content-title {
        font-size: 1.2rem;
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .content-body {
        line-height: 1.6;
        color: var(--text-secondary);
        white-space: pre-line;
    }

    .seo-section {
        background: linear-gradient(135deg, #e3f2fd, #bbdefb);
        border-radius: var(--border-radius);
        padding: 20px;
        margin-top: 24px;
        border-left: 4px solid var(--info-color);
    }

    .seo-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: #1565c0;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .seo-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 16px;
    }

    .seo-item {
        background: white;
        border-radius: 6px;
        padding: 12px;
        border: 1px solid #e3f2fd;
    }

    .seo-label {
        font-size: 12px;
        font-weight: 600;
        color: #1565c0;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 4px;
    }

    .seo-value {
        font-size: 14px;
        color: var(--text-primary);
        word-break: break-word;
    }

    .action-buttons {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
        margin-top: 24px;
        padding-top: 24px;
        border-top: 1px solid var(--border-color);
    }

    @media (max-width: 768px) {
        .card-header {
            flex-direction: column;
            align-items: stretch;
        }

        .material-header-actions {
            justify-content: center;
        }

        .vacancy-meta {
            flex-direction: column;
            align-items: flex-start;
            gap: 12px;
        }

        .info-grid {
            grid-template-columns: 1fr;
        }

        .action-buttons {
            flex-direction: column;
        }

        .material-btn {
            justify-content: center;
        }
    }
</style>

<div class="container-fluid">
    <div class="material-card">
        <div class="card-header">
            <h5>
                <i class="fas fa-briefcase"></i> Vacature Details
            </h5>
            <div class="material-header-actions">
                <a href="{{ route('admin.vacancies.edit', $vacancy) }}" class="material-btn material-btn-warning">
                    <i class="fas fa-edit"></i> Bewerken
                </a>
                @if($vacancy->status !== 'Open' && $vacancy->status !== 'In behandeling')
                    <form action="{{ route('admin.vacancies.update', $vacancy) }}" method="POST" class="d-inline">
                        @csrf @method('PUT')
                        <input type="hidden" name="status" value="Open">
                        <input type="hidden" name="title" value="{{ $vacancy->title }}">
                        <input type="hidden" name="company_id" value="{{ $vacancy->company_id }}">
                        <input type="hidden" name="description" value="{{ $vacancy->description }}">
                        <button type="submit" class="material-btn material-btn-success">
                            <i class="fas fa-play"></i> Openen
                        </button>
                    </form>
                @elseif($vacancy->status === 'In behandeling')
                    <form action="{{ route('admin.vacancies.update', $vacancy) }}" method="POST" class="d-inline">
                        @csrf @method('PUT')
                        <input type="hidden" name="status" value="Open">
                        <input type="hidden" name="title" value="{{ $vacancy->title }}">
                        <input type="hidden" name="company_id" value="{{ $vacancy->company_id }}">
                        <input type="hidden" name="description" value="{{ $vacancy->description }}">
                        <button type="submit" class="material-btn material-btn-success">
                            <i class="fas fa-play"></i> Openen
                        </button>
                    </form>
                    <form action="{{ route('admin.vacancies.update', $vacancy) }}" method="POST" class="d-inline">
                        @csrf @method('PUT')
                        <input type="hidden" name="status" value="Gesloten">
                        <input type="hidden" name="title" value="{{ $vacancy->title }}">
                        <input type="hidden" name="company_id" value="{{ $vacancy->company_id }}">
                        <input type="hidden" name="description" value="{{ $vacancy->description }}">
                        <button type="submit" class="material-btn material-btn-danger">
                            <i class="fas fa-stop"></i> Sluiten
                        </button>
                    </form>
                @else
                    <form action="{{ route('admin.vacancies.update', $vacancy) }}" method="POST" class="d-inline">
                        @csrf @method('PUT')
                        <input type="hidden" name="status" value="In behandeling">
                        <input type="hidden" name="title" value="{{ $vacancy->title }}">
                        <input type="hidden" name="company_id" value="{{ $vacancy->company_id }}">
                        <input type="hidden" name="description" value="{{ $vacancy->description }}">
                        <button type="submit" class="material-btn material-btn-warning">
                            <i class="fas fa-clock"></i> In behandeling
                        </button>
                    </form>
                    <form action="{{ route('admin.vacancies.update', $vacancy) }}" method="POST" class="d-inline">
                        @csrf @method('PUT')
                        <input type="hidden" name="status" value="Gesloten">
                        <input type="hidden" name="title" value="{{ $vacancy->title }}">
                        <input type="hidden" name="company_id" value="{{ $vacancy->company_id }}">
                        <input type="hidden" name="description" value="{{ $vacancy->description }}">
                        <button type="submit" class="material-btn material-btn-danger">
                            <i class="fas fa-stop"></i> Sluiten
                        </button>
                    </form>
                @endif
                <a href="{{ route('admin.vacancies.index') }}" class="material-btn material-btn-secondary">
                    <i class="fas fa-arrow-left"></i> Terug naar Overzicht
                </a>
            </div>
        </div>
        
        <div class="card-body">
            <!-- Vacature Header -->
            <div class="vacancy-header">
                <h1 class="vacancy-title">{{ $vacancy->title }}</h1>
                <div class="vacancy-meta">
                    <div class="meta-item">
                        <i class="fas fa-building"></i>
                        <span>{{ $vacancy->company->name }}</span>
                    </div>
                    @if($vacancy->location)
                        <div class="meta-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <span>{{ $vacancy->location }}</span>
                        </div>
                    @endif
                    @if($vacancy->employment_type)
                        <div class="meta-item">
                            <i class="fas fa-clock"></i>
                            <span>{{ $vacancy->employment_type }}</span>
                        </div>
                    @endif
                    <div class="meta-item">
                        <i class="fas fa-calendar"></i>
                        <span>Gepubliceerd: {{ $vacancy->publication_date ? $vacancy->publication_date->format('d-m-Y') : 'Niet opgegeven' }}</span>
                    </div>
                </div>
                <div class="status-badge @if($vacancy->status === 'In behandeling') status-processing @elseif($vacancy->status === 'Gesloten') status-closed @else status-{{ strtolower(str_replace(' ', '-', $vacancy->status)) }} @endif">
                    <i class="fas fa-circle"></i>
                    {{ $vacancy->status }}
                </div>
            </div>

            <!-- Informatie Grid -->
            <div class="info-grid">
                <!-- Basis Informatie -->
                <div class="info-section">
                    <h6 class="section-title">
                        <i class="fas fa-info-circle"></i> Basis Informatie
                    </h6>
                    <table class="info-table">
                        <tr>
                            <td>ID</td>
                            <td>{{ $vacancy->id }}</td>
                        </tr>
                        <tr>
                            <td>Referentie</td>
                            <td>{{ $vacancy->reference_number ?? 'Niet opgegeven' }}</td>
                        </tr>
                        <tr>
                            <td>Bedrijf</td>
                            <td>{{ $vacancy->company->name }}</td>
                        </tr>
                        <tr>
                            <td>Categorie</td>
                            <td>{{ $vacancy->category->name ?? 'Geen categorie' }}</td>
                        </tr>
                        <tr>
                            <td>Locatie</td>
                            <td>{{ $vacancy->location ?? 'Niet opgegeven' }}</td>
                        </tr>
                        <tr>
                            <td>Type Werk</td>
                            <td>{{ $vacancy->employment_type ?? 'Niet opgegeven' }}</td>
                        </tr>
                        <tr>
                            <td>Taal</td>
                            <td>{{ $vacancy->language ?? 'Nederlands' }}</td>
                        </tr>
                    </table>
                </div>

                <!-- Salaris & Details -->
                <div class="info-section">
                    <h6 class="section-title">
                        <i class="fas fa-euro-sign"></i> Salaris & Details
                    </h6>
                    <table class="info-table">
                        <tr>
                            <td>Status</td>
                            <td>
                                <span class="status-badge @if($vacancy->status === 'In behandeling') status-processing @elseif($vacancy->status === 'Gesloten') status-closed @else status-{{ strtolower(str_replace(' ', '-', $vacancy->status)) }} @endif">
                                    <i class="fas fa-circle"></i>
                                    {{ $vacancy->status }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td>Salaris</td>
                            <td>{{ $vacancy->salary_range ?? 'Niet opgegeven' }}</td>
                        </tr>
                        <tr>
                            <td>Startdatum</td>
                            <td>{{ $vacancy->start_date ? $vacancy->start_date->format('d-m-Y') : 'Niet opgegeven' }}</td>
                        </tr>
                        <tr>
                            <td>Werkuren</td>
                            <td>{{ $vacancy->working_hours ?? 'Niet opgegeven' }}</td>
                        </tr>
                        <tr>
                            <td>Reiskosten</td>
                            <td>
                                @if($vacancy->travel_expenses)
                                    <span class="text-success"><i class="fas fa-check"></i> Vergoed</span>
                                @else
                                    <span class="text-secondary"><i class="fas fa-times"></i> Niet vergoed</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td>Remote Werk</td>
                            <td>
                                @if($vacancy->remote_work)
                                    <span class="text-success"><i class="fas fa-check"></i> Mogelijk</span>
                                @else
                                    <span class="text-secondary"><i class="fas fa-times"></i> Niet mogelijk</span>
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Datums -->
                <div class="info-section">
                    <h6 class="section-title">
                        <i class="fas fa-calendar-alt"></i> Datums
                    </h6>
                    <table class="info-table">
                        <tr>
                            <td>Publicatiedatum</td>
                            <td>{{ $vacancy->publication_date ? $vacancy->publication_date->format('d-m-Y H:i') : 'Niet opgegeven' }}</td>
                        </tr>
                        <tr>
                            <td>Sluitingsdatum</td>
                            <td>{{ $vacancy->closing_date ? $vacancy->closing_date->format('d-m-Y') : 'Niet opgegeven' }}</td>
                        </tr>
                        <tr>
                            <td>Aangemaakt</td>
                            <td>{{ $vacancy->created_at->format('d-m-Y H:i') }}</td>
                        </tr>
                        <tr>
                            <td>Bijgewerkt</td>
                            <td>{{ $vacancy->updated_at->format('d-m-Y H:i') }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Beschrijving -->
            @if($vacancy->description)
                <div class="content-section">
                    <h6 class="content-title">
                        <i class="fas fa-align-left"></i> Functieomschrijving
                    </h6>
                    <div class="content-body">{{ $vacancy->description }}</div>
                </div>
            @endif

            <!-- Vereisten -->
            @if($vacancy->requirements)
                <div class="content-section">
                    <h6 class="content-title">
                        <i class="fas fa-list-check"></i> Vereisten
                    </h6>
                    <div class="content-body">{{ $vacancy->requirements }}</div>
                </div>
            @endif

            <!-- Aanbod -->
            @if($vacancy->offer)
                <div class="content-section">
                    <h6 class="content-title">
                        <i class="fas fa-gift"></i> Wat Wij Bieden
                    </h6>
                    <div class="content-body">{{ $vacancy->offer }}</div>
                </div>
            @endif

            <!-- Sollicitatie Instructies -->
            @if($vacancy->application_instructions)
                <div class="content-section">
                    <h6 class="content-title">
                        <i class="fas fa-paper-plane"></i> Sollicitatie Instructies
                    </h6>
                    <div class="content-body">{{ $vacancy->application_instructions }}</div>
                </div>
            @endif

            <!-- SEO Informatie -->
            <div class="seo-section">
                <h6 class="seo-title">
                    <i class="fas fa-search"></i> SEO Informatie
                </h6>
                <div class="seo-grid">
                    <div class="seo-item">
                        <div class="seo-label">Meta Titel</div>
                        <div class="seo-value">{{ $vacancy->meta_title ?? 'Niet ingesteld' }}</div>
                    </div>
                    <div class="seo-item">
                        <div class="seo-label">Meta Beschrijving</div>
                        <div class="seo-value">{{ Str::limit($vacancy->meta_description ?? 'Niet ingesteld', 100) }}</div>
                    </div>
                    <div class="seo-item">
                        <div class="seo-label">Meta Keywords</div>
                        <div class="seo-value">{{ Str::limit($vacancy->meta_keywords ?? 'Niet ingesteld', 100) }}</div>
                    </div>
                    <div class="seo-item">
                        <div class="seo-label">URL</div>
                        <div class="seo-value">{{ $vacancy->url ?? 'Niet beschikbaar' }}</div>
                    </div>
                </div>
            </div>

            <!-- Actie Knoppen -->
            <div class="action-buttons">
                <a href="{{ route('admin.vacancies.edit', $vacancy) }}" class="material-btn material-btn-warning">
                    <i class="fas fa-edit"></i> Vacature Bewerken
                </a>
                <a href="{{ route('admin.vacancies.index') }}" class="material-btn material-btn-secondary">
                    <i class="fas fa-list"></i> Terug naar Overzicht
                </a>
                <form action="{{ route('admin.vacancies.destroy', $vacancy) }}" method="POST" class="d-inline" onsubmit="return confirm('Weet je zeker dat je deze vacature wilt verwijderen?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="material-btn material-btn-danger">
                        <i class="fas fa-trash"></i> Vacature Verwijderen
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
