@extends('admin.layouts.app')

@section('title', 'Categorie Details')

@section('content')
<style>
    :root {
        --primary-color: #ff9800;
        --primary-light: #ffb74d;
        --primary-dark: #f57c00;
        --primary-hover: #ff8f00;
    }
</style>

@include('admin.material-design-template')


<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="material-card">
                <div class="card-header">
                    <h5 >
                        <i class="fas fa-tags"></i> Categorie Details
                    </h5>
                    <div>
                        <a href="{{ route('admin.categories.edit', $category) }}" class="btn btn-warning me-2">
                            <i class="fas fa-edit"></i> Bewerken
                        </a>
                        <a href="{{ route('admin.categories.index') }}" class="material-btn material-btn-secondary">
                            <i class="fas fa-arrow-left"></i> Terug naar Overzicht
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <h4>
                                @if($category->icon)
                                    <i class="{{ $category->icon }}" style="color: {{ $category->color ?? '#007bff' }}"></i>
                                @endif
                                {{ $category->name }}
                            </h4>
                            <p class="material-section-title">
                                <span class="badge bg-{{ $category->is_active ? 'success' : 'secondary' }}">
                                    {{ $category->is_active ? 'Actief' : 'Inactief' }}
                                </span>
                                @if($category->sort_order)
                                    <span class="ms-2">Volgorde: {{ $category->sort_order }}</span>
                                @endif
                            </p>
                        </div>
                        <div class="col-md-4 text-end">
                            @if($category->color)
                                <div class="d-inline-block p-3 rounded" style="background-color: {{ $category->color }}; width: 60px; height: 60px;"></div>
                            @endif
                        </div>
                    </div>

                    <hr class="material-divider">

                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="material-section-title">Categorie Informatie</h6>
                            <table class="material-info-table">
                                <tr>
                                    <td width="150"><strong>ID:</strong></td>
                                    <td>{{ $category->id }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Naam:</strong></td>
                                    <td>{{ $category->name }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Slug:</strong></td>
                                    <td><code>{{ $category->slug }}</code></td>
                                </tr>
                                <tr>
                                    <td><strong>Status:</strong></td>
                                    <td>
                                        <span class="badge bg-{{ $category->is_active ? 'success' : 'secondary' }}">
                                            {{ $category->is_active ? 'Actief' : 'Inactief' }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Sorteervolgorde:</strong></td>
                                    <td>{{ $category->sort_order ?? 'Niet ingesteld' }}</td>
                                </tr>
                            </table>
                        </div>
                        
                        <div class="col-md-6">
                            <h6 class="material-section-title">Weergave Instellingen</h6>
                            <table class="material-info-table">
                                <tr>
                                    <td width="150"><strong>Icoon:</strong></td>
                                    <td>
                                        @if($category->icon)
                                            <i class="{{ $category->icon }}"></i> {{ $category->icon }}
                                        @else
                                            <span class="material-text-muted">Geen icoon</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Kleur:</strong></td>
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
                                    <td><strong>Aangemaakt:</strong></td>
                                    <td>{{ $category->created_at->format('d-m-Y H:i') }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Bijgewerkt:</strong></td>
                                    <td>{{ $category->updated_at->format('d-m-Y H:i') }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    @if($category->description)
                        <hr class="material-divider">
                        <div class="row">
                            <div class="col-md-12">
                                <h6 class="material-section-title">Beschrijving</h6>
                                <div class="material-card">
                                    <div class="card-body">
                                        {!! nl2br(e($category->description)) !!}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <hr class="material-divider">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="material-section-title">Statistieken</h6>
                            <table class="material-info-table">
                                <tr>
                                    <td width="150"><strong>Aantal vacatures:</strong></td>
                                    <td>{{ $category->vacancies->count() }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    @if($category->vacancies->isNotEmpty())
                        <hr class="material-divider">
                        <div class="row">
                            <div class="col-12">
                                <h6 class="material-section-title">Vacatures in deze categorie</h6>
                                <div class="table-responsive">
                                    <table class="material-table">
                                        <thead>
                                            <tr>
                                                <th>Titel</th>
                                                <th>Bedrijf</th>
                                                <th>Status</th>
                                                <th>Aangemaakt</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($category->vacancies as $vacancy)
                                                <tr>
                                                    <td>{{ $vacancy->title }}</td>
                                                    <td>{{ $vacancy->company->name ?? 'Geen bedrijf' }}</td>
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
