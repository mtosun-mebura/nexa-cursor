@extends('admin.layouts.app')

@section('title', 'Vacature Details - ' . $vacancy->title)

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
                <i class="fas fa-briefcase"></i> Vacature Details
            </h5>
            <div class="material-header-actions">
                <a href="{{ route('admin.vacancies.edit', $vacancy) }}" class="kt-btn kt-btn-warning">
                    <i class="fas fa-edit"></i> Bewerken
                </a>
                @if($vacancy->status !== 'Open' && $vacancy->status !== 'In behandeling')
                    <form action="{{ route('admin.vacancies.update', $vacancy) }}" method="POST" class="d-inline">
                        @csrf @method('PUT')
                        <input type="hidden" name="status" value="Open">
                        <input type="hidden" name="title" value="{{ $vacancy->title }}">
                        <input type="hidden" name="company_id" value="{{ $vacancy->company_id }}">
                        <input type="hidden" name="description" value="{{ $vacancy->description }}">
                        <button type="submit" class="kt-btn kt-btn-success">
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
                        <button type="submit" class="kt-btn kt-btn-success">
                            <i class="fas fa-play"></i> Openen
                        </button>
                    </form>
                    <form action="{{ route('admin.vacancies.update', $vacancy) }}" method="POST" class="d-inline">
                        @csrf @method('PUT')
                        <input type="hidden" name="status" value="Gesloten">
                        <input type="hidden" name="title" value="{{ $vacancy->title }}">
                        <input type="hidden" name="company_id" value="{{ $vacancy->company_id }}">
                        <input type="hidden" name="description" value="{{ $vacancy->description }}">
                        <button type="submit" class="kt-btn kt-btn-danger">
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
                        <button type="submit" class="kt-btn kt-btn-warning">
                            <i class="fas fa-clock"></i> In behandeling
                        </button>
                    </form>
                    <form action="{{ route('admin.vacancies.update', $vacancy) }}" method="POST" class="d-inline">
                        @csrf @method('PUT')
                        <input type="hidden" name="status" value="Gesloten">
                        <input type="hidden" name="title" value="{{ $vacancy->title }}">
                        <input type="hidden" name="company_id" value="{{ $vacancy->company_id }}">
                        <input type="hidden" name="description" value="{{ $vacancy->description }}">
                        <button type="submit" class="kt-btn kt-btn-danger">
                            <i class="fas fa-stop"></i> Sluiten
                        </button>
                    </form>
                @endif
                <a href="{{ route('admin.vacancies.index') }}" class="kt-btn kt-btn-outline">
                    <i class="fas fa-arrow-left"></i> Terug naar Overzicht
                </a>
            </div>
        </div>
        
        <div class="kt-card-content">
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
                    <kt-table class="info-kt-table">
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
                            <td>{{ $vacancy->branch->name ?? 'Geen branch' }}</td>
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
                    </kt-table>
                </div>

                <!-- Salaris & Details -->
                <div class="info-section">
                    <h6 class="section-title">
                        <i class="fas fa-euro-sign"></i> Salaris & Details
                    </h6>
                    <kt-table class="info-kt-table">
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
                    </kt-table>
                </div>

                <!-- Datums -->
                <div class="info-section">
                    <h6 class="section-title">
                        <i class="fas fa-calendar-alt"></i> Datums
                    </h6>
                    <kt-table class="info-kt-table">
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
                    </kt-table>
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
                <a href="{{ route('admin.vacancies.edit', $vacancy) }}" class="kt-btn kt-btn-warning">
                    <i class="fas fa-edit"></i> Vacature Bewerken
                </a>
                <a href="{{ route('admin.vacancies.index') }}" class="kt-btn kt-btn-outline">
                    <i class="fas fa-list"></i> Terug naar Overzicht
                </a>
                <form action="{{ route('admin.vacancies.destroy', $vacancy) }}" method="POST" class="d-inline" onsubmit="return confirm('Weet je zeker dat je deze vacature wilt verwijderen?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="kt-btn kt-btn-danger">
                        <i class="fas fa-trash"></i> Vacature Verwijderen
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
