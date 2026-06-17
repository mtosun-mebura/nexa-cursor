@extends('admin.layouts.app')

@section('title', 'Routeplanner — '.$group->name)

@section('content')
@php
    $pickupStops = $template->stops->where('stop_type', 'pickup')->values();
    $destinationStop = $template->stops->firstWhere('stop_type', 'destination');
    $weekdayLabels = \App\Modules\NexaTaxi\Models\TransportRouteTemplate::weekdayLabels();
    $selectedDays = old('recurrence_days', $template->recurrence_days ?: [1, 2, 3, 4, 5]);
    $routeWarnings = (array) session('route_warnings', []);
@endphp
<div class="kt-container-fixed min-w-0">
    <div class="flex flex-wrap items-center justify-between gap-3 pb-7.5">
        <div>
            <h1 class="text-xl font-medium leading-none text-mono">Routeplanner</h1>
            <p class="text-sm text-muted-foreground pt-2">{{ $group->name }} · {{ $contract->name }} · {{ $customer->name }}</p>
            <div class="pt-3 flex flex-wrap gap-2">
                <a href="{{ $backUrl }}" class="kt-btn kt-btn-outline">
                    <i class="ki-filled ki-arrow-left me-2"></i>
                    Terug naar groep
                </a>
            </div>
        </div>
        <div class="flex flex-wrap gap-2 shrink-0">
            @if($template->route_locked)
                <span class="kt-badge kt-badge-warning kt-badge-sm">Route vastgezet</span>
            @else
                <span class="kt-badge kt-badge-secondary kt-badge-sm">Route bewerkbaar</span>
            @endif
            @if($template->active)
                <span class="kt-badge kt-badge-success kt-badge-sm">Actief</span>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="kt-alert kt-alert-success mb-5" role="alert">
            <i class="ki-filled ki-check-circle me-2"></i> {{ session('success') }}
            @if(session('route_departure_time'))
                <span class="block text-sm mt-1">Geschat vertrek: {{ session('route_departure_time') }}</span>
            @endif
        </div>
    @endif

    @if($routeWarnings !== [])
        <div class="kt-alert kt-alert-warning mb-5" role="alert">
            <ul class="list-disc list-inside text-sm">
                @foreach($routeWarnings as $warning)
                    <li>{{ $warning }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($errors->any())
        <div class="kt-alert kt-alert-danger mb-5" role="alert">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid gap-5 lg:gap-7.5">

        {{-- Instellingen --}}
        @can('rides.update')
        <div class="kt-card w-full min-w-0">
            <div class="kt-card-header"><h3 class="kt-card-title mb-0">Route-instellingen</h3></div>
            <div class="kt-card-content p-0">
                <form method="POST" action="{{ route('admin.taxi.transport_groups.route.settings', [$customer->id, $contract->id, $group->id]) }}" class="px-3 sm:px-5 pb-5">
                    @csrf
                    @method('PUT')
                    <fieldset @disabled($template->route_locked) class="min-w-0">
                        <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground w-full">
                            <tr>
                                <td class="min-w-56 text-secondary-foreground font-medium">Label</td>
                                <td>
                                    <input type="text" name="label" class="kt-input w-full max-w-md" value="{{ old('label', $template->label) }}" required>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-secondary-foreground font-medium align-top pt-3">Weekdagen</td>
                                <td class="pt-3">
                                    <div class="flex flex-wrap gap-3">
                                        @foreach($weekdayLabels as $dayNum => $dayLabel)
                                            <label class="inline-flex items-center gap-2 cursor-pointer">
                                                <input type="checkbox" name="recurrence_days[]" value="{{ $dayNum }}"
                                                    class="kt-checkbox"
                                                    @checked(in_array($dayNum, array_map('intval', (array) $selectedDays), true))>
                                                <span>{{ $dayLabel }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-secondary-foreground font-medium">Startpunt chauffeur</td>
                                <td>
                                    <select name="driver_start_mode" id="driver_start_mode" class="kt-select w-full max-w-xs">
                                        <option value="depot" @selected(old('driver_start_mode', $template->driver_start_mode) === 'depot')>Depot</option>
                                        <option value="first_stop" @selected(old('driver_start_mode', $template->driver_start_mode) === 'first_stop')>Eerste stop</option>
                                    </select>
                                </td>
                            </tr>
                            <tr id="depot-address-row">
                                <td class="text-secondary-foreground font-medium">Depotadres</td>
                                <td>
                                    @include('admin.partials.google-address-input', [
                                        'name' => 'driver_start_address',
                                        'value' => old('driver_start_address', $template->driver_start_address),
                                        'latName' => 'driver_start_lat',
                                        'lngName' => 'driver_start_lng',
                                        'latValue' => old('driver_start_lat', $template->driver_start_lat),
                                        'lngValue' => old('driver_start_lng', $template->driver_start_lng),
                                        'placeholder' => 'Zoek depotadres...',
                                    ])
                                </td>
                            </tr>
                            <tr>
                                <td class="text-secondary-foreground font-medium">Buffer per stop</td>
                                <td>
                                    <div class="flex items-center gap-2 max-w-xs">
                                        <input type="number" name="buffer_seconds" class="kt-input w-24" min="0" max="900" step="30"
                                            value="{{ old('buffer_seconds', $template->buffer_seconds ?? 120) }}" required>
                                        <span class="text-sm">seconden</span>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-secondary-foreground font-medium">Eindlocatie</td>
                                <td>{{ $group->destination_address }} <span class="text-muted-foreground">(aankomst {{ substr($group->destination_arrival_time, 0, 5) }})</span></td>
                            </tr>
                            <tr>
                                <td class="text-secondary-foreground font-medium">Actieve leden</td>
                                <td>{{ $activeMembers->count() }} passagier(s)</td>
                            </tr>
                        </table>
                        @unless($template->route_locked)
                        <div class="flex justify-end mt-4">
                            <button type="submit" class="kt-btn kt-btn-primary kt-btn-sm">Instellingen opslaan</button>
                        </div>
                        @endunless
                    </fieldset>
                </form>
            </div>
        </div>
        @endcan

        {{-- Stops --}}
        <div class="kt-card w-full min-w-0">
            <div class="kt-card-header flex flex-wrap items-center justify-between gap-2">
                <h3 class="kt-card-title mb-0">Stops ({{ $pickupStops->count() }} ophalen + bestemming)</h3>
                @can('rides.update')
                <div class="flex flex-wrap gap-2">
                    <form method="POST" action="{{ route('admin.taxi.transport_groups.route.calculate', [$customer->id, $contract->id, $group->id]) }}" class="inline">
                        @csrf
                        <button type="submit" class="kt-btn kt-btn-primary kt-btn-sm">
                            <i class="ki-filled ki-route me-1"></i>
                            {{ $template->route_locked ? 'Tijden herberekenen' : 'Route berekenen' }}
                        </button>
                    </form>
                    @if($pickupStops->isNotEmpty())
                    <form method="POST" action="{{ route('admin.taxi.transport_groups.route.lock', [$customer->id, $contract->id, $group->id]) }}" class="inline">
                        @csrf
                        <button type="submit" class="kt-btn kt-btn-outline kt-btn-sm">
                            {{ $template->route_locked ? 'Ontgrendelen' : 'Route vastzetten' }}
                        </button>
                    </form>
                    @endif
                </div>
                @endcan
            </div>
            <div class="kt-card-content p-0 min-w-0">
                @if($pickupStops->isEmpty())
                    <div class="px-3 sm:px-5 py-8 text-center text-muted-foreground text-sm">
                        @if($activeMembers->isEmpty())
                            Voeg eerst leden toe aan de groep en druk op <strong>Route berekenen</strong>.
                        @else
                            Druk op <strong>Route berekenen</strong> om stops en tijden te genereren.
                        @endif
                    </div>
                @else
                    @php $canReorderStops = auth()->user()->can('rides.update') && ! $template->route_locked; @endphp
                    @if($canReorderStops)
                    <form method="POST" action="{{ route('admin.taxi.transport_groups.route.stops', [$customer->id, $contract->id, $group->id]) }}" id="route-stops-form">
                        @csrf
                        @method('PUT')
                    @endif
                    <div class="kt-scrollable-x-auto admin-table-scroll-wrap">
                        <table class="kt-table kt-table-border admin-fluid-table align-middle text-sm w-full" id="route-stops-table">
                            <thead>
                                <tr>
                                    <th class="w-12">#</th>
                                    <th>Type</th>
                                    <th>Passagier</th>
                                    <th>Adres</th>
                                    <th>Tijd</th>
                                    @if($canReorderStops)
                                    <th class="w-24 text-center">Volgorde</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody id="route-stops-tbody">
                                @foreach($pickupStops as $index => $stop)
                                <tr data-stop-id="{{ $stop->id }}">
                                    <td class="text-muted-foreground">{{ $index + 1 }}</td>
                                    <td><span class="kt-badge kt-badge-light kt-badge-sm">Ophalen</span></td>
                                    <td class="font-medium">{{ $stop->passenger?->full_name ?? '—' }}</td>
                                    <td class="text-muted-foreground">{{ Str::limit($stop->address, 55) }}</td>
                                    <td class="font-medium">{{ substr($stop->planned_at_time, 0, 5) }}</td>
                                    @if($canReorderStops)
                                    <td class="text-center whitespace-nowrap">
                                        <input type="hidden" name="stop_order[]" value="{{ $stop->id }}">
                                        <button type="button" class="kt-btn kt-btn-xs kt-btn-icon kt-btn-ghost route-stop-up" title="Omhoog" aria-label="Omhoog" @disabled($index === 0)>
                                            <i class="ki-filled ki-up"></i>
                                        </button>
                                        <button type="button" class="kt-btn kt-btn-xs kt-btn-icon kt-btn-ghost route-stop-down" title="Omlaag" aria-label="Omlaag" @disabled($index === $pickupStops->count() - 1)>
                                            <i class="ki-filled ki-down"></i>
                                        </button>
                                    </td>
                                    @endif
                                </tr>
                                @endforeach
                                @if($destinationStop)
                                <tr class="bg-muted/30">
                                    <td class="text-muted-foreground">{{ $pickupStops->count() + 1 }}</td>
                                    <td><span class="kt-badge kt-badge-success kt-badge-sm">Bestemming</span></td>
                                    <td class="text-muted-foreground">—</td>
                                    <td class="text-muted-foreground">{{ Str::limit($destinationStop->address, 55) }}</td>
                                    <td class="font-medium">{{ substr($destinationStop->planned_at_time, 0, 5) }}</td>
                                    @if($canReorderStops)
                                    <td></td>
                                    @endif
                                </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                    @if($canReorderStops)
                    <div class="px-3 sm:px-5 py-4 border-t border-input flex justify-end">
                        <button type="submit" class="kt-btn kt-btn-outline kt-btn-sm">Volgorde opslaan</button>
                    </div>
                    </form>
                    @endif
                @endif
            </div>
        </div>

        {{-- Chauffeur + voertuig --}}
        @can('rides.update')
        <div class="kt-card w-full min-w-0">
            <div class="kt-card-header"><h3 class="kt-card-title mb-0">Vaste chauffeur & voertuig</h3></div>
            <div class="kt-card-content p-0">
                <form method="POST" action="{{ route('admin.taxi.transport_groups.route.assignment', [$customer->id, $contract->id, $group->id]) }}" class="px-3 sm:px-5 pb-5">
                    @csrf
                    @method('PUT')
                    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground w-full transport-route-assignment-table">
                        <tr>
                            <td class="transport-route-assignment-table__label text-secondary-foreground font-medium">Chauffeur</td>
                            <td>
                                <select name="driver_id" class="kt-select w-full max-w-md">
                                    <option value="">— Geen vaste chauffeur —</option>
                                    @foreach($drivers as $driver)
                                        <option value="{{ $driver->id }}" @selected(old('driver_id', $assignment?->driver_id) == $driver->id)>
                                            {{ $driver->first_name }} {{ $driver->last_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td class="transport-route-assignment-table__label text-secondary-foreground font-medium">Voertuig</td>
                            <td>
                                <select name="vehicle_id" class="kt-select w-full max-w-md">
                                    <option value="">— Geen vast voertuig —</option>
                                    @foreach($vehicles as $vehicle)
                                        <option value="{{ $vehicle->id }}" @selected(old('vehicle_id', $assignment?->vehicle_id) == $vehicle->id)>
                                            {{ $vehicle->name }}@if($vehicle->license_plate) — {{ $vehicle->license_plate }}@endif
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                        </tr>
                    </table>
                    <div class="flex justify-end mt-4">
                        <button type="submit" class="kt-btn kt-btn-primary kt-btn-sm">Toewijzing opslaan</button>
                    </div>
                </form>
            </div>
        </div>
        @endcan

    </div>
</div>
@endsection

@push('styles')
<style>
    #content .transport-route-assignment-table .transport-route-assignment-table__label {
        width: 7.5rem;
        min-width: 7.5rem;
        max-width: 7.5rem;
        white-space: nowrap;
        vertical-align: middle;
    }
</style>
@endpush

@push('scripts')
<script>
(function () {
    var modeSelect = document.getElementById('driver_start_mode');
    var depotRow = document.getElementById('depot-address-row');

    function toggleDepotRow() {
        if (!modeSelect || !depotRow) return;
        depotRow.style.display = modeSelect.value === 'depot' ? '' : 'none';
    }

    if (modeSelect) {
        modeSelect.addEventListener('change', toggleDepotRow);
        toggleDepotRow();
    }

    var tbody = document.getElementById('route-stops-tbody');
    if (!tbody) return;

    function refreshOrderButtons() {
        var rows = tbody.querySelectorAll('tr[data-stop-id]');
        rows.forEach(function (row, index) {
            var up = row.querySelector('.route-stop-up');
            var down = row.querySelector('.route-stop-down');
            if (up) up.disabled = index === 0;
            if (down) down.disabled = index === rows.length - 1;
        });
    }

    tbody.addEventListener('click', function (event) {
        var btn = event.target.closest('.route-stop-up, .route-stop-down');
        if (!btn || btn.disabled) return;

        var row = btn.closest('tr[data-stop-id]');
        if (!row) return;

        if (btn.classList.contains('route-stop-up')) {
            var prev = row.previousElementSibling;
            if (prev && prev.hasAttribute('data-stop-id')) {
                tbody.insertBefore(row, prev);
            }
        } else {
            var next = row.nextElementSibling;
            if (next && next.hasAttribute('data-stop-id')) {
                tbody.insertBefore(next, row);
            }
        }

        var form = document.getElementById('route-stops-form');
        if (form) {
            form.querySelectorAll('input[name="stop_order[]"]').forEach(function (input) {
                input.remove();
            });
            tbody.querySelectorAll('tr[data-stop-id]').forEach(function (tr) {
                var hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'stop_order[]';
                hidden.value = tr.getAttribute('data-stop-id');
                form.appendChild(hidden);
            });
        }

        refreshOrderButtons();
    });

    refreshOrderButtons();
})();
</script>
@endpush
