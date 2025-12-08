@extends('admin.layouts.app')

@section('title', 'Categorie Details - ' . $category->name)

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
                <i class="fas fa-tags"></i>
                Categorie Details: {{ $category->name }}
            </h5>
            <div class="material-header-actions">
                <a href="{{ route('admin.categories.edit', $category) }}" class="kt-btn kt-btn-warning me-2">
                    <i class="fas fa-edit"></i> Bewerken
                </a>
                <a href="{{ route('admin.categories.index') }}" class="kt-btn kt-btn-outline">
                    <i class="fas fa-arrow-left"></i> Terug naar Overzicht
                </a>
            </div>
        </div>
        <div class="kt-card-content">
            <!-- Category Header Section -->
            <div class="category-header">
                <h1 class="category-title">{{ $category->name }}</h1>
                <div class="category-meta">
                    <div class="meta-item">
                        <i class="fas fa-tag"></i>
                        <span>{{ $category->name }}</span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-sort-numeric-up"></i>
                        <span>Volgorde: {{ $category->sort_order }}</span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-palette"></i>
                        <span>Kleur: {{ $category->color }}</span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-calendar"></i>
                        <span>Aangemaakt: {{ $category->created_at->format('d-m-Y') }}</span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-briefcase"></i>
                        <span>{{ $category->vacancies->count() }} vacatures</span>
                    </div>
                </div>
                <div class="category-status status-{{ $category->is_active ? 'active' : 'inactive' }}">
                    <i class="fas fa-circle"></i>
                    {{ $category->is_active ? 'Actief' : 'Inactief' }}
                </div>
            </div>

            <div class="info-grid">
                <div class="info-section">
                    <h6 class="section-title">
                        <i class="fas fa-info-circle"></i>
                        Categorie Informatie
                    </h6>
                    <kt-table class="info-kt-table">
                        <tr>
                            <td>ID</td>
                            <td>{{ $category->id }}</td>
                        </tr>
                        <tr>
                            <td>Naam</td>
                            <td>{{ $category->name }}</td>
                        </tr>
                        <tr>
                            <td>Slug</td>
                            <td><code>{{ $category->slug }}</code></td>
                        </tr>
                        <tr>
                            <td>Status</td>
                            <td>
                                <span class="kt-badge kt-badge-{{ $category->is_active ? 'success' : 'secondary' }}">
                                    {{ $category->is_active ? 'Actief' : 'Inactief' }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td>Sorteervolgorde</td>
                            <td>{{ $category->sort_order ?? 'Niet ingesteld' }}</td>
                        </tr>
                    </kt-table>
                </div>
                
                <div class="info-section">
                    <h6 class="section-title">
                        <i class="fas fa-cog"></i>
                        Weergave Instellingen
                    </h6>
                    <kt-table class="info-kt-table">
                        <tr>
                            <td>Icoon</td>
                            <td>
                                @if($category->icon)
                                    <i class="{{ $category->icon }}"></i> {{ $category->icon }}
                                @else
                                    <span class="material-text-muted">Geen icoon</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td>Kleur</td>
                            <td>
                                @if($category->color)
                                    <div class="d-flex align-items-center">
                                        <div class="me-2 rounded" style="background-color: {{ $category->color }}; width: 20px; height: 20px;"></div>
                                        {{ $category->color }}
                                    </div>
                                @else
                                    <span class="material-text-muted">Geen kleur ingesteld</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td>Aangemaakt op</td>
                            <td>{{ $category->created_at->format('d-m-Y H:i') }}</td>
                        </tr>
                        <tr>
                            <td>Laatst bijgewerkt</td>
                            <td>{{ $category->updated_at->format('d-m-Y H:i') }}</td>
                        </tr>
                    </kt-table>
                </div>
            </div>

            @if($category->description)
                <div class="info-section">
                    <h6 class="section-title">
                        <i class="fas fa-align-left"></i>
                        Beschrijving
                    </h6>
                    <div class="p-3 bg-light rounded">
                        {{ $category->description }}
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
