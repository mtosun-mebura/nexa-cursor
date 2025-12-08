@extends('admin.layouts.app')

@section('title', 'Bedrijven Beheer')

@section('content')

<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5">
        <h1 class="text-xl font-medium leading-none text-mono">
            Bedrijven Beheer
        </h1>
        @can('create-companies')
        <a href="{{ route('admin.companies.create') }}" class="kt-btn kt-btn-primary">
            <i class="ki-filled ki-plus me-2"></i>
            Nieuw Bedrijf
        </a>
        @endcan
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
            <div class="flex lg:px-10 py-1.5 gap-2">
                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                    <span class="text-mono text-2xl lg:text-2xl leading-none font-semibold">
                        {{ $stats['total_companies'] ?? 0 }}
                    </span>
                    <span class="text-secondary-foreground text-sm">
                        Bedrijven
                    </span>
                </div>
                <span class="not-last:border-e border-e-input my-1"></span>
                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                    <span class="text-mono text-2xl lg:text-2xl leading-none font-semibold">
                        {{ $stats['active_companies'] ?? 0 }}
                    </span>
                    <span class="text-secondary-foreground text-sm">
                        Actief
                    </span>
                </div>
                <span class="not-last:border-e border-e-input my-1"></span>
                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                    <span class="text-mono text-2xl lg:text-2xl leading-none font-semibold">
                        {{ $stats['total_users'] ?? 0 }}
                    </span>
                    <span class="text-secondary-foreground text-sm">
                        Gebruikers
                    </span>
                </div>
                <span class="not-last:border-e border-e-input my-1"></span>
                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                    <span class="text-mono text-2xl lg:text-2xl leading-none font-semibold">
                        {{ $stats['total_vacancies'] ?? 0 }}
                    </span>
                    <span class="text-secondary-foreground text-sm">
                        Vacatures
                    </span>
                </div>
                <span class="not-last:border-e border-e-input my-1"></span>
                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                    <span class="text-mono text-2xl lg:text-2xl leading-none font-semibold">
                        {{ $stats['intermediaries'] ?? 0 }}
                    </span>
                    <span class="text-secondary-foreground text-sm">
                        Tussenpartijen
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="grid gap-5 lg:gap-7.5">
        <div class="kt-card kt-card-grid min-w-full">
            <div class="kt-card-header py-5 flex-wrap gap-2">
                <h3 class="kt-card-title text-sm pb-3 w-full">
                    Toon 1 tot {{ $companies->count() }} van {{ $companies->count() }} bedrijven
                </h3>
                <div class="flex flex-wrap gap-2 lg:gap-5 justify-end w-full">
                    <!-- Search -->
                    <div class="flex">
                        <form method="GET" action="{{ route('admin.companies.index') }}" class="flex gap-2" id="search-form">
                            @if(request('status'))
                                <input type="hidden" name="status" value="{{ request('status') }}">
                            @endif
                            @if(request('intermediary'))
                                <input type="hidden" name="intermediary" value="{{ request('intermediary') }}">
                            @endif
                            @if(request('industry'))
                                <input type="hidden" name="industry" value="{{ request('industry') }}">
                            @endif
                            @if(request('sort'))
                                <input type="hidden" name="sort" value="{{ request('sort') }}">
                            @endif
                            @if(request('direction'))
                                <input type="hidden" name="direction" value="{{ request('direction') }}">
                            @endif
                            @if(request('per_page'))
                                <input type="hidden" name="per_page" value="{{ request('per_page') }}">
                            @endif
                            <label class="kt-input" style="position: relative !important;">
                                <i class="ki-filled ki-magnifier"></i>
                                <input placeholder="Zoek bedrijven..." 
                                       type="text" 
                                       name="search" 
                                       value="{{ request('search') }}"
                                       id="search-input"
                                       data-kt-datatable-search="#companies_table"
                                       style="{{ request('search') ? 'padding-right: 2.5rem !important;' : '' }}"/>
                                @if(request('search'))
                                <button type="button" 
                                        class="kt-input-clear" 
                                        id="search-clear-btn"
                                        title="Zoekopdracht wissen"
                                        style="position: absolute !important; right: 0.75rem !important; top: 50% !important; transform: translateY(-50%) !important; background: transparent !important; border: none !important; padding: 0.25rem !important; cursor: pointer !important; display: flex !important; align-items: center !important; justify-content: center !important; color: var(--muted-foreground) !important; opacity: 1 !important; visibility: visible !important; z-index: 10 !important; width: 1.5rem !important; height: 1.5rem !important;">
                                    <i class="ki-filled ki-cross" style="font-size: 0.875rem !important; display: block !important; visibility: visible !important;"></i>
                                </button>
                                @endif
                            </label>
                        </form>
                    </div>
                    <!-- Filters -->
                    <div class="flex flex-wrap gap-2.5 items-center">
                        <form method="GET" action="{{ route('admin.companies.index') }}" id="filters-form" class="flex gap-2.5">
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
                                    name="intermediary" 
                                    data-kt-select="true" 
                                    data-kt-select-placeholder="Type"
                                    id="intermediary-filter">
                                <option value="">Alle types</option>
                                <option value="yes" {{ request('intermediary') == 'yes' ? 'selected' : '' }}>Tussenpartij</option>
                                <option value="no" {{ request('intermediary') == 'no' ? 'selected' : '' }}>Directe werkgever</option>
                            </select>
                            
                            @if($industries->count() > 0)
                            <select class="kt-select w-36" 
                                    name="industry" 
                                    data-kt-select="true" 
                                    data-kt-select-placeholder="Industrie"
                                    id="industry-filter">
                                <option value="">Alle industrieën</option>
                                @foreach($industries as $industry)
                                    <option value="{{ $industry }}" {{ request('industry') == $industry ? 'selected' : '' }}>
                                        {{ $industry }}
                                    </option>
                                @endforeach
                            </select>
                            @endif
                            
                            <select class="kt-select w-36" 
                                    name="sort" 
                                    data-kt-select="true" 
                                    data-kt-select-placeholder="Sorteren"
                                    id="sort-filter">
                                <option value="created_at" {{ request('sort', 'created_at') == 'created_at' ? 'selected' : '' }}>Datum</option>
                                <option value="name" {{ request('sort') == 'name' ? 'selected' : '' }}>Naam</option>
                                <option value="is_active" {{ request('sort') == 'is_active' ? 'selected' : '' }}>Status</option>
                            </select>
                        </form>
                        @if(request('status') || request('intermediary') || request('industry') || (request('sort') && request('sort') != 'created_at') || request('direction') || request('search'))
                        <a href="{{ route('admin.companies.index') }}" 
                           class="kt-btn kt-btn-outline kt-btn-icon" 
                           title="Filters resetten"
                           id="reset-filter-btn"
                           style="display: inline-flex !important; visibility: visible !important; opacity: 1 !important; min-width: 34px !important; height: 34px !important; align-items: center !important; justify-content: center !important; border: 1px solid var(--input) !important; background-color: var(--background) !important; color: var(--secondary-foreground) !important; position: relative !important; z-index: 1 !important;">
                            <i class="ki-filled ki-arrows-circle text-base" style="display: block !important; visibility: visible !important; opacity: 1 !important; font-size: 1rem !important;"></i>
                        </a>
                        @endif
                    </div>
                </div>
            </div>
            
            <div class="kt-card-content">
                @if($companies->count() > 0)
                    <div class="grid" data-kt-datatable="true" data-kt-datatable-page-size="10" id="companies_table">
                        <div class="kt-scrollable-x-auto">
                            <table class="kt-table table-auto kt-table-border" data-kt-datatable-table="true">
                            <thead>
                                <tr>
                                    <th class="min-w-[250px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Bedrijf</span>
                                            <span class="kt-table-col-sort">
                                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'name', 'direction' => request('sort') == 'name' && request('direction') == 'asc' ? 'desc' : 'asc']) }}" 
                                                   class="kt-table-col-sort-btn"></a>
                                            </span>
                                        </span>
                                    </th>
                                    <th class="min-w-[200px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Contact</span>
                                            <span class="kt-table-col-sort"></span>
                                        </span>
                                    </th>
                                    <th class="min-w-[150px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Locatie</span>
                                            <span class="kt-table-col-sort"></span>
                                        </span>
                                    </th>
                                    <th class="min-w-[120px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Type</span>
                                            <span class="kt-table-col-sort"></span>
                                        </span>
                                    </th>
                                    <th class="min-w-[100px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Status</span>
                                            <span class="kt-table-col-sort">
                                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'is_active', 'direction' => request('sort') == 'is_active' && request('direction') == 'asc' ? 'desc' : 'asc']) }}" 
                                                   class="kt-table-col-sort-btn"></a>
                                            </span>
                                        </span>
                                    </th>
                                    <th class="min-w-[150px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Aangemaakt</span>
                                            <span class="kt-table-col-sort">
                                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'created_at', 'direction' => request('sort') == 'created_at' && request('direction') == 'asc' ? 'desc' : 'asc']) }}" 
                                                   class="kt-table-col-sort-btn"></a>
                                            </span>
                                        </span>
                                    </th>
                                    <th class="w-[60px] text-center">Acties</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($companies as $company)
                                    <tr class="company-row">
                                        <td>
                                            <span class="text-sm font-medium text-mono" data-company-id="{{ $company->id }}">
                                                {{ $company->name }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="flex flex-col gap-1">
                                                @if($company->email)
                                                    <div class="text-sm text-foreground">
                                                        <i class="ki-filled ki-sms me-1 text-xs"></i>
                                                        {{ $company->email }}
                                                    </div>
                                                @endif
                                                @if($company->phone)
                                                    <div class="text-sm text-foreground">
                                                        <i class="ki-filled ki-phone me-1 text-xs"></i>
                                                        {{ $company->phone }}
                                                    </div>
                                                @endif
                                                @if(!$company->email && !$company->phone)
                                                    <span class="text-sm text-muted-foreground">Geen contact</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="text-foreground font-normal">
                                            @if($company->city || $company->country)
                                                <div class="text-sm">
                                                    @if($company->city){{ $company->city }}@endif
                                                    @if($company->city && $company->country), @endif
                                                    @if($company->country){{ $company->country }}@endif
                                                </div>
                                            @else
                                                <span class="text-sm text-muted-foreground">Geen locatie</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($company->is_intermediary)
                                                <span class="kt-badge kt-badge-info">Tussenpartij</span>
                                            @else
                                                <span class="kt-badge kt-badge-success">Directe werkgever</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($company->is_active)
                                                <span class="kt-badge kt-badge-success">Actief</span>
                                            @else
                                                <span class="kt-badge kt-badge-danger">Inactief</span>
                                            @endif
                                        </td>
                                        <td class="text-foreground font-normal">
                                            <span class="text-sm">{{ $company->created_at->format('d-m-Y') }}</span>
                                        </td>
                                        <td class="w-[60px]" onclick="event.stopPropagation();">
                                            <div class="kt-menu flex justify-center" data-kt-menu="true">
                                                <div class="kt-menu-item" data-kt-menu-item-offset="0, 10px" data-kt-menu-item-placement="bottom-end" data-kt-menu-item-placement-rtl="bottom-start" data-kt-menu-item-toggle="dropdown" data-kt-menu-item-trigger="click">
                                                    <button class="kt-menu-toggle kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost">
                                                        <i class="ki-filled ki-dots-vertical text-lg"></i>
                                                    </button>
                                                    <div class="kt-menu-dropdown kt-menu-default w-full max-w-[175px]" data-kt-menu-dismiss="true">
                                                        @can('view-companies')
                                                        <div class="kt-menu-item">
                                                            <a class="kt-menu-link" href="{{ route('admin.companies.show', $company) }}">
                                                                <span class="kt-menu-icon">
                                                                    <i class="ki-filled ki-eye"></i>
                                                                </span>
                                                                <span class="kt-menu-title">Bekijken</span>
                                                            </a>
                                                        </div>
                                                        @endcan
                                                        @can('edit-companies')
                                                        <div class="kt-menu-item">
                                                            <a class="kt-menu-link" href="{{ route('admin.companies.edit', $company) }}">
                                                                <span class="kt-menu-icon">
                                                                    <i class="ki-filled ki-pencil"></i>
                                                                </span>
                                                                <span class="kt-menu-title">Bewerken</span>
                                                            </a>
                                                        </div>
                                                        @endcan
                                                        @if(($canView = auth()->user()->can('view-companies')) || ($canEdit = auth()->user()->can('edit-companies')))
                                                        <div class="kt-menu-separator"></div>
                                                        @endif
                                                        @can('edit-companies')
                                                        <div class="kt-menu-item">
                                                            <form action="{{ route('admin.companies.toggle-status', $company) }}" 
                                                                  method="POST" 
                                                                  style="display: inline;"
                                                                  onsubmit="return confirm('Weet je zeker dat je de status wilt wijzigen?')">
                                                                @csrf
                                                                <button type="submit" class="kt-menu-link w-full text-left">
                                                                    <span class="kt-menu-icon">
                                                                        <i class="ki-filled {{ $company->is_active ? 'ki-pause' : 'ki-play' }}"></i>
                                                                    </span>
                                                                    <span class="kt-menu-title">{{ $company->is_active ? 'Deactiveren' : 'Activeren' }}</span>
                                                                </button>
                                                            </form>
                                                        </div>
                                                        @endcan
                                                        @can('delete-companies')
                                                        <div class="kt-menu-separator"></div>
                                                        <div class="kt-menu-item">
                                                            <form action="{{ route('admin.companies.destroy', $company) }}" 
                                                                  method="POST" 
                                                                  style="display: inline;"
                                                                  onsubmit="return confirm('Weet je zeker dat je dit bedrijf wilt verwijderen?')">
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
                                                        @endcan
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        </div>
                    
                    <!-- Pagination -->
                    <div class="kt-card-footer justify-center md:justify-between flex-col md:flex-row gap-5 text-secondary-foreground text-sm font-medium">
                        <div class="flex items-center gap-2 order-2 md:order-1">
                            Toon
                            <select class="kt-select w-24" data-kt-datatable-size="true" data-kt-select="" name="perpage">
                            </select>
                            per pagina
                        </div>
                        <div class="flex items-center gap-4 order-1 md:order-2">
                            <span data-kt-datatable-info="true">
                            </span>
                            <div class="kt-datatable-pagination" data-kt-datatable-pagination="true">
                            </div>
                        </div>
                    </div>
                    </div>
                @else
                    <div class="flex flex-col items-center justify-center py-16">
                        <i class="ki-filled ki-information-5 text-4xl text-muted-foreground mb-4"></i>
                        <h4 class="text-lg font-semibold text-mono mb-2">Geen bedrijven gevonden</h4>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Replace "of" with "van" in pagination info
        function replaceOfWithVan() {
            const infoSpan = document.querySelector('[data-kt-datatable-info="true"]');
            if (infoSpan && infoSpan.textContent.includes(' of ')) {
                infoSpan.textContent = infoSpan.textContent.replace(' of ', ' van ');
            }
        }
        
        // Initial replacement
        replaceOfWithVan();
        
        // Watch for changes in the info span
        const infoSpan = document.querySelector('[data-kt-datatable-info="true"]');
        if (infoSpan) {
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'childList' || mutation.type === 'characterData') {
                        replaceOfWithVan();
                    }
                });
            });
            
            observer.observe(infoSpan, {
                childList: true,
                characterData: true,
                subtree: true
            });
        }
        
        // Filter form submission (server-side filters)
        const filterForm = document.getElementById('filters-form');
        const statusFilter = document.getElementById('status-filter');
        const intermediaryFilter = document.getElementById('intermediary-filter');
        const industryFilter = document.getElementById('industry-filter');
        const sortFilter = document.getElementById('sort-filter');
        const perPageFilter = document.getElementById('per-page-filter');
        
        if (statusFilter && filterForm) {
            statusFilter.addEventListener('change', function() {
                filterForm.submit();
            });
        }
        
        if (intermediaryFilter && filterForm) {
            intermediaryFilter.addEventListener('change', function() {
                filterForm.submit();
            });
        }
        
        if (industryFilter && filterForm) {
            industryFilter.addEventListener('change', function() {
                filterForm.submit();
            });
        }
        
        if (sortFilter && filterForm) {
            sortFilter.addEventListener('change', function() {
                filterForm.submit();
            });
        }
        
        if (perPageFilter && filterForm) {
            perPageFilter.addEventListener('change', function() {
                filterForm.submit();
            });
        }
        
        // Auto-dismiss success alert after 3 seconds
        const successAlert = document.getElementById('success-alert');
        if (successAlert) {
            setTimeout(function() {
                successAlert.style.transition = 'opacity 0.3s ease-out';
                successAlert.style.opacity = '0';
                setTimeout(function() {
                    successAlert.remove();
                }, 300); // Wait for fade-out animation
            }, 3000); // 3 seconds
        }
        
        // Clear search input button
        const searchClearBtn = document.getElementById('search-clear-btn');
        const searchInput = document.getElementById('search-input');
        const searchForm = document.getElementById('search-form');
        
        if (searchClearBtn && searchInput && searchForm) {
            searchClearBtn.addEventListener('click', function(e) {
                e.preventDefault();
                searchInput.value = '';
                // Trigger input event for datatable search
                searchInput.dispatchEvent(new Event('input', { bubbles: true }));
                // Submit form to clear search from URL
                searchForm.submit();
            });
        }
        
        // Make table rows clickable (except actions column)
        document.querySelectorAll('tbody tr.company-row').forEach(function(row) {
            row.addEventListener('click', function(e) {
                // Don't navigate if clicking on actions column or menu
                if (e.target.closest('td:last-child') || e.target.closest('.kt-menu') || e.target.closest('button') || e.target.closest('a')) {
                    return;
                }
                
                // Get company ID from the name span
                const nameSpan = this.querySelector('td:first-child span[data-company-id]');
                if (nameSpan) {
                    const companyId = nameSpan.getAttribute('data-company-id');
                    if (companyId) {
                        window.location.href = '/admin/companies/' + companyId;
                    }
                }
            });
        });
    });
