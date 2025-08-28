@extends('admin.layouts.app')

@section('title', 'Nieuwe Vacature')

@section('content')
<style>
    :root {
        --primary-color: #9c27b0;
        --primary-light: #ba68c8;
        --primary-dark: #7b1fa2;
        --primary-hover: #ab47bc;
        --secondary-color: #607d8b;
        --success-color: #4caf50;
        --warning-color: #ff9800;
        --danger-color: #f44336;
        --info-color: #2196f3;
        --text-primary: #212121;
        --text-secondary: #757575;
        --border-color: #e0e0e0;
        --border-radius: 8px;
        --shadow: 0 2px 4px rgba(0,0,0,0.1);
        --shadow-medium: 0 4px 8px rgba(0,0,0,0.15);
        --transition: all 0.3s ease;
    }

    .material-card {
        background: white;
        border-radius: var(--border-radius);
        box-shadow: var(--shadow);
        border: 1px solid var(--border-color);
        margin-bottom: 24px;
    }

    .card-header {
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
        color: white;
        padding: 24px;
        border-radius: var(--border-radius) var(--border-radius) 0 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 16px;
    }

    .card-header h5 {
        margin: 0;
        font-size: 1.5rem;
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
        padding: 12px 24px;
        border: none;
        border-radius: var(--border-radius);
        font-weight: 600;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: var(--transition);
        cursor: pointer;
        font-size: 14px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        box-shadow: var(--shadow);
    }

    .material-btn:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-medium);
        text-decoration: none;
    }

    .material-btn-primary {
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
        color: white;
    }

    .material-btn-primary:hover {
        background: linear-gradient(135deg, var(--primary-hover) 0%, var(--primary-color) 100%);
        color: white;
    }

    .material-btn-secondary {
        background: linear-gradient(135deg, var(--secondary-color) 0%, #455a64 100%);
        color: white;
    }

    .material-btn-secondary:hover {
        background: linear-gradient(135deg, #78909c 0%, var(--secondary-color) 100%);
        color: white;
    }

    .material-btn-success {
        background: linear-gradient(135deg, var(--success-color) 0%, #388e3c 100%);
        color: white;
    }

    .material-btn-success:hover {
        background: linear-gradient(135deg, #66bb6a 0%, var(--success-color) 100%);
        color: white;
    }

    .material-btn-warning {
        background: linear-gradient(135deg, var(--warning-color) 0%, #f57c00 100%);
        color: white;
    }

    .material-btn-warning:hover {
        background: linear-gradient(135deg, #ffb74d 0%, var(--warning-color) 100%);
        color: white;
    }

    .material-btn-danger {
        background: linear-gradient(135deg, var(--danger-color) 0%, #d32f2f 100%);
        color: white;
    }

    .material-btn-danger:hover {
        background: linear-gradient(135deg, #ef5350 0%, var(--danger-color) 100%);
        color: white;
    }

    .material-btn-info {
        background: linear-gradient(135deg, var(--info-color) 0%, #1976d2 100%);
        color: white;
    }

    .material-btn-info:hover {
        background: linear-gradient(135deg, #42a5f5 0%, var(--info-color) 100%);
        color: white;
    }

    .card-body {
        padding: 32px;
    }

    .form-section {
        background: #fafafa;
        border-radius: var(--border-radius);
        padding: 24px;
        margin-bottom: 24px;
        border: 1px solid var(--border-color);
    }

    .section-title {
        font-size: 1.2rem;
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: 20px;
        padding-bottom: 12px;
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
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .form-control,
    .form-select {
        width: 100%;
        padding: 12px 16px;
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

    .form-control:focus,
    .form-select:focus {
        outline: none;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(156, 39, 176, 0.1);
    }

    .form-control[type="date"],
    .form-control[type="datetime-local"] {
        position: relative;
        background-image: none;
        padding-right: 45px;
        cursor: pointer;
        padding: 12px 16px;
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
        transform: scale(4);
        transform-origin: center;
        font-size: 18px;
    }

    .form-control[type="date"]::-webkit-calendar-picker-indicator:active {
        transform: scale(4.5);
    }

    .form-control[type="date"]::-webkit-calendar-picker-indicator:hover {
        transform: scale(4.2);
    }

    .form-control[type="date"]::-webkit-calendar-picker-indicator:focus {
        transform: scale(5);
    }

    .form-control[type="date"]::-webkit-datetime-edit {
        font-size: 16px;
        padding: 8px 0;
    }

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

    textarea.form-control {
        height: auto;
        min-height: 120px;
        resize: vertical;
        line-height: 1.6;
    }

    .form-check {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 16px;
    }

    .form-check-input {
        width: 20px;
        height: 20px;
        border: 2px solid var(--border-color);
        border-radius: 4px;
        cursor: pointer;
        transition: var(--transition);
    }

    .form-check-input:checked {
        background-color: var(--primary-color);
        border-color: var(--primary-color);
    }

    .form-check-label {
        font-weight: 500;
        color: var(--text-primary);
        cursor: pointer;
    }

    .form-help {
        font-size: 12px;
        color: var(--text-secondary);
        margin-top: 4px;
    }

    .required {
        color: var(--danger-color);
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

    @media (max-width: 768px) {
        .card-header {
            flex-direction: column;
            align-items: stretch;
        }

        .material-header-actions {
            justify-content: center;
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
                <i class="fas fa-plus"></i> Nieuwe Vacature Aanmaken
            </h5>
            <div class="material-header-actions">
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

            <form action="{{ route('admin.vacancies.store') }}" method="POST">
                @csrf
                
                <!-- Status Sectie -->
                <div class="form-section">
                    <h6 class="section-title">
                        <i class="fas fa-info-circle"></i> Huidige Status
                    </h6>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="status" class="form-label">Status <span class="required">*</span></label>
                            <select class="form-select @error('status') is-invalid @enderror" 
                                    id="status" name="status" required>
                                <option value="Open" {{ old('status') == 'Open' ? 'selected' : '' }}>Open</option>
                                <option value="In behandeling" {{ old('status') == 'In behandeling' ? 'selected' : '' }}>In behandeling</option>
                                <option value="Gesloten" {{ old('status') == 'Gesloten' ? 'selected' : '' }}>Gesloten</option>
                            </select>
                            @error('status')
                                <div class="form-help text-danger">{{ $message }}</div>
                            @enderror
                        </div>
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
                                   id="title" name="title" value="{{ old('title') }}" required>
                            @error('title')
                                <div class="form-help text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="form-group">
                            <label for="reference_number" class="form-label">Referentienummer</label>
                            <input type="text" class="form-control @error('reference_number') is-invalid @enderror" 
                                   id="reference_number" name="reference_number" value="{{ old('reference_number') }}">
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
                                        <option value="{{ $company->id }}" {{ old('company_id') == $company->id ? 'selected' : '' }}>
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
                                    <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
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
                                   id="location" name="location" value="{{ old('location') }}">
                            @error('location')
                                <div class="form-help text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="form-group">
                            <label for="employment_type" class="form-label">Type Werk</label>
                            <select class="form-select @error('employment_type') is-invalid @enderror" 
                                    id="employment_type" name="employment_type">
                                <option value="">Selecteer type</option>
                                <option value="Fulltime" {{ old('employment_type') == 'Fulltime' ? 'selected' : '' }}>Fulltime</option>
                                <option value="Parttime" {{ old('employment_type') == 'Parttime' ? 'selected' : '' }}>Parttime</option>
                                <option value="Contract" {{ old('employment_type') == 'Contract' ? 'selected' : '' }}>Contract</option>
                                <option value="Tijdelijke" {{ old('employment_type') == 'Tijdelijke' ? 'selected' : '' }}>Tijdelijke</option>
                                <option value="Stage" {{ old('employment_type') == 'Stage' ? 'selected' : '' }}>Stage</option>
                                <option value="Traineeship" {{ old('employment_type') == 'Traineeship' ? 'selected' : '' }}>Traineeship</option>
                                <option value="Freelance" {{ old('employment_type') == 'Freelance' ? 'selected' : '' }}>Freelance</option>
                                <option value="ZZP" {{ old('employment_type') == 'ZZP' ? 'selected' : '' }}>ZZP</option>
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
                                <option value="Nederlands" {{ old('language') == 'Nederlands' ? 'selected' : '' }}>Nederlands</option>
                                <option value="Engels" {{ old('language') == 'Engels' ? 'selected' : '' }}>Engels</option>
                                <option value="Duits" {{ old('language') == 'Duits' ? 'selected' : '' }}>Duits</option>
                                <option value="Frans" {{ old('language') == 'Frans' ? 'selected' : '' }}>Frans</option>
                            </select>
                            @error('language')
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
                            <label for="salary_range" class="form-label">Salaris</label>
                            <input type="text" class="form-control @error('salary_range') is-invalid @enderror" 
                                   id="salary_range" name="salary_range" value="{{ old('salary_range') }}" 
                                   placeholder="bijv. €3.000 - €5.000 per maand">
                            @error('salary_range')
                                <div class="form-help text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="form-group">
                            <label for="working_hours" class="form-label">Werkuren</label>
                            <input type="text" class="form-control @error('working_hours') is-invalid @enderror" 
                                   id="working_hours" name="working_hours" value="{{ old('working_hours') }}" 
                                   placeholder="bijv. 40 uur per week">
                            @error('working_hours')
                                <div class="form-help text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="start_date" class="form-label">Startdatum</label>
                            <div class="date-input-wrapper">
                                <input type="date" class="form-control @error('start_date') is-invalid @enderror"
                                       id="start_date" name="start_date" value="{{ old('start_date') }}">
                            </div>
                            @error('start_date')
                                <div class="form-help text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="form-group">
                            <label for="publication_date" class="form-label">Publicatiedatum</label>
                            <div class="date-input-wrapper">
                                <input type="date" class="form-control @error('publication_date') is-invalid @enderror"
                                       id="publication_date" name="publication_date" value="{{ old('publication_date') }}">
                            </div>
                            @error('publication_date')
                                <div class="form-help text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="form-group">
                            <label for="closing_date" class="form-label">Sluitingsdatum</label>
                            <div class="date-input-wrapper">
                                <input type="date" class="form-control @error('closing_date') is-invalid @enderror"
                                       id="closing_date" name="closing_date" value="{{ old('closing_date') }}">
                            </div>
                            @error('closing_date')
                                <div class="form-help text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input @error('travel_expenses') is-invalid @enderror" 
                                       id="travel_expenses" name="travel_expenses" value="1" {{ old('travel_expenses') ? 'checked' : '' }}>
                                <label for="travel_expenses" class="form-check-label">Reiskosten vergoed</label>
                            </div>
                            @error('travel_expenses')
                                <div class="form-help text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="form-group">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input @error('remote_work') is-invalid @enderror" 
                                       id="remote_work" name="remote_work" value="1" {{ old('remote_work') ? 'checked' : '' }}>
                                <label for="remote_work" class="form-check-label">Remote werk mogelijk</label>
                            </div>
                            @error('remote_work')
                                <div class="form-help text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Content Sectie -->
                <div class="form-section">
                    <h6 class="section-title">
                        <i class="fas fa-file-alt"></i> Content
                    </h6>
                    
                    <div class="form-group">
                        <label for="description" class="form-label">Beschrijving <span class="required">*</span></label>
                        <textarea class="form-control @error('description') is-invalid @enderror" 
                                  id="description" name="description" rows="6" required>{{ old('description') }}</textarea>
                        @error('description')
                            <div class="form-help text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="requirements" class="form-label">Vereisten</label>
                        <textarea class="form-control @error('requirements') is-invalid @enderror" 
                                  id="requirements" name="requirements" rows="4">{{ old('requirements') }}</textarea>
                        @error('requirements')
                            <div class="form-help text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="offer" class="form-label">Aanbod</label>
                        <textarea class="form-control @error('offer') is-invalid @enderror" 
                                  id="offer" name="offer" rows="4">{{ old('offer') }}</textarea>
                        @error('offer')
                            <div class="form-help text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="application_instructions" class="form-label">Sollicitatie-instructies</label>
                        <textarea class="form-control @error('application_instructions') is-invalid @enderror" 
                                  id="application_instructions" name="application_instructions" rows="4">{{ old('application_instructions') }}</textarea>
                        @error('application_instructions')
                            <div class="form-help text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- SEO Sectie -->
                <div class="form-section">
                    <h6 class="section-title">
                        <i class="fas fa-search"></i> SEO Instellingen
                    </h6>
                    
                    <div class="form-group">
                        <label for="meta_title" class="form-label">Meta Titel</label>
                        <input type="text" class="form-control @error('meta_title') is-invalid @enderror" 
                               id="meta_title" name="meta_title" value="{{ old('meta_title') }}" 
                               placeholder="SEO-vriendelijke titel voor zoekmachines">
                        @error('meta_title')
                            <div class="form-help text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="meta_description" class="form-label">Meta Beschrijving</label>
                        <textarea class="form-control @error('meta_description') is-invalid @enderror" 
                                  id="meta_description" name="meta_description" rows="3" 
                                  placeholder="Korte beschrijving voor zoekmachines (max 160 karakters)">{{ old('meta_description') }}</textarea>
                        @error('meta_description')
                            <div class="form-help text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="meta_keywords" class="form-label">Meta Keywords</label>
                        <input type="text" class="form-control @error('meta_keywords') is-invalid @enderror" 
                               id="meta_keywords" name="meta_keywords" value="{{ old('meta_keywords') }}" 
                               placeholder="Zoekwoorden gescheiden door komma's">
                        @error('meta_keywords')
                            <div class="form-help text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="form-actions">
                    <a href="{{ route('admin.vacancies.index') }}" class="material-btn material-btn-secondary">
                        <i class="fas fa-times"></i> Annuleren
                    </a>
                    <button type="submit" class="material-btn material-btn-primary">
                        <i class="fas fa-save"></i> Vacature Aanmaken
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
