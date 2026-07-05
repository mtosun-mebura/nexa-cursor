@extends('admin.layouts.app')

@section('title', 'Bedrijven Beheer')

@section('content')

<div class="kt-container-fixed">
    <div class="flex flex-col gap-4 pb-7.5">
        <div class="flex flex-wrap items-center gap-x-5 gap-y-3">
            <h1 class="text-xl font-medium leading-none text-mono shrink-0">
                Bedrijven Beheer
            </h1>
            @auth
            {{-- Iedereen die deze pagina mag zien (super-admin of view-companies) ziet de acties; aanmaken blijft afgedwongen in controllers. --}}
            <div class="flex flex-1 flex-wrap items-center justify-end gap-2 relative z-10 min-w-0" data-company-create-actions="true">
                <a href="{{ route('admin.companies.wizard.start') }}" class="kt-btn kt-btn-primary">
                    <i class="ki-filled ki-element-11 me-2"></i>
                    Nieuwe tenant (wizard)
                </a>
                <a href="{{ route('admin.companies.create') }}" class="kt-btn kt-btn-outline">
                    <i class="ki-filled ki-plus me-2"></i>
                    Nieuw bedrijf (formulier)
                </a>
            </div>
            @endauth
        </div>
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
                        {{ $stats['total_companies'] ?? 0 }}
                    </span>
                    <span class="text-secondary-foreground text-sm">
                        Bedrijven
                    </span>
                </div>
                <span class="hidden sm:block not-last:border-e border-e-input my-1"></span>
                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                    <span class="text-mono text-2xl lg:text-2xl leading-none font-semibold">
                        {{ $stats['active_companies'] ?? 0 }}
                    </span>
                    <span class="text-secondary-foreground text-sm">
                        Actief
                    </span>
                </div>
                <span class="hidden sm:block not-last:border-e border-e-input my-1"></span>
                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                    <span class="text-mono text-2xl lg:text-2xl leading-none font-semibold">
                        {{ $stats['total_users'] ?? 0 }}
                    </span>
                    <span class="text-secondary-foreground text-sm">
                        Gebruikers
                    </span>
                </div>
                <span class="hidden sm:block not-last:border-e border-e-input my-1"></span>
                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                    <span class="text-mono text-2xl lg:text-2xl leading-none font-semibold">
                        {{ $stats['total_vacancies'] ?? 0 }}
                    </span>
                    <span class="text-secondary-foreground text-sm">
                        Vacatures
                    </span>
                </div>
                <span class="hidden sm:block not-last:border-e border-e-input my-1"></span>
                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                    <span class="text-mono text-2xl lg:text-2xl leading-none font-semibold">
                        {{ $stats['intermediaries'] ?? 0 }}
                    </span>
                    <span class="text-secondary-foreground text-sm">
                        Tussenpartijen / Recruiters
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
                <div class="flex flex-col sm:flex-row flex-wrap gap-2 lg:gap-5 justify-center sm:justify-end items-center w-full">
                    <!-- Search -->
                    <div class="flex w-full sm:w-auto justify-center sm:justify-start">
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
                            <label class="kt-input w-full sm:w-auto" style="position: relative !important;">
                                <i class="ki-filled ki-magnifier"></i>
                                <input placeholder="Zoek bedrijven..." 
                                       type="text" 
                                       name="search" 
                                       value="{{ request('search') }}"
                                       id="search-input"
/>
                            </label>
                        </form>
                    </div>
                    <!-- Filters -->
                    <div class="flex flex-col sm:flex-row flex-wrap gap-2.5 items-center justify-center sm:justify-start w-full sm:w-auto">
                        <form method="GET" action="{{ route('admin.companies.index') }}" id="filters-form" class="flex flex-col sm:flex-row gap-2.5 w-full sm:w-auto items-center sm:items-stretch">
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
                            
                            <select class="kt-select w-full sm:w-36" 
                                    name="intermediary" 
                                    data-kt-select="true" 
                                    data-kt-select-placeholder="Type"
                                    id="intermediary-filter">
                                <option value="">Alle types</option>
                                <option value="yes" {{ request('intermediary') == 'yes' ? 'selected' : '' }}>Tussenpartij / Recruiter</option>
                                <option value="no" {{ request('intermediary') == 'no' ? 'selected' : '' }}>Directe werkgever</option>
                            </select>
                            
                            @if($industries->count() > 0)
                            <select class="kt-select w-full sm:w-36" 
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
                            
                            <select class="kt-select w-full sm:w-36" 
                                    name="sort" 
                                    data-kt-select="true" 
                                    data-kt-select-placeholder="Sortering"
                                    id="sort-filter">
                                <option value="" {{ !request('sort') ? 'selected' : '' }}>Geen sortering</option>
                                <option value="created_at" {{ request('sort') == 'created_at' ? 'selected' : '' }}>Datum</option>
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
                    <div class="grid" data-admin-datatable="true" data-admin-datatable-page-size="10" id="companies_table" data-admin-datatable-label="bedrijven" data-admin-datatable-on-page="initCompaniesTablePage">
                        <div class="kt-scrollable-x-auto min-w-0 companies-table-wrap">
                            <table class="kt-table kt-table-border admin-fluid-table w-full">
                            <thead>
                                <tr>
                                    <th data-label="Bedrijf">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Bedrijf</span>
                                            <span class="kt-table-col-sort">
                                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'name', 'direction' => request('sort') == 'name' && request('direction') == 'asc' ? 'desc' : 'asc']) }}" 
                                                   class="kt-table-col-sort-btn"></a>
                                            </span>
                                        </span>
                                    </th>
                                    <th data-label="Contact">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Contact</span>
                                            <span class="kt-table-col-sort"></span>
                                        </span>
                                    </th>
                                    <th data-label="Locatie">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Locatie</span>
                                            <span class="kt-table-col-sort"></span>
                                        </span>
                                    </th>
                                    <th data-label="Type">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Type</span>
                                            <span class="kt-table-col-sort"></span>
                                        </span>
                                    </th>
                                    <th data-label="Status">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Status</span>
                                            <span class="kt-table-col-sort">
                                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'is_active', 'direction' => request('sort') == 'is_active' && request('direction') == 'asc' ? 'desc' : 'asc']) }}" 
                                                   class="kt-table-col-sort-btn"></a>
                                            </span>
                                        </span>
                                    </th>
                                    <th data-label="Aangemaakt">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Aangemaakt</span>
                                            <span class="kt-table-col-sort">
                                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'created_at', 'direction' => request('sort') == 'created_at' && request('direction') == 'asc' ? 'desc' : 'asc']) }}" 
                                                   class="kt-table-col-sort-btn"></a>
                                            </span>
                                        </span>
                                    </th>
                                    <th class="text-center" data-label="Acties">Acties</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($companies as $company)
                                    <tr class="company-row" data-row-href="{{ route('admin.companies.show', $company) }}">
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
                                                <span class="kt-badge kt-badge-info">Tussenpartij / Recruiter</span>
                                            @else
                                                <span class="kt-badge kt-badge-success">Directe werkgever</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($company->is_active)
                                                <span class="kt-badge kt-badge-success">Actief</span>
                                            @else
                                                <span class="kt-badge kt-badge-sm kt-badge-danger">Inactief</span>
                                            @endif
                                        </td>
                                        <td class="text-foreground font-normal">
                                            <span class="text-sm">{{ $company->created_at?->format('d-m-Y') ?? '—' }}</span>
                                        </td>
                                        <td class="text-center companies-table__actions-col" data-no-row-link>
                                            <div class="kt-menu flex justify-center" data-kt-menu="true">
                                                <div class="kt-menu-item" data-kt-menu-item-offset="0, 10px" data-kt-menu-item-placement="bottom-end" data-kt-menu-item-placement-rtl="bottom-start" data-kt-menu-item-toggle="dropdown" data-kt-menu-item-trigger="click">
                                                    <button type="button" class="kt-menu-toggle kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" aria-label="Acties">
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
                                                        @can('create-companies')
                                                        @if(session()->has('company_wizard.'.$company->id.'.max_reachable'))
                                                        @php
                                                            $wizardResumeStep = max(1, min(7, (int) session('company_wizard.'.$company->id.'.max_reachable')));
                                                        @endphp
                                                        <div class="kt-menu-item">
                                                            <a class="kt-menu-link" href="{{ route('admin.companies.wizard.step', [$company, $wizardResumeStep]) }}">
                                                                <span class="kt-menu-icon">
                                                                    <i class="ki-filled ki-element-11"></i>
                                                                </span>
                                                                <span class="kt-menu-title">Nieuwe tenant — verder</span>
                                                            </a>
                                                        </div>
                                                        @endif
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
                    <div class="kt-card-footer admin-datatable-footer text-secondary-foreground text-sm font-medium">
                        <div class="admin-datatable-footer__perpage flex items-center gap-2">
                            Toon
                            <select class="kt-select w-24" data-admin-datatable-size="true" data-kt-select="" name="perpage">
                            </select>
                            per pagina
                        </div>
                        <div class="admin-datatable-footer__pagination">
                            <div class="kt-datatable-pagination" data-admin-datatable-pagination="true"></div>
                        </div>
                        <span class="admin-datatable-footer__info" data-admin-datatable-info="true"></span>
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

@push('styles')
<style>
    #content #companies_table .admin-fluid-table th:nth-child(1),
    #content #companies_table .admin-fluid-table td:nth-child(1) {
        width: 18%;
    }

    #content #companies_table .admin-fluid-table th:nth-child(2),
    #content #companies_table .admin-fluid-table td:nth-child(2) {
        width: 24%;
    }

    #content #companies_table .admin-fluid-table th:nth-child(3),
    #content #companies_table .admin-fluid-table td:nth-child(3) {
        width: 14%;
    }

    #content #companies_table .admin-fluid-table th:nth-child(4),
    #content #companies_table .admin-fluid-table td:nth-child(4) {
        width: 14%;
    }

    #content #companies_table .admin-fluid-table th:nth-child(5),
    #content #companies_table .admin-fluid-table td:nth-child(5) {
        width: 10%;
    }

    #content #companies_table .admin-fluid-table th:nth-child(6),
    #content #companies_table .admin-fluid-table td:nth-child(6) {
        width: 12%;
    }

    #content #companies_table .admin-fluid-table th:last-child,
    #content #companies_table .admin-fluid-table td:last-child {
        width: 3.5rem;
        white-space: nowrap;
    }

    #content #companies_table .companies-table__actions-col {
        overflow: visible !important;
        vertical-align: middle !important;
    }

    #content #companies_table .companies-table__actions-col .kt-menu {
        display: flex !important;
        justify-content: center !important;
        width: 100%;
    }

    .companies-table-wrap td:last-child .kt-menu-dropdown {
        position: fixed !important;
        z-index: 99999 !important;
    }

    .companies-table-wrap td:last-child .kt-menu-item.show .kt-menu-dropdown,
    .companies-table-wrap td:last-child .kt-menu-item[data-kt-menu-item-toggle="dropdown"].show .kt-menu-dropdown {
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
    }

    .companies-table-wrap .kt-scrollable-x-auto {
        overflow-x: auto !important;
        overflow-y: visible !important;
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Replace "of" with "van" in pagination info
        function replaceOfWithVan() {
            const infoSpan = document.querySelector('[data-admin-datatable-info="true"]');
            if (infoSpan && infoSpan.textContent.includes(' of ')) {
                infoSpan.textContent = infoSpan.textContent.replace(' of ', ' van ');
            }
        }
        
        // Initial replacement
        replaceOfWithVan();
        
        // Watch for changes in the info span
        const infoSpan = document.querySelector('[data-admin-datatable-info="true"]');
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
        
        // Make table rows clickable (except actions column)
        function initCompanyMenus() {
            if (window.KTMenu && typeof window.KTMenu.init === 'function') {
                try {
                    window.KTMenu.init();
                } catch (e) {
                    console.warn('KTMenu init error:', e);
                }
            }

            document.querySelectorAll('#companies_table .kt-menu-toggle').forEach(function(toggle) {
                if (toggle._companyMenuBound) {
                    return;
                }
                toggle._companyMenuBound = true;
                toggle.addEventListener('click', function(e) {
                    e.stopPropagation();
                    e.preventDefault();
                    var menuItem = toggle.closest('.kt-menu-item');
                    if (!menuItem) {
                        return;
                    }
                    var dropdown = menuItem.querySelector('.kt-menu-dropdown');
                    if (!dropdown) {
                        return;
                    }
                    var isShowing = menuItem.classList.contains('show');
                    document.querySelectorAll('#companies_table .kt-menu-item.show').forEach(function(item) {
                        if (item !== menuItem) {
                            item.classList.remove('show');
                            var otherDropdown = item.querySelector('.kt-menu-dropdown');
                            if (otherDropdown) {
                                otherDropdown.style.display = 'none';
                            }
                        }
                    });
                    if (!isShowing) {
                        menuItem.classList.add('show');
                        var rect = toggle.getBoundingClientRect();
                        dropdown.style.position = 'fixed';
                        dropdown.style.left = Math.max(8, rect.right - 175) + 'px';
                        dropdown.style.top = (rect.bottom + 5) + 'px';
                        dropdown.style.minWidth = '175px';
                        dropdown.style.width = '175px';
                        dropdown.style.zIndex = '99999';
                        dropdown.style.display = 'block';
                        dropdown.style.visibility = 'visible';
                        dropdown.style.opacity = '1';
                    } else {
                        menuItem.classList.remove('show');
                        dropdown.style.display = 'none';
                    }
                });
            });
        }

        function initCompanyRowLinks() {
            document.querySelectorAll('#companies_table tr.company-row[data-row-href]').forEach(function(row) {
                if (row._companyRowBound) {
                    return;
                }
                row._companyRowBound = true;
                row.addEventListener('click', function(event) {
                    if (event.target.closest('[data-no-row-link]')) {
                        return;
                    }
                    var href = row.getAttribute('data-row-href');
                    if (href) {
                        window.location.href = href;
                    }
                });
            });
        }

        window.initCompaniesTablePage = function() {
            initCompanyMenus();
            initCompanyRowLinks();
        };

        window.initCompaniesTablePage();

        document.addEventListener('click', function(e) {
            if (e.target.closest('#companies_table .kt-menu')) {
                return;
            }
            document.querySelectorAll('#companies_table .kt-menu-item.show').forEach(function(item) {
                item.classList.remove('show');
                var dropdown = item.querySelector('.kt-menu-dropdown');
                if (dropdown) {
                    dropdown.style.display = 'none';
                }
            });
        });

        var companiesTable = document.getElementById('companies_table');
        if (companiesTable) {
            companiesTable.addEventListener('admin-datatable:rendered', function() {
                window.initCompaniesTablePage();
            });
        }
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

@push('scripts')
<script src="{{ asset('assets/js/search-input-clear.js') }}"></script>
@endpush

@endsection
