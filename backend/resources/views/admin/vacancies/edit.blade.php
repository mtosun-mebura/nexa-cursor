@extends('admin.layouts.app')

@section('title', 'Vacature Bewerken - ' . $vacancy->title)

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

    .material-btn-info {
        background: var(--info-color);
        color: white;
    }

    .material-btn-info:hover {
        background: #1976d2;
        color: white;
        transform: translateY(-2px);
    }

    .card-body {
        padding: 24px;
    }

    .form-section {
        background: white;
        border-radius: var(--border-radius);
        padding: 24px;
        margin-bottom: 24px;
        box-shadow: var(--shadow);
        border: 1px solid var(--border-color);
    }

    .section-title {
        font-size: 1.2rem;
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: 20px;
        padding-bottom: 8px;
        border-bottom: 2px solid var(--primary-color);
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .form-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
        margin-bottom: 20px;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-label {
        display: block;
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: 8px;
        font-size: 14px;
    }

    .form-control {
        width: 100%;
        padding: 12px 16px;
        border: 2px solid var(--border-color);
        border-radius: var(--border-radius);
        font-size: 14px;
        transition: var(--transition);
        background: white;
        height: 48px;
        box-sizing: border-box;
        line-height: 24px;
        display: flex;
        align-items: center;
    }

    .form-control:focus {
        outline: none;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(156, 39, 176, 0.1);
    }

    /* Material Design Date/Time Inputs */
    .form-control[type="date"],
    .form-control[type="datetime-local"] {
        position: relative;
        background-image: none;
        padding-right: 45px;
        cursor: pointer;
    }

    .form-control[type="date"]:hover,
    .form-control[type="datetime-local"]:hover {
        border-color: var(--primary-light);
        background-color: #fafafa;
    }

    .form-control[type="date"]:focus,
    .form-control[type="datetime-local"]:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(156, 39, 176, 0.1);
        background-color: white;
    }

    /* Custom date input styling for webkit browsers */
    .form-control[type="date"]::-webkit-calendar-picker-indicator,
    .form-control[type="datetime-local"]::-webkit-calendar-picker-indicator {
        background: transparent;
        color: transparent;
        cursor: pointer;
        height: 100%;
        left: 0;
        position: absolute;
        right: 0;
        top: 0;
        width: 100%;
    }

    /* Firefox date input styling */
    .form-control[type="date"]::-moz-calendar-picker-indicator,
    .form-control[type="datetime-local"]::-moz-calendar-picker-indicator {
        background: transparent;
        border: none;
        color: transparent;
        cursor: pointer;
        height: 100%;
        left: 0;
        position: absolute;
        right: 0;
        top: 0;
        width: 100%;
    }

    /* Date input wrapper for better positioning */
    .date-input-wrapper {
        position: relative;
        display: block;
        width: 100%;
    }

    .date-input-wrapper::after {
        content: '';
        position: absolute;
        right: 16px;
        top: 50%;
        transform: translateY(-50%);
        width: 20px;
        height: 20px;
        background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%239c27b0' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3crect x='3' y='4' width='18' height='18' rx='2' ry='2'%3e%3c/rect%3e%3cline x='16' y='2' x='16' y='6'%3e%3c/line%3e%3cline x='8' y='2' x='8' y='6'%3e%3c/line%3e%3cline x='3' y='10' x='21' y='10'%3e%3c/line%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-size: contain;
        pointer-events: none;
        z-index: 1;
        opacity: 0.7;
        transition: opacity 0.3s ease;
    }

    .date-input-wrapper:hover::after {
        opacity: 1;
    }

    .date-input-wrapper:focus-within::after {
        opacity: 1;
        stroke: var(--primary-color);
    }

    /* Larger calendar dropdown styling */
    .form-control[type="date"]::-webkit-calendar-picker-indicator {
        background: transparent;
        color: transparent;
        cursor: pointer;
        height: 100%;
        left: 0;
        position: absolute;
        right: 0;
        top: 0;
        width: 100%;
    }

    /* Make the calendar dropdown larger */
    .form-control[type="date"]::-webkit-datetime-edit {
        font-size: 16px;
        padding: 8px 0;
    }

    /* Calendar popup styling (when opened) - Larger */
    .form-control[type="date"]::-webkit-calendar-picker-indicator:hover {
        background-color: rgba(156, 39, 176, 0.1);
    }

    /* Firefox calendar styling */
    .form-control[type="date"]::-moz-calendar-picker-indicator {
        background: transparent;
        border: none;
        color: transparent;
        cursor: pointer;
        height: 100%;
        left: 0;
        position: absolute;
        right: 0;
        top: 0;
        width: 100%;
    }

    /* Make calendar popup much larger */
    .form-control[type="date"]::-webkit-calendar-picker-indicator {
        transform: scale(4);
        transform-origin: center;
    }

    /* Calendar popup size override */
    .form-control[type="date"]::-webkit-calendar-picker-indicator:active {
        transform: scale(4.5);
    }

    /* Additional calendar popup styling for larger size */
    .form-control[type="date"]::-webkit-calendar-picker-indicator:hover {
        transform: scale(4.2);
    }

    /* Calendar popup styling for larger calendar */
    .form-control[type="date"]::-webkit-calendar-picker-indicator {
        font-size: 18px;
    }

    /* Make the calendar dropdown larger */
    .form-control[type="date"]::-webkit-datetime-edit {
        font-size: 16px;
        padding: 8px 0;
    }

    /* Calendar popup size increase */
    .form-control[type="date"]::-webkit-calendar-picker-indicator:focus {
        transform: scale(5);
    }

    /* Ensure date inputs have same height as other inputs */
    .form-control[type="date"] {
        width: 100%;
        padding: 12px 16px;
        padding-right: 45px;
        border: 2px solid var(--border-color);
        border-radius: var(--border-radius);
        font-size: 14px;
        transition: var(--transition);
        background: white;
        cursor: pointer;
        line-height: 24px;
        height: 48px;
        box-sizing: border-box;
        display: flex;
        align-items: center;
    }

    /* Date input wrapper sizing */
    .date-input-wrapper {
        width: 100%;
        display: block;
    }

    .date-input-wrapper .form-control {
        width: 100%;
    }

    .form-select {
        width: 100%;
        padding: 12px 16px;
        border: 2px solid var(--border-color);
        border-radius: var(--border-radius);
        font-size: 14px;
        transition: var(--transition);
        background: white;
        cursor: pointer;
        appearance: none;
        background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6,9 12,15 18,9'%3e%3c/polyline%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right 12px center;
        background-size: 16px;
        padding-right: 40px;
        height: 48px;
        box-sizing: border-box;
        line-height: 1.2;
    }

    .form-select:focus {
        outline: none;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(156, 39, 176, 0.1);
    }

    .form-textarea {
        width: 100%;
        padding: 12px 16px;
        border: 2px solid var(--border-color);
        border-radius: var(--border-radius);
        font-size: 14px;
        transition: var(--transition);
        background: white;
        resize: vertical;
        min-height: 120px;
        font-family: inherit;
    }

    .form-textarea:focus {
        outline: none;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(156, 39, 176, 0.1);
    }

    .form-checkbox {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 16px;
    }

    .form-checkbox input[type="checkbox"] {
        width: 18px;
        height: 18px;
        accent-color: var(--primary-color);
    }

    .form-checkbox label {
        font-weight: 500;
        color: var(--text-primary);
        cursor: pointer;
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

    .alert {
        padding: 16px;
        border-radius: var(--border-radius);
        margin-bottom: 20px;
        border: 1px solid;
    }

    .alert-danger {
        background: #ffebee;
        color: #c62828;
        border-color: #f44336;
    }

    .alert ul {
        margin: 0;
        padding-left: 20px;
    }

    .form-actions {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
        margin-top: 32px;
        padding-top: 24px;
        border-top: 1px solid var(--border-color);
    }





    .required {
        color: var(--danger-color);
    }

    .form-help {
        font-size: 12px;
        color: var(--text-secondary);
        margin-top: 4px;
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

        .form-row {
            grid-template-columns: 1fr;
        }

        .form-actions {
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
                <i class="fas fa-edit"></i> Vacature Bewerken
            </h5>
            <div class="material-header-actions">
                <a href="{{ route('admin.vacancies.show', $vacancy) }}" class="material-btn material-btn-info">
                    <i class="fas fa-eye"></i> Bekijken
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
            @if($errors->any())
                <div class="alert alert-danger">
                    <strong>Er zijn fouten opgetreden:</strong>
                    <ul>
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('admin.vacancies.update', $vacancy) }}" method="POST">
                @csrf
                @method('PUT')
                
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

                <!-- Basis Informatie -->
                <div class="form-section">
                    <h6 class="section-title">
                        <i class="fas fa-briefcase"></i> Basis Informatie
                    </h6>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="title" class="form-label">Titel <span class="required">*</span></label>
                            <input type="text" class="form-control @error('title') is-invalid @enderror" 
                                   id="title" name="title" value="{{ old('title', $vacancy->title) }}" required>
                            @error('title')
                                <div class="form-help text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="form-group">
                            <label for="reference_number" class="form-label">Referentienummer</label>
                            <input type="text" class="form-control @error('reference_number') is-invalid @enderror" 
                                   id="reference_number" name="reference_number" value="{{ old('reference_number', $vacancy->reference_number) }}">
                            @error('reference_number')
                                <div class="form-help text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="company_id" class="form-label">Bedrijf <span class="required">*</span></label>
                            @if(auth()->user()->hasRole('super-admin') && session('selected_tenant'))
                                @php $selectedCompany = \App\Models\Company::find(session('selected_tenant')); @endphp
                                <input type="text" class="form-control" value="{{ $selectedCompany->name }}" readonly>
                                <input type="hidden" name="company_id" value="{{ session('selected_tenant') }}">
                                <div class="form-help">Tenant geselecteerd: {{ $selectedCompany->name }}</div>
                            @else
                                <select class="form-select @error('company_id') is-invalid @enderror" 
                                        id="company_id" name="company_id" required>
                                    <option value="">Selecteer bedrijf</option>
                                    @foreach(\App\Models\Company::all() as $company)
                                        <option value="{{ $company->id }}" {{ old('company_id', $vacancy->company_id) == $company->id ? 'selected' : '' }}>
                                            {{ $company->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('company_id')
                                    <div class="form-help text-danger">{{ $message }}</div>
                                @enderror
                            @endif
                        </div>
                        
                        <div class="form-group">
                            <label for="category_id" class="form-label">Categorie</label>
                            <select class="form-select @error('category_id') is-invalid @enderror" 
                                    id="category_id" name="category_id">
                                <option value="">Selecteer categorie</option>
                                @foreach(\App\Models\Category::all() as $category)
                                    <option value="{{ $category->id }}" {{ old('category_id', $vacancy->category_id) == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('category_id')
                                <div class="form-help text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="location" class="form-label">Locatie</label>
                            <input type="text" class="form-control @error('location') is-invalid @enderror" 
                                   id="location" name="location" value="{{ old('location', $vacancy->location) }}">
                            @error('location')
                                <div class="form-help text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="form-group">
                            <label for="employment_type" class="form-label">Type Werk</label>
                            <select class="form-select @error('employment_type') is-invalid @enderror" 
                                    id="employment_type" name="employment_type">
                                <option value="">Selecteer type</option>
                                <option value="Fulltime" {{ old('employment_type', $vacancy->employment_type) == 'Fulltime' ? 'selected' : '' }}>Fulltime</option>
                                <option value="Parttime" {{ old('employment_type', $vacancy->employment_type) == 'Parttime' ? 'selected' : '' }}>Parttime</option>
                                <option value="Contract" {{ old('employment_type', $vacancy->employment_type) == 'Contract' ? 'selected' : '' }}>Contract</option>
                                <option value="Tijdelijk" {{ old('employment_type', $vacancy->employment_type) == 'Tijdelijk' ? 'selected' : '' }}>Tijdelijk</option>
                                <option value="Stage" {{ old('employment_type', $vacancy->employment_type) == 'Stage' ? 'selected' : '' }}>Stage</option>
                            </select>
                            @error('employment_type')
                                <div class="form-help text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="language" class="form-label">Taal</label>
                            <select class="form-select @error('language') is-invalid @enderror" 
                                    id="language" name="language">
                                <option value="Nederlands" {{ old('language', $vacancy->language) == 'Nederlands' ? 'selected' : '' }}>Nederlands</option>
                                <option value="Engels" {{ old('language', $vacancy->language) == 'Engels' ? 'selected' : '' }}>Engels</option>
                                <option value="Duits" {{ old('language', $vacancy->language) == 'Duits' ? 'selected' : '' }}>Duits</option>
                                <option value="Frans" {{ old('language', $vacancy->language) == 'Frans' ? 'selected' : '' }}>Frans</option>
                            </select>
                            @error('language')
                                <div class="form-help text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="form-group">
                            <label for="working_hours" class="form-label">Werkuren</label>
                            <input type="text" class="form-control @error('working_hours') is-invalid @enderror" 
                                   id="working_hours" name="working_hours" value="{{ old('working_hours', $vacancy->working_hours) }}" placeholder="bijv. 40 uur per week">
                            @error('working_hours')
                                <div class="form-help text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Salaris & Details -->
                <div class="form-section">
                    <h6 class="section-title">
                        <i class="fas fa-euro-sign"></i> Salaris & Details
                    </h6>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="salary_range" class="form-label">Salaris Range</label>
                            <input type="text" class="form-control @error('salary_range') is-invalid @enderror" 
                                   id="salary_range" name="salary_range" value="{{ old('salary_range', $vacancy->salary_range) }}" placeholder="bijv. €4.500 - €6.500 per maand">
                            @error('salary_range')
                                <div class="form-help text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="form-group">
                            <label for="start_date" class="form-label">Startdatum</label>
                            <div class="date-input-wrapper">
                                <input type="date" class="form-control @error('start_date') is-invalid @enderror" 
                                       id="start_date" name="start_date" value="{{ old('start_date', $vacancy->start_date ? $vacancy->start_date->format('Y-m-d') : '') }}">
                            </div>
                            @error('start_date')
                                <div class="form-help text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <div class="form-checkbox">
                                <input type="checkbox" id="travel_expenses" name="travel_expenses" value="1" {{ old('travel_expenses', $vacancy->travel_expenses) ? 'checked' : '' }}>
                                <label for="travel_expenses">Reiskosten vergoed</label>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <div class="form-checkbox">
                                <input type="checkbox" id="remote_work" name="remote_work" value="1" {{ old('remote_work', $vacancy->remote_work) ? 'checked' : '' }}>
                                <label for="remote_work">Remote werk mogelijk</label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Datums -->
                <div class="form-section">
                    <h6 class="section-title">
                        <i class="fas fa-calendar-alt"></i> Datums
                    </h6>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="publication_date" class="form-label">Publicatiedatum</label>
                            <div class="date-input-wrapper">
                                <input type="date" class="form-control @error('publication_date') is-invalid @enderror" 
                                       id="publication_date" name="publication_date" value="{{ old('publication_date', $vacancy->publication_date ? $vacancy->publication_date->format('Y-m-d') : '') }}">
                            </div>
                            @error('publication_date')
                                <div class="form-help text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="form-group">
                            <label for="closing_date" class="form-label">Sluitingsdatum</label>
                            <div class="date-input-wrapper">
                                <input type="date" class="form-control @error('closing_date') is-invalid @enderror" 
                                       id="closing_date" name="closing_date" value="{{ old('closing_date', $vacancy->closing_date ? $vacancy->closing_date->format('Y-m-d') : '') }}">
                            </div>
                            @error('closing_date')
                                <div class="form-help text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Content -->
                <div class="form-section">
                    <h6 class="section-title">
                        <i class="fas fa-align-left"></i> Content
                    </h6>
                    
                    <div class="form-group">
                        <label for="description" class="form-label">Functieomschrijving <span class="required">*</span></label>
                        <textarea class="form-textarea @error('description') is-invalid @enderror" 
                                  id="description" name="description" required>{{ old('description', $vacancy->description) }}</textarea>
                        @error('description')
                            <div class="form-help text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="requirements" class="form-label">Vereisten</label>
                        <textarea class="form-textarea @error('requirements') is-invalid @enderror" 
                                  id="requirements" name="requirements">{{ old('requirements', $vacancy->requirements) }}</textarea>
                        @error('requirements')
                            <div class="form-help text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="offer" class="form-label">Wat Wij Bieden</label>
                        <textarea class="form-textarea @error('offer') is-invalid @enderror" 
                                  id="offer" name="offer">{{ old('offer', $vacancy->offer) }}</textarea>
                        @error('offer')
                            <div class="form-help text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="application_instructions" class="form-label">Sollicitatie Instructies</label>
                        <textarea class="form-textarea @error('application_instructions') is-invalid @enderror" 
                                  id="application_instructions" name="application_instructions">{{ old('application_instructions', $vacancy->application_instructions) }}</textarea>
                        @error('application_instructions')
                            <div class="form-help text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- SEO -->
                <div class="form-section">
                    <h6 class="section-title">
                        <i class="fas fa-search"></i> SEO Instellingen
                    </h6>
                    
                    <div class="form-group">
                        <label for="meta_title" class="form-label">Meta Titel</label>
                        <input type="text" class="form-control @error('meta_title') is-invalid @enderror" 
                               id="meta_title" name="meta_title" value="{{ old('meta_title', $vacancy->meta_title) }}" maxlength="60">
                        <div class="form-help">Laat leeg om automatisch te genereren. Maximaal 60 karakters.</div>
                        @error('meta_title')
                            <div class="form-help text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="meta_description" class="form-label">Meta Beschrijving</label>
                        <textarea class="form-textarea @error('meta_description') is-invalid @enderror" 
                                  id="meta_description" name="meta_description" maxlength="160">{{ old('meta_description', $vacancy->meta_description) }}</textarea>
                        <div class="form-help">Laat leeg om automatisch te genereren. Maximaal 160 karakters.</div>
                        @error('meta_description')
                            <div class="form-help text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="meta_keywords" class="form-label">Meta Keywords</label>
                        <textarea class="form-textarea @error('meta_keywords') is-invalid @enderror" 
                                  id="meta_keywords" name="meta_keywords">{{ old('meta_keywords', $vacancy->meta_keywords) }}</textarea>
                        <div class="form-help">Laat leeg om automatisch te genereren. Scheid keywords met komma's.</div>
                        @error('meta_keywords')
                            <div class="form-help text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- Actie Knoppen -->
                <div class="form-actions">
                    <a href="{{ route('admin.vacancies.show', $vacancy) }}" class="material-btn material-btn-secondary">
                        <i class="fas fa-times"></i> Annuleren
                    </a>
                    <button type="submit" class="material-btn material-btn-primary">
                        <i class="fas fa-save"></i> Wijzigingen Opslaan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
