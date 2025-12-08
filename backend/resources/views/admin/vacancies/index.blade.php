@extends('admin.layouts.app')

@section('title', 'Vacatures Beheer')

@section('content')


<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono mb-3">
                Vacatures Beheer
            </h1>
        </div>
        <div class="flex items-center gap-2.5">
            <a href="{{ route('admin.vacancies.create') }}" class="kt-btn kt-btn-primary">
                <i class="ki-filled ki-plus me-2"></i>
                Nieuw
            </a>
        </div>
    </div>
    <div class="grid gap-5 lg:gap-7.5">
        <div class="w-full">
            <!-- Status Statistieken -->
            <div class="stats-cards">
                <div class="stat-card">
                    <div class="stat-number" style="background: linear-gradient(135deg, #4caf50 0%, #66bb6a 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">{{ $statusStats['Open'] ?? 0 }}</div>
                    <div class="stat-label">Open</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" style="background: linear-gradient(135deg, #f44336 0%, #ef5350 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">{{ $statusStats['Gesloten'] ?? 0 }}</div>
                    <div class="stat-label">Gesloten</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" style="background: linear-gradient(135deg, #ff9800 0%, #ffb74d 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">{{ $statusStats['In behandeling'] ?? 0 }}</div>
                    <div class="stat-label">In behandeling</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" style="background: linear-gradient(135deg, #9c27b0 0%, #ba68c8 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">{{ $vacancies->total() }}</div>
                    <div class="stat-label">Totaal</div>
                </div>
            </div>

            <div class="kt-card">
                <!-- Header -->
                <div class="kt-card-header flex justify-between items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-briefcase me-2"></i> Vacatures Beheer
                    </h5>
                    <div class="flex gap-2">
                        @can('create-vacancies')
                            <a href="{{ route('admin.vacancies.create') }}" class="kt-btn kt-btn-primary">
                                <i class="fas fa-plus me-2"></i> Nieuwe Vacature
                            </a>
                        @endcan
                    </div>
                </div>

                <!-- Success Message -->
                @if(session('success'))
                    <div class="kt-alert kt-alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        {{ session('success') }}
                        <button type="button" class="kt-btn kt-btn-sm kt-btn-icon" data-kt-dismiss="alert"></button>
                    </div>
                @endif

                <!-- Filters -->
                <div class="filters-section">
                        <form method="GET" action="{{ route('admin.vacancies.index') }}" id="filters-form">
                            <div class="grid gap-5 lg:gap-7.5">
                                @if(auth()->user()->hasRole('super-admin'))
                                    <!-- Super-admin: 5 kolommen over gehele breedte -->
                                    <div class="lg:col-span-2">
                                        <div class="filter-group">
                                            <label class="filter-label">Status</label>
                                            <select name="status" class="filter-select" onchange="this.form.submit()">
                                                <option value="">Alle statussen</option>
                                                <option value="Open" {{ request('status') == 'Open' ? 'selected' : '' }}>Open</option>
                                                <option value="Gesloten" {{ request('status') == 'Gesloten' ? 'selected' : '' }}>Gesloten</option>
                                                <option value="In behandeling" {{ request('status') == 'In behandeling' ? 'selected' : '' }}>In behandeling</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="lg:col-span-2">
                                        <div class="filter-group">
                                            <label class="filter-label">Branch</label>
                                            <select name="branch_id" class="filter-select" onchange="this.form.submit()">
                                                <option value="">Alle branches</option>
                                                @foreach($branches ?? [] as $branch)
                                                    <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>
                                                        {{ $branch->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="lg:col-span-2">
                                        <div class="filter-group">
                                            <label class="filter-label">Bedrijf</label>
                                            <select name="company_id" class="filter-select" onchange="this.form.submit()">
                                                <option value="">Alle bedrijven</option>
                                                @foreach($companies ?? [] as $company)
                                                    <option value="{{ $company->id }}" {{ request('company_id') == $company->id ? 'selected' : '' }}>
                                                        {{ $company->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="lg:col-span-2">
                                        <div class="filter-group">
                                            <label class="filter-label">Items per pagina</label>
                                            <select name="per_page" class="filter-select" onchange="this.form.submit()">
                                                <option value="5" {{ request('per_page', 5) == 5 ? 'selected' : '' }}>5</option>
                                                <option value="15" {{ request('per_page', 5) == 15 ? 'selected' : '' }}>15</option>
                                                <option value="25" {{ request('per_page', 5) == 25 ? 'selected' : '' }}>25</option>
                                                <option value="50" {{ request('per_page', 5) == 50 ? 'selected' : '' }}>50</option>
                                                <option value="100" {{ request('per_page', 5) == 100 ? 'selected' : '' }}>100</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="lg:col-span-2">
                                        <div class="filter-group">
                                            <label class="filter-label">&nbsp;</label>
                                            <a href="{{ route('admin.vacancies.index') }}" class="btn btn-outline-secondary w-100" style="height: 36px; display: flex; align-items: center; justify-content: center; gap: 6px; text-decoration: none;">
                                                <i class="fas fa-times"></i>
                                                Filter wissen
                                            </a>
                                        </div>
                                    </div>
                                @else
                                    <!-- Non-super-admin: 4 kolommen over gehele breedte -->
                                    <div class="lg:col-span-3">
                                        <div class="filter-group">
                                            <label class="filter-label">Status</label>
                                            <select name="status" class="filter-select" onchange="this.form.submit()">
                                                <option value="">Alle statussen</option>
                                                <option value="Open" {{ request('status') == 'Open' ? 'selected' : '' }}>Open</option>
                                                <option value="Gesloten" {{ request('status') == 'Gesloten' ? 'selected' : '' }}>Gesloten</option>
                                                <option value="In behandeling" {{ request('status') == 'In behandeling' ? 'selected' : '' }}>In behandeling</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="lg:col-span-3">
                                        <div class="filter-group">
                                            <label class="filter-label">Branch</label>
                                            <select name="branch_id" class="filter-select" onchange="this.form.submit()">
                                                <option value="">Alle branches</option>
                                                @foreach($branches ?? [] as $branch)
                                                    <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>
                                                        {{ $branch->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="lg:col-span-3">
                                        <div class="filter-group">
                                            <label class="filter-label">Items per pagina</label>
                                            <select name="per_page" class="filter-select" onchange="this.form.submit()">
                                                <option value="5" {{ request('per_page', 5) == 5 ? 'selected' : '' }}>5</option>
                                                <option value="15" {{ request('per_page', 5) == 15 ? 'selected' : '' }}>15</option>
                                                <option value="25" {{ request('per_page', 5) == 25 ? 'selected' : '' }}>25</option>
                                                <option value="50" {{ request('per_page', 5) == 50 ? 'selected' : '' }}>50</option>
                                                <option value="100" {{ request('per_page', 5) == 100 ? 'selected' : '' }}>100</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="lg:col-span-3">
                                        <div class="filter-group">
                                            <label class="filter-label">&nbsp;</label>
                                            <a href="{{ route('admin.vacancies.index') }}" class="btn btn-outline-secondary w-100" style="height: 36px; display: flex; align-items: center; justify-content: center; gap: 6px; text-decoration: none;">
                                                <i class="fas fa-times"></i>
                                                Filter wissen
                                            </a>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </form>
                    </div>

                <!-- Table -->
                <div class="kt-card-content">
                    <div class="kt-table-responsive" style="width: 100%;">
                        <kt-table class="material-kt-table" style="width: 100%;">
                            <thead>
                                <tr>
                                    <th class="sorkt-table" data-sort="id">ID</th>
                                    <th class="sorkt-table highlight" data-sort="title">Titel & Locatie</th>
                                    <th class="sorkt-table" data-sort="company_id">Bedrijf</th>
                                    <th class="sorkt-table" data-sort="branch_id">Branch</th>
                                    <th class="sorkt-table" data-sort="status">Status</th>
                                    <th>Type</th>
                                    <th>Matches</th>
                                    <th class="sorkt-table" data-sort="publication_date">Publicatiedatum</th>
                                    <th>SEO</th>
                                    <th>Acties</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($vacancies as $vacancy)
                                    <tr>
                                        <td>{{ $vacancy->id }}</td>
                                        <td>
                                            <div class="vacancy-info">
                                                <div class="vacancy-title">{{ $vacancy->title }}</div>
                                                @if($vacancy->location)
                                                    <div class="vacancy-location">
                                                        <i class="fas fa-map-marker-alt"></i>{{ $vacancy->location }}
                                                    </div>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            @if($vacancy->company)
                                                <span class="vacancy-company">{{ $vacancy->company->name }}</span>
                                            @else
                                                <span class="text-muted">Geen bedrijf</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($vacancy->branch)
                                                <span class="vacancy-branch">{{ $vacancy->branch->name }}</span>
                                            @else
                                                <span class="text-muted-foreground">Geen branch</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.matches.index', ['vacancy' => $vacancy->id]) }}" 
                                               class="kt-badge kt-badge-info hover:kt-badge-primary">
                                                <i class="ki-filled ki-abstract-38 me-1"></i>
                                                {{ $vacancy->matches_count ?? 0 }} matches
                                            </a>
                                        </td>
                                        <td>
                                            @switch($vacancy->status)
                                                @case('Open')
                                                    <span class="status-badge status-open">Open</span>
                                                    @break
                                                @case('Gesloten')
                                                    <span class="status-badge status-closed">Gesloten</span>
                                                    @break
                                                @case('In behandeling')
                                                    <span class="status-badge status-processing">In behandeling</span>
                                                    @break
                                                @default
                                                    <span class="status-badge status-open">{{ $vacancy->status }}</span>
                                            @endswitch
                                        </td>
                                        <td>
                                            <span class="vacancy-type">{{ $vacancy->employment_type ?? 'Volledig' }}</span>
                                        </td>
                                        <td>
                                            <div class="date-info">
                                                <div>{{ $vacancy->publication_date?->format('d-m-Y') ?? 'Niet gepubliceerd' }}</div>
                                                <small>{{ $vacancy->publication_date?->format('H:i') ?? '' }}</small>
                                            </div>
                                        </td>
                                        <td>
                                            @php
                                                $seoScore = 0;
                                                if ($vacancy->meta_title) $seoScore++;
                                                if ($vacancy->meta_description) $seoScore++;
                                                if ($vacancy->meta_keywords) $seoScore++;
                                                if ($vacancy->description && strlen($vacancy->description) > 100) $seoScore++;
                                                
                                                $seoClass = $seoScore >= 3 ? 'seo-good' : ($seoScore >= 2 ? 'seo-warning' : 'seo-bad');
                                                $seoText = $seoScore >= 3 ? 'Goed' : ($seoScore >= 2 ? 'Gemiddeld' : 'Slecht');
                                            @endphp
                                            <span class="seo-indicator {{ $seoClass }}"></span>
                                            <small>{{ $seoText }}</small>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="{{ route('admin.vacancies.show', $vacancy) }}" class="action-btn action-btn-info" title="Bekijken">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                @can('edit-vacancies')
                                                    <a href="{{ route('admin.vacancies.edit', $vacancy) }}" class="action-btn action-btn-warning" title="Bewerken">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                @endcan
                                                @if($vacancy->status !== 'Open' && $vacancy->status !== 'In behandeling')
                                                    <form action="{{ route('admin.vacancies.update', $vacancy) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        @method('PUT')
                                                        <input type="hidden" name="status" value="Open">
                                                        <input type="hidden" name="title" value="{{ $vacancy->title }}">
                                                        <input type="hidden" name="company_id" value="{{ $vacancy->company_id }}">
                                                        <input type="hidden" name="description" value="{{ $vacancy->description }}">
                                                        <button type="submit" class="action-btn action-btn-success" title="Openen">
                                                            <i class="fas fa-play"></i>
                                                        </button>
                                                    </form>
                                                @elseif($vacancy->status === 'In behandeling')
                                                    <form action="{{ route('admin.vacancies.update', $vacancy) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        @method('PUT')
                                                        <input type="hidden" name="status" value="Open">
                                                        <input type="hidden" name="title" value="{{ $vacancy->title }}">
                                                        <input type="hidden" name="company_id" value="{{ $vacancy->company_id }}">
                                                        <input type="hidden" name="description" value="{{ $vacancy->description }}">
                                                        <button type="submit" class="action-btn action-btn-success" title="Openen">
                                                            <i class="fas fa-play"></i>
                                                        </button>
                                                    </form>
                                                    <form action="{{ route('admin.vacancies.update', $vacancy) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        @method('PUT')
                                                        <input type="hidden" name="status" value="Gesloten">
                                                        <input type="hidden" name="title" value="{{ $vacancy->title }}">
                                                        <input type="hidden" name="company_id" value="{{ $vacancy->company_id }}">
                                                        <input type="hidden" name="description" value="{{ $vacancy->description }}">
                                                        <button type="submit" class="action-btn action-btn-danger" title="Sluiten">
                                                            <i class="fas fa-stop"></i>
                                                        </button>
                                                    </form>
                                                @else
                                                    <form action="{{ route('admin.vacancies.update', $vacancy) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        @method('PUT')
                                                        <input type="hidden" name="status" value="In behandeling">
                                                        <input type="hidden" name="title" value="{{ $vacancy->title }}">
                                                        <input type="hidden" name="company_id" value="{{ $vacancy->company_id }}">
                                                        <input type="hidden" name="description" value="{{ $vacancy->description }}">
                                                        <button type="submit" class="action-btn action-btn-warning" title="In behandeling">
                                                            <i class="fas fa-clock"></i>
                                                        </button>
                                                    </form>
                                                    <form action="{{ route('admin.vacancies.update', $vacancy) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        @method('PUT')
                                                        <input type="hidden" name="status" value="Gesloten">
                                                        <input type="hidden" name="title" value="{{ $vacancy->title }}">
                                                        <input type="hidden" name="company_id" value="{{ $vacancy->company_id }}">
                                                        <input type="hidden" name="description" value="{{ $vacancy->description }}">
                                                        <button type="submit" class="action-btn action-btn-danger" title="Sluiten">
                                                            <i class="fas fa-stop"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                                @can('delete-vacancies')
                                                    <form action="{{ route('admin.vacancies.destroy', $vacancy) }}" method="POST" class="d-inline" onsubmit="return confirm('Weet je zeker dat je deze vacature wilt verwijderen?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="action-btn action-btn-danger" title="Verwijderen">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9">
                                            <div class="empty-state">
                                                <i class="fas fa-briefcase"></i>
                                                <h5>Nog geen vacatures</h5>
                                                <p>Er zijn nog geen vacatures aangemaakt.</p>
                                                @can('create-vacancies')
                                                    <a href="{{ route('admin.vacancies.create') }}" class="kt-btn kt-btn-primary">
                                                        <i class="fas fa-plus me-2"></i> Eerste Vacature Aanmaken
                                                    </a>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </kt-table>
                    </div>

                    <!-- Results Info -->
                    <div class="results-info-wrapper">
                        <div class="results-info">
                            <span class="results-text">
                                <i class="fas fa-info-circle me-2"></i>
                                Toon {{ $vacancies->firstItem() ?? 0 }} tot {{ $vacancies->lastItem() ?? 0 }} van {{ $vacancies->total() }} resultaten
                            </span>
                        </div>
                    </div>

                    <!-- Pagination -->
                    @if($vacancies->hasPages())
                        <div class="pagination-wrapper">
                            <nav aria-label="Paginering">
                                <ul class="pagination">
                                    {{-- Previous Page Link --}}
                                    @if ($vacancies->onFirstPage())
                                        <li class="page-item disabled">
                                            <span class="page-link">
                                                <i class="fas fa-chevron-left"></i>
                                            </span>
                                        </li>
                                    @else
                                        <li class="page-item">
                                            <a class="page-link" href="{{ $vacancies->previousPageUrl() }}">
                                                <i class="fas fa-chevron-left"></i>
                                            </a>
                                        </li>
                                    @endif

                                    {{-- Pagination Elements --}}
                                    @php
                                        $currentPage = $vacancies->currentPage();
                                        $lastPage = $vacancies->lastPage();
                                        $startPage = max(1, $currentPage - 2);
                                        $endPage = min($lastPage, $currentPage + 2);
                                        
                                        if ($endPage - $startPage < 4) {
                                            if ($startPage == 1) {
                                                $endPage = min($lastPage, $startPage + 4);
                                            } else {
                                                $startPage = max(1, $endPage - 4);
                                            }
                                        }
                                    @endphp
                                    
                                    {{-- First page if not in range --}}
                                    @if($startPage > 1)
                                        <li class="page-item">
                                            <a class="page-link" href="{{ $vacancies->url(1) }}">1</a>
                                        </li>
                                        @if($startPage > 2)
                                            <li class="page-item disabled">
                                                <span class="page-link">...</span>
                                            </li>
                                        @endif
                                    @endif
                                    
                                    {{-- Page range --}}
                                    @for($page = $startPage; $page <= $endPage; $page++)
                                        @if ($page == $currentPage)
                                            <li class="page-item active">
                                                <span class="page-link">{{ $page }}</span>
                                            </li>
                                        @else
                                            <li class="page-item">
                                                <a class="page-link" href="{{ $vacancies->url($page) }}">{{ $page }}</a>
                                            </li>
                                        @endif
                                    @endfor
                                    
                                    {{-- Last page if not in range --}}
                                    @if($endPage < $lastPage)
                                        @if($endPage < $lastPage - 1)
                                            <li class="page-item disabled">
                                                <span class="page-link">...</span>
                                            </li>
                                        @endif
                                        <li class="page-item">
                                            <a class="page-link" href="{{ $vacancies->url($lastPage) }}">{{ $lastPage }}</a>
                                        </li>
                                    @endif

                                    {{-- Next Page Link --}}
                                    @if ($vacancies->hasMorePages())
                                        <li class="page-item">
                                            <a class="page-link" href="{{ $vacancies->nextPageUrl() }}">
                                                <i class="fas fa-chevron-right"></i>
                                            </a>
                                        </li>
                                    @else
                                        <li class="page-item disabled">
                                            <span class="page-link">
                                                <i class="fas fa-chevron-right"></i>
                                            </span>
                                        </li>
                                    @endif
                                </ul>
                            </nav>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Sortering functionaliteit
    const sorkt-tableHeaders = document.querySelectorAll('.sorkt-table');
    
    sorkt-tableHeaders.forEach(header => {
        header.addEventListener('click', function() {
            const sortBy = this.dataset.sort;
            const currentSortBy = new URLSearchParams(window.location.search).get('sort_by');
            const currentSortOrder = new URLSearchParams(window.location.search).get('sort_order');
            
            let newSortOrder = 'desc';
            if (currentSortBy === sortBy && currentSortOrder === 'desc') {
                newSortOrder = 'asc';
            }
            
            const url = new URL(window.location);
            url.searchParams.set('sort_by', sortBy);
            url.searchParams.set('sort_order', newSortOrder);
            
            window.location.href = url.toString();
        });
    });
    
    // Huidige sortering markeren
    const currentSortBy = new URLSearchParams(window.location.search).get('sort_by');
    const currentSortOrder = new URLSearchParams(window.location.search).get('sort_order');
    
    if (currentSortBy) {
        const header = document.querySelector(`[data-sort="${currentSortBy}"]`);
        if (header) {
            header.classList.add(currentSortOrder === 'asc' ? 'sort-asc' : 'sort-desc');
        }
    }
    
    // Material Design ripple effect voor buttons
    const buttons = document.querySelectorAll(', 600);
        });
    });
});
</script>

<style>
.ripple {
    position: absolute;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.3);
    transform: scale(0);
    animation: ripple-animation 0.6s linear;
    pointer-events: none;
}

@keyframes ripple-animation {
    to {
        transform: scale(4);
        opacity: 0;
    }
}
</style>
@endsection
