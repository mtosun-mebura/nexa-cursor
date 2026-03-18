@extends('admin.layouts.app')

@section('title', 'Voertuigen')

@section('content')
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5">
        <h1 class="text-xl font-medium leading-none text-mono">
            Voertuigen
        </h1>
        @if(auth()->user()->hasRole('super-admin') || auth()->user()->can('vehicles.create'))
        <a href="{{ route('admin.taxiroyaal.vehicles.create') }}" class="kt-btn kt-btn-primary">
            <svg class="w-4 h-4 me-2 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            Nieuw voertuig
        </a>
        @endif
    </div>

    @if(session('success'))
        <div class="kt-alert kt-alert-success mb-5" id="success-alert" role="alert">
            <i class="ki-filled ki-check-circle me-2"></i> {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="kt-alert kt-alert-danger mb-5">
            <i class="ki-filled ki-cross-circle me-2"></i> {{ session('error') }}
        </div>
    @endif

    <div class="kt-card mb-5">
        <div class="kt-card-content">
            <div class="flex flex-col sm:flex-row lg:px-10 py-1.5 gap-2">
                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                    <span class="text-mono text-2xl leading-none font-semibold">{{ $activeCount ?? 0 }}</span>
                    <span class="text-secondary-foreground text-sm">Actief</span>
                </div>
                <span class="hidden sm:block border-e border-e-input my-1"></span>
                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                    <span class="text-mono text-2xl leading-none font-semibold">{{ $inactiveCount ?? 0 }}</span>
                    <span class="text-secondary-foreground text-sm">Inactief</span>
                </div>
                <span class="hidden sm:block border-e border-e-input my-1"></span>
                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                    <span class="text-mono text-2xl leading-none font-semibold">{{ $vehicles->total() }}</span>
                    <span class="text-secondary-foreground text-sm">Totaal</span>
                </div>
            </div>
        </div>
    </div>

    <div class="grid gap-5 lg:gap-7.5">
        <div class="kt-card kt-card-grid min-w-full">
            <div class="kt-card-header py-5 flex-wrap gap-2">
                @php
                    $from = $vehicles->isEmpty() ? 0 : (($vehicles->currentPage() - 1) * $vehicles->perPage()) + 1;
                    $to = min($vehicles->currentPage() * $vehicles->perPage(), $vehicles->total());
                @endphp
                <h3 class="kt-card-title text-sm pb-3 w-full">
                    Toon {{ $from }} tot {{ $to }} van {{ $vehicles->total() }} voertuigen
                </h3>
                <div class="flex flex-col sm:flex-row flex-wrap gap-2 lg:gap-5 justify-center sm:justify-end items-center w-full">
                    <form method="GET" action="{{ route('admin.taxiroyaal.vehicles.index') }}" class="flex gap-2" id="search-form">
                        @if(request('type'))<input type="hidden" name="type" value="{{ request('type') }}">@endif
                        @if(request('active') !== null && request('active') !== '')<input type="hidden" name="active" value="{{ request('active') }}">@endif
                        @if(request('company'))<input type="hidden" name="company" value="{{ request('company') }}">@endif
                        @if(request('sort'))<input type="hidden" name="sort" value="{{ request('sort') }}">@endif
                        @if(request('direction'))<input type="hidden" name="direction" value="{{ request('direction') }}">@endif
                        @if(request('per_page'))<input type="hidden" name="per_page" value="{{ request('per_page') }}">@endif
                        <label class="kt-input w-full sm:w-64" style="position: relative !important;">
                            <i class="ki-filled ki-magnifier"></i>
                            <input placeholder="Zoek naam of kenteken..." type="text" name="search" value="{{ request('search') }}">
                        </label>
                        <button type="submit" class="kt-btn kt-btn-outline kt-btn-sm">Zoeken</button>
                    </form>
                    <form method="GET" action="{{ route('admin.taxiroyaal.vehicles.index') }}" id="filters-form" class="flex flex-col sm:flex-row gap-2.5 w-full sm:w-auto">
                        @if(request('search'))<input type="hidden" name="search" value="{{ request('search') }}">@endif
                        @if(request('per_page'))<input type="hidden" name="per_page" value="{{ request('per_page') }}">@endif
                        <select class="kt-select w-full sm:w-36" name="active" id="active-filter">
                            <option value="">Alle statussen</option>
                            <option value="1" {{ request('active') === '1' ? 'selected' : '' }}>Actief</option>
                            <option value="0" {{ request('active') === '0' ? 'selected' : '' }}>Inactief</option>
                        </select>
                        <select class="kt-select w-full sm:w-36" name="type" id="type-filter">
                            <option value="">Alle types</option>
                            @foreach($typeLabels as $value => $label)
                                <option value="{{ $value }}" {{ request('type') === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @if($companies->count() > 0 && auth()->user()->hasRole('super-admin'))
                        <select class="kt-select w-full sm:w-36" name="company" id="company-filter">
                            <option value="">Alle bedrijven</option>
                            @foreach($companies as $c)
                                <option value="{{ $c->id }}" {{ request('company') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                            @endforeach
                        </select>
                        @endif
                        <select class="kt-select w-full sm:w-36" name="sort" id="sort-filter">
                            <option value="name" {{ request('sort', 'name') === 'name' ? 'selected' : '' }}>Naam</option>
                            <option value="type" {{ request('sort') === 'type' ? 'selected' : '' }}>Type</option>
                            <option value="license_plate" {{ request('sort') === 'license_plate' ? 'selected' : '' }}>Kenteken</option>
                            <option value="created_at" {{ request('sort') === 'created_at' ? 'selected' : '' }}>Datum</option>
                        </select>
                        <input type="hidden" name="direction" value="{{ request('direction', 'asc') }}">
                    </form>
                    @if(request('search') || request('type') || request('active') !== null || request('company') || request('sort'))
                    <a href="{{ route('admin.taxiroyaal.vehicles.index') }}" class="kt-btn kt-btn-outline kt-btn-icon" title="Filters resetten">
                        <i class="ki-filled ki-arrows-circle text-base"></i>
                    </a>
                    @endif
                </div>
            </div>
            <div class="kt-card-content">
                @if($vehicles->count() > 0)
                <div class="vehicles-table-wrap">
                <div class="kt-scrollable-x-auto">
                    <table class="kt-table table-auto kt-table-border">
                        <thead>
                            <tr>
                                <th class="w-[250px]">Foto</th>
                                <th class="min-w-[150px]">Naam</th>
                                <th class="min-w-[100px]">Type</th>
                                <th class="min-w-[120px]">Kenteken</th>
                                <th class="min-w-[100px]">Status</th>
                                <th class="w-[60px] text-center">Acties</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($vehicles as $v)
                            <tr class="vehicle-row vehicle-row--clickable" data-vehicle-id="{{ $v->id }}" data-href="{{ route('admin.taxiroyaal.vehicles.show', $v) }}">
                                <td class="w-[300px] text-center align-middle">
                                    @if($v->image_url)
                                        <img src="{{ app(\App\Services\WebsiteBuilderService::class)->storageUrlToDisplayUrl($v->image_url) }}" alt="" class="w-[220px] h-[120px] rounded-xl object-contain mx-auto block">
                                    @else
                                        <div class="w-[220px] h-[120px] rounded-xl bg-muted border border-input flex items-center justify-center mx-auto">
                                            <i class="ki-filled ki-car text-2xl text-muted-foreground"></i>
                                        </div>
                                    @endif
                                </td>
                                <td class="text-sm font-medium text-mono">{{ $v->name }}</td>
                                <td class="text-foreground font-normal">{{ $v->type_label }}</td>
                                <td class="text-foreground font-normal">{{ $v->license_plate ?? '—' }}</td>
                                <td>
                                    @if($v->active)
                                        <span class="kt-badge kt-badge-sm kt-badge-success">Actief</span>
                                    @else
                                        <span class="kt-badge kt-badge-sm kt-badge-danger">Inactief</span>
                                    @endif
                                </td>
                                <td class="w-[60px]" data-no-row-link onclick="event.stopPropagation();">
                                    <div class="kt-menu flex justify-center" data-kt-menu="true">
                                        <div class="kt-menu-item" data-kt-menu-item-offset="0, 10px" data-kt-menu-item-placement="bottom-end" data-kt-menu-item-placement-rtl="bottom-start" data-kt-menu-item-toggle="dropdown" data-kt-menu-item-trigger="click">
                                            <button class="kt-menu-toggle kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" type="button" aria-label="Acties">
                                                <svg class="w-5 h-5 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5ZM12 12.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5ZM12 18.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5Z"/>
                                                </svg>
                                            </button>
                                            <div class="kt-menu-dropdown kt-menu-default w-full max-w-[175px]" data-kt-menu-dismiss="true">
                                                @if(auth()->user()->hasRole('super-admin') || auth()->user()->can('vehicles.view'))
                                                <div class="kt-menu-item">
                                                    <a class="kt-menu-link" href="{{ route('admin.taxiroyaal.vehicles.show', $v) }}">
                                                        <span class="kt-menu-icon">
                                                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                                                        </span>
                                                        <span class="kt-menu-title">Bekijken</span>
                                                    </a>
                                                </div>
                                                @endif
                                                @if(auth()->user()->hasRole('super-admin') || auth()->user()->can('vehicles.update'))
                                                <div class="kt-menu-item">
                                                    <a class="kt-menu-link" href="{{ route('admin.taxiroyaal.vehicles.edit', $v) }}">
                                                        <span class="kt-menu-icon">
                                                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125"/></svg>
                                                        </span>
                                                        <span class="kt-menu-title">Bewerken</span>
                                                    </a>
                                                </div>
                                                @endif
                                                @if(auth()->user()->hasRole('super-admin') || auth()->user()->can('vehicles.delete'))
                                                <div class="kt-menu-separator"></div>
                                                <div class="kt-menu-item">
                                                    <form action="{{ route('admin.taxiroyaal.vehicles.destroy', $v) }}" method="POST" style="display: inline;" onsubmit="return confirm('Weet je zeker dat je dit voertuig wilt verwijderen?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="kt-menu-link w-full text-left text-danger">
                                                            <span class="kt-menu-icon">
                                                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                                                            </span>
                                                            <span class="kt-menu-title">Verwijderen</span>
                                                        </button>
                                                    </form>
                                                </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                </div>
                <div class="kt-card-footer justify-center md:justify-between flex-col md:flex-row gap-5 text-secondary-foreground text-sm font-medium pt-5">
                    <div class="flex items-center gap-2 order-2 md:order-1">
                        Toon
                        <form method="GET" action="{{ route('admin.taxiroyaal.vehicles.index') }}" class="inline" id="perpage-form">
                            @if(request('search'))<input type="hidden" name="search" value="{{ request('search') }}">@endif
                            @if(request('type'))<input type="hidden" name="type" value="{{ request('type') }}">@endif
                            @if(request('active') !== null && request('active') !== '')<input type="hidden" name="active" value="{{ request('active') }}">@endif
                            @if(request('company'))<input type="hidden" name="company" value="{{ request('company') }}">@endif
                            @if(request('sort'))<input type="hidden" name="sort" value="{{ request('sort') }}">@endif
                            @if(request('direction'))<input type="hidden" name="direction" value="{{ request('direction') }}">@endif
                            <select class="kt-select w-24" name="per_page" onchange="this.form.submit()">
                                @foreach([10, 15, 25, 50] as $n)
                                    <option value="{{ $n }}" {{ (int) request('per_page', 15) === $n ? 'selected' : '' }}>{{ $n }}</option>
                                @endforeach
                            </select>
                        </form>
                        per pagina
                    </div>
                    <div class="flex items-center gap-4 order-1 md:order-2">
                        <span>{{ $vehicles->firstItem() ?? 0 }}-{{ $vehicles->lastItem() ?? 0 }} van {{ $vehicles->total() }}</span>
                        {{ $vehicles->links() }}
                    </div>
                </div>
                @else
                <div class="flex flex-col items-center justify-center py-16">
                    <i class="ki-filled ki-information-5 text-4xl text-muted-foreground mb-4"></i>
                    <h4 class="text-lg font-semibold text-mono mb-2">Geen voertuigen gevonden</h4>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .vehicles-table-wrap td:last-child .kt-menu-dropdown { position: fixed !important; z-index: 99999 !important; }
    .vehicles-table-wrap td:last-child .kt-menu-item.show .kt-menu-dropdown,
    .vehicles-table-wrap td:last-child .kt-menu-item[data-kt-menu-item-toggle="dropdown"].show .kt-menu-dropdown { display: block !important; visibility: visible !important; opacity: 1 !important; }
    .vehicles-table-wrap td:last-child .kt-menu-item.show { z-index: 99999 !important; }
    .vehicles-table-wrap .kt-scrollable-x-auto { overflow-x: auto !important; overflow-y: visible !important; }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var filterForm = document.getElementById('filters-form');
    ['active-filter', 'type-filter', 'company-filter', 'sort-filter'].forEach(function(id) {
        var el = document.getElementById(id);
        if (el && filterForm) el.addEventListener('change', function() { filterForm.submit(); });
    });
    var successAlert = document.getElementById('success-alert');
    if (successAlert) setTimeout(function() { successAlert.style.opacity = '0'; setTimeout(function() { successAlert.remove(); }, 300); }, 3000);

    function initVehicleMenus() {
        if (window.KTMenu && typeof window.KTMenu.init === 'function') {
            try { window.KTMenu.init(); } catch (e) {}
        }
        document.querySelectorAll('.vehicles-table-wrap .kt-menu-toggle').forEach(function(toggle) {
            if (toggle._vehicleMenuBound) return;
            toggle._vehicleMenuBound = true;
            toggle.addEventListener('click', function(e) {
                e.stopPropagation();
                e.preventDefault();
                var menuItem = toggle.closest('.kt-menu-item');
                if (!menuItem) return;
                var dropdown = menuItem.querySelector('.kt-menu-dropdown');
                if (!dropdown) return;
                var isShowing = menuItem.classList.contains('show');
                document.querySelectorAll('.vehicles-table-wrap .kt-menu-item.show').forEach(function(item) {
                    if (item !== menuItem) {
                        item.classList.remove('show');
                        var d = item.querySelector('.kt-menu-dropdown');
                        if (d) d.style.display = 'none';
                    }
                });
                if (!isShowing) {
                    menuItem.classList.add('show');
                    var rect = toggle.getBoundingClientRect();
                    dropdown.style.position = 'fixed';
                    dropdown.style.left = (rect.right - 175) + 'px';
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
    initVehicleMenus();
    setTimeout(initVehicleMenus, 300);

    document.querySelectorAll('.vehicle-row--clickable').forEach(function(row) {
        if (row._rowLinkBound) return;
        row._rowLinkBound = true;
        row.style.cursor = 'pointer';
        row.addEventListener('click', function(e) {
            if (e.target.closest('a, button, input, select, textarea, [data-no-row-link]')) return;
            var href = row.getAttribute('data-href');
            if (href) window.location.href = href;
        });
    });

    document.addEventListener('click', function(e) {
        if (e.target.closest('.vehicles-table-wrap .kt-menu')) return;
        document.querySelectorAll('.vehicles-table-wrap .kt-menu-item.show').forEach(function(item) {
            item.classList.remove('show');
            var d = item.querySelector('.kt-menu-dropdown');
            if (d) d.style.display = 'none';
        });
    });
});
</script>
@endpush
@endsection
