@php
    $weekdayLabels = \App\Modules\NexaTaxi\Models\TransportRouteTemplate::weekdayLabels();
    $recurrenceDays = $routeTemplate->recurrence_days ?: \App\Modules\NexaTaxi\Models\TransportRouteTemplate::defaultRecurrenceDays();
    $weekdayText = collect($recurrenceDays)
        ->sort()
        ->map(fn (int $day) => $weekdayLabels[$day] ?? $day)
        ->implode(', ');
    $assignment = $routeTemplate->assignment;
    $driver = $assignment?->driver;
    $vehicle = $assignment?->vehicle;
    $destinationTime = $routeDestinationStop
        ? substr((string) $routeDestinationStop->planned_at_time, 0, 5)
        : substr((string) $group->destination_arrival_time, 0, 5);
    $firstPickupTime = $routePickupStops->isNotEmpty()
        ? substr((string) $routePickupStops->first()->planned_at_time, 0, 5)
        : null;
    $departureTime = $routeDepartureTime ? substr($routeDepartureTime, 0, 5) : null;
@endphp

<div class="px-3 sm:px-5 pb-3 min-w-0">
    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground w-full">
        <tr>
            <td class="min-w-56 text-secondary-foreground font-medium">Weekdagen</td>
            <td>{{ $weekdayText }}</td>
        </tr>
        <tr>
            <td class="text-secondary-foreground font-medium">Passagiers op route</td>
            <td>{{ $routePickupStops->count() }} ophaalstop(s)</td>
        </tr>
        <tr>
            <td class="text-secondary-foreground font-medium">Vertrek</td>
            <td>
                @if($departureTime)
                    {{ $departureTime }}
                    @if($routeTemplate->driver_start_mode === 'depot' && $routeTemplate->driver_start_address)
                        <span class="text-muted-foreground">(vanaf depot)</span>
                    @else
                        <span class="text-muted-foreground">(bij eerste stop{{ $firstPickupTime ? ' '.$firstPickupTime : '' }})</span>
                    @endif
                @else
                    —
                @endif
            </td>
        </tr>
        <tr>
            <td class="text-secondary-foreground font-medium">Aankomst bestemming</td>
            <td>
                {{ $destinationTime }}
                <span class="text-muted-foreground">· {{ Str::limit($group->destination_address, 60) }}</span>
            </td>
        </tr>
        <tr>
            <td class="text-secondary-foreground font-medium">Chauffeur</td>
            <td>
                @if($driver)
                    {{ trim($driver->first_name.' '.$driver->last_name) }}
                @else
                    <span class="text-muted-foreground">Niet toegewezen</span>
                @endif
            </td>
        </tr>
        <tr>
            <td class="text-secondary-foreground font-medium">Voertuig</td>
            <td>
                @if($vehicle)
                    {{ $vehicle->name }}@if($vehicle->license_plate) — {{ $vehicle->license_plate }}@endif
                @else
                    <span class="text-muted-foreground">Niet toegewezen</span>
                @endif
            </td>
        </tr>
        <tr>
            <td class="text-secondary-foreground font-medium">Route-status</td>
            <td>
                @if($routeTemplate->route_locked)
                    <span class="kt-badge kt-badge-warning kt-badge-sm">Vastgezet</span>
                @else
                    <span class="kt-badge kt-badge-secondary kt-badge-sm">Bewerkbaar</span>
                @endif
            </td>
        </tr>
    </table>
</div>

@if($routePickupStops->isNotEmpty())
<div class="kt-scrollable-x-auto admin-table-scroll-wrap border-t border-input">
    <table class="kt-table kt-table-border admin-fluid-table align-middle text-sm w-full">
        <thead>
            <tr>
                <th class="w-12">#</th>
                <th>Type</th>
                <th>Passagier</th>
                <th>Adres</th>
                <th>Tijd</th>
            </tr>
        </thead>
        <tbody>
            @foreach($routePickupStops as $index => $stop)
            <tr>
                <td class="text-muted-foreground">{{ $index + 1 }}</td>
                <td><span class="kt-badge kt-badge-light kt-badge-sm">Ophalen</span></td>
                <td class="font-medium">{{ $stop->passenger?->full_name ?? '—' }}</td>
                <td class="text-muted-foreground">{{ Str::limit($stop->address, 55) }}</td>
                <td class="font-medium">{{ substr($stop->planned_at_time, 0, 5) }}</td>
            </tr>
            @endforeach
            @if($routeDestinationStop)
            <tr class="bg-muted/30">
                <td class="text-muted-foreground">{{ $routePickupStops->count() + 1 }}</td>
                <td><span class="kt-badge kt-badge-success kt-badge-sm">Bestemming</span></td>
                <td class="text-muted-foreground">—</td>
                <td class="text-muted-foreground">{{ Str::limit($routeDestinationStop->address, 55) }}</td>
                <td class="font-medium">{{ substr($routeDestinationStop->planned_at_time, 0, 5) }}</td>
            </tr>
            @endif
        </tbody>
    </table>
</div>
@endif
