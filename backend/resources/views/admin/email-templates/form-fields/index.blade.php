@extends('admin.layouts.app')

@section('title', 'Formulier velden beheer')

@section('content')

<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5">
        <div>
            <h1 class="text-xl font-medium leading-none text-mono">
                Formulier velden
            </h1>
            <p class="text-sm text-muted-foreground mt-1">Velden voor het formulier &quot;Informatie aanvragen&quot; op de website. Verplicht en validatie worden bij de POST gecontroleerd.</p>
        </div>
        <a href="{{ route('admin.email-templates.form-fields.create') }}" class="kt-btn kt-btn-primary">
            <i class="ki-filled ki-plus me-2"></i>
            Veld toevoegen
        </a>
    </div>

    @if(session('success'))
        <div class="kt-alert kt-alert-success mb-5" id="form-fields-success-alert" role="alert">
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
                        {{ $stats['total'] ?? 0 }}
                    </span>
                    <span class="text-secondary-foreground text-sm">
                        Totaal velden
                    </span>
                </div>
                <span class="hidden sm:block not-last:border-e border-e-input my-1"></span>
                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                    <span class="text-mono text-2xl lg:text-2xl leading-none font-semibold">
                        {{ $stats['required'] ?? 0 }}
                    </span>
                    <span class="text-secondary-foreground text-sm">
                        Verplicht
                    </span>
                </div>
                <span class="hidden sm:block not-last:border-e border-e-input my-1"></span>
                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                    <span class="text-mono text-2xl lg:text-2xl leading-none font-semibold">
                        {{ $stats['optional'] ?? 0 }}
                    </span>
                    <span class="text-secondary-foreground text-sm">
                        Optioneel
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="grid gap-5 lg:gap-7.5">
        <div class="kt-card kt-card-grid min-w-full">
            <div class="kt-card-header py-5 flex-wrap gap-2">
                <h3 class="kt-card-title text-sm pb-3 w-full">
                    Toon 1 tot {{ $fields->count() }} van {{ $fields->count() }} velden
                </h3>
                <div class="flex flex-col sm:flex-row flex-wrap gap-2 lg:gap-5 justify-center sm:justify-end items-center w-full">
                    <!-- Search -->
                    <div class="flex w-full sm:w-auto justify-center sm:justify-start">
                        <form method="GET" action="{{ route('admin.email-templates.form-fields.index') }}" class="flex gap-2" id="search-form">
                            @if(request('required'))
                                <input type="hidden" name="required" value="{{ request('required') }}">
                            @endif
                            @if(request('validation'))
                                <input type="hidden" name="validation" value="{{ request('validation') }}">
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
                            <label class="kt-input w-full sm:w-64" style="position: relative !important;">
                                <i class="ki-filled ki-magnifier"></i>
                                <input placeholder="Zoek velden..."
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
                        <form method="GET" action="{{ route('admin.email-templates.form-fields.index') }}" id="filters-form" class="flex flex-col sm:flex-row gap-2.5 w-full sm:w-auto items-center sm:items-stretch">
                            @if(request('search'))
                                <input type="hidden" name="search" value="{{ request('search') }}">
                            @endif

                            <select class="kt-select w-full sm:w-36"
                                    name="required"
                                    data-kt-select="true"
                                    data-kt-select-placeholder="Verplicht"
                                    id="required-filter">
                                <option value="">Alle</option>
                                <option value="1" {{ request('required') === '1' ? 'selected' : '' }}>Ja</option>
                                <option value="0" {{ request('required') === '0' ? 'selected' : '' }}>Nee</option>
                            </select>

                            <select class="kt-select w-full sm:w-36"
                                    name="validation"
                                    data-kt-select="true"
                                    data-kt-select-placeholder="Validatie"
                                    id="validation-filter">
                                <option value="">Alle</option>
                                <option value="email" {{ request('validation') === 'email' ? 'selected' : '' }}>email</option>
                                <option value="tel" {{ request('validation') === 'tel' ? 'selected' : '' }}>tel</option>
                                <option value="none" {{ request('validation') === 'none' ? 'selected' : '' }}>Geen</option>
                            </select>

                            <select class="kt-select w-full sm:w-36"
                                    name="sort"
                                    data-kt-select="true"
                                    data-kt-select-placeholder="Sortering"
                                    id="sort-filter">
                                <option value="sort_order" {{ request('sort', 'sort_order') === 'sort_order' ? 'selected' : '' }}>Volgorde</option>
                                <option value="name" {{ request('sort') === 'name' ? 'selected' : '' }}>Naam (slug)</option>
                                <option value="label" {{ request('sort') === 'label' ? 'selected' : '' }}>Label</option>
                                <option value="created_at" {{ request('sort') === 'created_at' ? 'selected' : '' }}>Datum</option>
                            </select>
                            <input type="hidden" name="direction" value="{{ request('direction', 'asc') }}">
                        </form>
                        @if(request('required') || request('validation') || request('search') || (request('sort') && request('sort') !== 'sort_order') || request('direction'))
                        <a href="{{ route('admin.email-templates.form-fields.index') }}"
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
                @if($fields->count() > 0)
                    <div class="grid" data-admin-datatable="true" data-admin-datatable-page-size="10" id="form_fields_table" data-admin-datatable-label="velden">
                        <div class="kt-scrollable-x-auto">
                            <table class="kt-table table-auto kt-table-border">
                                <thead>
                                    @php
                                        $currentSort = request('sort', 'sort_order');
                                        $currentDirection = request('direction', 'asc');
                                    @endphp
                                    <tr>
                                        <th class="min-w-[140px]">
                                            <span class="kt-table-col">
                                                <span class="kt-table-col-label">Naam (slug)</span>
                                                <span class="kt-table-col-sort">
                                                    @php $nextDir = ($currentSort === 'name' && $currentDirection === 'asc') ? 'desc' : 'asc'; @endphp
                                                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'name', 'direction' => $currentSort === 'name' ? $nextDir : 'asc']) }}" class="kt-table-col-sort-btn"></a>
                                                </span>
                                            </span>
                                        </th>
                                        <th class="min-w-[180px]">
                                            <span class="kt-table-col">
                                                <span class="kt-table-col-label">Label</span>
                                                <span class="kt-table-col-sort">
                                                    @php $nextDir = ($currentSort === 'label' && $currentDirection === 'asc') ? 'desc' : 'asc'; @endphp
                                                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'label', 'direction' => $currentSort === 'label' ? $nextDir : 'asc']) }}" class="kt-table-col-sort-btn"></a>
                                                </span>
                                            </span>
                                        </th>
                                        <th class="min-w-[100px]">
                                            <span class="kt-table-col">
                                                <span class="kt-table-col-label">Verplicht</span>
                                            </span>
                                        </th>
                                        <th class="min-w-[100px]">
                                            <span class="kt-table-col">
                                                <span class="kt-table-col-label">Validatie</span>
                                            </span>
                                        </th>
                                        <th class="min-w-[90px]">
                                            <span class="kt-table-col">
                                                <span class="kt-table-col-label">Volgorde</span>
                                                <span class="kt-table-col-sort">
                                                    @php $nextDir = ($currentSort === 'sort_order' && $currentDirection === 'asc') ? 'desc' : 'asc'; @endphp
                                                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'sort_order', 'direction' => $currentSort === 'sort_order' ? $nextDir : 'asc']) }}" class="kt-table-col-sort-btn"></a>
                                                </span>
                                            </span>
                                        </th>
                                        <th class="w-[60px] text-center">Acties</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($fields as $field)
                                        <tr class="form-field-row" data-field-id="{{ $field->id }}">
                                            <td class="form-field-cell-link">
                                                <a href="{{ route('admin.email-templates.form-fields.edit', $field) }}" class="block min-h-full py-2 no-underline text-inherit">
                                                    <span class="text-sm font-mono font-medium">{{ $field->name }}</span>
                                                </a>
                                            </td>
                                            <td class="text-foreground font-normal form-field-cell-link">
                                                <a href="{{ route('admin.email-templates.form-fields.edit', $field) }}" class="block min-h-full py-2 no-underline text-inherit">
                                                    <span class="text-sm">{{ $field->label }}{{ $field->is_required ? ' *' : '' }}</span>
                                                </a>
                                            </td>
                                            <td class="form-field-cell-link">
                                                <a href="{{ route('admin.email-templates.form-fields.edit', $field) }}" class="block min-h-full py-2 no-underline text-inherit">
                                                    @if($field->is_required)
                                                        <span class="kt-badge kt-badge-sm kt-badge-success">Ja</span>
                                                    @else
                                                        <span class="kt-badge kt-badge-sm kt-badge-secondary">Nee</span>
                                                    @endif
                                                </a>
                                            </td>
                                            <td class="text-foreground font-normal form-field-cell-link">
                                                <a href="{{ route('admin.email-templates.form-fields.edit', $field) }}" class="block min-h-full py-2 no-underline text-inherit">
                                                    <span class="text-sm text-muted-foreground">{{ $field->validation_rule ?: '—' }}</span>
                                                </a>
                                            </td>
                                            <td class="text-foreground font-normal form-field-cell-link">
                                                <a href="{{ route('admin.email-templates.form-fields.edit', $field) }}" class="block min-h-full py-2 no-underline text-inherit">
                                                    <span class="text-sm">{{ $field->sort_order }}</span>
                                                </a>
                                            </td>
                                            <td class="w-[60px] form-fields-actions-col" onclick="event.stopPropagation();">
                                                <div class="kt-menu flex justify-center" data-kt-menu="true">
                                                    <div class="kt-menu-item" data-kt-menu-item-offset="0, 10px" data-kt-menu-item-placement="bottom-end" data-kt-menu-item-placement-rtl="bottom-start" data-kt-menu-item-toggle="dropdown" data-kt-menu-item-trigger="click">
                                                        <button class="kt-menu-toggle kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost">
                                                            <i class="ki-filled ki-dots-vertical text-lg"></i>
                                                        </button>
                                                        <div class="kt-menu-dropdown kt-menu-default w-full max-w-[175px]" data-kt-menu-dismiss="true">
                                                            <div class="kt-menu-item">
                                                                <a class="kt-menu-link" href="{{ route('admin.email-templates.form-fields.edit', $field) }}">
                                                                    <span class="kt-menu-icon">
                                                                        <i class="ki-filled ki-pencil"></i>
                                                                    </span>
                                                                    <span class="kt-menu-title">Bewerken</span>
                                                                </a>
                                                            </div>
                                                            <div class="kt-menu-separator"></div>
                                                            <div class="kt-menu-item">
                                                                <form action="{{ route('admin.email-templates.form-fields.destroy', $field) }}"
                                                                      method="POST"
                                                                      style="display: inline;"
                                                                      onsubmit="return confirm('Weet u zeker dat u dit veld wilt verwijderen?');">
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
                        <h4 class="text-lg font-semibold text-mono mb-2">Geen formuliervelden gevonden</h4>
                        <p class="text-muted-foreground text-center mb-4">Voeg een veld toe of run de seeder voor de standaardvelden (Voornaam, Achternaam, E-mailadres, Telefoonnummer, Omschrijving).</p>
                        <a href="{{ route('admin.email-templates.form-fields.create') }}" class="kt-btn kt-btn-primary">
                            <i class="ki-filled ki-plus me-2"></i>
                            Veld toevoegen
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="mt-4">
        <a href="{{ route('admin.email-templates.index') }}" class="kt-btn kt-btn-outline">
            <i class="ki-filled ki-arrow-left me-2"></i>
            Terug naar E-mail templates
        </a>
    </div>
