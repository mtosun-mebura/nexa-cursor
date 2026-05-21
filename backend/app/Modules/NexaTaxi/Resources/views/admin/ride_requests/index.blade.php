@extends('admin.layouts.app')

@section('title', 'Ritten')

@section('content')
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5">
        <h1 class="text-xl font-medium leading-none text-mono">Ritten</h1>
        @can('rides.create')
        <a href="{{ route('admin.taxi.ride_requests.create') }}" class="kt-btn kt-btn-primary">
            <i class="ki-filled ki-plus me-2"></i>Nieuwe rit
        </a>
        @endcan
    </div>

    @if(session('success'))
        <div class="kt-alert kt-alert-success mb-5"><i class="ki-filled ki-check-circle me-2"></i> {{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="kt-alert kt-alert-danger mb-5"><i class="ki-filled ki-cross-circle me-2"></i> {{ session('error') }}</div>
    @endif

    <div class="kt-card">
        <div class="kt-card-header py-5 flex-wrap gap-2">
            <h3 class="kt-card-title text-sm pb-3 w-full">Overzicht ritten</h3>
            <div class="flex flex-col sm:flex-row flex-wrap gap-2.5 w-full justify-center sm:justify-end items-center">
                <form method="GET" action="{{ route('admin.taxi.ride_requests.index') }}" id="ride-filters-form" class="flex flex-col sm:flex-row gap-2.5 w-full sm:w-auto">
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
                    <button type="submit" class="kt-btn kt-btn-outline kt-btn-sm" style="height: 34px;">Filter</button>
                </form>
                @if(request('status') !== null && request('status') !== '' || request('vehicle_id') !== null && request('vehicle_id') !== '' || request('from') || request('to'))
                <a href="{{ route('admin.taxi.ride_requests.index') }}" class="kt-btn kt-btn-outline kt-btn-icon" title="Filters resetten">
                    <i class="ki-filled ki-arrows-circle text-base"></i>
                </a>
                @endif
            </div>
        </div>
        <div class="kt-card-table kt-scrollable-x-auto">
            <table class="kt-table kt-table-border-dashed align-middle text-sm">
                <thead>
                    <tr>
                        <th class="text-secondary-foreground font-normal text-left">Datum/tijd</th>
                        <th class="text-secondary-foreground font-normal text-left">Klant</th>
                        <th class="text-secondary-foreground font-normal text-left">Route</th>
                        <th class="text-secondary-foreground font-normal text-left">Voertuig</th>
                        <th class="text-secondary-foreground font-normal text-left">Status</th>
                        <th class="text-secondary-foreground font-normal text-left">Prijs</th>
                        <th class="text-secondary-foreground font-normal text-right">Acties</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rideRequests as $r)
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
                        <td>{{ $r->pickup_at->format('d-m-Y H:i') }}</td>
                        <td>{{ $r->customer_name }}@if($r->customer_phone)<br><span class="text-muted-foreground text-xs">{{ $r->customer_phone }}</span>@endif</td>
                        <td>{{ Str::limit($r->pickup_address, 20) }} → {{ Str::limit($r->dropoff_address, 20) }}</td>
                        <td>{{ $r->vehicle?->name ?? '—' }}</td>
                        <td>{{ $r->status_label }}</td>
                        <td class="whitespace-nowrap tabular-nums admin-currency-cell">@if($r->quoted_price !== null)€&nbsp;{{ number_format((float) $r->quoted_price, 2, ',', '.') }}@else—@endif</td>
                        <td class="text-right w-[60px]" data-no-row-link>
                            @if($canViewRide || $canUpdateRide)
                                <div class="kt-menu inline-flex justify-end" data-kt-menu="true">
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
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-8 text-muted-foreground">Geen ritten gevonden.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="kt-card-footer justify-center md:justify-between flex-col md:flex-row gap-5 text-secondary-foreground text-sm font-medium pt-5">
            <div class="flex items-center gap-2 order-2 md:order-1">
                Toon
                <form method="GET" action="{{ route('admin.taxi.ride_requests.index') }}" class="inline" id="ride-perpage-form">
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
            <div class="flex items-center gap-4 order-1 md:order-2">
                <span>{{ $rideRequests->firstItem() ?? 0 }}-{{ $rideRequests->lastItem() ?? 0 }} van {{ $rideRequests->total() }}</span>
                {{ $rideRequests->links() }}
            </div>
        </div>
    </div>
</div>
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
