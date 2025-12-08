@extends('admin.layouts.app')

@section('title', 'Branches Beheer')

@section('content')

<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono mb-3">
                Branches Beheer
            </h1>
            <p class="text-sm text-secondary-foreground">Beheer alle branches (voorheen categorieën)</p>
        </div>
        <div class="flex items-center gap-2.5">
            @can('create-branches')
            <a href="{{ route('admin.branches.create') }}" class="kt-btn kt-btn-primary">
                <i class="ki-filled ki-plus me-2"></i>
                Nieuwe Branch
            </a>
            @endcan
        </div>
    </div>

    <div class="grid gap-5 lg:gap-7.5">
        <div class="kt-card kt-card-grid min-w-full">
            <div class="kt-card-header py-5 flex-wrap gap-2">
                <h3 class="kt-card-title text-sm">
                    Toon {{ $branches->firstItem() ?? 0 }} tot {{ $branches->lastItem() ?? 0 }} van {{ $branches->total() }} branches
                </h3>
                <div class="flex flex-wrap gap-2 lg:gap-5 ml-auto">
                    <!-- Search -->
                    <div class="flex">
                        <form method="GET" action="{{ route('admin.branches.index') }}" class="flex gap-2" id="search-form">
                            @if(request('status'))
                                <input type="hidden" name="status" value="{{ request('status') }}">
                            @endif
                            @if(request('sort_by'))
                                <input type="hidden" name="sort_by" value="{{ request('sort_by') }}">
                            @endif
                            @if(request('sort_order'))
                                <input type="hidden" name="sort_order" value="{{ request('sort_order') }}">
                            @endif
                            @if(request('per_page'))
                                <input type="hidden" name="per_page" value="{{ request('per_page') }}">
                            @endif
                            <label class="kt-input">
                                <i class="ki-filled ki-magnifier"></i>
                                <input placeholder="Zoek branches..." 
                                       type="text" 
                                       name="search" 
                                       value="{{ request('search') }}"
                                       id="search-input"/>
                            </label>
                            <button type="submit" class="kt-btn kt-btn-primary">
                                <i class="ki-filled ki-magnifier"></i>
                            </button>
                        </form>
                    </div>
                    <!-- Filters -->
                    <div class="flex flex-wrap gap-2.5">
                        <form method="GET" action="{{ route('admin.branches.index') }}" id="filters-form" class="flex gap-2.5">
                            @if(request('search'))
                                <input type="hidden" name="search" value="{{ request('search') }}">
                            @endif
                            
                            <select class="kt-select w-36" 
                                    name="status" 
                                    data-kt-select="true" 
                                    data-kt-select-placeholder="Status"
                                    id="status-filter">
                                <option value="">Alle statussen</option>
                                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Actief</option>
                                <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactief</option>
                            </select>
                            
                            <select class="kt-select w-36" 
                                    name="sort_by" 
                                    data-kt-select="true" 
                                    data-kt-select-placeholder="Sorteren"
                                    id="sort-filter">
                                <option value="sort_order" {{ request('sort_by', 'sort_order') == 'sort_order' ? 'selected' : '' }}>Volgorde</option>
                                <option value="name" {{ request('sort_by') == 'name' ? 'selected' : '' }}>Naam</option>
                                <option value="created_at" {{ request('sort_by') == 'created_at' ? 'selected' : '' }}>Datum</option>
                                <option value="is_active" {{ request('sort_by') == 'is_active' ? 'selected' : '' }}>Status</option>
                            </select>
                            
                            <select class="kt-select w-36" 
                                    name="per_page" 
                                    data-kt-select="true" 
                                    data-kt-select-placeholder="Per pagina"
                                    id="per-page-filter">
                                <option value="10" {{ request('per_page', 25) == 10 ? 'selected' : '' }}>10</option>
                                <option value="25" {{ request('per_page', 25) == 25 ? 'selected' : '' }}>25</option>
                                <option value="50" {{ request('per_page', 25) == 50 ? 'selected' : '' }}>50</option>
                                <option value="100" {{ request('per_page', 25) == 100 ? 'selected' : '' }}>100</option>
                            </select>
                            
                            @if(request('status') || request('sort_by') || request('per_page') || request('search'))
                            <a href="{{ route('admin.branches.index') }}" 
                               class="kt-btn kt-btn-outline kt-btn-icon" 
                               title="Filters resetten">
                                <i class="ki-filled ki-arrows-circle"></i>
                            </a>
                            @endif
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="kt-card-content">
                @if(session('success'))
                    <div class="kt-alert kt-alert-success mb-5">
                        <i class="ki-filled ki-check-circle me-2"></i>
                        {{ session('success') }}
                    </div>
                @endif
                
                @if($branches->count() > 0)
                    <div class="kt-scrollable-x-auto">
                        <table class="kt-table table-auto kt-table-border">
                            <thead>
                                <tr>
                                    <th class="min-w-[250px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Branch Naam</span>
                                            <span class="kt-table-col-sort">
                                                <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'name', 'sort_order' => request('sort_by') == 'name' && request('sort_order') == 'asc' ? 'desc' : 'asc']) }}" 
                                                   class="kt-table-col-sort-btn">
                                                    <i class="ki-filled ki-up text-xs"></i>
                                                    <i class="ki-filled ki-down text-xs"></i>
                                                </a>
                                            </span>
                                        </span>
                                    </th>
                                    <th class="min-w-[200px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Beschrijving</span>
                                        </span>
                                    </th>
                                    <th class="min-w-[120px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Status</span>
                                            <span class="kt-table-col-sort">
                                                <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'is_active', 'sort_order' => request('sort_by') == 'is_active' && request('sort_order') == 'asc' ? 'desc' : 'asc']) }}" 
                                                   class="kt-table-col-sort-btn">
                                                    <i class="ki-filled ki-up text-xs"></i>
                                                    <i class="ki-filled ki-down text-xs"></i>
                                                </a>
                                            </span>
                                        </span>
                                    </th>
                                    <th class="min-w-[100px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Volgorde</span>
                                            <span class="kt-table-col-sort">
                                                <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'sort_order', 'sort_order' => request('sort_by') == 'sort_order' && request('sort_order') == 'asc' ? 'desc' : 'asc']) }}" 
                                                   class="kt-table-col-sort-btn">
                                                    <i class="ki-filled ki-up text-xs"></i>
                                                    <i class="ki-filled ki-down text-xs"></i>
                                                </a>
                                            </span>
                                        </span>
                                    </th>
                                    <th class="min-w-[150px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Aangemaakt</span>
                                            <span class="kt-table-col-sort">
                                                <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'created_at', 'sort_order' => request('sort_by') == 'created_at' && request('sort_order') == 'asc' ? 'desc' : 'asc']) }}" 
                                                   class="kt-table-col-sort-btn">
                                                    <i class="ki-filled ki-up text-xs"></i>
                                                    <i class="ki-filled ki-down text-xs"></i>
                                                </a>
                                            </span>
                                        </span>
                                    </th>
                                    <th class="w-[100px] text-center">Acties</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($branches as $branch)
                                    <tr>
                                        <td>
                                            <div class="flex items-center gap-2.5">
                                                @if($branch->icon)
                                                    <i class="{{ $branch->icon }} text-lg" style="color: {{ $branch->color ?? '#666' }};"></i>
                                                @else
                                                    <i class="ki-filled ki-tag text-lg text-muted-foreground"></i>
                                                @endif
                                                <div class="flex flex-col">
                                                    <a class="text-sm font-medium text-mono hover:text-primary mb-px" 
                                                       href="{{ route('admin.branches.show', $branch) }}">
                                                        {{ $branch->name }}
                                                    </a>
                                                    @if($branch->slug)
                                                        <span class="text-2sm text-secondary-foreground font-normal">
                                                            {{ $branch->slug }}
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-foreground font-normal">
                                            @if($branch->description)
                                                {{ Str::limit($branch->description, 50) }}
                                            @else
                                                <span class="text-muted-foreground">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($branch->is_active)
                                                <span class="kt-badge kt-badge-success">Actief</span>
                                            @else
                                                <span class="kt-badge kt-badge-danger">Inactief</span>
                                            @endif
                                        </td>
                                        <td class="text-foreground font-normal">
                                            {{ $branch->sort_order }}
                                        </td>
                                        <td class="text-foreground font-normal">
                                            {{ $branch->created_at->format('d-m-Y') }}
                                        </td>
                                        <td>
                                            <div class="flex justify-center gap-1">
                                                @can('view-branches')
                                                <a href="{{ route('admin.branches.show', $branch) }}" 
                                                   class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" 
                                                   title="Bekijken">
                                                    <i class="ki-filled ki-eye"></i>
                                                </a>
                                                @endcan
                                                @can('edit-branches')
                                                <a href="{{ route('admin.branches.edit', $branch) }}" 
                                                   class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" 
                                                   title="Bewerken">
                                                    <i class="ki-filled ki-notepad-edit"></i>
                                                </a>
                                                @endcan
                                                @can('delete-branches')
                                                <form action="{{ route('admin.branches.destroy', $branch) }}" 
                                                      method="POST" 
                                                      style="display: inline;"
                                                      onsubmit="return confirm('Weet je zeker dat je deze branch wilt verwijderen?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" 
                                                            class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost text-danger" 
                                                            title="Verwijderen">
                                                        <i class="ki-filled ki-trash"></i>
                                                    </button>
                                                </form>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    @if($branches->hasPages())
                        <div class="flex justify-between items-center mt-5 pt-5 border-t border-border">
                            <div class="text-sm text-secondary-foreground">
                                Toon {{ $branches->firstItem() }} tot {{ $branches->lastItem() }} van {{ $branches->total() }} resultaten
                            </div>
                            <div>
                                {{ $branches->links() }}
                            </div>
                        </div>
                    @endif
                @else
                    <div class="flex flex-col items-center justify-center py-16">
                        <i class="ki-filled ki-information-5 text-4xl text-muted-foreground mb-4"></i>
                        <h4 class="text-lg font-semibold text-mono mb-2">Geen branches gevonden</h4>
                        <p class="text-sm text-secondary-foreground mb-6">Er zijn nog geen branches aangemaakt.</p>
                        @can('create-branches')
                        <a href="{{ route('admin.branches.create') }}" class="kt-btn kt-btn-primary">
                            <i class="ki-filled ki-plus me-2"></i> Nieuwe Branch
                        </a>
                        @endcan
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Filter form submission
        const filterForm = document.getElementById('filters-form');
        const statusFilter = document.getElementById('status-filter');
        const sortFilter = document.getElementById('sort-filter');
        const perPageFilter = document.getElementById('per-page-filter');
        
        if (statusFilter) {
            statusFilter.addEventListener('change', function() {
                filterForm.submit();
            });
        }
        
        if (sortFilter) {
            sortFilter.addEventListener('change', function() {
                filterForm.submit();
            });
        }
        
        if (perPageFilter) {
            perPageFilter.addEventListener('change', function() {
                filterForm.submit();
            });
        }
        
        // Search form - submit on Enter key
        const searchInput = document.getElementById('search-input');
        const searchForm = document.getElementById('search-form');
        
        if (searchInput && searchForm) {
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    searchForm.submit();
                }
            });
        }
    });
</script>
@endpush

@endsection
