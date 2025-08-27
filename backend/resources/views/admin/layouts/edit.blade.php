@extends('admin.layouts.app')

@section('title', 'Layout Bewerken')

@section('content')
<style>
    :root {
        --primary-color: #f44336;
        --primary-light: #ef5350;
        --primary-dark: #d32f2f;
        --primary-hover: #e53935;
    }
</style>

@include('admin.material-design-template')


<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="mdc-card mdc-card--elevated">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 >
                        <span class="material-icons">palette</span>
                        Layout Bewerken
                    </h5>
                    <div>
                        <a href="{{ route('admin.layouts.show', $layout) }}" class="mdc-button mdc-button--outlined me-2">
                            <span class="material-icons">visibility</span>
                            Bekijken
                        </a>
                        <a href="{{ route('admin.layouts.index') }}" class="mdc-button mdc-button--outlined">
                            <span class="material-icons">arrow_back</span>
                            Terug naar Overzicht
                        </a>
                    </div>
                </div>

                @if($errors->any())
                    <div class="mdc-alert mdc-alert--error mb-3">
                        <span class="material-icons">error</span>
                        <ul >
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('admin.layouts.update', $layout) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="row">
                        <div class="col-md-6 col-lg-6">
                            <div class="mdc-text-field">
                                <input type="text" class="mdc-text-field__input" 
                                       id="name" name="name" value="{{ old('name', $layout->name) }}" required>
                                <label class="mdc-text-field__label" for="name">Naam *</label>
                            </div>
                        </div>
                        
                        <div class="col-md-6 col-lg-6">
                            <select class="mdc-select" id="type" name="type" required>
                                <option value="">Selecteer type</option>
                                <option value="email" {{ old('type', $layout->type) == 'email' ? 'selected' : '' }}>E-mail</option>
                                <option value="landing_page" {{ old('type', $layout->type) == 'landing_page' ? 'selected' : '' }}>Landing Page</option>
                                <option value="dashboard" {{ old('type', $layout->type) == 'dashboard' ? 'selected' : '' }}>Dashboard</option>
                                <option value="profile" {{ old('type', $layout->type) == 'profile' ? 'selected' : '' }}>Profiel</option>
                                <option value="vacancy" {{ old('type', $layout->type) == 'vacancy' ? 'selected' : '' }}>Vacature</option>
                                <option value="custom" {{ old('type', $layout->type) == 'custom' ? 'selected' : '' }}>Aangepast</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 col-lg-6">
                            <div class="mdc-text-field">
                                <input type="text" class="mdc-text-field__input" 
                                       id="version" name="version" value="{{ old('version', $layout->version ?? '1.0') }}" 
                                       placeholder="1.0">
                                <label class="mdc-text-field__label" for="version">Versie</label>
                            </div>
                        </div>
                        
                        <div class="col-md-6 col-lg-6">
                            <select class="mdc-select" id="is_active" name="is_active">
                                <option value="1" {{ old('is_active', $layout->is_active) == '1' ? 'selected' : '' }}>Actief</option>
                                <option value="0" {{ old('is_active', $layout->is_active) == '0' ? 'selected' : '' }}>Inactief</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="mdc-text-field">
                                <textarea class="mdc-text-field__input" 
                                          id="description" name="description" rows="3">{{ old('description', $layout->description) }}</textarea>
                                <label class="mdc-text-field__label" for="description">Beschrijving</label>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="mdc-text-field">
                                <textarea class="mdc-text-field__input" 
                                          id="html_content" name="html_content" rows="15" required>{{ old('html_content', $layout->html_content) }}</textarea>
                                <label class="mdc-text-field__label" for="html_content">HTML Inhoud *</label>
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
                            <div class="mdc-text-field">
                                <textarea class="mdc-text-field__input" 
                                          id="css_content" name="css_content" rows="10">{{ old('css_content', $layout->css_content) }}</textarea>
                                <label class="mdc-text-field__label" for="css_content">CSS Inhoud</label>
                            </div>
                            <small class="material-text-muted">Optionele CSS styling voor de layout</small>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 col-lg-6">
                            <div class="mdc-text-field">
                                <input type="color" class="mdc-text-field__input" 
                                       id="header_color" name="header_color" value="{{ old('header_color', $layout->header_color ?? '#007bff') }}">
                                <label class="mdc-text-field__label" for="header_color">Header Kleur</label>
                            </div>
                        </div>
                        
                        <div class="col-md-6 col-lg-6">
                            <div class="mdc-text-field">
                                <input type="color" class="mdc-text-field__input" 
                                       id="footer_color" name="footer_color" value="{{ old('footer_color', $layout->footer_color ?? '#6c757d') }}">
                                <label class="mdc-text-field__label" for="footer_color">Footer Kleur</label>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 col-lg-6">
                            <div class="mdc-text-field">
                                <input type="url" class="mdc-text-field__input" 
                                       id="logo_url" name="logo_url" value="{{ old('logo_url', $layout->logo_url) }}" 
                                       placeholder="https://example.com/logo.png">
                                <label class="mdc-text-field__label" for="logo_url">Logo URL</label>
                            </div>
                        </div>
                        
                        <div class="col-md-6 col-lg-6">
                            <div class="mdc-text-field">
                                <input type="text" class="mdc-text-field__input" 
                                       id="footer_text" name="footer_text" value="{{ old('footer_text', $layout->footer_text) }}" 
                                       placeholder="© 2024 Skillmatching Platform">
                                <label class="mdc-text-field__label" for="footer_text">Footer Tekst</label>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="mdc-text-field">
                                <textarea class="mdc-text-field__input" 
                                          id="metadata" name="metadata" rows="4" 
                                          placeholder='{"responsive": true, "theme": "light", "features": ["header", "footer"]}'>{{ old('metadata', $layout->metadata) }}</textarea>
                                <label class="mdc-text-field__label" for="metadata">Metadata (JSON)</label>
                            </div>
                            <small class="material-text-muted">Optionele JSON metadata voor layout configuratie</small>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mt-3">
                        <a href="{{ route('admin.layouts.index') }}" class="mdc-button mdc-button--outlined me-2">Annuleren</a>
                        <button type="submit" class="mdc-button">
                            <span class="material-icons">save</span>
                            Wijzigingen Opslaan
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
    
    .d-flex.justify-content-between {
        flex-direction: column;
        gap: 16px;
        align-items: stretch;
    }
    
    .d-flex.justify-content-between > div {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    
    .d-flex.justify-content-between .mdc-button {
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
