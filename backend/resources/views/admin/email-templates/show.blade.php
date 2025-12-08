@extends('admin.layouts.app')

@section('title', 'E-mail Sjabloon Details - ' . $emailTemplate->name)

@section('content')


<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono mb-3">
                {{ $title ?? "Pagina" }}
            </h1>
        </div>
        <div class="flex items-center gap-2.5">
            <a href="{{ route('admin.' . str_replace(['admin.', '.create', '.edit', '.show'], ['', '.index', '.index', '.index'], request()->route()->getName())) }}" class="kt-btn kt-btn-outline">
                <i class="ki-filled ki-arrow-left me-2"></i>
                Terug
            </a>
        </div>
    </div>

    <div class="kt-card">
        <div class="kt-card-header">
            <h5>
                <i class="fas fa-envelope"></i>
                E-mail Sjabloon Details: {{ $emailTemplate->name }}
            </h5>
            <div class="material-header-actions">
                <a href="{{ route('admin.email-templates.edit', $emailTemplate) }}" class="kt-btn kt-btn-warning me-2">
                    <i class="fas fa-edit"></i> Bewerken
                </a>
                <a href="{{ route('admin.email-templates.index') }}" class="kt-btn kt-btn-outline">
                    <i class="fas fa-arrow-left"></i> Terug naar Overzicht
                </a>
            </div>
        </div>
        <div class="kt-card-content">
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
                    <kt-table class="info-kt-table">
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
                                <span class="kt-badge kt-badge-{{ $emailTemplate->is_active ? 'success' : 'secondary' }}">
                                    {{ $emailTemplate->is_active ? 'Actief' : 'Inactief' }}
                                </span>
                            </td>
                        </tr>
                    </kt-table>
                </div>
                
                <div class="info-section">
                    <h6 class="section-title">
                        <i class="fas fa-cog"></i>
                        Systeem Informatie
                    </h6>
                    <kt-table class="info-kt-table">
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
                    </kt-table>
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
