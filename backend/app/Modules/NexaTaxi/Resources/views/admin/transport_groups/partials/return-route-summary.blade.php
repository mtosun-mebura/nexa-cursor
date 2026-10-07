@php
    $stopTypeLabels = [
        \App\Modules\NexaTaxi\Models\TransportRouteStop::STOP_TYPE_PICKUP => ['Ophalen', 'kt-badge-light'],
        \App\Modules\NexaTaxi\Models\TransportRouteStop::STOP_TYPE_DROPOFF => ['Afzetten', 'kt-badge-primary'],
        \App\Modules\NexaTaxi\Models\TransportRouteStop::STOP_TYPE_DESTINATION => ['Bestemming', 'kt-badge-success'],
    ];
    $firstPickup = $returnRouteStops->firstWhere('stop_type', \App\Modules\NexaTaxi\Models\TransportRouteStop::STOP_TYPE_PICKUP);
    $lastStop = $returnRouteStops->last();
    $pickupCount = $returnRouteStops->where('stop_type', \App\Modules\NexaTaxi\Models\TransportRouteStop::STOP_TYPE_PICKUP)->count();
@endphp

<div class="px-3 sm:px-5 pb-3 min-w-0">
    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground w-full">
        <tr>
            <td class="min-w-56 text-secondary-foreground font-medium">Passagiers op route</td>
            <td>{{ $pickupCount }} ophaalstop(s) bij school</td>
        </tr>
        <tr>
            <td class="text-secondary-foreground font-medium">Ophalen school</td>
            <td>
                @if($firstPickup)
                    {{ substr((string) $firstPickup->planned_at_time, 0, 5) }}
                    <span class="text-muted-foreground">· {{ Str::limit($firstPickup->address, 55) }}</span>
                @else
                    —
                @endif
            </td>
        </tr>
        <tr>
            <td class="text-secondary-foreground font-medium">Laatste afzet</td>
            <td>
                @if($lastStop)
                    {{ substr((string) $lastStop->planned_at_time, 0, 5) }}
                    <span class="text-muted-foreground">· {{ Str::limit($lastStop->address, 55) }}</span>
                @else
                    —
                @endif
            </td>
        </tr>
    </table>
</div>

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
            @foreach($returnRouteStops as $index => $stop)
                @php
                    $typeMeta = $stopTypeLabels[$stop->stop_type] ?? ['Stop', 'kt-badge-secondary'];
                @endphp
                <tr @class(['route-stop-destination-row' => $stop->stop_type === \App\Modules\NexaTaxi\Models\TransportRouteStop::STOP_TYPE_DESTINATION])>
                    <td class="text-muted-foreground">{{ $index + 1 }}</td>
                    <td><span class="kt-badge {{ $typeMeta[1] }} kt-badge-sm">{{ $typeMeta[0] }}</span></td>
                    <td class="font-medium">{{ $stop->passenger?->full_name ?? '—' }}</td>
                    <td class="text-muted-foreground">{{ Str::limit($stop->address, 55) }}</td>
                    <td class="font-medium">{{ substr((string) $stop->planned_at_time, 0, 5) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
