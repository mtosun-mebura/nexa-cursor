@extends('admin.layouts.app')

@section('title', 'Nieuwe Layout')

@section('content')
<style>
    :root {
        --primary-color: #2196F3;
        --primary-light: #1976D2;
        --primary-dark: #1565C0;
        --primary-hover: #1976D2;
    }
</style>

@include('admin.material-design-template')


<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="material-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-palette me-2"></i> Nieuwe Layout
                    </h5>
                    <a href="{{ route('admin.layouts.index') }}" class="material-btn material-btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i> Terug naar Overzicht
                    </a>
                </div>
                <div class="card-body">
                    @if($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                <form action="{{ route('admin.layouts.store') }}" method="POST">
                    @csrf
                    
                    <div class="row">
                        <div class="col-md-6 col-lg-6">
                            <div class="material-form-group">
                                <label for="name" class="material-form-label">Naam *</label>
                                <input type="text" class="material-form-control" 
                                       id="name" name="name" value="{{ old('name') }}" required>
                            </div>
                        </div>
                        
                        <div class="col-md-6 col-lg-6">
                            <div class="material-form-group">
                                <label for="type" class="material-form-label">Type *</label>
                                <select class="material-form-select" id="type" name="type" required>
                                    <option value="">Selecteer type</option>
                                    <option value="email" {{ old('type') == 'email' ? 'selected' : '' }}>E-mail</option>
                                    <option value="landing_page" {{ old('type') == 'landing_page' ? 'selected' : '' }}>Landing Page</option>
                                    <option value="dashboard" {{ old('type') == 'dashboard' ? 'selected' : '' }}>Dashboard</option>
                                    <option value="profile" {{ old('type') == 'profile' ? 'selected' : '' }}>Profiel</option>
                                    <option value="vacancy" {{ old('type') == 'vacancy' ? 'selected' : '' }}>Vacature</option>
                                    <option value="custom" {{ old('type') == 'custom' ? 'selected' : '' }}>Aangepast</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 col-lg-6">
                            <div class="material-form-group">
                                <label for="version" class="material-form-label">Versie</label>
                                <input type="text" class="material-form-control" 
                                       id="version" name="version" value="{{ old('version', '1.0') }}" 
                                       placeholder="1.0">
                            </div>
                        </div>
                        
                        <div class="col-md-6 col-lg-6">
                            <div class="material-form-group">
                                <label for="is_active" class="material-form-label">Status</label>
                                <select class="material-form-select" id="is_active" name="is_active">
                                    <option value="1" {{ old('is_active', '1') == '1' ? 'selected' : '' }}>Actief</option>
                                    <option value="0" {{ old('is_active') == '0' ? 'selected' : '' }}>Inactief</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="material-form-group">
                                <label for="description" class="material-form-label">Beschrijving</label>
                                <textarea class="material-form-control" 
                                          id="description" name="description" rows="3">{{ old('description') }}</textarea>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="material-form-group">
                                <label for="html_content" class="material-form-label">HTML Inhoud *</label>
                                <textarea class="material-form-control" 
                                          id="html_content" name="html_content" rows="15" required>{{ old('html_content') }}</textarea>
                            </div>
                            <small class="material-text-muted">
                                Beschikbare variabelen: 
                                @foreach($templateVariables as $variable => $description)
                                    <code>{{ $variable }}</code> ({{ $description }})@if(!$loop->last), @endif
                                @endforeach
                            </small>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="material-form-group">
                                <label for="css_content" class="material-form-label">CSS Inhoud</label>
                                <textarea class="material-form-control" 
                                          id="css_content" name="css_content" rows="10">{{ old('css_content') }}</textarea>
                            </div>
                            <small class="material-text-muted">Optionele CSS styling voor de layout</small>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 col-lg-6">
                            <div class="material-form-group">
                                <label for="header_color" class="material-form-label">Header Kleur</label>
                                <input type="color" class="material-form-control" 
                                       id="header_color" name="header_color" value="{{ old('header_color', '#2196F3') }}">
                            </div>
                        </div>
                        
                        <div class="col-md-6 col-lg-6">
                            <div class="material-form-group">
                                <label for="footer_color" class="material-form-label">Footer Kleur</label>
                                <input type="color" class="material-form-control" 
                                       id="footer_color" name="footer_color" value="{{ old('footer_color', '#6c757d') }}">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 col-lg-6">
                            <div class="material-form-group">
                                <label for="logo_url" class="material-form-label">Logo URL</label>
                                <input type="url" class="material-form-control" 
                                       id="logo_url" name="logo_url" value="{{ old('logo_url') }}" 
                                       placeholder="https://example.com/logo.png">
                            </div>
                        </div>
                        
                        <div class="col-md-6 col-lg-6">
                            <div class="material-form-group">
                                <label for="footer_text" class="material-form-label">Footer Tekst</label>
                                <input type="text" class="material-form-control" 
                                       id="footer_text" name="footer_text" value="{{ old('footer_text') }}" 
                                       placeholder="© 2024 Skillmatching Platform">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="material-form-group">
                                <label for="metadata" class="material-form-label">Metadata (JSON)</label>
                                <textarea class="material-form-control" 
                                          id="metadata" name="metadata" rows="4" 
                                          placeholder='{"responsive": true, "theme": "light", "features": ["header", "footer"]}'>{{ old('metadata') }}</textarea>
                            </div>
                            <small class="material-text-muted">Optionele JSON metadata voor layout configuratie</small>
                        </div>
                    </div>

                    <div class="material-form-actions">
                        <a href="{{ route('admin.layouts.index') }}" class="material-btn material-btn-secondary">
                            <i class="fas fa-times me-2"></i> Annuleren
                        </a>
                        <button type="submit" class="material-btn material-btn-primary">
                            <i class="fas fa-save me-2"></i> Layout Opslaan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
/* Responsive Design voor Material Design 3 */
@media (max-width: 768px) {
    .mdc-card {
        margin: 8px;
        padding: 16px;
    }
    
    .mdc-text-field {
        margin-bottom: 16px;
    }
    
    .mdc-select {
        margin-bottom: 16px;
    }
    
    .d-flex.justify-content-end {
        flex-direction: column;
        gap: 8px;
    }
    
    .d-flex.justify-content-end .mdc-button {
        width: 100%;
        justify-content: center;
    }
}

@media (max-width: 576px) {
    .container-fluid {
        padding: 8px;
    }
    
    .row {
        margin: 0;
    }
    
    .col-md-6, .col-lg-6 {
        padding: 0 4px;
    }
    
    .mdc-text-field__input {
        font-size: 16px; /* Voorkomt zoom op iOS */
    }
}

/* Tablet optimizations */
@media (min-width: 769px) and (max-width: 1024px) {
    .mdc-card {
        margin: 16px;
        padding: 24px;
    }
    
    .col-md-6 {
        margin-bottom: 16px;
    }
}

/* Material Design 3 verbeteringen */
.mdc-text-field {
    position: relative;
    margin-bottom: 20px;
}

.mdc-text-field__input {
    width: 100%;
    padding: 16px 12px 8px;
    border: 1px solid var(--md-sys-color-outline);
    border-radius: 4px;
    background-color: var(--md-sys-color-surface);
    color: var(--md-sys-color-on-surface);
    font-family: 'Roboto', sans-serif;
    font-size: var(--md-sys-typescale-body-large-size);
    transition: all 0.2s ease;
    min-height: 56px;
}

.mdc-text-field__input:focus {
    outline: none;
    border-color: var(--md-sys-color-primary);
    border-width: 2px;
    box-shadow: 0 0 0 1px var(--md-sys-color-primary);
}

.mdc-text-field__label {
    position: absolute;
    top: 8px;
    left: 12px;
    color: var(--md-sys-color-on-surface-variant);
    font-size: var(--md-sys-typescale-body-medium-size);
    transition: all 0.2s ease;
    pointer-events: none;
    background-color: var(--md-sys-color-surface);
    padding: 0 4px;
}

.mdc-text-field__input:focus + .mdc-text-field__label,
.mdc-text-field__input:not(:placeholder-shown) + .mdc-text-field__label {
    top: 4px;
    font-size: var(--md-sys-typescale-label-small-size);
    color: var(--md-sys-color-primary);
    transform: translateY(-50%);
}

.mdc-select {
    width: 100%;
    padding: 16px 12px 8px;
    border: 1px solid var(--md-sys-color-outline);
    border-radius: 4px;
    background-color: var(--md-sys-color-surface);
    color: var(--md-sys-color-on-surface);
    font-family: 'Roboto', sans-serif;
    font-size: var(--md-sys-typescale-body-large-size);
    cursor: pointer;
    min-height: 56px;
    transition: all 0.2s ease;
}

.mdc-select:focus {
    outline: none;
    border-color: var(--md-sys-color-primary);
    border-width: 2px;
    box-shadow: 0 0 0 1px var(--md-sys-color-primary);
}

/* Color input styling */
input[type="color"] {
    height: 56px;
    border: 1px solid var(--md-sys-color-outline);
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.2s ease;
}

input[type="color"]:focus {
    outline: none;
    border-color: var(--md-sys-color-primary);
    border-width: 2px;
    box-shadow: 0 0 0 1px var(--md-sys-color-primary);
}

/* Textarea styling */
textarea.mdc-text-field__input {
    resize: vertical;
    min-height: 120px;
    font-family: 'Roboto Mono', monospace;
    line-height: 1.5;
}

/* Code styling */
code {
    background-color: var(--md-sys-color-surface-variant);
    color: var(--md-sys-color-on-surface-variant);
    padding: 2px 6px;
    border-radius: 4px;
    font-family: 'Roboto Mono', monospace;
    font-size: 0.9em;
}

/* Button improvements */
.mdc-button {
    min-height: 48px;
    padding: 12px 24px;
    font-weight: 500;
    letter-spacing: 0.1px;
    text-transform: none;
    border-radius: 24px;
    transition: all 0.2s ease;
}

.mdc-button:hover {
    transform: translateY(-1px);
    box-shadow: var(--md-sys-elevation-level2);
}

.mdc-button:active {
    transform: translateY(0);
}

/* Alert improvements */
.mdc-alert {
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 16px;
    display: flex;
    align-items: flex-start;
    gap: 12px;
}

.mdc-alert ul {
    margin: 0;
    padding-left: 20px;
}

.mdc-alert li {
    margin-bottom: 4px;
}

/* Card improvements */
.mdc-card {
    border-radius: 16px;
    box-shadow: var(--md-sys-elevation-level1);
    border: 1px solid var(--md-sys-color-outline-variant);
    transition: all 0.2s ease;
}

.mdc-card:hover {
    box-shadow: var(--md-sys-elevation-level2);
}

/* Typography improvements */
h5 {
    font-size: var(--md-sys-typescale-headline-small-size);
    font-weight: var(--md-sys-typescale-headline-small-weight);
    color: var(--md-sys-color-on-surface);
    display: flex;
    align-items: center;
    gap: 8px;
}

.material-icons {
    font-size: 20px;
}

/* Small text styling */
.text-muted {
    color: var(--md-sys-color-on-surface-variant) !important;
    font-size: var(--md-sys-typescale-body-small-size);
    line-height: 1.4;
}
</style>
@endsection
