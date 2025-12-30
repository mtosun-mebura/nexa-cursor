@extends('admin.layouts.app')

@section('title', 'Branches Beheer')

@section('content')

<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5">
        <h1 class="text-xl font-medium leading-none text-mono">
            Branches Beheer
        </h1>
        @if(auth()->user()->hasRole('super-admin') || auth()->user()->can('create-branches'))
        <a href="{{ route('admin.branches.create') }}" class="kt-btn kt-btn-primary">
            <i class="ki-filled ki-plus me-2"></i>
            Nieuwe Branch
        </a>
        @endif
    </div>

    <!-- Success Alert -->
    @if(session('success'))
        <div class="kt-alert kt-alert-success mb-5" id="success-alert" role="alert">
            <i class="ki-filled ki-check-circle me-2"></i>
            {{ session('success') }}
        </div>
    @endif

    <!-- Statistics Cards -->
    <div class="kt-card mb-5">
        <div class="kt-card-content">
            <div class="flex flex-col sm:flex-row lg:px-10 py-1.5 gap-2">
                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                    <span class="text-mono text-2xl lg:text-2xl leading-none font-semibold">
                        {{ $stats['total_branches'] ?? 0 }}
                    </span>
                    <span class="text-secondary-foreground text-sm">
                        Branches
                    </span>
                </div>
                <span class="hidden sm:block not-last:border-e border-e-input my-1"></span>
                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                    <span class="text-mono text-2xl lg:text-2xl leading-none font-semibold">
                        {{ $stats['active_branches'] ?? 0 }}
                    </span>
                    <span class="text-secondary-foreground text-sm">
                        Actief
                    </span>
                </div>
                <span class="hidden sm:block not-last:border-e border-e-input my-1"></span>
                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                    <span class="text-mono text-2xl lg:text-2xl leading-none font-semibold">
                        {{ $stats['inactive_branches'] ?? 0 }}
                    </span>
                    <span class="text-secondary-foreground text-sm">
                        Inactief
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="grid gap-5 lg:gap-7.5">
        <div class="kt-card kt-card-grid min-w-full">
            <div class="kt-card-header py-5 flex-wrap gap-2">
                <h3 class="kt-card-title text-sm pb-3 w-full">
                    Toon 1 tot {{ $branches->count() }} van {{ $branches->count() }} branches
                </h3>
                <div class="flex flex-col sm:flex-row flex-wrap gap-2 lg:gap-5 justify-center sm:justify-end items-center w-full">
                    <!-- Search -->
                    <div class="flex w-full sm:w-auto justify-center sm:justify-start">
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
                            <label class="kt-input w-full sm:w-auto" style="position: relative !important;">
                                <i class="ki-filled ki-magnifier"></i>
                                <input placeholder="Zoek branches..." 
                                       type="text" 
                                       name="search" 
                                       value="{{ request('search') }}"
                                       id="search-input"
                                       data-kt-datatable-search="#branches_table"/>
                            </label>
                        </form>
                    </div>
                    <!-- Filters -->
                    <div class="flex flex-col sm:flex-row flex-wrap gap-2.5 items-center justify-center sm:justify-start w-full sm:w-auto">
                        <form method="GET" action="{{ route('admin.branches.index') }}" id="filters-form" class="flex flex-col sm:flex-row gap-2.5 w-full sm:w-auto items-center sm:items-stretch">
                            @if(request('search'))
                                <input type="hidden" name="search" value="{{ request('search') }}">
                            @endif
                            
                            <select class="kt-select w-full sm:w-36" 
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
                                <option value="name" {{ request('sort_by', 'name') == 'name' ? 'selected' : '' }}>Naam</option>
                                <option value="used_count" {{ request('sort_by') == 'used_count' ? 'selected' : '' }}>Gebruikt aantal</option>
                                <option value="created_at" {{ request('sort_by') == 'created_at' ? 'selected' : '' }}>Datum</option>
                                <option value="is_active" {{ request('sort_by') == 'is_active' ? 'selected' : '' }}>Status</option>
                            </select>
                            
                            @if(request('status') || request('sort_by') || request('search'))
                            <a href="{{ route('admin.branches.index') }}" 
                               class="kt-btn kt-btn-outline kt-btn-icon" 
                               title="Filters resetten"
                               id="reset-filter-btn"
                               style="display: inline-flex !important; visibility: visible !important; opacity: 1 !important; min-width: 34px !important; height: 34px !important; align-items: center !important; justify-content: center !important; border: 1px solid var(--input) !important; background-color: var(--background) !important; color: var(--secondary-foreground) !important; position: relative !important; z-index: 1 !important;">
                                <i class="ki-filled ki-arrows-circle text-base" style="display: block !important; visibility: visible !important; opacity: 1 !important; font-size: 1rem !important;"></i>
                            </a>
                            @endif
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="kt-card-content">
                @if($branches->count() > 0)
                    <div class="grid" data-kt-datatable="true" data-kt-datatable-page-size="10" id="branches_table">
                        <div class="kt-scrollable-x-auto">
                        <table class="kt-table table-auto kt-table-border" data-kt-datatable-table="true">
                            <thead>
                                <tr>
                                    <th class="min-w-[250px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Branch Naam</span>
                                            <span class="kt-table-col-sort">
                                                @php
                                                    $currentSort = request('sort_by');
                                                    $currentDirection = request('sort_order');
                                                    $nextDirection = ($currentSort == 'name' && $currentDirection == 'asc') ? 'desc' : 'asc';
                                                @endphp
                                                <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'name', 'sort_order' => $nextDirection]) }}" class="kt-table-col-sort-btn"></a>
                                            </span>
                                        </span>
                                    </th>
                                    <th class="min-w-[200px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Beschrijving</span>
                                        </span>
                                    </th>
                                    <th class="min-w-[150px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Gebruikt aantal</span>
                                            <span class="kt-table-col-sort">
                                                @php
                                                    $currentSort = request('sort_by');
                                                    $currentDirection = request('sort_order');
                                                    $nextDirection = ($currentSort == 'used_count' && $currentDirection == 'desc') ? 'asc' : 'desc';
                                                @endphp
                                                <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'used_count', 'sort_order' => $nextDirection]) }}" class="kt-table-col-sort-btn"></a>
                                            </span>
                                        </span>
                                    </th>
                                    <th class="min-w-[120px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Status</span>
                                            <span class="kt-table-col-sort">
                                                @php
                                                    $currentSort = request('sort_by');
                                                    $currentDirection = request('sort_order');
                                                    $nextDirection = ($currentSort == 'is_active' && $currentDirection == 'asc') ? 'desc' : 'asc';
                                                @endphp
                                                <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'is_active', 'sort_order' => $nextDirection]) }}" class="kt-table-col-sort-btn"></a>
                                            </span>
                                        </span>
                                    </th>
                                    <th class="min-w-[150px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Aangemaakt</span>
                                            <span class="kt-table-col-sort">
                                                @php
                                                    $currentSort = request('sort_by');
                                                    $currentDirection = request('sort_order');
                                                    $nextDirection = ($currentSort == 'created_at' && $currentDirection == 'desc') ? 'asc' : 'desc';
                                                @endphp
                                                <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'created_at', 'sort_order' => $nextDirection]) }}" class="kt-table-col-sort-btn"></a>
                                            </span>
                                        </span>
                                    </th>
                                    <th class="w-[100px] text-center">Acties</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($branches as $branch)
                                    <tr class="branch-row" data-branch-id="{{ $branch->id }}" data-branch-url="{{ route('admin.branches.show', $branch) }}">
                                        <td>
                                            <a href="{{ route('admin.branches.show', $branch) }}" class="branch-row-link">
                                                <div class="flex items-center gap-2.5">
                                                    <span class="size-9 rounded-full shrink-0 bg-accent/60 border border-input flex items-center justify-center">
                                                        @if($branch->icon)
                                                            @if(is_string($branch->icon) && str_starts_with($branch->icon, 'heroicon-'))
                                                                <x-dynamic-component :component="$branch->icon" class="w-5 h-5" style="color: {{ $branch->color ?? '#666' }};" />
                                                            @else
                                                                <i class="{{ $branch->icon }} text-lg" style="color: {{ $branch->color ?? '#666' }};"></i>
                                                            @endif
                                                        @else
                                                            <i class="ki-filled ki-tag text-lg text-muted-foreground"></i>
                                                        @endif
                                                    </span>
                                                    <div class="flex flex-col">
                                                        <span class="text-sm font-medium text-mono hover:text-primary mb-px">
                                                            {{ $branch->name }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </a>
                                        </td>
                                        <td class="text-foreground font-normal">
                                            <a href="{{ route('admin.branches.show', $branch) }}" class="branch-row-link">
                                                @if($branch->description)
                                                    {{ Str::limit($branch->description, 50) }}
                                                @else
                                                    <span class="text-muted-foreground">-</span>
                                                @endif
                                            </a>
                                        </td>
                                        <td class="text-foreground font-normal">
                                            <a href="{{ route('admin.branches.show', $branch) }}" class="branch-row-link">
                                                {{ $branch->used_count ?? 0 }}
                                            </a>
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.branches.show', $branch) }}" class="branch-row-link">
                                                @if($branch->is_active)
                                                    <span class="kt-badge kt-badge-sm kt-badge-success">Actief</span>
                                                @else
                                                    <span class="kt-badge kt-badge-sm kt-badge-danger">Inactief</span>
                                                @endif
                                            </a>
                                        </td>
                                        <td class="text-foreground font-normal">
                                            <a href="{{ route('admin.branches.show', $branch) }}" class="branch-row-link">
                                                {{ $branch->created_at->format('d-m-Y') }}
                                            </a>
                                        </td>
                                        <td class="w-[60px]" onclick="event.stopPropagation();">
                                            @php
                                                $canViewBranch = auth()->user()->hasRole('super-admin') || auth()->user()->can('view-branches');
                                                $canEditBranch = auth()->user()->hasRole('super-admin') || auth()->user()->can('edit-branches');
                                                $canDeleteBranch = auth()->user()->hasRole('super-admin') || auth()->user()->can('delete-branches');
                                            @endphp

                                            @if($canViewBranch || $canEditBranch || $canDeleteBranch)
                                                <div class="kt-menu flex justify-center" data-kt-menu="true">
                                                    <div class="kt-menu-item"
                                                         data-kt-menu-item-offset="0, 10px"
                                                         data-kt-menu-item-placement="bottom-end"
                                                         data-kt-menu-item-placement-rtl="bottom-start"
                                                         data-kt-menu-item-toggle="dropdown"
                                                         data-kt-menu-item-trigger="click">
                                                        <button class="kt-menu-toggle kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" type="button" aria-label="Acties">
                                                            <i class="ki-filled ki-dots-vertical text-lg"></i>
                                                        </button>
                                                        <div class="kt-menu-dropdown kt-menu-default w-full max-w-[175px]" data-kt-menu-dismiss="true">
                                                            @if($canViewBranch)
                                                                <div class="kt-menu-item">
                                                                    <a class="kt-menu-link" href="{{ route('admin.branches.show', $branch) }}">
                                                                        <span class="kt-menu-icon">
                                                                            <i class="ki-filled ki-eye"></i>
                                                                        </span>
                                                                        <span class="kt-menu-title">Bekijken</span>
                                                                    </a>
                                                                </div>
                                                            @endif
                                                            @if($canEditBranch)
                                                                <div class="kt-menu-item">
                                                                    <a class="kt-menu-link" href="{{ route('admin.branches.edit', $branch) }}">
                                                                        <span class="kt-menu-icon">
                                                                            <i class="ki-filled ki-pencil"></i>
                                                                        </span>
                                                                        <span class="kt-menu-title">Bewerken</span>
                                                                    </a>
                                                                </div>
                                                            @endif
                                                            @if($canEditBranch)
                                                                @if($canViewBranch || $canEditBranch)
                                                                    <div class="kt-menu-separator"></div>
                                                                @endif
                                                                <div class="kt-menu-item">
                                                                    <form action="{{ route('admin.branches.toggle-status', $branch) }}"
                                                                          method="POST"
                                                                          style="display: inline;"
                                                                          class="branch-toggle-status-form"
                                                                          data-branch-id="{{ $branch->id }}">
                                                                        @csrf
                                                                        <button type="submit" class="kt-menu-link w-full text-left">
                                                                            <span class="kt-menu-icon">
                                                                                <i class="ki-filled {{ $branch->is_active ? 'ki-pause' : 'ki-play' }}"></i>
                                                                            </span>
                                                                            <span class="kt-menu-title">{{ $branch->is_active ? 'Deactiveren' : 'Activeren' }}</span>
                                                                        </button>
                                                                    </form>
                                                                </div>
                                                                <div class="kt-menu-separator"></div>
                                                            @endif
                                                            @if($canDeleteBranch)
                                                                <div class="kt-menu-item">
                                                                    <form action="{{ route('admin.branches.destroy', $branch) }}"
                                                                          method="POST"
                                                                          style="display: inline;"
                                                                          onsubmit="return confirm('Weet je zeker dat je deze branch wilt verwijderen?')">
                                                                        @csrf
                                                                        @method('DELETE')
                                                                        <button type="submit" class="kt-menu-link w-full text-left text-danger">
                                                                            <span class="kt-menu-icon">
                                                                                <i class="ki-filled ki-trash"></i>
                                                                            </span>
                                                                            <span class="kt-menu-title">Verwijderen</span>
                                                                        </button>
                                                                    </form>
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            @else
                                                <span class="text-sm text-muted-foreground">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        </div>
                    
                    <!-- Pagination (KT Datatable) -->
                    <div class="kt-card-footer justify-center md:justify-between flex-col md:flex-row gap-5 text-secondary-foreground text-sm font-medium">
                        <div class="flex items-center gap-2 order-2 md:order-1">
                            Toon
                            <select class="kt-select w-24" data-kt-datatable-size="true" data-kt-select="" name="perpage"></select>
                            per pagina
                        </div>
                        <div class="flex items-center gap-4 order-1 md:order-2">
                            <span data-kt-datatable-info="true"></span>
                            <div class="kt-datatable-pagination" data-kt-datatable-pagination="true"></div>
                        </div>
                    </div>
                    </div>
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
        // Auto-dismiss success alert after 3 seconds
        const successAlert = document.getElementById('success-alert');
        if (successAlert) {
            setTimeout(function() {
                successAlert.style.transition = 'opacity 0.3s ease-out';
                successAlert.style.opacity = '0';
                setTimeout(function() {
                    successAlert.remove();
                }, 300);
            }, 3000);
        }

        // Filter form submission
        const filterForm = document.getElementById('filters-form');
        const statusFilter = document.getElementById('status-filter');
        const sortFilter = document.getElementById('sort-filter');
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
        
        // Replace "of" with "van" in pagination info (same as users)
        function replaceOfWithVan() {
            const infoSpan = document.querySelector('[data-kt-datatable-info="true"]');
            if (infoSpan && infoSpan.textContent.includes(' of ')) {
                infoSpan.textContent = infoSpan.textContent.replace(' of ', ' van ');
            }
        }
        replaceOfWithVan();
        const infoSpan = document.querySelector('[data-kt-datatable-info="true"]');
        if (infoSpan) {
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'childList' || mutation.type === 'characterData') {
                        replaceOfWithVan();
                    }
                });
            });
            observer.observe(infoSpan, { childList: true, characterData: true, subtree: true });
        }

        // Rows navigate via regular <a href="..."> inside cells (more robust than JS handlers)
    });
</script>
<script src="{{ asset('assets/js/search-input-clear.js') }}"></script>
@endpush

@push('styles')
<style>
    /* Match users overview: row feels clickable */
    .branch-row { cursor: pointer; }
    .branch-row:hover { background-color: rgba(0, 0, 0, 0.02); }
    .dark .branch-row:hover { background-color: rgba(255, 255, 255, 0.03); }

    /* Make entire cell consistently clickable */
    .branch-row-link {
        display: block;
        width: 100%;
        height: 100%;
    }
    .branch-row-link * {
        pointer-events: none;
    }

    /* Table column sorting (same as users overview) */
    .kt-table-col {
        display: flex !important;
        justify-content: space-between !important;
        align-items: center !important;
        width: 100% !important;
    }
    .kt-table-col-sort {
        margin-left: auto !important;
    }
</style>
@endpush

@endsection
