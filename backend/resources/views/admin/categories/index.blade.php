@extends('admin.layouts.app')

@section('title', 'Categorieën Beheer')

@section('content')


<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono mb-3">
                Categorieën Beheer
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
            <!-- Status Statistieken -->
            <div class="stats-cards">
                <div class="stat-card">
                    <div class="stat-number" style="background: linear-gradient(135deg, #4caf50 0%, #81c784 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">{{ $categories->where('is_active', true)->count() }}</div>
                    <div class="stat-label">Actief</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" style="background: linear-gradient(135deg, #f44336 0%, #ef5350 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">{{ $categories->where('is_active', false)->count() }}</div>
                    <div class="stat-label">Inactief</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" style="background: linear-gradient(135deg, #ff9800 0%, #ffb74d 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">{{ $categories->count() }}</div>
                    <div class="stat-label">Totaal</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" style="background: linear-gradient(135deg, #ff9800 0%, #ffb74d 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">{{ $categories->unique('group')->count() }}</div>
                    <div class="stat-label">Groepen</div>
                </div>
            </div>

            <div class="kt-card">
                <!-- Header -->
                <div class="kt-card-header flex justify-between items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-tags me-2"></i> Categorieën Beheer
                    </h5>
                    <div class="flex gap-2">
                        @can('create-categories')
                        <a href="{{ route('admin.categories.create') }}" class="kt-btn kt-btn-primary">
                            <i class="fas fa-plus me-2"></i> Nieuwe Categorie
                        </a>
                        @endcan
                    </div>
                </div>

                <!-- Filters -->
                <div class="filters-section">
                    <form method="GET" action="{{ route('admin.categories.index') }}" id="filters-form">
                        <div class="grid gap-5 lg:gap-7.5">
                            @if(auth()->user()->hasRole('super-admin'))
                                <!-- Super-admin: 5 kolommen over gehele breedte -->
                                <div class="lg:col-span-2">
                                    <div class="filter-group">
                                        <label class="filter-label">Status</label>
                                        <select name="status" class="filter-select" onchange="this.form.submit()">
                                            <option value="">Alle statussen</option>
                                            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Actief</option>
                                            <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactief</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="lg:col-span-2">
                                    <div class="filter-group">
                                        <label class="filter-label">Groep</label>
                                        <select name="group" class="filter-select" onchange="this.form.submit()">
                                            <option value="">Alle groepen</option>
                                            @foreach($categories->unique('group')->pluck('group')->filter() as $group)
                                                <option value="{{ $group }}" {{ request('group') == $group ? 'selected' : '' }}>
                                                    {{ $group }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="lg:col-span-2">
                                    <div class="filter-group">
                                        <label class="filter-label">Sorteren op</label>
                                        <select name="sort_by" class="filter-select" onchange="this.form.submit()">
                                            <option value="sort_order" {{ request('sort_by') === 'sort_order' ? 'selected' : '' }}>Volgorde</option>
                                            <option value="name" {{ request('sort_by') === 'name' ? 'selected' : '' }}>Naam</option>
                                            <option value="created_at" {{ request('sort_by') === 'created_at' ? 'selected' : '' }}>Datum</option>
                                            <option value="is_active" {{ request('sort_by') === 'is_active' ? 'selected' : '' }}>Status</option>
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
                                        <a href="{{ route('admin.categories.index') }}" class="btn btn-outline-secondary w-100" style="height: 36px; display: flex; align-items: center; justify-content: center; gap: 6px; text-decoration: none;">
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
                                            <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactief</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="lg:col-span-3">
                                    <div class="filter-group">
                                        <label class="filter-label">Groep</label>
                                        <select name="group" class="filter-select" onchange="this.form.submit()">
                                            <option value="">Alle groepen</option>
                                            @foreach($categories->unique('group')->pluck('group')->filter() as $group)
                                                <option value="{{ $group }}" {{ request('group') == $group ? 'selected' : '' }}>
                                                    {{ $group }}
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
                                        <a href="{{ route('admin.categories.index') }}" class="btn btn-outline-secondary w-100" style="height: 36px; display: flex; align-items: center; justify-content: center; gap: 6px; text-decoration: none;">
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
                    @if($categories->count() > 0)
                        <div class="kt-table-responsive">
                            <kt-table class="material-kt-table">
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
                                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'name', 'order' => request('sort') == 'name' && request('order') == 'asc' ? 'desc' : 'asc']) }}" class="sorkt-table-header">
                                                Categorie & Details
                                                @if(request('sort') == 'name')
                                                    <i class="fas fa-sort-{{ request('order') == 'asc' ? 'up' : 'down' }}"></i>
                                                @else
                                                    <i class="fas fa-sort"></i>
                                                @endif
                                            </a>
                                        </th>
                                        <th>
                                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'group', 'order' => request('sort') == 'group' && request('order') == 'asc' ? 'desc' : 'asc']) }}" class="sorkt-table-header">
                                                Groep
                                                @if(request('sort') == 'group')
                                                    <i class="fas fa-sort-{{ request('order') == 'asc' ? 'up' : 'down' }}"></i>
                                                @else
                                                    <i class="fas fa-sort"></i>
                                                @endif
                                            </a>
                                        </th>
                                        <th>
                                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'is_active', 'order' => request('sort') == 'is_active' && request('order') == 'asc' ? 'desc' : 'asc']) }}" class="sorkt-table-header">
                                                Status
                                                @if(request('sort') == 'is_active')
                                                    <i class="fas fa-sort-{{ request('order') == 'asc' ? 'up' : 'down' }}"></i>
                                                @else
                                                    <i class="fas fa-sort"></i>
                                                @endif
                                            </a>
                                        </th>
                                        <th>
                                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'sort_order', 'order' => request('sort') == 'sort_order' && request('order') == 'asc' ? 'desc' : 'asc']) }}" class="sorkt-table-header">
                                                Volgorde
                                                @if(request('sort') == 'sort_order')
                                                    <i class="fas fa-sort-{{ request('order') == 'asc' ? 'up' : 'down' }}"></i>
                                                @else
                                                    <i class="fas fa-sort"></i>
                                                @endif
                                            </a>
                                        </th>
                                        <th>
                                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'created_at', 'order' => request('sort') == 'created_at' && request('order') == 'asc' ? 'desc' : 'asc']) }}" class="sorkt-table-header">
                                                Aangemaakt
                                                @if(request('sort') == 'created_at')
                                                    <i class="fas fa-sort-{{ request('order') == 'asc' ? 'up' : 'down' }}"></i>
                                                @else
                                                    <i class="fas fa-sort"></i>
                                                @endif
                                            </a>
                                        </th>
                                        <th>Acties</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($categories as $category)
                                        <tr>
                                            <td>
                                                <strong>{{ $category->id }}</strong>
                                            </td>
                                            <td>
                                                <div class="category-info">
                                                    <div class="category-name">
                                                        @if($category->icon)
                                                            <i class="{{ $category->icon }} me-2" style="color: {{ $category->color }};"></i>
                                                        @endif
                                                        {{ $category->name }}
                                                    </div>
                                                    @if($category->description)
                                                        <div class="category-description">
                                                            <i class="fas fa-info-circle"></i>
                                                            {{ Str::limit($category->description, 50) }}
                                                        </div>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                @if($category->group)
                                                    <span class="category-group">{{ $category->group }}</span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="status-badge {{ $category->is_active ? 'status-active' : 'status-inactive' }}">
                                                    {{ $category->is_active ? 'Actief' : 'Inactief' }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark">{{ $category->sort_order }}</span>
                                            </td>
                                            <td>
                                                <div class="date-info">
                                                    {{ $category->created_at->format('d-m-Y H:i') }}
                                                </div>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    @can('view-categories')
                                                    <a href="{{ route('admin.categories.show', $category) }}" 
                                                       class="action-btn action-btn-info" 
                                                       title="Bekijken">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    @endcan
                                                    @can('edit-categories')
                                                    <a href="{{ route('admin.categories.edit', $category) }}" 
                                                       class="action-btn action-btn-warning" 
                                                       title="Bewerken">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    @endcan
                                                    @can('delete-categories')
                                                    <form action="{{ route('admin.categories.destroy', $category) }}" 
                                                          method="POST" 
                                                          style="display: inline;"
                                                          onsubmit="return confirm('Weet je zeker dat je deze categorie wilt verwijderen?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" 
                                                                class="action-btn action-btn-danger" 
                                                                title="Verwijderen">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                    @endcan
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
                                    Toon {{ $categories->firstItem() ?? 0 }} tot {{ $categories->lastItem() ?? 0 }} van {{ $categories->total() }} resultaten
                                </span>
                            </div>
                        </div>

                        <!-- Pagination -->
                        @if($categories->hasPages())
                            <div class="pagination-wrapper">
                                <nav aria-label="Paginering">
                                    <ul class="pagination">
                                {{-- Previous Page Link --}}
                                @if ($categories->onFirstPage())
                                    <li class="page-item disabled">
                                        <span class="page-link">
                                            <i class="fas fa-chevron-left"></i>
                                        </span>
                                    </li>
                                @else
                                    <li class="page-item">
                                        <a class="page-link" href="{{ $categories->previousPageUrl() }}">
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                    </li>
                                @endif

                                {{-- Pagination Elements --}}
                                @php
                                    $currentPage = $categories->currentPage();
                                    $lastPage = $categories->lastPage();
                                    $startPage = max(1, $currentPage - 2);
                                    $endPage = min($lastPage, $currentPage + 2);
                                    
                                    // Adjust range if we're near the beginning or end
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
                                        <a class="page-link" href="{{ $categories->url(1) }}">1</a>
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
                                            <a class="page-link" href="{{ $categories->url($page) }}">{{ $page }}</a>
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
                                        <a class="page-link" href="{{ $categories->url($lastPage) }}">{{ $lastPage }}</a>
                                    </li>
                                @endif

                                {{-- Next Page Link --}}
                                @if ($categories->hasMorePages())
                                    <li class="page-item">
                                        <a class="page-link" href="{{ $categories->nextPageUrl() }}">
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
                            <i class="fas fa-tags"></i>
                            <h4>Geen categorieën gevonden</h4>
                            <p>Er zijn nog geen categorieën aangemaakt. Maak je eerste categorie aan om te beginnen.</p>
                            @can('create-categories')
                            <a href="{{ route('admin.categories.create') }}" class="kt-btn kt-btn-primary">
                                <i class="fas fa-plus me-2"></i> Nieuwe Categorie
                            </a>
                            @endcan
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection