@extends('admin.layouts.app')

@section('title', 'Vacatures Beheer')

@section('content')

<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5">
        <h1 class="text-xl font-medium leading-none text-mono">
            Vacatures Beheer
        </h1>
        @can('create-vacancies')
        <a href="{{ route('admin.vacancies.create') }}" class="kt-btn kt-btn-primary">
            <i class="ki-filled ki-plus me-2"></i>
            Nieuwe Vacature
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

    <!-- Statistics Cards (same style as Users overview) -->
    <div class="kt-card mb-5">
        <div class="kt-card-content">
            <div class="flex lg:px-10 py-1.5 gap-2">
                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                    <span class="text-mono text-2xl lg:text-2xl leading-none font-semibold">
                        {{ $statusStats['Open'] ?? 0 }}
                    </span>
                    <span class="text-secondary-foreground text-sm">Open</span>
                </div>
                <span class="not-last:border-e border-e-input my-1"></span>
                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                    <span class="text-mono text-2xl lg:text-2xl leading-none font-semibold">
                        {{ $statusStats['In behandeling'] ?? 0 }}
                    </span>
                    <span class="text-secondary-foreground text-sm">In behandeling</span>
                </div>
                <span class="not-last:border-e border-e-input my-1"></span>
                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                    <span class="text-mono text-2xl lg:text-2xl leading-none font-semibold">
                        {{ $statusStats['Gesloten'] ?? 0 }}
                    </span>
                    <span class="text-secondary-foreground text-sm">Gesloten</span>
                </div>
                <span class="not-last:border-e border-e-input my-1"></span>
                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                    <span class="text-mono text-2xl lg:text-2xl leading-none font-semibold">
                        {{ $vacancies->count() }}
                    </span>
                    <span class="text-secondary-foreground text-sm">Totaal</span>
                </div>
            </div>
        </div>
    </div>

    <div class="grid gap-5 lg:gap-7.5">
        <div class="kt-card kt-card-grid min-w-full">
            <div class="kt-card-header py-5 flex-wrap gap-2">
                <h3 class="kt-card-title text-sm pb-3 w-full">
                    Toon 1 tot {{ $vacancies->count() }} van {{ $vacancies->count() }} vacatures
                </h3>

                <div class="flex flex-wrap gap-2 lg:gap-5 justify-end w-full">
                    <!-- Search -->
                    <div class="flex">
                        <form method="GET" action="{{ route('admin.vacancies.index') }}" class="flex gap-2" id="search-form">
                            @if(request('status'))
                                <input type="hidden" name="status" value="{{ request('status') }}">
                            @endif
                            @if(request('branch_id'))
                                <input type="hidden" name="branch_id" value="{{ request('branch_id') }}">
                            @endif
                            @if(request('company_id'))
                                <input type="hidden" name="company_id" value="{{ request('company_id') }}">
                            @endif
                            @if(request('sort'))
                                <input type="hidden" name="sort" value="{{ request('sort') }}">
                            @endif
                            @if(request('direction'))
                                <input type="hidden" name="direction" value="{{ request('direction') }}">
                            @endif
                            <label class="kt-input" style="position: relative !important;">
                                <i class="ki-filled ki-magnifier"></i>
                                <input placeholder="Zoek vacatures..."
                                       type="text"
                                       name="search"
                                       value="{{ request('search') }}"
                                       id="search-input"
                                       data-kt-datatable-search="#vacancies_table"/>
                            </label>
                        </form>
                    </div>

                    <!-- Filters -->
                    <div class="flex flex-wrap gap-2.5 items-center">
                        <form method="GET" action="{{ route('admin.vacancies.index') }}" id="filters-form" class="flex gap-2.5">
                            @if(request('search'))
                                <input type="hidden" name="search" value="{{ request('search') }}">
                            @endif
                            @if(request('sort'))
                                <input type="hidden" name="sort" value="{{ request('sort') }}">
                            @endif
                            @if(request('direction'))
                                <input type="hidden" name="direction" value="{{ request('direction') }}">
                            @endif

                            <select class="kt-select w-48"
                                    name="status"
                                    data-kt-select="true"
                                    data-kt-select-placeholder="Status"
                                    id="status-filter">
                                <option value="">Alle statussen</option>
                                <option value="Open" {{ request('status') == 'Open' ? 'selected' : '' }}>Open</option>
                                <option value="In behandeling" {{ request('status') == 'In behandeling' ? 'selected' : '' }}>In behandeling</option>
                                <option value="Gesloten" {{ request('status') == 'Gesloten' ? 'selected' : '' }}>Gesloten</option>
                            </select>

                            <select class="kt-select w-56"
                                    name="branch_id"
                                    data-kt-select="true"
                                    data-kt-select-placeholder="Branch"
                                    id="branch-filter">
                                <option value="">Alle branches</option>
                                @foreach($branches ?? [] as $branch)
                                    <option value="{{ $branch->id }}" {{ (string)request('branch_id') === (string)$branch->id ? 'selected' : '' }}>
                                        {{ $branch->name }}
                                    </option>
                                @endforeach
                            </select>

                            @if(auth()->user()->hasRole('super-admin'))
                            <select class="kt-select w-56"
                                    name="company_id"
                                    data-kt-select="true"
                                    data-kt-select-placeholder="Bedrijf"
                                    id="company-filter">
                                <option value="">Alle bedrijven</option>
                                @foreach($companies ?? [] as $company)
                                    <option value="{{ $company->id }}" {{ (string)request('company_id') === (string)$company->id ? 'selected' : '' }}>
                                        {{ $company->name }}
                                    </option>
                                @endforeach
                            </select>
                            @endif

                            @if(request('status') || request('branch_id') || request('company_id') || request('search') || request('sort') || request('direction'))
                            <a href="{{ route('admin.vacancies.index') }}"
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
                @if($vacancies->count() > 0)
                    <div class="grid" data-kt-datatable="true" data-kt-datatable-page-size="10" id="vacancies_table">
                        <div class="kt-scrollable-x-auto">
                            <table class="kt-table table-auto kt-table-border" data-kt-datatable-table="true">
                                <thead>
                                    <tr>
                                        <th class="min-w-[320px]">
                                            <span class="kt-table-col">
                                                <span class="kt-table-col-label">Vacature</span>
                                                <span class="kt-table-col-sort">
                                                    @php
                                                        $currentSort = request('sort');
                                                        $currentDirection = request('direction');
                                                        $nextDirection = ($currentSort == 'title' && $currentDirection == 'asc') ? 'desc' : 'asc';
                                                    @endphp
                                                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'title', 'direction' => $nextDirection]) }}" class="kt-table-col-sort-btn"></a>
                                                </span>
                                            </span>
                                        </th>
                                        <th class="min-w-[180px]">
                                            <span class="kt-table-col">
                                                <span class="kt-table-col-label">Bedrijf</span>
                                            </span>
                                        </th>
                                        <th class="min-w-[160px]">
                                            <span class="kt-table-col">
                                                <span class="kt-table-col-label">Branch</span>
                                            </span>
                                        </th>
                                        <th class="min-w-[120px]">
                                            <span class="kt-table-col">
                                                <span class="kt-table-col-label">Status</span>
                                                <span class="kt-table-col-sort">
                                                    @php
                                                        $currentSort = request('sort');
                                                        $currentDirection = request('direction');
                                                        $nextDirection = ($currentSort == 'status' && $currentDirection == 'asc') ? 'desc' : 'asc';
                                                    @endphp
                                                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'status', 'direction' => $nextDirection]) }}" class="kt-table-col-sort-btn"></a>
                                                </span>
                                            </span>
                                        </th>
                                        <th class="min-w-[120px]">
                                            <span class="kt-table-col">
                                                <span class="kt-table-col-label">Matches</span>
                                                <span class="kt-table-col-sort">
                                                    @php
                                                        $currentSort = request('sort');
                                                        $currentDirection = request('direction');
                                                        $nextDirection = ($currentSort == 'matches_count' && $currentDirection == 'desc') ? 'asc' : 'desc';
                                                    @endphp
                                                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'matches_count', 'direction' => $nextDirection]) }}" class="kt-table-col-sort-btn"></a>
                                                </span>
                                            </span>
                                        </th>
                                        <th class="min-w-[150px]">
                                            <span class="kt-table-col">
                                                <span class="kt-table-col-label">Publicatie</span>
                                                <span class="kt-table-col-sort">
                                                    @php
                                                        $currentSort = request('sort');
                                                        $currentDirection = request('direction');
                                                        $nextDirection = ($currentSort == 'publication_date' && $currentDirection == 'desc') ? 'asc' : 'desc';
                                                    @endphp
                                                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'publication_date', 'direction' => $nextDirection]) }}" class="kt-table-col-sort-btn"></a>
                                                </span>
                                            </span>
                                        </th>
                                        <th class="w-[60px] text-center">Acties</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($vacancies as $vacancy)
                                        <tr class="vacancy-row" data-vacancy-id="{{ $vacancy->id }}">
                                            <td>
                                                <div class="flex flex-col">
                                                    <a class="text-sm font-medium text-mono hover:text-primary mb-px" href="{{ route('admin.vacancies.show', $vacancy) }}" data-vacancy-id="{{ $vacancy->id }}">
                                                        {{ $vacancy->title }}
                                                    </a>
                                                    @if($vacancy->location)
                                                        <span class="text-xs text-muted-foreground">{{ $vacancy->location }}</span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="text-foreground font-normal">
                                                {{ $vacancy->company?->name ?? '-' }}
                                            </td>
                                            <td class="text-foreground font-normal">
                                                {{ $vacancy->branch?->name ?? '-' }}
                                            </td>
                                            <td>
                                                @php $status = (string)($vacancy->status ?? ''); @endphp
                                                @if($status === 'Open')
                                                    <span class="kt-badge kt-badge-sm kt-badge-success">Open</span>
                                                @elseif($status === 'Gesloten')
                                                    <span class="kt-badge kt-badge-sm kt-badge-danger">Gesloten</span>
                                                @elseif($status === 'In behandeling')
                                                    <span class="kt-badge kt-badge-sm kt-badge-warning">In behandeling</span>
                                                @else
                                                    <span class="kt-badge kt-badge-sm kt-badge-secondary">{{ $status ?: '-' }}</span>
                                                @endif
                                            </td>
                                            <td class="text-foreground font-normal">
                                                <a class="text-sm hover:text-primary" href="{{ route('admin.matches.index', ['vacancy' => $vacancy->id]) }}" onclick="event.stopPropagation();">
                                                    {{ $vacancy->matches_count ?? 0 }}
                                                </a>
                                            </td>
                                            <td class="text-foreground font-normal">
                                                <span class="text-sm">{{ optional($vacancy->publication_date)->format('d-m-Y') ?? '-' }}</span>
                                            </td>
                                            <td class="w-[60px]" onclick="event.stopPropagation();">
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
                                                            @can('view-vacancies')
                                                                <div class="kt-menu-item">
                                                                    <a class="kt-menu-link" href="{{ route('admin.vacancies.show', $vacancy) }}">
                                                                        <span class="kt-menu-icon"><i class="ki-filled ki-eye"></i></span>
                                                                        <span class="kt-menu-title">Bekijken</span>
                                                                    </a>
                                                                </div>
                                                            @endcan
                                                            @can('edit-vacancies')
                                                                <div class="kt-menu-item">
                                                                    <a class="kt-menu-link" href="{{ route('admin.vacancies.edit', $vacancy) }}">
                                                                        <span class="kt-menu-icon"><i class="ki-filled ki-pencil"></i></span>
                                                                        <span class="kt-menu-title">Bewerken</span>
                                                                    </a>
                                                                </div>
                                                            @endcan
                                                            @if($vacancy->matches_count > 0)
                                                                <div class="kt-menu-separator"></div>
                                                                <div class="kt-menu-item">
                                                                    <a class="kt-menu-link" href="{{ route('admin.matches.candidates', $vacancy->id) }}">
                                                                        <span class="kt-menu-icon">
                                                                            <i class="ki-filled ki-people"></i>
                                                                        </span>
                                                                        <span class="kt-menu-title" style="position: relative;">
                                                                            Kandidaten
                                                                            <span style="position: absolute; top: -4px; right: -8px; background-color: #3b82f6; color: white; border-radius: 50%; width: 18px; height: 18px; display: flex; align-items: center; justify-content: center; font-size: 0.65rem; font-weight: 600;">{{ $vacancy->matches_count }}</span>
                                                                        </span>
                                                                    </a>
                                                                </div>
                                                            @endif
                                                            @can('delete-vacancies')
                                                                <div class="kt-menu-separator"></div>
                                                                <div class="kt-menu-item">
                                                                    <form action="{{ route('admin.vacancies.destroy', $vacancy) }}" method="POST" onsubmit="return confirm('Weet je zeker dat je deze vacature wilt verwijderen?')">
                                                                        @csrf
                                                                        @method('DELETE')
                                                                        <button type="submit" class="kt-menu-link w-full text-left text-danger">
                                                                            <span class="kt-menu-icon"><i class="ki-filled ki-trash"></i></span>
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
                        <h4 class="text-lg font-semibold text-mono mb-2">Geen vacatures gevonden</h4>
                        <p class="text-sm text-secondary-foreground mb-6">Er zijn nog geen vacatures aangemaakt.</p>
                        @can('create-vacancies')
                        <a href="{{ route('admin.vacancies.create') }}" class="kt-btn kt-btn-primary">
                            <i class="ki-filled ki-plus me-2"></i> Nieuwe Vacature
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

        // Filter form autosubmit
        const filterForm = document.getElementById('filters-form');
        const statusFilter = document.getElementById('status-filter');
        const branchFilter = document.getElementById('branch-filter');
        const companyFilter = document.getElementById('company-filter');

        if (statusFilter && filterForm) statusFilter.addEventListener('change', () => filterForm.submit());
        if (branchFilter && filterForm) branchFilter.addEventListener('change', () => filterForm.submit());
        if (companyFilter && filterForm) companyFilter.addEventListener('change', () => filterForm.submit());

        // Replace "of" with "van" in pagination info
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

        // Make table rows clickable (except actions column)
        document.querySelectorAll('tbody tr.vacancy-row').forEach(function(row) {
            row.addEventListener('click', function(e) {
                if (e.target.closest('td:last-child') || e.target.closest('.kt-menu') || e.target.closest('button') || e.target.closest('a')) {
                    return;
                }

                const link = this.querySelector('td:first-child a[data-vacancy-id]');
                if (link) {
                    window.location.href = link.getAttribute('href');
                }
            });
        });
    });
</script>
<script src="{{ asset('assets/js/search-input-clear.js') }}"></script>
@endpush

@push('styles')
<style>
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

    /* Table row hover styling */
    .vacancy-row {
        cursor: pointer !important;
    }
    .vacancy-row:hover {
        background-color: var(--muted) !important;
    }
    @supports (color: color-mix(in lab, red, red)) {
        .vacancy-row:hover {
            background-color: color-mix(in oklab, var(--muted) 50%, transparent) !important;
        }
    }
</style>
@endpush

@endsection