</script>
@endpush

@push('styles')
<style>
    /* Table column sorting */
    .kt-table-col {
        display: flex !important;
        justify-content: space-between !important;
        align-items: center !important;
        width: 100% !important;
    }
    .kt-table-col-sort {
        margin-left: auto !important;
    }
    
    /* Input clear button */
    .kt-input {
        position: relative !important;
    }
    .kt-input:has(.kt-input-clear) input {
        padding-right: 2.5rem !important;
    }
    .kt-input-clear {
        position: absolute !important;
        right: 0.75rem !important;
        top: 50% !important;
        transform: translateY(-50%) !important;
        background: transparent !important;
        border: none !important;
        padding: 0.25rem !important;
        cursor: pointer !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        color: var(--muted-foreground) !important;
        opacity: 1 !important;
        visibility: visible !important;
        z-index: 10 !important;
        width: 1.5rem !important;
        height: 1.5rem !important;
        transition: opacity 0.2s, color 0.2s !important;
    }
    .kt-input-clear:hover {
        opacity: 1 !important;
        color: var(--foreground) !important;
    }
    .kt-input-clear i {
        font-size: 0.875rem !important;
        display: block !important;
        visibility: visible !important;
    }
    
    /* Reset button visibility */
    a[title="Filters resetten"] {
        display: inline-flex !important;
        visibility: visible !important;
        opacity: 1 !important;
        min-width: 34px !important;
        height: 34px !important;
        align-items: center !important;
        justify-content: center !important;
        border: 1px solid var(--input) !important;
        background-color: var(--background) !important;
        color: var(--secondary-foreground) !important;
    }
    a[title="Filters resetten"]:hover {
        background-color: var(--accent) !important;
        color: var(--accent-foreground) !important;
    }
    a[title="Filters resetten"] i {
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
    }
    
    /* Table row hover styling (same as demo) */
    .company-row {
        cursor: pointer !important;
    }
    .company-row:hover {
        background-color: var(--muted) !important;
    }
    @supports (color: color-mix(in lab, red, red)) {
        .company-row:hover {
            background-color: color-mix(in oklab, var(--muted) 50%, transparent) !important;
        }
    }
</style>
@endpush

@endsection
