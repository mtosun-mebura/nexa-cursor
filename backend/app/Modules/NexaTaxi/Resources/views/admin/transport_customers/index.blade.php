@extends('admin.layouts.app')

@section('title', 'Contractklanten')

@section('content')
<div class="kt-container-fixed min-w-0">
    <div class="flex flex-wrap items-center justify-between gap-3 pb-7.5">
        <h1 class="text-xl font-medium leading-none text-mono">Contractklanten</h1>
        @can('rides.create')
        <a href="{{ route('admin.taxi.transport_customers.create') }}" class="kt-btn kt-btn-primary shrink-0">
            <svg class="w-4 h-4 me-2 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            Nieuwe klant
        </a>
        @endcan
    </div>

    @if(session('success'))
        <div class="kt-alert kt-alert-success mb-5" role="alert">
            <i class="ki-filled ki-check-circle me-2"></i> {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="kt-alert kt-alert-danger mb-5">
            <i class="ki-filled ki-cross-circle me-2"></i> {{ session('error') }}
        </div>
    @endif

    <div class="grid gap-5 lg:gap-7.5">
        <div class="kt-card kt-card-grid w-full min-w-0">
            <div class="kt-card-header py-5 flex-wrap gap-2 min-w-0">
                <h3 class="kt-card-title text-sm pb-3 w-full mb-0">
                    {{ $customers->total() }} klant{{ $customers->total() !== 1 ? 'en' : '' }}
                </h3>
                <form method="GET" class="admin-filter-panel flex flex-col sm:flex-row flex-wrap gap-2.5 w-full sm:w-auto min-w-0 items-stretch sm:items-center">
                    <label class="kt-input w-full sm:w-64 min-w-0">
                        <i class="ki-filled ki-magnifier"></i>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Zoek op naam, e-mail...">
                    </label>
                    <select name="active" class="kt-select w-full sm:w-44">
                        <option value="">Alle statussen</option>
                        <option value="1" @selected(request('active') === '1')>Actief</option>
                        <option value="0" @selected(request('active') === '0')>Inactief</option>
                    </select>
                    <button type="submit" class="kt-btn kt-btn-outline w-full sm:w-auto shrink-0">Filteren</button>
                </form>
            </div>
            <div class="kt-card-content p-0 min-w-0">
                <div class="transport-customers-table-wrap min-w-0">
                <div class="kt-scrollable-x-auto admin-table-scroll-wrap">
                    <table id="transport-customers-table" class="kt-table kt-table-border admin-fluid-table align-middle text-sm w-full">
                        <thead>
                            <tr>
                                <th data-label="Naam">Naam</th>
                                <th data-label="Contact">Contact</th>
                                <th data-label="Debiteurnr.">Debiteurnr.</th>
                                <th class="transport-customers-table__status-col" data-label="Status">Status</th>
                                <th class="transport-customers-table__actions-col text-secondary-foreground font-normal text-center" data-label="Acties">Acties</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($customers as $customer)
                            <tr
                                data-row-href="{{ route('admin.taxi.transport_customers.show', $customer->id) }}"
                                class="transport-customers-table__row cursor-pointer hover:bg-muted/40"
                                tabindex="0"
                                role="link"
                                aria-label="Bekijk {{ $customer->name }}"
                            >
                                <td>
                                    <span class="font-medium text-foreground">{{ $customer->name }}</span>
                                </td>
                                <td class="text-muted-foreground">
                                    @if($customer->contact_name)
                                        {{ $customer->contact_name }}<br>
                                    @endif
                                    {{ $customer->contact_email ?? '—' }}
                                </td>
                                <td class="text-muted-foreground">{{ $customer->debtor_number ?? '—' }}</td>
                                <td class="transport-customers-table__status-col">
                                    @if($customer->active)
                                        <span class="kt-badge kt-badge-success kt-badge-sm">Actief</span>
                                    @else
                                        <span class="kt-badge kt-badge-secondary kt-badge-sm">Inactief</span>
                                    @endif
                                </td>
                                <td class="transport-customers-table__actions-col" data-no-row-link onclick="event.stopPropagation();">
                                    <div class="kt-menu flex justify-center" data-kt-menu="true">
                                        <div class="kt-menu-item"
                                             data-kt-menu-item-offset="0, 10px"
                                             data-kt-menu-item-placement="bottom-end"
                                             data-kt-menu-item-placement-rtl="bottom-start"
                                             data-kt-menu-item-toggle="dropdown"
                                             data-kt-menu-item-trigger="click">
                                            <button class="kt-menu-toggle kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" type="button" aria-label="Acties">
                                                <svg class="w-5 h-5 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5ZM12 12.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5ZM12 18.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5Z"/>
                                                </svg>
                                            </button>
                                            <div class="kt-menu-dropdown kt-menu-default w-[175px] min-w-[175px]" data-kt-menu-dismiss="true">
                                                <div class="kt-menu-item">
                                                    <a class="kt-menu-link" href="{{ route('admin.taxi.transport_customers.show', $customer->id) }}">
                                                        <span class="kt-menu-icon">
                                                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                                                        </span>
                                                        <span class="kt-menu-title">Bekijken</span>
                                                    </a>
                                                </div>
                                                @can('rides.update')
                                                <div class="kt-menu-item">
                                                    <a class="kt-menu-link" href="{{ route('admin.taxi.transport_customers.edit', $customer->id) }}">
                                                        <span class="kt-menu-icon">
                                                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125"/></svg>
                                                        </span>
                                                        <span class="kt-menu-title">Bewerken</span>
                                                    </a>
                                                </div>
                                                @endcan
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted-foreground py-8">
                                    Geen contractklanten gevonden.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                </div>
                @if($customers->hasPages())
                <div class="px-5 py-4">
                    {{ $customers->links() }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    #content #transport-customers-table .transport-customers-table__status-col {
        width: 6.5rem !important;
        min-width: 6.5rem !important;
        max-width: 6.5rem !important;
        padding-inline: 0.375rem !important;
        white-space: nowrap;
        vertical-align: middle !important;
    }

    #content #transport-customers-table .transport-customers-table__actions-col {
        width: 4.5rem !important;
        min-width: 4.5rem !important;
        max-width: 4.5rem !important;
        padding-inline: 0.375rem !important;
        text-align: center !important;
        vertical-align: middle !important;
        white-space: nowrap;
        overflow: visible !important;
    }

    #content #transport-customers-table .transport-customers-table__actions-col .kt-menu {
        display: flex !important;
        justify-content: center !important;
        width: 100%;
        margin-inline: auto;
    }

    .transport-customers-table-wrap td:last-child .kt-menu-dropdown { position: fixed !important; z-index: 99999 !important; }
    .transport-customers-table-wrap td:last-child .kt-menu-item.show .kt-menu-dropdown,
    .transport-customers-table-wrap td:last-child .kt-menu-item[data-kt-menu-item-toggle="dropdown"].show .kt-menu-dropdown { display: block !important; visibility: visible !important; opacity: 1 !important; }
    .transport-customers-table-wrap td:last-child .kt-menu-item.show { z-index: 99999 !important; }
    .transport-customers-table-wrap .kt-scrollable-x-auto { overflow-x: auto !important; overflow-y: visible !important; }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    function initTransportCustomerMenus() {
        if (window.KTMenu && typeof window.KTMenu.init === 'function') {
            try { window.KTMenu.init(); } catch (e) {}
        }
        document.querySelectorAll('.transport-customers-table-wrap .kt-menu-toggle').forEach(function(toggle) {
            if (toggle._transportCustomerMenuBound) return;
            toggle._transportCustomerMenuBound = true;
            toggle.addEventListener('click', function(e) {
                e.stopPropagation();
                e.preventDefault();
                var menuItem = toggle.closest('.kt-menu-item');
                if (!menuItem) return;
                var dropdown = menuItem.querySelector('.kt-menu-dropdown');
                if (!dropdown) return;
                var isShowing = menuItem.classList.contains('show');
                document.querySelectorAll('.transport-customers-table-wrap .kt-menu-item.show').forEach(function(item) {
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
    initTransportCustomerMenus();
    setTimeout(initTransportCustomerMenus, 300);

    document.addEventListener('click', function(e) {
        if (e.target.closest('.transport-customers-table-wrap .kt-menu')) return;
        document.querySelectorAll('.transport-customers-table-wrap .kt-menu-item.show').forEach(function(item) {
            item.classList.remove('show');
            var d = item.querySelector('.kt-menu-dropdown');
            if (d) d.style.display = 'none';
        });
    });

    document.querySelectorAll('#transport-customers-table tr[data-row-href]').forEach(function(row) {
        row.addEventListener('click', function(event) {
            if (event.target.closest('[data-no-row-link]')) {
                return;
            }
            window.location.href = row.getAttribute('data-row-href');
        });

        row.addEventListener('keydown', function(event) {
            if (event.key === 'Enter' || event.key === ' ') {
                if (event.target.closest('[data-no-row-link]')) {
                    return;
                }
                event.preventDefault();
                window.location.href = row.getAttribute('data-row-href');
            }
        });
    });
});
</script>
@endpush