</div>

@push('scripts')
<script src="{{ asset('assets/js/search-input-clear.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        function replaceOfWithVan() {
            const infoSpan = document.querySelector('[data-admin-datatable-info="true"]');
            if (infoSpan && infoSpan.textContent.includes(' of ')) {
                infoSpan.textContent = infoSpan.textContent.replace(' of ', ' van ');
            }
        }
        replaceOfWithVan();
        const infoSpan = document.querySelector('[data-admin-datatable-info="true"]');
        if (infoSpan) {
            const observer = new MutationObserver(function() { replaceOfWithVan(); });
            observer.observe(infoSpan, { childList: true, characterData: true, subtree: true });
        }

        // Filter form submission
        var filterForm = document.getElementById('filters-form');
        ['required-filter', 'validation-filter', 'sort-filter'].forEach(function(id) {
            var el = document.getElementById(id);
            if (el && filterForm) {
                el.addEventListener('change', function() { filterForm.submit(); });
            }
        });

        // Row click -> edit
        document.addEventListener('click', function(e) {
            var row = e.target.closest('tr[data-field-id]');
            var inActions = !!e.target.closest('.form-fields-actions-col');
            var inMenu = !!e.target.closest('.kt-menu');
            if (!row || inActions || inMenu) return;
            if (e.target.closest('a[href*="form-fields"]')) return;
            var id = row.getAttribute('data-field-id');
            if (id) window.location.href = '/admin/email-templates/form-fields/' + id + '/edit';
        }, true);

        // Auto-dismiss success
        var successAlert = document.getElementById('form-fields-success-alert');
        if (successAlert) {
            setTimeout(function() {
                successAlert.style.transition = 'opacity 0.3s ease-out';
                successAlert.style.opacity = '0';
                setTimeout(function() { successAlert.remove(); }, 300);
            }, 3000);
        }
    });
</script>
@endpush

@push('styles')
<style>
    .kt-table-col { display: flex !important; justify-content: space-between !important; align-items: center !important; width: 100% !important; }
    .kt-table-col-sort { margin-left: auto !important; }
    a[title="Filters resetten"] {
        display: inline-flex !important; visibility: visible !important; opacity: 1 !important;
        min-width: 34px !important; height: 34px !important; align-items: center !important; justify-content: center !important;
        border: 1px solid var(--input) !important; background-color: var(--background) !important; color: var(--secondary-foreground) !important;
    }
    a[title="Filters resetten"]:hover { background-color: var(--accent) !important; color: var(--accent-foreground) !important; }
    .form-field-cell-link a { cursor: pointer; min-height: 100%; }
    .form-field-row { cursor: pointer !important; }
    .form-field-row:hover { background-color: var(--muted) !important; }
</style>
@endpush

@endsection
