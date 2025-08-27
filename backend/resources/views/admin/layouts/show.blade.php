@extends('admin.layouts.app')

@section('title', 'Layout Details')

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
                        Layout Details
                    </h5>
                    <div>
                        <a href="{{ route('admin.layouts.edit', $layout) }}" class="mdc-button mdc-button--secondary me-2">
                            <span class="material-icons">edit</span>
                            Bewerken
                        </a>
                        <a href="{{ route('admin.layouts.index') }}" class="mdc-button mdc-button--outlined">
                            <span class="material-icons">arrow_back</span>
                            Terug naar Overzicht
                        </a>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-8">
                        <h4>{{ $layout->name }}</h4>
                        <p class="material-section-title">
                            <span class="mdc-chip mdc-chip--{{ $layout->is_active ? 'success' : 'secondary' }}">
                                {{ $layout->is_active ? 'Actief' : 'Inactief' }}
                            </span>
                            <span class="ms-2">Type: {{ ucfirst(str_replace('_', ' ', $layout->type)) }}</span>
                            <span class="ms-2">Versie: {{ $layout->version ?? 'N/A' }}</span>
                        </p>
                    </div>
                    <div class="col-md-4 text-end">
                        <span class="mdc-chip mdc-chip--primary">{{ $layout->created_at->format('d-m-Y H:i') }}</span>
                    </div>
                </div>

                <hr class="material-divider">

                <div class="row">
                    <div class="col-md-6">
                        <h6 class="material-section-title">Layout Informatie</h6>
                        <table class="material-info-table">
                            <tr>
                                <td width="150"><strong>ID:</strong></td>
                                <td>{{ $layout->id }}</td>
                            </tr>
                            <tr>
                                <td><strong>Naam:</strong></td>
                                <td>{{ $layout->name }}</td>
                            </tr>
                            <tr>
                                <td><strong>Type:</strong></td>
                                <td>{{ ucfirst(str_replace('_', ' ', $layout->type)) }}</td>
                            </tr>
                            <tr>
                                <td><strong>Versie:</strong></td>
                                <td>{{ $layout->version ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td><strong>Status:</strong></td>
                                <td>
                                    <span class="mdc-chip mdc-chip--{{ $layout->is_active ? 'success' : 'secondary' }}">
                                        {{ $layout->is_active ? 'Actief' : 'Inactief' }}
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <div class="col-md-6">
                        <h6 class="material-section-title">Styling Informatie</h6>
                        <table class="material-info-table">
                            <tr>
                                <td width="150"><strong>Header Kleur:</strong></td>
                                <td>
                                    @if($layout->header_color)
                                        <div class="d-flex align-items-center">
                                            <div class="color-preview me-2" style="width: 20px; height: 20px; background-color: {{ $layout->header_color }}; border: 1px solid #ddd; border-radius: 3px;"></div>
                                            {{ $layout->header_color }}
                                        </div>
                                    @else
                                        N/A
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Footer Kleur:</strong></td>
                                <td>
                                    @if($layout->footer_color)
                                        <div class="d-flex align-items-center">
                                            <div class="color-preview me-2" style="width: 20px; height: 20px; background-color: {{ $layout->footer_color }}; border: 1px solid #ddd; border-radius: 3px;"></div>
                                            {{ $layout->footer_color }}
                                        </div>
                                    @else
                                        N/A
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Logo URL:</strong></td>
                                <td>
                                    @if($layout->logo_url)
                                        <a href="{{ $layout->logo_url }}" target="_blank" class="text-truncate d-inline-block" style="max-width: 200px;">
                                            {{ $layout->logo_url }}
                                        </a>
                                    @else
                                        N/A
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Footer Tekst:</strong></td>
                                <td>{{ $layout->footer_text ?? 'N/A' }}</td>
                            </tr>
                        </table>
                    </div>
                </div>

                @if($layout->description)
                    <hr class="material-divider">
                    <div class="row">
                        <div class="col-md-12">
                            <h6 class="material-section-title">Beschrijving</h6>
                            <div class="mdc-card">
                                <div class="p-3">
                                    {!! nl2br(e($layout->description)) !!}
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <hr class="material-divider">

                <div class="row">
                    <div class="col-md-12">
                        <h6 class="material-section-title">HTML Inhoud</h6>
                        <div class="mdc-card">
                            <div class="p-3">
                                <small class="material-text-muted">HTML template voor de layout</small>
                            </div>
                            <div class="p-3">
                                <pre class="bg-light p-3 rounded" style="max-height: 400px; overflow-y: auto;">{{ $layout->html_content }}</pre>
                            </div>
                        </div>
                    </div>
                </div>

                @if($layout->css_content)
                    <hr class="material-divider">
                    <div class="row">
                        <div class="col-md-12">
                            <h6 class="material-section-title">CSS Inhoud</h6>
                            <div class="mdc-card">
                                <div class="p-3">
                                    <small class="material-text-muted">CSS styling voor de layout</small>
                                </div>
                                <div class="p-3">
                                    <pre class="bg-light p-3 rounded" style="max-height: 300px; overflow-y: auto;">{{ $layout->css_content }}</pre>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                @if($layout->metadata)
                    <hr class="material-divider">
                    <div class="row">
                        <div class="col-md-12">
                            <h6 class="material-section-title">Metadata</h6>
                            <div class="mdc-card">
                                <div class="p-3">
                                    <small class="material-text-muted">JSON metadata voor layout configuratie</small>
                                </div>
                                <div class="p-3">
                                    <pre class="bg-light p-3 rounded" style="max-height: 200px; overflow-y: auto;">{{ $layout->metadata }}</pre>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <hr class="material-divider">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="material-section-title">Systeem Informatie</h6>
                        <table class="material-info-table">
                            <tr>
                                <td width="150"><strong>Aangemaakt:</strong></td>
                                <td>{{ $layout->created_at->format('d-m-Y H:i') }}</td>
                            </tr>
                            <tr>
                                <td><strong>Bijgewerkt:</strong></td>
                                <td>{{ $layout->updated_at->format('d-m-Y H:i') }}</td>
                            </tr>
                        </table>
                    </div>
                    
                    <div class="col-md-6">
                        <h6 class="material-section-title">Beschikbare Variabelen</h6>
                        <div class="mdc-alert mdc-alert--info">
                            <p class="mb-2"><strong>Deze variabelen kunnen worden gebruikt in de layout:</strong></p>
                            <ul >
                                @foreach($templateVariables as $variable => $description)
                                    <li><code>{{ $variable }}</code> - {{ $description }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>

                <hr class="material-divider">
                <div class="row">
                    <div class="col-md-12">
                        <h6 class="material-section-title">Voorbeeld Preview</h6>
                        <div class="mdc-card">
                            <div class="p-3">
                                <small class="material-text-muted">Voorbeeld van hoe de layout eruit ziet</small>
                            </div>
                            <div class="p-3">
                                <div class="border rounded p-3" style="background-color: #f8f9fa;">
                                    <div class="text-center mb-3">
                                        <small class="material-text-muted">Layout Preview (HTML wordt niet gerenderd)</small>
                                    </div>
                                    <div class="bg-white border rounded p-3">
                                        <div class="material-text-muted">
                                            <span class="material-icons">visibility</span> HTML preview zou hier worden getoond
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
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
    
    .text-end {
        text-align: left !important;
        margin-top: 16px;
    }
    
    .table {
        font-size: 14px;
    }
    
    .table td {
        padding: 8px 4px;
    }
    
    pre {
        font-size: 12px;
        max-height: 200px;
    }
}

@media (max-width: 576px) {
    .container-fluid {
        padding: 8px;
    }
    
    .row {
        margin: 0;
    }
    
    .col-md-6, .col-md-8, .col-md-4 {
        padding: 0 4px;
        margin-bottom: 16px;
    }
    
    h4 {
        font-size: 1.5rem;
    }
    
    h6 {
        font-size: 1rem;
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
.mdc-card {
    border-radius: 16px;
    box-shadow: var(--md-sys-elevation-level1);
    border: 1px solid var(--md-sys-color-outline-variant);
    transition: all 0.2s ease;
    margin-bottom: 16px;
}

.mdc-card:hover {
    box-shadow: var(--md-sys-elevation-level2);
}

.mdc-chip {
    display: inline-flex;
    align-items: center;
    padding: 4px 12px;
    border-radius: 16px;
    font-size: var(--md-sys-typescale-label-medium-size);
    font-weight: var(--md-sys-typescale-label-medium-weight);
    margin: 2px;
}

.mdc-chip--primary {
    background-color: var(--md-sys-color-primary-container);
    color: var(--md-sys-color-on-primary-container);
}

.mdc-chip--secondary {
    background-color: var(--md-sys-color-secondary-container);
    color: var(--md-sys-color-on-secondary-container);
}

.mdc-chip--success {
    background-color: #E8F5E8;
    color: #1B5E20;
}

.mdc-chip--error {
    background-color: var(--md-sys-color-error-container);
    color: var(--md-sys-color-on-error-container);
}

.mdc-button {
    min-height: 48px;
    padding: 12px 24px;
    font-weight: 500;
    letter-spacing: 0.1px;
    text-transform: none;
    border-radius: 24px;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.mdc-button:hover {
    transform: translateY(-1px);
    box-shadow: var(--md-sys-elevation-level2);
}

.mdc-button:active {
    transform: translateY(0);
}

.mdc-alert {
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 16px;
    display: flex;
    align-items: flex-start;
    gap: 12px;
}

.mdc-alert--info {
    background-color: var(--md-sys-color-primary-container);
    color: var(--md-sys-color-on-primary-container);
    border: 1px solid var(--md-sys-color-primary);
}

.mdc-alert ul {
    margin: 0;
    padding-left: 20px;
}

.mdc-alert li {
    margin-bottom: 4px;
}

/* Table improvements */
.table {
    background-color: var(--md-sys-color-surface);
    border-radius: 12px;
    overflow: hidden;
    box-shadow: var(--md-sys-elevation-level1);
}

.table th {
    background-color: var(--md-sys-color-surface-variant);
    color: var(--md-sys-color-on-surface-variant);
    font-weight: var(--md-sys-typescale-label-large-weight);
    padding: 16px;
    border-bottom: 1px solid var(--md-sys-color-outline-variant);
}

.table td {
    padding: 16px;
    border-bottom: 1px solid var(--md-sys-color-outline-variant);
    color: var(--md-sys-color-on-surface);
}

.table tbody tr:hover {
    background-color: var(--md-sys-color-primary-container);
}

.table-borderless td {
    border: none;
    padding: 8px 16px;
}

/* Typography improvements */
h4 {
    font-size: var(--md-sys-typescale-headline-small-size);
    font-weight: var(--md-sys-typescale-headline-small-weight);
    color: var(--md-sys-color-on-surface);
    margin-bottom: 8px;
}

h5 {
    font-size: var(--md-sys-typescale-headline-small-size);
    font-weight: var(--md-sys-typescale-headline-small-weight);
    color: var(--md-sys-color-on-surface);
    display: flex;
    align-items: center;
    gap: 8px;
}

h6 {
    font-size: var(--md-sys-typescale-title-medium-size);
    font-weight: var(--md-sys-typescale-title-medium-weight);
    color: var(--md-sys-color-on-surface-variant);
    margin-bottom: 12px;
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

/* Code styling */
code {
    background-color: var(--md-sys-color-surface-variant);
    color: var(--md-sys-color-on-surface-variant);
    padding: 2px 6px;
    border-radius: 4px;
    font-family: 'Roboto Mono', monospace;
    font-size: 0.9em;
}

/* Pre styling */
pre {
    background-color: var(--md-sys-color-surface-variant);
    color: var(--md-sys-color-on-surface-variant);
    border-radius: 8px;
    font-family: 'Roboto Mono', monospace;
    font-size: 0.9em;
    line-height: 1.5;
    border: 1px solid var(--md-sys-color-outline-variant);
}

/* Color preview */
.color-preview {
    border-radius: 4px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

/* Link styling */
a {
    color: var(--md-sys-color-primary);
    text-decoration: none;
    transition: color 0.2s ease;
}

a:hover {
    color: var(--md-sys-color-primary);
    text-decoration: underline;
}

/* HR styling */
hr {
    border: none;
    height: 1px;
    background-color: var(--md-sys-color-outline-variant);
    margin: 24px 0;
}
</style>
@endsection
