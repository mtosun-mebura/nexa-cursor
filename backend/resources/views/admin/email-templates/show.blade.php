@extends('admin.layouts.app')

@section('title', 'E-mail Sjabloon Details')

@section('content')
<style>
    :root {
        --primary-color: #009688;
        --primary-light: #4db6ac;
        --primary-dark: #00695c;
        --primary-hover: #26a69a;
    }
</style>

@include('admin.material-design-template')


<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="material-card">
                <div class="card-header">
                    <h5 >
                        <i class="fas fa-envelope"></i> E-mail Sjabloon Details
                    </h5>
                    <div>
                        <a href="{{ route('admin.email-templates.edit', $emailTemplate) }}" class="btn btn-warning me-2">
                            <i class="fas fa-edit"></i> Bewerken
                        </a>
                        <a href="{{ route('admin.email-templates.index') }}" class="material-btn material-btn-secondary">
                            <i class="fas fa-arrow-left"></i> Terug naar Overzicht
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <h4>{{ $emailTemplate->name }}</h4>
                            <p class="material-section-title">
                                <span class="badge bg-{{ $emailTemplate->is_active ? 'success' : 'secondary' }}">
                                    {{ $emailTemplate->is_active ? 'Actief' : 'Inactief' }}
                                </span>
                                <span class="ms-2">Type: {{ ucfirst(str_replace('_', ' ', $emailTemplate->type)) }}</span>
                            </p>
                        </div>
                        <div class="col-md-4 text-end">
                            @if($emailTemplate->company)
                                <span class="badge bg-info">{{ $emailTemplate->company->name }}</span>
                            @endif
                        </div>
                    </div>

                    <hr class="material-divider">

                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="material-section-title">Sjabloon Informatie</h6>
                            <table class="material-info-table">
                                <tr>
                                    <td width="150"><strong>ID:</strong></td>
                                    <td>{{ $emailTemplate->id }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Naam:</strong></td>
                                    <td>{{ $emailTemplate->name }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Type:</strong></td>
                                    <td>{{ ucfirst(str_replace('_', ' ', $emailTemplate->type)) }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Onderwerp:</strong></td>
                                    <td>{{ $emailTemplate->subject }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Status:</strong></td>
                                    <td>
                                        <span class="badge bg-{{ $emailTemplate->is_active ? 'success' : 'secondary' }}">
                                            {{ $emailTemplate->is_active ? 'Actief' : 'Inactief' }}
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        
                        <div class="col-md-6">
                            <h6 class="material-section-title">Systeem Informatie</h6>
                            <table class="material-info-table">
                                <tr>
                                    <td width="150"><strong>Bedrijf:</strong></td>
                                    <td>{{ $emailTemplate->company->name ?? 'Globaal' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Aangemaakt:</strong></td>
                                    <td>{{ $emailTemplate->created_at->format('d-m-Y H:i') }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Bijgewerkt:</strong></td>
                                    <td>{{ $emailTemplate->updated_at->format('d-m-Y H:i') }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    @if($emailTemplate->description)
                        <hr class="material-divider">
                        <div class="row">
                            <div class="col-md-12">
                                <h6 class="material-section-title">Beschrijving</h6>
                                <div class="material-card">
                                    <div class="card-body">
                                        {!! nl2br(e($emailTemplate->description)) !!}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <hr class="material-divider">

                    <div class="row">
                        <div class="col-md-12">
                            <h6 class="material-section-title">HTML Inhoud</h6>
                            <div class="material-card">
                                <div class="card-header">
                                    <small class="material-text-muted">HTML versie van de e-mail</small>
                                </div>
                                <div class="card-body">
                                    <pre class="bg-light p-3 rounded" style="max-height: 300px; overflow-y: auto;">{{ $emailTemplate->html_content }}</pre>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($emailTemplate->text_content)
                        <hr class="material-divider">
                        <div class="row">
                            <div class="col-md-12">
                                <h6 class="material-section-title">Tekst Inhoud (Plain Text)</h6>
                                <div class="material-card">
                                    <div class="card-header">
                                        <small class="material-text-muted">Tekstversie voor e-mail clients die geen HTML ondersteunen</small>
                                    </div>
                                    <div class="card-body">
                                        <pre class="bg-light p-3 rounded" style="max-height: 200px; overflow-y: auto;">{{ $emailTemplate->text_content }}</pre>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <hr class="material-divider">
                    <div class="row">
                        <div class="col-md-12">
                            <h6 class="material-section-title">Beschikbare Variabelen</h6>
                            <div class="alert alert-info">
                                <p class="mb-2"><strong>Deze variabelen kunnen worden gebruikt in de sjabloon:</strong></p>
                                <ul >
                                    @foreach($templateVariables as $variable => $description)
                                        <li><code>{{ $variable }}</code> - {{ $description }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
