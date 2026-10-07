@php
    $returnRouteTemplate = $returnRouteTemplate ?? null;
    $returnRouteStops = $returnRouteStops ?? collect();
    $hasOutboundStops = $routeTemplate && ($routePickupStops ?? collect())->isNotEmpty();
    $hasReturnStops = $returnRouteTemplate && $returnRouteStops->isNotEmpty();
    $showDirectionLabels = $hasOutboundStops || $hasReturnStops || ($group->has_return_trip ?? false);
@endphp

@if($hasOutboundStops || $hasReturnStops || $routeTemplate || ($group->has_return_trip ?? false))
    @if($hasOutboundStops)
        @if($showDirectionLabels)
            <div class="px-3 sm:px-5 pt-4 pb-1">
                <h4 class="text-sm font-semibold text-foreground mb-0">Heenweg</h4>
                <p class="text-xs text-muted-foreground mb-0 mt-0.5">Huis → school</p>
            </div>
        @endif
        @include('taxi::admin.transport_groups.partials.route-summary')
    @elseif($routeTemplate)
        <div class="px-3 sm:px-5 pb-5 text-sm text-muted-foreground">
            @if($showDirectionLabels)
                <h4 class="text-sm font-semibold text-foreground mb-2">Heenweg</h4>
            @endif
            Route-instellingen staan klaar, maar er zijn nog geen stops berekend.
            @can('rides.update')
            Open de routeplanner en druk op <strong>Route berekenen</strong>.
            @endcan
        </div>
    @endif

    @if($hasReturnStops)
        <div class="border-t border-input px-3 sm:px-5 pt-4 pb-1">
            <h4 class="text-sm font-semibold text-foreground mb-0">Terugweg</h4>
            <p class="text-xs text-muted-foreground mb-0 mt-0.5">
                School → huis
                @if($group->return_pickup_time)
                    · ophalen {{ substr((string) $group->return_pickup_time, 0, 5) }}
                @endif
                · +{{ (int) ($group->return_boarding_delay_minutes ?? 15) }} min instaptijd
            </p>
        </div>
        @include('taxi::admin.transport_groups.partials.return-route-summary')
    @elseif($group->has_return_trip ?? false)
        <div class="border-t border-input px-3 sm:px-5 py-5 text-sm text-muted-foreground">
            <h4 class="text-sm font-semibold text-foreground mb-2">Terugweg</h4>
            Terugweg staat aan, maar er is nog geen retourroute berekend.
            @can('rides.update')
            Sla de groep opnieuw op of voeg passagiers toe om de terugweg te berekenen.
            @endcan
        </div>
    @endif
@else
    <div class="px-3 sm:px-5 pb-5 text-sm text-muted-foreground">
        Nog geen route gepland.
        @can('rides.update')
        Open de routeplanner om weekdagen, stopvolgorde, tijden en vaste chauffeur in te stellen.
        @endcan
    </div>
@endif
