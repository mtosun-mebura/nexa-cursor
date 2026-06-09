@extends('admin.layouts.app')

@section('title', 'Ritten')

@section('content')
<div class="kt-container-fixed min-w-0">
    <div class="flex flex-wrap items-center justify-between gap-3 pb-7.5">
        <h1 class="text-xl font-medium leading-none text-mono">Ritten</h1>
    </div>

    @if(session('success'))
        <div class="kt-alert kt-alert-success mb-5"><i class="ki-filled ki-check-circle me-2"></i> {{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="kt-alert kt-alert-danger mb-5"><i class="ki-filled ki-cross-circle me-2"></i> {{ session('error') }}</div>
    @endif

    <div class="kt-card w-full min-w-0">
        <div class="kt-card-header py-5 flex-wrap gap-2 min-w-0">
            <h3 class="kt-card-title text-sm pb-3 w-full mb-0">Overzicht ritten</h3>
            <div class="flex flex-col sm:flex-row flex-wrap gap-2 gap-2.5 w-full sm:justify-end items-stretch sm:items-center min-w-0">
                <form method="GET" action="{{ route('admin.taxi.ride_requests.index') }}" id="ride-filters-form" class="flex flex-col sm:flex-row flex-wrap gap-2.5 w-full sm:w-auto min-w-0">
                    @if(request('per_page'))<input type="hidden" name="per_page" value="{{ request('per_page') }}">@endif
                    <select name="status" id="ride-status-filter" class="kt-select w-full sm:w-40">
                        <option value="">Alle statussen</option>
                        @foreach($statusLabels as $value => $label)
                            <option value="{{ $value }}" {{ request('status') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    <select name="vehicle_id" id="ride-vehicle-filter" class="kt-select w-full sm:w-44">
                        <option value="">Alle voertuigen</option>
                        @foreach($vehicles as $v)
                            <option value="{{ $v->id }}" {{ request('vehicle_id') == $v->id ? 'selected' : '' }}>{{ $v->name }}</option>
                        @endforeach
                    </select>
                    <input type="text"
                           name="from"
                           value="{{ request('from') }}"
                           class="kt-input w-full sm:w-40 text-sm"
                           placeholder="Van"
                           data-kt-date-picker="true"
                           data-kt-date-picker-input-mode="true"
                           data-kt-date-picker-position-to-input="left"
                           data-kt-date-picker-format="yyyy-MM-dd">
                    <input type="text"
                           name="to"
                           value="{{ request('to') }}"
                           class="kt-input w-full sm:w-40 text-sm"
                           placeholder="Tot"
                           data-kt-date-picker="true"
                           data-kt-date-picker-input-mode="true"
                           data-kt-date-picker-position-to-input="left"
                           data-kt-date-picker-format="yyyy-MM-dd">
                    <button type="submit" class="kt-btn kt-btn-outline kt-btn-sm w-full sm:w-auto shrink-0">Filter</button>
                </form>
                @if(request('status') !== null && request('status') !== '' || request('vehicle_id') !== null && request('vehicle_id') !== '' || request('from') || request('to'))
                <a href="{{ route('admin.taxi.ride_requests.index') }}" class="kt-btn kt-btn-outline kt-btn-icon rides-filter-reset-btn shrink-0 w-full sm:w-auto" title="Filters resetten">
                    <i class="ki-filled ki-arrows-circle text-base"></i>
                </a>
                @endif
            </div>
        </div>
        <div class="kt-card-content p-0 min-w-0">
            @if($rideRequests->count() > 0)
            <div class="rides-list-table-wrap min-w-0">
            <div class="kt-scrollable-x-auto admin-table-scroll-wrap">
            <table class="kt-table kt-table-border admin-fluid-table align-middle text-sm w-full rides-list-table">
                <thead>
                    <tr>
                        <th class="text-secondary-foreground font-normal text-left" data-label="Datum/tijd">Datum/tijd</th>
                        <th class="text-secondary-foreground font-normal text-left" data-label="Klant">Klant</th>
                        <th class="text-secondary-foreground font-normal text-left" data-label="Route">Route</th>
                        <th class="text-secondary-foreground font-normal text-left" data-label="Status">Status</th>
                        <th class="text-secondary-foreground font-normal text-left" data-label="Prijs">Prijs</th>
                        <th class="rides-list-table__actions-col text-secondary-foreground font-normal" data-label="Acties">Acties</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $shortRideAddress = static function (?string $address): string {
                            if ($address === null || trim($address) === '') {
                                return '—';
                            }

                            $address = trim($address);
                            $firstComma = strpos($address, ',');
                            if ($firstComma === false) {
                                return $address;
                            }

                            $secondComma = strpos($address, ',', $firstComma + 1);
                            if ($secondComma === false) {
                                return trim(substr($address, 0, $firstComma));
                            }

                            return trim(substr($address, 0, $secondComma));
                        };
                    @endphp
                    @foreach($rideRequests as $r)
                    @php
                        $canViewRide = auth()->user()->hasRole('super-admin') || auth()->user()->can('rides.view');
                        $canUpdateRide = auth()->user()->hasRole('super-admin') || auth()->user()->can('rides.update');
                    @endphp
                    <tr
                        @if($canViewRide)
                            data-row-href="{{ route('admin.taxi.ride_requests.show', $r) }}"
                            class="cursor-pointer hover:bg-muted/40"
                            tabindex="0"
                            role="link"
                            aria-label="Bekijk rit #{{ $r->id }}"
                        @endif
                    >
                        <td class="rides-list-table__datetime">
                            <span class="rides-list-table__date block whitespace-nowrap">{{ $r->pickup_at->format('d-m-Y') }}</span>
                            <span class="rides-list-table__time block whitespace-nowrap text-muted-foreground text-xs">{{ $r->pickup_at->format('H:i') }}</span>
                        </td>
                        <td class="rides-list-table__customer">
                            <span class="block truncate">{{ $r->customer_name }}</span>
                            @if($r->customer_phone)<span class="text-muted-foreground text-xs block truncate">{{ $r->customer_phone }}</span>@endif
                        </td>
                        <td class="rides-list-table__route" title="{{ $r->pickup_address }} → {{ $r->dropoff_address }}">
                            <div class="rides-route-stack">
                                <span class="rides-route-stack__address">{{ $shortRideAddress($r->pickup_address) }}</span>
                                <span class="rides-route-stack__arrow" aria-hidden="true">
                                    <svg class="rides-route-stack__arrow-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M20 12 18.59 10.59 13 16.17V4h-2v12.17l-5.59-5.58L4 12l8 8 8-8z"/>
                                    </svg>
                                </span>
                                <span class="rides-route-stack__address">{{ $shortRideAddress($r->dropoff_address) }}</span>
                            </div>
                        </td>
                        <td class="rides-list-table__status truncate">{{ $r->status_label }}</td>
                        <td class="rides-list-table__price whitespace-nowrap tabular-nums admin-currency-cell">@if($r->quoted_price !== null)€&nbsp;{{ number_format((float) $r->quoted_price, 2, ',', '.') }}@else—@endif</td>
                        <td class="rides-list-table__actions-col rides-list-table__actions" data-no-row-link onclick="event.stopPropagation();">
                            @if($canViewRide || $canUpdateRide)
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
                                        <div class="kt-menu-dropdown kt-menu-default w-[190px] min-w-[190px]" data-kt-menu-dismiss="true">
                                            @if($canViewRide)
                                            <div class="kt-menu-item">
                                                <a class="kt-menu-link" href="{{ route('admin.taxi.ride_requests.show', $r) }}">
                                                    <span class="kt-menu-icon">
                                                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                                                    </span>
                                                    <span class="kt-menu-title">Details</span>
                                                </a>
                                            </div>
                                            @endif
                                            @if($canViewRide && ($notificationLogTableExists ?? false))
                                            <div class="kt-menu-item">
                                                <a class="kt-menu-link" href="{{ route('admin.taxi.ride_requests.notification_log', $r) }}">
                                                    <span class="kt-menu-icon">
                                                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25"/></svg>
                                                    </span>
                                                    <span class="kt-menu-title">
                                                        Notificatielog
                                                        @if(($r->notification_logs_count ?? 0) > 0)
                                                            <span class="text-muted-foreground">({{ $r->notification_logs_count }})</span>
                                                        @endif
                                                    </span>
                                                </a>
                                            </div>
                                            @endif
                                            @if($canUpdateRide)
                                            <div class="kt-menu-item">
                                                <a class="kt-menu-link" href="{{ route('admin.taxi.ride_requests.edit', $r) }}">
                                                    <span class="kt-menu-icon">
                                                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125"/></svg>
                                                    </span>
                                                    <span class="kt-menu-title">Status aanpassen</span>
                                                </a>
                                            </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @else
                                <span class="text-muted-foreground">—</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
            </div>
            @else
            <div class="flex flex-col items-center justify-center py-16 px-3">
                <i class="ki-filled ki-information-5 text-4xl text-muted-foreground mb-4"></i>
                <p class="text-sm text-muted-foreground mb-0">Geen ritten gevonden.</p>
            </div>
            @endif
        </div>
        @if($rideRequests->count() > 0)
        <div class="kt-card-footer admin-datatable-footer text-secondary-foreground text-sm font-medium pt-5 min-w-0">
            <div class="admin-datatable-footer__perpage flex flex-wrap items-center gap-2">
                Toon
                <form method="GET" action="{{ route('admin.taxi.ride_requests.index') }}" class="inline-flex" id="ride-perpage-form">
                    @if(request('status'))<input type="hidden" name="status" value="{{ request('status') }}">@endif
                    @if(request('vehicle_id'))<input type="hidden" name="vehicle_id" value="{{ request('vehicle_id') }}">@endif
                    @if(request('from'))<input type="hidden" name="from" value="{{ request('from') }}">@endif
                    @if(request('to'))<input type="hidden" name="to" value="{{ request('to') }}">@endif
                    <select class="kt-select w-24" name="per_page" onchange="this.form.submit()">
                        @foreach([10, 15, 25, 50] as $n)
                            <option value="{{ $n }}" {{ (int) request('per_page', 15) === $n ? 'selected' : '' }}>{{ $n }}</option>
                        @endforeach
                    </select>
                </form>
                per pagina
            </div>
            <div class="admin-datatable-footer__pagination max-w-full overflow-x-auto">
                {{ $rideRequests->links('vendor.pagination.admin-datatable') }}
            </div>
            <span class="admin-datatable-footer__info">{{ $rideRequests->firstItem() ?? 0 }}-{{ $rideRequests->lastItem() ?? 0 }} van {{ $rideRequests->total() }}</span>
        </div>
        @endif
    </div>
</div>
@push('styles')
<style>
    .rides-list-table-wrap .kt-scrollable-x-auto,
    .rides-list-table-wrap .admin-table-scroll-wrap,
    .rides-list-table-wrap .admin-desktop-table-wrap {
        overflow-x: auto !important;
        overflow-y: visible !important;
        -webkit-overflow-scrolling: touch;
        max-width: 100%;
        width: 100%;
        padding: 0 !important;
    }

    .rides-list-table-wrap .admin-mobile-list {
        padding-left: 0;
        padding-right: 0;
    }

    #content .rides-list-table-wrap .admin-table-scroll-wrap .kt-table {
        width: 100%;
        min-width: 100%;
    }

    #content .rides-list-table th:nth-child(1),
    #content .rides-list-table td:nth-child(1) {
        width: 15%;
    }

    #content .rides-list-table th:nth-child(2),
    #content .rides-list-table td:nth-child(2) {
        width: 16%;
    }

    #content .rides-list-table th:nth-child(3),
    #content .rides-list-table td:nth-child(3) {
        width: 32%;
    }

    #content .rides-list-table th:nth-child(4),
    #content .rides-list-table td:nth-child(4) {
        width: 16%;
    }

    #content .rides-list-table th:nth-child(5),
    #content .rides-list-table td:nth-child(5) {
        width: 11%;
    }

    #content .rides-list-table .rides-list-table__actions-col {
        width: 3.5rem !important;
        min-width: 3.5rem !important;
        max-width: 3.5rem !important;
        padding-inline: 0.375rem !important;
        text-align: center !important;
        vertical-align: middle !important;
        white-space: nowrap;
    }

    #content .rides-list-table__datetime {
        vertical-align: middle;
        line-height: 1.35;
    }

    #content .rides-list-table__actions .kt-menu {
        display: flex !important;
        justify-content: center !important;
        width: 100%;
        margin-inline: auto;
    }

    #content .rides-list-table .rides-list-table__customer,
    #content .rides-list-table .rides-list-table__route,
    #content .rides-list-table .rides-list-table__status {
        max-width: 0;
    }

    .rides-filter-reset-btn {
        min-width: 2.75rem !important;
        width: 2.75rem !important;
        padding-inline: 0.5rem !important;
        justify-content: center !important;
    }

    #content .rides-route-stack {
        display: inline-flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 0.125rem;
        max-width: 100%;
        text-align: left;
    }

    #content .rides-route-stack__address {
        display: block;
        line-height: 1.35;
        text-align: left;
        overflow-wrap: anywhere;
        word-break: break-word;
    }

    #content .rides-route-stack__arrow {
        display: flex;
        align-items: center;
        justify-content: center;
        align-self: center;
        width: 100%;
        color: var(--muted-foreground);
        line-height: 1;
        padding-block: 0.0625rem;
    }

    #content .rides-route-stack__arrow-icon {
        display: block;
        width: 0.875rem;
        height: 0.875rem;
        flex-shrink: 0;
    }
    .rides-list-table-wrap td:last-child .kt-menu-dropdown {
        position: fixed !important;
        z-index: 99999 !important;
    }
    .rides-list-table-wrap td:last-child .kt-menu-item.show .kt-menu-dropdown,
    .rides-list-table-wrap td:last-child .kt-menu-item[data-kt-menu-item-toggle="dropdown"].show .kt-menu-dropdown {
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
    }
</style>
@endpush
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var filterForm = document.getElementById('ride-filters-form');
    var statusFilter = document.getElementById('ride-status-filter');
    var vehicleFilter = document.getElementById('ride-vehicle-filter');
    var fromFilter = filterForm ? filterForm.querySelector('input[name="from"]') : null;
    var toFilter = filterForm ? filterForm.querySelector('input[name="to"]') : null;
    if (statusFilter && filterForm) statusFilter.addEventListener('change', function() { filterForm.submit(); });
    if (vehicleFilter && filterForm) vehicleFilter.addEventListener('change', function() { filterForm.submit(); });
    if (fromFilter && filterForm) fromFilter.addEventListener('change', function() { filterForm.submit(); });
    if (toFilter && filterForm) toFilter.addEventListener('change', function() { filterForm.submit(); });

    document.querySelectorAll('tr[data-row-href]').forEach(function(row) {
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
@endsection
