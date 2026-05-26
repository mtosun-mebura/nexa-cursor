@extends('admin.layouts.app')

@section('title', 'Kandidaten')

@section('content')


<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono mb-3">
                Kandidaten
            </h1>
        </div>
        <div class="flex items-center gap-2.5">
            <a href="{{ route(\'admin.\' . str_replace(\'admin.\', \'\', request()->route()->getName()) . \'.create\') }}" class="kt-btn kt-btn-primary">
                <i class="ki-filled ki-plus me-2"></i>
                Nieuw
            </a>
        </div>
    </div>
    <div class="grid gap-5 lg:gap-7.5">
        <div class="w-full">
            <!-- Dashboard Statistieken -->
            <div class="stats-cards">
                <div class="stat-card">
                    <div class="stat-number" style="background: linear-gradient(135deg, #4caf50 0%, #66bb6a 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">{{ $stats['active_candidates'] }}</div>
                    <div class="stat-label">ACTIEF</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" style="background: linear-gradient(135deg, #f44336 0%, #ef5350 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">{{ $stats['rejected_candidates'] }}</div>
                    <div class="stat-label">INACTIEF</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" style="background: linear-gradient(135deg, #ff9800 0%, #ffb74d 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">{{ $stats['pending_candidates'] }}</div>
                    <div class="stat-label">TEST MODUS</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" style="background: linear-gradient(135deg, #1976d2 0%, #42a5f5 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">{{ $stats['total_candidates'] }}</div>
                    <div class="stat-label">TOTAAL</div>
                </div>
            </div>

            <div class="kt-card">
                <div class="kt-card-header flex justify-between items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-user-graduate me-2"></i> Kandidaten Overzicht
                    </h5>
                    <a href="{{ route('admin.candidates.create') }}" class="kt-btn kt-btn-primary">
                        <i class="fas fa-plus me-2"></i> NIEUWE KANDIDAAT
                    </a>
                </div>
                <div class="kt-card-content">
                    @if(session('success'))
                        <div class="kt-alert kt-alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            {{ session('success') }}
                            <button type="button" class="kt-btn kt-btn-sm kt-btn-icon" data-kt-dismiss="alert"></button>
                        </div>
                    @endif

                    <!-- Filters -->
                    <div class="filters-section">
                        <form method="GET" action="{{ route('admin.candidates.index') }}" id="filters-form">
                            <div class="grid gap-5 lg:gap-7.5">
                                @if(auth()->user()->hasRole('super-admin'))
                                    <!-- Super-admin: 5 kolommen over gehele breedte -->
                                    <div class="lg:col-span-2">
                                        <div class="filter-group">
                                            <label class="filter-label">Status</label>
                                            <select name="status" class="filter-select" onchange="this.form.submit()">
                                                <option value="">Alle statussen</option>
                                                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Actief</option>
                                                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>In Afwachting</option>
                                                <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Afgewezen</option>
                                                <option value="hired" {{ request('status') == 'hired' ? 'selected' : '' }}>Aangenomen</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="lg:col-span-2">
                                        <div class="filter-group">
                                            <label class="filter-label">Ervaring</label>
                                            <select name="experience" class="filter-select" onchange="this.form.submit()">
                                                <option value="">Alle niveaus</option>
                                                <option value="1" {{ request('experience') == '1' ? 'selected' : '' }}>1+ jaar</option>
                                                <option value="3" {{ request('experience') == '3' ? 'selected' : '' }}>3+ jaar</option>
                                                <option value="5" {{ request('experience') == '5' ? 'selected' : '' }}>5+ jaar</option>
                                                <option value="7" {{ request('experience') == '7' ? 'selected' : '' }}>7+ jaar</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="lg:col-span-2">
                                        <div class="filter-group">
                                            <label class="filter-label">Opleiding</label>
                                            <select name="education" class="filter-select" onchange="this.form.submit()">
                                                <option value="">Alle niveaus</option>
                                                <option value="high_school" {{ request('education') == 'high_school' ? 'selected' : '' }}>Middelbare School</option>
                                                <option value="vocational" {{ request('education') == 'vocational' ? 'selected' : '' }}>MBO</option>
                                                <option value="bachelor" {{ request('education') == 'bachelor' ? 'selected' : '' }}>HBO/Bachelor</option>
                                                <option value="master" {{ request('education') == 'master' ? 'selected' : '' }}>WO/Master</option>
                                                <option value="phd" {{ request('education') == 'phd' ? 'selected' : '' }}>PhD/Doctoraat</option>
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
                                            <a href="{{ route('admin.candidates.index') }}" class="btn btn-outline-secondary w-100" style="height: 36px; display: flex; align-items: center; justify-content: center; gap: 6px; text-decoration: none;">
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
                                                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Actief</option>
                                                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>In Afwachting</option>
                                                <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Afgewezen</option>
                                                <option value="hired" {{ request('status') == 'hired' ? 'selected' : '' }}>Aangenomen</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="lg:col-span-3">
                                        <div class="filter-group">
                                            <label class="filter-label">Ervaring</label>
                                            <select name="experience" class="filter-select" onchange="this.form.submit()">
                                                <option value="">Alle niveaus</option>
                                                <option value="1" {{ request('experience') == '1' ? 'selected' : '' }}>1+ jaar</option>
                                                <option value="3" {{ request('experience') == '3' ? 'selected' : '' }}>3+ jaar</option>
                                                <option value="5" {{ request('experience') == '5' ? 'selected' : '' }}>5+ jaar</option>
                                                <option value="7" {{ request('experience') == '7' ? 'selected' : '' }}>7+ jaar</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="lg:col-span-3">
                                        <div class="filter-group">
                                            <label class="filter-label">Opleiding</label>
                                            <select name="education" class="filter-select" onchange="this.form.submit()">
                                                <option value="">Alle niveaus</option>
                                                <option value="high_school" {{ request('education') == 'high_school' ? 'selected' : '' }}>Middelbare School</option>
                                                <option value="vocational" {{ request('education') == 'vocational' ? 'selected' : '' }}>MBO</option>
                                                <option value="bachelor" {{ request('education') == 'bachelor' ? 'selected' : '' }}>HBO/Bachelor</option>
                                                <option value="master" {{ request('education') == 'master' ? 'selected' : '' }}>WO/Master</option>
                                                <option value="phd" {{ request('education') == 'phd' ? 'selected' : '' }}>PhD/Doctoraat</option>
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
                                @endif
                            </div>
                        </form>
                    </div>

                    @if($candidates->count() > 0)
                    <div class="kt-table-responsive">
                        <kt-table class="kt-table material-kt-kt-table">
                            <thead>
                                <tr>
                                    <th>
                                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'id', 'order' => request('sort') == 'id' && request('order') == 'asc' ? 'desc' : 'asc']) }}" class="sorkt-table-header">
                                            ID
                                            @if(request('sort') == 'id')
                                                <i class="fas fa-sort-{{ request('order') == 'asc' ? 'up' : 'down' }}"></i>
                                            @else
                                                <i class="fas fa-sort"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th>
                                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'first_name', 'order' => request('sort') == 'first_name' && request('order') == 'asc' ? 'desc' : 'asc']) }}" class="sorkt-table-header">
                                            NAAM & BESCHRIJVING
                                            @if(request('sort') == 'first_name')
                                                <i class="fas fa-sort-{{ request('order') == 'asc' ? 'up' : 'down' }}"></i>
                                            @else
                                                <i class="fas fa-sort"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th>
                                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'experience_years', 'order' => request('sort') == 'experience_years' && request('order') == 'asc' ? 'desc' : 'asc']) }}" class="sorkt-table-header">
                                            ERVARING
                                            @if(request('sort') == 'experience_years')
                                                <i class="fas fa-sort-{{ request('order') == 'asc' ? 'up' : 'down' }}"></i>
                                            @else
                                                <i class="fas fa-sort"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th>
                                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'education_level', 'order' => request('sort') == 'education_level' && request('order') == 'asc' ? 'desc' : 'asc']) }}" class="sorkt-table-header">
                                            OPLEIDING
                                            @if(request('sort') == 'education_level')
                                                <i class="fas fa-sort-{{ request('order') == 'asc' ? 'up' : 'down' }}"></i>
                                            @else
                                                <i class="fas fa-sort"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th>
                                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'status', 'order' => request('sort') == 'status' && request('order') == 'asc' ? 'desc' : 'asc']) }}" class="sorkt-table-header">
                                            STATUS
                                            @if(request('sort') == 'status')
                                                <i class="fas fa-sort-{{ request('order') == 'asc' ? 'up' : 'down' }}"></i>
                                            @else
                                                <i class="fas fa-sort"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th>FOTO</th>
                                    <th>TYPE</th>
                                    <th>
                                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'created_at', 'order' => request('sort') == 'created_at' && request('order') == 'asc' ? 'desc' : 'asc']) }}" class="sorkt-table-header">
                                            AANGEMAAKT
                                            @if(request('sort') == 'created_at')
                                                <i class="fas fa-sort-{{ request('order') == 'asc' ? 'up' : 'down' }}"></i>
                                            @else
                                                <i class="fas fa-sort"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th>ACTIES</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($candidates as $candidate)
                                    <tr>
                                        <td>
                                            <strong>{{ $candidate->id }}</strong>
                                        </td>
                                        <td>
                                            <div class="candidate-info">
                                                <div class="candidate-name">
                                                    <strong>{{ $candidate->full_name }}</strong>
                                                </div>
                                                <div class="candidate-location">
                                                    <i class="fas fa-map-marker-alt me-1"></i>
                                                    {{ $candidate->city }}, {{ $candidate->country }}
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="experience-info">
                                                <span class="experience-badge">{{ $candidate->experience_years }}+ jaar</span>
                                            </div>
                                        </td>
                                        <td>
                                            @if($candidate->education_level)
                                                <span class="education-badge">{{ $candidate->education_level_display }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="status-badge status-{{ $candidate->status }}">
                                                @if($candidate->status == 'active')
                                                    ACTIEF
                                                @elseif($candidate->status == 'pending')
                                                    TEST MODUS
                                                @elseif($candidate->status == 'rejected')
                                                    INACTIEF
                                                @elseif($candidate->status == 'hired')
                                                    AANGENOMEN
                                                @else
                                                    {{ ucfirst($candidate->status) }}
                                                @endif
                                            </span>
                                        </td>
                                        <td>
                                            @if($candidate->photo_blob)
                                                <div class="candidate-photo">
                                                    <img src="{{ route('candidate.photo', ['token' => $candidate->getCompanyPhotoToken(1)]) }}" 
                                                         alt="Kandidaat foto" 
                                                         class="candidate-photo-img"
                                                         style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid #e0e0e0;">
                                                </div>
                                            @else
                                                <div class="no-photo">
                                                    <i class="fas fa-user-circle" style="font-size: 24px; color: #ccc;"></i>
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="candidate-type">
                                                {{ $candidate->preferred_work_type ? ucfirst($candidate->preferred_work_type) : 'Fulltime' }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="date-info">
                                                <div class="date">{{ $candidate->created_at->format('d-m-Y') }}</div>
                                                <div class="time">{{ $candidate->created_at->format('H:i') }}</div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="{{ route('admin.candidates.show', $candidate) }}" 
                                                   class="action-btn action-btn-info" title="Bekijken">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('admin.candidates.edit', $candidate) }}" 
                                                   class="action-btn action-btn-warning" title="Bewerken">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="{{ route('admin.candidates.download-cv', $candidate) }}" 
                                                   class="action-btn action-btn-success" title="Download CV">
                                                    <i class="fas fa-play"></i>
                                                </a>
                                                <form action="{{ route('admin.candidates.toggle-status', $candidate) }}" 
                                                      method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="action-btn action-btn-secondary" title="Status wijzigen">
                                                        <i class="fas fa-square"></i>
                                                    </button>
                                                </form>
                                                <form action="{{ route('admin.candidates.destroy', $candidate) }}" 
                                                      method="POST" class="d-inline" 
                                                      onsubmit="return confirm('Weet je zeker dat je deze kandidaat wilt verwijderen?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="action-btn action-btn-danger" title="Verwijderen">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </kt-table>
                    </div>

                    <!-- Results Info -->
                    <div class="results-info-wrapper">
                        <div class="results-info">
                            <span class="results-text">
                                <i class="fas fa-info-circle me-2"></i>
                                Toon {{ $candidates->firstItem() ?? 0 }} tot {{ $candidates->lastItem() ?? 0 }} van {{ $candidates->total() }} resultaten
                            </span>
                        </div>
                    </div>

                    <!-- Pagination -->
                    @if($candidates->hasPages())
                        <div class="pagination-wrapper">
                            <nav aria-label="Paginering">
                                <ul class="pagination">
                                    {{-- Previous Page Link --}}
                                    @if ($candidates->onFirstPage())
                                        <li class="page-item disabled">
                                            <span class="page-link">
                                                <i class="fas fa-chevron-left"></i>
                                            </span>
                                        </li>
                                    @else
                                        <li class="page-item">
                                            <a class="page-link" href="{{ $candidates->previousPageUrl() }}">
                                                <i class="fas fa-chevron-left"></i>
                                            </a>
                                        </li>
                                    @endif

                                    {{-- Pagination Elements --}}
                                    @foreach ($candidates->getUrlRange(1, $candidates->lastPage()) as $page => $url)
                                        @if ($page == $candidates->currentPage())
                                            <li class="page-item active">
                                                <span class="page-link">{{ $page }}</span>
                                            </li>
                                        @else
                                            <li class="page-item">
                                                <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                                            </li>
                                        @endif
                                    @endforeach

                                    {{-- Next Page Link --}}
                                    @if ($candidates->hasMorePages())
                                        <li class="page-item">
                                            <a class="page-link" href="{{ $candidates->nextPageUrl() }}">
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




                    @else
                    <div class="empty-state">
                        <i class="fas fa-users"></i>
                        <h4>Geen kandidaten gevonden</h4>
                        <p>Er zijn momenteel geen kandidaten beschikbaar.</p>
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
            const sortField = this.dataset.sort;
            const currentUrl = new URL(window.location);
            const currentSort = currentUrl.searchParams.get('sort');
            const currentOrder = currentUrl.searchParams.get('order');
            
            let newOrder = 'asc';
            if (currentSort === sortField && currentOrder === 'asc') {
                newOrder = 'desc';
            }
            
            currentUrl.searchParams.set('sort', sortField);
            currentUrl.searchParams.set('order', newOrder);
            window.location.href = currentUrl.toString();
        });
    });
    
    // Huidige sortering markeren
    const currentSort = '{{ request("sort", "created_at") }}';
    const currentOrder = '{{ request("order", "desc") }}';
    
    sorkt-tableHeaders.forEach(header => {
        if (header.dataset.sort === currentSort) {
            header.classList.remove('sort-asc', 'sort-desc');
            header.classList.add(`sort-${currentOrder}`);
        }
    });
});
</script>
@endsection
